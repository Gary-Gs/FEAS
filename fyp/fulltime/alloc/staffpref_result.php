<?php
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');

$localHostDomain = 'http://localhost';
$ServerDomainHTTP = 'http://155.69.100.32';
$ServerDomainHTTPS = 'https://155.69.100.32';
$ServerDomain = 'https://fypExam.scse.ntu.edu.sg';
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

// initialize months array
$sem1Array = array("Jul", "Aug","Sep","Oct","Nov","Dec");
$sem2Array = array("Jan","Feb","Mar", "Apr", "May", "Jun");

$currentYrSem1 = date("y") . (date("y") + 1);
$currentYrSem2 = (date("y")-1) . date("y");

// Get current year and semester
if (in_array(date("M"), $sem1Array)) {
  $sem = 1;
  $currentYrSem = "Yr " . $currentYrSem1 . " Sem 1";
}
else {
  $sem = 2;
  $currentYrSem = "Yr " . $currentYrSem2 . " Sem 2";
}

/* Select current open period that is saved in DB
$query_rsSettings = "SELECT * FROM ".$TABLES['allocation_settings_others']." WHERE type= 'FT'";
$settings 	= $conn_db_ntu->query($query_rsSettings)->fetch();
//$start 		 	= DateTime::createFromFormat('Y-m-d', $settings['pref_start']);
//$end 			= DateTime::createFromFormat('Y-m-d', $settings['pref_end']);
$month = date("M",strtotime($settings['pref_start']));

// Get the month of the open period
if (in_array($month, $sem1Array) && in_array($month, $sem1Array)) {
    $sem = 1;
}
if (in_array($month, $sem2Array) && in_array($month, $sem2Array)) {
    $sem = 2;
}
*/

// initialize array for filter
$CurrentYear = sprintf("%02d", substr(date("Y"), -2));
//$LastestYear = sprintf("%02d", substr(date("Y")-1, -2));
if ($sem == 1) {
  $LastestYear = sprintf("%02d", substr(date("Y"), -2));
}
else
  $LastestYear = sprintf("%02d", substr(date("Y")-1, -2));

$EarlistYear = $CurrentYear - 5;
$filterArray = array();

foreach (range($LastestYear, $EarlistYear) as $i) {
  $l = $i + 1;

  if ($i == $LastestYear) {
    if ($sem == 1) {
      array_push($filterArray, "Yr " . $i . $l . " Sem 1");
    }
    else {
      array_push($filterArray, "Yr " . $i . $l . " Sem 2");
      array_push($filterArray, "Yr " . $i . $l . " Sem 1");
    }
  }
  else {
    array_push($filterArray, "Yr " . $i . $l . " Sem 2");
    array_push($filterArray, "Yr " . $i . $l . " Sem 1");
  }
}

$searchArray = array();

