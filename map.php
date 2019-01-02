<?php
	include_once "db.php";	// connect to the database
	$hosts = $db->query("SELECT * FROM t_hosts ORDER BY host")->fetchAll();
	eval("\$parameter = \$_POST + \$_GET + array(".$db->query("SELECT params from t_plot_parameters WHERE plot='".substr(strrchr($_SERVER['SCRIPT_NAME'],"/"),1)."'")->fetch(PDO::FETCH_OBJ)->params.");");
?>
<!DOCTYPE html>
<html>
	<head>
		<title>SSF WX</title>		
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="mobile/jquery.mobile-1.4.5.min.css" />
		<link rel="stylesheet" href="mobile/jquery.mobile.theme-1.4.5.min.css" />
        <link rel="stylesheet" href="leaflet.css">
		<link rel="stylesheet" href="Control.FullScreen.css">
		<script type="text/javascript" src="jquery.min.js"></script>
		<script type="text/javascript" src="mobile/jquery.mobile-1.4.5.min.js"></script>
		<script type="text/javascript" src="mqttws31.js"></script>
		<script type="text/javascript" src="config.php"></script>
		<script type="text/javascript" src="leaflet.js"></script>
		<script type="text/javascript" src="Control.FullScreen.js"></script>  
		<script type="text/javascript" src="leaflet-omnivore.min.js"></script>
		<script type="text/javascript" src="leaflet-windbarb.js"> </script>
		<script type="text/javascript" src="wx.php" ></script>
    
  <style type="text/css">
        #mapPage, #map {
            height: 100%;
        }

        #map-content{
            height: 100%;
            padding: 0px; 
            margin:0px;
            z-index: -1;
        }
  </style>

		<script>			
			$(function() {
				TWD_virgin = TWS_virgin = 1;
				TWD_old_time = new Date().getTime();
				
				var i=0;

				var mqtt;
				var plots = {};
			
				var element = $('#mapPage');

				element.height(element.height() - 42);
				
				var map = L.map('map', {
					zoom: <?php echo $parameter['zoom']; ?>,
					zoomControl: true,
					fullscreenControl: true,
					center: [<?php echo $parameter['Latitude']; ?>, <?php echo $parameter['Longitude']; ?>]
				});
				map.zoomControl.setPosition('topright');
				L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png').addTo(map);
				
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
			
				function MQTTconnect() {
					mqtt = new Paho.MQTT.Client(host, port, path, client_id);
					var options = {
						timeout: 3,
						userName: username,
						password: password,
						useSSL: useTLS,
						cleanSession: cleansession,
						onSuccess: onConnect,
						onFailure: function (message) {
							console.log("Connection failed: " + message.errorMessage + " Retrying");
							setTimeout(MQTTconnect, reconnectTimeout);
						}
					};

					mqtt.onConnectionLost = onConnectionLost;
					mqtt.onMessageArrived = onMessageArrived;
					
					function onConnect() {
						console.log('Connected to ' + host + ':' + port + path + " for topic: " + topic);
						// Connection succeeded; subscribe to our topic
						mqtt.subscribe(topic, {qos: 0});
					}
				
					mqtt.connect(options);
				}

				function onConnectionLost(response) {
					console.log("connection lost: " + response.errorMessage + ". Reconnecting");
					setTimeout(MQTTconnect, reconnectTimeout);
					document.body.style.backgroundColor = null;
				};

				function onMessageArrived(message) {
					console.log(message);
					var topic = message.destinationName;
					var value = message.payloadString;
					new_time = new Date().getTime();
					//if (message.destinationName=="vessels/self") {
					//if (plots.indexOf(message.destinationName.split("/")[1])!=-1) {
					var vessel = message.destinationName.split("/")[1];
					if (plots.hasOwnProperty(vessel)) {
						var signalk = JSON.parse(value);
						var obj = signalk.updates[0].values;
						for (i = 0; i < obj.length; i++) { 
							if (obj[i].path == "environment.wind.directionMagnetic" && !(obj[i].value == 0)) {
								plots[vessel].TWD_new_time = new Date().getTime();
								TWD_temp = Math.round(obj[i].value * 180 / Math.PI); // debug
								if (plots[vessel].TWD_virgin) {
									plots[vessel].TWD_y_ave = Math.sin(obj[i].value);
									plots[vessel].TWD_x_ave = Math.cos(obj[i].value);
								} else {
									plots[vessel].TWD_y_ave = (1 - (plots[vessel].TWD_new_time - plots[vessel].TWD_old_time) * 0.001 / <?php echo $parameter['damping']; ?>) * plots[vessel].TWD_y_ave + ((plots[vessel].TWD_new_time - plots[vessel].TWD_old_time) * 0.001 / <?php echo $parameter['damping']; ?>) * Math.sin(obj[i].value);
									plots[vessel].TWD_x_ave = (1 - (plots[vessel].TWD_new_time - plots[vessel].TWD_old_time) * 0.001 / <?php echo $parameter['damping']; ?>) * plots[vessel].TWD_x_ave + ((plots[vessel].TWD_new_time - plots[vessel].TWD_old_time) * 0.001 / <?php echo $parameter['damping']; ?>) * Math.cos(obj[i].value);							
								}
								plots[vessel].TWD_ave = Math.atan2(plots[vessel].TWD_y_ave, plots[vessel].TWD_x_ave) * 180 / Math.PI;
								if (plots[vessel].TWD_ave < 0) plots[vessel].TWD_ave = 360 + plots[vessel].TWD_ave;
								<?php //if ($parameter['flip']==0) echo "if (TWD_ave<0) TWD_ave = 360 + TWD_ave;"; ?>
								//document.getElementById('TWD_numbers').innerHTML = Math.round(plots[vessel].TWD_ave);
								plots[vessel].TWD_old_time = plots[vessel].TWD_new_time;
								//plots[vessel].TWDtimeSeries.append(plots[vessel].TWD_new_time, plots[vessel].TWD_ave);
								plots[vessel].TWD_virgin = 0;
								//document.getElementById('TWD_inst_' + vessel).innerHTML = vessel + ": " + Math.round(obj[i].value * 180 / Math.PI); // + " TWD: " + Math.round(TWD_ave) + " TWD_y_ave: " + Math.round(TWD_y_ave * 100) / 100 + " TWD_x_ave: " + Math.round(TWD_x_ave * 100) / 100;
								//if (vessel == "self") document.getElementById('TWD_numbers').innerHTML = Math.round(obj[i].value * 180 / Math.PI);
							}
							if (obj[i].path == "environment.wind.speedTrue" && !(obj[i].value == 0)) {
								//console.log("TWS:" + obj[i].value * 1.943844);
								plots[vessel].TWS_new_time = new Date().getTime();
								if (plots[vessel].TWS_virgin) {
									plots[vessel].TWS_ave = obj[i].value * 1.943844;
								} else {
									plots[vessel].TWS_ave = (1 - (plots[vessel].TWS_new_time - plots[vessel].TWS_old_time) * 0.001 / <?php echo $parameter['damping']; ?>) * plots[vessel].TWS_ave + ((plots[vessel].TWS_new_time - plots[vessel].TWS_old_time) * 0.001 / <?php echo $parameter['damping']; ?>) * obj[i].value * 1.943844;
								}
								TWS_temp = Math.round(obj[i].value*10)/10; // debug
								Delta = Math.round((plots[vessel].TWS_new_time - plots[vessel].TWS_old_time) * 0.001 * 100 / <?php echo $parameter['damping']; ?>)/100; // debug
								plots[vessel].TWS_old_time = plots[vessel].TWS_new_time;
								//plots[vessel].TWStimeSeries.append(plots[vessel].TWS_new_time, plots[vessel].TWS_ave);
								if (obj[i].value > 5) {
									//document.getElementById('TWS_inst_' + vessel).innerHTML = vessel + ": " + Math.round(obj[i].value * 1.943844);
								} else {
									//document.getElementById('TWS_inst_' + vessel).innerHTML = vessel + ": " + Math.round(obj[i].value * 1.943844 * 10)/10;
								}
								plots[vessel].TWS_virgin = 0;
							}
							if (obj[i].path == "navigation.position" && !(obj[i].value == 0)) {
								plots[vessel].Latitude = obj[i].value.latitude;
								plots[vessel].Longitude = obj[i].value.longitude;
								console.log("Latitude:" + obj[i].value.latitude);
								console.log("Longitude:" + obj[i].value.longitude);
							}
						}
						plots[vessel].icon = L.WindBarb.icon({deg: plots[vessel].TWD_ave, speed: plots[vessel].TWS_ave, pointRadius: 5, strokeLength: 20, fillColor: plots[vessel].Color});
						plots[vessel].LMarker.setIcon(plots[vessel].icon);
						plots[vessel].LMarker.setLatLng([plots[vessel].Latitude, plots[vessel].Longitude]);
						console.log("TWD:" + TWD_temp + " / TWS: " + TWS_temp + " Δ:	" + Delta + " TWD:" + Math.round(plots[vessel].TWD_ave) + " / TWS: " + Math.round(plots[vessel].TWS_ave*10)/10 + " <-- " + vessel);
					} else {
						console.log("Wrong topic:" + message.destinationName);
					}
				}
				
				console.log("Connecting to host="+ host + ", port=" + port + ", path=" + path + ", TLS=" + useTLS + ", username=" + username + ", password=" + password);
				MQTTconnect();
				
				map.on('zoomend', function() {
					console.log(map.getZoom());
					$.post( "settings.php", { plot: "map.php", "zoom": map.getZoom() } );
					//$.post( "settings.php", { plot: "index.php", "TWD-show": 0 } );
				});
				
				map.on('moveend', function() {
					console.log(map.getCenter().toString());
					$.post( "settings.php", { plot: "map.php", "Latitude": map.getCenter().lat, "Longitude": map.getCenter().lng } );
					//$.post( "settings.php", { plot: "index.php", "TWD-show": 0 } );
				});
			});
		</script>

  
