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
		<style>
			/* Hide the number input */
			.full-width-slider .ui-slider-track {
				margin-left: 15px;
			}
			.full-width-slider .ui-rangeslider-sliders {
				margin: 10px;
			}
		</style>
		<script type="text/javascript" src="jquery.min.js"></script>
		<script type="text/javascript" src="mobile/jquery.mobile-1.4.5.min.js"></script>
		<script type="text/javascript" src="mobile/jquery.mobile.swiper.js"></script>
		<script type="text/javascript" src="smoothie.js"></script>
		<script type="text/javascript" src="mqttws31.js"></script>
		<script type="text/javascript" src="config.php"></script>
		<script type="text/javascript">
			$(function() {
				var PlotWidth = Math.round(0.9 * $(window).width());
				var TWDcanvas = document.getElementById("TWD");
				var TWScanvas = document.getElementById("TWS");
				var TWDtx = $("#TWD").get(0).getContext("2d"); // TWDcanvas.getContext("2d");
				var TWStx = $("#TWS").get(0).getContext("2d"); // TWScanvas.getContext("2d");
				var mqtt;
				var plots = {};

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
					//console.log(message);
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
								document.getElementById('TWD_numbers').innerHTML = Math.round(plots[vessel].TWD_ave);
								plots[vessel].TWD_old_time = plots[vessel].TWD_new_time;
								plots[vessel].TWD_virgin = 0;
								document.getElementById('TWD_inst_' + vessel).innerHTML = vessel + ": " + Math.round(obj[i].value * 180 / Math.PI); // + " TWD: " + Math.round(TWD_ave) + " TWD_y_ave: " + Math.round(TWD_y_ave * 100) / 100 + " TWD_x_ave: " + Math.round(TWD_x_ave * 100) / 100;
								if (vessel == "self") document.getElementById('TWD_numbers').innerHTML = Math.round(obj[i].value * 180 / Math.PI);
								<?php 
									if ($parameter['flip']==1) {
										echo "if (plots[vessel].TWD_ave>180) {
												plots[vessel].TWDtimeSeries.append(plots[vessel].TWD_new_time, plots[vessel].TWD_ave - 360);
											} else {
												plots[vessel].TWDtimeSeries.append(plots[vessel].TWD_new_time, plots[vessel].TWD_ave);
											}";
									} else {
										echo "plots[vessel].TWDtimeSeries.append(plots[vessel].TWD_new_time, plots[vessel].TWD_ave);";
									}								
								?>
								//console.log("Debug:"+plots[vessel].TWD_ave)
								//plots[vessel].TWDtimeSeries.append(plots[vessel].TWD_new_time, plots[vessel].TWD_ave);
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
								plots[vessel].TWStimeSeries.append(plots[vessel].TWS_new_time, plots[vessel].TWS_ave);
								if (obj[i].value > 5) {
									document.getElementById('TWS_inst_' + vessel).innerHTML = vessel + ": " + Math.round(obj[i].value * 1.943844);
								} else {
									document.getElementById('TWS_inst_' + vessel).innerHTML = vessel + ": " + Math.round(obj[i].value * 1.943844 * 10)/10;
								}
								plots[vessel].TWS_virgin = 0;
							}
						}
						console.log("TWD:" + TWD_temp + " / TWS: " + TWS_temp + " Δ:	" + Delta + " TWD:" + Math.round(plots[vessel].TWD_ave) + " / TWS: " + Math.round(plots[vessel].TWS_ave*10)/10 + " <-- " + vessel);
					} else {
						console.log("Wrong topic:" + message.destinationName);
					}
					
				};
			
				console.log("Connecting to host="+ host + ", port=" + port + ", path=" + path + ", TLS=" + useTLS + ", username=" + username + ", password=" + password);
				MQTTconnect();

				var TWDsmoothieChart = new SmoothieChart({
					minValue: <?php echo ($parameter['TWD-min'] ? $parameter['TWD-min'] : "0"); ?>,
					maxValue: <?php echo ($parameter['TWD-max'] ? $parameter['TWD-max'] : "360"); ?>,
					grid:{millisPerLine:5000},
					millisPerPixel: Math.round((timeframe.value*60000)/PlotWidth),
					timestampFormatter: function(date) {
						function pad2(number) { 
							return (number < 10 ? '0' : '') + number 
						}
						if (TWSsmoothieChart.options.millisPerPixel>300){
							return pad2(date.getHours()) + ':' + pad2(date.getMinutes());
						} else {
							return pad2(date.getHours()) + ':' + pad2(date.getMinutes()) + ':' + pad2(date.getSeconds());
						}
					},
					grid: { 
						strokeStyle: 'rgb(0, 0, 0)', 
						fillStyle: 'rgb(255, 255, 255)',
						sharpLines:true,
						lineWidth: 1, 
						millisPerLine: Math.round(timeframe.value*6000), 
						//verticalSections: 12
						verticalSections: <?php echo round(($parameter['TWD-max'] - $parameter['TWD-min']) / 10) / ceil(($parameter['TWD-max'] - $parameter['TWD-min']) / 180); ?>
					},
					labels: {
						fillStyle:'#ff0000',
						fontSize:20,
						precision:0
					}
				});
				
				var TWSsmoothieChart = new SmoothieChart({
					minValue: <?php echo ($parameter['TWS-min'] ? $parameter['TWS-min'] : "0"); ?>,
					maxValue: <?php echo ($parameter['TWS-max'] ? $parameter['TWS-max'] : "15"); ?>,
					millisPerPixel: Math.round((timeframe.value*60000)/PlotWidth),
	//				timestampFormatter: SmoothieChart.timeFormatter,
					timestampFormatter: function(date) {
						function pad2(number) { 
							return (number < 10 ? '0' : '') + number 
						}
						if (TWSsmoothieChart.options.millisPerPixel>300){
							return pad2(date.getHours()) + ':' + pad2(date.getMinutes());
						} else {
							return pad2(date.getHours()) + ':' + pad2(date.getMinutes()) + ':' + pad2(date.getSeconds());
						}
					},
					grid: { 
						strokeStyle: 'rgb(0, 0, 0)', 
						fillStyle: 'rgb(255, 255, 255)',
						sharpLines:true,
						lineWidth: 1, 
						millisPerLine: Math.round(timeframe.value*6000), 
						verticalSections: <?php echo round($parameter['TWS-max'] - $parameter['TWS-min']); ?>
					},
					labels: {
						fillStyle:'#ff0000',
						fontSize:20,
						precision:0
					}
				});
				