if (!isset($_POST['filterProject_To']) || $_POST["filterProject_To"] == $currentYrSem) {
   /*$query_rsProjectPreference		= "SELECT prefer as project_id, COUNT(DISTINCT staff_id) as total FROM " . $TABLES['staff_pref'] .
                                  " WHERE (archive = 0 || archive IS NULL) AND prefer LIKE 'SC%' " .
                                  "GROUP BY prefer " .
                                  "ORDER BY prefer ASC";
*/

/*
   $query_rsProjectPreference		= "SELECT project.project_id, project.title, count(DISTINCT p4.staff_id) as total FROM " .
                                  "(SELECT DISTINCT(p1.project_id), p2.title FROM " . $TABLES["fea_projects"] . " as p1 " .
                                  " LEFT JOIN " . $TABLES['fyp'] . " as p2 " .
                                  " ON p1.project_id = p2.project_id ".
                                  " LEFT JOIN " . $TABLES['allocation_settings_others'] . " as p3 " .
                                  " ON p1.examine_year = p3.exam_year AND p1.examine_sem = p3.exam_sem " .
                                  " WHERE p3.type = 'FT') AS project " .
                                  " LEFT JOIN " . $TABLES['staff_pref'] . " as p4" .
                                  " ON project.project_id = p4.prefer " .
                                  " GROUP BY project.project_id, project.title " .
                                  " ORDER BY project.project_id ASC";
*/
  $query_rsProjectPreference  = "SELECT project.project_id, project.title, count(DISTINCT p4.staff_id) as total FROM " .
                                "(SELECT DISTINCT(p1.project_id), p2.title FROM " . $TABLES['fea_projects'] . " AS p1 LEFT JOIN " . $TABLES['fyp'] .
                                " as p2 ON p1.project_id = p2.project_id WHERE p1.examine_year = (SELECT examine_year FROM " . $TABLES['fea_projects'] .
                                " ORDER BY examine_year Desc LIMIT 1) AND p1.examine_sem = (SELECT examine_sem FROM " . $TABLES['fea_projects'] .
                                " ORDER BY project_id Desc LIMIT 1)) AS project LEFT JOIN " . $TABLES['staff_pref'] . " as p4 ON project.project_id = p4.prefer " . 
                                " GROUP BY project.project_id, project.title ORDER BY project.project_id ASC";

    $stmt_1 = $conn_db_ntu->prepare($query_rsProjectPreference);
    $stmt_1->execute();
    $rsProjectPreference = $stmt_1->fetchAll(PDO::FETCH_ASSOC);
}
else {
  $query_rsProjectPreference		= "SELECT project.project_id, project.title, p2.count as total, p2.choose_date FROM " . $TABLES['fyp'] . " as project LEFT JOIN " . $TABLES['staff_pref_count'] . " as p2 ".
                                  "ON project.project_id = p2.prefer ".
                                  "WHERE p2.choose_date = '" . $_POST['filterProject_To'] . "'" .
                                  "GROUP BY project.project_id, project.title " .
                                  "ORDER BY project.project_id ASC";

  $stmt_1 = $conn_db_ntu->prepare($query_rsProjectPreference);
  $stmt_1->execute();
  $rsProjectPreference = $stmt_1->fetchAll(PDO::FETCH_ASSOC);
}


// When page first loaded, or when viewing current sem result. This is for filtering area
if ((!isset($_POST["filter_From"]) && !isset($_POST["filter_To"])) || ($_POST["filter_From"] == $currentYrSem && $_POST["filter_To"] == $currentYrSem)) {
  // SQL query to retrieve data from DB
  $query_rsInterestArea = "SELECT p1.key, p1.title, COUNT(DISTINCT p2.staff_id) as total FROM " . $TABLES['interest_area'] . " as p1 LEFT JOIN " . $TABLES['staff_pref'] . " as p2 ".
                          "ON p1.key = p2.prefer ".
                          "WHERE p2.archive = 0 || p2.archive IS NULL " .
                          "GROUP BY p1.key, p1.title " .
                          "ORDER BY p1.key ASC";

  array_push($searchArray, $currentYrSem);

  // Query DB
  try {
    $stmt_0 = $conn_db_ntu->prepare($query_rsInterestArea);
  	$stmt_0->execute();
  	$rsInterestArea = $stmt_0->fetchAll(PDO::FETCH_ASSOC);

    // Put results in array
    $preferenceArray["interest"][] = $rsInterestArea;
  }
  catch (PDOException $e) {
    die($e->getMessage());
  }
}
else {
  $filterError = false;

  if ($_POST["filter_From"] > $_POST["filter_To"]) {
    $filterError = true;
    $_SESSION['filterFromExceedFilterTo'] = 'error';
  }

  if ($filterError == false) {

    $filter_From = $_POST["filter_From"];
    $filter_To = $_POST["filter_To"];

    // Get the range for From to To
    $indexFrom = array_search($_POST["filter_From"], $filterArray);
    $indexTo = array_search($_POST["filter_To"], $filterArray);

    // If filer more than 4 semester
    if (($indexFrom - $indexTo) <= 3) {
     // Initialize the number of array needed
     for ($i = $indexFrom; $i >= $indexTo && $i > -1; $i--) {
       $chooseDate = $filterArray[$i];

       array_push($searchArray, $chooseDate);

      // Get current sem result if filter until current sem, afterwards append to array
      if ((($_POST["filter_To"] == $currentYrSem) && ($chooseDate == $currentYrSem)) || ((!isset($_POST["filter_To"])) && ($chooseDate == $currentYrSem))) {
        // SQL query to retrieve data from DB
        $query_rsCurrentInterestArea = "SELECT p1.key, p1.title, COUNT(DISTINCT p2.staff_id) as total FROM " . $TABLES['interest_area'] . " as p1 LEFT JOIN " . $TABLES['staff_pref'] . " as p2 ".
                                "ON p1.key = p2.prefer ".
                                "WHERE p2.archive = 0 || p2.archive IS NULL " .
                                "GROUP BY p1.key, p1.title " .
                                "ORDER BY p1.key ASC";

        $stmt_2 = $conn_db_ntu->prepare($query_rsCurrentInterestArea);
        $stmt_2->execute();
        $rsCurrentInterestArea = $stmt_2->fetchAll(PDO::FETCH_ASSOC);

        $preferenceArray["interest"][] = $rsCurrentInterestArea;
      }
      else {
        // Get previous sem results, exclude current semester
        $query_rsInterestArea = "SELECT p1.key, p1.title, p2.count as total, p2.choose_date FROM " . $TABLES['interest_area'] . " as p1 LEFT JOIN " . $TABLES['staff_pref_count'] . " as p2 ".
                                "ON p1.key = p2.prefer ".
                                "WHERE p2.choose_date = '" . $chooseDate . "'" .
                                "GROUP BY p1.key, p1.title, p2.count " .
                                "ORDER BY p1.key ASC";

        try {
          $stmt_0 = $conn_db_ntu->prepare($query_rsInterestArea);
        	$stmt_0->execute();
        	$rsInterestArea = $stmt_0->fetchAll(PDO::FETCH_ASSOC);

          // Put results in array
          $preferenceArray["interest"][] = $rsInterestArea;
        }
        catch (PDOException $e) {
            die($e->getMessage());
          }
        }
      } // end for loop
    }
    else {
      $_SESSION['filterExceedMax'] = 'error';
    }
  }
}

