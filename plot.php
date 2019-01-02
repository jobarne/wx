<?php
	include_once "db.php";	// connect to the database
	eval("\$parameter = \$_POST + \$_GET + array(".$db->query("SELECT params from t_plot_parameters WHERE plot='".substr(strrchr($_SERVER['SCRIPT_NAME'],"/"),1)."'")->fetch(PDO::FETCH_OBJ)->params.");");

	$data = $db->query("SELECT FROM_UNIXTIME(MAX(UNIX_TIMESTAMP( timer ) *1000)/1000) AS timer, IF( degrees (atan2( sum(sin(radians(TW_Dirn))) , sum(cos(radians(TW_Dirn))) ) )<".($parameter['flip'] ? "-180" : "0").", 360+degrees ( atan2( sum(sin(radians(TW_Dirn))), sum(cos(radians(TW_Dirn))) )), degrees(atan2(sum(sin(radians(TW_Dirn))), sum(cos(radians(TW_Dirn))) )) ) AS TW_Dirn, AVG( TW_speed ) AS TW_speed FROM t_session_val WHERE host = '".$parameter['host']."' AND TW_Dirn<>0 AND TW_speed<>0 AND timer>=(NOW() - INTERVAL ".$parameter['timeframe']." MINUTE) GROUP BY ROUND( UNIX_TIMESTAMP( timer ) DIV ".$parameter['damping']." ) ORDER BY timer")->fetchAll(PDO::FETCH_ASSOC);
	$host = $db->query("SELECT * FROM t_hosts WHERE host = '".$parameter['host']."'")->fetch();
	
	function data_item($row, $params) {
		return $row[$params];
	}
	
	function data_array($param, $data2slice) {
		return array_map("data_item", $data2slice, array_fill(0, count($data2slice), $param));
	}
?>
<!DOCTYPE html>
<html>
<head>
	<script src="plotly-latest.min.js"></script>
</head>
<body>
	<p>
	<!-- Plots go in blank <div> elements. 
		You can size them in the plot layout,
		or give the div a size as shown here.
	-->
	<div id="plot1" style="width:90%;height:90%;"></div>
	
<?php
	
?>
	
	<script type="text/javascript">
		plot1 = document.getElementById('plot1');

		Plotly.plot( plot1, 
			[
				{
					x: ['<?php echo implode("', '", data_array('timer', $data)); ?>'],
					y: [<?php echo implode(", ", data_array('TW_Dirn', $data)); ?>],
					name: '<?php echo $parameter['host']." - TW_Dirn"; ?>',
					mode: 'lines+markers',
					line: {width: 3, shape: 'spline', smoothing: 1.3, color:<?php echo "'".$host['strokeStyle']."'"; ?>},
					type: 'scatter'
				},
				{
					x: ['<?php echo implode("', '", data_array('timer', $data)); ?>'],
					y: [<?php echo implode(", ", data_array('TW_speed', $data)); ?>],
					name: '<?php echo $parameter['host']." - TW_speed"; ?>',
					yaxis: 'y2',
					mode: 'lines+markers',
					line: {dash: 'dot',width: 3, shape: 'spline', smoothing: 1.3, color:<?php echo "'".$host['strokeStyle']."'"; ?>},
					type: 'scatter'
				}			
			], 
			{ 
				margin: { t: 0 },
				yaxis: {title: 'yaxis title'},
				yaxis: {title: 'TW_Dirn'},
				yaxis2: {
					title: 'TW_speed',
					overlaying: 'y',
					side: 'right'
				},
			} 
		);

		/* Current Plotly.js version */
		console.log( Plotly.BUILD );
	</script>
</body>
</html>

