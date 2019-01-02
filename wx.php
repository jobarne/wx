<?php
	Header("content-type: application/x-javascript");
	
	include_once "db.php";	// connect to the database
	
	class wx {
		// property declaration
		public $id;
		public $host;
		public $strokeStyle;
		public $lineWidth;
		public $plot;
		
		// method declaration
		public function displayVId() {
			return "Host is:".$this->host; // $this->id;
		}
	}
	
	try {
		$sql = "SELECT * FROM t_hosts ORDER BY host";
		$sth = $db->prepare($sql);
		$sth->execute();
		$wxs = $sth->fetchAll(PDO::FETCH_CLASS, "wx");
	} catch(PDOException $e) {
		echo $e->getMessage();		
	}
	
	//echo json_encode($wxs);
	
	echo "var plots = {};\n";
	
	foreach ($wxs as $wx) {
		echo "plots.".$wx->host." = ".json_encode($wx).";\n";
	}
	
	echo "console.log(plots);";
	
	//die(var_dump($wxs[2]->displayVId()));
?>