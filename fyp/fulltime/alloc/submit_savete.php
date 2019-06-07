<?php require_once('../../../Connections/db_ntu.php');
	require_once('../../../CSRFProtection.php');
	require_once('../../../Utility.php');?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['QUERY_STRING'])) {
		header('Location: timeslot_exception.php');
		exit;
}
else {
	$csrf = new CSRFProtection();

	$_REQUEST['validate'] = $csrf->cfmRequest();

	/* if(isset($_POST['save']))
	  setcookie("submit_status","save");

  if(isset($_POST['btn_clear']))
		 if($_POST['clear'] == 1)
		   setcookie("submit_status","clear");

  if(isset($_POST['call'])){
    setcookie("submit_status","csrf");
    setcookie("submit_status","call");
  }
*/

	//Set Values (Timeslot Exceptions)
	try
	{
		$conn_db_ntu->exec("DELETE FROM ".$TABLES['fea_settings_availability']);
	}
	catch (PDOException $e)
	{
		die($e->getMessage());
	}

	$i=1;
	$j=1;
	while(isset($_REQUEST['staff_'.$i]) ||
		  isset($_REQUEST['day_'.$i]) ||
		  isset($_REQUEST['timestart_'.$i]) ||
		  isset($_REQUEST['timeend_'.$i]) && (!isset($_POST['clear']))) {

		echo "<br/>";

		$staff		= $_REQUEST['staff_'.$i];
		$day 		= $_REQUEST['day_'.$i];
		$timestart	= $_REQUEST['timestart_'.$i];
		$timeend 	= $_REQUEST['timeend_'.$i];
		if($timestart >= $timeend)
		{

		}
		else if(empty($staff) || empty($day) || empty($timestart) || empty($timeend)) {
			//echo "Has Empty Fields";
		}
		else {


				$stmt = $conn_db_ntu->prepare("SELECT * FROM  ". $TABLES['fea_settings_availability'] . " WHERE staff_id = ? and day = ? and time_start = ? and time_end = ?");
					$stmt->bindParam(1, $staff);
					$stmt->bindParam(2, $day);
					$stmt->bindParam(3, $timestart);
					$stmt->bindParam(4, $timeend);
					$stmt->execute();
					$existRecords = $stmt->fetchAll();
				if (sizeof($existRecords) <= 0)	//Has no duplicates
				{

					$stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['fea_settings_availability'] . " (staff_id, day, time_start, time_end) VALUES (?, ?, ?, ?)");
					$stmt->bindParam(1, $staff);
					$stmt->bindParam(2, $day);
					$stmt->bindParam(3, $timestart);
					$stmt->bindParam(4, $timeend);
					$stmt->execute();
					$j++;

				}

		}
		$i++;
		$_SESSION['savete_msg'] = 'save';
	}
	$conn_db_ntu = null;
}
?>

<?php
	if (isset ($_REQUEST['validate'])) {
				header("location:timeslot_exception.php?validate=1");
				exit;
	}
	else if (isset($_POST['clear'])) {
				$_SESSION['clear'] = 'clear';
				unset($_SESSION['savete_msg']);
  			header("location:timeslot_exception.php");
  			exit;
  }
	else {
		header("location:timeslot_exception.php");
		exit;
	}

	exit;
?>
