<?php require_once('../../Connections/db_ntu.php'); 
	 require_once('../../CSRFProtection.php');
	 require_once('../../Utility.php');?>
<?php
	
    $csrf = new CSRFProtection();
	
	$_REQUEST['validate'] =	$csrf->cfmRequest();

	
	$staffid = $_REQUEST['staffid'];

	try
	{
		//$conn_db_ntu->exec("DELETE FROM ".$TABLES['staff_pref']." WHERE staff_id LIKE $sid");
		$stmt = $conn_db_ntu->prepare("DELETE FROM ".$TABLES['staff_pref_part_time']." WHERE staff_id = ? and archive = 0");
		$stmt->bindParam(1, $staffid );
		$stmt->execute();
		
	}
	catch (PDOException $e)
	{
		die($e->getMessage());
	}
	
	$i=1;
	$j=1;
	$cid = "";

	while(isset($_REQUEST['projpref'.$i])) {
		echo "<br/>";
		$projectPref = $_REQUEST['projpref'.$i];
		
		
		if($projectPref=='blank')
			echo "Empty";
		else {

			$stmt = $conn_db_ntu->prepare("SELECT * FROM ".$TABLES['staff_pref_part_time']." WHERE staff_id= ? and prefer= ?");
			$stmt->bindParam(1, $staffid);
			$stmt->bindParam(2, $projectPref);
			$stmt->execute();
			$existProjectPrefs= $stmt->fetchAll();
			if (sizeof ($existProjectPrefs) == 0)
			{
				$stmt = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['staff_pref_part_time']." (staff_id, prefer, choice) VALUES (?, ?, ?)");
				$stmt->bindParam(1, $staffid);
				$stmt->bindParam(2, $projectPref);
				$stmt->bindParam(3, $j);
				$stmt->execute();
				
				$j++;
				//echo "C:".$check.":";
			}
		}
		$i++;
	}
	
	$i=1;
	$j=1;

	while(isset($_REQUEST['areapref'.$i])) {
		echo "<br/>";
		$areaPref = $_REQUEST['areapref'.$i];
		if($areaPref=='blank')
			echo "Empty";
		else {
			
			$stmt = $conn_db_ntu->prepare("SELECT * FROM ".$TABLES['staff_pref_part_time']." WHERE staff_id= ? and prefer= ?");
			$stmt->bindParam(1, $staffid);
			$stmt->bindParam(2, $areaPref);
			$stmt->execute();
			$existAreaPrefs = $stmt->fetchAll();
			
			if (sizeof ($existAreaPrefs) == 0)
			{
				$stmt = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['staff_pref_part_time']." (staff_id, prefer, choice) VALUES (?, ?, ?)");
				$stmt->bindParam(1, $staffid);
				$stmt->bindParam(2, $areaPref);
				$stmt->bindParam(3, $j);
				$stmt->execute();
				
				$j++;
				
		}
		$i++;
	}
	
	$conn_db_ntu = null;
?>

<?php
	  if (isset ($_REQUEST['validate'])) {
           header("location:staffpref_parttime.php?validate=1");
      }
      else {
		header("location:staffpref_parttime.php?save=1");
       }
	  exit;
?>
