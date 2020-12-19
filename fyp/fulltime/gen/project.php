<?php
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');
require_once('../../../restriction.php');


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


global $TABLES;


//initialize once
if(!isset($_SESSION['project_pagination'])){
    $_SESSION["project_pagination"] = '';
}

if(!isset($_SESSION['pre_filter_ProjectYear'])){
    $_SESSION["pre_filter_ProjectYear"] = '';
}

if(!isset($_SESSION['pre_filter_ProjectSem'])){
    $_SESSION["pre_filter_ProjectSem"] = '';
}

if(!isset($_SESSION['pre_filter_Supervisor'])){
    $_SESSION["pre_filter_Supervisor"] = '';
}

/*Wee Teck Zong [12.16.2020]
- Set session for filter exam year to be empty if it's not selected
*/
if(!isset($_SESSION['pre_filter_ExamYear'])){
    $_SESSION["pre_filter_ExamYear"] = '';
}

/*Wee Teck Zong [12.16.2020]
- Set session for filter exam sem to be empty if it's not selected
*/
if(!isset($_SESSION['pre_filter_ExamSem'])){
    $_SESSION["pre_filter_ExamSem"] = '';
}



if(!isset($_SESSION['pre_search'])){
    $_SESSION["pre_search"] = '';
}

//reset pagination when project year filter changes
if(isset($_POST['filter_ProjectYear']) && !empty($_POST['filter_ProjectYear'])){
    if($_SESSION["pre_filter_ProjectYear"] != $_POST["filter_ProjectYear"]){
        $_SESSION["project_pagination"] = 0;
    }
}

$filter_ProjectYear 	= "%". (isset($_POST['filter_ProjectYear']) && !empty($_POST['filter_ProjectYear']) ?
        preg_replace('/[^0-9]/','',$_POST['filter_ProjectYear']) : '') ."%";
$pre_filter_ProjectYear = explode("%",$filter_ProjectYear);
$_SESSION["pre_filter_ProjectYear"] = $pre_filter_ProjectYear[1];

//reset pagination when project sem filter changes
if(isset($_POST['filter_ProjectSem']) && !empty($_POST['filter_ProjectSem'])) {
    if ($_SESSION["pre_filter_ProjectSem"] != $_POST["filter_ProjectSem"]) {
        $_SESSION["project_pagination"] = 0;
    }
}

$filter_ProjectSem 		= "%". (isset($_POST['filter_ProjectSem']) && !empty($_POST['filter_ProjectSem']) ?
        preg_replace('/[^0-9]/','',$_POST['filter_ProjectSem']) : '') ."%";
$pre_filter_ProjectSem = explode("%",$filter_ProjectSem);
$_SESSION["pre_filter_ProjectSem"] = $pre_filter_ProjectSem[1];


/*Wee Teck Zong [12.16.2020]
- reset pagination when exam year filter changes
*/
if(isset($_POST['filter_ExamYear']) && !empty($_POST['filter_ExamYear'])){
    if($_SESSION["pre_filter_ExamYear"] != $_POST["filter_ExamYear"]){
        $_SESSION["project_pagination"] = 0;
    }
}
$filter_ExamYear 	= "%". (isset($_POST['filter_ExamYear']) && !empty($_POST['filter_ExamYear']) ?
        preg_replace('/[^0-9]/','',$_POST['filter_ExamYear']) : '') ."%";
$pre_filter_ExamYear = explode("%",$filter_ExamYear);
$_SESSION["pre_filter_ExamYear"] = $pre_filter_ExamYear[1];

/*Wee Teck Zong [12.16.2020]
- reset pagination when exam sem filter changes
*/
if(isset($_POST['filter_ExamSem']) && !empty($_POST['filter_ExamSem'])) {
    if ($_SESSION["pre_filter_ExamSem"] != $_POST["filter_ExamSem"]) {
        $_SESSION["project_pagination"] = 0;
    }
}
$filter_ExamSem 		= "%". (isset($_POST['filter_ExamSem']) && !empty($_POST['filter_ExamSem']) ?
        preg_replace('/[^0-9]/','',$_POST['filter_ExamSem']) : '') ."%";
$pre_filter_ExamSem = explode("%",$filter_ExamSem);
$_SESSION["pre_filter_ExamSem"] = $pre_filter_ExamSem[1];



//reset pagination when supervisor sem filter changes
if(isset($_POST['filter_Supervisor']) && !empty($_POST['filter_Supervisor'])) {
    if ($_SESSION["pre_filter_Supervisor"] != $_POST["filter_Supervisor"]) {
        $_SESSION["project_pagination"] = 0;
    }
}

