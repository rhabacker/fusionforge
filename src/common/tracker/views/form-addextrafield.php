<?php
/**
 * Tracker Facility
 *
 * Copyright 2010 (c) FusionForge Team
 * Copyright 2014-2015, Franck Villaume - TrivialDev
 * Copyright 2016, Stéphane-Eymeric Bredthauer - TrivialDev
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

global $HTML;

//
//  FORM TO BUILD SELECTION BOXES
//

$title = sprintf(_('Manage Custom Fields for %s'), $ath->getName());
$ath->adminHeader(array('title'=>$title, 'modal'=>1));

/*
	List of possible user built Selection Boxes for an ArtifactType
*/
$efarr = $ath->getExtraFields(array(),true,true);
$eftypes=ArtifactExtraField::getAvailableTypes();
$keys=array_keys($efarr);
$rows=count($keys);
if ($rows > 0) {

	$title_arr=array();
	$title_arr[]=_('Custom Fields Defined');
	$title_arr[]=_('Type');
	$title_arr[]=_('Enabled');
	$title_arr[]=_('Required');
	$title_arr[]=_('Shown on Submit');
	$title_arr[]=_('Auto Assign');
	$title_arr[]=_('Depend on');
	$title_arr[]=_('Elements Defined');
	$title_arr[]=_('Add Options');
	$autoAssignFieldId = $ath->getAutoAssignField();
	echo $HTML->listTableTop($title_arr);
	$rownb = 0;
	for ($k=0; $k < $rows; $k++) {
		$i=$keys[$k];
		$rownb++;
		$id=str_replace('@','',$efarr[$i]['alias']);
		echo '<tr id="field-'.$id.'" '. $HTML->boxGetAltRowStyle($rownb) .">\n".
			'<td>'.$efarr[$i]['field_name'].(($efarr[$i]['is_required']) ? utils_requiredField() : '').
				util_make_link('/tracker/admin/?update_box=1&id='.$efarr[$i]['extra_field_id'].'&group_id='.$group_id.'&atid='.$ath->getID(), ' ['._('Edit').']').
				util_make_link('/tracker/admin/?deleteextrafield=1&id='.$efarr[$i]['extra_field_id'].'&group_id='.$group_id.'&atid='. $ath->getID(), ' ['._('Delete').']').
				util_make_link('/tracker/admin/?copy_opt=1&id='.$efarr[$i]['extra_field_id'].'&group_id='.$group_id.'&atid='. $ath->getID(), ' ['._('Copy').']').
				"</td>\n";
		echo '<td>'.$eftypes[$efarr[$i]['field_type']]."</td>\n";
		if ($efarr[$i]['is_disabled'] == 0) {
			echo '<td class="align-center">'.html_image("ic/check.png",'15','13').'</td>'."\n";
		} else {
			echo '<td></td>'."\n";
		}
		if ($efarr[$i]['is_required'] == 1) {
			echo '<td class="align-center">'.html_image("ic/check.png",'15','13').'</td>'."\n";
		} else {
			echo '<td></td>'."\n";
		}
		if ($efarr[$i]['is_hidden_on_submit'] == 0) {
			echo '<td class="align-center">'.html_image("ic/check.png",'15','13').'</td>'."\n";
		} else {
			echo '<td></td>'."\n";
		}
		if ($autoAssignFieldId==$i) {
			echo '<td class="align-center">'.html_image("ic/check.png",'15','13').'</td>'."\n";
		} else {
			echo '<td></td>'."\n";
		}
		$parentFieldId = $efarr[$i]['parent'];
		if ($parentFieldId!=100) {
			echo '<td>'.$efarr[$parentFieldId]['field_name'].'</td>'."\n";
		} else {
			echo '<td></td>'."\n";
		}
		/*
			List of possible options for a user built Selection Box
		*/
		$elearray = $ath->getExtraFieldElements($efarr[$i]['extra_field_id']);
		if ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_USER && !isset($roles)) {
			$rolesarray = array();
			$roles = $ath->getGroup()->getRoles();
			foreach ($roles as $role) {
				$rolesarray[$role->getID()]=$role->getName();
			}
		}

		if (!empty($elearray)) {
			$optrows=count($elearray);

			echo '<td>';
			for ($j=0; $j <$optrows; $j++) {

				if ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_USER) {
					echo $rolesarray[$elearray[$j]['element_name']];
				} else {
					echo $elearray[$j]['element_name'];
					echo util_make_link('/tracker/admin/?update_opt=1&id='.$elearray[$j]['element_id'].'&group_id='.$group_id.'&atid='.$ath->getID().'&boxid='.$efarr[$i]['extra_field_id'], ' ['._('Edit').']');
				}
				echo util_make_link('/tracker/admin/?delete_opt=1&id='.$elearray[$j]['element_id'].'&group_id='.$group_id.'&atid='.$ath->getID().'&boxid='.$efarr[$i]['extra_field_id'], ' ['._('Delete').']');
				echo '<br />';
			}
		} else {
			echo '<td>';
		}

		echo "</td>\n";
		echo "<td>\n";
		if ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_SELECT
			|| $efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_RADIO
			|| $efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_CHECKBOX
			|| $efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_MULTISELECT
			|| $efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_STATUS) {
			echo util_make_link('/tracker/admin/?add_opt=1&boxid='.$efarr[$i]['extra_field_id'].'&group_id='.$group_id.'&atid='.$ath->getID(), '['._('Add/Reorder choices').']');
		}
		if ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_USER) {
					echo util_make_link('/tracker/admin/?add_opt=1&boxid='.$efarr[$i]['extra_field_id'].'&group_id='.$group_id.'&atid='.$ath->getID(), '['._('Add/remove roles for user choices').']');
				}
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo $HTML->listTableBottom();
	echo $HTML->addRequiredFieldsInfoBox();
} else {
	echo $HTML->warning_msg(_('You have not defined any custom fields'));
}