<?php
					try {
						foreach ($hosts as $host) {
							if ($host['plot']) {
								echo "				plots.".$host['host']." = {TWD_virgin:1, TWS_virgin:1, TWD_old_time:new Date().getTime(), TWS_old_time:new Date().getTime()};\n";
								echo "				plots.".$host['host'].".TWDtimeSeries = new TimeSeries();\n";
								echo "				plots.".$host['host'].".TWStimeSeries = new TimeSeries();\n";
								echo "				TWDsmoothieChart.addTimeSeries(plots.".$host['host'].".TWDtimeSeries, { strokeStyle:'".$host['strokeStyle']."', lineWidth:".$host['lineWidth']." });\n";
								echo "				TWSsmoothieChart.addTimeSeries(plots.".$host['host'].".TWStimeSeries, { strokeStyle:'".$host['strokeStyle']."', lineWidth:".$host['lineWidth']." });\n";
								echo "				console.log(plots.".$host['host'].");\n\n";
								$sql = "SELECT MAX(UNIX_TIMESTAMP( timer ) *1000) AS timer, IF( degrees (atan2( sum(sin(radians(TW_Dirn))) , sum(cos(radians(TW_Dirn))) ) )<".($parameter['flip'] ? "-180" : "0").", 360+degrees ( atan2( sum(sin(radians(TW_Dirn))), sum(cos(radians(TW_Dirn))) )), degrees(atan2(sum(sin(radians(TW_Dirn))), sum(cos(radians(TW_Dirn))) )) ) AS TW_Dirn, AVG( TW_speed ) AS TW_speed FROM t_session_val WHERE host = '".$host['host']."' AND TW_Dirn<>0 AND TW_speed<>0 AND timer>=(NOW() - INTERVAL ".($parameter['timeframe']*2)." MINUTE) GROUP BY ROUND( UNIX_TIMESTAMP( timer ) DIV ".$parameter['damping']." ) ORDER BY timer\n";
								echo "//".$sql;
								foreach ($db->query($sql) as $data) {
									echo "				plots.".$host['host'].".TWDtimeSeries.append(".$data['timer'].", ".$data['TW_Dirn'].");\n";
									echo "				plots.".$host['host'].".TWStimeSeries.append(".$data['timer'].", ".$data['TW_speed'].");\n";
								}
							}
						}
					} catch(PDOException $e) {
						//echo "document.getElementById('error').innerHTML = \"ERROR: ". $e->getMessage()."\";";
						echo "$('#error').text( \"ERROR: ". $e->getMessage()."\" ).delay(10000).fadeOut('slow');";		
					}