$filter_Supervisor  	= "%". (isset($_POST['filter_Supervisor']) && !empty($_POST['filter_Supervisor']) ?
        preg_replace('/[^a-zA-Z._\s\-]/','',$_POST['filter_Supervisor']) : ''); //."%";
$pre_filter_Supervisor = explode("%",$filter_Supervisor);
$_SESSION["pre_filter_Supervisor"] = $pre_filter_Supervisor[1];

//reset pagination when search button is clicked
if(isset($_POST['click'])) {
    if($_SESSION["pre_search"] != $_POST["search"]){
        $_SESSION["project_pagination"] = 0;
    }
}

$cleanedSearchID = (isset($_POST['search']) && !empty($_POST['search'])) ?
    preg_replace(['/\s+/', '[^a-zA-Z0-9\s\-()]'], ['', ''], $_POST['search']) : '';
$filter_SearchID 			= "%". $cleanedSearchID . "%";

$cleanedSearchTitle = (isset($_POST['search']) && !empty($_POST['search'])) ?
    preg_replace('[^a-zA-Z0-9\s\-()]', '', $_POST['search']) : '';
$filter_SearchTitle 		= "%". $cleanedSearchTitle . "%";
$_SESSION["pre_search"] = explode("%",$filter_SearchTitle)[1];


$maxRow_Project = 20;

//next page
if(isset($_POST["nextpage"])) {
    $_SESSION["project_pagination"]+=1;
}

//previous page
if(isset($_POST["previouspage"])) {
    $_SESSION["project_pagination"]-=1;
}
//store page number
$pageNum_Project = (isset($_POST['filter_ProjectYear']) && !empty($_POST['filter_ProjectYear'])) ?
    $_SESSION["project_pagination"]:  $_SESSION["project_pagination"];


//ensure valid page number
if($pageNum_Project == ''){
    $pageNum_Project = 0;
    $_SESSION["project_pagination"] = 0;
}

$startRow_Project = $pageNum_Project * $maxRow_Project; //first page start row = 0 , 2nd page start row = 20

$query_rsStaff				= "SELECT * FROM " . $TABLES["staff"];

/* Wee Teck Zong [12.16.2020]
- New query to retrieve UNIQUE Exam_Years from database
*/
$query_uniqueExamYear				= "SELECT * FROM " . $TABLES["fea_projects"] . " GROUP BY examine_year ORDER BY examine_year";

/* Wee Teck Zong [12.16.2020]
- New query to retrieve UNIQUE years from database
*/
$query_uniqueYear				= "SELECT * FROM " . $TABLES["fyp_assign"] . " GROUP BY year ORDER BY year";

/* Wee Teck Zong [12.16.2020]
- Comment away due to adding new filter for exam sem and year. New filter query below.
$query_rsProject 			= "SELECT * FROM " .
    $TABLES['fea_projects'] . " as p1 LEFT JOIN " .
    $TABLES['fyp_assign'] 	. " as p2 ON p1.project_id 	= p2.project_id LEFT JOIN "	.
    $TABLES['fyp']			. " as p3 ON p2.project_id 	= p3.project_id LEFT JOIN "	.
    $TABLES['staff']		. " as p4 ON p2.staff_id 	= p4.id "					.
    "WHERE p2.complete = 0 AND (p2.project_id LIKE ? OR p3.title LIKE ?) AND (p2.year LIKE ? AND p2.sem LIKE ? AND p2.staff_id LIKE ?) ORDER BY p2.project_id DESC";
*/

