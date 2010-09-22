<?php
/**
 * Role Editing Page
 *
 * Copyright 2010, Roland Mas
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FusionForge; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('../env.inc.php');
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'admin/admin_utils.php';
require_once $gfwww.'include/role_utils.php';

site_admin_header(array('title'=>_('Site Admin')));
echo '<h1>' . _('Site Admin') . '</h1>';

$role_id = getStringFromRequest('role_id');
$data = getStringFromRequest('data');

if (getStringFromRequest('add')) {
	$role_name = trim(getStringFromRequest('role_name')) ;
	$role = new Role (NULL) ;
	$role_id=$role->createDefault($role_name) ;
} else {
	$role = RBACEngine::getInstance()->getRoleById($role_id) ;
}

if (!$role || !is_object($role)) {
	exit_error('Error',_('Could Not Get Role'));
} elseif ($role->isError()) {
	exit_error('Error',$role->getErrorMessage());
}

$old_data = $role->getGlobalSettings () ;
$new_data = array () ;

if (!is_array ($data)) {
	$data = array () ;
}
foreach ($old_data as $section => $values) {
	if (!array_key_exists ($section, $data)) {
		continue ;
	}
	foreach ($values as $ref_id => $val) {
		if (!array_key_exists ($ref_id, $data[$section])) {
			continue ;
		}
		$new_data[$section][$ref_id] = $data[$section][$ref_id] ;
	}
}
$data = $new_data ;

if (getStringFromRequest('submit')) {
	if ($role instanceof RoleExplicit) {
		$role_name = trim(getStringFromRequest('role_name'));
	} else {
		$role_name = $role->getName() ;
	}
	if (!$role_name) {
		$feedback .= ' Missing Role Name ';
	} else {
		if (!$role_id) {
			$role_id=$role->create($role_name,$data);
			if (!$role_id) {
				$feedback .= $role->getErrorMessage();
			} else {
				$feedback = _('Successfully Created New Role');
			}
		} else {
			if (!$role->update($role_name,$data)) {
				$feedback .= $role->getErrorMessage();
			} else {
				$feedback = _('Successfully Updated Role');
			}
		}
	}
}

echo '
<p>
<form action="'.getStringFromServer('PHP_SELF').'?role_id='. $role_id .'" method="post">';

if ($role instanceof RoleExplicit) {
	echo '<p><strong>'._('Role Name').'</strong><br /><input type="text" name="role_name" value="'.$role->getName().'"></p>';
} else {
	echo '<p><strong>'._('Role Name').'</strong><br />'.$role->getName().'</p>';
}

$titles[]=_('Section');
$titles[]=_('Subsection');
$titles[]=_('Setting');

setup_rbac_strings () ;

echo $HTML->listTableTop($titles);

//
//	Get the keys for this role and interate to build page
//
//	Everything is built on the multi-dimensial arrays in the Role object
//
$j = 0;

$keys = array_keys($role->getGlobalSettings ()) ;
$keys2 = array () ;
foreach ($keys as $key) {
	if (in_array ($key, $role->global_settings)) {
		$keys2[] = $key ;
	}
}
$keys = $keys2 ;

for ($i=0; $i<count($keys); $i++) {
	echo '<tr '. $HTML->boxGetAltRowStyle($j++) . '>
		<td colspan="2"><strong>'.$rbac_edit_section_names[$keys[$i]].'</strong></td>
		<td>';
	echo html_build_select_box_from_assoc($role->getRoleVals($keys[$i]), "data[".$keys[$i]."][-1]", $role->getVal($keys[$i],-1), false, false ) ;
	echo '</td>
		</tr>';
	
}

echo $HTML->listTableBottom();

echo '<p><input type="submit" name="submit" value="'._('Submit').'" /></p>
</form>';

site_admin_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
