<?php
/**
 * Project Registration: Project Information.
 *
 * This page is used to request data required for project registration:
 *	 o Project Public Name
 *	 o Project Registration Purpose
 *	 o Project License
 *	 o Project Public Description
 *	 o Project Unix Name
 * All these data are more or less strictly validated.
 *
 * This is last page in registration sequence. Its successful subsmission
 * leads to creation of new group with Pending status, suitable for approval.
 *
 * Portions Copyright 1999-2001 (c) VA Linux Systems
 * Portions Copyright 2002-2004 (c) GForge Team
 * Portions Copyright 2002-2009 (c) Roland Mas
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012, Jean-Christophe Masson - French National Education Department
 * Copyright 2013-2014,2016, Franck Villaume - TrivialDev
 * http://fusionforge.org/
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

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'scm/SCMFactory.class.php';

global $HTML;

//
//	Test if restricted project registration
//
if (forge_get_config('project_registration_restricted')) {
	session_require_global_perm ('approve_projects', '',
				     sprintf (_('Project registration is restricted on %s, and only administrators can create new projects.'),
					      forge_get_config ('forge_name')));
} elseif (!session_loggedin()) {
	exit_not_logged_in();
}

$template_projects = group_get_template_projects() ;
sortProjectList ($template_projects) ;
$full_name = trim(getStringFromRequest('full_name'));
$purpose = trim(getStringFromRequest('purpose'));
$description = trim(getStringFromRequest('description'));
$unix_name = trim(strtolower(getStringFromRequest('unix_name')));
$scm = getStringFromRequest('scm');
$built_from_template = getIntFromRequest('built_from_template');

$index = 1;

if (getStringFromRequest('submit')) {
	if (!form_key_is_valid(getStringFromRequest('form_key'))) {
		exit_form_double_submit('my');
	}

	if (!$scm) {
		$scm = 'noscm' ;
	}

	$template_project = group_get_object($built_from_template);
	if ($template_project
	    && !$template_project->isError()
	    && $template_project->isTemplate()) {
		// Valid template selected, nothing to do
	} elseif (forge_get_config('allow_project_without_template')) {
		// Empty projects allowed
		$built_from_template = 0 ;
	} elseif (empty($template_projects)) {
		// No empty projects allowed, but no template available
		$built_from_template = 0 ;
	} else {
		// No empty projects allowed, picking the first available template
		$built_from_template = $template_projects[0]->getID() ;
	}

	$scm_host = '';
	if (forge_get_config('use_scm')) {
		$plugin = false ;
		if (forge_get_config('use_scm') && $scm && $scm != 'noscm') {
			$plugin = plugin_get_object($scm);
			if ($plugin) {
				$scm_host = $plugin->getDefaultServer();
			}
		}
		if (! $scm_host) {
			$scm_host = forge_get_config('scm_host');
		}
	}

	if ( !$purpose && forge_get_config ('project_auto_approval') ) {
		$purpose = _('No purpose given, autoapprove was on');
	}

	$send_mail = ! forge_get_config ('project_auto_approval') ;

	$group = new Group();
	$u = session_get_user();
	$res = $group->create(
		$u,
		$full_name,
		$unix_name,
		$description,
		$purpose,
		'shell1',
		$scm_host,
		$send_mail,
		$built_from_template
	);
	# TODO: enable SCM after the project is approved (if ever), to create systasks when needed
	if ($res && forge_get_config('use_scm') && $plugin) {
		$group->setUseSCM (true) ;
		$res = $group->setPluginUse ($scm, true);
	} else {
		$group->setUseSCM (false) ;
	}

	if (!$res) {
		form_release_key(getStringFromRequest("form_key"));
		$error_msg .= $group->getErrorMessage();
	} else {
		site_user_header(array('title'=>_('Registration complete')));

		if ( !forge_get_config('project_auto_approval') && !forge_check_global_perm('approve_projects')) {
			echo '<p>';
			printf(_('Your project has been submitted to the %s administrators. Within 72 hours, you will receive notification of their decision and further instructions.'), forge_get_config ('forge_name'));
			echo '</p>';
			echo '<p>';
			printf(_('Thank you for choosing %s.'), forge_get_config ('forge_name'));
			echo '</p>';
		} elseif ($group->isError()) {
			echo $HTML->error_msg($group->getErrorMessage());
		} else {
			echo _('Approving Project')._(': ').$group->getUnixName();
			echo '<br />';

			if (forge_get_config('project_auto_approval')) {
				$u = user_get_object_by_name(forge_get_config('project_auto_approval_user'));
			}

			if (!$group->approve($u)) {
				echo $HTML->error_msg(_('Approval Error')._(': '), $group->getErrorMessage());
			} else {
				echo '<p>';
				echo _('Your project has been automatically approved. You should receive an email containing further information shortly.');
				echo '</p>';
				echo '<p>';
				printf(_('Thank you for choosing %s.'), forge_get_config ('forge_name'));
				echo '</p>';
			}
		}

		site_footer();
		exit();
	}
} elseif (getStringFromRequest('i_disagree')) {
	session_redirect('/');
}

site_user_header(array('title'=>_('Register Project')));
?>

<p>
<?php echo _('To apply for project registration, you should fill in basic information about it. Please read descriptions below carefully and provide complete and comprehensive data. All fields below are mandatory.') ?>
</p>
<?php
echo $HTML->openForm(array('action' => getStringFromServer('PHP_SELF'), 'method' => 'post')); ?>
<input type="hidden" name="form_key" value="<?php echo form_generate_key(); ?>"/>
<h2><?php echo $index++.'. '._('Project Full Name') ?></h2>
<p>
<?php echo _('You should start with specifying the name of your project. The “Full Name” is descriptive, and has no arbitrary restrictions (except min 3 characters and max 40 characters).') ?>
</p>
<p>
<label for="full_name">
<?php echo _('Full Name') . _(': ') ?><br/>
</label>
<input id="full_name" required="required" size="40" maxlength="40" type="text" name="full_name" placeholder="<?php echo _('Project Full Name'); ?>" value="<?php echo htmlspecialchars($full_name); ?>" pattern=".{3,40}"/>
</p>

<?php
// Don't display Project purpose if auto approval is on, because it won't be used.
if ( !forge_get_config ('project_auto_approval') ) {
	echo '<h2>'.$index++.'. '._('Project Purpose And Summarization').'</h2>';
	echo '<p>';
	echo '<label for="purpose">';
	printf(_('Please provide detailed, accurate description of your project and what %1$s resources and in which way you plan to use. This description will be the basis for the approval or rejection of your project\'s hosting on %1$s, and later, to ensure that you are using the services in the intended way. This description will not be used as a public description of your project. It must be written in English. From 10 to 1500 characters.'), forge_get_config ('forge_name'));
	echo '</label>';
	echo '</p>';
	echo '<textarea id="purpose" required="required" name="purpose" cols="70" rows="10" placeholder="'. _('Project Purpose And Summarization').'" >';
	echo htmlspecialchars($purpose);
	echo '</textarea>';
}
?>

<h2><?php echo $index++.'. '. _('Project Public Description') ?></h2>
<p>
<label for="description">
<?php echo _('This is the description of your project which will be shown on the Project Summary page, in search results, etc. (at least 10 characters)') ?>
</label>
</p>

<textarea id="description" required="required" name="description" cols="70" rows="5" placeholder="<?php echo _('Project Public Description'); ?>" >
<?php echo htmlspecialchars($description); ?>
</textarea>

<h2><?php echo $index++.'. '._('Project Unix Name') ?></h2>
<p><?php echo _('In addition to full project name, you will need to choose short, “Unix” name for your project.') ?></p>
<p><?php echo _('The “Unix Name” has several restrictions because it is used in so many places around the site. They are:') ?></p>
<ul>
<li><?php echo _('cannot match the Unix name of any other project;') ?></li>
<li><?php echo _('must be between 3 and 15 characters in length;') ?></li>
<li><?php echo _('must be in lower case (upper case letters will be converted to lower case);') ?></li>
<li><?php echo _('can only contain characters, numbers, and dashes;') ?></li>
<li><?php echo _('must be a valid Unix username;') ?></li>
<li><?php echo _('cannot match one of our reserved domains;') ?></li>
<li><?php echo _('Unix name will never change for this project;') ?></li>
</ul>
<p><?php echo _('Your Unix name is important, however, because it will be used for many things, including:') ?></p>
<ul>
<li><?php printf(_('a web site at <samp>unixname.%s</samp>,'), forge_get_config('web_host')) ?></li>
<li><?php echo _('the URL of your source code repository,') ?></li>
<?php if (forge_get_config('use_shell')) { ?>
<li><?php printf(_('shell access to <span class="tt">unixname.%s</span>,'), forge_get_config('web_host')) ?></li>
<?php } ?>
<li><?php echo _('search engines throughout the site.') ?></li>
</ul>
<p>
<label for="unix_name">
<?php echo _('Unix Name') . _(':'); ?>
</label>
<br />
<input id="unix_name" required="required" type="text" maxlength="15" size="15" name="unix_name" value="<?php echo htmlspecialchars($unix_name); ?>" placeholder="<?php echo _('Unix Name'); ?>" pattern="[a-z0-9-]{3,15}"/>
</p>
<div id="uname_response"></div>
<?php
$SCMFactory = new SCMFactory();
$scm_plugins=$SCMFactory->getSCMs();
if (forge_get_config('use_scm') && count($scm_plugins) > 0) {
	echo '<h2>'.$index++.'. '._('Source Code').'</h2>';
	echo '<p>' . _('You can choose among different SCM for your project, but just one (or none at all). Please select the SCM system you want to use.')."</p>\n";
	echo '<table><tbody><tr><td><strong>'._('SCM Repository')._(':').'</strong></td>';
	echo '<td>';
	echo '<label for="noscm">';
	if (!$scm) {
		echo '<input id="noscm" type="radio" name="scm" value="noscm" checked="checked" />'._('No SCM');
	} else {
		echo '<input id="noscm" type="radio" name="scm" value="noscm" />'._('No SCM');
	}
	echo '</label>';
	echo '</td>';
	foreach($scm_plugins as $plugin) {
		$myPlugin = plugin_get_object($plugin);
		echo "<td>\n";
		echo '<input id="'.$myPlugin->name.'" type="radio" name="scm" ';
		echo 'value="'.$myPlugin->name.'"';
		if ($scm && strcmp($scm, $myPlugin->name) == 0) {
			echo ' checked="checked"';
		}
		echo ' />';
		echo '<label for="'.$myPlugin->name.'">';
		echo $myPlugin->text;
		echo '</label>';
		echo "\n</td>\n";
	}
	echo '</tr></tbody></table>'."\n";
}

if (count ($template_projects) > 1) {
	$tpv_arr = array () ;
	$tpn_arr = array () ;
	echo '<h2>'.$index++.'. '._('Project template'). '</h2>';
	echo '<p>';
	if (forge_get_config('allow_project_without_template')) {
		printf(_('You can either start from an empty project, or pick a project that will act as a template for yours.  Your project will initially have the same configuration as the template (same roles and permissions, same trackers, same set of enabled plugins, and so on).')) ;
		$tpv_arr[] = 0 ;
		$tpn_arr[] = _('Start from empty project') ;
	} else {
		printf(_('Please pick a project that will act as a template for yours.  Your project will initially have the same configuration as the template (same roles and permissions, same trackers, same set of enabled plugins, and so on).')) ;
	}
	echo '</p>' ;
	foreach ($template_projects as $tp) {
		$tpv_arr[] = $tp->getID() ;
		$tpn_arr[] = $tp->getPublicName() ;
	}
	echo html_build_select_box_from_arrays ($tpv_arr, $tpn_arr, 'built_from_template', $built_from_template,
						false, '', false, '') ;
} elseif (count ($template_projects) == 1) {
	if (forge_get_config('allow_project_without_template')) {
		echo '<h2>'.$index++.'. '._('Project template'). '</h2>';
		echo '<p>';
		printf(_('You can either start from an empty project, or use the %s project as a template for yours.  Your project will initially have the same configuration as the template (same roles and permissions, same trackers, same set of enabled plugins, and so on).'),
		       $template_projects[0]->getPublicName()) ;
		echo '</p>' ;
		$tpv_arr = array () ;
		$tpn_arr = array () ;
		$tpv_arr[] = 0 ;
		$tpn_arr[] = _('Start from empty project') ;
		$tpv_arr[] = $template_projects[0]->getID() ;
		$tpn_arr[] = $template_projects[0]->getPublicName() ;
		echo html_build_select_box_from_arrays ($tpv_arr, $tpn_arr, 'built_from_template', $template_projects[0]->getID(),
							false, '', false, '') ;
	} else {
		printf(_('Your project will initially have the same configuration as the %s project (same roles and permissions, same trackers, same set of enabled plugins, and so on).'),
		       $template_projects[0]->getPublicName()) ;
		echo '<input type="hidden" name="built_from_template" value="'.$template_projects[0]->getID().'" />' ;
		echo '</p>' ;
	}
} else {
	echo '<p>';
	printf(_('Since no template project is available, your project will start empty.')) ;
	echo '<input type="hidden" name="built_from_template" value="0" />' ;
	echo '</p>';
}
?>

<p class="align-center">
<input type="submit" name="submit" value="<?php echo _('Submit') ?>" />
<input type="submit" name="i_disagree" formnovalidate="formnovalidate" value="<?php echo _('Cancel') ?>" />
</p>

<?php
echo $HTML->closeForm();
?>
<script>
$(document).ready(function(){
    $('#unix_name').keyup(function(){
        var unix_name = $(this).val();
        if(unix_name != ''){
            $.ajax({
                url: '../account/check_unix_name.php',
                type: 'post',
                data: {unix_name: unix_name},
                success: function(response){
                    $('#uname_response').html(response);
                }
            });
        } else {
            $('#uname_response').html('');
        }
    });
});
</script>
<?php 
site_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
