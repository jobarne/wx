<?php
	include_once "db.php";	// connect to the database
	
	$db->exec("DROP TABLE IF EXISTS `t_session_val`;
		CREATE TABLE IF NOT EXISTS `t_session_val` (
		  `session_id` smallint(5) NOT NULL DEFAULT '0',
		  `host` varchar(16) NOT NULL,
		  `timer` datetime NOT NULL,
		  `Latitude` double DEFAULT NULL,
		  `Longitude` double DEFAULT NULL,
		  `Heading` double DEFAULT NULL,
		  `AirPressure` double DEFAULT NULL,
		  `AirTemp` double DEFAULT NULL,
		  `TW_Dirn` smallint(5) DEFAULT NULL,
		  `TW_speed` double DEFAULT NULL,
		  `Ext_COG` double DEFAULT NULL,
		  `Ext_SOG` double DEFAULT NULL,
		  `AW_speed` double DEFAULT NULL,
		  `AW_angle` double DEFAULT NULL,
		  PRIMARY KEY (`host`,`timer`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
	);
	
?>