<?php
	include_once "db.php";	// connect to the database
	
	$session_id = $db->query("SELECT max(session_id) AS max_id from t_session_val")->fetch();
	
	//$filename = "backup-" . date("d-m-Y") . ".sql.gz";
	//$mime = "application/x-gzip";
	$filename = "backup-" . date("d-m-Y") . ".sql";
	$mime = "application/text";

	header( "Content-Type: " . $mime );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	
	//$cmd = "mysqldump -u ssf_wx3 --password=ssf_ceas --disable-keys --no-create-info --skip-add-locks --where=\"session_id='".$session_id['max_id']."'\" ssf_laser t_session_val | gzip --best";
	$cmd = "mysqldump -u ".$username." --password=".$password." --insert-ignore --disable-keys --no-create-info --skip-add-locks --where=\"session_id='".$session_id['max_id']."' AND host NOT LIKE 'self'\" ".$dbname." t_session_val";

	passthru( $cmd );

	//echo $cmd;
	exit(0);
?>