?>				
				TWDsmoothieChart.streamTo(document.getElementById("TWD", 500));
				TWSsmoothieChart.streamTo(document.getElementById("TWS", 500));
				TWD_virgin = TWS_virgin = 1;
				TWD_ave = 180;
				TWD_y_ave = -1;
				TWD_x_ave = 0;
				TWS_ave = 5;
				TWD_old_time = TWS_old_time = new Date().getTime();
				
				$("#timeframe").change( function() {
					TWSsmoothieChart.options.millisPerPixel = Math.round((timeframe.value*60000)/PlotWidth);
					TWSsmoothieChart.options.grid.millisPerLine = Math.round(timeframe.value*6000);
					TWDsmoothieChart.options.millisPerPixel = Math.round((timeframe.value*60000)/PlotWidth);
					TWDsmoothieChart.options.grid.millisPerLine = Math.round(timeframe.value*6000);
					$.post( "settings.php", { plot: "index.php", "timeframe": $(this).val()} );
					//location.reload();
				});
				
				$("#TWD_flip").change( function() {
					if ($("#TWD_flip").val()==1) {
						$("#TWD-min").prop({
							min: -180,
							max: 180
						});
						$("#TWD-max").prop({
							min: -180,
							max: 180
						});
						$("#TWD-min").val(-180).slider("refresh");
						$("#TWD-max").val(180).slider("refresh");
						$.post( "settings.php", { plot: "index.php", "flip": $(this).val(), "TWD-min": -180, "TWD-max": 180 } );
					} else {
						$("#TWD-min").prop({
							min: 0,
							max: 360
						});
						$("#TWD-max").prop({
							min: 0,
							max: 360
						});
						$("#TWD-min").val(0).slider("refresh");
						$("#TWD-max").val(360).slider("refresh");	
						$.post( "settings.php", { plot: "index.php", "flip": $(this).val(), "TWD-min": 0, "TWD-max": 360 } );
					}
					//location.reload();
				});
				
				$("#TWD-min").change( function() {
					if ($(this).val() < TWDsmoothieChart.options.maxValue) { // only update resonable values
						TWDsmoothieChart.options.minValue = $(this).val();
						TWDsmoothieChart.options.grid.verticalSections = Math.round((TWDsmoothieChart.options.maxValue - TWDsmoothieChart.options.minValue) / 10) / Math.ceil((TWDsmoothieChart.options.maxValue - TWDsmoothieChart.options.minValue) / 180);
						$.post( "settings.php", { plot: "index.php", "TWD-min": $(this).val() } );
					}
				});
				
				$("#TWD-max").change( function() {
					if ($(this).val() > TWDsmoothieChart.options.minValue) { // only update resonable values
						TWDsmoothieChart.options.maxValue = $(this).val();
						TWDsmoothieChart.options.grid.verticalSections = Math.round((TWDsmoothieChart.options.maxValue - TWDsmoothieChart.options.minValue) / 10) / Math.ceil((TWDsmoothieChart.options.maxValue - TWDsmoothieChart.options.minValue) / 180);
						$.post( "settings.php", { plot: "index.php", "TWD-max": $(this).val() } );
					}
				});
				
				$("#TWS-min").change( function() {
					if ($(this).val() < TWSsmoothieChart.options.maxValue) { // only update resonable values
						TWSsmoothieChart.options.minValue = $(this).val();
						TWSsmoothieChart.options.grid.verticalSections = Math.round(TWSsmoothieChart.options.maxValue - TWSsmoothieChart.options.minValue);
						$.post( "settings.php", { plot: "index.php", "TWS-min": $(this).val() } );
					}
				});
				
				$("#TWS-max").change( function() {
					if ($(this).val() > TWSsmoothieChart.options.minValue) { // only update resonable values
						TWSsmoothieChart.options.maxValue = $(this).val();
						TWSsmoothieChart.options.grid.verticalSections = Math.round(TWSsmoothieChart.options.maxValue - TWSsmoothieChart.options.minValue);
						$.post( "settings.php", { plot: "index.php", "TWS-max": $(this).val() } );
					}
				});
				
				$("#damping").change( function() {
					$.post( "settings.php", { plot: "index.php", "damping": $(this).val() } );
					//location.reload();
				});
				
				$("#TWD").mousemove(function(eventObject) {
					var TWDrect = TWDcanvas.getBoundingClientRect(); // Needed as element and bitmat are not the same
					mouseYdown = eventObject.pageY - this.offsetTop;
					TWDtx.font = "30px Arial";
					TWDtx.fillStyle="#FF0000";
					TWDtx.fillText(Number(TWDsmoothieChart.options.minValue) + Math.round((TWDsmoothieChart.options.maxValue-TWDsmoothieChart.options.minValue)*(TWDrect.height-1-mouseYdown)/TWDrect.height),10,95);
					TWDsmoothieChart.options.horizontalLines = [ { value: Number(TWDsmoothieChart.options.minValue) + Math.round((TWDsmoothieChart.options.maxValue-TWDsmoothieChart.options.minValue)*(TWDrect.height-1-mouseYdown)/TWDrect.height), color: '#ff0000', lineWidth: 1 } ];
				});
				
				$("#TWS").mousemove(function(eventObject) {
					var TWSrect = TWScanvas.getBoundingClientRect();
					mouseYdown = eventObject.pageY - this.offsetTop;
					TWStx.font = "30px Arial";
					TWStx.fillStyle="#FF0000";
					TWStx.fillText(Number(TWSsmoothieChart.options.minValue) + Math.round((TWSsmoothieChart.options.maxValue-TWSsmoothieChart.options.minValue)*(TWSrect.height-1-mouseYdown)*10/TWSrect.height)/10,10,95);
					TWSsmoothieChart.options.horizontalLines = [ { value: Number(TWSsmoothieChart.options.minValue) + Math.round((TWSsmoothieChart.options.maxValue-TWSsmoothieChart.options.minValue)*(TWSrect.height-1-mouseYdown)*10/TWSrect.height)/10, color: '#ff0000', lineWidth: 1 } ];
				});

			<?php
				if ($parameter['TWD-show']==0) echo "$( \"#TWD\" ).hide();\n$( \"#TWD_inst\" ).hide();";
				if ($parameter['TWS-show']==0) echo "$( \"#TWS\" ).hide();\n$( \"#TWS_inst\" ).hide();";
			?>		
	/*			TWDcanvas.width  = $(window).width()-50;
				TWDcanvas.height  = Math.round($(window).height()/<?php echo (1+$parameter['TWS-show']); ?>-100);
				TWScanvas.width  = $(window).width()-50;
				TWScanvas.height  = Math.round($(window).height()/<?php echo (1+$parameter['TWS-show']); ?>-100);
	*/			
				$( "#TWD_show" ).click(function() {
					if ($( "#TWD" ).is(":visible")) {
						$( "#TWD" ).hide();
						$.post( "settings.php", { plot: "index.php", "TWD-show": 0 } );
					} else {
						$( "#TWD" ).show();
						$.post( "settings.php", { plot: "index.php", "TWD-show": 1 } );
						//location.reload();
					}
					$( "#TWD_inst" ).toggle();
					resize_canvas();
				});
				
				$( "#TWS_show" ).click(function() {
					if ($( "#TWS" ).is(":visible")) {
						$( "#TWS" ).hide();
						$.post( "settings.php", { plot: "index.php", "TWS-show": 0 } );
					} else {
						$( "#TWS" ).show();
						$.post( "settings.php", { plot: "index.php", "TWS-show": 1 } );
						// location.reload();
					}
					$( "#TWS_inst" ).toggle();
					resize_canvas();
				});
				
				$( "#submit_session" ).click(function() {
					$("#menu").hide();
					$("#new_session_button").text( "<?php echo gettext('New session is created'); ?>" );
					window.setTimeout('$("#new_session_button").text( "<?php echo gettext('Create new session'); ?>" )',60000);
					//alert($("#delete_data").is(":checked") + " / " + ($("#create_session").is(":checked")=='true') + " / " + $("#session_name").val());
					$.post( "start.php", { delete_data: $("#delete_data").is(":checked"), create_session: $("#create_session").is(":checked"), session_name: $("#session_name").val()} );
				});
				
				$( "#repair_db" ).click(function() {
					$("#menu").hide();
					$("#repair_button").text( "<?php echo gettext('Database is reset'); ?>" );
					window.setTimeout('$("#repair_button").text( "<?php echo gettext('Reset database'); ?>" )',60000);
					$.post("repair.php");
				});
				
				$( "#shutdown_wx" ).click(function() {
					$("#menu").hide();
					$("#shutdown_button").text( "<?php echo gettext('Weather station is closing down'); ?>" );
					window.setTimeout('$("#shutdown_button").text( "<?php echo gettext('Turn off'); ?>" )',60000);
					$.post( "shutdown.php", { type: 'h'} );
				});
				
				$( ".plot_boxes" ).click(function(e) {
					console.log(e.target.checked);
					if(e.target.checked) {
						$('#error').text( '<?php echo gettext('Please refresh page to add station'); ?>' ).fadeOut().fadeIn().fadeOut().fadeIn().fadeOut().fadeIn().fadeOut().fadeIn().delay(10000).fadeOut('slow');
					} else {
						delete plots[e.target.name.replace("plot_", "")];
						document.getElementById("TWD_inst_" + e.target.name.replace("plot_", "")).outerHTML="";
						document.getElementById("TWS_inst_" + e.target.name.replace("plot_", "")).outerHTML="";
					}
					$.post( "select_plots.php", { "host": e.target.name.replace("plot_", ""), "show": (e.target.checked ? 1 : 0) } );
				});
			});
			
			function resize_canvas(){
				TWDcanvas = document.getElementById("TWD");
				TWScanvas = document.getElementById("TWS");
				if ($( "#TWS" ).is(":visible")) {
					TWDcanvas.height  = Math.round($(window).height()/2-90);
				} else {
					TWDcanvas.height  = $(window).height()-120;
				}
				if ($( "#TWD" ).is(":visible")) {
					TWScanvas.height  = Math.round($(window).height()/2-90);
				} else {
					TWScanvas.height  = $(window).height()-120;
				}
				TWDcanvas.width  = $(window).width()-50;
				TWScanvas.width  = $(window).width()-50;
				if($(window).width() < 1.5 * $(window).height()) {
					$( "#TWD_numbers" ).css( "font-size", Math.round($(window).width()*0.667-90)) ;
				} else {
					$( "#TWD_numbers" ).css( "font-size", $(window).height()-90 );
				}
			}
			</script>
	</head>
	<body onresize="resize_canvas()" onload="resize_canvas()" >
	<form class="full-width-slider">
	<div id="graphs" data-role="page" data-swipeleft="#numbers">
		<div data-role="header" >
			<a href="#menu" class="ui-btn ui-btn-left ui-btn-inline ui-mini ui-corner-all ui-icon-bars ui-btn-icon-notext">Meny</a>
			<a href="#settings" class="ui-btn ui-btn-right ui-btn-inline ui-mini ui-corner-all ui-icon-gear ui-btn-icon-notext">Settings</a>
		</div><!-- /header -->
		<div role="main" class="ui-content">
			<div data-role="panel" id="settings" data-display="overlay" data-position="right" data-position-fixed="true" >
					<fieldset data-role="controlgroup" data-type="horizontal">
						<input type="checkbox" name="TWD_show" id="TWD_show" <?php if ($parameter['TWD-show']==1) echo "checked"; ?>>
						<label for="TWD_show"><?php echo gettext('Show TWD'); ?></label>
						<input type="checkbox" name="TWS_show" id="TWS_show" <?php if ($parameter['TWS-show']==1) echo "checked"; ?>>
						<label for="TWS_show"><?php echo gettext('Show TWS'); ?></label>
					</fieldset>
					<label for="timeframe"><?php echo gettext('Set time frame').":"; ?></label>
					<select id="timeframe" name="timeframe">
				<?php 
					$options =array("1'"=>1, "10'"=>10, "30'"=>30, "1h"=>60, "3h"=>180, "6h"=>360);
					foreach ($options as $label=>$value) {
						if ($parameter['timeframe']==$value) {
							echo "		<option value='".$value."' selected>".$label."</option>\n";
						} else {
							echo "		<option value='".$value."'>".$label."</option>\n";
						}
					}
				?>
					</select>
					<div id="TWD_rangeslider" data-role="rangeslider" >
						<label for="TWD-min" ><?php echo gettext('TWD range'); ?></label>
						<input name="TWD-min" id="TWD-min" type="range" class="ui-hidden-accessible" data-show-value="true" data-popup-enabled="true" min="<?php echo ($parameter['flip'] ? "-180" : "0"); ?>" max="<?php echo ($parameter['flip'] ? "180" : "360"); ?>" step="10" value="<?php echo ($parameter['TWD-min'] ? $parameter['TWD-min'] : "0"); ?>">
						<label for="TWD-max" ><?php echo gettext('TWD range'); ?></label>
						<input name="TWD-max" id="TWD-max" type="range" class="ui-hidden-accessible" data-show-value="true" data-popup-enabled="true" min="<?php echo ($parameter['flip'] ? "-180" : "0"); ?>" max="<?php echo ($parameter['flip'] ? "180" : "360"); ?>" step="10" value="<?php echo ($parameter['TWD-max'] ? $parameter['TWD-max'] : "360"); ?>">
					</div>
					<label for="TWD_flip"><?php echo gettext('Flip TWD'); ?></label>
					<select name="TWD_flip" id="TWD_flip" data-role="slider">
						<option value="0" <?php if ($parameter['flip']==0) echo "selected"; ?>><?php echo gettext('Off'); ?></option>
						<option value="1" <?php if ($parameter['flip']==1) echo "selected"; ?>><?php echo gettext('On'); ?></option>
					</select>
					<div id="TWS_rangeslider" data-role="rangeslider" >
						<label for="TWS-min" ><?php echo gettext('TWS range'); ?></label>
						<input name="TWS-min" id="TWS-min" type="range" class="ui-hidden-accessible" data-show-value="true" data-popup-enabled="true" min="0" max="30" value="<?php echo ($parameter['TWS-min'] ? $parameter['TWS-min'] : "0"); ?>">
						<label for="TWS-max" ><?php echo gettext('TWS range'); ?></label>
						<input name="TWS-max" id="TWS-max" type="range" class="ui-hidden-accessible" data-show-value="true" data-popup-enabled="true" min="0" max="30" value="<?php echo ($parameter['TWS-max'] ? $parameter['TWS-max'] : "15"); ?>">
					</div>
					<label for="damping" ><?php echo gettext('Damping'); ?></label>
					<input name="damping" id="damping" type="range" data-show-value="true" data-popup-enabled="true" min="10" max="600" step="10" value="<?php echo ($parameter['damping'] ? $parameter['damping'] : "20"); ?>">
					<fieldset data-role="controlgroup" data-type="horizontal" data-mini="true">
						<legend><?php echo gettext('Select stations to plot'); ?>:</legend>
