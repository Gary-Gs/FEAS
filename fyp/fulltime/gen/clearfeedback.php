<?php
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');

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

$csrf = new CSRFProtection();

$_REQUEST['csrf'] 	= $csrf->cfmRequest();

if (isset ($_REQUEST['csrf'])) {
    header("location:staffpref_fulltime.php?csrf=1");
exit;
}

$stmt1 = $conn_db_ntu->prepare("DELETE FROM " . $TABLES['fea_feedback']);
$stmt1->execute();

$conn_db_ntu = null;

$_SESSION['clearAllFeedback'] = 'clearAll';
header("location:feedback.php");
exit;

?>
