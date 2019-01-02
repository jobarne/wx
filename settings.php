<?php
	include_once "db.php";	// connect to the database
	$new_params = $_REQUEST;
	unset($new_params['plot']);
	eval("\$params = \$new_params + array(".$db->query("SELECT params from t_plot_parameters WHERE plot='".$_REQUEST['plot']."'")->fetch(PDO::FETCH_OBJ)->params.");");
	foreach ($params as $key=>$data) {
			$updates[] = "\"".$key."\"=>\"".$data."\"";
	}
	$db->exec("UPDATE t_plot_parameters SET params='".implode($updates,",")."' WHERE plot='".$_REQUEST['plot']."'");
?>