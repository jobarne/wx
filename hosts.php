<?php
	include_once "db.php";	// connect to the database
	$hosts = $db->query("SELECT * FROM t_hosts ORDER BY host")->fetchAll();
	eval("\$parameter = \$_POST + \$_GET + array(".$db->query("SELECT params from t_plot_parameters WHERE plot='".substr(strrchr($_SERVER['SCRIPT_NAME'],"/"),1)."'")->fetch(PDO::FETCH_OBJ)->params.");");
	if (isset($_POST['submit'])) {
		foreach ($hosts as $host) {
			$db->query("UPDATE t_hosts SET host='".$_POST['host'][$host['id']]."', lineWidth='".$_POST['lw'][$host['id']]."', strokeStyle='".$_POST['color'][$host['id']]."', plot='".$_POST['plot'][$host['id']]."' WHERE id='".$host['id']."';");
		}
		$hosts = $db->query("SELECT * FROM t_hosts ORDER BY host")->fetchAll();
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>SSF WX</title>		
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="mobile/jquery.mobile-1.4.5.min.css" />
		<link rel="stylesheet" href="mobile/jquery.mobile.theme-1.4.5.min.css" />
		<script type="text/javascript" src="jquery.min.js"></script>
		<script type="text/javascript" src="mobile/jquery.mobile-1.4.5.min.js"></script>
		<script type="text/javascript" src="mqttws31.js"></script>
		<script type="text/javascript" src="config.php"></script>
		<script type="text/javascript" src="wx.php" ></script>
		<script>			
			$(function() {				
<?php
					try {
						foreach ($hosts as $host) {
							if ($host['plot']) {
								echo "				plots.".$host['host']." = {TWD_virgin:1, TWS_virgin:1, Latitude:0, Longitude:0, Color: '".$host['strokeStyle']."', TWD_old_time:new Date().getTime(), TWS_old_time:new Date().getTime()};\n";
								echo "				plots.".$host['host'].".icon = L.WindBarb.icon({deg: 90, speed: 0, pointRadius: 5, strokeLength: 20, fillColor: \"".$host['strokeStyle']."\"});\n";
								echo "				plots.".$host['host'].".LMarker = L.marker([0, 0], {icon: plots.".$host['host'].".icon});\n";
								echo "				plots.".$host['host'].".LMarker.addTo(map);\n";								
								echo "				plots.".$host['host'].".LMarker.on('click', function() {";
								echo "					window.open('http://".$_SERVER['HTTP_HOST']."/plot.php?host=".$host['host']."');";
								echo "				});";
								echo "				console.log(plots.".$host['host'].");\n\n";
							}
						}
					} catch(PDOException $e) {
						//echo "document.getElementById('error').innerHTML = \"ERROR: ". $e->getMessage()."\";";
						echo "$('#error').text( \"ERROR: ". $e->getMessage()."\" ).delay(10000).fadeOut('slow');";		
					}
?>
			});
		</script>

  
</head>
      <body>

		<div data-role="page" id="mapPage" > 
            <div data-role="header" data-position="fixed" >
				<a href="#menu" class="ui-btn ui-btn-left ui-btn-inline ui-mini ui-corner-all ui-icon-bars ui-btn-icon-notext">Meny</a>
            </div> 

            <div role="main" class="ui-content">
				<div data-role="panel" id="menu" data-display="overlay" data-position="left" data-position-fixed="true" data-theme="b" >
					<ul data-role="listview">
						<!--<li><a href="settime.php" data-ajax="false" >Ställ in tid</a></li>-->
						<li><a href="#new_session" id="new_session_button" name="new_session_button" data-rel="popup" data-position-to="window" data-transition="pop"><?php echo gettext('Create new session'); ?></a></li>
						<li><a href="#repair" id="repair_button" name="repair_button" data-rel="popup" data-position-to="window" data-transition="pop"><?php echo gettext('Reset database'); ?></a></li>
						<li><a href="index.php" data-ajax="false" ><?php echo gettext('Show graph'); ?></a></li>
						<li><a href="map.php" data-ajax="false" ><?php echo gettext('Show map'); ?></a></li>
						<li><a href="backup.php" data-ajax="false" ><?php echo gettext('Download backup'); ?></a></li>
						<li><a href="#shutdown" id="shutdown_button" name="shutdown_button" data-rel="popup" data-position-to="window" data-transition="pop"><?php echo gettext('Turn off station'); ?></a></li>
					</ul>
				</div>
				<?php

	?>
				<form name="stations" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
				<fieldset class="ui-grid-c">
<?php
				foreach ($hosts as $key=>$host) {
					echo "				<div class='ui-block-a'>\n";
					if ($key==0) echo "					<label for='host[".$host['id']."]'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Station</label>\n";
					echo "					<input type='text' name='host[".$host['id']."]' id='host[".$host['id']."]' value='".$host['host']."'></div>\n";
					echo "				<div class='ui-block-b'>\n";
					if ($key==0) echo "					<label for='lw[".$host['id']."]'>Linewidth</label>\n";
					echo "					<input type='range' name='lw[".$host['id']."]' id='lw[".$host['id']."]' value='".$host['lineWidth']."' min='1' max='15'></div>\n";
					echo "				<div class='ui-block-c'>\n";
					if ($key==0) echo "					<label for='color[".$host['id']."]'>Color</label>\n";
					echo "					<input type='color' name='color[".$host['id']."]' id='color[".$host['id']."]' value='".$host['strokeStyle']."'></div>\n";
					echo "				<div class='ui-block-d'>\n";
					if ($key==0) echo "					<label for='plot[".$host['id']."]'>Plot</label>\n";
					echo "						<select name='plot[".$host['id']."]' id='plot[".$host['id']."]' data-role='slider'>\n";
					if ($host['plot']) {
						echo "					    	<option value='0'>No</option>\n";
						echo "					    	<option value='1' selected>Plot</option>\n";
					} else {
						echo "					    	<option value='0' selected>No</option>\n";
						echo "					    	<option value='1'>Plot</option>\n";
					}
					echo "						</select>\n";
					echo "				</div>\n";
				}
?>
				</fieldset>
				<input class="ui-shadow ui-btn ui-corner-all" type="submit" name="submit" id="submit" value="Submit" >
				</form>
			</div>
			
			
        </div> 
    </body>

</html>