<?php
					foreach ($hosts as $host) {
						echo "     <input type='checkbox' class='plot_boxes' name='plot_".$host['host']."' id='plot_".$host['host']."' ".($host['plot'] ? "checked" : "")." >";
						echo "     <label for='plot_".$host['host']."'>".$host['host']."</label>";
					}
?>
					</fieldset>
			</div>
			
			<div data-role="panel" id="menu" data-display="overlay" data-position="left" data-position-fixed="true" data-theme="b" >
				<ul data-role="listview">
					<!--<li><a href="settime.php" data-ajax="false" >Ställ in tid</a></li>-->
					<li><a href="#new_session" id="new_session_button" name="new_session_button" data-rel="popup" data-position-to="window" data-transition="pop"><?php echo gettext('Create new session'); ?></a></li>
					<li><a href="#repair" id="repair_button" name="repair_button" data-rel="popup" data-position-to="window" data-transition="pop"><?php echo gettext('Reset database'); ?></a></li>
					<li><a href="hosts.php" data-ajax="false" ><?php echo gettext('Handle stations'); ?></a></li>
					<li><a href="/wifi/index.php?page=wpa_conf" data-ajax="false" ><?php echo gettext('Configure internet'); ?></a></li>
					<li><a href="map.php" data-ajax="false" ><?php echo gettext('Show map'); ?></a></li>
					<li><a href="backup.php" data-ajax="false" ><?php echo gettext('Download backup'); ?></a></li>
					<li><a href="#shutdown" id="shutdown_button" name="shutdown_button" data-rel="popup" data-position-to="window" data-transition="pop"><?php echo gettext('Turn off station'); ?></a></li>
				</ul>
			</div>

			<canvas id="TWD" name="TWD" width="1000" height="200"><?php echo gettext('Canvas not supported'); ?></canvas>
