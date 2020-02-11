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

// Set options for feedback type
$feedback_Type = array("Bug", "Suggestion", "Others");

// Retrieve feedback table based on these conditions
$filter_Year 	= "%". (isset($_POST['filter_Year']) && !empty($_POST['filter_Year']) ?
        preg_replace('/[^0-9]/','',$_POST['filter_Year']) : '') ."%";
$filter_Sem 	= "%". (isset($_POST['filter_Sem']) && !empty($_POST['filter_Sem']) ?
        preg_replace('/[^0-9]/','',$_POST['filter_Sem']) : '') ."%";
$filter_Rating 	= "%". (isset($_POST['filter_Rating']) && !empty($_POST['filter_Rating']) ?
        preg_replace('/[^0-9]/','',$_POST['filter_Rating']) : '') ."%";
$filter_Type  	= "%". (isset($_POST['filter_Type']) && !empty($_POST['filter_Type']) ? $_POST['filter_Type'] : '') ."%";
$filter_StaffID 	= "%". (isset($_POST['filter_StaffID']) && !empty($_POST['filter_StaffID']) ?
        preg_replace('/[^a-zA-Z._\s\-]/','',$_POST['filter_StaffID']) : '') ."%";

$query_rsFeedback = "SELECT * FROM " . $TABLES['fea_feedback'] . " as p1 LEFT JOIN " . $TABLES['staff'] . " as p2 ON p1.staff_id = p2.id ".
    "WHERE p1.exam_year LIKE ? AND p1.exam_sem LIKE ? AND p1.rating LIKE ? AND p1.type LIKE ? AND p1.staff_id LIKE ?";
$query_rsStaff		= "SELECT * FROM " . $TABLES["staff"];

