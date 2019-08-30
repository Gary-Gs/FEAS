<?php
require_once('../../../Connections/db_ntu.php');
require_once('./entity.php');
//require_once('../../../PHPExcel.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');
require_once ('../../../vendor/autoload.php');

$csrf = new CSRFProtection();

$_REQUEST['csrf'] 	= $csrf->cfmRequest();

// initialize months array
$sem1Array = array("Jul", "Aug","Sep","Oct","Nov","Dec");
$sem2Array = array("Jan","Feb","Mar", "Apr", "May", "Jun");

$currentYrSem1 = date("y") . (date("y") + 1);
$currentYrSem2 = (date("y")-1) . date("y");

// Get current year and semester
if (in_array(date("M"), $sem1Array)) {
  $currentYrSem = "Yr " . $currentYrSem1 . " Sem 1";
}
else {
  $currentYrSem = "Yr " . $currentYrSem2 . " Sem 2";
}
/*
// Get current year and semester
if (in_array(date("M"), $sem1Array)) {
  $currentYrSem = "Yr " . substr(date("Y"), -2) . substr(date('Y', strtotime('+1 year')), -2) . " Sem 1";
}
else {
  $currentYrSem = "Yr " . substr(date("Y"), -2) . substr(date('Y', strtotime('+1 year')), -2) . " Sem 2";
} */

if (isset($_POST['download_filter_From'])) {
  $filter_From = $_POST['download_filter_From'];
}
else {
  $filter_From = $currentYrSem;
}

if (isset($_POST['download_filter_To'])) {
  $filter_To = $_POST['download_filter_To'];
}
else {
  $filter_To = $currentYrSem;
}

if (isset($_POST['download_project_filter_To'])) {
  $filterProject_To = $_POST['download_project_filter_To'];
}
else {
  $filterProject_To = $currentYrSem;
}

// Select current open period that is saved in DB
$query_rsSettings = "SELECT * FROM ".$TABLES['allocation_settings_others']." WHERE type= 'FT'";
$settings 	= $conn_db_ntu->query($query_rsSettings)->fetch();
$start 		 	= DateTime::createFromFormat('Y-m-d', $settings['pref_start']);
$end 			= DateTime::createFromFormat('Y-m-d', $settings['pref_end']);

// Get the month of the open period
if (in_array(date("M", strtotime($start)), $sem1Array) && in_array(date("M", strtotime($end)), $sem1Array)) {
    $sem = 1;
}
if (in_array(date("M", strtotime($start)), $sem2Array) && in_array(date("M", strtotime($end)), $sem2Array)) {
    $sem = 2;
}

// initialize array for filter
$CurrentYear = sprintf("%02d", substr(date("Y"), -2));
$LastestYear = sprintf("%02d", substr(date("Y"), -2));
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

// Get the range for From to To for Area
$searchArray = array();
$indexFrom = array_search($filter_From, $filterArray);
$indexTo = array_search($filter_To, $filterArray);