<?php
				foreach ($hosts as $host) {
					if ($host['plot']) echo "<div id='TWD_inst_".$host['host']."' style='display:inline-block;width:150px;color:".$host['strokeStyle'].";'>".$host['host']." wait...</div>  ";
				}
?>
			<span id="error" style="color:red;"></span><br /><br />
			
			<canvas id="TWS" id="TWS" width="1000" height="200"><?php echo gettext('Canvas not supported'); ?></canvas>
<?php
				foreach ($hosts as $host) {
					if ($host['plot']) echo "<div id='TWS_inst_".$host['host']."' style='display:inline-block;width:150px;color:".$host['strokeStyle'].";'>".$host['host']." wait...</div>  ";
				}
?>

			<div id="new_session" data-history="false" data-role="popup" class="ui-corner-all ui-content">
				<label for="delete_data"><?php echo gettext('Delete previous data today'); ?></label>
				<input type="checkbox" id="delete_data" name="delete_data">
				<label for="create_session"><?php echo gettext('Create new session'); ?></label>
				<input type="checkbox" id="create_session" name="create_session" checked>
				<label for="session_name"><?php echo gettext('Session name'); ?></label>
				<input type="text" id="session_name" name="session_name" placeholder="<?php echo date('Y-m-d');?>" value="<?php echo date('Y-m-d');?>">
				<fieldset class="ui-grid-b">
				<div class="ui-block-a"><a href="#" id="submit_session" name="submit_session" data-rel="back" class="ui-btn ui-btn-inline ui-corner-all"><?php echo gettext('Submit'); ?></a></div>
				<div class="ui-block-b"><a href="#" data-rel="back" class="ui-btn ui-btn-inline ui-corner-all"<?php echo gettext('Cancel'); ?>></a></div>
				<div class="ui-block-c"><input type="reset" value="<?php echo gettext('Reset'); ?>"></div>
			</div>
			
			<div id="repair" name="repair" data-history="false" data-role="popup" class="ui-corner-all ui-content">
				<?php echo gettext('A reset delete all previous data but can be necessary if the table is locked. Are you really sure?'); ?>
				<fieldset class="ui-grid-a">
					<div class="ui-block-a"><a href="#" id="repair_db" name="repair_db" data-rel="back" class="ui-btn ui-btn-inline ui-corner-all"><?php echo gettext('Reset'); ?></a></div>
					<div class="ui-block-b"><a href="#" data-rel="back" class="ui-btn ui-btn-inline ui-corner-all"><?php echo gettext('Regret'); ?></a></div>
				</div>
			</div>
			
			<div id="shutdown" name="shutdown" data-history="false" data-role="popup" class="ui-corner-all ui-content">
				<?php echo gettext('Are you really sure?'); ?>
				<fieldset class="ui-grid-a">
					<div class="ui-block-a"><a href="#" id="shutdown_wx" name="shutdown_wx" data-rel="back" class="ui-btn ui-btn-inline ui-corner-all"><?php echo gettext('Turn off'); ?></a></div>
					<div class="ui-block-b"><a href="#" data-rel="back" class="ui-btn ui-btn-inline ui-corner-all"><?php echo gettext('Regret'); ?></a></div>
				</div>
			</div>
		</div><!-- /content -->
	</div><!-- /page -->
	
	<div id="numbers" data-role="page" data-swiperight="#graphs" >

		<div role="main" class="ui-content">
			<div id="TWD_numbers" name="TWD_numbers" style="color:red;font-size: 502px" ></div>
		</div><!-- /content -->

		<div data-role="footer" data-position="fixed">
			<a data-iconpos="notext" data-icon="bars" href="#huvudmeny"><?php echo gettext('Main menu'); ?></a>
		</div><!-- /footer -->
	</div><!-- /page -->
	</form>
	</body>
</html>