<?php require_once('Connections/db_ntu.php');


session_start();

if(!isset($_SESSION['login'])){
	header("location: ../../../login.php");
	exit;
}
else if (isLoginSessionExpired()) {
	header("location: ../../../logout.php?session_expired=1");
	exit;
} 
function isLoginSessionExpired() {
	$timeOutDuration = 7200; //120 mins for now
	$current_time = time(); 
	
	if(isset($_SESSION['loginTime']) and isset($_SESSION["login"])){  
		if(((time() - $_SESSION['loginTime']) > $timeOutDuration)){ 
			return true; 
		} 
	}
	return false;
}
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") {
			if (PHP_VERSION < 6) {
				$theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
			}
			global $hostname_db_ntu, $username_db_ntu, $password_db_ntu, $database_db_ntu;
			$connection = mysqli_connect($hostname_db_ntu, $username_db_ntu, $password_db_ntu, $database_db_ntu);
			
			$theValue = function_exists("mysqli_real_escape_string") ? mysqli_real_escape_string($connection,$theValue) : mysqli_escape_string($connection,$theValue);
			
			switch ($theType) {
				case "text":
					$theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
					break;    
				case "long":
				case "int":
					$theValue = ($theValue != "") ? intval($theValue) : "NULL";
					break;
				case "double":
					$theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
					break;
				case "date":
					$theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
					break;
				case "defined":
					$theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
					break;
			}
			return $theValue;
		} 
function retrieveRooms ($day, $tableStr) {
	
	global $conn_db_ntu, $TABLES;
	
	$stmt1 = $conn_db_ntu->prepare("SELECT roomArray FROM ".$TABLES[$tableStr]." WHERE day = ? ");
	$stmt1->bindParam(1, $day);
	$stmt1->execute();
	$rooms = $stmt1->fetchAll();
	
	if (sizeof($rooms)>0) {
		$roomsNewArr = (array) json_decode($rooms[0]["roomArray"]);
	
		for($j=1;$j<=sizeof($roomsNewArr);$j++) {
			$rooms_table[] = new Room((string)$j, $roomsNewArr[$j]);
		}
		return $rooms_table;
	}
	else {
		return null;
	}
	
}	
function SystemLog($user, $query, $remark)
	{
		global $TABLES, $conn_db_ntu;
		
		//$logQuery = sprintf("INSERT INTO %s (`user_id`, `query_exec`, `query_remark`) VALUES ('%s', '%s', '%s')",
		//					$TABLES['fea_log'],
		//					$user, mysqli_real_escape_string(mysqli_connect(),$query), //$remark);
		
		//$conn_db_ntu->exec($logQuery);
        $logQuery	= "INSERT INTO ". $TABLES['fea_log'] . " (user_id, query_exec, query_remark) VALUES (?, ?, ?)";
		$stmt = $conn_db_ntu->prepare ($logQuery);
		$stmt->bindParam(1, $user);
		$stmt->bindParam(2, $query);
		$stmt->bindParam(3, $remark);
		$stmt->execute();		
		//echo "SYSTEM LOG: ".$remark.'<br/>';
	}
 ?>