<?php require_once('../../../Connections/db_ntu.php'); 
	   require_once('../../../CSRFProtection.php');
	    require_once('../../../Utility.php');?>

<?php
		
	$csrf = new CSRFProtection();
	
	$_REQUEST['validate']=$csrf->cfmRequest();
	
	
	
	//Set Values (General)
	$error_code = -1;
	$projectID = null;
	
	if(isset($_REQUEST['user_id']) && isset($_REQUEST['project_id']))
	{
		$user = $_REQUEST['user_id'];
		
		$projectID = $_REQUEST['project_id'];
		
		
		$query_rsProjectAssign	= "SELECT * from ".$TABLES['allocation_result_part_time']." WHERE project_id = ?";
		try
		{
			$stmt = $conn_db_ntu->prepare ($query_rsProjectAssign);
		    $stmt->bindParam(1, $projectID);
			$stmt->execute();
			$projectData =$stmt->fetch();
			
			//$projectData = $conn_db_ntu->query($query_rsProjectAssign)->fetch();
		}
		catch (PDOException $e)
		{
			die("1." .$e->getMessage());
		}
		
		if ($projectData != null)	//Valid Project
		{
			$examinerID = (isset($_REQUEST['examiner'])) ? $_REQUEST['examiner'] : -2;
			//$examinerID = GetSQLValueString($examinerID, "text");
			
			$exam_day =  (isset($_REQUEST['exam_day'])) ? $_REQUEST['exam_day'] : -2;
			//$exam_day = GetSQLValueString($exam_day, "int");
			
			$exam_slot =  (isset($_REQUEST['exam_slot'])) ? $_REQUEST['exam_slot'] : -2;
			//$exam_slot = GetSQLValueString($exam_slot, "int");

			$exam_room =  (isset($_REQUEST['exam_room'])) ? $_REQUEST['exam_room'] : -2;
			//$exam_room = GetSQLValueString($exam_room, "int");
			
			$hasEmpty = ($examinerID == -1 || $exam_day == -1 || $exam_slot == -1 || $exam_room == -1);
			
			if ($hasEmpty)
			{
				/*$updateQuery = sprintf("UPDATE %s SET `day`=NULL, `slot`=NULL, `room`=NULL, `clash`='0' WHERE `project_id`='%s'",
											$TABLES['allocation_result_part_time'],
											$projectID);*/
				//$conn_db_ntu->exec($updateQuery);
				
				$updateQuery = "UPDATE ". $TABLES['allocation_result_part_time']. " SET day = NULL, slot = NULL, room = NULL, clash = 0 WHERE project_id = ?";
				$stmt = $conn_db_ntu->prepare ($updateQuery);
				$stmt->bindParam(1, $projectID);
				$stmt->execute();							
				
				SystemLog($user, $updateQuery, "Delete allocation for $projectID");
				
			}
			else
			{
				//Update Examiner
				//$existExaminer = null;
				$query_rsExaminer = "SELECT * FROM ".$TABLES['staff']." WHERE `id`= ?";
				try
				
				{
					$stmt = $conn_db_ntu->prepare ($query_rsExaminer);
					$stmt->bindParam(1, $examinerID);
					$stmt->execute();
					$existExaminer  =$stmt->fetch();
					//$existExaminer 	= $conn_db_ntu->query($query_rsExaminer)->fetch();
				}
				catch (PDOException $e)
				{
					die($e->getMessage());
				}

				if ($existExaminer && $examinerID != $projectData['examiner_id'] )	//Valid Examiner and Examiner Changed
				{
					/*$updateQuery = sprintf("UPDATE %s SET `examiner_id`='%s' WHERE `project_id`='%s'",
											$TABLES['allocation_result_part_time'],
											$examinerID,
											$projectID);*/
					//$conn_db_ntu->exec($updateQuery);						
					$updateQuery = "UPDATE ". $TABLES['allocation_result_part_time']. " SET examiner_id = ? WHERE project_id = ?";
					$stmt = $conn_db_ntu->prepare ($updateQuery);
					$stmt->bindParam(1, $examinerID);
					$stmt->bindParam(2, $projectID);
					$stmt->execute();
					
					SystemLog($user, $updateQuery, "Set $projectID allocated examiner to $examinerID");
				}
				
				//Update Examination Day
				//$existDay = null;
				$query_rsDay = "SELECT * FROM ".$TABLES['allocation_result_part_time']." WHERE `day`= ?";
				try
				{
					$stmt = $conn_db_ntu->prepare ($query_rsDay);
					$stmt->bindParam(1, $exam_day);
					$stmt->execute();
					$existDay   = $stmt->fetch();
					//$existDay	= $conn_db_ntu->query($query_rsDay)->fetch();
				}
				catch (PDOException $e)
				{
					die($e->getMessage());
				}
				
				if ($existDay && $exam_day != $projectData['day'] )	//Valid Day and Day Changed
				{
					//$updateQuery = sprintf("UPDATE %s SET `day`='%s' WHERE `project_id`='%s'",
					//						$TABLES['allocation_result_part_time'],
					//						$exam_day,
					//						$projectID);
					//$conn_db_ntu->exec($updateQuery);
					
					$updateQuery = "UPDATE ". $TABLES['allocation_result_part_time']. " SET day = ? WHERE project_id = ?";
					$stmt = $conn_db_ntu->prepare ($updateQuery);
					$stmt->bindParam(1, $exam_day);
					$stmt->bindParam(2, $projectID);
					$stmt->execute();
					
					SystemLog($user, $updateQuery, "Set $projectID allocated day to $exam_day");
					
					
				}

				//Update Examination Slot
				//$existSlot = null;
				//$query_rsSlot = "SELECT * FROM //".$TABLES['allocation_result_timeslot_part_time']." WHERE `id`= ?";
				$query_rsSlot = "SELECT * FROM ".$TABLES['allocation_result_timeslot_part_time']." WHERE day = ? and slot = ?";	
				try
				{
					$stmt = $conn_db_ntu->prepare ($query_rsSlot);
					$stmt->bindParam(1, $exam_day);
					$stmt->bindParam(2, $exam_slot);
					$stmt->execute();
					$existSlot    = $stmt->fetch();
					//$existSlot  	= $conn_db_ntu->query($query_rsSlot)->fetch();
				}
				catch (PDOException $e)
				{
					die($e->getMessage());
				}
				
				if ($existSlot   && $exam_slot != $projectData['slot'] )	//Valid Slot and Slot Changed
				{
					//$updateQuery = sprintf("UPDATE %s SET `slot`='%s' WHERE //`project_id`='%s'",
					//						$TABLES['allocation_result_part_time'],
					//						$validSlot['slot'],
					//						$projectID);				
					//$conn_db_ntu->exec($updateQuery);
					
					$updateQuery = "UPDATE ". $TABLES['allocation_result_part_time']. " SET slot = ? WHERE project_id = ?";
					$stmt = $conn_db_ntu->prepare ($updateQuery);
					$stmt->bindParam(1, $exam_slot);
					$stmt->bindParam(2, $projectID);
					$stmt->execute();
					SystemLog($user, $updateQuery, "Set $projectID allocated slot to $exam_slot");
				}
				
				//Update Examination Room
				
				//$query_rsRoom = "SELECT * FROM ".$TABLES['allocation_result_room_part_time']." WHERE `id`= ?";
				//$existRoom = null;
				$query_rsRoom = "SELECT roomArray FROM ".$TABLES['allocation_result_room_part_time']." WHERE day = ?";
				try
				{
					$stmt = $conn_db_ntu->prepare ($query_rsRoom);
					$stmt->bindParam(1, $exam_room);
					$stmt->execute();
					$existRoom  = $stmt->fetch();
					//$existRoom 	= $conn_db_ntu->query($query_rsRoom)->fetch();
				}
				catch (PDOException $e)
				{
					die($e->getMessage());
				}
				
				if ($existRoom  && $exam_room != $projectData['room'] )	//Valid Room and Room Changed
				{
					
					$roomValid= false;
					
					$roomArray  = (array)json_decode($existRoom[0]);
					for ($i=1;$i<sizeof($roomArray);$i++) {
						//echo ("room array: ".$roomArray[$i]);
						//echo "<br>";
						//echo ("use room" .$exam_room);
						if ($i == $exam_room) {
							$roomValid = true;
							break;
						}
						
						
					}
					
				
					if ($roomValid) {
						/*$updateQuery = sprintf("UPDATE %s SET `room`='%s' WHERE `project_id`='%s'",
											$TABLES['allocation_result_part_time'],
											$exam_room,
											$projectID);*/
					    //$conn_db_ntu->exec($updateQuery);
						$updateQuery = "UPDATE ". $TABLES['allocation_result_part_time']. " SET room = ? WHERE project_id = ?";
						$stmt = $conn_db_ntu->prepare ($updateQuery);
						$stmt->bindParam(1, $exam_room);
						$stmt->bindParam(2, $projectID);
						$stmt->execute();
						SystemLog($user, $updateQuery, "Set $projectID allocated room to $exam_room");
						
					}
					
					
					SystemLog($user, $updateQuery, "Set $projectID allocated room to $exam_room");
					
				}
			}
			
			//Clash Calculation
			$resetClash = sprintf("UPDATE %s SET clash=0", $TABLES['allocation_result_part_time']);
			$conn_db_ntu->exec($resetClash);
			
			$clashQuery = sprintf("SELECT f1.project_id as pid FROM %s f1, %s f2 WHERE f1.project_id<>f2.project_id AND f1.day=f2.day AND f1.room=f2.room AND f1.slot=f2.slot",
									$TABLES['allocation_result_part_time'],
									$TABLES['allocation_result_part_time']);
			$rs_clash = $conn_db_ntu->query($clashQuery)->fetchAll();
			foreach($rs_clash as $clash)
			{
				if ($clash['pid'] == $projectID) {
					$error_code = 1;	//Has Clash
				}
				//$setClash = sprintf("UPDATE %s SET clash=1 WHERE project_id='%s'", $TABLES['allocation_result_part_time'], $clash['pid']);
				//$conn_db_ntu->exec($setClash);
				
				$setClash = "UPDATE ". $TABLES['allocation_result_part_time']. " SET clash= 1 WHERE project_id = ?";
				$stmt = $conn_db_ntu->prepare ($setClash);	
				$stmt->bindParam(1, $clash['pid']);
				$stmt->execute();
			}
		}
		
		if ($error_code == -1) {
			$error_code = 0;
		}
	}
	
	$conn_db_ntu = null;
?>

<?php
		if(isset ($_REQUEST['validate'])) {
		   header("location:examiner_setting.php?validate=1");
	    }
		else if ($projectID != null)
		{
			if ($error_code == 1)
				header("location:allocation_edit.php?project=".$projectID."&warn=1");	//Has clashes
			else
				header("location:allocation_edit.php?project=".$projectID."&save=1");
		}
		else {
			header("location:allocation.php");
		}
		exit;
	?>
