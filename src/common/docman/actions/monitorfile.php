<?php
/**
 * FusionForge Documentation Manager
 *
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright 2012-2013, Franck Villaume - TrivialDev
 * http://fusionforge.org
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

/* please do not add require here : use www/docman/index.php to add require */
/* global variables used */
global $dirid; //id of doc_group
global $group_id; // id of group
global $LUSER; // User object

if (!forge_check_perm('docman', $group_id, 'read')) {
	$return_msg = _('Document Manager Action Denied.');
	session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&warning_msg='.urlencode($return_msg));
}

$arr_fileid = explode(',', getStringFromRequest('fileid'));
$option = getStringFromRequest('option');
$return_msg = _('Document(s)').' ';
switch ($option) {
	case "add": {
		foreach ($arr_fileid as $fileid) {
			if (!empty($fileid)) {
				$d = new Document($g, $fileid);
				$return_msg .= $d->getFilename().' ';

				if ($d->isError())
					session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&error_msg='.urlencode($d->getErrorMessage()));

				if (!$d->addMonitoredBy($LUSER->getID()))
					session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&error_msg='.urlencode($d->getErrorMessage()));
			} else {
				$warning_msg = _('No action to perform.');
				session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&warning_msg='.urlencode($warning_msg));
			}
		}
		$return_msg .= _('Monitoring started.');
		break;
	}
	case "remove": {
		foreach ($arr_fileid as $fileid) {
			if (!empty($fileid)) {
				$d = new Document($g, $fileid);
				$return_msg .= $d->getFilename().' ';

				if ($d->isError())
					session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&error_msg='.urlencode($d->getErrorMessage()));

				if (!$d->removeMonitoredBy($LUSER->getID()))
					session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&error_msg='.urlencode($d->getErrorMessage()));

			} else {
				$warning_msg = _('No action to perform');
				session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&warning_msg='.urlencode($warning_msg));
			}
		}
		$return_msg .= _('Monitoring stopped.');
		break;
	}
	default: {
		$error_msg = _('Docman: monitoring action unknown.');
		session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&error_msg='.urlencode($error_msg));
	}
}

session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&feedback='.urlencode($return_msg));