echo html_e('h2', array(), _('Add New Custom Field'));

echo $HTML->openForm(array('action' => '/tracker/admin/?group_id='.$group_id.'&atid='.$ath->getID(), 'method' => 'post'));

echo html_ao('p');
echo html_e('input', array('type'=>'hidden', 'name'=>'add_extrafield', 'value'=>'y'));

echo html_e('strong', array(), _('Custom Field Name').utils_requiredField()._(':')).html_e('br');
echo html_e('input', array('type'=>'text', 'name'=>'name', 'value'=>'', 'size'=>'15', 'maxlength'=>'30', 'required'=>'required'));
echo html_ac(html_ap() - 1);

echo html_ao('p');
echo html_e('strong', array(), _('Field alias')._(':')).html_e('br');
echo html_e('input', array('type'=>'text', 'name'=>'alias', 'value'=>'', 'size'=>'15', 'maxlength'=>'30'));
echo html_ac(html_ap() - 1);

echo html_ao('p');
echo html_e('strong', array(), _('Description')._(':')).html_e('br');
echo html_e('input', array('type'=>'text', 'name'=>'description', 'value'=>'', 'size'=>'50', 'maxlength'=>'255'));
echo html_ac(html_ap() - 1);

echo html_ao('p');
echo html_build_checkbox('is_disabled', false, false);
echo html_e('label', array('for'=>'is_disabled'), _('Field is disabled'));
echo html_ac(html_ap() - 1);

echo html_ao('p');
echo html_build_checkbox('is_required', false, false);
echo html_e('label', array('for'=>'is_required'), _('Field is mandatory'));
echo html_ac(html_ap() - 1);

echo html_ao('p');
echo html_build_checkbox('is_hidden_on_submit', false, false);
echo html_e('label', array('for'=>'is_hidden_on_submit'), _('Hide this Field on a new submission'));
echo html_ac(html_ap() - 1);

$jsvariable ="
	var size = '"._("Size")."';
	var maxLength = '". _("Maxlength")."';
	var rows = '"._("Rows")."';
	var columns = '". _("Columns")."';";
$javascript = <<<'EOS'
	$("p[class^='for-']").hide()
	$("input[value=1]").on('change', function(){
		$("p.for-select").show();
		$("p[class^='for-']:not(.for-select)").hide();
	});
	$("input[value=2]").on('change', function(){
		$("p.for-check").show();
		$("p[class^='for-']:not(.for-check)").hide();
	});
	$("input[value=3]").on('change', function(){
		$("p.for-radio").show();
		$("p[class^='for-']:not(.for-radio)").hide();
	});
	$("input[value=4]").on('change', function(){
		$("label[for='attribute1']").text(size);
		$("label[for='attribute2']").text(maxLength);
		$("p.for-text").show();
		$("p[class^='for-']:not(.for-text)").hide();
	});
	$("input[value=5]").on('change', function(){
		$("p.for-multiselect").show();
		$("p[class^='for-']:not(.for-multiselect)").hide();
	});
	$("input[value=6]").on('change', function(){
		$("label[for='attribute1']").text(rows);
		$("label[for='attribute2']").text(columns);
		$("p.for-textarea").show();
		$("p[class^='for-']:not(.for-textarea)").hide();
	});
	$("input[value=9]").on('change', function(){
		$("label[for='attribute1']").text(size);
		$("label[for='attribute2']").text(maxLength);
		$("p.for-relation").show();
		$("p[class^='for-']:not(.for-relation)").hide();
	});
	$("input[value=10]").on('change', function(){
		$("label[for='attribute1']").text(size);
		$("label[for='attribute2']").text(maxLength);
		$("p.for-integer").show();
		$("p[class^='for-']:not(.for-integer)").hide();
	});
	$("input[value=14]").on('change', function(){
		$("p.for-user").show();
		$("p[class^='for-']:not(.for-user)").hide();
	});

