<?php
/** External authentication via OpenID for FusionForge
 * Copyright 2011, Roland Mas
 * Copyright 2011, Olivier Berger & Institut Telecom
 *
 * This program was developped in the frame of the COCLICO project
 * (http://www.coclico-project.org/) with financial support of the Paris
 * Region council.
 *
 * This file is part of FusionForge
 *
 * This plugin, like FusionForge, is free software; you can redistribute it
 * and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FusionForge; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  US
 * 
 */

require_once $GLOBALS['gfcommon'].'include/User.class.php';

// from lightopenid (http://code.google.com/p/lightopenid/)
//require_once 'openid.php';

/**
 * Authentication manager for FusionForge CASification
 *
 */
class AuthOpenIDPlugin extends ForgeAuthPlugin {
	var $openid;
	
	var $openid_identity;
	
	function AuthOpenIDPlugin () {
		global $gfconfig;
		$this->ForgeAuthPlugin() ;
		$this->name = "authopenid";
		$this->text = "OpenID authentication";

		$this->_addHook('display_auth_form');
		$this->_addHook("check_auth_session");
		$this->_addHook("fetch_authenticated_user");
		$this->_addHook("close_auth_session");

		$this->saved_login = '';
		$this->saved_user = NULL;

		$this->openid = FALSE;
		
		$this->openid_identity = FALSE;
		
		$this->declareConfigVars();
	}

	function startSession($username) {
		if ($this->isSufficient() || $this->isRequired()) {
			$username = $this->getUserIdFromOpenIDIdentity($username);
			$params = array();
			$params['username'] = $username;
			$params['event'] = 'login';
			plugin_hook('sync_account_info', $params);
			$user = user_get_object_by_name($username);
			$this->saved_user = $user;
			$this->setSessionCookie();
			return $user;
		} else {
			return false;
		}
	}
	
	/**
	 * Display a form to input credentials
	 * @param unknown_type $params
	 * @return boolean
	 */
	function displayAuthForm(&$params) {
		if (!$this->isRequired() && !$this->isSufficient()) {
			return true;
		}
		$return_to = $params['return_to'];

		$result = '';

		$result .= '<p>';
		$result .= _('Cookies must be enabled past this point.');
		$result .= '</p>';
		
		$result .= '<form action="' . util_make_url('/plugins/authopenid/post-login.php') . '" method="post">
<input type="hidden" name="form_key" value="' . form_generate_key() . '"/>
<input type="hidden" name="return_to" value="' . htmlspecialchars(stripslashes($return_to)) . '" />
Your OpenID identifier: <input type="text" name="openid_identifier" /> 
<input type="submit" name="login" value="' . _('Login via OpenID') . '" />
</form>';

		$params['html_snippets'][$this->name] = $result;

	}

    /**
	 * Is there a valid session?
	 * @param unknown_type $params
	 */
	
	function checkAuthSession(&$params) {
		$this->saved_user = NULL;
		$user = NULL;

		if (isset($params['auth_token']) && $params['auth_token'] != '') {
			$user_id = $this->checkSessionToken($params['auth_token']);
		} else {
			$user_id = $this->checkSessionCookie();
		}
		if ($user_id) {
			$user = user_get_object($user_id);
		} else {
			if ($this->openid && $this->openid->identity) {
				$user_id = $this->getUserIdFromOpenIDIdentity($this->openid->identity);
				if ($user_id) {
					$user = $this->startSession($user_id);
				}
			}
		}
		
		if ($user) {
			if ($this->isSufficient()) {
				$this->saved_user = $user;
				$params['results'][$this->name] = FORGE_AUTH_AUTHORITATIVE_ACCEPT;
				
			} else {
				$params['results'][$this->name] = FORGE_AUTH_NOT_AUTHORITATIVE;
			}
		} else {
			if ($this->isRequired()) {
				$params['results'][$this->name] = FORGE_AUTH_AUTHORITATIVE_REJECT;
			} else {
				$params['results'][$this->name] = FORGE_AUTH_NOT_AUTHORITATIVE;
			}
		}
	}

	protected function getUserIdFromOpenIDIdentity($openid_identity) {
		$user_id = FALSE;
		$res = db_query_params('SELECT user_id FROM plugin_authopenid_user_identities WHERE openid_identity=$1',
							    array($openid_identity));
		if($res) {
			$row = db_fetch_array_by_row($res, 0);
			if($row) {
				$user_id = $row['user_id'];
			}
		}
		return $user_id;
	}
	/**
	 * What GFUser is logged in?
	 * @param unknown_type $params
	 */
	/*

	function closeAuthSession($params) {
		$this->initCAS();

		if ($this->isSufficient() || $this->isRequired()) {
			$this->unsetSessionCookie();
			// logs user out from CAS
			// TODO : make it optional to not mess with other apps' SSO sessions with CAS
			phpCAS::logoutWithRedirectService(util_make_url('/'));
		} else {
			return true;
		}
	}
*/
	/**
	 * Terminate an authentication session
	 * @param unknown_type $params
	 * @return boolean
	 */
	protected function declareConfigVars() {
		parent::declareConfigVars();
		
		// Change vs default 
		forge_define_config_item ('required', $this->name, 'yes');
		forge_set_config_item_bool ('required', $this->name) ;

		// Change vs default
		forge_define_config_item ('sufficient', $this->name, 'yes');
		forge_set_config_item_bool ('sufficient', $this->name) ;
	
	}

}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
