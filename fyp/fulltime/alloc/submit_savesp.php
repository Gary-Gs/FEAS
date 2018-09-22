<?php require_once('../../../Connections/db_ntu.php');
	  require_once('../../../CSRFProtection.php');
	  require_once('../../../Utility.php');?>

<?php
	
	$csrf = new CSRFProtection();
	
	$_REQUEST['validate'] =$csrf->cfmRequest();
	
	
	
	if(isset($_REQUEST['start_date']) && isset($_REQUEST['end_date'])) {
	
		$startDate = $_REQUEST['start_date'];
		$endDate = $_REQUEST['end_date'];
		$tempStartDate = explode('-', $startDate);
		$tempEndDate = explode('-', $endDate);

		if(!checkdate($tempStartDate[1], $tempStartDate[2], $tempStartDate[0])){
			$error_code = 1;
		}

		elseif(!checkdate($tempEndDate[1], $tempEndDate[2], $tempEndDate[0])){
			$error_code = 2;
		}

		else{
			$start_dt = new DateTime($startDate);
			$end_dt = new DateTime($endDate);
		}
		
		if ($start_dt > $end_dt ) {
			$error_code = 3;
			
		}
		//check if it's valid dates before updating the dates
		elseif(checkdate($tempStartDate[1], $tempStartDate[2], $tempStartDate[0]) && checkdate($tempEndDate[1], $tempEndDate[2], $tempEndDate[0])) {
			
			$stmt = $conn_db_ntu->prepare("UPDATE ". $TABLES['allocation_settings_others'] . " SET pref_start= ? WHERE type= 'FT'");
			$stmt->bindParam(1, $startDate );
			$stmt->execute();				
			
			$stmt = $conn_db_ntu->prepare("UPDATE ". $TABLES['allocation_settings_others'] . " SET pref_end= ? WHERE type= 'FT'");
			$stmt->bindParam(1, $endDate);
			$stmt->execute();
			
			$stmt = $conn_db_ntu->prepare("UPDATE ". $TABLES['staff_pref'] . " SET archive = 1 WHERE  prefer LIKE 'SCE%' AND choose_time < ?");
			$stmt->bindParam(1, $startDate);
			$stmt->execute();

	 }
	}
	
	$conn_db_ntu = null;
?>
	
<?php
	if (isset ($_REQUEST['validate'])) {
		header("location:staffpref_setting.php?validate=1");
	}
	else if (isset ($error_code)) {
		
		header("location:staffpref_setting.php?error=$error_code");
		
	}
	else {
		header("location:staffpref_setting.php?save=1");
	}
	exit;
	?>