/* Wee Teck Zong [12.16.2020]
- New query to add filter for exam year and exam sem
*/
$query_rsProject 			= "SELECT * FROM " .
    $TABLES['fea_projects'] . " as p1 LEFT JOIN " .
    $TABLES['fyp_assign'] 	. " as p2 ON p1.project_id 	= p2.project_id LEFT JOIN "	.
    $TABLES['fyp']			. " as p3 ON p2.project_id 	= p3.project_id LEFT JOIN "	.
    $TABLES['staff']		. " as p4 ON p2.staff_id 	= p4.id "					.
    "WHERE p2.complete = 0 AND (p2.project_id LIKE ? OR p3.title LIKE ?) AND (p2.year LIKE ? AND p2.sem LIKE ? AND p1.examine_year LIKE ? AND p1.examine_sem LIKE ? AND p2.staff_id LIKE ?) ORDER BY p2.project_id DESC";

    /* Wee Teck Zong [12.16.2020]
    - GET Unique year for filter dropdownlist
    */
    try
    {
        $stmt_11 			= $conn_db_ntu->prepare($query_uniqueYear);
        $stmt_11->execute();
        $DBData_rsUniqueYear 	= $stmt_11->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (PDOException $e)
    {
        die($e->getMessage());
    }

    /* Wee Teck Zong [12.16.2020]
    - GET Unique exam year for filter dropdownlist
    */
    try
    {

        $stmt_10 			= $conn_db_ntu->prepare($query_uniqueExamYear);
        $stmt_10->execute();
        $DBData_rsUniqueExamYear 	= $stmt_10->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (PDOException $e)
    {
        die($e->getMessage());
    }


// GET ALL STAFF FOR FILTER DROP DOWN CONTROL
try
{

    $stmt_0 			= $conn_db_ntu->prepare($query_rsStaff);
    $stmt_0->execute();
    $DBData_rsStaff 	= $stmt_0->fetchAll(PDO::FETCH_ASSOC);
    $AL_Staff			= array();
    foreach ($DBData_rsStaff as $key => $value) {
        $AL_Staff[$value["id"]] = $value["name"];
    }
    asort($AL_Staff);

    // GET Project data
    $stmt 				= $conn_db_ntu->prepare($query_rsProject);
    $stmt->bindParam(1, $filter_SearchID);				// Search project id
    $stmt->bindParam(2, $filter_SearchTitle);				// Search project title
    $stmt->bindParam(3, $filter_ProjectYear);			// Search project year
    $stmt->bindParam(4, $filter_ProjectSem);			// Search project sem
    /* Wee Teck Zong [12.16.2020]
    - Add bindParam 5 & 6 to filter exam year and exam sem
    */
    $stmt->bindParam(5, $filter_ExamYear);			// Search Exam Year
    $stmt->bindParam(6, $filter_ExamSem);			// Search Exam Sem
    $stmt->bindParam(7, $filter_Supervisor);			// Search supervisor
    $stmt->execute();
    $DBData_rsProject   = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $Total_RowCount 	= count($DBData_rsProject);

    //Check if there is any Supervisor who is not in the Facult table
    $new_Staff = array();
    foreach ($DBData_rsProject as $element) {
        $new_Staff_row = array();
        if(!array_key_exists($element['staff_id'], $AL_Staff)){
            $new_Staff_row["staff_id"] = $element['staff_id'];
            $new_Staff_row["Supervisor"] = $element['Supervisor'];
            array_push($new_Staff, $new_Staff_row);
        }
    }

    $new_Staff = array_map("unserialize", array_unique(array_map("serialize", $new_Staff)));
    //Request the serve to add the Supervisor into the Staff List
    foreach ($new_Staff as $element) {
        echo '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
              <script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>';
        echo '<script type="text/javascript">';
        echo 'if(confirm("' . $element['Supervisor'] . ' is not in the Faculty List. Do you want to add this supervisor into the list?")){';
        echo '
                var data = {name:"' . $element['Supervisor'] . '", id:"' . $element['staff_id'] . '"};
                $.ajax({
                    type: "POST",
                    url: "submit_add_supervisor.php",
                    data: data,
                    success: function() {
                        alert("Supervisor successfully added!");
                    }
                });';
        echo '}';
        echo 'location.reload();';
        echo '</script>';
        
    }

}

catch (PDOException $e)
{
    die($e->getMessage());
}

$total_pages = ceil($Total_RowCount / $maxRow_Project) - 1;


//limit record to 20 per page
//$query_limit_rsStaff = sprintf("%s LIMIT %d,%d", $query_rsStaff, $startRow_Project, $maxRow_Project);
$query_limit_rsProject = sprintf("%s LIMIT %d,%d", $query_rsProject, $startRow_Project, $maxRow_Project);

try {
    // GET ALL STAFF FOR FILTER DROP DOWN CONTROL
    $stmt_0 = $conn_db_ntu->prepare($query_rsStaff);
    $stmt_0->execute();
    $DBData_rsStaff = $stmt_0->fetchAll(PDO::FETCH_ASSOC);
    $AL_Staff = array();
    foreach ($DBData_rsStaff as $key => $value) {
        $AL_Staff[$value["id"]] = $value["name"];
    }
    asort($AL_Staff);

    // GET Project data
    $stmt = $conn_db_ntu->prepare($query_limit_rsProject);
    $stmt->bindParam(1, $filter_SearchID);                // Search project id
    $stmt->bindParam(2, $filter_SearchTitle);                // Search project title
    $stmt->bindParam(3, $filter_ProjectYear);            // Search project year
    $stmt->bindParam(4, $filter_ProjectSem);            // Search project sem
    /* Wee Teck Zong [12.16.2020]
    - Add bindParam 5 & 6 to filter exam year and exam sem
    */
    $stmt->bindParam(5, $filter_ExamYear);			// Search Exam Year
    $stmt->bindParam(6, $filter_ExamSem);			// Search Exam Sem
    $stmt->bindParam(7, $filter_Supervisor);            // Search supervisor*/
    $stmt->execute();
    $DBData_rsProject = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $Total_RowCount = count($DBData_rsProject);

} catch (PDOException $e) {
    die($e->getMessage());
}

$currentPage = $_SERVER ["PHP_SELF"];

$queryString_rsStaff = "";
$queryString_rsStaff = sprintf("&totalRows=%d%s", $Total_RowCount, $queryString_rsStaff);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">


<head>
    <title>Full Time Project List</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        @media only screen and (max-width: 800px) {
            .floatWrapper {float:none!important;}
            .float-panel {position:static!important;}
            .main-content {padding:20px;margin-right:0px;}
        }

        #Table_Filter_ProjectList td{
            display:block;
            width:auto;
        }

        @media only screen and (min-width: 70em) {
            #Table_Filter_ProjectList td{
                display:table-cell;
                margin-bottom:0px;
            }
        }
    </style>

