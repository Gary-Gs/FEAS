<?php require_once('../../../Connections/db_ntu.php');
	  require_once('../../../CSRFProtection.php');
	  require_once('../../../Utility.php');?>

<?php
$localHostDomain = 'http://localhost';
$ServerDomainHTTP = 'http://155.69.100.32';
$ServerDomainHTTPS = 'https://155.69.100.32';
$ServerDomain = 'https://fypexam.scse.ntu.edu.sg';
if(isset($_SERVER['HTTP_REFERER'])) {
	try {
			// If referer is correct
			if ((strpos($_SERVER['HTTP_REFERER'], $localHostDomain) !== false) || (strpos($_SERVER['HTTP_REFERER'], $ServerDomainHTTP) !== false) || (strpos($_SERVER['HTTP_REFERER'], $ServerDomainHTTPS) !== false) || (strpos($_SERVER['HTTP_REFERER'], $ServerDomain) !== false)) {
					//echo "<script>console.log( 'Debug: " . "Correct Referer" . "' );</script>";
			}
			else {
					throw new Exception($_SERVER['Invalid Referer']);
					//echo "<script>console.log( 'Debug: " . "Incorrect Referer" . "' );</script>";
			}
	}
	catch (Exception $e) {
			header("HTTP/1.1 400 Bad Request");
			die ("Invalid Referer.");
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_SERVER['QUERY_STRING'])) {
		header("HTTP/1.1 400 Bad Request");
		exit("Bad Request");
}
else {

	$csrf = new CSRFProtection();

	$_REQUEST['validate'] =$csrf->cfmRequest();

	if(isset($_POST['save']))
	{
	  setcookie("submit_status","save");
  }

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
		//else if ((date('m', strtodate($start_dt)) <= 06 && date('m', strtotime($end_dt)) >= 07) || (date('m', strtotime($start_dt)) >= 07 && date('m', strtotime($end_dt)) <= 06)) {
		else if (($start_dt->format('m') <= 06 && $end_dt->format('m') >= 07) || ($start_dt->format('m') >= 07 && $end_dt->format('m') <= 06)) {
			$error_code = 4;
		}
		else if ($start_dt->format('Y-m-d') < date("Y-m-d") && $end_dt->format('Y-m-d') < date("Y-m-d")) {
			$error_code = 5;
		}
		//check if it's valid dates before updating the dates
		elseif(checkdate($tempStartDate[1], $tempStartDate[2], $tempStartDate[0]) && checkdate($tempEndDate[1], $tempEndDate[2], $tempEndDate[0])) {

			// initialize months array
			$sem1Array = array("Jul", "Aug","Sep","Oct","Nov","Dec");
			$sem2Array = array("Jan","Feb","Mar", "Apr", "May", "Jun");

			// Get current year to get latest open period
			$currentYrSem1 = substr($tempStartDate[0], -2) . (substr($tempStartDate[0], -2) + 1);
			$currentYrSem2 = (substr($tempStartDate[0], -2) -1) . (substr($tempStartDate[0], -2));//substr($tempStartDate[0], -2);

			if (in_array(date('M', mktime(0, 0, 0, $tempStartDate[1], 10)), $sem1Array) && in_array(date('M', mktime(0, 0, 0, $tempEndDate[1], 10)), $sem1Array)) {
					$currentYrSem = "Yr " . $currentYrSem1 . " Sem 1";
			}

			if (in_array(date('M', mktime(0, 0, 0, $tempStartDate[1], 10)), $sem2Array) && in_array(date('M', mktime(0, 0, 0, $tempEndDate[1], 10)), $sem2Array)) {
					$currentYrSem = "Yr " . $currentYrSem2 . " Sem 2";
			}

			// Get the Yr Sem period that is stored in DB
			$query_rsSettings = "SELECT * FROM ".$TABLES['allocation_settings_others']." WHERE type= 'FT'";
			$settings 	= $conn_db_ntu->query($query_rsSettings)->fetch();

			$originalYrSem1 = (date("y", strtotime($settings['pref_start']))) . (date("y", strtotime($settings['pref_start']))+1);
			$originalYrSem2 = (date("y", strtotime($settings['pref_start']))-1) . (date("y", strtotime($settings['pref_start'])));

			// Get the month of the original open period
			if (in_array(date("M", strtotime($settings['pref_start'])), $sem1Array) && in_array(date("M", strtotime($settings['pref_end'])), $sem1Array)) {
					$originalYrSem = "Yr " . $originalYrSem1 . " Sem 1";
			}
			if (in_array(date("M", strtotime($settings['pref_start'])), $sem2Array) && in_array(date("M", strtotime($settings['pref_end'])), $sem2Array)) {
					$originalYrSem = "Yr " . $originalYrSem2 . " Sem 2";
			}

			if ($originalYrSem != null && $currentYrSem != $originalYrSem && $currentYrSem > $originalYrSem) {

					// Retrieve records with the $originalYrSem already in DB
					$query_preferenceExist = "SELECT * FROM " . $TABLES['staff_pref_count'] .
																	" WHERE choose_date = '" . $originalYrSem . "'";

					$stmt_PreferenceExist = $conn_db_ntu->prepare($query_preferenceExist);
					$stmt_PreferenceExist->execute();
					$rsPreferenceExist = $stmt_PreferenceExist->fetchAll(PDO::FETCH_ASSOC);

					// If records does not exist
					if ($rsPreferenceExist == null || sizeof($rsPreferenceExist) == 0) {

						// Retrieve all interest area
						$query_rsAllArea = "SELECT * FROM " . $TABLES['interest_area'];

						$stmt_area = $conn_db_ntu->prepare($query_rsAllArea);
						$stmt_area->execute();
						$rsAllArea = $stmt_area->fetchAll(PDO::FETCH_ASSOC);

						// Foreach area, check if it exists in staff pref table where archive = 0. If no, insert as 0, else insert count
						foreach ($rsAllArea as $row_rsAllArea) {
								$query_rsPreference = "SELECT prefer, COUNT(DISTINCT staff_id) as total FROM " . $TABLES['staff_pref'] .
																				" WHERE archive = 0 AND prefer = '" . $row_rsAllArea['key'] . "' " .
																				" GROUP BY prefer";

								$stmt_Preference = $conn_db_ntu->prepare($query_rsPreference);
								$stmt_Preference->execute();
								$rsAreaPreference = $stmt_Preference->fetchAll();

								// Insert
								if ($rsAreaPreference != null || sizeof($rsAreaPreference) > 0 ) {
									$stmt_CountPreference = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['staff_pref_count']." (choose_date, prefer, count) VALUES (?, ?, ?)");

									$stmt_CountPreference->bindParam(1, $originalYrSem);
									$stmt_CountPreference->bindParam(2, $row_rsAllArea['key']);
									$stmt_CountPreference->bindParam(3, $rsAreaPreference[0]['total']);
									$stmt_CountPreference->execute();
								}
								else {
									$count = 0;
									$stmt_CountPreference = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['staff_pref_count']." (choose_date, prefer, count) VALUES (?, ?, ?)");

									$stmt_CountPreference->bindParam(1, $originalYrSem);
									$stmt_CountPreference->bindParam(2, $row_rsAllArea['key']);
									$stmt_CountPreference->bindParam(3, $count);
									$stmt_CountPreference->execute();
								}
						 }

				 		// Retrieve all interest area
				 		$query_rsAllProject = "SELECT project_id FROM " . $TABLES['fea_projects'] .
															 " WHERE examine_year = (SELECT examine_year FROM " . $TABLES['fea_projects'] . " GROUP BY examine_year ORDER BY examine_year Desc LIMIT 1, 1) " .
															 " AND examine_sem = (SELECT examine_sem FROM " . $TABLES['fea_projects'] . " GROUP BY examine_sem ORDER BY project_id Desc LIMIT 1, 1) ";

				 		$stmt_proj = $conn_db_ntu->prepare($query_rsAllProject);
				 		$stmt_proj->execute();
				 		$rsAllProj = $stmt_proj->fetchAll(PDO::FETCH_ASSOC);

				 		// Foreach area, check if it exists in staff pref table where archive = 0. If no, insert as 0, else insert count
				 		foreach ($rsAllProj as $row_rsAllProj) {
				 				$query_rsProjPreference = "SELECT prefer, COUNT(DISTINCT staff_id) as total FROM " . $TABLES['staff_pref'] .
				 																" WHERE archive = 0 AND prefer = '" . $row_rsAllProj['project_id'] . "' " .
				 																" GROUP BY prefer";

				 				$stmt_ProjPreference = $conn_db_ntu->prepare($query_rsProjPreference);
				 				$stmt_ProjPreference->execute();
				 				$rsProjPreference = $stmt_ProjPreference->fetch();

				 				// Insert
				 				if ($rsProjPreference != null) {
									$stmt_addProjPreference = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['staff_pref_count']." (choose_date, prefer, count) VALUES (?, ?, ?)");

			 						$stmt_addProjPreference->bindParam(1, $originalYrSem);
			 						$stmt_addProjPreference->bindParam(2, $row_rsAllProj['project_id']);
			 						$stmt_addProjPreference->bindParam(3, $rsProjPreference['total']);
			 						$stmt_addProjPreference->execute();
								}
								else {
									$count = 0;
									$stmt_addProjPreference = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['staff_pref_count']." (choose_date, prefer, count) VALUES (?, ?, ?)");

			 						$stmt_addProjPreference->bindParam(1, $originalYrSem);
			 						$stmt_addProjPreference->bindParam(2, $row_rsAllProj['project_id']);
			 						$stmt_addProjPreference->bindParam(3, $count);
			 						$stmt_addProjPreference->execute();
								}
							}
						 /* original
						 $query_rsPreference = "SELECT prefer, COUNT(DISTINCT staff_id) as total FROM " . $TABLES['staff_pref'] .
																		 " WHERE archive = 0 AND prefer LIKE '%SC%' " .
																		 " GROUP BY prefer";

						 $stmt_Preference = $conn_db_ntu->prepare($query_rsPreference);
						 $stmt_Preference->execute();
						 $rsPreference = $stmt_Preference->fetchAll(PDO::FETCH_ASSOC);

						 foreach ($rsPreference as $row_rsPreference) {
								$stmt_addPreference = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['staff_pref_count']." (choose_date, prefer, count) VALUES (?, ?, ?)");

		 						$stmt_addPreference->bindParam(1, $originalYrSem);
		 						$stmt_addPreference->bindParam(2, $row_rsPreference['prefer']);
		 						$stmt_addPreference->bindParam(3, $row_rsPreference['total']);
		 						$stmt_addPreference->execute();
						 } */
					}
					$stmt = $conn_db_ntu->prepare("UPDATE ". $TABLES['allocation_settings_others'] . " SET pref_start= ? WHERE type= 'FT'");
					$stmt->bindParam(1, $startDate);
					$stmt->execute();

					$stmt = $conn_db_ntu->prepare("UPDATE ". $TABLES['allocation_settings_others'] . " SET pref_end= ? WHERE type= 'FT'");
					$stmt->bindParam(1, $endDate);
					$stmt->execute();

					$stmt = $conn_db_ntu->prepare("UPDATE ". $TABLES['staff_pref'] . " SET archive = 1 WHERE  prefer LIKE 'SC%' AND choose_time < ?");
					$stmt->bindParam(1, $startDate);
					$stmt->execute();
			}
			else {

				$stmt = $conn_db_ntu->prepare("UPDATE ". $TABLES['allocation_settings_others'] . " SET pref_start= ? WHERE type= 'FT'");
				$stmt->bindParam(1, $startDate );
				$stmt->execute();

				$stmt = $conn_db_ntu->prepare("UPDATE ". $TABLES['allocation_settings_others'] . " SET pref_end= ? WHERE type= 'FT'");
				$stmt->bindParam(1, $endDate);
				$stmt->execute();
			}
	  }
	}
	$conn_db_ntu = null;
}
?>

<?php
	if (isset ($_REQUEST['validate'])) {
		//header("location:staffpref_setting.php");
	}
	else if (isset ($error_code)) {
		$_SESSION['error'] =  $error_code;
		header("location:staffpref_setting.php");
	}
	else {
		header("location:staffpref_setting.php");
	}
	exit;
	?>
