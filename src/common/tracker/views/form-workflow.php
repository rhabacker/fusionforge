<?php
/**
 * Update Artifact Type Form
 *
 * Copyright 2010, FusionForge Team
 * Copyright 2015, Franck Villaume - TrivialDev
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

require_once 'common/tracker/ArtifactWorkflow.class.php';

global $HTML;

$has_error = false;
$efarr = $ath->getExtraFields(array(ARTIFACT_EXTRAFIELDTYPE_STATUS));
if (count($efarr) === 0) {
	$has_error = true;
	$error_msg .= _('To create a workflow, you need first to create a custom field of type “Status”.');
} elseif (count($efarr) !== 1) {
	// Internal error.
	$has_error = true;
	$error_msg .= _('Internal error: Illegal number of status fields (WKFL01).');
}

$ath->adminHeader(array('title'=> _('Configure Workflow'),
	'pagename'=>'tracker_admin_customize_liste',
	'titlevals'=>array($ath->getName())));

/*
	List of possible user built Selection Boxes for an ArtifactType
*/
if (!$has_error) {

	$keys=array_keys($efarr);
	$field_id = $keys[0];
	$field_name = $efarr[$field_id]['field_name'];

	$atw = new ArtifactWorkflow($ath, $field_id);

	$elearray = $ath->getExtraFieldElements($field_id);
	$states = $elearray;

?>

	<h2><?php printf(_('Allowed initial values for the %s field'), $field_name) ?></h2>
<?php
echo $HTML->openForm(array('action' => '/tracker/admin/?group_id='.$group_id.'&amp;atid='.$ath->getID(), 'method' => 'post'));
?>
	<input type="hidden" name="field_id" value="<?php echo $field_id ?>" />
	<input type="hidden" name="workflow" value="1" />

<?php
	$from = _('From').' ';
	$to = _('To').' ';
	$init = _('Initial values').' ';

	$title_arr=array();
	$to_title_arr=array();
	$class_arr=array();
	$title_arr[]=_('From Value');
	$class_arr[]=('');
	$to_title_arr[]=('');
	foreach ($elearray as $status) {
		$title_arr[]='<div><span>'.$status['element_name'].'</span></div>';
		$to_title_arr[]='<div><span>'.$to.$status['element_name'].'</span></div>';
		$class_arr[]='rotate';
	}
	echo $HTML->listTableTop($title_arr, false, 'table-header-rotated','',$class_arr);
	echo "\n";

	// Special treatment for the initial value (in the Submit form).
	echo '<tr id="initval"><th class="row-header" style="text-align:left">'.$init.'</th>'."\n";
	$next = $atw->getNextNodes('100');
	foreach ($states as $s) {
		$name = 'wk[100]['. $s['element_id'].']';
		$value = in_array($s['element_id'], $next)? ' checked="checked"' : '';
		$str = '<input type="checkbox" name="'.$name.'"'.$value.' />';
		$str .= ' '.html_image('spacer.gif', 20, 20);
		echo '<td class="align-center">'.$str.'</td>'."\n";
	}
	echo '</tr>'."\n";
	echo $HTML->listTableBottom();

	$count=count($title_arr);
	$totitle_arr = array();
	$class_arr= array();
	for ($i=0; $i<$count; $i++) {
		$totitle_arr[] = $title_arr[$i]? $to_title_arr[$i] : '';
		$class_arr[]='rotate';
	}
	echo $HTML->listTableTop($totitle_arr, false, 'table-header-rotated','',$class_arr);

	$i=1;
	foreach ($elearray as $status) {
		echo '<tr id="configuring-'.$i++.'"><th class ="row-header" style="text-align:left">'.$from.$status['element_name'].'</th>'."\n";
		$next = $atw->getNextNodes($status['element_id']);
		foreach ($states as $s) {
			if ($status['element_id'] !== $s['element_id']) {
				$name = 'wk['.$status['element_id'].']['. $s['element_id'].']';
				$value = in_array($s['element_id'], $next)? ' checked="checked"' : '';
				$str = '<input type="checkbox" name="'.$name.'"'.$value.' />';
				if ($value) {
					$url = '/tracker/admin/?group_id='.$group_id.'&atid='.$ath->getID().'&workflow_roles=1&from='.$status['element_id'].'&next='.$s['element_id'];
					$str .= util_make_link($url, html_image('ic/acl_roles20.png', 20, 20, array('alt'=>_('Edit Roles'))), array('title' => _('Edit roles')));
					$url = '/tracker/admin/?group_id='.$group_id.'&atid='.$ath->getID().'&workflow_required_fields=1&from='.$status['element_id'].'&next='.$s['element_id'];
					$str .= util_make_link($url, html_image('ic/required.png', 20, 20, array('alt'=>_('Edit Required Fields'))), array('title' => _('Edit required fields')));
				} else {
					$str .= ' '.html_image('spacer.gif', 20, 20);
					$str .= ' '.html_image('spacer.gif', 20, 20);
				}
			} else {
				$str = '<input type="checkbox" checked="checked" disabled="disabled" />';
				$str .= ' '.html_image('spacer.gif', 20, 20);
				$str .= ' '.html_image('spacer.gif', 20, 20);
			}
			echo '<td class="align-center">'.$str.'</td>'."\n";
		}
		echo '</tr>'."\n";
	}
	echo $HTML->listTableBottom();

?>
<div class="tips">Tip: Click on <?php echo html_image('ic/acl_roles20.png', 20, 20, array('alt'=> _('Edit Roles'))) ?> to configure allowed roles for a transition (all by default).</div>
<div class="tips">Tip2: Click on <?php echo html_image('ic/required.png', 20, 20, array('alt'=> _('Edit Required Fields'))) ?> to configure required fields for a transition (none by default).</div>

<p>
<input type="submit" name="post_changes" value="<?php echo _('Submit') ?>" /></p>
<?php
	echo $HTML->closeForm();
}

$ath->footer();
