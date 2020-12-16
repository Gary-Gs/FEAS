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

/* Prevent XSS input */
foreach ($_GET as $name => $value) {
    $name = htmlentities($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = strip_tags($value);
}
$_GET   = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

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
        die ("Invalid Referer.");
    }
}

//initialize once for pagination
if(!isset($_SESSION['faculty_pagination'])){
    $_SESSION["faculty_pagination"] = '';
}

if(!isset($_SESSION['pre_filter_StaffID'])){
    $_SESSION["pre_filter_StaffID"] = '';
}

if(!isset($_SESSION['pre_search'])){
    $_SESSION["pre_search"] = '';
}

$_REQUEST['csrf'] 	= $csrf->cfmRequest();

//reset pagination when staff ID filter changes
if(isset($_REQUEST['filter_StaffID']) && !empty($_REQUEST['filter_StaffID'])){
    if($_SESSION["pre_filter_StaffID"] != $_REQUEST["filter_StaffID"]){
        $_SESSION["faculty_pagination"] = 0;
    }
}

$filter_StaffID  		= "%". (isset($_REQUEST['filter_StaffID']) && !empty($_REQUEST['filter_StaffID']) ? $_REQUEST['filter_StaffID'] : ''); //."%";
$_pre_filter_StaffID = explode("%",$filter_StaffID);
$_SESSION["pre_filter_StaffID"] = $_pre_filter_StaffID[1];

//reset pagination when search button is clicked
if(isset($_POST['click'])) {
    if($_SESSION["pre_search"] != $_REQUEST["search"]){
        $_SESSION["faculty_pagination"] = 0;
    }
}

$filter_Search = "%". (isset($_REQUEST['search']) && !empty($_REQUEST['search']) ? $_REQUEST['search'] : '') ."%";
$_SESSION["pre_search"] = explode("%",$filter_Search)[1];

$maxRow_faculty = 20;

//next page
if(isset($_POST["nextpage"])){
    $_SESSION["faculty_pagination"]+=1;
}

//previous page
if(isset($_POST["previouspage"])){
    $_SESSION["faculty_pagination"]-=1;
}

// store page number
$pageNum_faculty = (isset($_REQUEST['filter_StaffID']) && !empty($_REQUEST['filter_StaffID'])) ?
    $_SESSION["faculty_pagination"]: $_SESSION["faculty_pagination"];

//ensure valid page number
if($pageNum_faculty == ''){
    $pageNum_faculty = 0;
    $_SESSION["faculty_pagination"] = 0;
}

$startRow_faculty = $pageNum_faculty * $maxRow_faculty;

$query_rsStaff 			= "SELECT * FROM " . $TABLES['staff'];
//Filter for staff ID, staff name or Area of interest
$query_rsStaff_Filter 	= "SELECT DISTINCT s.id, s.name FROM " . 
                            $TABLES['staff'] 		. " as s LEFT JOIN " . 
                            $TABLES['staff_pref'] 	. " as sp ON s.id 	= sp.staff_id LEFT JOIN " .
                            $TABLES['interest_area'] . " as ia ON sp.prefer= ia.key AND archive=0 " . 
                            " WHERE s.id LIKE ? AND (s.id LIKE ? OR s.name LIKE ? OR ia.title LIKE ?) ORDER BY s.name ASC ";
