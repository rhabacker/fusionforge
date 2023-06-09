<?php
/**
 * FusionForge Subversion plugin
 *
 * Copyright 2003-2010, Roland Mas, Franck Villaume
 * Copyright 2004, GForge, LLC
 * Copyright 2010, Alain Peyrat <aljeux@free.fr>
 * Copyright 2012-2014,2016-2018,2021-2022, Franck Villaume - TrivialDev
 * Copyright 2013, French Ministry of National Education
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
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require_once $gfcommon.'include/plugins_utils.php';

forge_define_config_item('default_server', 'scmsvn', forge_get_config('scm_host'));
forge_define_config_item('repos_path', 'scmsvn', forge_get_config('chroot').'/scmrepos/svn');
forge_define_config_item('serve_path', 'scmsvn', forge_get_config('repos_path'));
forge_define_config_item_bool('use_ssh', 'scmsvn', false);
forge_define_config_item_bool('use_dav', 'scmsvn', true);
forge_define_config_item_bool('use_ssl', 'scmsvn', true);
forge_define_config_item('ssh_port', 'core', 22);

class SVNPlugin extends SCMPlugin {

	var $svn_root_dav;
	var $svn_root_fs;
	var $web_port;

	function __construct() {
		parent::__construct();
		$this->name = 'scmsvn';
		$this->text = _('Subversion');
		$this->pkg_desc =
_("This plugin contains the Subversion subsystem of FusionForge. It allows
each FusionForge project to have its own Subversion repository, and gives
some control over it to the project's administrator.");
		$this->svn_root_fs = forge_get_config('repos_path', $this->name);
		$this->svn_root_dav = '/svn';
		$this->_addHook('scm_admin_form');
		$this->_addHook('scm_browser_page');
		$this->_addHook('scm_update_repolist');
		$this->_addHook('scm_regen_apache_auth');
		$this->_addHook('scm_generate_snapshots');
		$this->_addHook('scm_gather_stats');
		$this->_addHook('scm_admin_form');
		$this->_addHook('scm_add_repo');
		$this->_addHook('scm_delete_repo');
		$this->_addHook('get_scm_repo_list');
		$this->_addHook('get_scm_repo_info');
		$this->_addHook('parse_scm_repo_activities');
		$this->_addHook('activity');

		$this->provides['svn'] = true;
		$this->web_port = util_url_port(forge_get_config('use_ssl', 'scmsvn'));
		$this->register();
	}

	function getDefaultServer() {
		return forge_get_config('default_server', 'scmsvn');
	}

	function printShortStats($params) {
		$project = $this->checkParams($params);
		if (!$project) {
			return;
		}

		if (forge_check_perm('scm', $project->getID(), 'read')) {
			$result = db_query_params('SELECT sum(updates) AS updates, sum(adds) AS adds FROM stats_cvs_group WHERE group_id=$1',
						array($project->getID()));
			$commit_num = db_result($result,0,'updates');
			$add_num    = db_result($result,0,'adds');
			if (!$commit_num) {
				$commit_num=0;
			}
			if (!$add_num) {
				$add_num=0;
			}
			$params['result'] .= ' (Subversion: '.sprintf(_('<strong>%1$s</strong> updates, <strong>%2$s</strong> adds'), number_format($commit_num, 0), number_format($add_num, 0)).")";
		}
	}

	function getBlurb() {
		return '<p>'
				. sprintf(_('Documentation for %1$s is available at <a href="%2$s">%2$s</a>.'),
							'Subversion (“SVN”)',
							'http://svnbook.red-bean.com/')
				. '</p>';
	}

	function topModule($project, $repo_name) {
		$scm_paths = array();
		$scm_paths_file = forge_get_config('groupdir_prefix').'/'.$project->getUnixName().'/'.$repo_name.'_scmsvn_paths.txt';
		if (file_exists($scm_paths_file)) {
			$scm_paths = array_map("htmlentities", file($scm_paths_file));
		}
		// Check scm_path module presence in repository
		$modules = array();
		$repo = 'file://' . forge_get_config('repos_path', $this->name).'/'.$repo_name;
		foreach ($scm_paths as $scm_path) {
			$scm_path = trim($scm_path);
			if (strpos($scm_path, "!") === false) {
				$res = array();
				exec("svn info '$repo'", $res);
				if (!preg_grep("/svn: warning: W170000: URL/", $res)) {
					if (substr($scm_path, 0, 1) === '/') {
						$modules[] = $scm_path;
					} else {
						$modules[] = '/'.$scm_path;
					}
				}
			}
		}
		if (empty($modules)) {
			$modules[] = '/';
		}
		return $modules;
	}

	function getInstructionsForAnon($project) {
		$repo_list = array($project->getUnixName());
		$result = db_query_params('SELECT repo_name FROM scm_secondary_repos WHERE group_id=$1 AND next_action = $2 AND plugin_id=$3 ORDER BY repo_name',
				array($project->getID(), SCM_EXTRA_REPO_ACTION_UPDATE, $this->getID()));
		$rows = db_numrows($result);

		for ($i = 0; $i < $rows; $i++) {
			$repo_list[] = db_result($result, $i, 'repo_name');
		}

		$b = html_e('h2', array(), _('Anonymous Access'));
		$b .= html_e('p', array(),
			ngettext("This project's SVN repository can be checked out through anonymous access with the following command(s).",
				"This project's SVN repositories can be checked out through anonymous access with the following command(s).",
				count($repo_list)));

		if (forge_get_config('use_ssh', 'scmsvn')) {
			$ssh_port = '';
			if (forge_get_config('ssh_port') != 22) {
				$ssh_port = '--config-option="config:tunnels:ssh=ssh -p '.forge_get_config('ssh_port').'"';
			}
			$b .= html_e('h3', array(), _('via SVN'));
			foreach ($repo_list as $repo_name) {
				$modules = $this->topModule($project, $repo_name);
				foreach ($modules as $module) {
					$b .= html_e('kbd', array(), 'svn '.$ssh_port.' checkout svn://'.$this->getBoxForProject($project).$this->svn_root_fs.'/'.$repo_name.$module).html_e('br');
				}
			}
		}

		if (forge_get_config('use_dav', 'scmsvn')) {
			$b .= html_e('h3', array(), _('via DAV'));
			foreach ($repo_list as $repo_name) {
				$modules = $this->topModule($project, $repo_name);
				foreach ($modules as $module) {
					$b .= html_e('kbd', array(), 'svn checkout http'.((forge_get_config('use_ssl', 'scmsvn')) ? 's' : '').'://'. $this->getBoxForProject($project).$this->web_port.'/anonscm/svn/'.$repo_name.$module).html_e('br');
				}
			}
		}
		return $b;
	}

	function getInstructionsForRW($project) {
		$repo_list = array($project->getUnixName());
		$result = db_query_params('SELECT repo_name FROM scm_secondary_repos WHERE group_id=$1 AND next_action = $2 AND plugin_id=$3 ORDER BY repo_name',
				array($project->getID(), SCM_EXTRA_REPO_ACTION_UPDATE, $this->getID()));
		$rows = db_numrows($result);
		for ($i=0; $i<$rows; $i++) {
			$repo_list[] = db_result($result, $i, 'repo_name');
		}

		$b = html_e('h2', array(), _('Developer Access'));
		$b .= sprintf(_('Only project developers can access the %s tree via this method.'), 'Subversion');
		$b .= '<div id="tabber-svn">';
		$b .= '<ul>';
		if (forge_get_config('use_ssh', 'scmsvn')) {
			$b .= '<li><a href="#tabber-svnssh">'._('via SSH').'</a></li>';
			$configuration = 1;
		}
		if (forge_get_config('use_dav', 'scmsvn')) {
			$b .= '<li><a href="#tabber-svndav">'._('via DAV').'</a></li>';
			$configuration = 1;
		}
		$b .= '</ul>';
		if (session_loggedin()) {
			$u = user_get_object(user_getid());
			$d = $u->getUnixName();
			if (forge_get_config('use_ssh', 'scmsvn')) {
				$b .= '<div id="tabber-svnssh" class="tabbertab" >';
				$b .= '<p>';
				$b .= _('SSH must be installed on your client machine.');
				$b .= ' ';
				$b .= _('Enter your site password when prompted.');
				$b .= '</p><p>';
				$ssh_port = '';
				if (forge_get_config('ssh_port') != 22) {
					$ssh_port = '--config-option="config:tunnels:ssh=ssh -p '.forge_get_config('ssh_port').'" ';
				}
				foreach ($repo_list as $repo_name) {
					$modules = $this->topModule($project, $repo_name);
					foreach ($modules as $module) {
						if (forge_get_config('use_shell_limited')) {
							$b .= html_e('kbd', array(), 'svn '.$ssh_port.'checkout svn+ssh://'.$d.'@'.$this->getBoxForProject($project).'/'.$repo_name.$module).html_e('br');
						} else {
							$b .= html_e('kbd', array(), 'svn '.$ssh_port.'checkout svn+ssh://'.$d.'@'.$this->getBoxForProject($project).$this->svn_root_fs.'/'.$repo_name.$module).html_e('br');
						}
					}
				}
				$b .= '</p></div>';
			}
			if (forge_get_config('use_dav', 'scmsvn')) {
				$b .= '<div id="tabber-svndav" class="tabbertab" >';
				$b .= '<p>';
				$b .= _('Enter your site password when prompted.');
				$b .= '</p>';
				foreach ($repo_list as $repo_name) {
					$modules = $this->topModule($project, $repo_name);
					foreach ($modules as $module) {
						$b .= html_e('kbd', array(), 'svn checkout --username '.$d.' http'.((forge_get_config('use_ssl', 'scmsvn')) ? 's' : '').'://'.$this->getBoxForProject($project).$this->web_port.'/authscm/'.$d.'/svn/'.$repo_name.$module).html_e('br');
					}
				}
				$b .= '</div>';
			}
		} else {
			if (forge_get_config('use_ssh', 'scmsvn')) {
				$b .= '<div id="tabber-svnssh" class="tabbertab" >';
				$b .= html_e('p', array(),
					ngettext('Only project developers can access the SVN repository via this method.',
						'Only project developers can access the SVN repositories via this method.',
						count($repo_list)).
					' '. _('SSH must be installed on your client machine.').
					' '. _('Additionally, a public ssh key must be available in the FusionForge settings of the respective user.').
					' '. _('Substitute <em>developername</em> with the proper value.')).'<p>';
				$ssh_port = '';
				if (forge_get_config('ssh_port') != 22) {
					$ssh_port = '--config-option="config:tunnels:ssh=ssh -p '.forge_get_config('ssh_port').'" ';
				}
				foreach ($repo_list as $repo_name) {
					$modules = $this->topModule($project, $repo_name);
					foreach ($modules as $module) {
						if (forge_get_config('use_shell_limited')) {
							$b .= html_e('kbd', array(), 'svn '.$ssh_port.'checkout svn+ssh://'.html_e('em', array(), _('developername'), true, false).'@'.$this->getBoxForProject($project).'/'.$repo_name.$module).html_e('br');
						} else {
							$b .= html_e('kbd', array(), 'svn '.$ssh_port.'checkout svn+ssh://'.html_e('em', array(), _('developername'), true, false).'@'.$this->getBoxForProject($project).$this->svn_root_fs .'/'.$repo_name.$module).html_e('br');
						}
					}
				}
				$b .= '</p></div>';
			}
			if (forge_get_config('use_dav', 'scmsvn')) {
				$b .= '<div id="tabber-svndav" class="tabbertab" >';
				$b .= '<p>';
				$b .= _('Substitute <em>developername</em> with the proper value.');
				$b .= ' ';
				$b .= _('Enter your site password when prompted.');
				$b .= '</p>';
				foreach ($repo_list as $repo_name) {
					$modules = $this->topModule($project, $repo_name);
					foreach ($modules as $module) {
						$b .= html_e('kbd', array(), 'svn checkout --username '.html_e('em', array(), _('developername'),true, false).' http'.((forge_get_config('use_ssl', 'scmsvn')) ? 's' : '').'://'.$this->getBoxForProject($project).$this->web_port.'/authscm/'.html_e('em', array(), _('developername'),true, false).'/svn/'.$repo_name.$module).html_e('br');
					}
				}
				$b .= '</div>';
			}
		}
		$b .= '</div>';
		return $b;
	}

	function getSnapshotPara($project) {
		$b = '';
		$filename = $project->getUnixName().'-scm-latest.tar'.util_get_compressed_file_extension();
		if (file_exists(forge_get_config('scm_snapshots_path').'/'.$filename)) {
			$b .= html_e('p', array(), '['.util_make_link("/snapshots.php?group_id=".$project->getID(), _('Download the nightly snapshot')).']');
		}
		return $b;
	}

	function getBrowserLinkBlock($project) {
		$b = html_e('h2', array(), _('Subversion Repository Browser'));
		$b .= html_e('p', array(),_("Browsing the Subversion tree gives you a view into the current status of this project's code.")
						.' '
						._('You may also view the complete histories of any file in the repository.'));
		$b .= html_e('p', array(), '['.util_make_link ("/scm/browser.php?group_id=".$project->getID().'&scm_plugin='.$this->name, sprintf(_('Browse %s Repository'), 'Subversion')).']');
		# Extra repos
		$repo_list = $this->getRepositories($project, false);
		foreach ($repo_list as $repo_name) {
			$b .= '['.util_make_link('/scm/browser.php?group_id='.$project->getID().'&extra='.$repo_name.'&scm_plugin='.$this->name, _('Browse extra SVN repository')._(': ').$repo_name).']'.html_e('br');
		}
		return $b ;
	}

	function getStatsBlock($project) {
		global $HTML;
		$b = '';

		$result = db_query_params('SELECT u.realname, u.user_name, u.user_id, sum(updates) as updates, sum(adds) as adds, sum(adds+updates) as combined FROM stats_cvs_user s, users u WHERE group_id=$1 AND s.user_id=u.user_id AND (updates>0 OR adds >0) GROUP BY u.user_id, realname, user_name, u.user_id ORDER BY combined DESC, realname',
			array($project->getID()));

		if (db_numrows($result) > 0) {
			$tableHeaders = array(
				_('Name'),
				_('Adds'),
				_('Updates')
				);
			$b .= $HTML->listTableTop($tableHeaders, array(), '', 'repo-history-'.$this->name);

			$i = 0;
			$total = array('adds' => 0, 'updates' => 0);

			while($data = db_fetch_array($result)) {
				$cells = array();
				$cells[] = array(util_display_user($data['user_name'], $data['user_id'], $data['realname']), 'class' => 'halfwidth');
				$cells[] = array($data['adds'], 'class' => 'onequarterwidth align-right');
				$cells[] = array($data['updates'], 'class' => 'onequarterwidth align-right');
				$b .= $HTML->multiTableRow(array(), $cells);
				$total['adds'] += $data['adds'];
				$total['updates'] += $data['updates'];
				$i++;
			}
			$cells = array();
			$cells[] = array(html_e('strong', array(), _('Total')._(':')), 'class' => 'halfwidth');
			$cells[] = array($total['adds'], 'class' => 'onequarterwidth align-right');
			$cells[] = array($total['updates'], 'class' => 'onequarterwidth align-right');
			$b .= $HTML->multiTableRow(array(), $cells);
			$b .= $HTML->listTableBottom();
		} else {
			$b .= $HTML->warning_msg(_('No history yet.'));
		}

		return $b;
	}

	function printBrowserPage($params) {
		if ($params['scm_plugin'] != $this->name) {
			return;
		}
		$project = $this->checkParams($params);
		if (!$project) {
			return;
		}
		if (isset($params['extra']) && !empty($params['extra']) && $params['extra'] != 'none') {
			$iframe_src = '/scm/viewvc.php?root='.$params['extra'];
		} else {
			$iframe_src = '/scm/viewvc.php?root='.$project->getUnixName();
		}
		if ($params['commit']) {
			$iframe_src .= '&view=rev&revision='.$params['commit'];
		}
		htmlIframe($iframe_src, array('id'=>'scmsvn_iframe'));
	}

	function createOrUpdateRepo($params) {
		$project = $this->checkParams($params);
		if (!$project) {
			return false;
		}
		$repo_prefix = forge_get_config('repos_path', 'scmsvn');
		if (!is_dir($repo_prefix) && !mkdir($repo_prefix, 0755, true)) {
			return false;
		}

		$repo = $repo_prefix.'/'.$project->getUnixName();

		if (!is_dir ($repo) || !is_file ("$repo/format")) {
			if (!mkdir($repo, 0700, true)) {
				return false;
			}
			$ret = 0;
			system ("svnadmin create $repo", $ret);
			if ($ret != 0) {
				return false;
			}
			system ("sed -i '/enable-rep-sharing = false/s/^. //' $repo/db/fsfs.conf") ;
			// dav/ and dav/activities.d directories are required by old svn clients (eg. svn 1.6.17 on ubuntu 12.04)
			if (!is_dir ("$repo/dav")) {
				mkdir("$repo/dav");
			}
			if (!is_dir ("$repo/dav/activities.d")) {
				mkdir("$repo/dav/activities.d");
			}
			system ("svn mkdir -m'Init' file:///$repo/trunk file:///$repo/tags file:///$repo/branches >/dev/null") ;
			system ("find $repo -type d -print0 | xargs -r -0 chmod g+s") ;
			// Allow read/write users to modify the SVN repository
			$rw_unix_group = $project->getUnixName() . '_scmrw';
			system("chgrp -R $rw_unix_group $repo");
			// Allow read-only users to enter the (top-level) directory
			$ro_unix_group = $project->getUnixName() . '_scmro';
			system("chgrp $ro_unix_group $repo");
			// open permissions to allow switching private/public easily
			// see after to restrict the top-level directory
			system ("chmod -R g+rwX,o+rX-w $repo") ;
		}

		if ($project->enableAnonSCM()) {
			system("chmod g+rX-w,o+rX-w $repo") ;
		} else {
			system("chmod g+rX-w,o-rwx $repo") ;
		}

		// Create project-wide secondary repositories
		$result = db_query_params('SELECT repo_name FROM scm_secondary_repos WHERE group_id = $1 AND next_action = $2 AND plugin_id = $3',
					   array($project->getID(),
						  SCM_EXTRA_REPO_ACTION_UPDATE,
						  $this->getID()));
		$rows = db_numrows($result);
		for ($i = 0; $i < $rows; $i++) {
			$repo_name = db_result($result, $i, 'repo_name');
			$repo = $repo_prefix.'/'.$repo_name;
			if (!is_dir($repo) || !is_file("$repo/format")) {
				if (!mkdir($repo, 0700, true)) {
					return false;
				}
				$ret = 0;
				system ("svnadmin create $repo", $ret);
				if ($ret != 0) {
					return false;
				}
				system ("sed -i '/enable-rep-sharing = false/s/^. //' $repo/db/fsfs.conf") ;
				// dav/ directory is required by old svn clients (eg. svn 1.6.17 on ubuntu 12.04)
				if (!is_dir ("$repo/dav")) {
					mkdir("$repo/dav");
				}
				system ("svn mkdir -m'Init' file:///$repo/trunk file:///$repo/tags file:///$repo/branches >/dev/null") ;
				system ("find $repo -type d -print0 | xargs -r -0 chmod g+s") ;
				// Allow read/write users to modify the SVN repository
				$rw_unix_group = $project->getUnixName() . '_scmrw';
				system("chgrp -R $rw_unix_group $repo");
				// Allow read-only users to enter the (top-level) directory
				$ro_unix_group = $project->getUnixName() . '_scmro';
				system("chgrp $ro_unix_group $repo");
				// open permissions to allow switching private/public easily
				// see after to restrict the top-level directory
				system ("chmod -R g+rwX,o+rX-w $repo") ;
			}
			if ($project->enableAnonSCM()) {
				system("chmod g+rX-w,o+rX-w $repo") ;
			} else {
				system("chmod g+rX-w,o-rwx $repo") ;
			}
		}

		// Delete project-wide secondary repositories
		$result = db_query_params('SELECT repo_name FROM scm_secondary_repos WHERE group_id=$1 AND next_action = $2 AND plugin_id=$3',
					   array($project->getID(),
						  SCM_EXTRA_REPO_ACTION_DELETE,
						  $this->getID()));
		$rows = db_numrows ($result);
		for ($i=0; $i<$rows; $i++) {
			$repo_name = db_result($result, $i, 'repo_name');
			$repodir = $repo_prefix.'/'.$repo_name;
			if (util_is_valid_repository_name($repo_name)) {
				system("rm -rf $repodir");
			}
			db_query_params ('DELETE FROM scm_secondary_repos WHERE group_id=$1 AND repo_name=$2 AND next_action = $3 AND plugin_id=$4',
					 array($project->getID(),
						$repo_name,
						SCM_EXTRA_REPO_ACTION_DELETE,
						$this->getID()));
		}
		$this->regenApacheAuth($params);
	}

	function updateRepositoryList(&$params) {
	}

	function regenApacheAuth(&$params) {
		# Enable /authscm/$user/svn URLs
		$config_fname = forge_get_config('data_path').'/scmsvn-auth.inc';
		$config_f = fopen($config_fname.'.new', 'w');

		$res = db_query_params("SELECT login FROM nss_passwd WHERE status=$1", array('A'));
		while ($arr = db_fetch_array($res)) {
			fwrite($config_f, 'Use ScmsvnUser '.$arr['login']."\n");
		}

		fclose($config_f);
		chmod($config_fname.'.new', 0644);
		rename($config_fname.'.new', $config_fname);
	}

	function gatherStats($params) {
		global $last_user, $last_time, $last_tag, $time_ok, $start_time, $end_time,
			$adds, $deletes, $updates, $commits, $date_key,
			$usr_adds, $usr_deletes, $usr_updates, $usr_commits;

		$time_ok = true;

		$project = $this->checkParams($params);
		if (!$project) {
			return false;
		}

		if ($params['mode'] == 'day') {
			db_begin();

			$year = $params['year'];
			$month = $params['month'];
			$day = $params['day'];
			$month_string = sprintf("%04d%02d", $year, $month);
			$start_time = gmmktime(0, 0, 0, $month, $day, $year);
			$end_time = $start_time + 86400;

			$adds    = 0;
			$updates = 0;
			$deletes = 0;
			$commits = 0;

			$usr_adds    = array();
			$usr_updates = array();
			$usr_deletes = array();
			$usr_commits = array();

			$repo = forge_get_config('repos_path', 'scmsvn').'/'.$project->getUnixName().'/'.$project->getUnixName();
			if (!is_dir ($repo) || !is_file ("$repo/format")) {
				db_rollback();
				return false;
			}

			$d1 = date('Y-m-d', $start_time - 150000);
			$d2 = date('Y-m-d', $end_time + 150000);

			$pipe = popen ("svn log file://$repo --xml -v -q -r '".'{'.$d2.'}:{'.$d1.'}'."' 2> /dev/null", 'r' ) ;

			// cleaning stats_cvs_* table for the current day
			$res = db_query_params('DELETE FROM stats_cvs_group WHERE month = $1 AND day = $2 AND group_id = $3 AND reponame = $4',
				array($month_string,
					$day,
					$project->getID(),
					$project->getUnixName()));
			if(!$res) {
				echo "Error while cleaning stats_cvs_group\n";
				db_rollback();
				return false;
			}

			$res = db_query_params('DELETE FROM stats_cvs_user WHERE month = $1 AND day = $2 AND group_id = $3 AND reponame = $4',
				array($month_string,
					$day,
					$project->getID(),
					$project->getUnixName()));
			if(!$res) {
				echo "Error while cleaning stats_cvs_user\n" ;
				db_rollback () ;
				return false ;
			}

			$xml_parser = xml_parser_create();
			xml_set_element_handler($xml_parser, "SVNPluginStartElement", "SVNPluginEndElement");
			xml_set_character_data_handler($xml_parser, "SVNPluginCharData");

			// Analyzing history stream
			while (!feof($pipe) &&
				$data = fgets ($pipe, 4096)) {
				if (!xml_parse ($xml_parser, $data, feof ($pipe))) {
					$this->setError("Unable to parse XML with error " .
						xml_error_string(xml_get_error_code($xml_parser)) .
						" on line " .
						xml_get_current_line_number($xml_parser));
					db_rollback () ;
					return false ;
				}
			}

			xml_parser_free($xml_parser);

			// inserting group results in stats_cvs_groups
			if ($updates > 0 || $adds > 0 || $deletes > 0 || $commits > 0) {
				if (!db_query_params('INSERT INTO stats_cvs_group (month, day, group_id, checkouts, commits, adds, updates, deletes, reponame)
								VALUES ($1, $2, $3, $4, $5, $6, $7, $8)',
					array($month_string,
						$day,
						$project->getID(),
						0,
						$commits,
						$adds,
						$updates,
						$deletes,
						$project->getUnixName()))) {
					echo "Error while inserting into stats_cvs_group\n";
					db_rollback();
					return false;
				}
			}

			// building the user list
			$user_list = array_unique( array_merge( array_keys( $usr_adds ), array_keys( $usr_updates ),  array_keys( $usr_deletes ), array_keys( $usr_commits )) );

			foreach ($user_list as $user) {
				// Trying to get user id from user name
				$u = user_get_object_by_name($user);
				if ($u) {
					$user_id = $u->getID();
				} else {
					continue;
				}

				$uc = isset($usr_commits[$user]) ? $usr_commits[$user] : 0 ;
				$uu = isset($usr_updates[$user]) ? $usr_updates[$user] : 0 ;
				$ua = isset($usr_adds[$user]) ? $usr_adds[$user] : 0 ;
				$ud = isset($usr_deletes[$user]) ? $usr_deletes[$user] : 0 ;
				if ($uu > 0 || $ua > 0 || $uc > 0 || $ud > 0) {
					if (!db_query_params('INSERT INTO stats_cvs_user (month, day, group_id, user_id, commits, adds, updates, deletes, reponame)
									VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)',
							      array ($month_string,
								     $day,
								     $project->getID(),
								     $user_id,
								     $uc,
								     $ua,
								     $uu,
								     $ud,
									$project->getUnixName()))) {
						echo "Error while inserting into stats_cvs_user\n";
						db_rollback();
						return false;
					}
				}
			}
			db_commit();
		}
	}

	function generateSnapshots($params) {
		$us = forge_get_config('use_scm_snapshots') ;
		$ut = forge_get_config('use_scm_tarballs') ;
		if (!$us && !$ut) {
			return false ;
		}

		$project = $this->checkParams($params);
		if (!$project) {
			return false;
		}

		$group_name = $project->getUnixName();

		$snapshot = forge_get_config('scm_snapshots_path').'/'.$group_name.'-scm-latest.tar'.util_get_compressed_file_extension();
		$tarball = forge_get_config('scm_tarballs_path').'/'.$group_name.'-scmroot.tar'.util_get_compressed_file_extension();

		if (!$project->enableAnonSCM()) {
			if (is_file($snapshot)) {
				unlink($snapshot);
			}
			if (is_file($tarball)) {
				unlink($tarball);
			}
			return false;
		}

		$toprepo = forge_get_config('repos_path', 'scmsvn');
		$repo = $toprepo.'/'.$group_name.'/'.$group_name;

		if (!is_dir($repo) || !is_file ("$repo/format")) {
			if (is_file($snapshot)) {
				unlink($snapshot);
			}
			if (is_file($tarball)) {
				unlink($tarball);
			}
			return false;
		}

		$tmp = trim(`mktemp -d`);
		if ($tmp == '') {
			return false;
		}

		$today = date('Y-m-d');
		$dir = $project->getUnixName ()."-$today" ;
		system("mkdir -p $tmp") ;
		$code = 0 ;
		system ("svn ls file://$repo/trunk > /dev/null 2> /dev/null", $code) ;
		if ($us) {
			if ($code == 0) {
				system ("cd $tmp ; svn export file://$repo/trunk $dir > /dev/null 2>&1") ;
				system ("tar cCf $tmp - $dir |".forge_get_config('compression_method')."> $tmp/snapshot") ;
				chmod("$tmp/snapshot", 0644);
				copy("$tmp/snapshot", $snapshot);
				unlink("$tmp/snapshot");
				system ("rm -rf $tmp/$dir") ;
			} else {
				if (is_file($snapshot)) {
					unlink ($snapshot) ;
				}
			}
		}

		if ($ut) {
			system("tar cCf $toprepo - ".$project->getUnixName() ."|".forge_get_config('compression_method')."> $tmp/tarball") ;
			chmod("$tmp/tarball", 0644);
			copy("$tmp/tarball", $tarball);
			unlink("$tmp/tarball");
			system("rm -rf $tmp");
		}
	}

	function activity($params) {
		global $last_user, $last_time, $last_tag, $time_ok, $start_time, $end_time,
			$adds, $deletes, $updates, $commits, $date_key,
			$messages, $last_message, $times, $revisions, $users, $xml_parser;
		$commits = 0;
		$adds = 0;
		$updates = 0;
		$deletes = 0;
		$users = array();
		$messages = array();
		$times = array();
		$revisions = array();

		$project = $this->checkParams($params);
		if (!$project) {
			return false;
		}
		if (isset($params['exclusive_area']) && ($params['exclusive_area'] != $this->name)) {
			return false;
		}

		if (in_array('scmsvn', $params['show']) || (count($params['show']) < 1)) {
			$start_time = $params['begin'];
			$end_time = $params['end'];

			if ($project->enableAnonSCM()) {
				$server_script = '/anonscm/svnlog';
			} else {
				$u = session_get_user();
				if ($u && !$u->isError()) {
					$server_script = '/authscm/'.$u->getUnixName().'/svnlog';
				} else {
					return false;
				}
			}

			$repo_list = $this->getRepositories($project);
			$protocol = forge_get_config('use_ssl', 'scmsvn') ? 'https://' : 'http://';
			foreach ($repo_list as $repo_name) {
				$xml_parser = xml_parser_create();
				xml_set_element_handler($xml_parser, "SVNPluginStartElement", "SVNPluginEndElement");
				xml_set_character_data_handler($xml_parser, "SVNPluginCharData");

				// Grab & parse commit log
				$script_url = $protocol.$this->getBoxForProject($project)
					. $this->web_port
					. $server_script
					.'?unix_group_name='.$project->getUnixName()
					.'&repo_name='.$repo_name
					.'&mode=date_range'
					.'&begin='.$params['begin']
					.'&end='.$params['end'];
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $script_url);
				curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'curl2xml');
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, forge_get_config('use_ssl_verification'));
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, forge_get_config('use_ssl_verification'));
				curl_setopt($ch, CURLOPT_COOKIE, @$_SERVER['HTTP_COOKIE']);  // for session validation
				curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);  // for session validation
				curl_setopt($ch, CURLOPT_HTTPHEADER,
							array('X-Forwarded-For: '.$_SERVER['REMOTE_ADDR']));  // for session validation
				$body = curl_exec($ch);
				if ($body === false) {
					$this->setError(curl_error($ch));
				}
				curl_close($ch);

				// final checks
				if (!xml_parse($xml_parser, '', true)) {
					$this->setError('Unable to parse XML with error '
							. xml_error_string(xml_get_error_code($xml_parser))
							. ' on line ' . xml_get_current_line_number($xml_parser));
				}
				xml_parser_free($xml_parser);

				if ($adds > 0 || $updates > 0 || $commits > 0 || $deletes > 0) {
					$i = 0;
					foreach ($messages as $message) {
						$result = array();
						$result['section'] = 'scm';
						$result['group_id'] = $project->getID();
						$result['ref_id'] = 'browser.php?group_id='.$project->getID().'&scm_plugin='.$this->name.'&repo_name='.$repo_name;
						$result['description'] = htmlspecialchars($message).' (repository: '.$repo_name.' r'.$revisions[$i].')';
						$userObject = user_get_object_by_name($users[$i]);
						if (is_a($userObject, 'FFUser')) {
							$result['realname'] = util_display_user($userObject->getUnixName(), $userObject->getID(), $userObject->getRealName());
						} else {
							$result['realname'] = '';
						}
						$result['activity_date'] = $times[$i];
						$result['subref_id'] = '&commit='.$revisions[$i];
						$params['results'][] = $result;
						$i++;
					}
				}
			}
		}
		if (!in_array($this->name, $params['ids']) && ($project->enableAnonSCM() || session_loggedin())) {
			$params['ids'][] = $this->name;
			$params['texts'][] = _('Subversion Commits');
		}
		return true;
	}

	// Get latest commits for inclusion in a widget
	function getCommits($project, $user, $nbCommits) {
		global $commits, $users, $adds, $updates, $messages, $times, $revisions, $deletes, $time_ok, $user_list, $last_message, $notimecheck, $xml_parser;
		$commits = 0;
		$users = array();
		$adds = 0;
		$updates = 0;
		$messages = array();
		$times = array();
		$revisions = array();
		$deletes = 0;
		$time_ok = false;
		$user_list = array();
		$last_message = '';
		$notimecheck = true;
		$revisionsArr = array();
		if ($project->usesPlugin($this->name) && forge_check_perm('scm', $project->getID(), 'read')) {
			// Grab&parse commit log
			$protocol = forge_get_config('use_ssl', 'scmsvn') ? 'https://' : 'http://';
			$u = session_get_user();
			if ($project->enableAnonSCM()) {
				$server_script = '/anonscm/svnlog';
			} else {
				$server_script = '/authscm/'.$u->getUnixName().'/svnlog';
			}
			if ($user) {
				$userunixname = $user->getUnixName();
				$params = '&mode=latest_user&user_name='.$userunixname;
			} else {
				$params = '&mode=latest';
			}
			$repo_list = $this->getRepositories($project);
			$i = 0;
			foreach ($repo_list as $repo_name) {
				$script_url = $protocol.$this->getBoxForProject($project)
					. $this->web_port
					. $server_script
					.'?unix_group_name='.$project->getUnixName()
					.'&repo_name='.$repo_name
					. $params
					.'&limit='.$nbCommits;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $script_url);
				curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'curl2xml');
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, forge_get_config('use_ssl_verification'));
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, forge_get_config('use_ssl_verification'));
				curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);  // for session validation
				curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);  // for session validation
				curl_setopt($ch, CURLOPT_HTTPHEADER,
							array('X-Forwarded-For: '.$_SERVER['REMOTE_ADDR']));  // for session validation
				$body = curl_exec($ch);
				if ($body === false) {
					$this->setError(curl_error($ch));
				}
				curl_close($ch);

				$xml_parser = xml_parser_create();
				xml_set_element_handler($xml_parser, "SVNPluginStartElement", "SVNPluginEndElement");
				xml_set_character_data_handler($xml_parser, "SVNPluginCharData");
				// final checks
				if (!xml_parse($xml_parser, '', true)) {
					$this->setError('Unable to parse XML with error '
						   . xml_error_string(xml_get_error_code($xml_parser))
						   . ' on line ' . xml_get_current_line_number($xml_parser));
				}
				xml_parser_free($xml_parser);

				if ($adds > 0 || $updates > 0 || $commits > 0 || $deletes > 0) {
					foreach ($messages as $message) {
						$revisionsArr[$i]['pluginName'] = 'scmsvn';
						$revisionsArr[$i]['description'] = htmlspecialchars($message);
						$revisionsArr[$i]['commit_id'] = $revisions[$i];
						$revisionsArr[$i]['repo_name'] = $repo_name;
						$revisionsArr[$i]['date'] = $times[$i];
						$i++;
					}
				}
			}
		}
		return $revisionsArr;
	}

	function scm_add_repo(&$params) {
		$project = $this->checkParams($params);
		if (!$project) {
			return false;
		}

		if (!isset($params['repo_name'])) {
			return false;
		}

		if ($params['repo_name'] == $project->getUnixName()) {
			$params['error_msg'] = _('Cannot create a secondary repository with the same name as the primary');
			return false;
		}

		if (!util_is_valid_repository_name($params['repo_name'])) {
			$params['error_msg'] = _('This repository name is not valid');
			return false;
		}

		$result = db_query_params('SELECT count(*) AS count FROM scm_secondary_repos WHERE repo_name = $1 AND plugin_id=$2',
					  array($params['repo_name'], $this->getID()));
		if (!$result) {
			$params['error_msg'] = db_error();
			return false;
		}
		if (db_result($result, 0, 'count')) {
			$params['error_msg'] = sprintf(_('%s as repository name cannot be used'), $params['repo_name']);
			return false;
		}

		// do to current implementation of multiple repositories in SVN : we need to check repo_name against project names and forbid it
		$result = db_query_params('SELECT count(*) as count FROM groups WHERE unix_group_name = $1',
					  array($params['repo_name']));
		if (!$result) {
			$params['error_msg'] = db_error();
			return false;
		}
		if (db_result($result, 0, 'count')) {
			$params['error_msg'] = sprintf(_('%s as repository name cannot be used'), $params['repo_name']);
			return false;
		}

		$description = '';
		$clone = '';
		if (isset($params['description'])) {
			$description = $params['description'];
		}
		if (!$description) {
			$description = "Subversion repository $params[repo_name] for project ".$project->getUnixName();
		}
		$result = db_query_params('INSERT INTO scm_secondary_repos (group_id, repo_name, description, clone_url, plugin_id) VALUES ($1, $2, $3, $4, $5)',
					   array($params['group_id'],
						  $params['repo_name'],
						  $description,
						  $clone,
						  $this->getID()));
		if (!$result) {
			$params['error_msg'] = db_error();
			return false;
		}

		plugin_hook ("scm_admin_update", $params);
		return true;
	}

	function get_scm_repo_list(&$params) {
		if (array_key_exists('group_name',$params)) {
			$unix_group_name = $params['group_name'];
		} else {
			$unix_group_name = '';
		}
		$protocol = forge_get_config('use_ssl', 'scmsvn')? 'https' : 'http';
		if (session_loggedin()) {
			$u = user_get_object(user_getid());
			$d = $u->getUnixName();
		}

		$results = array();
		if ($unix_group_name) {
			$res = db_query_params("SELECT unix_group_name, groups.group_id FROM groups
			JOIN group_plugin ON (groups.group_id=group_plugin.group_id)
			WHERE groups.status=$1 AND group_plugin.plugin_id=$2 AND groups.unix_group_name=$3
			ORDER BY unix_group_name", array('A', $this->getID(),$unix_group_name));
		} else {
			$res = db_query_params("SELECT unix_group_name, groups.group_id FROM groups
			JOIN group_plugin ON (groups.group_id=group_plugin.group_id)
			WHERE groups.status=$1 AND group_plugin.plugin_id=$2
			ORDER BY unix_group_name", array('A', $this->getID()));
		}
		while ($arr = db_fetch_array($res)) {
			if (!forge_check_perm('scm', $arr['group_id'], 'read')) {
				continue;
			}
			$urls = array();
			$project = group_get_object($arr['group_id']);
			if (forge_get_config('use_dav', 'scmsvn')) {
				$urls[] = $protocol.'://'.$this->getBoxForProject($project).$this->web_port.'/anonscm/svn/'.$arr['unix_group_name'];
			}
			if (forge_get_config('use_ssh', 'scmsvn')) {
				$urls[] = 'svn://'.$this->getBoxForProject($project).$this->svn_root_fs.'/'.$arr['unix_group_name'];
			}
			if (session_loggedin()) {
				if (forge_get_config('use_dav', 'scmsvn')) {
					$urls[] = $protocol.'://'.$this->getBoxForProject($project).$this->web_port.'/authscm/'.$d.'/svn/'.$arr['unix_group_name'];
				}
				if (forge_get_config('use_ssh', 'scmsvn')) {
					$urls[] = 'svn+ssh://'.$d.'@'.$this->getBoxForProject($project).$this->svn_root_fs .'/'. $arr['unix_group_name'];
				}
			}
			$results[] = array('group_id' => $arr['group_id'],
							   'repository_type' => 'svn',
							   'repository_id' => $arr['unix_group_name'].'/svn/'.$arr['unix_group_name'],
							   'repository_urls' => $urls,
				);
		}

		foreach ($results as $res) {
			$params['results'][] = $res;
		}
	}

	function get_scm_repo_info(&$params) {
		$rid = $params['repository_id'];
		$e = explode('/',$rid);
		if ($e[1] != 'svn') {
			return;
		}
		$g = $e[0];
		$p = array('group_name' => $g);
		$this->get_scm_repo_list($p);
		foreach ($p['results'] as $r) {
			if ($r['repository_id'] == $rid) {
				$params['results'] = $r;
				return;
			}
		}
	}

	function parse_scm_repo_activities(&$params) {
		$repos = array();
		$res = db_query_params("SELECT unix_group_name, groups.group_id FROM groups
			JOIN group_plugin ON (groups.group_id=group_plugin.group_id)
			WHERE groups.status=$1 AND group_plugin.plugin_id=$2
			ORDER BY unix_group_name", array('A', $this->getID()));
		while ($arr = db_fetch_array($res)) {
			$el = array();
			$el['rpath'] = $this->svn_root_fs.'/'.$arr['unix_group_name'];
			$el['rid'] = $arr['unix_group_name'].'/svn/'.$arr['unix_group_name'];
			$el['gid'] = $arr['group_id'];
			$repos[] = $el;
		}

		$lastactivities = array();
		$res = db_query_params("SELECT repository_id, max(tstamp) AS last FROM scm_activities WHERE plugin_id=$1 GROUP BY repository_id",
							   array($this->getID()));
		while ($arr = db_fetch_array($res)) {
			$lastactivities[$arr['repository_id']] = $arr['last'];
		}

		foreach ($repos as $rdata) {
			$since = "";
			if (array_key_exists($rdata['rid'], $lastactivities)) {
				$since = '-r {$(date -d @'.$lastactivities[$rdata['rid']].' -Iseconds)}:HEAD';
			}
			$rpath = $rdata['rpath'];
			$tstamps = array();
			$f = popen("svn log -q 'file:///$rpath' $since 2> /dev/null", "r");
			while (($l = fgets($f, 4096)) !== false) {
				if (preg_match("/.*?\|.*\|(?P<tstamp>[-0-9 :+]+)/", $l, $matches)) {
					$t = strtotime($matches['tstamp']);
					if (array_key_exists($rdata['rid'], $lastactivities)
						&& $t <= $lastactivities[$rdata['rid']]) {
						continue;
					}
					$tstamps[$t] = 1;
				}
			}
			foreach ($tstamps as $t => $v) {
				db_query_params("INSERT INTO scm_activities (group_id, plugin_id, repository_id, tstamp) VALUES ($1,$2,$3,$4)",
									   array($rdata['gid'],
											 $this->getID(),
											 $rdata['rid'],
											 $t));
			}
		}
	}

	function scm_admin_form(&$params) {
		global $HTML;
		$project = $this->checkParams($params);
		if (!$project) {
			return false;
		}

		session_require_perm('project_admin', $params['group_id']);
		if (forge_get_config('allow_multiple_scm') && ($params['allow_multiple_scm'] > 1)) {
			echo html_ao('div', array('id' => 'tabber-'.$this->name, 'class' => 'tabbertab'));
		}

		$project_name = $project->getUnixName();

		$result = db_query_params('SELECT repo_name, description FROM scm_secondary_repos WHERE group_id=$1 AND next_action = $2 AND plugin_id=$3 ORDER BY repo_name',
					  array($params['group_id'],
						 SCM_EXTRA_REPO_ACTION_UPDATE,
						 $this->getID()));
		if (!$result) {
			$params['error_msg'] = db_error();
			return false;
		}
		$existing_repos = array();
		while($data = db_fetch_array($result)) {
			$existing_repos[] = array('repo_name' => $data['repo_name'],
						  'description' => $data['description']);
		}
		if (empty($existing_repos)) {
			echo $HTML->information(_('No extra Subversion repository for project').' '.$project_name);
		} else {
			echo html_e('h2', array(), sprintf(ngettext('Extra Subversion repository for project %1$s',
									'Extra Subversion repositories for project %1$s',
									count($existing_repos)), $project_name));
			$titleArr = array(_('Repository name'), ('Initial repository description'), _('Delete'));
			echo $HTML->listTableTop($titleArr);
			foreach ($existing_repos as $repo) {
				$cells = array();
				$cells[][] = html_e('kbd', array(), $repo['repo_name']);
				$cells[][] = $repo['description'];
				$deleteForm = $HTML->openForm(array('name' => 'form_delete_repo_'.$repo['repo_name'], 'action' => getStringFromServer('PHP_SELF'), 'method' => 'post'));
				$deleteForm .= html_e('input', array('type' => 'hidden', 'name' => 'group_id', 'value' => $params['group_id']));
				$deleteForm .= html_e('input', array('type' => 'hidden', 'name' => 'delete_repository', 'value' => 1));
				$deleteForm .= $HTML->html_input('scm_plugin_id', '', '', 'hidden', $this->getID());
				$deleteForm .= html_e('input', array('type' => 'hidden', 'name' => 'repo_name', 'value' => $repo['repo_name']));
				$deleteForm .= html_e('input', array('type' => 'submit', 'name' => 'submit', 'value' => _('Delete')));
				$deleteForm .= $HTML->closeForm();
				$cells[][] = $deleteForm;
				echo $HTML->multiTableRow(array(), $cells);
			}
			echo $HTML->listTableBottom();
		}

		echo html_e('h2', array(), _('Create new Subversion repository for project').' '.$project_name);
		echo $HTML->openForm(array('name' => 'form_create_repo', 'action' => getStringFromServer('PHP_SELF'), 'method' => 'post'));
		echo html_e('input', array('type' => 'hidden', 'name' => 'group_id', 'value' => $params['group_id']));
		echo html_e('input', array('type' => 'hidden', 'name' => 'create_repository', 'value' => 1));
		echo html_e('p', array(), html_e('strong', array(), _('Repository name')._(':')).utils_requiredField().html_e('br').
				html_e('input', array('type' => 'text', 'required' => 'required', 'size' => 20, 'name' => 'repo_name', 'value' => '')));
		echo html_e('p', array(), html_e('strong', array(), _('Description')._(':')).html_e('br').
				html_e('input', array('type' => 'text', 'size' => 60, 'name' => 'description', 'value' => '')));
		echo html_e('input', array('type' => 'submit', 'name' => 'cancel', 'value' => _('Cancel')));
		echo html_e('input', array('type' => 'submit', 'name' => 'submit', 'value' => _('Submit')));
		echo $HTML->closeForm();

		if ($project->usesPlugin('scmhook')) {
			$scmhookPlugin = plugin_get_object('scmhook');
			$scmhookPlugin->displayScmHook($project->getID(), $this->name);
		}
		if (forge_get_config('allow_multiple_scm') && ($params['allow_multiple_scm'] > 1)) {
			echo html_ac(html_ap() - 1);
		}
	}

	function getRepositories($group, $autoinclude = true) {
		$repoarr = array();
		if ($autoinclude) {
			$repoarr[] = $group->getUnixName();
		}
		$result = db_query_params('SELECT repo_name FROM scm_secondary_repos WHERE group_id = $1 AND next_action = $2 AND plugin_id = $3 ORDER BY repo_name',
						   array($group->getID(),
							  SCM_EXTRA_REPO_ACTION_UPDATE,
							  $this->getID()));
		while ($arr = db_fetch_array($result)) {
			$repoarr[] = $arr['repo_name'];
		}
		return $repoarr;
	}

	function getGroupIdFromSecondReponame($repo_name) {
		$result = db_query_params('SELECT scm_secondary_repos.group_id FROM scm_secondary_repos, groups WHERE scm_secondary_repos.group_id = groups.group_id AND repo_name = $1 AND plugin_id = $2 AND next_action = $3', array($repo_name, $this->getID(), SCM_EXTRA_REPO_ACTION_UPDATE));
		$arr = db_fetch_array($result);
		return $arr['group_id'];
	}
}

// End of class, helper functions now

function SVNPluginCharData($parser, $chars) {
	global $last_tag, $last_user, $last_time, $start_time, $end_time, $usr_commits, $commits,
		$time_ok, $user_list, $last_message, $messages, $times, $users, $notimecheck;
	switch ($last_tag) {
		case "AUTHOR": {
			$last_user = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($chars)));
			$users[] = $last_user;
			$usr_commits[$last_user] = isset($usr_commits[$last_user]) ? ($usr_commits[$last_user]+1) : 1 ;
			$commits++;
			break;
		}
		case "DATE": {
			$chars = preg_replace('/T(\d\d:\d\d:\d\d)\.\d+Z?$/', ' ${1}', $chars);
			$last_time = strtotime($chars);
			if ($start_time <= $last_time && $last_time < $end_time) {
				$time_ok = true;
			} else {
				$time_ok = false;
				if (!isset($notimecheck)) {
					if ($last_user !== '') { // empty in e.g. tags from cvs2svn
						$usr_commits[$last_user]--;
					}
					$commits--;
				}
			}
			$times[] = $last_time;
			break;
		}
		case "MSG": {
			if ($time_ok === true || isset($notimecheck)) {
				$messages[count($messages)-1] .= $chars;
			}
                        /* note: there may be more than one msg
			 * (happen when the message contain accents).
			 */
			break;
		}
	}
}

