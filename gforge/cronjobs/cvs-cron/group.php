#!/usr/bin/php
<?php
//
//	unix_group_id is database_group_id+50000
//
//this reads the database and creates groups in /etc/group
//this script must be ran before you run the add users to groups first,
//because you need a group to be a member of it

require_once('squal_pre.php');

//1) read in groups from db
$groups = array();
$res=db_query("SELECT group_id,unix_group_name FROM groups");
for($i = 0; $i < db_numrows($res); $i++) {
	$groups[] = db_result($res,$i,'unix_group_name');
	$gids[db_result($res,$i,'unix_group_name')]=db_result($res,$i,'group_id')+50000;
}

//2) read in groups from /etc/group
$h = fopen("/etc/group.backup","r");

if(!$h) {
	die("Groups.php -- unable to open /etc/group for reading");
}

$filecontent = fread($h, filesize("/etc/group.backup"));
fclose($h);
$lines = explode("\n",$filecontent);

//3) if group is listed in the db and not /etc/group add
$h2 = fopen("/etc/group.backup","w");

if(!h2) {
	die("Groups.php -- unable to open /etc/group for writing");
}

//write the group file out again, followed by new gforge stuff
$i = 0;
for($i; $i < count($lines)-1; $i++) {
	fwrite($h2,$lines[$i]."\n");
}
fwrite($h2,$lines[$i]);

//see if there is no group with same name, if not add group, if so don't add group	
foreach($groups as $group) {
	foreach($lines as $line) {
		$etcline = explode(":",$line);

		if($group == $etcline[0]) {
			continue 2;
		}
	}

	$gid = $gids[$group];
	$writegrouptofile = "$group:x:$gid:\n";
	fwrite($h2,$writegrouptofile);
}

fclose($h2);	
?>