$query_rsProjPref 		= "SELECT *, LOWER(staff_id) as staff_id  FROM " . $TABLES['staff_pref'] 	. " as sp WHERE (sp.prefer LIKE 'SCE%' OR sp.prefer LIKE 'SCSE%') AND archive=0";
$query_rsAreaPref 		= "SELECT *, LOWER(staff_id) as staff_id  FROM " . $TABLES['staff_pref'] 	. " as sp INNER JOIN ". $TABLES['interest_area'] ." as ia ON sp.prefer= ia.key AND archive=0";
try
{
    // GET ALL STAFF FOR FILTER DROP DOWN CONTROL
    $stmt_0 			= $conn_db_ntu->prepare($query_rsStaff);
    $stmt_0->execute();
    $DBData_rsStaff 	= $stmt_0->fetchAll(PDO::FETCH_ASSOC);
    $AL_Staff			= array();
    foreach ($DBData_rsStaff as $key => $value) {
        $AL_Staff[$value["id"]] = $value["name"];
    }
    asort($AL_Staff);

    // GET STAFF WITH USER SELECTED FILTER
    $stmt_1 				= $conn_db_ntu->prepare($query_rsStaff_Filter);
    $stmt_1->bindParam(1, $filter_StaffID);
    $stmt_1->bindParam(2, $filter_Search);
    $stmt_1->bindParam(3, $filter_Search);
    $stmt_1->bindParam(4, $filter_Search);
    $stmt_1->execute();
    $DBData_rsStaff_Filter 	= $stmt_1->fetchAll(PDO::FETCH_ASSOC);
    $AL_Staff_Filter 		= array();
    foreach ($DBData_rsStaff_Filter as $key => $value) {
        $AL_Staff_Filter[$value["id"]] = $value;
    }

    $Total_RowCount 	= count($AL_Staff_Filter);

    // GET STAFF PROJECT PREF
    $stmt_2 			= $conn_db_ntu->prepare($query_rsProjPref);
    $stmt_2->execute();
    $DBData_rsProjPref   = $stmt_2->fetchAll(PDO::FETCH_ASSOC);

    // GET STAFF AREA PREF
    $stmt_3 			= $conn_db_ntu->prepare($query_rsAreaPref);
    $stmt_3->execute();
    $DBData_rsAreaPref   = $stmt_3->fetchAll(PDO::FETCH_ASSOC);


}
catch (PDOException $e)
{
    die($e->getMessage());
}

$total_pages = ceil($Total_RowCount/$maxRow_faculty) - 1;

//limit record to 20 per page
$query__limit_rsStaff_Filter = sprintf("%s LIMIT %d,%d", $query_rsStaff_Filter, $startRow_faculty, $maxRow_faculty);

try
{
    // GET ALL STAFF FOR FILTER DROP DOWN CONTROL
    $stmt_0 			= $conn_db_ntu->prepare($query_rsStaff);
    $stmt_0->execute();
    $DBData_rsStaff 	= $stmt_0->fetchAll(PDO::FETCH_ASSOC);
    $AL_Staff			= array();
    foreach ($DBData_rsStaff as $key => $value) {
        $AL_Staff[$value["id"]] = $value["name"];
    }
    asort($AL_Staff);

    // GET STAFF WITH USER SELECTED FILTER
    $stmt_1 				= $conn_db_ntu->prepare($query__limit_rsStaff_Filter);
    $stmt_1->bindParam(1, $filter_StaffID);
    $stmt_1->bindParam(2, $filter_Search);
    $stmt_1->bindParam(3, $filter_Search);
    $stmt_1->bindParam(4, $filter_Search);
    $stmt_1->execute();
    $DBData_rsStaff_Filter 	= $stmt_1->fetchAll(PDO::FETCH_ASSOC);
    $AL_Staff_Filter 		= array();
    foreach ($DBData_rsStaff_Filter as $key => $value) {
        $AL_Staff_Filter[$value["id"]] = $value;
    }
    
    $Total_RowCount 	= count($AL_Staff_Filter);

}
catch (PDOException $e)
{
    die($e->getMessage());
}