function SVNPluginStartElement($parser, $name, $attrs) {
	global $last_user, $last_time, $last_tag, $time_ok, $commits,
		$adds, $updates, $usr_adds, $usr_updates, $last_message, $messages, $times, $revisions, $deletes, $usr_deletes, $notimecheck;
	$last_tag = $name;
	switch($name) {
		case "LOGENTRY": {
			// Make sure we clean up before doing a new log entry
			$last_user = "";
			$last_time = "";
			$revisions[] = $attrs['REVISION'];
			break;
		}
		case "PATH": {
			if ($time_ok === true || isset($notimecheck)) {

				if ($attrs['ACTION'] == "M") {
					$updates++;
					if ($last_user) {
						$usr_updates[$last_user] = isset($usr_updates[$last_user]) ? ($usr_updates[$last_user]+1) : 1 ;
					}
				} elseif ($attrs['ACTION'] == "A") {
					$adds++;
					if ($last_user) {
						$usr_adds[$last_user] = isset($usr_adds[$last_user]) ? ($usr_adds[$last_user]+1) : 1 ;
					}
				} elseif ($attrs['ACTION'] == 'D') {
					$deletes++;
					if ($last_user) {
						$usr_deletes[$last_user] = isset($usr_deletes[$last_user]) ? ($usr_deletes[$last_user]+1) : 1 ;
					}
				}
			}
			break;
		}
                case "MSG": {
			if ($time_ok === true || isset($notimecheck)) {
				$messages[] = "";
			}
			break;
                }
	}
}

function SVNPluginEndElement($parser, $name) {
	global $last_tag;
	$last_tag = "";
}

function curl2xml($ch, $data) {
	global $xml_parser;
	if (!xml_parse($xml_parser, $data, false)) {
		exit_error('Unable to parse XML with error '
				   . xml_error_string(xml_get_error_code($xml_parser))
				   . ' on line ' . xml_get_current_line_number($xml_parser),
				   'activity');
	}
	return strlen($data);
}
// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
