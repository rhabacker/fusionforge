<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: category.php,v 1.19 2000/08/31 06:11:35 gherteg Exp $

require "pre.php";    // Initial db and session library, opens session
require "vars.php";
session_require(array('isloggedin'=>'1'));
require "account.php";

if ($group_id && $insert_license && $rand_hash && $form_license) {
	/*
		Hash prevents them from updating a live, existing group account
	*/
	$sql="UPDATE groups SET license='$form_license', license_other='$form_license_other' ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'";
	$result=db_query($sql);
	if (db_affected_rows($result) < 1) {
		exit_error('Error','This is an invalid state. Update query failed. <B>PLEASE</B> report to admin@'.$GLOBALS['sys_default_domain']);
	}

} else {
	exit_error('Error','This is an invalid state. Some form variables were missing.
		If you are certain you entered everything, <B>PLEASE</B> report to admin@'.$GLOBALS['sys_default_domain'].' and
		include info on your browser and platform configuration');
}

$HTML->header(array('title'=>'Project Category'));
echo "<H2>".$Language->REGISTER_step6_title."</H2>";
echo $Language->REGISTER_step6;
?>

<FONT size=-1>
<FORM action="confirmation.php" method="post">
<INPUT TYPE="HIDDEN" NAME="show_confirm" VALUE="y">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="rand_hash" VALUE="<?php echo $rand_hash; ?>">
<P>
<H2><FONT COLOR="RED"><?php echo $Language->REGISTER_step4_warn;?></FONT></H2> 
<P>
<INPUT type=submit name="Submit" value="<?php echo $Language->REGISTER_step6_finish; ?>">
</FORM>
</FONT>

<?php
$HTML->footer(array());

?>