//$conn_db_ntu = null;
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>Faculty List</title>
    <style>
        @media only screen and (max-width: 800px) {
            .floatWrapper {float:none!important;}
            .float-panel {position:static!important;}
            .main-content {padding:20px;margin-right:0px;}
        }

        #Table_Filter_FacultyList td{
            display:block;
            width:auto;
        }

        @media only screen and (min-width: 70em) {
            #Table_Filter_FacultyList td{
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
    <p>Uploading staff list...</p>
</div>


<div style="margin-left: -15px;">

    <div class="container-fluid">
        <?php require_once('../../nav.php'); ?>

        <!-- Page Content Holder -->
        <div class="container-fluid">
            <!-- for going back to top -->
            <div id="backtop"></div>
            <h3>Faculty List for Full Time Projects</h3>

            <?php
            if (isset ($_REQUEST['csrf']) || isset ($_REQUEST['validate'])) {
                echo "<p class='warn'> CSRF validation failed.</p>";
            }

            else {

                if (isset ($_SESSION['import_examiner'])){
                    echo "<p class='success'> Faculty List uploaded successfully.</p>";
                    unset($_SESSION['import_examiner']);
                }
                if (isset ($_SESSION['error_code'])) {
                    $error_code = $_SESSION['error_code'];
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
                    }
                    unset($_SESSION['error_code']);
                }
            }
            ?>

            <?php require_once('../../../upload_head.php'); ?>
            <form id="FORM_FileToUpload_FacultyList" method="post" enctype="multipart/form-data" role="form">
                <table style="text-align: left; width: 100%;">
                    <col width="20%">
                    <col width="20%">
                    <col width="20%">
                    <col width="20%">
                    <col width="20%">
                    <tr>
                        <td colspan="2">
                            Please select the <b><u>Faculty List</u></b>:
                        </td>
                        <td colspan="3" style="text-align: right;">
                            <input type="submit" value="Import" name="submit" class="btn btn-xs btn-success" >
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5">File Name format: <b>staff_list</b></td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <input type="file" id="FileToUpload_FacultyList" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" name="file" >
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
            <div class="table-responsive">
                <form name="searchbox" action="faculty.php" method="post">
                    <table id="Table_Filter_FacultyList" width="100%">
                        <colgroup>
                            <col width="20%" >
                            <col width="20%" >
                            <col width="20%" >
                            <col width="20%" >
                            <col width="20%" >
                        </colgroup>
                        <tr>
                            <td colspan="5" style="text-align: right;">
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
                            <td>
                                <b>Staff Name</b>
                            </td>
                            <td>
                                <select id="filter_StaffID" name="filter_StaffID" onchange="this.form.submit()">
                                    <option value="" selected>SELECT</option>
                                    <?php
                                    foreach ($AL_Staff as $key => $value) {
                                        if(isset($_REQUEST["filter_StaffID"])){
                                            $StaffID_Filter = $_REQUEST["filter_StaffID"];
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
                            <td colspan="3" style="text-align:right;">
                                <input id="search" type="search" name="search" value="<?php echo isset($_REQUEST['search']) ?  $_REQUEST['search'] : '' ?>" placeholder="e.g. 'Arijit Khan' or 'Cloud'" />
                                <input type="submit" value="Search" title="Search for a staff" name="click" class="bt"/>
                            </td>
                        </tr>

                        <td colspan="6"  style="text-align:right">
                            <!--pagination-->
                            <br/>
                            <?php if ($pageNum_faculty >0) { // Show if not first page ?>
                                <input type="submit" value="Previous" name="previouspage" class="bt"/>
                            <?php }?>

                            <?php if ($pageNum_faculty < $total_pages) { // Show if not last page ?>
                                <input type="submit" value="Next" name="nextpage" class="bt"/>
                            <?php } // Show if not last page ?>
                        </td>

                    </table>
                    <div style="text-align:right; width:100%">
                        <br/>
                        <?php
                        if($total_pages==-1)
                        {
                            echo "Page 0"." of " .($total_pages+1);
                        }else {
                            echo "Page ".($pageNum_faculty+1)." of " .($total_pages+1);
                        }
                        ?>
                </form>
            </div>
            <br/>

            <table id="table_faculty" cellpadding="0" cellspacing="0" width="100%" border="1">
                <col width="5%" />
                <col width="4%" />
                <col width="7%" />
                <col width="7%" />

                <tr class="bg-dark text-white text-center">
                    <td>Staff Name</td>
                    <td>Staff ID</td>
                    <td>Project Preference</td>
                    <td>Area Preference</td>
                </tr>
                <?php

                foreach ($AL_Staff_Filter as $key => $AL_Staff_value) {
                    $StaffID 	= $AL_Staff_value['id'];

                    echo "<tr>";
                    echo "<td>" . $AL_Staff_value['name'] . "</td>";
                    echo "<td>" . $StaffID. "</td>";
                    echo "<td>";
                    $AL_StaffProjPref = array();
                    foreach ($DBData_rsProjPref as $key => $DBData_rsProjPref_value) {
                        if($DBData_rsProjPref_value["staff_id"] == $StaffID){
                            $AL_StaffProjPref[] = $DBData_rsProjPref_value["prefer"];
                        }
                    }
                    $AL_StaffProjPref =array_unique($AL_StaffProjPref);
                    asort($AL_StaffProjPref);
                    echo implode(",<br/>",  array_filter($AL_StaffProjPref));
                    echo "</td>";
                    echo "<td>";
                    $AL_StaffAreaPref = array();
                    foreach ($DBData_rsAreaPref as $key => $DBData_rsAreaPref_value) {
                        if($DBData_rsAreaPref_value["staff_id"] == $StaffID){
                            $AL_StaffAreaPref[] = $DBData_rsAreaPref_value["title"];
                        }
                    }
                    $AL_StaffAreaPref = array_unique($AL_StaffAreaPref);
                    asort($AL_StaffAreaPref);
                    echo implode(",<br/>",  array_filter($AL_StaffAreaPref));
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </table>

            <br/><br/>
        </div>
    </div>
    <div class="container col-sm-1 col-md-1 col-lg-1">
        <div style="position: fixed;">
            <br/><br/><br/>
            <a href="#backtop"><img src="../../../images/totop.png" width="30%" height="30%" /></a><br/>
            <a href="#tobottom"><img src="../../../images/tobottom.png" width="30%" height="30%" /></a><br/>
        </div>
    </div>

    <script type="text/javascript">

        $("#FORM_FileToUpload_FacultyList").submit(function( event ) {
            uploadFile();
            event.preventDefault();
        });
        function _(el){
            return document.getElementById(el);
        }
        function uploadFile(){
            if(_("FileToUpload_FacultyList").files.length == 0) {
                alert("Please select a file to upload!");
            }
            else {
                var file_data = _("FileToUpload_FacultyList").files[0];
                var csrfToken = _("CSRF_token").value;
                console.log(file_data.name + ", "+ file_data.size +", "+ file_data.type);
                var formData = new FormData();

                formData.append("file", file_data);
                formData.append("csrf__",csrfToken );
                _("loadingdiv").style.display  = "block";
                $.ajax({
                    url: 'submit_import_facultylist.php',
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
                                $("#progressbar").text(percentComplete + '%');
                                $("#progressbar").css('width', percentComplete + '%');

                                if(percentComplete == 100){
                                    _('status').innerHTML = "File uploaded. Waiting for server to respond!";
                                }
                            }
                        }, false );
                        return xhr;
                    },
                    success: function (data) {
                        console.log(data);
                        console.log("File uploaded. Server Responded!");
                        _('status').innerHTML = "File uploaded. Server Responded!";
                        _("progressbardiv").style.display  = "none";
                        _("loadingdiv").style.display  = "none";
                        $("#progressbar").text(0 + '%');
                        $("#progressbar").css('width', 0 + '%');

                        window.location.href = ("faculty.php");
                    },
                    error: function(data){
                        console.log("File upload failed!");
                        _('status').innerHTML = "File upload failed!";
                    }
                });

            }
        }
    </script>

    <!-- page content closing div -->
</div>


<!-- closing navigation div in nav.php -->
</div>
</div>
</div>
<!-- for going back to bottom -->
<div id="tobottom"></div>

</div>
<?php require_once('../../../footer.php'); ?>

</body>
<!-- InstanceEnd -->
</html>

<?php
unset($rsStaff);
unset($rsProjPref);
unset($rsAreaPref);
?>
