#! /usr/bin/php4 -f
<?php
/**
 * GForge Cron Job
 *
 * The rest Copyright 2002-2005 (c) GForge Team
 * http://gforge.org/
 *
 * @version   $Id$
 *
 * This file is part of GForge.
 *
 * GForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GForge; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require ('squal_pre.php');
require ('common/include/cron_utils.php');
require_once('common/mail/MailingList.class');
require_once('common/mail/MailingListFactory.class');

require_once('common/include/SCM.class') ;

setup_plugin_manager () ;

/**
 * Retrieve a file into a temporary directory from a CVS server
 *
 * @param String $repos Repository Name
 * @param String $file File Name
 *
 * return String the FileName in the working repository
 */
function checkout_cvs_file($repos,$file) {
//echo "$repos,$file\n";
        $actual_dir = getcwd();
        $tempdirname = tempnam("/tmp","cvstracker");
        if (!$tempdirname) 
                return false;
        if (!unlink($tempdirname))
                return false;

        // Create the temporary directory and returns its name.
        if (!mkdir($tempdirname))
                return false;

        if (!chdir($tempdirname))
                return false;
        system("cvs -d ".$repos." co ".$file);

        chdir($actual_dir);
        return $tempdirname."/".$file;
}

/**
 * commit_cvs_file commit a file to the repository
 *
 * @param String $repos Repository
 * @param String $file to commit
 * @param String $message to commit
 */
function commit_cvs_file($repos,$file,$message="Automatic updated by cvstracker") {
	$actual_dir = getcwd();
	chdir(dirname($file));	
	system("cvs -d ".$repos." ci -m \"".$message."\" ".basename($file));
	// unlink (basename($file));
	chdir($actual_dir);
}

/**
 * release_cvs_file - Remove the file that was checked out from cvs
 * @see checkout_cvs_file
 */
function release_cvs_file($file) {
	// $file is something like /tmp/(tmp_dir)/path/to/file
	// we must delete /tmp/tmp_dir
	if (!preg_match("/^(\\/tmp\\/[^\\/]*)\\/.* /", $file, $result)) {		// Make sure the dir is under /tmp
		echo "Trying to release a directory not in /tmp. Skipping...";
		return;
	}
	$dir = $result[1];
	
	// this shouldn't happen... but add it as a security checke
	if (util_is_root_dir($dir)) {
		echo "Trying to delete root dir. Skipping...";
		return;
	}
	$dir = escapeshellarg($dir);
	system("rm -rf ".$dir);
}

function write_File($filePath, $content) {
	$file = fopen($filePath, 'a');
	flock($file, LOCK_EX);
	ftruncate($file, 0);
	rewind($file);
	if(!empty($content)) {
		fwrite($file, $content);
	}
	flock($file, LOCK_UN);
	fclose($file);
}

/**
 *add_sync_mail write to /CVSROOT/loginfo unix_name-commits@lists.gforge.company.com
 *
 *@param $unix_group_name Name Group
 *@return void
 *@date 2004-10-25
 */
function add_sync_mail($unix_group_name) {

	global $sys_lists_host;
	global $cvsdir_prefix;
	$loginfo_file=$cvsdir_prefix.'/'.$unix_group_name.'/CVSROOT/loginfo';

	if (!$loginfo_file) {
		echo "Couldn't get loginfo for $unix_group_name";
		return;
	}

	$content = file_get_contents ($loginfo_file);
	if ( strstr($content, "syncmail") == FALSE) {
//		echo $unix_group_name.":Syncmail not found in loginfo.Adding\n";
		$pathsyncmail = "ALL ".
			dirname(__FILE__)."/syncmail -u %p %{sVv} ".
			$unix_group_name."-commits@".$sys_lists_host."\n";
		$content .= "\n#BEGIN Added by cvs.php script\n".
			$pathsyncmail. "\n#END Added by cvs.php script\n";
		$loginfo_file = checkout_cvs_file($cvsdir_prefix.'/'.$unix_group_name,'CVSROOT/loginfo');
		if(is_file($loginfo_file)){
			echo $unix_group_name.":About to write the lines\n";
			write_File($loginfo_file, $content, 1);
		}
		commit_cvs_file($cvsdir_prefix."/".$unix_group_name,$loginfo_file);
		release_cvs_file($loginfo_file);
	} else {
//		echo "Syncmail Found!\n";
	}
}

/**
 * Function to add cvstracker lines to a loginfo file
 * @param   string  the unix_group_name
 *
 */
function add_cvstracker($unix_group_name) {
	global $cvsdir_prefix, $sys_plugins_path, $cvs_binary_version;
	$loginfo_file=$cvsdir_prefix.'/'.$unix_group_name.'/CVSROOT/loginfo';

	if (!$loginfo_file) {
		echo "Couldn't get loginfo for $unix_group_name";
		return;
	}

	$content = file_get_contents ($loginfo_file);
	if ( strstr($content, "cvstracker") == FALSE) {
        $content = "\n# BEGIN added by gforge-plugin-cvstracker";
        if ( $cvs_binary_version == "1.11" ) {
                $content .= "\nALL ( php -q -d include_path=".ini_get('include_path').
                    " ".$sys_plugins_path."/cvstracker/bin/post.php".
                    " %r %{sVv} )\n";
        }else { //it's version 1.12
            $content .= "\nALL ( php -q -d include_path=".ini_get('include_path').
            " ".$sys_plugins_path."/cvstracker/bin/post.php".
            " %r %p %{sVv} )";
        }
        $content .= "\n# END added by gforge-plugin-cvstracker";

		$loginfo_file = checkout_cvs_file($cvsdir_prefix.'/'.$unix_group_name,'CVSROOT/loginfo');
		if(is_file($loginfo_file)){
			echo $unix_group_name.":About to write the lines\n";
			write_File($loginfo_file, $content, 1);
		}
		commit_cvs_file($cvsdir_prefix."/".$unix_group_name,$loginfo_file);
		release_cvs_file($loginfo_file);
	} else {
//		echo "cvstracker Found!\n";
	}
}