// Initialize the number of array needed
for ($i = $indexFrom; $i >= $indexTo && $i > -1; $i--) {
  $chooseDate = $filterArray[$i];

  array_push($searchArray, $chooseDate);

  // Get current sem result if filter until current sem, afterwards append to array
  if ($filter_To == $currentYrSem && $chooseDate == $currentYrSem) {
    // SQL query to retrieve data from DB
    $rsInterestArea = "SELECT p1.key, p1.title, COUNT(DISTINCT p2.staff_id) as total FROM " . $TABLES['interest_area'] . " as p1 LEFT JOIN " . $TABLES['staff_pref'] . " as p2 ".
                            "ON p1.key = p2.prefer ".
                            "WHERE p2.archive = 0 or p2.archive IS NULL " .
                            "GROUP BY p1.key, p1.title " .
                            "ORDER BY p1.key ASC";

    $stmt_2 = $conn_db_ntu->prepare($rsInterestArea);
    $stmt_2->execute();
    $rsInterestArea = $stmt_2->fetchAll(PDO::FETCH_ASSOC);

    $preferenceArray["interest"][] = $rsInterestArea;
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

// Project
if (isset($_POST['download_project_filter_To']) && $_POST['download_project_filter_To'] == $currentYrSem) {

  /*$query_rsProjectPreference		= "SELECT project.project_id, project.title, count(DISTINCT p4.staff_id) as total FROM " .
                                  "(SELECT DISTINCT(p1.project_id), p2.title FROM " . $TABLES["fea_projects"] . " as p1 " .
                                  " LEFT JOIN " . $TABLES['fyp'] . " as p2 " .
                                  " ON p1.project_id = p2.project_id ".
                                  " LEFT JOIN " . $TABLES['allocation_settings_others'] . " as p3 " .
                                  " ON p1.examine_year = p3.exam_year AND p1.examine_sem = p3.exam_sem " .
                                  " WHERE p3.type = 'FT') AS project " .
                                  " LEFT JOIN " . $TABLES['staff_pref'] . " as p4" .
                                  " ON project.project_id = p4.prefer " .
                                  " GROUP BY project.project_id, project.title " .
                                  " ORDER BY project.project_id ASC"; */

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
                                  "WHERE p2.choose_date = '" . $filterProject_To . "'" .
                                  "GROUP BY project.project_id, project.title " .
                                  "ORDER BY project.project_id ASC";

  $stmt_1 = $conn_db_ntu->prepare($query_rsProjectPreference);
  $stmt_1->execute();
  $rsProjectPreference = $stmt_1->fetchAll(PDO::FETCH_ASSOC);
}

// Query total
$query_rsTotalInterestArea = "SELECT COUNT(DISTINCT p2.staff_id) as total FROM " . $TABLES['interest_area'] . " as p1 LEFT JOIN " . $TABLES['staff_pref'] . " as p2 ".
                        "ON p1.key = p2.prefer ".
                        "WHERE p2.archive = 0 or p2.archive IS NULL ";

$query_rsTotalProjectPreference		= "SELECT count(DISTINCT p4.staff_id) as totalCount FROM " .
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
  $stmt_2 = $conn_db_ntu->prepare($query_rsTotalInterestArea);
	$stmt_2->execute();
	$rsTotalInterestArea = $stmt_2->fetchAll(PDO::FETCH_ASSOC);

  // Retrieve Feedback
  $stmt_3 = $conn_db_ntu->prepare($query_rsTotalProjectPreference);
  $stmt_3->execute();
  $rsTotalProjectPreference = $stmt_3->fetchAll(PDO::FETCH_ASSOC);

  $stmt_4 = $conn_db_ntu->prepare($query_rsTotalStaff);
  $stmt_4->execute();
  $rsTotalStaff = $stmt_4->fetchAll(PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
  die($e->getMessage());
}

// Write to Excel
$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$objWorksheet = $objPHPExcel->getActiveSheet();

function cellColor($cells,$color){
	global $objPHPExcel;
	$objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
		'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
		//'type' => PHPExcel_Style_Fill::FILL_SOLID,
		'startColor' => array( 'rgb' => $color )
	));
}

function autosize_currentSheet()
{
	global $objPHPExcel;

	$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(9);

	foreach (range('B', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
		$objPHPExcel->getActiveSheet()
				->getColumnDimension($col)
				->setAutoSize(true);
	}
}


//Default Styles
$objPHPExcel->getDefaultStyle()	->getFont()
								->setName('Arial')
								->setSize(10);

//Sheet 1 - Overall
//Create Header
$objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getActiveSheet()->setTitle('Overall');

$headers = ['Preference', 'No. of Faculty Staff Who Indicated Their Preferred Area'];
$objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
$objPHPExcel->getActiveSheet()->getStyle('A1:B1')->getFont()->setBold(true);

$rowCount = 2;	//First Data Row, excluding header

foreach ($rsTotalInterestArea as $row_rsTotalInterestArea)
{
  $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, "Area");
  $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $row_rsTotalInterestArea['total']);

  if (($rowCount%2) == 0)	//Even Rows
		cellColor('A'.$rowCount.':B'.$rowCount, 'FFFF99');
	else
		cellColor('A'.$rowCount.':B'.$rowCount, 'CCFFCC');

	$rowCount++;
}

