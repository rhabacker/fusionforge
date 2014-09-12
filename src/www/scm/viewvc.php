<?php
/**
 * FusionForge ViewCVS PHP wrapper.
 *
 * Portion of this file is inspired from the ViewCVS wrapper
 * contained in CodeX.
 * Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001,2002. All Rights Reserved.
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * http://codex.xerox.com
 *
 * Copyright 2010, Franck Villaume - Capgemini
 * Copyright 2012,2014, Franck Villaume - TrivialDev
 * Copyright (C) 2014  Inria (Sylvain Beucler)
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

// make sure we're not compressing output if we are making a tarball
if (isset($_GET['view']) && $_GET['view'] == 'tar') {
	$no_gz_buffer = true;
}

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'scm/include/scm_utils.php';
require_once $gfwww.'scm/include/viewvc_utils.php';

if (!forge_get_config('use_scm')) {
	exit_disabled();
}

// Get the project name from query
$projectName = "";
if (getStringFromGet('root') && strpos(getStringFromGet('root'), ';') === false) {
	$projectName = getStringFromGet('root');
} elseif ($_SERVER['PATH_INFO']) {
	$arr = explode('/', $_SERVER['PATH_INFO']);
	$projectName = $arr[1];
} else {
	$queryString = getStringFromServer('QUERY_STRING');
	if(preg_match_all('/[;]?([^\?;=]+)=([^;]+)/', $queryString, $matches, PREG_SET_ORDER)) {
		for($i = 0, $size = sizeof($matches); $i < $size; $i++) {
			$query[$matches[$i][1]] = urldecode($matches[$i][2]);
		}
		$projectName = $query['root'];
	}
}
// Remove eventual leading /root/ or root/
$projectName = preg_replace('%^..[^/]*/%','', $projectName);
if (!$projectName) {
	exit_no_group();
}

// Check permissions
$Group = group_get_object_by_name($projectName);
if (!$Group || !is_object($Group)) {
	exit_no_group();
} elseif ( $Group->isError()) {
	exit_error($Group->getErrorMessage(),'summary');
}
if (!$Group->usesSCM()) {
	exit_disabled();
}

// check if the scm_box is located in another server
$scm_box = $Group->getSCMBox();
//$external_scm = (gethostbyname(forge_get_config('web_host')) != gethostbyname($scm_box));
$external_scm = !forge_get_config('scm_single_host');

if (!forge_check_perm('scm', $Group->getID(), 'read')) {
	exit_permission_denied('scm');
}

if ($external_scm) {
	//$server_script = "/cgi-bin/viewcvs.cgi";
	$server_script = $GLOBALS["sys_path_to_scmweb"]."/viewcvs.cgi";
	// remove leading / (if any)
	$server_script = preg_replace("/^\\//", "", $server_script);

	// pass the parameters passed to this script to the remote script in the same fashion
	$script_url = "http://".$scm_box."/".$server_script.$_SERVER["PATH_INFO"]."?".$_SERVER["QUERY_STRING"];
	$fh = @fopen($script_url, "r");
	if (!$fh) {
		exit_error(sprintf(_('Could not open script %s.'),$script_url),'home');
	}

	// start reading the output of the script (in 8k chunks)
	$content = "";
	while (!feof($fh)) {
		$content .= fread($fh, 8192);
	}

	if (viewcvs_is_html()) {
		// Now, we must replace the occurencies of $server_script with this script
		// (do this only of outputting HTML)
		// We must do this because we can't pass the environment variable SCRIPT_NAME
		// to the cvsweb script (maybe this can be fixed in the future?)
		$content = str_replace("/".$server_script, $_SERVER["SCRIPT_NAME"], $content);
	}
} else {
	$unix_name = $Group->getUnixName();

	// Call to ViewCVS CGI locally (see viewcvs_utils.php)

	// see what type of plugin this project if using
	if ($Group->usesPlugin('scmcvs')) {
		$repos_type = 'cvs';
	} elseif ($Group->usesPlugin('scmsvn')) {
		$repos_type = 'svn';
	}

	// HACK : getSiteMenu in Navigation.class.php use GLOBAL['group_id']
	// to fix missing projet name tab
	$group_id = $Group->getID();

	$content = viewcvs_execute($unix_name, $repos_type);
}

// Set content type header from the value set by ViewCVS
// No other headers are generated by ViewCVS because in generate_etags
// is set to 0 in the ViewCVS config file
$exploded_content = explode("\r\n\r\n", $content);
if (count($exploded_content) > 1) {
	list($headers, $body) = explode("\r\n\r\n", $content);
	$headers = explode("\r\n", $headers);
	$content_type = '';
	$charset = '';
	foreach ($headers as $header) {
		header($header);
		if (preg_match('/^Content-Type:\s*(([^;]*)(\s*;\s*charset=(.*))?)/i', $header, $matches)) {
			$content_type = $matches[2];
			$charset = $matches[4];
		}
	}
} else {
	$body = $content;
}

if (!isset($_GET['view'])) {
	$_GET['view'] = 'none';
}

switch ($_GET['view']) {
	case 'tar':
	case 'co':
	case 'patch': {
		$sysdebug_enable = false;
		if (isset($content_type)) {
			switch ($content_type) {
				case (preg_match('/text\/.*/', $content_type) ? true : false):
				case (preg_match('/.*\/javascript/', $content_type) ? true : false): {
					header('Content-Type: text/plain');
					break;
				}
			}
		}
		echo $body;
		break;
	}
	default: {
		// If we output html and we found the mbstring extension, we
		// should try to encode the output of ViewCVS in UTF-8
		if ($charset != 'UTF-8' && extension_loaded('mbstring'))
			$body = mb_convert_encoding($body, 'UTF-8', $encoding);
		scm_header(array('title'=>_("SCM Repository"),
			'group'=>$Group->getID()));
		echo $body;
		scm_footer();
		break;
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
