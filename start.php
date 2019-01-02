<?php
	include_once "db.php";	// connect to the database

	if (isset($_POST['delete_data'])) {
		$delete_count = $db->exec("DELETE FROM t_session_val WHERE timer > NOW() - INTERVAL 10 HOUR");
		echo "DELETE FROM t_session_val WHERE timer > NOW() - INTERVAL 10 HOUR";
		$response_bits[] = $delete_count." observations deleted";
	}
	
	if (isset($_POST['create_session'])) {
		$insert_count = $db->exec("INSERT INTO t_session (`name`) VALUES ('".$_POST['session_name']."')");
		$response_bits[] = $insert_count." session named ".$_POST['session_name']." created";
	}
	
	$response = implode(" and ", $response_bits);
?>
<html>
<body>
	<form name="start" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
		<label for="delete_data">Delete previous data today</label>
		<input type="checkbox" name="delete_data"><br />
		<label for="new_session">Create new session with name <input type="text" name="session_name" value="<?php echo date('Y-m-d');?>"></label>
		<input type="checkbox" name="create_session" checked><br />
		<input type="submit" value="Submit form" >
	</form>
<?php
	echo $response; 
?>
</body>
</html>