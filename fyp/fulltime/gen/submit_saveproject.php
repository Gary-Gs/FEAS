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
    $total = count($_POST['project']);
    $deleteCount = 0;

    if (isset($_POST['project']))	{
        foreach ($_POST['project'] as $row_project) {
            // Set variables
            $project_id		= $row_project['project_id'];
            $year		= $row_project['year'];
            $sem		= $row_project['sem'];
            $title		= $row_project['title'];
            $Supervisor		= $row_project['Supervisor'];
            $examine_year		= $row_project['examine_year'];
            $examine_sem	= $row_project['examine_sem'];


            try {
                $query_rsDeleteProject = "DELETE FROM ".$TABLES['fea_projects']." WHERE project_id = ? AND examine_year = ? AND examine_sem = ?";

                $stmt_1 = $conn_db_ntu->prepare($query_rsDeleteProject);
                $stmt_1->bindParam(1, $project_id); //Search feedback date time
                $stmt_1->bindParam(2, $examine_year); //Search feedback type
                $stmt_1->bindParam(3, $examine_sem); //Search feedback type
                $stmt_1->execute();

                $query_rsDeleteProject = "DELETE FROM ".
                    $TABLES['fyp'] ." WHERE project_id = ? AND sem = ? AND title = ? AND Supervisor = ?";

                $stmt_1 = $conn_db_ntu->prepare($query_rsDeleteProject);
                $stmt_1->bindParam(1, $project_id); //Search feedback date time
                $stmt_1->bindParam(2, $sem); //Search project sem
                $stmt_1->bindParam(3, $title); //Search staff id
                $stmt_1->bindParam(4, $Supervisor); //Search feedback rating
                $stmt_1->execute();

                $query_rsDeleteProject = "DELETE FROM ".
                    $TABLES['fyp_assign'] ." WHERE project_id = ? AND year = ? AND sem = ?";

                $stmt_1 = $conn_db_ntu->prepare($query_rsDeleteProject);
                $stmt_1->bindParam(1, $project_id); //Search feedback date time
                $stmt_1->bindParam(2, $year); //Search project year
                $stmt_1->bindParam(3, $sem); //Search project sem
                $stmt_1->execute();

                $deleteCount++;
            }
            catch (PDOException $e) {
                die($e->getMessage());
            }
        }
    }
    else {
        $_SESSION['deleteProject'] = 'error';
    }
    $conn_db_ntu = null;
} // end else statement

// If there is exception
if (isset($_SESSION['deleteProject']) && $_SESSION['deleteProject'] == 'error') {
    header("location:project.php");
}
else if ($deleteCount == $total) {
    $_SESSION['deleteProject'] = 'success';
}
else {
    $_SESSION['deleteProject'] = 'error';
}
header("location:project.php");
exit;

?>
