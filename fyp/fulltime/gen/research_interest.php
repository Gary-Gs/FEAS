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

$filter_Search 		= "%". (isset($_REQUEST['search']) && !empty($_REQUEST['search']) ? $_REQUEST['search'] : '') ."%";
$filter_StaffID 	= "%". (isset($_POST['filter_StaffID']) && !empty($_POST['filter_StaffID']) ?
        preg_replace('/[^a-zA-Z._\s\-]/','',$_POST['filter_StaffID']) : '') ."%";

$query_rsResearchInterest = "SELECT name, staff_id, GROUP_CONCAT(interest SEPARATOR '---') AS interests FROM " . $TABLES['research_interest'] .
    " LEFT JOIN " . $TABLES['staff'] .
    " ON research_interest.staff_id = staff.id " .
    " WHERE staff_id LIKE ? AND (staff_id LIKE ? OR name LIKE ? OR interest LIKE ?) GROUP BY name, staff_id ORDER BY name ASC ";

$query_rsStaff 			= "SELECT * FROM " . $TABLES['staff'];

$query_totalRecords = "SELECT COUNT(DISTINCT staff_id) as total FROM " . $TABLES['research_interest'];

/*
if (isset($_POST['filter_StaffID'])) {
  $query_rsResearchInterestForStaff = "SELECT name, staff_id, GROUP_CONCAT(interest) AS interests FROM " . $TABLES['research_interest'] .
                              " LEFT JOIN " . $TABLES['staff'] .
                              " ON research_interest.staff_id = staff.id " .
                              " WHERE staff_id = ?" .
                              " GROUP BY name, staff_id ORDER BY name ASC ";

  $stmt = $conn_db_ntu->prepare($query_rsResearchInterestForStaff);
  $stmt->bindParam(1, $_POST['filter_StaffID']); //Search project year
  $stmt->execute();
  $rsResearchInterestForStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);
}*/

try {
    $stmt_0 			= $conn_db_ntu->prepare($query_rsStaff);
    $stmt_0->execute();
    $DBData_rsStaff 	= $stmt_0->fetchAll(PDO::FETCH_ASSOC);
    $AL_Staff			= array();
    foreach ($DBData_rsStaff as $key => $value) {
        $AL_Staff[$value["id"]] = $value["name"];
    }
    asort($AL_Staff);

    $stmt_1 = $conn_db_ntu->prepare($query_rsResearchInterest);
    $stmt_1->bindParam(1, $filter_StaffID);
    $stmt_1->bindParam(2, $filter_Search);
    $stmt_1->bindParam(3, $filter_Search);
    $stmt_1->bindParam(4, $filter_Search);
    $stmt_1->execute();
    $rsResearchInterest = $stmt_1->fetchAll(PDO::FETCH_ASSOC);
    $AL_Staff_Filter 		= array();
    foreach ($rsResearchInterest as $key => $value) {
        $AL_Staff_Filter[$value["staff_id"]] = $value;
    }
    asort($AL_Staff_Filter);
    $Total_RowCount 	= count($AL_Staff_Filter);


    $stmt_2 = $conn_db_ntu->prepare($query_totalRecords);
    $stmt_2->execute();
    $rs_totalRecords = $stmt_2->fetch();
    $totalRecords = $rs_totalRecords["total"];
}
catch (PDOException $e) {
    die($e->getMessage());
}

$conn_db_ntu = null;
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Research Interests</title>
    <style>
        #table_research td {white-space:pre-wrap; }

        @media only screen and (max-width: 800px) {
            .floatWrapper {float:none!important;}
            .float-panel {position:static!important;}
            .main-content {padding:20px;margin-right:0px;}
        }

        #Table_Filter_ResearchInterest td{
            display:block;
            width:auto;
        }

        @media only screen and (min-width: 70em) {
            #Table_Filter_ResearchInterest td{
                display:table-cell;
                margin-bottom:0px;
            }
        }
    </style>
</head>

<body>
<?php require_once('../../../php_css/headerwnav.php'); ?>
<div style="margin-left: -15px;">
    <div class="container-fluid">
        <?php require_once('../../nav.php'); ?>

        <div class="container-fluid">
            <!-- for going back to top -->
            <div id="backtop"></div>
            <h3>Research Interests</h3>
            <br />
            <!--?php
              if (isset($_SESSION['scrape']) && $_SESSION['scrape'] > 0 && $_SESSION['scrape'] == $totalRecords) {
                echo "<p class='success'> All research interests retrieved. " . "</p>";
                unset($_SESSION['scrape']);
              }
              if (isset($_SESSION['scrape']) && $_SESSION['scrape'] == 0) {
                echo "<p class='warn'> No research interests retrieved. " . "</p>";
                unset($_SESSION['scrape']);
              }
              if (isset($_SESSION['scrape']) && $_SESSION['scrape'] > 0 && $_SESSION['scrape'] != $totalRecords) {
                echo "<p class='warn'> Partial research interests retrieved. Please retry." . "</p>";
                unset($_SESSION['scrape']);
              }
            ?-->

            <div class="table-responsive">
                <div>
                    <form id="filterParams" name="searchbox" action="research_interest.php" method="post">
                        <table id="Table_Filter_ResearchInterest" width="100%" >
                            <colgroup>
                                <col width="10%" >
                                <col width="30%" >
                                <col width="20%" >
                                <col width="20%" >
                                <col width="20%" >
                            </colgroup>
                            <tr>
                                <td colspan="5" style="text-align:right;">
                                    <?php
                                    if( $Total_RowCount > 0){
                                        echo $Total_RowCount . " records";
                                    }else{
                                        echo "0 record";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td >
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
                                    <input type="search" name="search" value="<?php echo isset($_REQUEST['search']) ?  $_REQUEST['search'] : '' ?>" />
                                    <input type="submit" value="Search" title="Search for a project" class="bt"/>
                                </td>
                            </tr>

                        </table>
                    </form>




                    <!--a href="scrape.php" class="btn bg-dark text-white" style="font-size:12px; float:right" title="Retrieve Research Interest">Retrieve Research Interests</a-->
                </div>


                <br/>

                <table id="table_research" border="1" cellpadding="0" cellspacing="0" width="100%">
                    <col width="10%"/>
                    <col width="10%"/>
                    <col width="30%"/>

                    <tr class="bg-dark text-white text-center">
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Staff Name</th>
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Staff ID</th>
                        <th class="bg-dark text-white text-center" style="font-weight:normal">Research Interests</th>
                    </tr>

                    <?php
                    foreach ($rsResearchInterest as $row_rsResearchInterest) {
                        echo "<tr class='text-center'>";
                        echo "<td>" . $row_rsResearchInterest['name'] . "</td>";
                        echo "<td>" . $row_rsResearchInterest['staff_id'] . "</td>";

                        $interest = $row_rsResearchInterest["interests"];
                        if ($interest != "") {
                            $interest = str_replace('---', "\n", $interest);
                        }

                        echo "<td>" . $interest . "</td>";
                        echo "</tr>";
                    }

                    ?>
                </table>
                <br /><br />
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
</div>

<!-- closing navigation div in nav.php -->
</div>
<div id="tobottom"></div>
<?php require_once('../../../footer.php'); ?>
</body>
</html>
