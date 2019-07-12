<?php require_once('../../../Connections/db_ntu.php');
        require_once('./entity.php');
        require_once('../../../CSRFProtection.php');
        require_once('../../../Utility.php');?>

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
?>

<?php
      $csrf = new CSRFProtection();
      $time = time();
      $currentMonth = date("M", $time);
      $nmonth = date('m', strtotime($currentMonth));

      if(isset($_REQUEST['filter_ProjectSem']) && !empty($_REQUEST['filter_ProjectSem'])){
            $filter_ProjectSem = $_REQUEST['filter_ProjectSem'];
      }
      else{
            if(($nmonth >= 01) && ($nmonth <= 06)){
                  $filter_ProjectSem = 2;
            }
            elseif(($nmonth >= 07) && ($nmonth <= 12)){
                  $filter_ProjectSem = 1;
            }
      }


      if(isset($_REQUEST['filter_ProjectYear']) && !empty($_REQUEST['filter_ProjectYear'])){
            $filter_ProjectYear = $_REQUEST['filter_ProjectYear'];
      }
      else{

            $projectCurrentYear = date("Y", $time);
            $projectPreviousYear = $projectCurrentYear - 1;
            $projectCurrentYearSub = substr($projectCurrentYear, 2, 4);
            $projectPreviousYearSub = substr($projectPreviousYear, 2, 4);
            $filter_ProjectYear = $projectPreviousYearSub . $projectCurrentYearSub;
      }

      // for semester 1, we retrieve the exemption number from exemption column in staff table
      if($filter_ProjectSem == 1){

                  // you need to order them in this order so that you will get the supervising slot first then examining slot
            $query_rsProject = "SELECT DISTINCT staff_name, staff_id, project_id, student_name, project_name, no_of_exemption, examinerid, examiner_name, day, slot, room, supervisor_name
            FROM
            (SELECT s.name as staff_name, s.id as staff_id, p.project_id as project_id, student.name as student_name, p1.title as project_name,
            s.exemption as no_of_exemption, r.examiner_id as examinerid, examiner_info.name as examiner_name,  r.day as day, r.slot as slot, r.room as room, null as supervisor_name
            FROM  " . $TABLES['fyp_assign'] . " as p
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
            JOIN " . $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id
            LEFT JOIN " . $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id
            LEFT JOIN " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
            UNION ALL
            SELECT examiner_info.name as staff_name, r.examiner_id as staff_id,
            p.project_id as project_id, student.name as student_name, p1.title as project_name,
            examiner_info.exemption as no_of_exemption, null as examinerid, null as examiner_name,  r.day as day, r.slot as slot, r.room as room, s.name as supervisor_name
            FROM  " . $TABLES['fyp_assign'] . " as p
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
            JOIN " . $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id
            LEFT JOIN " . $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id
            LEFT JOIN " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
            ) as totalResults
            ORDER BY staff_name, supervisor_name, examiner_name";


            //you need to get the supervising project count, exemption count and project examining count
            $query_rsProjectCount = "SELECT DISTINCT staff_name, staff_id, SUM(project_count) as project_count, no_of_exemption,
            SUM(examining_project) as examining_project
            FROM
            (SELECT s.name as staff_name, s.id as staff_id, COUNT(p.project_id) as project_count, s.exemption as no_of_exemption,
            0 examining_project
            FROM  " . $TABLES['fyp_assign'] . "  as p
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
            JOIN " . $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id
            LEFT JOIN " . $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id
            LEFT JOIN " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
            GROUP BY s.name, s.id
            UNION ALL
                  SELECT s.name as staff_name, s.id as staff_id, 0 as project_count, s.exemption as no_of_exemption,
                  COUNT(r.project_id) as  examining_project
                  FROM " . $TABLES['staff'] . " as s
                  JOIN " . $TABLES['allocation_result'] . " as r ON s.id = r.examiner_id
                  JOIN " . $TABLES['fea_projects'] . " as projects ON projects.project_id = r.project_id
                  WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
            GROUP BY s.name, s.id, s.exemption
            UNION ALL
            SELECT examiner_info.name as staff_name, r.examiner_id as staff_id,
            0 project_count, examiner_info.exemption as no_of_exemption, 0 examining_project
            FROM  " . $TABLES['fyp_assign'] . " as p
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
            JOIN " . $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id
            LEFT JOIN " . $TABLES['allocation_result'] . "  as r ON r.project_id = p.project_id
            LEFT JOIN " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
            ) as totalResults
            GROUP By staff_name, staff_id
            ORDER BY staff_name";
      }
      // for semester 2, we retrieve the exemption number from exemptionS2 column in staff table
      else{

            // you need to order them in this order so that you will get the supervising slot first then examining slot
            $query_rsProject = "SELECT DISTINCT staff_name, staff_id, project_id, student_name, project_name, no_of_exemption, examinerid, examiner_name, day, slot, room, supervisor_name
            FROM
            (SELECT s.name as staff_name, s.id as staff_id, p.project_id as project_id, student.name as student_name, p1.title as project_name,
            s.exemptionS2 as no_of_exemption, r.examiner_id as examinerid, examiner_info.name as examiner_name,  r.day as day, r.slot as slot, r.room as room, null as supervisor_name
            FROM  " . $TABLES['fyp_assign'] . " as p
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
            JOIN " . $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id
            LEFT JOIN " . $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id
            LEFT JOIN " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
            UNION ALL
            SELECT examiner_info.name as staff_name, r.examiner_id as staff_id,
            p.project_id as project_id, student.name as student_name, p1.title as project_name,
            examiner_info.exemptionS2 as no_of_exemption, null as examinerid, null as examiner_name,  r.day as day, r.slot as slot, r.room as room, s.name as supervisor_name
            FROM  " . $TABLES['fyp_assign'] . " as p
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
            JOIN " . $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id
            LEFT JOIN " . $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id
            LEFT JOIN " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
            ) as totalResults
            ORDER BY staff_name, supervisor_name, examiner_name";

            //you need to get the supervising project count, exemption count and project examining count
            $query_rsProjectCount = "SELECT DISTINCT staff_name, staff_id, SUM(project_count) as project_count, no_of_exemption,
            SUM(examining_project) as examining_project
            FROM
            (SELECT s.name as staff_name, s.id as staff_id, COUNT(p.project_id) as project_count, s.exemptionS2 as no_of_exemption,
            0 examining_project
            FROM  " . $TABLES['fyp_assign'] . "  as p
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
            JOIN " . $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id
            LEFT JOIN " . $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id
            LEFT JOIN " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
            GROUP BY s.name, s.id
            UNION ALL
                  SELECT s.name as staff_name, s.id as staff_id, 0 as project_count, s.exemptionS2 as no_of_exemption,
                  COUNT(r.project_id) as  examining_project
                  FROM " . $TABLES['staff'] . " as s
                  JOIN " . $TABLES['allocation_result'] . " as r ON s.id = r.examiner_id
                  JOIN " . $TABLES['fea_projects'] . " as projects ON projects.project_id = r.project_id
                  WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
            GROUP BY s.name, s.id, s.exemption
            UNION ALL
            SELECT examiner_info.name as staff_name, r.examiner_id as staff_id,
            0 project_count, examiner_info.exemptionS2 as no_of_exemption, 0 examining_project
            FROM  " . $TABLES['fyp_assign'] . " as p
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
            JOIN " . $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id
            LEFT JOIN " . $TABLES['allocation_result'] . "  as r ON r.project_id = p.project_id
            LEFT JOIN " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
            ) as totalResults
            GROUP By staff_name, staff_id
            ORDER BY staff_name";
      }

      $query_rsProjectExaminingCount     = "SELECT r.examiner_id as examinerid, COUNT(p.project_id) as project_count
      FROM " . $TABLES['fyp_assign'] . " as p
      JOIN " .$TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
      JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
      JOIN ". $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id
      JOIN  " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id
      WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)
      GROUP BY r.examiner_id
      ORDER BY examiner_info.id";

      $query_supervisingCount = "SELECT DISTINCT s.name as staff_name, s.id as staff_id
            FROM  " . $TABLES['fyp_assign'] . " as p
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
            JOIN " . $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id
            LEFT JOIN " . $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id
            LEFT JOIN " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?)";


      try
      {
            //Get before allocation data
            $stmt_0 = $conn_db_ntu->prepare($query_rsProject);
            $stmt_0->bindParam(1, $filter_ProjectSem); //Search project sem
            $stmt_0->bindParam(2, $filter_ProjectYear); //Search project year
            $stmt_0->bindParam(3, $filter_ProjectSem); //Search project sem
            $stmt_0->bindParam(4, $filter_ProjectYear); //Search project year
            $stmt_0->execute();
            $projects = $stmt_0->fetchAll(PDO::FETCH_ASSOC);

            //Get before allocation project count
            $stmt_1 = $conn_db_ntu->prepare($query_rsProjectCount);
            $stmt_1->bindParam(1, $filter_ProjectSem); //Search project sem
            $stmt_1->bindParam(2, $filter_ProjectYear); //Search project year
            $stmt_1->bindParam(3, $filter_ProjectSem); //Search project sem
            $stmt_1->bindParam(4, $filter_ProjectYear); //Search project year
            $stmt_1->bindParam(5, $filter_ProjectSem); //Search project sem
            $stmt_1->bindParam(6, $filter_ProjectYear); //Search project year
            $stmt_1->execute();
            $projectsCount = $stmt_1->fetchAll(PDO::FETCH_ASSOC);
            $totalRowCount = count($projectsCount);



            //Get after examining project count
            $stmt_3 = $conn_db_ntu->prepare($query_rsProjectExaminingCount);
            $stmt_3->bindParam(1, $filter_ProjectSem); //Search project sem
            $stmt_3->bindParam(2, $filter_ProjectYear); //Search project year
            $stmt_3->execute();
            $examiningProjectsCount = $stmt_3->fetchAll(PDO::FETCH_ASSOC);


            //Get supervising project count
            $stmt_4 = $conn_db_ntu->prepare($query_supervisingCount);
            $stmt_4->bindParam(1, $filter_ProjectSem); //Search project sem
            $stmt_4->bindParam(2, $filter_ProjectYear); //Search project year
            $stmt_4->execute();
            $supervisingProjectsCount = $stmt_4->fetchAll(PDO::FETCH_ASSOC);

      }
      catch (PDOException $e)
      {
            die($e->getMessage());
      }

      function getMaxColumnCount(){
      $max = 0;
        global $projectsCount;
            foreach($projectsCount as $value){
                  $no_of_exemption = $value['no_of_exemption'] - $value['project_count'];
                  if($no_of_exemption > 30){
                        $no_of_exemption = 30;
                  }
                  if(($no_of_exemption + $value['project_count']) > $max){
                        $max = ($no_of_exemption  + $value['project_count'] + 2);
                  }
            }
        return $max;
      }

      /*function getMaxColumnCountExamining(){
      $max = 0;
      global $projectsCount;
      global $examiningProjectsCount;

            foreach($projectsCount as $value){
                  $no_of_exemption = $value['no_of_exemption'] - $value['project_count'];
                  if($no_of_exemption > 30){
                        $no_of_exemption = 30;
                  }
                  if(!empty($examiningProjectsCount)){
                        foreach($examiningProjectsCount as $value1){

                             if(strcmp($value['staff_id'], $value1['examinerid']) == 0){
                                   if(($no_of_exemption + $value['project_count'] + $value['project_count'] + $value['examining_project']) > $max){
                                          $max = ($no_of_exemption  + $value['project_count'] + $value['project_count'] + $value['examining_project'] + 5);
                                    }
                              }
                        }
                  }
                  else{
                        if(($no_of_exemption + $value['project_count']) > $max){
                        $max = ($no_of_exemption  + $value['project_count'] + $value['examining_project'] + 5);
                        }

                  }
            }

        return $max;
      }*/