// Retrieve all area within that chosen filter period
$query_rsAllArea = "SELECT p1.key, p1.title FROM " . $TABLES['interest_area'] . " as p1 ".
                        "ORDER BY p1.key ASC";

$stmt_area = $conn_db_ntu->prepare($query_rsAllArea);
$stmt_area->execute();
$rsAllArea = $stmt_area->fetchAll(PDO::FETCH_ASSOC);

$allArea = [];
foreach ($rsAllArea as $row_rsAllArea) {
    array_push($allArea, $row_rsAllArea['title']);
}

// Pie Chart
$query_rsTotalInterestArea = "SELECT COUNT(DISTINCT p2.staff_id) as total FROM " . $TABLES['interest_area'] . " as p1 LEFT JOIN " . $TABLES['staff_pref'] . " as p2 ".
                        "ON p1.key = p2.prefer ".
                        "WHERE p2.archive = 0 or p2.archive IS NULL ";

$query_rsTotalProjectPreference		= "SELECT count(DISTINCT p4.staff_id) as total FROM " .
                                "(SELECT DISTINCT(p1.project_id), p2.title FROM " . $TABLES["fea_projects"] . " as p1 " .
                                " LEFT JOIN " . $TABLES['fyp'] . " as p2 " .
                                " ON p1.project_id = p2.project_id ".
                                " LEFT JOIN " . $TABLES['allocation_settings_others'] . " as p3 " .
                                " ON p1.examine_year = p3.exam_year AND p1.examine_sem = p3.exam_sem " .
                                " WHERE p3.type = 'FT') AS project " .
                                " LEFT JOIN " . $TABLES['staff_pref'] . " as p4" .
                                " ON project.project_id = p4.prefer " ;

$query_rsTotalStaff = "SELECT COUNT(DISTINCT id) as total FROM " . $TABLES['staff'];

