<?php require_once('../../Connections/db_ntu.php');
	 require_once('../../CSRFProtection.php');
	 require_once('../../Utility.php');?>

<?php

$error = false;

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

	/* Check if staff pref is open for selection */
	try
	{
		date_default_timezone_set('Asia/Singapore');
		$currentDateTime = date('Y-m-d H:i:s');

		// initialize months array
		$sem1Array = array("Aug","Sep","Oct","Nov","Dec");
		$sem2Array = array("Jan","Feb","Mar", "Apr", "May", "Jun", "Jul");

		// Get current year and semester
		if (in_array(date("M"), $sem1Array)) {
		  $examSemValue = "1";
			$examYearValue = date("y") . (date("y") + 1);
		}
		else {
		  $examSemValue = "2";
			$examYearValue = (date("y") - 1) . date("y");
		}

		if (isset($_POST['SubmitFeedback'])) {
	    if (isset($_REQUEST['staffid']) && isset($_POST['rating']) && isset($_POST['type']) && isset($_POST['comment'])) {

				//$examYearValue = $_REQUEST['examYearValue'];
			  //$examSemValue = $_REQUEST['examSemValue'];
				$staffid = $_REQUEST['staffid'];
	      $rating = $_POST['rating'];
	      $type = $_POST['type'];
	      $comment = $_POST['comment'];

	      $stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['fea_feedback'] . " (feedback_datetime, exam_year, exam_sem, staff_id, rating, type, comment) VALUES (?, ?, ?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $currentDateTime);
				$stmt->bindParam(2, $examYearValue);
				$stmt->bindParam(3, $examSemValue);
	      $stmt->bindParam(4, $staffid);
	      $stmt->bindParam(5, $rating);
	      $stmt->bindParam(6, $type);
	      $stmt->bindParam(7, $comment);
	      $stmt->execute();
	    }
			else {
				$error = true;
			}
	  }
		else {
			$error = true;
		}
	}
	catch(Exception $e)
	{
		$error = true;
	}
	$conn_db_ntu = null;
}
?>

<?php

if ($error) {
		// show error
    header("location:staffpref_fulltime.php?validate=1");
} else {
    header("location:staffpref_fulltime.php");
    //header("location:examiner_setting.php?verified=1");
}
	?>
