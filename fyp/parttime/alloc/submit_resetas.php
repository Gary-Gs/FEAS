<?php require_once('../../../Connections/db_ntu.php'); 
	  require_once('../../../CSRFProtection.php');?>
<?php
	
	$csrf = new CSRFProtection();
	
	$_REQUEST['validate'] =	$csrf->cfmRequest();
	
	$conn_db_ntu->exec("DELETE FROM ".$TABLES['allocation_settings_general_part_time']);
	$conn_db_ntu->exec("DELETE FROM ".$TABLES['allocation_settings_room_part_time']);
	
	$today = new DateTime();
	 $id = 1;
     for ($i=0; $i<3; $i++){
	//Set Default Values (General)
	//$updateQuery = sprintf("INSERT INTO %s (`id`, `alloc_days`, `alloc_date`, //`alloc_start`, `alloc_end`, `alloc_duration`, `exam_year`, `exam_sem`) VALUES (1, //'%s', '%s', '%s', '%s', '%s', '%s', '%s') ON DUPLICATE KEY UPDATE //`alloc_days`=VALUES(`alloc_days`), `alloc_start`=VALUES(`alloc_start`), //`alloc_end`=VALUES(`alloc_end`), `alloc_duration`=VALUES(`alloc_duration`), //`exam_year`=VALUES(`exam_year`), `exam_sem`=VALUES(`exam_sem`)",
	//						$TABLES['allocation_settings_general_part_time'],
	//						'3', $today->format('Y-m-d'), '08:30:00', '17:30:00', '30', //'1516', '1');
	$updateQuery = sprintf("INSERT INTO %s (`id`, `alloc_date`, `alloc_start`, `alloc_end`, `alloc_duration`) VALUES ($id, '%s', '%s', '%s', '%s') ON DUPLICATE KEY UPDATE  `alloc_start`=VALUES(`alloc_start`), `alloc_end`=VALUES(`alloc_end`), `alloc_duration`=VALUES(`alloc_duration`)",	
							$TABLES['allocation_settings_general_part_time'],
							 $today->format('Y-m-d'), '08:30:00', '17:30:00', '30');
	 $conn_db_ntu->exec($updateQuery);
	 $id++;
     }
	 
	$type = "PT";
	$defaultDays =3;
	$stmt =$conn_db_ntu->prepare ("UPDATE ". $TABLES['allocation_settings_others']. " SET alloc_days = ? WHERE type = ?");
	$stmt->bindParam(1,  $defaultDays);
	$stmt->bindParam(2, $type);
	$stmt->execute();
	
	$examYear = 1718;	
	$examSem = 1;
	$stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['allocation_settings_others'] . " (`id`, `exam_year`, `exam_sem`) VALUES (2,?,?) ON DUPLICATE KEY UPDATE `exam_year`= VALUES(`exam_year`),`exam_sem`= VALUES(`exam_sem`) ");
	$stmt->bindParam(1, $examYear);
	$stmt->bindParam(2, $examSem);
	
	$stmt->execute();
	//Set Default Values (Room)
	$values = array();
	$NO_OF_DAYS =3;
	$roomArrStr = "";
	$roomDayArray = array();
	for($i=1; $i<=9; $i++) {
		$roomName = "TR+ ". $i . " ";
		$roomDayArray[$i] = $roomName;
	}
	
	//Set Default Values (Room)
	for ($day=1;$day<=$NO_OF_DAYS; $day++) {
		
		$roomArr= json_encode($roomDayArray);
		if ($roomArr != null ) {       
               
			$stmt1 = $conn_db_ntu->prepare("SELECT * FROM ".$TABLES['allocation_settings_room_part_time']." WHERE day = ?");
			$stmt1->bindParam("1", $day);
			$stmt1->execute();
			$results = $stmt1->fetchall();		
			if (sizeof($results)> 0) {  
				$stmt1 = $conn_db_ntu->prepare("UPDATE ".$TABLES['allocation_settings_room_part_time']." SET roomArray = ? where day = ?");
				$stmt1->bindParam(1, $roomArr);  
				$stmt1->bindParam(2, $day);
				$stmt1->execute();      	
				
			}
			else  {    
				$stmt1 = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['allocation_settings_room_part_time']." ( `day`, `roomArray`) VALUES (? , ?)");
			    $stmt1->bindParam(1, $day);
				$stmt1->bindParam(2, $roomArr);
				$stmt1->execute();       
				      
			}
		}	
	}
	unset($values);
	
	$conn_db_ntu = null;
?>
<?php
	if (isset ($_REQUEST['validate'])) {
		header("location:allocation_setting.php?validate=1");
	}
	else {
		header("location:allocation_setting.php?reset=1");
	}
	exit;
	?>
