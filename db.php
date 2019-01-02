<?php
	$servername = "localhost";
	$username = "ssf_xxx";
	$password = "ssf_yyyy";
	$dbname = "ssf_wx4";
	
	try {
		$db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
		// set the PDO error mode to exception
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch(PDOException $e) {
		echo $sql . "<br>" . $e->getMessage();
	}
 ?> 