// Query DB
try {
  $stmt_4 = $conn_db_ntu->prepare($query_rsTotalInterestArea);
	$stmt_4->execute();
	$rsTotalInterestArea = $stmt_4->fetchAll(PDO::FETCH_ASSOC);

  $stmt_5 = $conn_db_ntu->prepare($query_rsTotalProjectPreference);
  $stmt_5->execute();
  $rsTotalProjectPreference = $stmt_5->fetchAll(PDO::FETCH_ASSOC);

  $stmt_6 = $conn_db_ntu->prepare($query_rsTotalStaff);
  $stmt_6->execute();
  $rsTotalStaff = $stmt_6->fetchAll(PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
  die($e->getMessage());
}

// Set options for view type
$viewArray = array("Chart", "Table");
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Staff Preference Results</title>
	<style>
  .nav-tabs > li > a
  {
      width: 15em;
  }
  </style>

  <script src="https://www.chartjs.org/dist/2.8.0/Chart.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src='https://cdn.jsdelivr.net/lodash/4.17.2/lodash.min.js'></script>
  <script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js'></script>
</head>

<body>
	<?php require_once('../../../php_css/headerwnav.php'); ?>
  		 <?php require_once('../../nav.php'); ?>
       <div class="container-fluid" style="margin-bottom:5%;">
        <h3>Staff Preference Results</h3>
        <?php
          if (isset($_SESSION['filterFromExceedFilterTo'])) {
            echo "<p class='warn'> Filter Semesters in Ascending Order.</p>";
            unset($_SESSION['filterFromExceedFilterTo']);
          }
          if (isset($_SESSION['filterExceedMax'])) {
            echo "<p class='warn'> Maximum 4 semesters allowed.</p>";
            unset($_SESSION['filterExceedMax']);
          }
        ?>
        <br />
        <br />
        <div class="container-fluid">
         <div style="clear:right; margin-bottom:15%;">
           <div style="float:left; width:50%;">
              <canvas id="areaTotal" height="100"></canvas>
            </div>
            <div style="float:right; width:50%;">
                 <canvas id="projectTotal" height="100"></canvas>
            </div>
         </div>
         <br />
         <div style="clear:left;">
          <br /><br />
          <br /><br />
           <ul class="nav nav-tabs">
             <li class="active"><a data-toggle="tab" href="#home">Area Preference</a></li>
             <li><a data-toggle="tab" href="#menu1">Project Preference</a></li>
           </ul>
           <div class="tab-content">
             <div id="home" class="tab-pane fade in active">
               <br />
               <div style="float:left;">
                 <form id="filterParams" name="searchbox" action="staffpref_result.php" method="post">
                    View in:
                   <select id="viewFilter" name="viewFilter" onchange="this.form.submit()">
                     <?php
                       foreach ($viewArray as $view) {
                         if(isset($_POST["viewFilter"]) && $_POST["viewFilter"] == $view) {
                           echo "<option selected value='".$view."'>".$view."</option>";
                         } else {
                           echo "<option value='".$view."'>".$view."</option>";
                         }
                       }
                     ?>
                  </select>
                  <br /><br />
                    From:
                   <select id="filter_From" name="filter_From" onchange="this.form.submit()">
                     <?php
                       foreach ($filterArray as $filter) {
                         if(isset($_POST["filter_From"]) && $_POST["filter_From"] == $filter) {
                           echo "<option selected value='".$filter."'>".$filter."</option>";
                         } else {
                           echo "<option value='".$filter."'>".$filter."</option>";
                         }
                       }
                     ?>
                   </select>
                   To:
                  <select id="filter_To" name="filter_To" onchange="this.form.submit()">
                    <?php
                      foreach ($filterArray as $filter) {
                        if(isset($_POST["filter_To"]) && $_POST["filter_To"] == $filter) {
                          echo "<option selected value='".$filter."'>".$filter."</option>";
                        } else {
                          echo "<option value='".$filter."'>".$filter."</option>";
                        }
                      }
                    ?>
                  </select>
                  <?php $csrf->echoInputField();?>
                 </form>
               </div>
               <div style="float:right;">
                 <form method="post" action="submit_download_pref.php">
                     <input type="hidden" id="download_filter_From" name="download_filter_From" value="<?php if (isset($_POST['filter_From'])) {
                      echo $_POST['filter_From'];
                    } else echo $currentYrSem; ?>">
                     <input type="hidden" id="download_filter_To" name="download_filter_To" value="<?php if (isset($_POST['filter_To'])) {
                       echo $_POST['filter_To'];
                     } else echo $currentYrSem; ?>">
                     <input type="submit" value="Download Area Preference" class="btn bg-dark text-white" style="font-size:12px; float:right;" title="Download Area Preference">
                 </form>
               </div>
               <br /><br />
               <div class="table-responsive">
                 <canvas id="interestAreaCanvas" height="600" <?php if(isset($_POST['viewFilter']) && $_POST['viewFilter'] == 'Table') {echo " style='display: none'"; } else {echo " style='display: block'";} ?>></canvas>
                 <table id="interestAreaTable" border="1" cellpadding="0" cellspacing="0" width="100%" align="center" <?php if(!isset($_POST['viewFilter']) || $_POST['viewFilter'] == 'Chart') {echo " style='display: none'"; } else {echo " style='display: table'";} ?>>

                   <tr class="bg-dark text-white text-center">
                     <th class="bg-dark text-white text-center" style="font-weight:normal">Interest Area</th>
                     <?php
                        if (isset($_POST['filter_From']) || isset($_POST['filter_To'])) {
                          for ($i = 0; $i < count($searchArray); $i++) {
                            echo "<th class='bg-dark text-white text-center' style='font-weight:normal'>". $searchArray[$i] . "</th>";
                          }
                        }
                        else {
                          echo "<th class='bg-dark text-white text-center' style='font-weight:normal'>". $currentYrSem . "</th>";
                        }
                     ?>
                   </tr>

                   <?php
                     $index = 0;

                     foreach ($rsAllArea as $row_rsAllArea) {
                       echo "<tr class='text-center'>";
                       echo "<td>" . $row_rsAllArea['title'] . "</td>";

                       if (isset($_POST['filter_From']) || isset($_POST['filter_To'])) {
                         if (count($searchArray) == 4) {
                           if (isset($preferenceArray["interest"][0][$index])) {
                             echo "<td>" . $preferenceArray["interest"][0][$index]['total'] . "</td>";
                           }
                           else {
                             echo "<td>" . "0" . "</td>";
                           }
                           if (isset($preferenceArray["interest"][1][$index])) {
                             echo "<td>" . $preferenceArray["interest"][1][$index]['total'] . "</td>";
                           }
                           else {
                             echo "<td>" . "0" . "</td>";
                           }
                           if (isset($preferenceArray["interest"][2][$index])) {
                             echo "<td>" . $preferenceArray["interest"][2][$index]['total'] . "</td>";
                           }
                           else {
                             echo "<td>" . "0" . "</td>";
                           }
                           if (isset($preferenceArray["interest"][3][$index])) {
                             echo "<td>" . $preferenceArray["interest"][3][$index]['total'] . "</td>";
                           }
                           else {
                             echo "<td>" . "0" . "</td>";
                           }
                            /* echo "<td>" . $preferenceArray["interest"][0][$index]['total'] . "</td>";
                            echo "<td>" . $preferenceArray["interest"][1][$index]['total'] . "</td>";
                            echo "<td>" . $preferenceArray["interest"][2][$index]['total'] . "</td>";
                            echo "<td>" . $preferenceArray["interest"][3][$index]['total'] . "</td>"; */
                         }
                         else if (count($searchArray) == 3) {
                           if (isset($preferenceArray["interest"][0][$index])) {
                             echo "<td>" . $preferenceArray["interest"][0][$index]['total'] . "</td>";
                           }
                           else {
                             echo "<td>" . "0" . "</td>";
                           }
                           if (isset($preferenceArray["interest"][1][$index])) {
                             echo "<td>" . $preferenceArray["interest"][1][$index]['total'] . "</td>";
                           }
                           else {
                             echo "<td>" . "0" . "</td>";
                           }
                           if (isset($preferenceArray["interest"][2][$index])) {
                             echo "<td>" . $preferenceArray["interest"][2][$index]['total'] . "</td>";
                           }
                           else {
                             echo "<td>" . "0" . "</td>";
                           }
                         }
                         else if (count($searchArray) == 2) {
                            if (isset($preferenceArray["interest"][0][$index])) {
                              echo "<td>" . $preferenceArray["interest"][0][$index]['total'] . "</td>";
                            }
                            else {
                              echo "<td>" . "0" . "</td>";
                            }
                            if (isset($preferenceArray["interest"][1][$index])) {
                              echo "<td>" . $preferenceArray["interest"][1][$index]['total'] . "</td>";
                            }
                            else {
                              echo "<td>" . "0" . "</td>";
                            }
                         }
                         else
                            echo "<td>" . $preferenceArray["interest"][0][$index]['total'] . "</td>";
                     }
                     else {
                       echo "<td>" . $preferenceArray["interest"][0][$index]['total'] . "</td>";
                     }
                     echo "</tr>";

                     $index++;
                   }
                   ?>
               </table>
               </div>
             </div>
             <div id="menu1" class="tab-pane fade">
               <br />
               <div style="float:left;">
                 <form id="filterProjectParams" name="searchbox" action="staffpref_result.php" method="post">
                    View in:
                   <select id="viewProjectFilter" name="viewProjectFilter" onchange="this.form.submit()">
                     <?php
                       foreach ($viewArray as $view) {
                         if(isset($_POST["viewProjectFilter"]) && $_POST["viewProjectFilter"] == $view) {
                           echo "<option selected value='".$view."'>".$view."</option>";
                         } else {
                           echo "<option value='".$view."'>".$view."</option>";
                         }
                       }
                     ?>
                  </select>
                  <br /><br />
                   To:
                  <select id="filterProject_To" name="filterProject_To" onchange="this.form.submit()">
                    <?php
                      foreach ($filterArray as $filter) {
                        if(isset($_POST["filterProject_To"]) && $_POST["filterProject_To"] == $filter) {
                          echo "<option selected value='".$filter."'>".$filter."</option>";
                        } else {
                          echo "<option value='".$filter."'>".$filter."</option>";
                        }
                      }
                    ?>
                  </select>
                  <?php $csrf->echoInputField();?>
                  <br /> <br />
                 </form>
               </div>
               <div style="float:right;">
                 <form method="post" action="submit_download_pref.php">
                     <input type="hidden" id="download_project_filter_To" name="download_project_filter_To" value="<?php if (isset($_POST['filterProject_To'])) {
                       echo $_POST['filterProject_To'];
                     } else echo $currentYrSem; ?>">
                     <input type="submit" value="Download Project Preference" class="btn bg-dark text-white" style="font-size:12px; float:right;" title="Download Project Preference">
                 </form>
               </div>
               <br /><br />
               <div class="table-responsive">
                 <canvas id="projectPreferenceCanvas" height="800" <?php if(isset($_POST['viewProjectFilter']) && $_POST['viewProjectFilter'] == 'Table') {echo " style='display: none'"; } else {echo " style='display: block'"; } ?> >></canvas>
                 <table id="projectPreferenceTable" border="1" cellpadding="0" cellspacing="0" width="100%" align="center" <?php if(!isset($_POST['viewProjectFilter']) || $_POST['viewProjectFilter'] == 'Chart') {echo " style='display: none'"; } else {echo " style='display: table'"; } ?>>
                   <col width="20%"/>
                   <col width="70%"/>
                   <col width="10%"/>

                   <tr class="bg-dark text-white text-center">
                     <th class="bg-dark text-white text-center" style="font-weight:normal">Project ID</th>
                     <th class="bg-dark text-white text-center" style="font-weight:normal">Project Title</th>
                     <th class="bg-dark text-white text-center" style="font-weight:normal">Total</th>
                   </tr>

                   <?php
                   foreach ($rsProjectPreference as $row_rsProjectPreference) {
                       echo "<tr class='text-center'>";
                       echo "<td>" . $row_rsProjectPreference['project_id'] . "</td>";
                       echo "<td>" . $row_rsProjectPreference['title'] . "</td>";
                       echo "<td>" . $row_rsProjectPreference['total'] . "</td>";
                       echo "</tr>";
                   }
                   ?>
               </table>
               </div>
             </div>
           </div>
         </div>
       </div>
     </div>
