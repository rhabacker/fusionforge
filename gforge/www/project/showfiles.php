<?php
/*
	Temporary redirect to prevent breakage of existing installs/links
*/
$group_id = $_REQUEST["group_id"];
$release_id = $_REQUEST["release_id"];

header("Location: /frs/?group_id=$group_id&release_id=$release_id");

?>
