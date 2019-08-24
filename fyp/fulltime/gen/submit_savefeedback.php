<?php
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');
?>

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

    $_REQUEST['validate'] = $csrf->cfmRequest();

    // Retrieve $_POST values
    $total = count($_POST['feedback']);
    $deleteCount = 0;

		if (isset($_POST['feedback']))	{
			foreach ($_POST['feedback'] as $row_feedback) {
				// Set variables
				$feedback_datetime		= $row_feedback['feedbackDateTime'];
        $examYear		= $row_feedback['examYear'];
        $examSem		= $row_feedback['examSem'];
        $staffName		= $row_feedback['staffName'];
        $staffID		= $row_feedback['staffID'];
        $rating		= $row_feedback['rating'];
        $type	= $row_feedback['type'];
        $comment		= $row_feedback['comment'];

        try {
            $query_rsDeleteFeedback = "DELETE FROM ".$TABLES['fea_feedback']." WHERE feedback_datetime = ? AND exam_year = ? AND exam_sem = ? AND staff_id = ? AND rating = ? AND type = ? AND comment = ?";

            $stmt_1 = $conn_db_ntu->prepare($query_rsDeleteFeedback);
            $stmt_1->bindParam(1, $feedback_datetime); //Search feedback date time
            $stmt_1->bindParam(2, $examYear); //Search project year
            $stmt_1->bindParam(3, $examSem); //Search project sem
            $stmt_1->bindParam(4, $staffID); //Search staff id
            $stmt_1->bindParam(5, $rating); //Search feedback rating
            $stmt_1->bindParam(6, $type); //Search feedback type
            $stmt_1->bindParam(7, $comment); //Search feedback comment
            $stmt_1->execute();

            $deleteCount++;
        }
        catch (PDOException $e) {
            die($e->getMessage());
        }
    	}
		}
		else {
			$_SESSION['deleteFeedback'] = 'error';
		}
    $conn_db_ntu = null;
} // end else statement

// If there is exception
if (isset($_SESSION['deleteFeedback']) && $_SESSION['deleteFeedback'] == 'error') {
  header("location:feedback.php");
}
else if ($deleteCount == $total) {
  $_SESSION['deleteFeedback'] = 'success';
}
else {
	$_SESSION['deleteFeedback'] = 'error';
}
header("location:feedback.php");
exit;

?>
