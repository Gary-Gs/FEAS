<?php require_once('../../../Connections/db_ntu.php');
	  require_once('../../../CSRFProtection.php');
	  require_once('../../../Utility.php');?>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_SERVER['QUERY_STRING'])) {
    header('Location: staffpref_setting.php');
    exit;
}
else {
	/* to be used for server

	if($_SERVER['HTTP_REFERER'] != null){
	$urlString = explode('/', $_SERVER['HTTP_REFERER']);
	$foldername = $urlString[0];
		if(strcmp($foldername, 'https:') == 0){
			if(strcmp($_SERVER['HTTP_REFERER'], 'https://155.69.100.32/fyp/fulltime/alloc/staffpref_setting.php?') == 0)
			{
				//no error
			}
			elseif(strcmp($_SERVER['HTTP_REFERER'], 'https://155.69.100.32/fyp/fulltime/alloc/staffpref_setting.php') == 0){
				//no error
			}
			else{
				throw new Exception($_SERVER['Invalid referer']);
			}
		}
		elseif(strcmp($foldername,'http:') == 0){
			if(strcmp($_SERVER['HTTP_REFERER'], 'http://155.69.100.32/fyp/fulltime/alloc/staffpref_setting.php?') == 0)
			{
				//no error
			}
			elseif(strcmp($_SERVER['HTTP_REFERER'], 'http://155.69.100.32/fyp/fulltime/alloc/staffpref_setting.php') == 0){
				//no error
			}
			else{
				throw new Exception($_SERVER['Invalid referer']);
			}
		}
	}
*/
	/* this is for testing in localhost
	try {
		if($_SERVER['HTTP_REFERER'] != null && strcmp($_SERVER['HTTP_REFERER'], 'http://localhost/fyp/fulltime/alloc/staffpref_setting.php') != 0){
			throw new Exception("Invalid referer");
		}
	}
	catch (Exceptopn $e) {
			echo $e->getMessage();
	}
	*/

	$localHostDomain = 'http://localhost';
	$ServerDomainHTTP = 'http://155.69.100.32';
	$ServerDomainHTTPS = 'https://155.69.100.32';
	$ServerDomain = 'https://fypExam.scse.ntu.edu.sg';
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
	      die ("Invalid Referer.");
	  }
	}

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
}
?>

<?php
	if (isset ($_REQUEST['validate'])) {
		header("location:staffpref_setting.php");
	}
	else if (isset ($error_code)) {

		header("location:staffpref_setting.php?error=$error_code");

	}
	else {
		header("location:staffpref_setting.php");
	}
	exit;
	?>
