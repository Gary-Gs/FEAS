<?php require_once('../../../Connections/db_ntu.php');
	  require_once('../../../CSRFProtection.php');
	  require_once('../../../Utility.php');?>
<?php
		
	$csrf = new CSRFProtection();
	
	$_REQUEST['validate'] =	$csrf->cfmRequest();
	
	
	
	//Set Values (Exam Settings)
	if(isset($_REQUEST['exam_year']))
	{
		$examYear = $_REQUEST['exam_year'];
		
	
			
			$stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['allocation_settings_others'] . " (`id`, `exam_year`) VALUES (2 , ?) ON DUPLICATE KEY UPDATE `exam_year`= VALUES(`exam_year`)");
			$stmt->bindParam(1, $examYear);
			$stmt->execute();
		
	}
	
	if(isset($_REQUEST['exam_sem']))
	{
		$examSem = $_REQUEST['exam_sem'];
		
			
			$stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['allocation_settings_others'] . " (`id`, `exam_sem`) VALUES (2 , ?) ON DUPLICATE KEY UPDATE `exam_sem`= VALUES(`exam_sem`)");
			$stmt->bindParam(1, $examSem);
			$stmt->execute();
		
	}
	if(isset($_REQUEST['alloc_days']))
	{
		for($i=1; $i<sizeof($_REQUEST['alloc_days']); $i++){
			$allocDays = $_REQUEST['alloc_days'][$i];
			
			
			$id = $i+1;	
			
				
			$stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['allocation_settings_general_part_time'] . " (`id`, `alloc_date`) VALUES (? , ?) ON DUPLICATE KEY UPDATE `alloc_date`= VALUES(`alloc_date`)");
			$stmt->bindParam(1, $id);
			$stmt->bindParam(2, $allocDate );
			$stmt->execute();
		}
		
	}
	
		if(isset($_REQUEST['alloc_date'])) {
			$allocDate = $_REQUEST['alloc_date'];
			
			//for ($i=0;$i<3;$i++ ) {
				$id = 1;
				
				//$dateAlloc1 = new DateTime($allocDate);
				//$dayAdd = "P". $i. "D";
				//$dateAllocStr = $dateAlloc1->add(new DateInterval($dayAdd))->format('Y-m-d');
				
				$stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['allocation_settings_general_part_time'] . " (`id`, `alloc_date`) VALUES (? , ?) ON DUPLICATE KEY UPDATE `alloc_date`= VALUES(`alloc_date`)");
				$stmt->bindParam(1, $id);
				$stmt->bindParam(2, $allocDate );
				$stmt->execute();
			//}	
		
	}
	
	if(isset($_REQUEST['number_of_days']))
	{
		$noOfDays = $_REQUEST['number_of_days'];
		
		$stmt = $conn_db_ntu->prepare("UPDATE ".$TABLES['allocation_settings_others']. " SET alloc_days= ? ". "WHERE type= 'PT'");	
		$stmt->bindParam(1, $noOfDays);
		$stmt->execute();
		
	}
	
	if(isset($_REQUEST['start_time']))
	{
		$check = $_REQUEST['start_time'];
		

		for ($i=0; $i<sizeof($_REQUEST['start_time']); $i++){
			$startTime = $_REQUEST['start_time'][$i];

				$id = $i+1;
				
			
				$stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['allocation_settings_general_part_time'] . " (`id`, `alloc_start`) VALUES (? , ?) ON DUPLICATE KEY UPDATE `alloc_start`= VALUES(`alloc_start`)");
				$stmt->bindParam(1, $id);
				$stmt->bindParam(2, $startTime);
				$stmt->execute();
			}
		
	}
	
	if(isset($_REQUEST['end_time']))
	{
		$endTime = $_REQUEST['end_time'];
		
			
			for($i=0; $i<sizeof($_REQUEST['end_time']); $i++){
				$endTime = $_REQUEST['end_time'][$i];
		
				
				$id = $i+1;
				
				$stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['allocation_settings_general_part_time'] . " (`id`, `alloc_end`) VALUES (? , ?) ON DUPLICATE KEY UPDATE `alloc_end`= VALUES(`alloc_end`)");
				$stmt->bindParam(1, $id);
				$stmt->bindParam(2, $endTime);
				$stmt->execute();
			 
		}
		
	}
	
	if(isset($_REQUEST['duration']))
	{
			
			for($i=0; $i<sizeof($_REQUEST['duration']); $i++){
				$duration = $_REQUEST['duration'][$i];
				
					
					$id = $i+1;
					
		
					$stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['allocation_settings_general_part_time'] . " (`id`, `alloc_duration`) VALUES (? , ?) ON DUPLICATE KEY UPDATE `alloc_duration`= VALUES(`alloc_duration`)");
					$stmt->bindParam(1, $id);
					$stmt->bindParam(2, $duration );
					$stmt->execute();
				
            }
			
		
		
	}
	// opt out option
	// delete all opt-out values for all 3 days
	for($i=0;$i<3;$i++){
		$optOut=0;
		$id = $i+1;
		
		$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general_part_time'] . " (`id`, `opt_out`) VALUES ( ? , ?) ON DUPLICATE KEY UPDATE `opt_out`= VALUES(`opt_out`)");
		$stmt->bindParam(1, $id);
		$stmt->bindParam(2, $optOut);
		$stmt->execute();
	}
	if(isset($_REQUEST['opt_out'])){ // there some checkbox value selected
		for($i=0; $i<sizeof($_REQUEST['opt_out']); $i++){
			$id = $_REQUEST['opt_out'][$i];
			$value = 1;
			
			$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general'] . " (`id`, `opt_out`) VALUES ( ? , ?) ON DUPLICATE KEY UPDATE `opt_out`= VALUES(`opt_out`)");
			$stmt->bindParam(1, $id);
			$stmt->bindParam(2, $value);
			$stmt->execute();
		}
    }
	//Set Values (Room)
	try
	{
		$conn_db_ntu->exec("DELETE FROM ".$TABLES['allocation_settings_room_part_time']);
	}
	catch (PDOException $e)
	{
		die($e->getMessage());
	}
	
	
	$i=1;
	$j=1;
	$roomDay1Array= array();
	$roomDay2Array= array();
	$roomDay3Array= array();
	
	
	while(isset($_REQUEST['room_'.$i])) {
		$roomName = $_REQUEST['room_'.$i];
		if(empty($roomName)){
			echo "Empty";
		}
		else {
			
			$roomDay1Array[$i] = $roomName;
			
		}
		$i++;
	}
	
	$i=1;
	while(isset($_REQUEST['room1_'.$i])) {
		
		$roomName2 = $_REQUEST['room1_'.$i];
		if(empty($roomName2))
			echo "Empty2";
		else {
			$roomDay2Array[$i] = $roomName2;
			
		}
		$i++;
	}

	
	$i=1;
	while(isset($_REQUEST['room2_'.$i])) {
		$roomName3 = $_REQUEST['room2_'.$i];
		if(empty($roomName3))
			echo "Empty3";
		else {
			$roomDay3Array[$i] = $roomName3;
			
		}
		$i++;
	}
	
	
	//find a way to run a loop?
	if (isset ($roomDay1Array)) {
		
		insUpdateRoom($roomDay1Array,1);
		
	}
	if (isset ($roomDay2Array)) {
		insUpdateRoom($roomDay2Array,2);
	}
	if (isset ($roomDay3Array)){
		insUpdateRoom($roomDay3Array,3);
	}
	
	function insUpdateRoom ($roomDayArray,$day) {
		global $conn_db_ntu, $TABLES;
		$roomArr= json_encode($roomDayArray);
		if ($roomArr != null ) {       
               
			$stmt1 = $conn_db_ntu->prepare("SELECT * FROM ".$TABLES['allocation_settings_room_part_time']." WHERE day = ?");
			$stmt1->bindParam(1, $day);
			$stmt1->execute();
					
			if ($stmt1->rowCount() == 0) {  
					
				$stmt1 = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['allocation_settings_room_part_time']." ( `day`, `roomArray`) VALUES (? , ?)");
			    $stmt1->bindParam(1, $day);
				$stmt1->bindParam(2, $roomArr);
				$stmt1->execute();       
			}
			else  {    
				$stmt1 = $conn_db_ntu->prepare("UPDATE ".$TABLES['allocation_settings_room_part_time']." SET roomArray = ? where day = ?");
				$stmt1->bindParam(1, $roomArr);  
				$stmt1->bindParam(2, $day);
				$stmt1->execute();            
			}
		}	
	}
    
	//end of inserting for room allocation
	
	//apply to all 
        if(isset($_REQUEST['apply_to_all'])){
			//loop 3 days 
			for($i=0; $i<3; $i++){
				$id = $i+1;
				$checkDuration = $_REQUEST['duration'][0];
				
					
					
					$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general_part_time'] . " (`id`, `alloc_duration`) VALUES ( ? , ? ) ON DUPLICATE KEY UPDATE `alloc_duration`= VALUES(`alloc_duration`)");
					$stmt->bindParam("1", $id);
					$stmt->bindParam("2", $checkDuration);
					$stmt->execute();				
				    
						$checkStart = $_REQUEST['start_time'][0];
				
					
					$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general_part_time'] . " (`id`, `alloc_start`) VALUES ( ? , ? ) ON DUPLICATE KEY UPDATE `alloc_start`= VALUES(`alloc_start`)");
					$stmt->bindParam(1, $id);
					$stmt->bindParam(2, $checkStart);
					$stmt->execute();	
				
                    
				$checkEnd = $_REQUEST['end_time'][0];
				
					
					$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general_part_time'] . " (`id`, `alloc_end`) VALUES ( ? , ? ) ON DUPLICATE KEY UPDATE `alloc_end`= VALUES(`alloc_end`)");
					$stmt->bindParam(1, $id);
					$stmt->bindParam(2, $checkEnd);
					$stmt->execute();
				

				// delete all room allocation 
				$conn_db_ntu->exec("DELETE FROM ".$TABLES['allocation_settings_room_part_time']);
                    
				$roomNo=1;
				$roomArray= array();
				while(isset($_REQUEST['room_'.$roomNo])) {
					$roomName = $_REQUEST['room_'.$roomNo];
					if(empty($roomName)){
						echo "Empty";
					}
					else {
						$roomArray[$roomNo] = $roomName;
					}
					$roomNo++;
				}
				if (isset ($roomArray)) {
					$noOfDays= $_REQUEST['number_of_days'];
					for ($m=1;$m<=$noOfDays;$m++) {
						insUpdateRoom($roomArray,$m);
				
					}
				}
			}
		}			
	$conn_db_ntu = null;
?>

<?php
	if (isset ($_REQUEST['validate'])) {
	  header("location:allocation_setting.php?validate=1");
	}
	else {
		header("location:allocation_setting.php?save=1");
	}
	exit;
?>
