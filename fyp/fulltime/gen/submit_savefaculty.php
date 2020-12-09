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
    $total = count($_POST['staff']);
    $deleteCount = 0;

    if (isset($_POST['staff']))	{
        foreach ($_POST['staff'] as $row_project) {
            // Set variables
            $staff_name		= $row_project['staff_name'];
            $staff_id		= $row_project['staff_id'];
            $project_preference		= $row_project['project_preference'];
            $area_preference		= $row_project['area_preference'];


            try {
                $query_rsDeleteProject = "DELETE FROM ".$TABLES['staff']." WHERE id = ? AND name = ?";

                $stmt_1 = $conn_db_ntu->prepare($query_rsDeleteProject);
                $stmt_1->bindParam(1, $staff_id); //Search staff id
                $stmt_1->bindParam(2, $staff_name); //Search staff name
                $stmt_1->execute();

                $query_rsDeleteProject = "DELETE FROM ".$TABLES['staff_pref'] ." WHERE staff_id = ? ";

                $stmt_1 = $conn_db_ntu->prepare($query_rsDeleteProject);
                $stmt_1->bindParam(1, $staff_id); //Search staff id
                $stmt_1->execute();

                $deleteCount++;
            }
            catch (PDOException $e) {
                die($e->getMessage());
            }
        }
    }
    else {
        $_SESSION['deleteStaff'] = 'error';
    }
    $conn_db_ntu = null;
} // end else statement

// If there is exception
if (isset($_SESSION['deleteStaff']) && $_SESSION['deleteStaff'] == 'error') {
    header("location:project.php");
}
else if ($deleteCount == $total) {
    $_SESSION['deleteStaff'] = 'success';
}
else {
    $_SESSION['deleteStaff'] = 'error';
}
header("location:faculty.php");
exit;

?>
