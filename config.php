<?php
	header("Content-type: application/x-javascript");
	echo "host = '".$_SERVER['SERVER_NAME']."';\n";	// hostname or IP address
	echo "port = 9001;\n";
	echo "topic = 'vessels/#';\n"; //"#" for everything		// topic to subscribe to
	echo "useTLS = false;\n";
	echo "username = 'ssf_xxx';\n";
	echo "password = 'ssf_yyy';\n";
	echo "path = '';\n";
	echo "cleansession = true;\n";
	echo "reconnectTimeout = 5000;\n";
	echo "client_id = new Date().getTime().toString();";
?>