function add_acl_check($unix_group_name) {
	global $cvsdir_prefix;

	$commitinfofile = $cvsdir_prefix."/".$unix_group_name.'/CVSROOT/commitinfo';

	$content = file_get_contents ($commitinfofile);
	if ( strstr($content, "aclcheck") == FALSE) {

		$commitinfofile = checkout_cvs_file($cvsdir_prefix.'/'.$unix_group_name,'CVSROOT/commitinfo');
		$aclcheck = "\n#BEGIN adding cvs acl check".
			"\nALL php -q -d include_path=".ini_get('include_path').
				" ".$GLOBALS['sys_plugins_path']."/scmcvs/bin/aclcheck.php %r %p ".
			"\n#END adding cvs acl check\n";
		writeFile($commitinfofile, $aclcheck, 1);
		commit_cvs_file($cvsdir_prefix."/".$unix_group_name,$commitinfofile);
		release_cvs_file($loginfo_file);
	} else {
//		echo "cvstracker Found!\n";
	}
}

function writeFile($filePath, $content, $append=0) {
	if ($append == 1) {
		$file = fopen($filePath, 'a');
	} else {
		$file = fopen($filePath, 'w');
	}
	flock($file, LOCK_EX);
	if(!empty($content)) {
		fwrite($file, $content);
	}
	flock($file, LOCK_UN);
	fclose($file);
}

function update_cvs_repositories() {
	global $cvsdir_prefix;

	$res = db_query("select groups.group_id,groups.unix_group_name,groups.enable_anonscm,groups.enable_pserver".
		" FROM groups, plugins, group_plugin".
		" WHERE groups.status != 'P' ".
		" AND groups.group_id=group_plugin.group_id ".
		" AND group_plugin.plugin_id=plugins.plugin_id ".
		" AND plugins.plugin_name='scmcvs'");
	
	for($i = 0; $i < db_numrows($res); $i++) {
		/*
			Simply call cvscreate.sh
		*/
		
		$project = &group_get_object(db_result($res,$i,'group_id')); // get the group object for the current group
		
		if ( (!$project) || (!is_object($project))  )  {
			echo "Error Getting Group." . " Id : " . db_result($res,$i,'group_id') . " , Name : " . db_result($res,$i,'unix_group_name');
			break; // continue to the next project
		}
		
		$repositoryPath = $cvsdir_prefix."/".$project->getUnixName();
		if (is_dir($repositoryPath)) {
			$writersContent = '';
			$readersContent = '';
			$passwdContent = '';
			if($project->enableAnonSCM()) {
				$repositoryMode = 02775;
				if ($project->enablePserver()) {
					$readersContent = 'anonymous';
					$passwdContent = 'anonymous:8Z8wlZezt48mY';
				}
			} else {
				$repositoryMode = 02770;
			}
			chmod($repositoryPath, $repositoryMode);
			write_File($repositoryPath.'/CVSROOT/writers', $writersContent);
			write_File($repositoryPath.'/CVSROOT/readers', $readersContent);
			write_File($repositoryPath.'/CVSROOT/passwd', $passwdContent);
			if ($project->usesPlugin('cvssyncmail')) {
				add_sync_mail($project->getUnixName());
			}
			if ($project->usesPlugin('cvstracker')) {
				add_cvstracker($project->getUnixName());
			}
			add_acl_check($project->getUnixName());
		} elseif (is_file($repositoryPath)) {
			$err .= $repositoryPath.' already exists as a file';
		} else {
			$enableAnonSCM = ($project->enableAnonSCM()) ? 1 : 0;
			$enablePserver = ($project->enablePserver()) ? 1 : 0;
			system(dirname(__FILE__).'/cvscreate.sh '.
				$project->getUnixName().
				' '.($project->getID()+50000).
				' '.$enableAnonSCM.
				' '.$enablePserver);
			if ($project->usesPlugin('cvssyncmail')) {
				add_sync_mail($project->getUnixName());
			}
			if ($project->usesPlugin('cvstracker')) {
				add_cvstracker($project->getUnixName());
			}
			add_acl_check($project->getUnixName());
		}
	}
}



/*


	Loop through and create/update each repository for every project 
	that uses SCMCVS plugin


*/
if(is_dir($cvsdir_prefix)) {
	update_cvs_repositories();
} else {
	if(is_file($cvsdir_prefix)) {
		$err .= "$cvsdir_prefix exists but is a file\n";
		exit;
	} else {
		if (mkdir($cvsdir_prefix)) {
			//need to update group permissions using chmod
			update_cvs_repositories();
		} else {
			$err .= "unable to make $cvsdir_prefix directory\n";
			exit;
		}	
	}
}


cron_entry(13,$err);

?>