</head>

<body>
<?php require_once('../../../php_css/headerwnav.php'); ?>


<div id="loadingdiv" class="loadingdiv">
    <img id="loadinggif" src="../../../images/loading.gif"/>
    <p>Uploading projects...</p>
</div>

<div style="margin-left: -15px;">
    <div class="container-fluid">
        <?php require_once('../../nav.php'); ?>

        <!-- Page Content Holder -->
        <div class="container-fluid">

            <!-- for going back to top -->
            <div id="backtop"></div>
            <h3>Full Time Project List</h3>
            <?php
            if (isset($_SESSION['deleteProject']) && $_SESSION['deleteProject'] == 'success') {
                echo "<p class='success'> All selected projects are deleted.</p>";
                unset($_SESSION['deleteProject']);
            }
            if (isset($_SESSION['deleteProject']) && $_SESSION['deleteProject'] == 'error') {
                echo "<p class='warn'> Some projects are not deleted.</p>";
                unset($_SESSION['deleteProject']);
            }
            ?>
            <?php
            if (isset ($_REQUEST['csrf']) ||isset ($_REQUEST['validate'])) {
                echo "<p class='warn'> CSRF validation failed.</p>";
            }

            else {
                if (isset($_SESSION['import_project_error'])) {
                    $error_code = $_SESSION['import_project_error'];
                    switch ($error_code) {
                        case 1:
                            echo "<p class='warn'> Uploaded file has no file name!</p>";
                            break;
                        case 2:
                            echo "<p class='warn'> Uploaded file has an invalid format type. Only excel files (.xlsx .xls .csv) are allowed!</p>";
                            break;
                        case 3:
                            echo "<p class='warn'> Uploaded file is open. Close it and upload again!</p>";
                            break;
                        case 4:
                            echo "<p class='error'> Cannot load excel file. Please contact system admin!</p>";
                            break;
                        case 5:
                            echo "<p class='error'> Incorrect Data Format! Please upload the correct excel!</p>";
                            break;
                    }
                    unset($_SESSION['import_project_error']);
                }
            }

            if (isset ($_SESSION['import_project'])){
                echo "<p class='success'> Project List uploaded successfully.</p>";
                unset($_SESSION['import_project']);
            }
            ?>

            <?php require_once('../../../upload_head.php'); ?>


            <form id="FORM_FileToUpload_ProjectList" method="post" enctype="multipart/form-data">
                <table style="text-align: left; width: 100%;">
                    <col width="20%">
                    <col width="20%">
                    <col width="20%">
                    <col width="20%">
                    <col width="20%">
                    <tr>
                        <td colspan="4">
                            Please select the <b><u>Project List</u></b>:
                        </td>
                        <td style="text-align: right;">
                            <input type="submit" value="Import" name="submit" class="btn btn-xs btn-success" >
                        </td>
                    </tr>

                    <tr>
                        <td colspan="5">
                            <input type="file" id="FileToUpload_ProjectList" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" name="file" >
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <div id="progressbardiv" class="progress" style="display: none;">
                                <div id="progressbar" class="progress-bar progress-bar-success" role="progressbar" style="width:0%; color:black; ">
                                    <span>0%</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5"><div id="status"></div></td>
                    </tr>
                </table>
                <?php $csrf->echoInputField();?>
            </form>
            <br/>
            <form id="filterParams" name="searchbox" action="project.php" method="post">
                <table id="Table_Filter_ProjectList" width="100%" >
                    <colgroup>
                        <col width="20%" >
                        <col width="20%" >
                        <col width="20%" >
                        <col width="20%" >
                        <col width="20%" >
                    </colgroup>
                    <tr>
                        <td >
                            <b> Year</b>
                        </td>
                        <td colspan="3">
                            <select id="filter_ProjectYear" name="filter_ProjectYear" onchange="this.form.submit()">
                                <option value="">SELECT</option>

                                <?php
                                /*Wee Teck Zong [12.16.2020]
                                - Added new filter through pulling unique year from database
                                 */
                                foreach ($DBData_rsUniqueYear as $row_DBData_rsUniqueYear)
                                {
                                  if(isset($_POST["filter_ProjectYear"]) && $_POST["filter_ProjectYear"] == $row_DBData_rsUniqueYear["year"]){
                                      echo "<option selected value='".$row_DBData_rsUniqueYear["year"]."'>".$row_DBData_rsUniqueYear["year"]."</option>";
                                  }else{
                                      echo "<option value='".$row_DBData_rsUniqueYear["year"]."'>".$row_DBData_rsUniqueYear["year"]."</option>";
                                  }

                                }
                                /*Wee Teck Zong [12.16.2020]
                                - Remove hardcoded filter dropdownlist for year added new filter through pulling unique year from database
                                 */

                               /*
                                $CurrentYear = sprintf("%02d", substr(date("Y"), -2));
                                $LastestYear = sprintf("%02d", substr(date("Y"), -2));
                                $EarlistYear = $CurrentYear - 10;

                                // Loops over each int[year] from current year, back to the $earliest_year [1950]
                                foreach ( range( $LastestYear, $EarlistYear ) as $i ) {
                                    $i = sprintf("%02d", substr($i, -2)) . (sprintf("%02d", (substr($i, -2)+1)));

                                    if(isset($_POST["filter_ProjectYear"]) && $_POST["filter_ProjectYear"] == $i){
                                        echo "<option selected value='".$i."'>".$i."</option>";
                                    }else{
                                        echo "<option value='".$i."'>".$i."</option>";
                                    }
                                }*/
                                ?>
                            </select>
                        </td>
                        <td style="float: right;">
                            <?php
                            if( $Total_RowCount > 0){
                                echo $Total_RowCount . " records";
                            }else{
                                echo $Total_RowCount . " record";
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td >
                            <b> Sem</b>
                        </td>
                        <td>
                            <select id="filter_ProjectSem" name="filter_ProjectSem" onchange="this.form.submit()">
                                <option value="">SELECT</option>
                                <?php
                                for($index = 1; $index<3; $index++){
                                    if(isset($_POST["filter_ProjectSem"]) && $_POST["filter_ProjectSem"] == $index){
                                        echo "<option selected value='".$index."'>".$index."</option>";
                                    }else{
                                        echo "<option value='".$index."'>".$index."</option>";
                                    }
                                }
                                ?>
                            </select>
                        </td>
                        <td colspan="3"></td>
                    </tr>
                    <tr>
                        <td >
                            <b>Supervisor</b>
                        </td>
                        <td colspan="2">
                            <select id="filter_Supervisor" name="filter_Supervisor" onchange="this.form.submit()">
                                <option value="" selected>SELECT</option>
                                <?php
                                foreach ($AL_Staff as $key => $value) {
                                    if(isset($_POST["filter_Supervisor"])) {
                                        $StaffID_Filter = preg_replace('/[^a-zA-Z._\s\-]/','',$_POST['filter_Supervisor']);
                                    } else {
                                        $StaffID_Filter = null;
                                    }
                                    $StaffID = $key;
                                    $StaffName = $value;
                                    if($StaffID_Filter == $StaffID){
                                        echo "<option value=" . $StaffID . " selected>";
                                        echo $StaffName;
                                        echo "</option>";
                                    }else{
                                        echo "<option value=" . $StaffID . ">";
                                        echo $StaffName;
                                        echo "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </td>
                        <td colspan="2" style="text-align:right;">
                            <input type="search" id="filter_Search" name="search" value="<?php echo isset($_POST['search']) ?  $cleanedSearchTitle : '' ?>" placeholder="e.g'Web' or 'SCSE19-0553' " />
                            <input type="submit" value="Search" title="Search for a project" name="click" class="bt"/>
                        </td>
                    </tr>

                    <!-- Wee Teck Zong [12.16.2020]
                      - Add Filter Exam Year Dropdownlist that pulls unique rows from database
                    -->
                    <tr>
                        <td >
                            <b>Exam Year</b>
                        </td>
                        <td colspan="3">
                            <select id="filter_ExamYear" name="filter_ExamYear" onchange="this.form.submit()">
                                <option value="">SELECT</option>
                                <?php

                                foreach ($DBData_rsUniqueExamYear as $row_DBData_rsUniqueExamYear)
                                {
                                  if(isset($_POST["filter_ExamYear"]) && $_POST["filter_ExamYear"] == $row_DBData_rsUniqueExamYear["examine_year"]){
                                      echo "<option selected value='".$row_DBData_rsUniqueExamYear["examine_year"]."'>".$row_DBData_rsUniqueExamYear["examine_year"]."</option>";
                                  }else{
                                      echo "<option value='".$row_DBData_rsUniqueExamYear["examine_year"]."'>".$row_DBData_rsUniqueExamYear["examine_year"]."</option>";
                                  }

                                }

                                ?>
                            </select>
                        </td>
                    </tr>

                    <!-- Wee Teck Zong [12.16.2020]
                      - Add Filter Exam Sem Dropdownlist
                    -->
                    <tr>
                        <td >
                            <b>Exam Sem</b>
                        </td>
                        <td>
                            <select id="filter_ExamSem" name="filter_ExamSem" onchange="this.form.submit()">
                                <option value="">SELECT</option>
                                <?php
                                for($index = 1; $index<3; $index++){
                                    if(isset($_POST["filter_ExamSem"]) && $_POST["filter_ExamSem"] == $index){
                                        echo "<option selected value='".$index."'>".$index."</option>";
                                    }else{
                                        echo "<option value='".$index."'>".$index."</option>";
                                    }
                                }
                                ?>
                            </select>
                        </td>
                        <td colspan="3"></td>
                    </tr>


                    <td colspan="6"  style="text-align:right">
                        <!--pagination-->
                        <br/>
                        <?php if ($pageNum_Project >0) { // Show if not first page ?>
                            <input type="submit" value="Previous" name="previouspage" class="bt"/>
                        <?php }?>

                        <?php if ($pageNum_Project < $total_pages) { // Show if not last page ?>
                            <input type="submit" value="Next" name="nextpage" class="bt"/>
                        <?php } // Show if not last page ?>
                    </td>
                    </tr>
                </table>

                <div style="text-align:right;">
                    <br/>
                    <?php
                    if($total_pages==-1)
                    {
                        echo "Page 0"." of " .($total_pages+1); //no records
                    }else {
                        echo "Page ".($pageNum_Project+1)." of " .($total_pages+1);
                    }
                    ?>
                </div>

                <?php $csrf->echoInputField();?>
            </form>

            <br/>
            <div class="table-responsive">
                <table id="tables" width="100%" border="1">
                    <col width="13%" />
                    <col width="6%" />
                    <col width="5%" />
                    <col width="32%" />
                    <col width="30%" />
                    <col width="6%" />
                    <col width="6%" />

                    <tr class="bg-dark text-white text-center">
                        <td>Project ID</td>
                        <td>Year</td>
                        <td>Sem</td>
                        <td>Project Title</td>
                        <td>Supervisor</td>
                        <td>Exam Year</td>
                        <td>Exam Sem</td>
                        <td>Action</td>
                    </tr>
                    <?php
                    foreach ($DBData_rsProject as $key => $value) {
                        echo "<tr>";
                        echo "<td class='text-center'>" . $value['project_id'] . "</td>";
                        echo "<td class='text-center'>" . $value['year'] . "</td>";
                        echo "<td class='text-center'>" . $value['sem'] . "</td>";
                        echo "<td class='text-center'>" . $value['title'] . "</td>";
                        echo "<td class='text-center'>" . $value['Supervisor'] . "</td>";
                        echo "<td class='text-center'>" . $value['examine_year'] . "</td>";
                        echo "<td class='text-center'>" . $value['examine_sem'] . "</td>";

                        /*Wee Teck Zong [12.05.2020]
                         - Create edit button to edit the project in a new webpage i created called "edit.php"
                         - ProjectID will be passed to edit.php on click
                        */
                        echo "<td class='text-center'> <a href='edit.php?edit=" . $value['project_id'] ."'>edit </a> <br/>";
                        echo "</tr>";
                    }

                    if (count($DBData_rsProject)==0){
                        echo "<tr>";
                        echo "<td class='text-center'>" . "</td>";
                        echo "<td class='text-center'>" . "</td>";
                        echo "<td class='text-center'>" .  "</td>";
                        echo "<td class='text-center' style='color:red; font-weight: bold'>" . "No Records" . "</td>";
                        echo "<td class='text-center'>" .  "</td>";
                        echo "<td class='text-center'>" .  "</td>";
                        echo "<td class='text-center'>" . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </table>
            </div>
            <div style="float:right; padding-top:25px;">
                <!-- Hidden table, to delete afterwards -->
                <form id="delete_project_form" method="post" action="submit_saveproject.php">
                    <?php $csrf->echoInputField(); ?>
                    <table style="display:none;" id="delete_ProjectTable" border="1" cellpadding="0" cellspacing="0" width="100%" >
                        <col width="15%"/>
                        <col width="5%"/>
                        <col width="5%"/>
                        <col width="10%"/>
                        <col width="10%"/>
                        <col width="5%"/>
                        <col width="10%"/>
                        <col width="40%"/>

                        <tr class="bg-dark text-white text-center">
                            <td>Project ID</td>
                            <td>Year</td>
                            <td>Sem</td>
                            <td>Project Title</td>
                            <td>Supervisor</td>
                            <td>Exam Year</td>
                            <td>Exam Sem</td>
                        </tr>
                    </table>
                    <input type="button"id="deleteEntry" title="Delete entry" class="btn btn-primary text-white text-center" style="font-size:12px;" value="Delete Selected" />
                    <input type="submit" id="saveChanges" title="Save all changes" value="Save Changes" class="btn bg-dark text-white text-center" style="font-size:12px;width:110px !important;"/>
                    <br>
                    <br>
            </div>
        </div>

        </form>
        <script type="text/javascript">
            //Deleting
            var tableArr = [];
            $(document).ready(function () {

                $("#tables").click(function highlight_row() {
                    var table = document.getElementById("tables");
                    var cells = table.getElementsByTagName("td");

                    for (var i = 0; i < cells.length; i++) {
                        // Take each cell
                        var cell = cells[i];
                        // do something on onclick event for cell
                        cell.onclick = function () {

                            // Get the row id where the cell exists
                            var rowID = this.parentNode.rowIndex;
                            console.log("selected" + rowID);
                            var rowClicked = table.getElementsByTagName("tr")[rowID];
                            if (rowClicked.style.backgroundColor != "yellow" && !rowClicked.classList.contains("selected")) {
                                rowClicked.style.backgroundColor = "yellow";
                                rowClicked.className += " selected";
                            } else {
                                rowClicked.style.backgroundColor = "";
                                rowClicked.classList.remove("selected");
                            }
                        }
                    }
                })

                $("#deleteEntry").click(function deleteRow() {
                    var fTable = document.getElementById("tables");
                    var selectedRowsIndex = getHighlightedRows();
                    if (selectedRowsIndex === undefined || selectedRowsIndex.length == 0)
                        alert("Select at least one project to delete. Click anywhere on the row to select.");
                    else {
                        var r = confirm("Delete the selected projects?");
                        if (r == true) {
                            for (var i = 0; i < selectedRowsIndex.length; i++) {
                                // add to hidden table
                                let table = document.getElementById("delete_ProjectTable");

                                // Get number of rows in the current hidden table to use it as name of each table cell, will include header so we dont need to + 1
                                var currentRow = document.getElementById("delete_ProjectTable").rows.length;

                                let tr = table.insertRow(-1);
                                tr.className = "text-center";
                                let project_id = tr.insertCell(0);
                                let year = tr.insertCell(1);
                                let sem = tr.insertCell(2);
                                let title = tr.insertCell(3);
                                let Supervisor = tr.insertCell(4);
                                let examine_year = tr.insertCell(5);
                                let examine_sem = tr.insertCell(6);

                                project_id.innerHTML = "<input type='text' name='project[" + currentRow + "][project_id]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(0).innerHTML + "' readonly />";
                                year.innerHTML = "<input type='number' name='project[" + currentRow + "][year]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(1).innerHTML + "' readonly />";
                                sem.innerHTML = "<input type='number' name='project[" + currentRow + "][sem]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(2).innerHTML + "' readonly />";
                                title.innerHTML = "<input type='text' name='project[" + currentRow + "][title]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(3).innerHTML + "' readonly />";
                                Supervisor.innerHTML = "<input type='text' name='project[" + currentRow + "][Supervisor]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(4).innerHTML + "' readonly'/>"
                                examine_year.innerHTML = "<input type='number' name='project[" + currentRow + "][examine_year]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(5).innerHTML + "' readonly />";
                                examine_sem.innerHTML = "<input type='text' name='project[" + currentRow + "][examine_sem]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(6).innerHTML + "' readonly />";

                                fTable.deleteRow(selectedRowsIndex[i]);
                            }
                        }
                    }
                })
            });

            function getHighlightedRows(e) {
                var table = document.getElementById("tables");
                var rows = table.getElementsByTagName("tr");
                var selectedRowsIndex = [];
                for (var i = rows.length-1 ; i >= 0 ; i--) {
                    var row = rows[i];
                    if (row.style.backgroundColor == "yellow" && row.classList.contains("selected")) {
                        selectedRowsIndex.push(i);
                    }
                }
                return selectedRowsIndex;
            }

            $("#FORM_FileToUpload_ProjectList").submit(function( event ) {
                // start of xm edits
                //this for displaying to show that the data format for excel uploaded is wrong, somehow it worked but dont understand why
                //window.location.href = ("project.php?error_code=5");
                // end of xm edits
                uploadFile();
                event.preventDefault();
            });
            function _(el){
                return document.getElementById(el);
            }
            function uploadFile(){
                if(_("FileToUpload_ProjectList").files.length == 0) {
                    alert("Please select a file to upload!");
                }
                else {
                    var file_data = _("FileToUpload_ProjectList").files[0];
                    var csrfToken = _("CSRF_token").value;
                    console.log(file_data.name + ", "+ file_data.size +", "+ file_data.type);
                    var formData = new FormData();

                    formData.append("file", file_data);
                    formData.append("csrf__",csrfToken );
                    _("loadingdiv").style.display  = "block";
                    $.ajax({
                        url: 'submit_import_projectlist.php',
                        data: formData,
                        processData: false,
                        contentType: false,
                        type: 'POST',
                        xhr: function () {
                            // this part is progress bar
                            var xhr = new window.XMLHttpRequest();
                            xhr.upload.addEventListener("progress", function (evt) {
                                _("progressbardiv").style.display  = "block";
                                if (evt.lengthComputable) {
                                    var percentComplete = evt.loaded / evt.total;
                                    percentComplete = parseInt(percentComplete * 100);
                                    $("#progressbar").text(percentComplete + "%");
                                    $("#progressbar").css('width', percentComplete + "%");

                                    if(percentComplete == 100){
                                        _('status').innerHTML = "File uploaded. Waiting for server to respond!";
                                        _('status').setAttribute("class", "success");
                                    }
                                }
                            }, false );
                            return xhr;
                        },
                        success: function (data) {
                            console.log(data);
                            /* original codes */
                            console.log("File uploaded. Server Responded!");
                            _('status').innerHTML = "File uploaded. Server Responded!";
                            /* editted by xm */
                            /*console.log("Incorrect data format! Please upload the correct excel!");
                            _('status').innerHTML = "Incorrect data format! Please upload the correct excel!";
                            _('status').setAttribute("class", "error");*/
                            /* end of edits */
                            _("progressbardiv").style.display  = "none";
                            _("loadingdiv").style.display  = "none";
                            $("#progressbar").text(0 + "%");
                            $("#progressbar").css('width', 0 + "%");
                            window.location.href = ("project.php");
                        },
                        error: function(data){
                            console.log("File upload failed!");
                            _('status').innerHTML = "File upload failed!";
                        }
                    });

                }
            }

            $('#filterParams').submit(function(event)
            {
                let year = $('#filter_ProjectYear option:selected');
                let sem = $('#filter_ProjectSem option:selected');
                let supervisor = $('#filter_Supervisor option:selected');
                let search = $('#filter_Search');
                search.val(search.val().replace(/[^a-zA-Z0-9\s\-()]/gi, ""));
                supervisor.val(supervisor.val().replace(/[^a-zA-Z._\s\-]/gi, ""));
                sem.val(sem.val().replace(/[^0-9]/gi, ""));
                year.val(year.val().replace(/[^0-9]/gi, ""));
            });

        </script>
        <div class="container col-sm-1 col-md-1 col-lg-1">
            <div style="position: fixed;">
                <br/><br/><br/>
                <a href="#backtop"><img src="../../../images/totop.png" width="30%" height="30%" /></a><br/>
                <a href="#tobottom"><img src="../../../images/tobottom.png" width="30%" height="30%" /></a><br/>
            </div>
        </div>
    </div>


    <!-- closing navigation div in nav.php -->
</div>

</div>
</div>
<!-- for going back to bottom -->
<div id="tobottom"></div>

<?php require_once('../../../footer.php'); ?>
</body>
<!-- InstanceEnd -->
</html>
<?php
unset($rsProject);
?>
