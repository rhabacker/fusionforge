<?php
/**
 * FusionForge plugin system
 *
 * Copyright 2002, Roland Mas
 * Copyright 2010, Franck Villaume - Capgemini
 * Portions Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Portions Copyright 2010 (c) Mélanie Le Bail
 * http://fusionforge.org
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published
 * by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 * 
 * FusionForge is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FusionForge; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
 * USA
 */

class Plugin extends Error {
	var $name;
	var $hooks;

	/**
	 * Plugin() - constructor
	 *
	 * @param	int	id
	 */
	function Plugin($id=0) {
		$this->Error();
		$this->name = false;
		$this->hooks = array();
	}

	/**
	 * GetHooks() - get list of hooks to subscribe to.
	 *
	 * @return	array	List of strings.
	 */
	function GetHooks() {
		return $this->hooks;
	}
	/**
	 * _addHooks() - add a hook to the list of hooks.
	 *
	 * @return	string	name of the added hook
	 */
	function _addHook($name) {
		return $this->hooks[]=$name;
	}

	/**
	 * GetName() - get plugin name.
	 *
	 * @return	string	the plugin name.
	 */
	function GetName() {
		return $this->name;
	}

	/**
	 * GetInstallDir() - get installation dir for the plugin.
	 *
	 * @return	string	the directory where the plugin should be linked.
	 */
	function GetInstallDir() {
		if (isset($this->installdir) && $this->installdir)
			return $this->installdir;
		else
			return 'plugins/'.$this->name;
	}

	/**
	 * provide() - return true if plugin provides the feature.
	 *
	 * @return	bool	if feature is provided or not.
	 */
	function provide($feature) {
		return (isset($this->provides[$feature]) && $this->provides[$feature]);
	}

	/**
	 * Added for Codendi compatibility
	 * getPluginPath() - get installation dir for the plugin.
	 *
	 * @return	string	the directory where the plugin should be linked.
	 */
	function getPluginPath() {
		if (isset($this->installdir) && $this->installdir)
			return $this->installdir;
		else
			return 'plugins/'.$this->name;
	}

	/**
	 * CallHook() - call a particular hook.
	 *
	 * @param	string	the "handle" of the hook.
	 * @param	array	array of parameters to pass the hook.
	 * @return	bool	true only
	 */
	function CallHook($hookname, &$params) {
		return true;
	}

	/**
	 * getGroups - get a list of all groups using a plugin.
	 *
	 * @return	array	array containing group objects.
	 */
	function getGroups() {
		$result = array();
		$res = db_query_params('SELECT group_plugin.group_id
					FROM group_plugin, plugins
					WHERE group_plugin.plugin_id=plugins.plugin_id
					AND plugins.plugin_name=$1
					ORDER BY group_plugin.group_id ASC',
					array($this->name));
		$rows = db_numrows($res);
		
		for ($i=0; $i<$rows; $i++) {
			$group_id = db_result($res,$i,'group_id');
			$result[] = group_get_object($group_id);
		}
		return $result;
	}

	/*
	 * getThemePath - returns the directory of the theme for this plugin
	 *
	 * @return	string	the directory
	 */
	function getThemePath(){
		return util_make_url('plugins/'.$this->name.'/themes/default');
	}

	function registerRoleValues(&$params, $values) {
		$role =& $params['role'];
	}

	function groupisactivecheckbox(&$params) {
		//Check if the group is active
		// this code creates the checkbox in the project edit public info page to activate/deactivate the plugin
		$display = 1;
		$title = _('current plugin status is:').' '.forge_get_config('plugin_status', $this->name);
		$imgStatus = 'plugin_status_valid.png';
		if ( forge_get_config('plugin_status',$this->name) !== 'valid' ) {
			$display = 0;
			$imgStatus = 'plugin_status_broken.png';
		}
		if ( forge_get_config('installation_environment') === 'development' ) {
			$display = 1;
		}
		if ($display) {
			$group = group_get_object($params['group']);
			$flag = strtolower('use_'.$this->name);
			echo '<tr>';
			echo '<td>';
			echo ' <input type="checkbox" name="'.$flag.'" value="1" ';
			// checked or unchecked?
			if ($group->usesPlugin($this->name)) {
				echo 'checked="checked"';
			}
			echo ' /><br/>';
			echo '</td>';
			echo '<td>';
			echo '<strong>'. sprintf(_('Use %s Plugin'), $this->text) .'</strong>';
			echo html_image($imgStatus, '16', '16',array('alt'=>$title, 'title'=>$title));
			echo '</td>';
			echo '</tr>';
		}
	}

	/*
	 * @return	bool	actually only true ...
	 */
	function groupisactivecheckboxpost(&$params) {
		// this code actually activates/deactivates the plugin after the form was submitted in the project edit public info page
		$group = group_get_object($params['group']);
		$flag = strtolower('use_'.$this->name);
		if ( getIntFromRequest($flag) == 1 ) {
			$group->setPluginUse($this->name);
		} else {
			$group->setPluginUse($this->name, false);
		}
		return true;
	}

	function userisactivecheckbox(&$params) {
		//check if user is active
		// this code creates the checkbox in the user account manteinance page to activate/deactivate the plugin
		$display = 1;
		$title = _('current plugin status is:').' '.forge_get_config('plugin_status', $this->name);
		$imgStatus = 'plugin_status_valid.png';
		if ( forge_get_config('plugin_status', $this->name) !== 'valid' ) {
			$display = 0;
			$imgStatus = 'plugin_status_broken.png';
		}
		if ( forge_get_config('installation_environment') === 'development' ) {
			$display = 1;
		}
		if ($display) {
			$user = $params['user'];
			$flag = strtolower('use_'.$this->name);
			echo '<tr>';
			echo '<td>';
			echo ' <input type="checkbox" name="'.$flag.'" value="1" ';
			// checked or unchecked?
			if ( $user->usesPlugin($this->name)) {
				echo 'checked="checked"';
			}
			echo ' />    '. sprintf(_('Use %s Plugin'), $this->text);
			echo html_image($imgStatus, '16', '16',array('alt'=>$title, 'title'=>$title));
			echo '</td>';
			echo '</tr>';
		}
	}

	function userisactivecheckboxpost(&$params) {
		// this code actually activates/deactivates the plugin after the form was submitted in the user account manteinance page
		$user = $params['user'];
		$flag = strtolower('use_'.$this->name);
		if (getIntFromRequest($flag) == 1) {
			$user->setPluginUse($this->name);
		} else {
			$user->setPluginUse($this->name, false);
		}
	}
}

class PluginSpecificRoleSetting {
	var $role;
	var $name = '';
	var $section = '';
	var $values = array();
	var $default_values = array();
	var $global = false;

	function PluginSpecificRoleSetting(&$role, $name, $global = false) {
		$this->global = $global;
		$this->role =& $role;
		$this->name = $name;
	}
	
	function SetAllowedValues($values) {
		$this->role->role_values = array_replace_recursive($this->role->role_values,
								   array($this->name => $values));
		if ($this->global) {
			$this->role->global_settings[] = $this->name;
		}
	}

	function SetDefaultValues($defaults) {
		foreach ($defaults as $rname => $v) {
			$this->role->defaults[$rname][$this->name] = $v;
		}
	}

	function setValueDescriptions($descs) {
		global $rbac_permission_names ;
		foreach ($descs as $k => $v) {
			$rbac_permission_names[$this->name.$k] = $v;
		}
	}

	function setDescription($desc) {
		global $rbac_edit_section_names ;
		$rbac_edit_section_names[$this->name] = $desc;
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