?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Allocation Timetable</title>

      <style>
      .clash_td {
            background: #FFFF00;
            color: #FF0000;
            font-weight: bold;
      }

      .EditAllocationtBtn {
        background-color: transparent;
        border: none;
        height: 3em;
        white-space: normal;
      }

      .EditAllocationtBtn:hover {
        font-weight: bold;
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
                  <h3>Results Visualization For Full Time Projects</h3>
                  <?php
                  if (isset ($_REQUEST['csrf']) ||isset ($_REQUEST['validate'])) {
                              echo "<p class='warn'> CSRF validation failed.</p>";
                        }
                       //echo $filter_ProjectSem;
                       //echo $nmonth;
                  ?>
                  <br/>
                  <table width="80%" border="1">
                        <colgroup>
                                    <col width="10%" >
                                    <col width="80%" >
                                    <col width="10%" >
                        </colgroup>
                        <tr class="bg-dark text-white text-center">
                              <td colspan="3" style="font-size: 18px;padding: 7px;">Legend</td>
                        </tr>
                        <tr class="text-white text-center" style="background-color: grey;">
                              <td>Color Indication</td>
                              <td>Description</td>
                              <td>Color</td>
                        </tr>
                        <tr class="text-center">
                              <td>Green</td>
                              <td>
                                    Indicates <b>Supervising Projects</b><br/>
                                    When the slot background is highlighted in green, it indicates that project is under the supervision of the staff labelled within the same row.
                              </td>
                              <td bgcolor="limegreen"></td>
                        </tr>
                        <tr class="text-center">
                              <td>Yellow</td>
                              <td>
                                    Indicates <b>Number of Exemption Slots</b><br/>
                                    Each Supervising Projects contributes to 3 exemption units. <br/>
                                    Calculation of exemption slots = No. of Exemption Count - No. of Supervising Projects.
                              </td>
                              <td bgcolor="yellow"></td>
                        </tr>
                        <tr class="text-center">
                              <td>White</td>
                              <td>Indicates <b>Examining Projects</b><br/>
                              It indicates that the staff is being allocated as the examiner of the selected project.</td>
                              <td></td>
                        </tr>

                  </table>
                  <br/>
                  <h4><u>Filter Options:</u></h4>
                  <form name="searchbox" action="result_visualization.php" method="post" >
                        <table width="100%">
                              <colgroup>
                                    <col width="10%" >
                                    <col width="80%" >
                              </colgroup>
                              <tr>
                                    <td style="padding: 2px;"><b>Sem</b></td>
                                    <td style="padding: 2px;">
                                          <select id="filter_ProjectSem" name="filter_ProjectSem" onchange="this.form.submit()" >
                                                <option value="">SELECT</option>
                                                <?php
                                                for($index = 1; $index<3; $index++){
                                                      if(isset($_REQUEST["filter_ProjectSem"]) && $_REQUEST["filter_ProjectSem"] == $index){
                                                            echo "<option selected value='".$index."'>".$index."</option>";
                                                      }else{
                                                            echo "<option value='".$index."'>".$index."</option>";
                                                      }
                                                }
                                                ?>
                                          </select>
                                    </td>
                              </tr>

                              <tr>
                                    <td style="padding: 2px;"><b>Year</b></td>
                                    <td style="padding: 2px;">
                                          <select id="filter_ProjectYear" name="filter_ProjectYear" onchange="this.form.submit()">
                                                <option value="">SELECT</option>
                                                <?php
                                                $CurrentYear = sprintf("%02d", substr(date("Y"), -2));
                                                $LastestYear = sprintf("%02d", substr(date("Y"), -2));
                                                $EarlistYear = $CurrentYear - 10;

                                                       // Loops over each int[year] from current year, back to the $earliest_year [1950]
                                                foreach ( range( $LastestYear, $EarlistYear ) as $i ) {
                                                      $i = sprintf("%02d", substr($i, -2)) . (sprintf("%02d", (substr($i, -2)+1)));

                                                      if(isset($_REQUEST["filter_ProjectYear"]) && $_REQUEST["filter_ProjectYear"] == $i){
                                                            echo "<option selected value='".$i."'>".$i."</option>";
                                                      }else{
                                                            echo "<option value='".$i."'>".$i."</option>";
                                                      }
                                                }
                                                ?>
                                          </select>

                                    </td>
                              </tr>
                        </table>
                  </form>
                  <br/>
                  <h4><u>1) Before Allocation</u></h4>
                  <div class="table-responsive">
                     <?php
                          $maxColumn = getMaxColumnCount();
                          $width = ($maxColumn * 65) + 120;
                          echo '<table border=1 width="' . $width . 'px">'
                    ?>
                          <tr class="bg-dark text-white text-center" >
                                <td width="10px"  style="padding: 7px;">No.</td>
                                <td width="100px">Staff Name</td>
                                <td width="10px" style="padding: 7px;">EXE</td>
                                <?php
                                      $maxColumn = getMaxColumnCount();
                                      $width = ($maxColumn * 65);
                                      echo '<td ';
                                      echo 'width ="' . $width . 'px">Projects</td>';
                                ?>
                          </tr>
                          <?php
                                $exemptionCount = 0;
                                $staffProjectCount = 0;
                                $rowcount = 1;
                                //to cater situation when there is only 2 row
                                if(count($projects) == 2){
                                      $count = 0;
                                }
                                else{
                                      $count = 1;
                                }

                                $previousRecord;
                                $details = "";
                                foreach($projects as $value){
                                      if(is_null($value['examinerid'])){
                                            $count++;
                                      }
                                      else{
                                      //if(!is_null($value['examinerid'])){
                                            if($rowcount > 1){
                                            // when the staffid is the same as the previous record
                                            if(strcmp($previousRecord, $value['staff_id']) == 0){
                                                  $details = "Supervisor : ". $value['staff_name'] .
                                                  "\n Title : " . $value['project_name'] .
                                                  "\n Student : " . $value["student_name"] .
                                                  "\n Examiner: ". $value['examiner_name'];
                                                  echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'">
                                                      <form method="post" action="allocation_edit.php">
                                                          <input type="submit" class="EditAllocationtBtn" name="allocate_edit_project_id" id="' . $value['project_id'] . '" value="' .  $value['project_id'] .'">
                                                      </form>';
                                                  $previousRecord = $value['staff_id'];
                                                  $count++;
                                                  $staffProjectCount++;
                                            }
                                            if(strcmp($previousRecord, $value['staff_id']) != 0){
                                                  $details = "Supervisor : ". $value['staff_name'] .
                                                  "\n Title : " . $value['project_name'] .
                                                  "\n Student : " . $value["student_name"] .
                                                  "\n Examiner: ". $value['examiner_name'];
                                                 // to cater the first row
                                                  foreach($projectsCount as $countprojects){
                                                        if(strcmp($countprojects['staff_id'], $previousRecord) == 0){
                                                              $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                                              if($exemptionCount > 30){ //restriction to max 30
                                                                    $exemptionCount = 30;
                                                              }
                                                              for($i = 1; $i <= $exemptionCount; $i++){
                                                                    echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                              }
                                                        }

                                                  }



                                                  echo '</tr>';
                                                  echo '</table>';
                                                  echo '</td>';
                                                  echo '</tr>';
                                                  echo '<tr>';
                                                  echo '<td width="10px" style="padding: 7px;">' . $rowcount . '</td>';
                                                  echo '<td width="100px" style="padding: 7px;">' . $value['staff_name']. '</td>';
                                                  foreach($projectsCount as $countprojects){
                                                        if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                                                               echo '<td width="10px" style="padding: 7px;">' . ($countprojects['no_of_exemption'] - $countprojects['project_count']) .'</td>';
                                                        }
                                                  }
                                                  echo '<td>';
                                                  echo '<table border=1>';
                                                  echo '<tr>';
                                                  echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'">
                                                      <form method="post" action="allocation_edit.php">
                                                          <input type="submit" class="EditAllocationtBtn" name="allocate_edit_project_id" id="' . $value['project_id'] . '" value="' .  $value['project_id'] .'">
                                                      </form>';

                                                  if($rowcount == count($supervisingProjectsCount)){
                                                         foreach($projectsCount as $countprojects){
                                                              if(strcmp($countprojects['staff_id'], $value['staff_id']) == 0){
                                                                    $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                                              }
                                                        }

                                                        for($i = 1; $i <= $exemptionCount; $i++){
                                                                    echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                        }
                                                        echo '</tr>';
                                                        echo '</table>';
                                                        echo '</td>';
                                                  }
                                                  $previousRecord = $value['staff_id'];
                                                  $rowcount++;
                                                  $count++;
                                                  $staffProjectCount = 0;
                                            }



                                      }


                                      elseif($rowcount == 1){
                                             $details = "Supervisor : ". $value['staff_name'] . "\n Title : " . $value['project_name'] .
                                             "\n Student : " . $value["student_name"] .
                                             "\n Examiner: ". $value['examiner_name'];
                                            echo '<tr>';
                                            echo '<td width="10px" style="padding: 7px;">' . $rowcount . '</td>';
                                            echo '<td width="100px style="padding: 7px;">' . $value['staff_name']. '</td>';
                                            foreach($projectsCount as $countprojects){
                                                  if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                                                         echo '<td width="10px" style="padding: 7px;">' . ($countprojects['no_of_exemption'] - $countprojects['project_count']) . '</td>';
                                                  }
                                            }

                                            echo '<td>';
                                            echo '<table border=1>';
                                            echo '<tr>';
                                            echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'">
                                                <form method="post" action="allocation_edit.php">
                                                    <input type="submit" class="EditAllocationtBtn" name="allocate_edit_project_id" id="' . $value['project_id'] . '" value="' .  $value['project_id'] .'">
                                                </form>';
                                            //echo '</tr>';
                                            $rowcount++;
                                            $count++;
                                            $staffProjectCount++;
                                            $previousRecord = $value['staff_id'];

                                            if($rowcount == count($supervisingProjectsCount)){
                                                         foreach($projectsCount as $countprojects){
                                                              if(strcmp($countprojects['staff_id'], $value['staff_id']) == 0){
                                                                    $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                                              }
                                                        }

                                                        for($i = 1; $i <= $exemptionCount; $i++){
                                                                    echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                        }
                                                        echo '</tr>';
                                                        echo '</table>';
                                                        echo '</td>';
                                                  }
                                      }
                                      }

                                }


                          ?>

                    </table>
                  </div>
                  <br/>
                  <h4><u>2) After Allocation</u></h4>
                  <div class="table-responsive">
                    <?php
                          $maxColumn = getMaxColumnCount();
                          $width = ($maxColumn * 65) + 120;
                          echo '<table border=1 width="' . $width . 'px">'
                    ?>
                          <tr class="bg-dark text-white text-center" >
                                <td width="10px" style="padding: 7px;">No.</td>
                                <td width="100px">Staff Name</td>
                                <td width="10px" style="padding: 7px;">EXE</td>
                                <?php
                                      $maxColumn = getMaxColumnCount();
                                      $width = ($maxColumn * 65);
                                      echo '<td ';
                                      echo 'width ="' . $width . 'px">Projects</td>';
                                ?>
                          </tr>
                          <?php
                                $rowcount = 1;
                                $exemptionCount = 0;
                                $staffProjectCount = 0;
                                $count = 0;
                                $previousRecord;
                                $details = "";
                                $exemptionList = array();
                                foreach($projects as $value){
                                      if((!is_null($value['examinerid'])) && (is_null($value['supervisor_name']))){
                                            if($rowcount > 1){
                                                  // when the staffid is the same as the previous record
                                                  if(strcmp($previousRecord, $value['staff_id']) == 0){
                                                        $details = "Supervisor : ". $value['staff_name'] . "\n Title : " . $value['project_name'] .
                                                        "\n Student : " . $value["student_name"] .
                                                        "\n Examiner: ". $value['examiner_name'];
                                                        echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'">
                                                            <form method="post" action="allocation_edit.php">
                                                                <input type="submit" class="EditAllocationtBtn" name="allocate_edit_project_id" id="' . $value['project_id'] . '" value="' .  $value['project_id'] .'">
                                                            </form>';
                                                        $previousRecord = $value['staff_id'];
                                                        $count++;
                                                        $staffProjectCount++;
                                                  }
                                                  if(strcmp($previousRecord, $value['staff_id']) != 0){
                                                        if(in_array($previousRecord, $exemptionList) == false){

                                                              foreach($projectsCount as $countprojects){

                                                                          if(strcmp($previousRecord, $countprojects['staff_id']) == 0){
                                                                                if($countprojects['examining_project'] == 0){
                                                                                      $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                                                                      if($exemptionCount > 30){ //restriction to max 30
                                                                                      $exemptionCount = 30;
                                                                                      }
                                                                                      for($i = 1; $i <= $exemptionCount; $i++){
                                                                                      echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                                                      }
                                                                                      $exemptionList[$rowcount] = $previousRecord;
                                                                                }
                                                                          }
                                                              }

                                                        }
                                                        echo '</tr>';
                                                        echo '</table>';
                                                        echo '</td>';
                                                        echo '</tr>';
                                                        $details = "Supervisor : ". $value['staff_name'] .
                                                        "\n Title : " . $value['project_name'] .
                                                        "\n Student : " . $value["student_name"] .
                                                        "\n Examiner: ". $value['examiner_name'];
                                                        echo '<tr>';
                                                        echo '<td width="10px" style="padding: 7px;">' . $rowcount . '</td>';
                                                        echo '<td width="100px" style="padding: 7px;">' . $value['staff_name']. '</td>';
                                                        foreach($projectsCount as $countprojects){
                                                              if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                                                                     echo '<td width="10px" style="padding: 7px;">' . ($countprojects['no_of_exemption'] - $countprojects['project_count']) . '</td>';
                                                              }
                                                        }
                                                        echo '<td>';
                                                        echo '<table border=1>';
                                                        echo '<tr>';
                                                        echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'">
                                                            <form method="post" action="allocation_edit.php">
                                                                <input type="submit" class="EditAllocationtBtn" name="allocate_edit_project_id" id="' . $value['project_id'] . '" value="' .  $value['project_id'] .'">
                                                            </form>';
                                                        '</td>';
                                                        $previousRecord = $value['staff_id'];
                                                        $rowcount++;
                                                        $count++;
                                                        $staffProjectCount = 0;
                                                  }

                                                  // to close off the last row
                                                  if($count == count($projects)){
                                                        foreach($projectsCount as $countprojects){
                                                              if(strcmp($countprojects['staff_id'], $value['staff_id']) == 0){
                                                                    $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                                              }
                                                        }
                                                        if($exemptionCount > 30){ //restriction to max 30
                                                                    $exemptionCount = 30;
                                                             }
                                                              for($i = 1; $i <= $exemptionCount; $i++){
                                                                    echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                        }
                                                        echo '</tr>';
                                                        echo '</table>';
                                                        echo '</td>';
                                                  }

                                            }
                                            elseif($rowcount == 1){
                                                   $details = "Supervisor : ". $value['staff_name'] . "\n Title : " . $value['project_name'] .
                                                   "\n Student : " . $value["student_name"] .
                                                   "\n Examiner: ". $value['examiner_name'];
                                                  echo '<tr>';
                                                  echo '<td width="10px" style="padding: 7px;">' . $rowcount . '</td>';
                                                  echo '<td width="100px style="padding: 7px;">' . $value['staff_name']. '</td>';
                                                  foreach($projectsCount as $countprojects){
                                                        if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                                                              echo '<td width="10px" style="padding: 7px;">' . ($countprojects['no_of_exemption'] - $countprojects['project_count']) . '</td>';
                                                        }
                                                  }

                                                  echo '<td>';
                                                  echo '<table border=1>';
                                                  echo '<tr>';
                                                  echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'">
                                                        <form method="post" action="allocation_edit.php">
                                                            <input type="submit" class="EditAllocationtBtn" name="allocate_edit_project_id" id="' . $value['project_id'] . '" value="' .  $value['project_id'] .'">
                                                        </form>';
                                                  //echo '</tr>';
                                                  $rowcount++;
                                                  $count++;
                                                  $staffProjectCount++;
                                                  $previousRecord = $value['staff_id'];

                                                  if($count == count($projects)){
                                                        $exemptionCount =  $projectsCount[$rowcount-2]['no_of_exemption'] - $projectsCount[$rowcount-2]['project_count'];
                                                        if($exemptionCount > 30){ //restriction to max 30
                                                              $exemptionCount = 30;
                                                        }

                                                        for($i = 1; $i <= $exemptionCount; $i++){
                                                                    echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                        }
                                                        echo '</tr>';
                                                        echo '</table>';
                                                        echo '</td>';
                                                  }
                                            }
                                      }
                                      elseif(is_null($value['examinerid']) && !(is_null($value['supervisor_name']))){

                                            if($rowcount > 1){

                                            // when the staffid is the same as the previous record
                                            if(strcmp($previousRecord, $value['staff_id']) == 0){
                                                  if(in_array($value['staff_id'], $exemptionList) == false){

                                                  foreach($projectsCount as $countprojects){
                                                              if(strcmp($value['staff_id'], $countprojects['staff_id']) == 0){
                                                                     $exemptionCount = ($countprojects['no_of_exemption'] - $countprojects['project_count']);
                                                                     if($exemptionCount > 30){ //restriction to max 30
                                                                          $exemptionCount = 30;
                                                                    }

                                                                    for($i = 1; $i <= $exemptionCount; $i++){
                                                                          echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                                    }
                                                              }
                                                        }

                                                        $exemptionList[$rowcount] = $value['staff_id'];

                                                  }
                                                  $details = " Project id: " . $value['project_id'] .
                                                  "\n Supervisor : ". $value['supervisor_name'] .
                                                  "\n Title : " . $value['project_name'] .
                                                  "\n Student : " . $value["student_name"] .
                                                  "\n Examiner: ". $value['staff_name'];
                                                  echo '<td width="65px" style="padding: 2px;" title="' .$details .'">
                                                      <form method="post" action="allocation_edit.php">
                                                          <input type="submit" class="EditAllocationtBtn" name="allocate_edit_project_id" id="' . $value['project_id'] . '" value="' .  $value['project_id'] .'">
                                                      </form>';
                                                  $previousRecord = $value['staff_id'];
                                                  $count++;
                                                  $staffProjectCount++;
                                            }

                                            if(strcmp($previousRecord, $value['staff_id']) != 0){
                                                  if(in_array($previousRecord, $exemptionList) == false){

                                                              foreach($projectsCount as $countprojects){

                                                                          if(strcmp($previousRecord, $countprojects['staff_id']) == 0){
                                                                                if($countprojects['examining_project'] == 0){
                                                                                      $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                                                                      if($exemptionCount > 30){ //restriction to max 30
                                                                                      $exemptionCount = 30;
                                                                                      }
                                                                                      for($i = 1; $i <= $exemptionCount; $i++){
                                                                                      echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                                                      }
                                                                                      $exemptionList[$rowcount] = $previousRecord;
                                                                                }
                                                                          }
                                                              }

                                                        }


                                                  if($rowcount >=2){

                                                        echo '</tr>';
                                                        echo '</table>';
                                                        echo '</td>';
                                                        echo '</tr>';
                                                        echo '<tr>';
                                                        echo '<td width="10px" style="padding: 7px;">' . $rowcount . '</td>';
                                                        echo '<td width="100px" style="padding: 7px;">' . $value['staff_name']. '</td>';
                                                        foreach($projectsCount as $countprojects){
                                                              if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                                                                     echo '<td width="10px" style="padding: 7px;">' . ($countprojects['no_of_exemption'] - $countprojects['project_count']) . '</td>';
                                                              }
                                                        }
                                                        $details = "Project id: " . $value['project_id'] .
                                                        "\n Supervisor : ". $value['supervisor_name'] .
                                                        "\n Title : " . $value['project_name'] .
                                                        "\n Student : " . $value["student_name"] .
                                                        "\n Examiner: ". $value['staff_name'];
                                                        echo '<td>';
                                                        echo '<table border=1>';
                                                        echo '<tr>';
                                                        if(in_array($value['staff_id'], $exemptionList) == false){

                                                              foreach($projectsCount as $countprojects){
                                                                    if(strcmp($value['staff_id'], $countprojects['staff_id']) == 0){
                                                                           $exemptionCount = ($countprojects['no_of_exemption'] - $countprojects['project_count']);
                                                                           if($exemptionCount > 30){ //restriction to max 30
                                                                                $exemptionCount = 30;
                                                                          }

                                                                          for($i = 1; $i <= $exemptionCount; $i++){
                                                                                echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                                          }
                                                                    }
                                                              }

                                                              $exemptionList[$rowcount] = $value['staff_id'];

                                                        }
                                                       echo '<td width="65px" style="padding: 2px;" title="' .$details .'">
                                                           <form method="post" action="allocation_edit.php">
                                                               <input type="submit" class="EditAllocationtBtn" name="allocate_edit_project_id" id="' . $value['project_id'] . '" value="' .  $value['project_id'] .'">
                                                           </form>';

                                                  }


                                                  $previousRecord = $value['staff_id'];
                                                  $rowcount++;
                                                  $count++;
                                                  $staffProjectCount = 0;
                                            }

                                            if($count == count($projects)){

                                                  foreach($projectsCount as $countprojects){
                                                        if(strcmp($countprojects['staff_id'], $previousRecord) == 0){
                                                              $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                                        }
                                                  }

                                                  /* if($exemptionCount > 30){ //restriction to max 30
                                                        $exemptionCount = 30;
                                                        }

                                                  for($i = 1; $i <= $exemptionCount; $i++){
                                                        echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                  }*/
                                                  echo '</tr>';
                                                  echo '</table>';
                                                  echo '</td>';
                                                  }


                                            }

                                            elseif($rowcount == 1){

                                                   $details = "Project id: " . $value['project_id'] .
                                                   "\n Supervisor : ". $value['supervisor_name'] .
                                                   "\n Title : " . $value['project_name'] .
                                                   "\n Student : " . $value["student_name"] .
                                                   "\n Examiner: ". $value['staff_name'];
                                                  echo '<tr>';
                                                  echo '<td width="10px" style="padding: 7px;">' . $rowcount . '</td>';
                                                  echo '<td width="100px style="padding: 7px;">' . $value['staff_name']. '</td>';
                                                  echo '<td width="10px" style="padding: 7px;">' . ($projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count']) . '</td>';
                                                  echo '<td>';
                                                  echo '<table border=1>';
                                                  echo '<tr>';
                                                  if(in_array($value['staff_id'], $exemptionList) == false){

                                                              foreach($projectsCount as $countprojects){
                                                                    if(strcmp($value['staff_id'], $countprojects['staff_id']) == 0){
                                                                           $exemptionCount = ($countprojects['no_of_exemption'] - $countprojects['project_count']);
                                                                           if($exemptionCount > 30){ //restriction to max 30
                                                                                $exemptionCount = 30;
                                                                          }

                                                                          for($i = 1; $i <= $exemptionCount; $i++){
                                                                                echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                                          }
                                                                    }
                                                              }

                                                              $exemptionList[$rowcount] = $value['staff_id'];

                                                  }
                                                  echo '<td width="65px" style="padding: 2px;" title="' .$details .'">
                                                      <form method="post" action="allocation_edit.php">
                                                          <input type="submit" class="EditAllocationtBtn" name="allocate_edit_project_id" id="' . $value['project_id'] . '" value="' .  $value['project_id'] .'">
                                                      </form>';
                                                  //echo '</tr>';
                                                  $rowcount++;
                                                  $count++;
                                                  $staffProjectCount++;
                                                  $previousRecord = $value['staff_id'];

                                                  if($count == count($projects)){
                                                        echo '</tr>';
                                                        echo '</table>';
                                                        echo '</td>';
                                                  }
                                            }
                                      }

                                }


                          ?>

                    </table>
                  </div>
                  <br/>

                        <div style="float:left;">

                            <form method="post" action="submit_download_result_visualization.php">
                                <input type="hidden" id="sub_filter_ProjectSem" name="filter_ProjectSem" value="<?php echo $filter_ProjectSem ?>">
                                <input type="hidden" id="sub_filter_ProjectYear" name="filter_ProjectYear" value="<?php echo $filter_ProjectYear ?>">
                                <input type="submit" value="Download Visualization" class="btn bg-dark text-white text-center" title="Download Results Visualization">
                            </form>

                        </div>
                        <br/><br/><br/>

            </div>
            <!-- closing navigation div in nav.php -->
            </div>

        </div>
    </div>


      <?php require_once('../../../footer.php'); ?>
</body>

</html>