</head>

<body>
      <body>
		<div data-role="page" id="mapPage" > 
            <div data-role="header" data-position="fixed" >
				<a href="#menu" class="ui-btn ui-btn-left ui-btn-inline ui-mini ui-corner-all ui-icon-bars ui-btn-icon-notext">Meny</a>
            </div> 

            <div id="map-content" data-role="content">
				<div data-role="panel" id="menu" data-display="overlay" data-position="left" data-position-fixed="true" data-theme="b" >
					<ul data-role="listview">
						<!--<li><a href="settime.php" data-ajax="false" >Ställ in tid</a></li>-->
						<li><a href="#new_session" id="new_session_button" name="new_session_button" data-rel="popup" data-position-to="window" data-transition="pop"><?php echo gettext('Create new session'); ?></a></li>
						<li><a href="#repair" id="repair_button" name="repair_button" data-rel="popup" data-position-to="window" data-transition="pop"><?php echo gettext('Reset database'); ?></a></li>
						<li><a href="hosts.php" data-ajax="false" ><?php echo gettext('Handle stations'); ?></a></li>
						<li><a href="index.php" data-ajax="false" ><?php echo gettext('Show graph'); ?></a></li>
						<li><a href="backup.php" data-ajax="false" ><?php echo gettext('Download backup'); ?></a></li>
						<li><a href="#shutdown" id="shutdown_button" name="shutdown_button" data-rel="popup" data-position-to="window" data-transition="pop"><?php echo gettext('Turn off station'); ?></a></li>
					</ul>
				</div>
				<div id="map"></div>
            </div>
			
			
        </div> 
    </body>
  
</body>

</html>