foreach ($rsTotalProjectPreference as $row_rsTotalProjectPreference)
{
  $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, "Project");
  $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $row_rsTotalProjectPreference['totalCount']);

  if (($rowCount%2) == 0)	//Even Rows
  	cellColor('A'.$rowCount.':B'.$rowCount, 'FFFF99');
  else
  	cellColor('A'.$rowCount.':B'.$rowCount, 'CCFFCC');

  $rowCount++;
}
$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $row_rsTotalInterestArea['total'] + $row_rsTotalProjectPreference['totalCount']);
$objPHPExcel->getActiveSheet()->getStyle('B'.$rowCount)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
$objPHPExcel->getActiveSheet()->getStyle('B'.$rowCount)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);

//Autosize Sheet 1
autosize_currentSheet();

// Sheet 2 - Area
if (!isset($_POST['download_project_filter_To'])) {
  $FILENAME = "StaffPrefAreaOutput_" . date('d_M_Y'). ".xlsx";

  if (count($searchArray) > 0) {
    for ($i = count($searchArray)-1; $i >= 0; $i--) {
      $index = 0;

      $objPHPExcel->createSheet();
      $objPHPExcel->setActiveSheetIndex(count($searchArray)-$i);
      $objPHPExcel->getActiveSheet()->setTitle($searchArray[count($searchArray)-$i-1]);

      $headers = ['Area', 'Total'];
      $objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
      $objPHPExcel->getActiveSheet()->getStyle('A1:B1')->getFont()->setBold(true);

      $rowCount = 2;	//First Data Row, excluding header
      foreach ($rsInterestArea as $row_rsInterestArea)
      {
        //$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $rowCount-1);					//SNo
      	$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $row_rsInterestArea['title']);				//Project Code

        if (isset($preferenceArray["interest"][count($searchArray)-$i-1][$index])) {
          //echo $preferenceArray["interest"][count($searchArray)-$i-1][$index];
          $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $preferenceArray["interest"][count($searchArray)-$i-1][$index]['total']);			//Supervisor
        }
        else {
          $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, "0");
        }

      	if (($rowCount%2) == 0)	//Even Rows
      		cellColor('A'.$rowCount.':B'.$rowCount, 'FFFF99');
      	else
      		cellColor('A'.$rowCount.':B'.$rowCount, 'CCFFCC');

      	$rowCount++;
        $index++;
      }
      autosize_currentSheet();
    }
  }
}

// ----------- Next Sheet ------------
//Sheet x - Project
//Create Header
if (isset($_POST['download_project_filter_To'])) {
  $FILENAME = "StaffPrefProjectOutput_" . date('d_M_Y'). ".xlsx";

  $objPHPExcel->createSheet();
  $objPHPExcel->setActiveSheetIndex(1);
  $objPHPExcel->getActiveSheet()->setTitle($_POST['download_project_filter_To']);

  $headers = ['Project ID', 'Title', 'Total'];
  $objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
  $objPHPExcel->getActiveSheet()->getStyle('A1:C1')->getFont()->setBold(true);

  $rowCount = 2;	//First Data Row, excluding header
  foreach ($rsProjectPreference as $row_rsProjectPreference)
  {
  	//$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $rowCount-1);					//SNo
  	$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $row_rsProjectPreference['project_id']);				//Project Code
  	$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $row_rsProjectPreference['title']);			//Supervisor
    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $row_rsProjectPreference['total']);			//Supervisor

  	if (($rowCount%2) == 0)	//Even Rows
  		cellColor('A'.$rowCount.':C'.$rowCount, 'FFFF99');
  	else
  		cellColor('A'.$rowCount.':C'.$rowCount, 'CCFFCC');

  	$rowCount++;
  }

  //Autosize Sheet 1
  autosize_currentSheet();
}

// -------- END SHEET 2 -----------


// Switch back to active sheet
$objPHPExcel->setActiveSheetIndex(0);

$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, "Xlsx");
$objWriter->setIncludeCharts(true);
$objWriter->save($FILENAME);

ob_start();
header('Content-disposition: attachment; filename='.$FILENAME);
header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Length: '.filesize($FILENAME));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: no-cache');
ob_clean();

flush();
readfile($FILENAME);
$conn_db_ntu = null;
exit();

?>