EOS;
echo html_e('script', array( 'type'=>'text/javascript'), '//<![CDATA['."\n".'$(function(){'.$jsvariable."\n".$javascript.'});'."\n".'//]]>');

echo html_ao('p');
echo html_e('strong', array(), _('Type of custom field').utils_requiredField()._(':')).html_e('br');
if ($ath->usesCustomStatuses()) {
	unset($eftypes[ARTIFACT_EXTRAFIELDTYPE_STATUS]);
}
$vals = array_keys($eftypes);
$texts = array_values($eftypes);
echo html_build_radio_buttons_from_arrays($vals, $texts, 'field_type', '', false, '', false ,'', false, array('required'=>'required') );
echo html_ac(html_ap() - 1);

echo html_ao('p', array('class'=>'for-text for-textarea for-integer for-relation'));
echo html_e('label', array('for'=>'attribute1'), _('Size')._(':'));
echo html_e('input', array('type'=>'text', 'name'=>'attribute1', 'value'=>'20', 'size'=>'2', 'maxlength'=>'2')).html_e('br');
echo html_e('label', array('for'=>'attribute2'), _('Maxlength')._(':'));
echo html_e('input', array('type'=>'text', 'name'=>'attribute2', 'value'=>'80', 'size'=>'2', 'maxlength'=>'2')).html_e('br');
echo html_ac(html_ap() - 1);

echo html_ao('p', array('class'=>'for-text for-textarea for-integer'));

echo html_ac(html_ap() - 1);

echo html_ao('p', array('class'=>'for-text'));
echo _('Pattern');
echo html_e('input', array('type'=>'text', 'name'=>'pattern', 'value'=>'', 'size'=>'50', 'maxlength'=>'255')).html_e('br');
echo html_ac(html_ap() - 1);

echo html_ao('p', array('class'=>'for-select for-multiselect for-radio for-check'));
echo html_build_checkbox('hide100', false, false);
echo html_e('label', array('for'=>'hide100'), _('Hide the default none value'));
echo html_e('br');
echo html_e('label', array('for'=>'show100label'), _('Label for the none value'));
echo html_e('input', array('type'=>'text', 'name'=>'show100label', 'value'=>_('none'), 'size'=>'30')).html_e('br');
echo html_ac(html_ap() - 1);

echo html_ao('p', array('class'=>'for-select for-multiselect for-radio for-check'));
$pfarr = $ath->getExtraFields(array(ARTIFACT_EXTRAFIELDTYPE_RADIO, ARTIFACT_EXTRAFIELDTYPE_CHECKBOX,ARTIFACT_EXTRAFIELDTYPE_SELECT,ARTIFACT_EXTRAFIELDTYPE_MULTISELECT), false, true);
$parentField = array();
if (is_array($pfarr)) {
	foreach ($pfarr as $pf) {
		$parentField[$pf['extra_field_id']] = $pf['field_name'];
	}
}
asort($parentField,SORT_FLAG_CASE | SORT_STRING);
echo html_e('label', array('for'=>'parent'), _('Parent Field'));
echo html_build_select_box_from_arrays(array_keys($parentField), array_values($parentField), 'parent', null, true, 'none').html_e('br');
echo html_ac(html_ap() - 1);

echo html_ao('p', array('class'=>'for-select for-multiselect for-radio for-check'));
echo html_build_checkbox('autoassign', false, false);
echo html_e('label', array('for'=>'autoassign'), _('Field that triggers auto-assignment rules'));
echo html_ac(html_ap() - 1);

echo $HTML->warning_msg(_('Warning: this add new custom field'));

echo html_ao('p');
echo html_e('input', array('type'=>'submit', 'name'=>'post_changes', 'value'=>_('Add Custom Field')));
echo html_ac(html_ap() - 1);

echo $HTML->closeForm();

echo html_e('h2', array(), _('Custom Field Rendering Template'));
echo html_ao('p');
echo util_make_link('/tracker/admin/?edittemplate=1&group_id='.$group_id.'&atid='.$ath->getID(), _('Edit template')).html_e('br');
echo util_make_link('/tracker/admin/?deletetemplate=1&group_id='.$group_id.'&atid='.$ath->getID(), _('Delete template'));
echo html_ac(html_ap() - 1);

$ath->footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
