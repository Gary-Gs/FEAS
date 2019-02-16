<?php require_once('../../../Connections/db_ntu.php');
	  require_once('./entity.php'); 
	  require_once('../../../CSRFProtection.php');
	  require_once('../../../Utility.php');?>
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

      if($filter_ProjectSem == 1){
          $query_rsProject    = "SELECT p.project_id as project_id, student.name as student_name, p1.title as project_name, s.name as staff_name, s.id as staff_id, s.exemption as no_of_exemption, r.examiner_id as examinerid, examiner_info.name as examiner_name,  r.day as day, r.slot as slot, r.room as room 
            FROM " . $TABLES['fyp_assign'] . " as p 
            JOIN ". $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id 
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id 
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id 
            LEFT JOIN ". $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id 
            LEFT JOIN  " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id 
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?) ORDER BY s.id";  

            $query_rsProjectCount = "SELECT s.id as staff_id, COUNT(p.project_id) as project_count, s.exemption as no_of_exemption 
                  FROM " . $TABLES['fyp_assign'] . " as p 
                  JOIN " .$TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
                  JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id 
                  LEFT JOIN ". $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id 
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?) GROUP BY s.id ORDER BY s.id"; 
      }
      else{
             $query_rsProject    = "SELECT p.project_id as project_id, student.name as student_name, p1.title as project_name, s.name as staff_name, s.id as staff_id, s.exemptionS2 as no_of_exemption, r.examiner_id as examinerid, examiner_info.name as examiner_name,  r.day as day, r.slot as slot, r.room as room 
            FROM " . $TABLES['fyp_assign'] . " as p 
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id
            JOIN ". $TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id 
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id 
            LEFT JOIN ". $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id 
            LEFT JOIN  " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id 
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?) ORDER BY s.id";   

            $query_rsProjectCount = "SELECT s.id as staff_id, COUNT(p.project_id) as project_count, s.exemptionS2 as no_of_exemption 
                  FROM " . $TABLES['fyp_assign'] . " as p 
                  JOIN " .$TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
                  JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id 
                  LEFT JOIN ". $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id 
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?) GROUP BY s.id ORDER BY s.id"; 

            /* $query_rsProject  = "SELECT p.project_id as project_id, student.name as student_name, p1.title as project_name, s.name as staff_name, s.id as staff_id, r.examiner_id as examinerid, examiner_info.name as examiner_name,  r.day as day, r.slot as slot, r.room as room FROM " . $TABLES['fyp_assign'] . " as p 
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id 
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id 
            LEFT JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id 
            LEFT JOIN ". $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id 
            LEFT JOIN  " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id 
            WHERE (p.sem LIKE ? AND p.year LIKE ? AND s.id in ('adamskong', 'anupam')) ORDER BY s.id"; */
      }     
	

      $query_rsProjectExamining     = "SELECT p.project_id as project_id, student.name as student_name, p1.title as project_name, s.name as staff_name, s.id as staff_id, r.examiner_id as examinerid, examiner_info.name as examiner_name,  r.day as day, r.slot as slot, r.room as room 
            FROM " . $TABLES['fyp_assign'] . " as p 
            JOIN " .$TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id 
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id 
            JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id 
            JOIN ". $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id 
            JOIN  " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id 
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?) ORDER BY examiner_info.id";


      $query_rsProjectExaminingCount     = "SELECT r.examiner_id as examinerid, COUNT(p.project_id) as project_count
      FROM " . $TABLES['fyp_assign'] . " as p 
      JOIN " .$TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
      JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id 
      JOIN ". $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id 
      JOIN  " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id 
      WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?) 
      GROUP BY r.examiner_id
      ORDER BY examiner_info.id";


	try
	{
            //Get before allocation data
            $stmt_0 = $conn_db_ntu->prepare($query_rsProject);
            $stmt_0->bindParam(1, $filter_ProjectSem); //Search project sem
            $stmt_0->bindParam(2, $filter_ProjectYear); //Search project year
            $stmt_0->execute();
		$projects = $stmt_0->fetchAll(PDO::FETCH_ASSOC);

            //Get before allocation project count
            $stmt_1 = $conn_db_ntu->prepare($query_rsProjectCount);
            $stmt_1->bindParam(1, $filter_ProjectSem); //Search project sem
            $stmt_1->bindParam(2, $filter_ProjectYear); //Search project year
            $stmt_1->execute();
            $projectsCount = $stmt_1->fetchAll(PDO::FETCH_ASSOC);
            $totalRowCount = count($projectsCount);

            //Get after allocation examining project 
            $stmt_2 = $conn_db_ntu->prepare($query_rsProjectExamining);
            $stmt_2->bindParam(1, $filter_ProjectSem); //Search project sem
            $stmt_2->bindParam(2, $filter_ProjectYear); //Search project year
            $stmt_2->execute();
            $examiningProjects = $stmt_2->fetchAll(PDO::FETCH_ASSOC);


            //Get after examining project count
            $stmt_3 = $conn_db_ntu->prepare($query_rsProjectExaminingCount);
            $stmt_3->bindParam(1, $filter_ProjectSem); //Search project sem
            $stmt_3->bindParam(2, $filter_ProjectYear); //Search project year
            $stmt_3->execute();
            $examiningProjectsCount = $stmt_3->fetchAll(PDO::FETCH_ASSOC);

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

      function getMaxColumnCountExamining(){
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
                                   if(($no_of_exemption + $value['project_count'] + $value1['project_count']) > $max){
                                          $max = ($no_of_exemption  + $value['project_count'] + $value1['project_count'] + 2);
                                    }      
                              }
                        }
                  }
                  else{
                        if(($no_of_exemption + $value['project_count']) > $max){
                        $max = ($no_of_exemption  + $value['project_count'] + 2);
                        }
                        
                  }
            }
                  
        return $max;
      }

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
                              $count = 0;
                              $previousRecord;
                              $details = "";
                              foreach($projects as $value){
                                    if($rowcount > 1){
                                          // when the staffid is the same as the previous record
                                          if(strcmp($previousRecord, $value['staff_id']) == 0){
                                                $details = "Supervisor : ". $value['staff_name'] . "\n Title : " . $value['project_name'] .
                                                "\n Student : " . $value["student_name"] .
                                                "\n Examiner: ". $value['examiner_name'];
                                                echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'"><a href="allocation_edit.php?project='. $value['project_id'].'">' . $value['project_id']. '</a></td>';
                                                $previousRecord = $value['staff_id'];
                                                $count++;
                                                $staffProjectCount++;
                                          }
                                          if(strcmp($previousRecord, $value['staff_id']) != 0){
                                                $details = "Supervisor : ". $value['staff_name'] . "\n Title : " . $value['project_name'] .
                                           "\n Student : " . $value["student_name"] .
                                                "\n Examiner: ". $value['examiner_name'];
                                               // to cater the first row
                                                if($rowcount == 2){
                                                     $exemptionCount = $projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count'];
                                                     if($exemptionCount > 30){ //restriction to max 30
                                                            $exemptionCount = 30;
                                                     }
                                                      for($i = 1; $i <= $exemptionCount; $i++){
                                                            echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                      }                                        
                                                                                 
                                                }
                                                elseif($rowcount >=3){
                                                    $exemptionCount =  $projectsCount[$rowcount-2]['no_of_exemption'] - $projectsCount[$rowcount-2]['project_count'];

                                                     if($exemptionCount > 30){ //restriction to max 30
                                                            $exemptionCount = 30;
                                                     }
                                                      for($i = 1; $i <= $exemptionCount; $i++){
                                                            echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                      }                          
                                                }
                                                echo '</tr>';
                                                echo '</table>';
                                                echo '</td>';
                                                echo '</tr>';
                                                echo '<tr>';
                                                echo '<td width="10px" style="padding: 7px;">' . $rowcount . '</td>';
                                                echo '<td width="100px" style="padding: 7px;">' . $value['staff_name']. '</td>';
                                                echo '<td wwidth="10px" style="padding: 7px;">' . ($projectsCount[$rowcount-1]['no_of_exemption'] - $projectsCount[$rowcount-1]['project_count']) . '</td>';
                                                echo '<td>';
                                                echo '<table border=1>';
                                                echo '<tr>';
                                                echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'"><a href="allocation_edit.php?project='. $value['project_id'].'">' . $value['project_id']. '</a></td>';
                                                $previousRecord = $value['staff_id'];
                                                $rowcount++;
                                                $count++;
                                                $staffProjectCount = 0;
                                          }

                                          // to close off the last row
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
                                    
                                    elseif($rowcount == 1){
                                           $details = "Supervisor : ". $value['staff_name'] . "\n Title : " . $value['project_name'] .
                                           "\n Student : " . $value["student_name"] .
                                                "\n Examiner: ". $value['examiner_name'];
                                          echo '<tr>';
                                          echo '<td width="10px" style="padding: 7px;">' . $rowcount . '</td>';
                                          echo '<td width="100px style="padding: 7px;">' . $value['staff_name']. '</td>';
                                          echo '<td width="10px" style="padding: 7px;">' . ($projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count']) . '</td>';
                                          echo '<td>';
                                          echo '<table border=1>';
                                          echo '<tr>';
                                          echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'"><a href="allocation_edit.php?project='. $value['project_id'].'">' . $value['project_id']. '</a></td>';
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

                                            
                        ?>

                  </table>
                  <br/>

                  <h4><u>2) After Allocation</u></h4>
                  <?php 
                        $maxColumn = getMaxColumnCountExamining();
                        $width = ($maxColumn * 65) + 120;
                        echo '<table border=1 width="' . $width . 'px">'
                  ?>
                        <tr class="bg-dark text-white text-center" >
                              <td width="10px" style="padding: 7px;">No.</td>
                              <td width="100px">Staff Name</td>   
                              <td width="10px" style="padding: 7px;">EXE</td>
                              <?php 
                                    $maxColumn = getMaxColumnCountExamining();
                                    $width = ($maxColumn * 65);
                                    echo '<td ';
                                    echo 'width ="' . $width . 'px">Projects</td>';
                              ?>
                        </tr>
                        <?php 
                              $exemptionCount = 0;
                              $staffProjectCount = 0;
                              $rowcount = 1;
                              $count = 0;
                              $previousRecord;
                                    $details = "";
                              foreach($projects as $value){
                                    if($rowcount > 1){
                                          // when the staffid is the same as the previous record
                                          if(strcmp($previousRecord, $value['staff_id']) == 0){
                                                $details = "Supervisor : ". $value['staff_name'] . "\n Title : " . $value['project_name'] .
                                                "\n Student : " . $value["student_name"] .
                                                "\n Examiner: ". $value['examiner_name'];
                                                echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'"><a href="allocation_edit.php?project='. $value['project_id'].'">' . $value['project_id']. '</a></td>';
                                                $previousRecord = $value['staff_id'];
                                                $count++;
                                                $staffProjectCount++;
                                          }
                                          if(strcmp($previousRecord, $value['staff_id']) != 0){
                                                $details = "Supervisor : ". $value['staff_name'] . "\n Title : " . $value['project_name'] .
                                           "\n Student : " . $value["student_name"] .
                                                "\n Examiner: ". $value['examiner_name'];
                                                // to cater the first row
                                                if($rowcount == 2){

                                                     $exemptionCount = $projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count'];
                                                     if($exemptionCount > 30){ //restriction to max 30
                                                            $exemptionCount = 30;
                                                     }
                                                      for($i = 1; $i <= $exemptionCount; $i++){
                                                            echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                      } 
                                                      foreach($examiningProjects as $examiner){
                                                            $details = "Supervisor : ". $examiner['staff_name'] . "\n Title : " . $examiner['project_name'] . "\n Student : " . $value["student_name"] . "\n Examiner: ". $examiner['examiner_name'];

                                                            if(strcmp($examiner['examinerid'], $previousRecord) == 0){
                                                                  echo '<td width="65px" style="padding: 2px;" title="' .$details .'"><a href="allocation_edit.php?project='. $examiner['project_id'].'">' . $examiner['project_id'].'</a></td>';
                                                            }
                                                      }
                                                     
                                                }
                                                elseif($rowcount >=3){
                                                      
                                                      $exemptionCount =  $projectsCount[$rowcount-2]['no_of_exemption'] - $projectsCount[$rowcount-2]['project_count'];

                                                     if($exemptionCount > 30){ //restriction to max 30
                                                            $exemptionCount = 30;
                                                     }
                                                      for($i = 1; $i <= $exemptionCount; $i++){
                                                            echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                      }

                                                      foreach($examiningProjects as $examiner){
                                                            $details = "Supervisor : ". $examiner['staff_name'] . "\n Title : " . $examiner['project_name'] . "\n Student : " . $value["student_name"] . "\n Examiner: ". $examiner['examiner_name'];
                                                            if(strcmp($examiner['examinerid'], $previousRecord) == 0){
                                                                  echo '<td width="65px" style="padding: 2px;" title="' .$details .'"><a href="allocation_edit.php?project='. $examiner['project_id'].'">' . $examiner['project_id'].'</a></td>';
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
                                                echo '<td wwidth="10px" style="padding: 7px;">' . ($projectsCount[$rowcount-1]['no_of_exemption'] - $projectsCount[$rowcount-1]['project_count']) . '</td>';
                                                echo '<td>';
                                                echo '<table border=1>';
                                                echo '<tr>';
                                                echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'"><a href="allocation_edit.php?project='. $value['project_id'].'">' . $value['project_id']. '</a></td>';
                                                $previousRecord = $value['staff_id'];
                                                $rowcount++;
                                                $count++;
                                                $staffProjectCount = 0;
                                          }

                                          if($count == count($projects)){
                                                
                                                $exemptionCount =  $projectsCount[$rowcount-2]['no_of_exemption'] - $projectsCount[$rowcount-2]['project_count'];

                                                 if($exemptionCount > 30){ //restriction to max 30
                                                      $exemptionCount = 30;
                                                      }

                                                for($i = 1; $i <= $exemptionCount; $i++){
                                                      echo '<td width="65px" bgcolor="yellow">EXE</td>';
                                                }

                                                foreach($examiningProjects as $examiner){
                                                      $details = "Supervisor : ". $examiner['staff_name'] . "\n Title : " . $examiner['project_name'] . "\n Student : " . $value["student_name"] . "\n Examiner: ". $examiner['examiner_name'];
                                                      if(strcmp($examiner['examinerid'], $previousRecord) == 0){
                                                            echo '<td width="65px" style="padding: 2px;" title="' .$details .'"><a href="allocation_edit.php?project='. $examiner['project_id'].'">' . $examiner['project_id'].'</a></td>';
                                                      }
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
                                          echo '<td width="10px" style="padding: 7px;">' . ($projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count']) . '</td>';
                                          echo '<td>';
                                          echo '<table border=1>';
                                          echo '<tr>';
                                          echo '<td width="65px" bgcolor="limegreen" style="padding: 2px;" title="' .$details .'"><a href="allocation_edit.php?project='. $value['project_id'].'">' . $value['project_id']. '</a></td>';
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
                        ?>

                  </table>
                  <br/>
                  
                        <div style="float:left;">
                              <?php
                                    $urlLink = 'submit_download_result_visualization.php?filter_ProjectSem='.$filter_ProjectSem. '&filter_ProjectYear='.$filter_ProjectYear;

                                    echo '<a href=' . $urlLink . ' class="btn bg-dark text-white text-center" title="Download Results Visualization">Download Visualization</a>';
                                    
                              ?>

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