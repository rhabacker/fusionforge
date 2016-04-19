<?php
/**
 * FusionForge mailing lists
 *
 * Copyright 2002, Tim Perdue/GForge, LLC
 * Copyright 2003, Guillaume Smet
 * Copyright 2009, Roland Mas
 * Copyright 2012, Franck Villaume - TrivialDev
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require_once $gfcommon.'include/FFError.class.php';
require_once $gfcommon.'include/SysTasksQ.class.php';

class MailingList extends FFError {

	/**
	 * Associative array of data from db.
	 *
	 * @var	 array	$dataArray.
	 */
	var $dataArray;

	/**
	 * The Group object.
	 *
	 * @var	 object	$Group.
	 */
	var $Group;

	/**
	 * The mailing list id
	 *
	 * @var	int	$groupMailingListId
	 */
	var $groupMailingListId;

	/**
	 *  Constructor.
	 *
	 * @param	$Group
	 * @param	bool	$groupListId
	 * @param	bool	$dataArray
	 * @internal	param	\The $object Group object to which this mailing list is associated.
	 * @internal	param	\The $int group_list_id.
	 * @internal	param	\The $array associative array of data.
	 * @return	\MailingList
	 */
	function __construct(&$Group, $groupListId = false, $dataArray = false) {
		$this->Error();
		if (!$Group || !is_object($Group)) {
			$this->setError(_('No Valid Group Object'));
			return;
		}
		if ($Group->isError()) {
			$this->setError('MailingList: '.$Group->getErrorMessage());
			return;
		}
		$this->Group =& $Group;

		if ($groupListId) {
			$this->groupMailingListId = $groupListId;
			if (!$dataArray || !is_array($dataArray)) {
				if (!$this->fetchData($groupListId)) {
					return;
				}
			} else {
				$this->dataArray =& $dataArray;
				if ($this->dataArray['group_id'] != $this->Group->getID()) {
					$this->setError(_('group_id in db result does not match Group Object'));
					$this->dataArray = null;
					return;
				}
			}
			if (!$this->isPublic()) {
				$perm =& $this->Group->getPermission ();

				if (!$perm || !is_object($perm) || !$perm->isMember()) {
					$this->setPermissionDeniedError();
					$this->dataArray = null;
					return;
				}
			}
		}
	}

	/**
	 *    create - use this function to create a new entry in the database.
	 *
	 * @param	$listName
	 * @param	$description
	 * @param	string		$isPublic
	 * @param	bool		$creator_id
	 * @param	int		$is_external Pass (1) if it should be public (0) for private.
	 *
	 * @internal	param		\The $string name of the mailing list
	 * @internal	param		\The $string description of the mailing list
	 * @return	boolean		success.
	 */
	function create($listName, $description, $isPublic = MAIL__MAILING_LIST_IS_PUBLIC,$creator_id=false, $is_external=0) {
		//
		//	During the group creation, the current user_id will not match the admin's id
		//
		if (!$creator_id) {
			$creator_id=user_getid();
			if(!forge_check_perm ('project_admin', $this->Group->getID())) {
				$this->setPermissionDeniedError();
				return false;
			}
		}

		if(!$listName || strlen($listName) < MAIL__MAILING_LIST_NAME_MIN_LENGTH) {
			$this->setError(_('Must Provide List Name That Is 4 or More Characters Long'));
			return false;
		}

		$realListName = strtolower($this->Group->getUnixName().'-'.$listName);
		if (!preg_match('/^[a-z0-9\-_\.]*$/', $realListName)) {
			$this->setError(_('Invalid List Name')._(': ').$realListName);
			return false;
		}

		// '|' or '/' are valid chars in emails but are not allowed by mailman.
		if( preg_match('/[|\/]/', $realListName) ||
			!validate_email($realListName.'@'.forge_get_config('lists_host'))) {
			$this->setError(_('Invalid List Name')._(': ').
			$realListName.'@'.forge_get_config('lists_host'));
			return false;
		}

		$result = db_query_params ('SELECT 1 FROM mail_group_list WHERE lower(list_name)=$1',
					   array ($realListName)) ;

		if (db_numrows($result) > 0) {
			$this->setError(_('List Already Exists'));
			return false;
		}

		$result_forum_samename = db_query_params ('SELECT 1 FROM forum_group_list WHERE forum_name=$1 AND group_id=$2',
							  array ($listName,
								 $this->Group->getID())) ;

		if (db_numrows($result_forum_samename) > 0){
			$this->setError(_('Forum exists with the same name'));
			return false;
		}

		$listPassword = substr(md5(time() . util_randbytes()), 0, 16);

		db_begin();
		$result = db_query_params('INSERT INTO mail_group_list
					(group_id, list_name, is_public, password, list_admin, status, description)
					VALUES ($1, $2, $3, $4, $5, $6, $7)',
			array(
				$this->Group->getID(),
				$realListName,
				$isPublic,
				$listPassword,
				$creator_id,
				MAIL__MAILING_LIST_IS_REQUESTED,
				$description));

		if (!$result) {
			$this->setError(_('Error Creating mailing list')._(': ').db_error());
			db_rollback();
			return false;
		}

		// TODO: link 'mail_group_list' with 'systasks' for better reporting
		$systasksq = new SysTasksQ();
		$systasksq->add(SYSTASK_CORE, 'LISTS', $this->Group->getID());

		$this->groupMailingListId = db_insertid($result, 'mail_group_list', 'group_list_id');
		$this->fetchData($this->groupMailingListId);

		$user = user_get_object($creator_id);
		$userEmail = $user ? $user->getEmail() : "";
		if(empty($userEmail) || !validate_email($userEmail)) {
			$this->setInvalidEmailError();
			db_rollback();
			return false;
		} else {
			$mailBody = sprintf(_('A mailing list will be created on %1$s in one hour
and you are the list administrator.

This list is: %3$s@%2$s .

Your mailing list info is at:
%4$s .

List administration can be found at:
%5$s .

Your list password is: %6$s .
You are encouraged to change this password as soon as possible.

Thank you for registering your project with %1$s.'), forge_get_config ('forge_name'), forge_get_config('lists_host'), $realListName, $this->getExternalInfoUrl(), $this->getExternalAdminUrl(), $listPassword);
			$mailBody .= "\n\n";
			$mailBody .= sprintf(_('-- the %s staff'), forge_get_config ('forge_name'));
			$mailBody .= "\n";

			$mailSubject = sprintf(_('%s New Mailing List'), forge_get_config ('forge_name'));

			util_send_message($userEmail, $mailSubject, $mailBody);
		}

		db_commit();
		return true;
	}

	/**
	 * fetchData - re-fetch the data for this mailing list from the database.
	 *
	 * @param	int	$groupListId The list_id.
	 * @return	bool	success.
	 */
	function fetchData($groupListId) {
		$res = db_query_params ('SELECT * FROM mail_group_list WHERE group_list_id=$1 AND group_id=$2',
					array ($groupListId,
					       $this->Group->getID())) ;
		if (!$res || db_numrows($res) < 1) {
			$this->setError(_('Error Getting mailing list'));
			return false;
		}
		$this->dataArray = db_fetch_array($res);
		db_free_result($res);
		return true;
	}

	/**
	 * update - use this function to update an entry in the database.
	 *
	 * @param	$description
	 * @param	string		$isPublic
	 * @param	string		$status The description of the mailing list
	 * @param	int		$is_external Pass (1) if it should be public (0) for private
	 * @return	boolean		success.
	 */
	function update($description, $isPublic = MAIL__MAILING_LIST_IS_PUBLIC, $status = 'xyzzy', $is_external=0) {
		if(! forge_check_perm('project_admin', $this->Group->getID())) {
			$this->setPermissionDeniedError();
			return false;
		}

		// do not update the status if the mailing-list is not created yet
		if ($status == 'xyzzy'  || $this->getStatus() == MAIL__MAILING_LIST_IS_REQUESTED) {
			$status = $this->getStatus();
		}

		$res = db_query_params ('UPDATE mail_group_list SET is_public=$1, description=$2, status=$3
			                 WHERE group_list_id=$4 AND group_id=$5',
					array ($isPublic,
					       $description,
					       $status,
					       $this->groupMailingListId,
					       $this->Group->getID())) ;

		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(sprintf(_('Error On Update: %s'), db_error()));
			return false;
		}

		// TODO: link 'mail_group_list' with 'systasks' for better reporting
		$systasksq = new SysTasksQ();
		$systasksq->add(SYSTASK_CORE, 'LISTS', $this->Group->getID());

		return true;
	}

	/**
	 * getGroup - get the Group object this mailing list is associated with.
	 *
	 * @return	object	The Group object.
	 */
	function &getGroup() {
		return $this->Group;
	}

	/**
	 * getID - The id of this mailing list
	 *
	 * @return	int	The group_list_id #.
	 */
	function getID() {
		return $this->dataArray['group_list_id'];
	}


	/**
	 * isPublic - Is this mailing list open to the general public.
	 *
	 * @return	boolean	is_public.
	 */
	function isPublic() {
		return $this->dataArray['is_public'];
	}

	/**
	 * getName - get the name of this mailing list
	 *
	 * @return	string	The name of this mailing list
	 */
	function getName() {
		return $this->dataArray['list_name'];
	}


	/**
	 * getDescription - get the description of this mailing list
	 *
	 * @return	string	The description.
	 */
	function getDescription() {
		return $this->dataArray['description'];
	}

	/**
	 * getPassword - get the password to administrate the mailing list
	 *
	 * @return	string	The password
	 */
	function getPassword() {
		return $this->dataArray['password'];
	}

	/**
	 * getListAdmin - get the user who is the admin of this mailing list
	 *
	 * @return	User	The admin user
	 */
	function getListAdmin() {
		return user_get_object($this->dataArray['list_admin']);
	}

	/**
	 * getStatus - get the status of this mailing list
	 *
	 * @return int The status
	 */
	function getStatus() {
		return $this->dataArray['status'];
	}

	/**
	 * getListEmail - get the email of this mailing list
	 *
	 * @return	string	The email
	 */
	function getListEmail() {
		return $this->getName().'@'.forge_get_config('lists_host');
	}

	/**
	 * getArchivesUrl - get the url to see the archives of the list
	 *
	 * @return	string	url of the archives
	 */
	function getArchivesUrl() {
		$host = util_url_prefix() . forge_get_config('lists_host');
		if ($this->isPublic()) {
			return $host . '/pipermail/' . $this->getName() . '/';
		} else {
			return $host . '/mailman/private/' . $this->getName() . '/';
		}
	}

	/**
	 * getExternalInfoUrl - get the url to subscribe/unsubscribe
	 *
	 * @return	string	url of the info page
	 */
	function getExternalInfoUrl() {
		return util_url_prefix() . forge_get_config('lists_host') .
		    '/mailman/listinfo/' . $this->getName();
	}

	/**
	 * getExternalAdminUrl - get the url to admin the list with the external tools used
	 *
	 * @return	string	url of the admin
	 */
	function getExternalAdminUrl() {
		return util_url_prefix() . forge_get_config('lists_host') .
		    '/mailman/admin/' . $this->getName();
	}

	/**
	 * delete - permanently delete this mailing list
	 *
	 * @param	boolean	$sure I'm Sure.
	 * @param	boolean	$really_sure I'm Really Sure.
	 * @return	boolean	success;
	 */
	function delete($sure,$really_sure) {

		if (!$sure || !$really_sure) {
			$this->setMissingParamsError(_('Please tick all checkboxes.'));
			return false;
		}
		if (!forge_check_perm ('project_admin', $this->Group->getID())) {
			$this->setPermissionDeniedError();
			return false;
		}
		if (!$this->getID() || !$this->getName()) {
			$this->setError('ID or Name null in MailingList object');
			return false;
		}
		$res = db_query_params ('INSERT INTO deleted_mailing_lists (mailing_list_name,delete_date,isdeleted) VALUES ($1,$2,$3)',
					array ($this->getName(),
					       time(),
					       0)) ;
		if (!$res) {
			$this->setError('Could Not Insert Into Delete Queue: '.db_error());
			return false;
		}
		$res = db_query_params ('DELETE FROM mail_group_list WHERE group_list_id=$1',
					array ($this->getID())) ;
		if (!$res) {
			$this->setError('Could Not Delete List: '.db_error());
			return false;
		}

		// TODO: link 'mail_group_list' with 'systasks' for better reporting
		$systasksq = new SysTasksQ();
		$systasksq->add(SYSTASK_CORE, 'LISTS', $this->Group->getID());

		return true;
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