try {
    // Populate staff name in drop down list
    $stmt_0 = $conn_db_ntu->prepare($query_rsStaff);
    $stmt_0->execute();
    $DBData_rsStaff 	= $stmt_0->fetchAll(PDO::FETCH_ASSOC);
    $AL_Staff			= array();
    foreach ($DBData_rsStaff as $key => $value) {
        $AL_Staff[$value["id"]] = $value["name"];
    }
    asort($AL_Staff);

    // Retrieve Feedback
    $stmt_1 = $conn_db_ntu->prepare($query_rsFeedback);
    $stmt_1->bindParam(1, $filter_Year); //Search project year
    $stmt_1->bindParam(2, $filter_Sem); //Search project sem
    $stmt_1->bindParam(3, $filter_Rating); //Search feedback rating
    $stmt_1->bindParam(4, $filter_Type); //Search feedback type
    $stmt_1->bindParam(5, $filter_StaffID); //Search staff name
    $stmt_1->execute();
    $rsfeedback = $stmt_1->fetchAll(PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    die($e->getMessage());
}

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Feedback</title>
    <style>
        table{
            table-layout: fixed;
        }
        td{
            word-wrap:break-word
        }
    </style>

</head>

<body>
<?php require_once('../../../php_css/headerwnav.php'); ?>


<div style="margin-left: -15px;">
    <div class="container-fluid">
        <?php require_once('../../nav.php'); ?>

        <!-- Page Content Holder -->
        <div class="container-fluid">
            <div id="backtop"></div>
            <h3>Feedback</h3>
            <?php
            if (isset($_SESSION['deleteFeedback']) && $_SESSION['deleteFeedback'] == 'success') {
                echo "<p class='success'> All selected feedback cleared.</p>";
                unset($_SESSION['deleteFeedback']);
            }
            if (isset($_SESSION['deleteFeedback']) && $_SESSION['deleteFeedback'] == 'error') {
                echo "<p class='warn'> Some feedback not deleted.</p>";
                unset($_SESSION['deleteFeedback']);
            }
            if (isset($_SESSION['clearAllFeedback']) && $_SESSION['clearAllFeedback'] == 'clearAll') {
                echo "<p class='success'> All feedback cleared.</p>";
                unset($_SESSION['clearAllFeedback']);
            }
            ?>

            <form id="filterParams" name="searchbox" action="feedback.php" method="post">
                <div class="table-responsive">
                    <table id="Table_Filter_ProjectList" width="100%">
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
                                <select id="filter_Year" name="filter_Year" onchange="this.form.submit()">
                                    <option value="">SELECT</option>
                                    <?php
                                    $CurrentYear = sprintf("%02d", substr(date("Y"), -2));
                                    $LastestYear = sprintf("%02d", substr(date("Y"), -2));
                                    $EarlistYear = $CurrentYear - 10;

                                    // Loops over each int[year] from current year, back to the $earliest_year [1950]
                                    foreach ( range( $LastestYear, $EarlistYear ) as $i ) {
                                        $i = sprintf("%02d", substr($i, -2)) . (sprintf("%02d", (substr($i, -2)+1)));

                                        if(isset($_POST["filter_Year"]) && $_POST["filter_Year"] == $i){
                                            echo "<option selected value='".$i."'>".$i."</option>";
                                        }else{
                                            echo "<option value='".$i."'>".$i."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td >
                                <b> Sem</b>
                            </td>
                            <td>
                                <select id="filter_Sem" name="filter_Sem" onchange="this.form.submit()">
                                    <option value="">SELECT</option>
                                    <?php
                                    for($index = 1; $index<3; $index++){
                                        if(isset($_POST["filter_Sem"]) && $_POST["filter_Sem"] == $index){
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
                                <b> Rating</b>
                            </td>
                            <td>
                                <select id="filter_Rating" name="filter_Rating" onchange="this.form.submit()">
                                    <option value="">SELECT</option>
                                    <?php
                                    for($index = 1; $index<=5; $index++){
                                        if(isset($_POST["filter_Rating"]) && $_POST["filter_Rating"] == $index){
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
                                <b> Type</b>
                            </td>
                            <td>
                                <select id="filter_Type" name="filter_Type" onchange="this.form.submit()">
                                    <option value="">SELECT</option>
                                    <?php
                                    foreach ($feedback_Type as $type) {
                                        if(isset($_POST["filter_Type"])) {
                                            $Type_Filter = preg_replace('/[^a-zA-Z._\s\-]/','',$_POST['filter_Type']);
                                        } else {
                                            $Type_Filter = null;
                                        }
                                        if ($Type_Filter == $type) {
                                            echo "<option value=" . $type . " selected>";
                                            echo $type;
                                            echo "</option>";
                                        } else{
                                            echo "<option value=" . $type . ">";
                                            echo $type;
                                            echo "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td >
                                <b>Staff Name</b>
                            </td>
                            <td colspan="2">
                                <select id="filter_StaffID" name="filter_StaffID" onchange="this.form.submit()">
                                    <option value="" selected>SELECT</option>
                                    <?php
                                    foreach ($AL_Staff as $key => $value) {
                                        if(isset($_POST["filter_StaffID"])) {
                                            $StaffID_Filter = preg_replace('/[^a-zA-Z._\s\-]/','',$_POST['filter_StaffID']);
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
                            <!--td colspan="2" style="text-align:right;">
                                <input type="search" id="filter_Search" name="search" value="<!--?php echo isset($_POST['search']) ?  $cleanedSearch : '' ?>" />
                                <input type="submit" value="Search" title="Search for a project" class="bt"/>
                            </td-->
                            <td colspan="2" style="text-align:right;">
                                <a href="submit_download_feedback.php" class="btn bg-dark text-white" style="font-size:12px;" title="Download Feedback">Download Feedback</a>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php $csrf->echoInputField();?>
            </form>
            <br />

            <div class="table-responsive">
                <table id="feedbackTable" border="1" cellpadding="0" cellspacing="0" width="100%" >
                    <col width="15%"/>
                    <col width="5%"/>
                    <col width="5%"/>
                    <col width="10%"/>
                    <col width="10%"/>
                    <col width="5%"/>
                    <col width="10%"/>
                    <col width="40%"/>

                    <tr class="bg-dark text-white text-center">
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Created on</th>
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Year</th>
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Sem</th>
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Staff Name</th>
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Staff ID</th>
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Overall Rating</th>
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Type</th>
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Comment</th>
                    </tr>

                    <?php
                    foreach ($rsfeedback as $row_rsfeedback) {
                        //echo "<tr class='text-center'>";
                        /*echo "<td><input style='text-align:center; background:rgba(0,0,0,0);border:none;' type='text'  size='19' name='feedback_datetime_" . $row_rsfeedback['feedback_datetime'] . "'  value='" . $row_rsfeedback['feedback_datetime'] . "' readonly /></td>";
                        echo "<td><input style='text-align:center; background:rgba(0,0,0,0);border:none;' type='text' size='4' name='exam_year_" . $row_rsfeedback['exam_year'] . "'  value='" . $row_rsfeedback['exam_year'] . "' readonly /></td>";
                        echo "<td><input style='text-align:center; background:rgba(0,0,0,0);border:none;' type='text' size='1' name='exam_sem_" . $row_rsfeedback['exam_sem'] . "'  value='" . $row_rsfeedback['exam_sem'] . "' readonly /></td>";
                        echo "<td><input style='text-align:center; background:rgba(0,0,0,0);border:none;' type='text' name='name_" . $row_rsfeedback['name'] . "'  value='" . $row_rsfeedback['name'] . "' readonly /></td>";
                        echo "<td><input style='text-align:center; background:rgba(0,0,0,0);border:none;' type='text' name='staff_id_" . $row_rsfeedback['staff_id'] . "'  value='" . $row_rsfeedback['staff_id'] . "' readonly /></td>";
                        echo "<td><input style='text-align:center; background:rgba(0,0,0,0);border:none;' type='text' size='1' name='rating_" . $row_rsfeedback['rating'] . "'  value='" . $row_rsfeedback['rating'] . "' readonly /></td>";
                        echo "<td><input style='text-align:center; background:rgba(0,0,0,0);border:none;' type='text' size='10' name='type_" . $row_rsfeedback['type'] . "'  value='" . $row_rsfeedback['type'] . "' readonly /></td>";
                        echo "<td><textarea style='text-align:center; background:rgba(0,0,0,0); width: 100%; height: 100%; border: none' maxlength='500' name='comment_" . $row_rsfeedback['comment'] . "' readonly>" . $row_rsfeedback['comment'] . "</textarea></td>";
                        */  echo "<tr class='text-center'>";
                        echo "<td>" . $row_rsfeedback['feedback_datetime'] . "</td>";
                        echo "<td>" . $row_rsfeedback['exam_year'] . "</td>";
                        echo "<td>" . $row_rsfeedback['exam_sem'] . "</td>";
                        echo "<td>" . $row_rsfeedback['name'] . "</td>";
                        echo "<td>" . $row_rsfeedback['staff_id'] . "</td>";
                        echo "<td>" . $row_rsfeedback['rating'] . "</td>";
                        echo "<td>" . $row_rsfeedback['type'] . "</td>";
                        echo "<td>" . $row_rsfeedback['comment'] . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </table>
                <br />
                <div style="float:right; padding-top:25px;">
                    <a href="clearfeedback.php" class="btn btn-primary text-white text-center" title="Delete all feedback" style="font-size:12px;" onclick="return confirm('Delete all feedback?')">Delete All</a>
                    <input type="button"id="deleteEntry" title="Delete entry" class="btn btn-primary text-white text-center" style="font-size:12px;" value="Delete Selected" />
                </div>

                <!--div style="float:right">
                  <a href="clearfeedback.php" class="btn btn-primary text-white text-center" style="font-size:12px; float:right;" title="Delete All" onclick="return confirm('Are you sure you want to delete all feedback?')">Delete All</a>
                  <input type="button" id="deleteEntry" title="Delete entry"
                         value="Delete selected" class="btn btn-primary text-white text-center"
                         style="font-size:12px; margin-left: 30px;"/>
                 </div-->
                <br /><br />
                <br /><br />

                <!-- Hidden table, to delete afterwards -->
                <form id="delete_feedback_form" method="post" action="submit_savefeedback.php">
                    <?php $csrf->echoInputField(); ?>
                    <table style="display:none;" id="delete_feedbackTable" border="1" cellpadding="0" cellspacing="0" width="100%" >
                        <col width="15%"/>
                        <col width="5%"/>
                        <col width="5%"/>
                        <col width="10%"/>
                        <col width="10%"/>
                        <col width="5%"/>
                        <col width="10%"/>
                        <col width="40%"/>

                        <tr class="bg-dark text-white text-center">
                            <th>Created on</th>
                            <th>Year</th>
                            <th>Sem</th>
                            <th>Staff Name</th>
                            <th>Staff ID</th>
                            <th>Overall Rating</th>
                            <th>Type</th>
                            <th>Comment</th>
                        </tr>
                    </table>
                    <div style="float:right">
                        <input type="submit" id="saveChanges" title="Save all changes" value="Save Changes"
                               class="btn bg-dark text-white text-center" style="font-size:12px !important;"/>
                        <br/><br/>
                    </div>
                </form>

                <script>
                    var tableArr = [];

                    $(document).ready(function () {

                        $("#feedbackTable").click(function highlight_row() {
                            var table = document.getElementById("feedbackTable");
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
                            var fTable = document.getElementById("feedbackTable");
                            var selectedRowsIndex = getHighlightedRows();
                            if (selectedRowsIndex === undefined || selectedRowsIndex.length == 0)
                                alert("Select at least one feedback to delete. Click anywhere on the row to select.");
                            else {
                                var r = confirm("Delete the selected feedback?");
                                if (r == true) {
                                    for (var i = 0; i < selectedRowsIndex.length; i++) {
                                        // add to hidden table
                                        let table = document.getElementById("delete_feedbackTable");

                                        // Get number of rows in the current hidden table to use it as name of each table cell, will include header so we dont need to + 1
                                        var currentRow = document.getElementById("delete_feedbackTable").rows.length;

                                        let tr = table.insertRow(-1);
                                        tr.className = "text-center";
                                        let feedbackDateTime = tr.insertCell(0);
                                        let examYear = tr.insertCell(1);
                                        let examSem = tr.insertCell(2);
                                        let staffName = tr.insertCell(3);
                                        let staffID = tr.insertCell(4);
                                        let rating = tr.insertCell(5);
                                        let type = tr.insertCell(6);
                                        let comment = tr.insertCell(7);

                                        feedbackDateTime.innerHTML = "<input type='text' name='feedback[" + currentRow + "][feedbackDateTime]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(0).innerHTML + "' readonly />";
                                        examYear.innerHTML = "<input type='number' name='feedback[" + currentRow + "][examYear]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(1).innerHTML + "' readonly />";
                                        examSem.innerHTML = "<input type='number' name='feedback[" + currentRow + "][examSem]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(2).innerHTML + "' readonly />";
                                        staffName.innerHTML = "<input type='text' name='feedback[" + currentRow + "][staffName]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(3).innerHTML + "' readonly />";
                                        staffID.innerHTML = "<input type='text' name='feedback[" + currentRow + "][staffID]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(4).innerHTML + "' readonly'/>"
                                        rating.innerHTML = "<input type='number' name='feedback[" + currentRow + "][rating]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(5).innerHTML + "' readonly />";
                                        type.innerHTML = "<input type='text' name='feedback[" + currentRow + "][type]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(6).innerHTML + "' readonly />";
                                        comment.innerHTML = "<input type='text' name='feedback[" + currentRow + "][comment]'  value='" + fTable.rows[selectedRowsIndex[i]].cells.item(7).innerHTML + "' readonly />";

                                        fTable.deleteRow(selectedRowsIndex[i]);
                                    }
                                }
                            }
                        })
                    });

                    function getHighlightedRows(e) {
                        var table = document.getElementById("feedbackTable");
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
                    /*
                                  $(document).ready(function () {
                                    var tableArr = [];

                                      $("#deleteEntry").click(function deleteRow() {
                                          var fTable = document.getElementById("feedbackTable");
                                          var selectedRowsIndex = getHighlightedRows();
                                          if (selectedRowsIndex === undefined || selectedRowsIndex.length == 0)
                                              alert("Select at least one feedback to delete. Click anywhere on the row to select.");
                                          else {
                                              var r = confirm("Delete the selected feedback?");
                                              if (r == true) {
                                                  for (var i = 0; i < selectedRowsIndex.length; i++) {
                                                    tableArr.push({
                                                        feedbackDateTime: fTable.rows[i].cells[0].innerHTML,
                                                        staffname: fTable.rows[i].cells[1].innerHTML,
                                                        staffid: fTable.rows[i].cells[2].innerHTML,
                                                        rating: fTable.rows[i].cells[3].innerHTML,
                                                        type: fTable.rows[i].cells[4].innerHTML,
                                                        comment: fTable.rows[i].cells[5].innerHTML
                                                    });
                                                    //alert(tableArr);

                                                    fTable.deleteRow(selectedRowsIndex[i]);
                                                  }
                                              }
                                          }
                                      })
                                  }); */
                </script>
            </div>
        </div>
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
<div id="tobottom"></div>
<?php require_once('../../../footer.php'); ?>
</body>
</html>
