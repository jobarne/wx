<?php
	include_once "db.php";	// connect to the database
	$db->exec("UPDATE t_hosts SET plot=".$_REQUEST['show']." WHERE host='".$_REQUEST['host']."'");
?>