<!-- closing navigation div in nav.php -->
</div>

  <script>
    $(document).ready(function () {
      showTotalInterestAreaCanvas();
      showTotalProjectPreferenceCanvas();
      showInterestAreaCanvas();
      showProjectPreferenceCanvas();
    });

    function showTotalInterestAreaCanvas() {
      var colorArray = setColors(2);

      var total = <?php echo json_encode($rsTotalInterestArea); ?>;
      var totalStaff = <?php echo json_encode($rsTotalStaff); ?>;
      var count = [];

      for (var i in total) {
          count.push(total[i].total);
          count.push(totalStaff[i].total - total[i].total);
      }

      var data = {
        labels: ["With Preferred Area", "No Preferred Area"],
        datasets: [
          {
            //label: "TeamA Score",
            data: count,
            backgroundColor: colorArray[0],
            borderColor: colorArray[1],
            borderWidth: [1, 1]
          }
        ]
      };

      var ctx1 = $("#areaTotal");
      var areaTotal = new Chart(ctx1, {
        type: "pie",
        data: data,
        options: {
          responsive: true,
          maintainAspectRatio: true,
          title: {
            display: true,
            position: "top",
            text: "Area Preference Chart",
            fontSize: 18,
            fontColor: "#111"
          },
          legend: {
            display: true,
            position: "bottom",
            labels: {
              fontColor: "#333",
              fontSize: 12
            }
          }
        }
      });
    }

    function showTotalProjectPreferenceCanvas() {
      var colorArray = setColors(2);

      var total = <?php echo json_encode($rsTotalProjectPreference); ?>;
      var totalStaff = <?php echo json_encode($rsTotalStaff); ?>;
      var count = [];

      for (var i in total) {
          count.push(total[i].total);
          count.push(totalStaff[i].total - total[i].total);
      }

      var data = {
        labels: ["With Preferred Project", "No Preferred Project"],
        datasets: [
          {
            //label: "TeamA Score",
            data: count,
            backgroundColor: colorArray[0],
            borderColor: colorArray[1],
            borderWidth: [1, 1]
          }
        ]
      };

      var ctx1 = $("#projectTotal");
      var areaTotal = new Chart(ctx1, {
        type: "pie",
        data: data,
        options: {
          responsive: true,
          maintainAspectRatio: true,
          title: {
            display: true,
            position: "top",
            text: "Project Preference Chart",
            fontSize: 18,
            fontColor: "#111"
          },
          legend: {
            display: true,
            position: "bottom",
            labels: {
              fontColor: "#333",
              fontSize: 12
            }
          }
        }
      });
    }

    function showInterestAreaCanvas() {
      var data = <?php echo json_encode($preferenceArray["interest"]); ?>;
      var areaData = <?php echo json_encode($allArea) ?>;
      var labelData = <?php echo json_encode($searchArray); ?>;

      var countArray = Object.keys(data).length;

      var area = [];
      var count = [];
      var label = [];

      for (var l = 0; l < countArray; l++) {
        count[l] = [];

        for (var i in data[l]) {
          if (typeof data[l] == 'undefined') {
            count[l].push("0");
          }
          else {
            count[l].push(data[l][i].total);
          }
        }
      }

      for (var i in areaData) {
        area.push(areaData[i]);
      }

      for (var i in labelData) {
        label.push(labelData[i]);
        /*
        if (labelData[i] !== undefined || labelData[i].length != 0) {
          label.push("<!--?php echo $currentYrSem; ?-->");
        }
        else {

        } */
      }

      var interestAreaData = {
        labels: area,
        datasets: [{
            label: label[0],
            backgroundColor: "rgba(75, 192, 192, 0.8)", //graphColors, //getRandomColorEachEmployee(data.length),//'#CCCCCC',
            borderColor: "rgb(-5, 112, 112)", //'#666666',
            hoverBackgroundColor: "rgb(100, 217, 217)", //'#CCCCCC',
            hoverBorderColor: "rgb(100, 217, 217)", //hoverColor,//'#666666',
            data: count[0],
          }]
      };

      var ctx = document.getElementById('interestAreaCanvas').getContext('2d');
      window.myHorizontalBar = new Chart(ctx, {
        type: 'horizontalBar',
        data: interestAreaData,
        options: {
          elements: {
            rectangle: {
              borderWidth: 0.5,
            }
          },
          scales: {
            yAxes: [{
              ticks: {
                fontSize: 11
              },
              barPercentage: 0.9
            }],
            xAxes: [{
              ticks: {
                beginAtZero: true,
                callback: function(value) {if (value % 1 === 0) {return value;}}
              }
            }]
          },
          maintainAspectRatio: true,
          responsive: true,
          legend: {
            position: 'right',
          },
          title: {
            display: true,
            text: "<?php if (isset($_POST['filter_From']) && isset($_POST['filter_From'])) {
                      echo 'Area Preference for ' . $_POST['filter_From'] . " to " . $_POST['filter_To'];
                    }
                    else if (isset($_POST['filter_From']) && (!isset($_POST['filter_From'])) ) {
                      echo 'Area Preference for ' . $_POST['filter_From'] . " to " . $currentYrSem;
                    }
                    else
                      echo 'Area Preference for ' . $currentYrSem; ?>",
          }
        }
      });

      if (countArray >= 2) {
        var newDataset = {
            label: label[1],
            backgroundColor: "rgba(255, 159, 64, 0.8)", //graphColors, //getRandomColorEachEmployee(data.length),//'#CCCCCC',
            borderColor: "rgb(175, 79, -16)", //'#666666',
            hoverBackgroundColor: "rgb(280, 184, 89)", //'#CCCCCC',
            hoverBorderColor: "rgb(280, 184, 89)", //hoverColor,//'#666666',
            data: count[1],
        }
        interestAreaData.datasets.push(newDataset);
      }

      if (countArray >= 3) {
        var newDataset2 = {
            label: label[2],
            backgroundColor: "rgba(54, 162, 235, 0.8)", //graphColors, //getRandomColorEachEmployee(data.length),//'#CCCCCC',
            borderColor: "rgb(-26, 82, 155)", //'#666666',
            hoverBackgroundColor: "rgb(79, 187, 260)", //'#CCCCCC',
            hoverBorderColor: "rgb(79, 187, 260)", //hoverColor,//'#666666',
            data: count[2],
        }
        interestAreaData.datasets.push(newDataset2);
      }

      if (countArray >= 4) {
        var newDataset3 = {
            label: label[3],
            backgroundColor: "rgba(255, 99, 132, 0.8)", //graphColors, //getRandomColorEachEmployee(data.length),//'#CCCCCC',
            borderColor: "rgb(175, 19, -96)", //'#666666',
            hoverBackgroundColor: "rgb(280, 124, 114)", //'#CCCCCC',
            hoverBorderColor: "rgb(280, 124, 114)", //hoverColor,//'#666666',
            data: count[3],
        }
        interestAreaData.datasets.push(newDataset3);
      }

      window.myHorizontalBar.update();
    }

    function showProjectPreferenceCanvas() {
      var data = <?php echo json_encode($rsProjectPreference); ?>;
      var project = [];
      var count = [];

      for (var i in data) {
          project.push(data[i].project_id);
          count.push(data[i].total);
      }

      var colorArray = setColors(data.length);

      var projectPreferenceData = {
        labels: project,
        datasets: [{
            label: "<?php
                      if (isset($_POST['filterProject_To'])) {
                        echo $_POST['filterProject_To'];
                      }
                      else {
                        echo $currentYrSem;
                      }  ?>",
            backgroundColor: colorArray[0], //graphColors, //getRandomColorEachEmployee(data.length),//'#CCCCCC',
            borderColor: colorArray[1], //'#666666',
            hoverBackgroundColor: colorArray[2], //'#CCCCCC',
            hoverBorderColor: colorArray[2], //hoverColor,//'#666666',
            data: count
          }]
      };

      var ctx = document.getElementById('projectPreferenceCanvas').getContext('2d');
      //window.myBar = new Chart(ctx, {
      window.myHorizontalBar = new Chart(ctx, {
        type: 'horizontalBar',
        data: projectPreferenceData,
        options: {
          elements: {
            rectangle: {
              borderWidth: 2,
            }
          },
          onClick: function(c,i) {
              e = i[0];
              var x_value = this.data.labels[e._index];
              document.cookie = "temp_pid=" + x_value;
              document.location.href = 'allocation_edit.php';
          },
          scales: {
            xAxes:[{
                ticks: {
                  beginAtZero: true,
                  //autoSkip: false,
                  callback: function(value) {if (value % 1 === 0) {return value;}}
                }
            }],
            yAxes:[{
                ticks: {
                  autoSkip: false,
                }
            }],
          },
          maintainAspectRatio: true,
          responsive: true,
          legend: {
            position: 'bottom',
          },
          title: {
            display: true,
            text: "<?php if (isset($_POST['filterProject_To'])) {
                    echo 'Project Preference for ' . $_POST['filterProject_To'];
                  }
                  else {
                    echo 'Project Preference for ' . $currentYrSem;
                  }  ?>",
          }
        }
      });
    }

    function setColors(dataCount) {
      var graphColors = [];
      var graphOutlines = [];
      var hoverColor = [];
      var colorArray = [];

      var internalDataLength = dataCount;
      i = 0;
      while (i <= internalDataLength) {
          var randomR = Math.floor((Math.random() * 130) + 100);
          var randomG = Math.floor((Math.random() * 130) + 100);
          var randomB = Math.floor((Math.random() * 130) + 100);

          var graphBackground = "rgba("
                  + randomR + ", "
                  + randomG + ", "
                  + randomB + ", 0.8)";
          graphColors.push(graphBackground);

          var graphOutline = "rgb("
                  + (randomR - 80) + ", "
                  + (randomG - 80) + ", "
                  + (randomB - 80) + ")";
          graphOutlines.push(graphOutline);

          var hoverColors = "rgb("
                  + (randomR + 25) + ", "
                  + (randomG + 25) + ", "
                  + (randomB + 25) + ")";
          hoverColor.push(hoverColors);
        i++;
      };

      colorArray.push(graphColors);
      colorArray.push(graphOutlines);
      colorArray.push(hoverColor);
      return colorArray;
    }
  </script>

<?php require_once('../../../footer.php'); ?>


</body>
</html>
