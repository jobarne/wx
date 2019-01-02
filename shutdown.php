<?php
	if ($_POST['type']=='h') {
		system("sudo shutdown -h now");
	} else {
		system("sudo shutdown -r now");
	}
?>