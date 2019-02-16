<?php
	require_once('../../../Connections/db_ntu.php');
	require_once('./entity.php');
	//require_once('../../../PHPExcel.php');
	require_once('../../../CSRFProtection.php');
	require_once('../../../Utility.php');
	require_once ('../../../vendor/autoload.php');
?>
<?php
	$csrf = new CSRFProtection();
	$FILENAME = "ResultVisualizationOutput_" . date('d_M_Y'). ".xlsx";
    $filter_ProjectSem;
	if(isset($_REQUEST['filter_ProjectSem']) && !empty($_REQUEST['filter_ProjectSem'])){
            $filter_ProjectSem = $_REQUEST['filter_ProjectSem'];
    }
    if(isset($_REQUEST['filter_ProjectYear']) && !empty($_REQUEST['filter_ProjectYear'])){
            $filter_ProjectYear = $_REQUEST['filter_ProjectYear'];
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
    

      $query_rsProjectExamining     = "SELECT p.project_id as project_id, student.name as student_name, p1.title as project_name, s.name as staff_name, s.id as staff_id, r.examiner_id as examinerid, examiner_info.name as examiner_name,  r.day as day, r.slot as slot, r.room as room FROM " . $TABLES['fyp_assign'] . " as p 
            JOIN " .$TABLES['fea_projects'] . " as projects ON p.project_id = projects.project_id
            JOIN " . $TABLES['fyp'] . " as p1 ON p.project_id = p1.project_id 
            JOIN " . $TABLES['staff'] . " as s ON p.staff_id = s.id 
            JOIN " . $TABLES['student'] . " as student ON p.student_id = student.student_id 
            JOIN ". $TABLES['allocation_result'] . " as r ON r.project_id = p.project_id 
            JOIN  " . $TABLES['staff'] . " as examiner_info ON r.examiner_id = examiner_info.id 
            WHERE (projects.examine_sem LIKE ? AND projects.examine_year LIKE ?) ORDER BY examiner_info.id";

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
	}
	catch (PDOException $e)
	{
		die($e->getMessage());
	}

	function getMaxColumnCount(){
		$max = 0;
        global $projectsCount;
		foreach($projectsCount as $value){
			if((($value['no_of_exemption'] - $value['project_count']) + $value['project_count']) > $max){
                $max = ($value['no_of_exemption'] - $value['project_count'] + $value['project_count']);
            }
		}
        return $max;
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
    /* Write to Excel */
    $objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	//Default Styles
	$objPHPExcel->getDefaultStyle()	->getFont()
									->setName('Arial')
									->setSize(10);

	//Sheet 1 - Before Allocation
	//Create Header
	$objPHPExcel->createSheet();
	$objPHPExcel->setActiveSheetIndex(0);
	$objPHPExcel->getActiveSheet()->setTitle('BeforeAllocation');
	$headers = ['No.', 'Staff Name', 'EXE'];
	$objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
	$objPHPExcel->getActiveSheet()->getStyle('A1:C1')->getFont()->setBold(true);
    $maxColumn = getMaxColumnCount();
    $previousHeaderColumn = 'C';
    for($i=1; $i<= $maxColumn; $i++){
        $previousHeaderColumn++;
        $objPHPExcel->getActiveSheet()->SetCellValue($previousHeaderColumn.'1', 'Proj'.$i);
        $objPHPExcel->getActiveSheet()->getStyle($previousHeaderColumn.'1')->getFont()->setBold(true);
    }

    $rowCountExcel = 2; 
    $exemptionCount = 0;
    $staffProjectCount = 0;
    $rowcount = 1;
    $count = 0;
    $previousRecord;
    $previousCellColumn;
    $details = "";
    foreach($projects as $value){
        if($rowcount > 1){
        // when the staffid is the same as the previous record
            if(strcmp($previousRecord, $value['staff_id']) == 0){
                $previousCellColumn++;
                $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, $value['project_id']);
                $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startColor' => array( 'rgb' => 'CCFFCC' )));
                $previousRecord = $value['staff_id'];
                $count++;
                $staffProjectCount++; 
            }
            if(strcmp($previousRecord, $value['staff_id']) != 0){
                // to cater the first row
                if($rowcount == 2){
                    $exemptionCount = $projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count'];
                    /* if($exemptionCount > 30){ //restriction to max 30
                        $exemptionCount = 30;
                    }*/
                    for($i = 1; $i <= $exemptionCount; $i++){
                        $previousCellColumn++;
                        $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, 'EXE');
                        $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'startColor' => array( 'rgb' => 'FFFF99' )));
                    } 
                    $rowCountExcel++;
                }
                elseif($rowcount >=3){
                    $exemptionCount =  $projectsCount[$rowcount-2]['no_of_exemption'] - $projectsCount[$rowcount-2]['project_count'];

                    /* if($exemptionCount > 30){ //restriction to max 30
                        $exemptionCount = 30;
                    }*/
                    for($i = 1; $i <= $exemptionCount; $i++){
                        $previousCellColumn++;
                        $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, 'EXE');
                        $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'startColor' => array( 'rgb' => 'FFFF99' )));
                    }
                    $rowCountExcel++;
                }
                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCountExcel, $rowcount); //No.
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCountExcel, $value['staff_name']);
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCountExcel, ($projectsCount[$rowcount-1]['no_of_exemption'] - $projectsCount[$rowcount-1]['project_count']));
                $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCountExcel, $value['project_id']);
                $objPHPExcel->getActiveSheet()->getStyle('D'.$rowCountExcel)->getFill()->applyFromArray(array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startColor' => array( 'rgb' => 'CCFFCC' )));
                $previousRecord = $value['staff_id'];
                $rowcount++;
                $count++;
                $staffProjectCount = 0; 
                $previousCellColumn = 'D';
            }

              // to close off the last row
              if($count == count($projects)){
                     $exemptionCount = $projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count'];
                     /* if($exemptionCount > 30){ //restriction to max 30
                            $exemptionCount = 30;
                     }*/
                    for($i = 1; $i <= $exemptionCount; $i++){
                        $previousCellColumn++;
                         $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, 'EXE');
                        $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'startColor' => array( 'rgb' => 'FFFF99' )));
                    }
              }

        }
            
        elseif($rowcount == 1){
            $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCountExcel, $rowcount); //No.
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCountExcel, $value['staff_name']);
            $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCountExcel, ($projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count']));
            $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCountExcel, $value['project_id']);
            $objPHPExcel->getActiveSheet()->getStyle('D'.$rowCountExcel)->getFill()->applyFromArray(array(
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            //'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'startColor' => array( 'rgb' => 'CCFFCC' )));
            $rowcount++;
            $count++;
            $staffProjectCount++;
            $previousRecord = $value['staff_id'];
            $previousCellColumn = 'D';
        }
    }
    //Autosize Sheet 1
    autosize_currentSheet();

    //Sheet 2 - After Allocation
    //Create Header
    $objPHPExcel->createSheet();
    $objPHPExcel->setActiveSheetIndex(1);
    $objPHPExcel->getActiveSheet()->setTitle('AfterAllocation');
    $headers = ['No.', 'Staff Name', 'EXE'];
    $objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
    $objPHPExcel->getActiveSheet()->getStyle('A1:C1')->getFont()->setBold(true);
    $maxColumn = getMaxColumnCount();
    $previousHeaderColumn = 'C';
    for($i=1; $i<= $maxColumn; $i++){
        $previousHeaderColumn++;
        $objPHPExcel->getActiveSheet()->SetCellValue($previousHeaderColumn.'1', 'Proj'.$i);
        $objPHPExcel->getActiveSheet()->getStyle($previousHeaderColumn.'1')->getFont()->setBold(true);
    }

    $rowCountExcel = 2; 
    $exemptionCount = 0;
    $staffProjectCount = 0;
    $rowcount = 1;
    $count = 0;
    $previousRecord;
    $previousCellColumn;
    $details = "";
    foreach($projects as $value){
        if($rowcount > 1){
        // when the staffid is the same as the previous record
            if(strcmp($previousRecord, $value['staff_id']) == 0){
                $previousCellColumn++;
                $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, $value['project_id']);
                $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startColor' => array( 'rgb' => 'CCFFCC' )));
                $previousRecord = $value['staff_id'];
                $count++;
                $staffProjectCount++; 
            }
            if(strcmp($previousRecord, $value['staff_id']) != 0){
                // to cater the first row
                if($rowcount == 2){
                    $exemptionCount = $projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count'];
                    /* if($exemptionCount > 30){ //restriction to max 30
                        $exemptionCount = 30;
                    }*/
                    for($i = 1; $i <= $exemptionCount; $i++){
                        $previousCellColumn++;
                        $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, 'EXE');
                        $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'startColor' => array( 'rgb' => 'FFFF99' )));
                    } 
                    foreach($examiningProjects as $examiner){
                        if(strcmp($examiner['examinerid'], $previousRecord) == 0){
                            $previousCellColumn++;
                             $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, $examiner['project_id']);
                        }
                    }
                    $rowCountExcel++;
                }
                elseif($rowcount >=3){
                    $exemptionCount =  $projectsCount[$rowcount-2]['no_of_exemption'] - $projectsCount[$rowcount-2]['project_count'];

                    /* if($exemptionCount > 30){ //restriction to max 30
                        $exemptionCount = 30;
                    }*/
                    for($i = 1; $i <= $exemptionCount; $i++){
                        $previousCellColumn++;
                        $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, 'EXE');
                        $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'startColor' => array( 'rgb' => 'FFFF99' )));
                    }
                    foreach($examiningProjects as $examiner){
                        if(strcmp($examiner['examinerid'], $previousRecord) == 0){
                            $previousCellColumn++;
                             $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, $examiner['project_id']);
                        }
                    }
                    $rowCountExcel++;
                }
                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCountExcel, $rowcount); //No.
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCountExcel, $value['staff_name']);
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCountExcel, ($projectsCount[$rowcount-1]['no_of_exemption'] - $projectsCount[$rowcount-1]['project_count']));
                $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCountExcel, $value['project_id']);
                $objPHPExcel->getActiveSheet()->getStyle('D'.$rowCountExcel)->getFill()->applyFromArray(array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startColor' => array( 'rgb' => 'CCFFCC' )));
                $previousRecord = $value['staff_id'];
                $rowcount++;
                $count++;
                $staffProjectCount = 0; 
                $previousCellColumn = 'D';
            }

              // to close off the last row
              if($count == count($projects)){
                     $exemptionCount = $projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count'];
                     /* if($exemptionCount > 30){ //restriction to max 30
                            $exemptionCount = 30;
                     }*/
                    for($i = 1; $i <= $exemptionCount; $i++){
                        $previousCellColumn++;
                         $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, 'EXE');
                        $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'startColor' => array( 'rgb' => 'FFFF99' )));
                    }
                    foreach($examiningProjects as $examiner){
                        if(strcmp($examiner['examinerid'], $previousRecord) == 0){
                            $previousCellColumn++;
                             $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, $examiner['project_id']);
                        }
                    }
              }

        }
            
        elseif($rowcount == 1){
            $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCountExcel, $rowcount); //No.
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCountExcel, $value['staff_name']);
            $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCountExcel, ($projectsCount[0]['no_of_exemption'] - $projectsCount[0]['project_count']));
            $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCountExcel, $value['project_id']);
            $objPHPExcel->getActiveSheet()->getStyle('D'.$rowCountExcel)->getFill()->applyFromArray(array(
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            //'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'startColor' => array( 'rgb' => 'CCFFCC' )));
            $rowcount++;
            $count++;
            $staffProjectCount++;
            $previousRecord = $value['staff_id'];
            $previousCellColumn = 'D';

            if($count == count($projects)){
                $exemptionCount =  $projectsCount[$rowcount-2]['no_of_exemption'] - $projectsCount[$rowcount-2]['project_count'];
                for($i = 1; $i <= $exemptionCount; $i++){
                        $previousCellColumn++;
                         $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, 'EXE');
                        $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'startColor' => array( 'rgb' => 'FFFF99' )));
                    }
                foreach($examiningProjects as $examiner){
                    if(strcmp($examiner['examinerid'], $previousRecord) == 0){
                        $previousCellColumn++;
                         $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, $examiner['project_id']);
                    }
                }

            }
        }
    }
    //Autosize Sheet 2
    autosize_currentSheet();
    //Switch back to active sheet
    $objPHPExcel->setActiveSheetIndex(0);
    
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
    header('Pragma: public'); 
    ob_clean();
    flush();
    readfile($FILENAME);
    $conn_db_ntu = null;
    exit;
?>