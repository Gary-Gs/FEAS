<?php
	require_once('../../../Connections/db_ntu.php');
	//require_once('../../../PHPExcel.php');
	require_once('../../../CSRFProtection.php');
	require_once('../../../Utility.php');
	require_once ('../../../vendor/autoload.php');
?>
<?php
$csrf = new CSRFProtection();

$FILENAME = "FeedbackOutput_" . date('d_M_Y'). ".xlsx";

$query_rsFeedback = "SELECT * FROM " . $TABLES['fea_feedback'] . " as p1 LEFT JOIN " . $TABLES['staff'] . " as p2 ON p1.staff_id = p2.id";

try {
  // Retrieve Feedback
  $stmt_1 = $conn_db_ntu->prepare($query_rsFeedback);
  $stmt_1->execute();
  $rsfeedback = $stmt_1->fetchAll(PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
  die($e->getMessage());
}

/* Write to Excel */
$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

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

//Sheet 1 - Feedback
//Create Header
$objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getActiveSheet()->setTitle('Feedback');

$headers = ['SNo.', 'Created On', 'Project Year', 'Project Sem', 'Staff Name', 'Staff ID', 'Overall Rating', 'Type', 'Comment'];
$objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
$objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true);

$rowCount = 2;	//First Data Row, excluding header
foreach ($rsfeedback as $row_rsfeedback)
{
	$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $rowCount-1);					//SNo
	$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $row_rsfeedback['feedback_datetime']);				//Project Code
	$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $row_rsfeedback['exam_year']);			//Supervisor
	$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $row_rsfeedback['exam_sem']);				//Supervisor Network Account
	$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, $row_rsfeedback['name']);			//Examiner
	$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, $row_rsfeedback['staff_id']);					//Examiner
	$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, $row_rsfeedback['rating']);						//Room Number
	$objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, $row_rsfeedback['type']);						//Day No
	$objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, $row_rsfeedback['comment']);						//Date

	if (($rowCount%2) == 0)	//Even Rows
		cellColor('A'.$rowCount.':I'.$rowCount, 'FFFF99');
	else
		cellColor('A'.$rowCount.':I'.$rowCount, 'CCFFCC');

	$rowCount++;
}

//Autosize Sheet 1
autosize_currentSheet();

//Switch back to active sheet
//$objPHPExcel->setActiveSheetIndex(0);

// Save Excel 2007 file
//$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, "Xlsx");
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
exit;
?>
