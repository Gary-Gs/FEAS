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
	$filter_ProjectSem = $_POST["filter_ProjectSem"];
	$filter_ProjectYear = $_POST["filter_ProjectYear"];

	/*
	if(isset($_REQUEST['filter_ProjectSem']) && !empty($_REQUEST['filter_ProjectSem'])){
            $filter_ProjectSem = $_REQUEST['filter_ProjectSem'];
    }
    if(isset($_REQUEST['filter_ProjectYear']) && !empty($_REQUEST['filter_ProjectYear'])){
            $filter_ProjectYear = $_REQUEST['filter_ProjectYear'];
    }
	*/
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
        if(is_null($value['examinerid'])){
            $count++;
        }

        else{
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

                    foreach($projectsCount as $countprojects){
                          if(strcmp($countprojects['staff_id'], $previousRecord) == 0){
                                $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                
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
                    $rowCountExcel++;


                   
                    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCountExcel, $rowcount); //No.
                    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCountExcel, $value['staff_name']);
                    foreach($projectsCount as $countprojects){
                    if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                            $exemptionCount = ($countprojects['no_of_exemption'] - $countprojects['project_count']);
                        }
                    }
                    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCountExcel, $exemptionCount);
                    $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCountExcel, $value['project_id']);
                    $objPHPExcel->getActiveSheet()->getStyle('D'.$rowCountExcel)->getFill()->applyFromArray(array(
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startColor' => array( 'rgb' => 'CCFFCC' )));

                    $previousCellColumn = 'D';

                    if($rowcount == count($supervisingProjectsCount)){
                        foreach($projectsCount as $countprojects){
                            if(strcmp($countprojects['staff_id'], $value['staff_id']) == 0){
                                $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                            }
                        }
                        for($i = 1; $i <= $exemptionCount; $i++){
                            $previousCellColumn++;
                             $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, 'EXE');
                            $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                            'startColor' => array( 'rgb' => 'FFFF99' )));
                        }
                    }

                    
                    $previousRecord = $value['staff_id'];
                    $rowcount++;
                    $count++;
                    $staffProjectCount = 0; 
                    




                }

                  

            }
                
            elseif($rowcount == 1){
                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCountExcel, $rowcount); //No.
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCountExcel, $value['staff_name']);
                foreach($projectsCount as $countprojects){
                    if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                        $exemptionCount = ($countprojects['no_of_exemption'] - $countprojects['project_count']);
                    }
                }
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCountExcel, $exemptionCount);
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
    $exemptionList = array();
    foreach($projects as $value){
        if((!is_null($value['examinerid'])) && (is_null($value['supervisor_name']))){
            if($rowcount > 1){
                if(strcmp($previousRecord, $value['staff_id']) == 0){
                    
                    $previousCellColumn++;
                    $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, $value['project_id']);
                    $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startColor' => array( 'rgb' => 'CCFFCC' )));
                    $count++;
                    $staffProjectCount++;
                    $previousRecord = $value['staff_id'];

                }

                if(strcmp($previousRecord, $value['staff_id']) != 0){

                     if(in_array($previousRecord, $exemptionList) == false){

                        foreach($projectsCount as $countprojects){
                            if(strcmp($previousRecord, $countprojects['staff_id']) == 0){
                                $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                
                                for($i = 1; $i <= $exemptionCount; $i++){
                                    $previousCellColumn++;
                                    $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, 'EXE');
                                    $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                    'startColor' => array( 'rgb' => 'FFFF99' )));
                                }

                                $exemptionList[$rowcount] = $previousRecord;
                                  
                            }
                        }

                        
                    }

                    $rowCountExcel++;

                    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCountExcel, $rowcount); //No.
                    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCountExcel, $value['staff_name']);
                    foreach($projectsCount as $countprojects){
                        if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                                $exemptionCount = ($countprojects['no_of_exemption'] - $countprojects['project_count']);
                            }
                    }
                    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCountExcel, $exemptionCount);
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

                if($count == count($projects)){
                    foreach($projectsCount as $countprojects){
                        $exemptionCount = ($countprojects['no_of_exemption'] - $countprojects['project_count']);
                        for($i = 1; $i <= $exemptionCount; $i++){
                            echo '<td width="65px" bgcolor="yellow">EXE</td>';
                        }

                    }
                    $exemptionList[$rowcount] = $previousRecord;
                }


            }
            elseif($rowcount == 1){
                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCountExcel, $rowcount); //No.
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCountExcel, $value['staff_name']);
                foreach($projectsCount as $countprojects){
                    if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                            $exemptionCount = ($countprojects['no_of_exemption'] - $countprojects['project_count']);
                        }
                }
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCountExcel, $exemptionCount);
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
                     if(strcmp($value['staff_id'], $countprojects['staff_id']) == 0){
                        $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                        
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
            }
        }

        elseif(is_null($value['examinerid']) && !(is_null($value['supervisor_name']))){
            if($rowcount>1){
                if(strcmp($previousRecord, $value['staff_id']) == 0){

                    if(in_array($previousRecord, $exemptionList) == false){

                        foreach($projectsCount as $countprojects){
                            if(strcmp($previousRecord, $countprojects['staff_id']) == 0){
                                $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                
                                for($i = 1; $i <= $exemptionCount; $i++){
                                    $previousCellColumn++;
                                    $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, 'EXE');
                                    $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                    'startColor' => array( 'rgb' => 'FFFF99' )));
                                }

                                $exemptionList[$rowcount] = $previousRecord;
                                  
                            }
                        }

                        
                    }
                    $previousCellColumn++;
                    $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, $value['project_id']);
                    $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startColor' => array( 'rgb' => 'FFFFFF' )));
                    $count++;
                    $staffProjectCount++;
                    $previousRecord = $value['staff_id'];

                }

                if(strcmp($previousRecord, $value['staff_id']) != 0){


                    if(in_array($previousRecord, $exemptionList) == false){
                        foreach($projectsCount as $countprojects){
                            if(strcmp($previousRecord, $countprojects['staff_id']) == 0){
                                if($countprojects['examining_project'] == 0){
                                    $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                
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
                        }
                        $exemptionList[$rowcount] = $previousRecord;
                    }


                    $rowCountExcel++;
                    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCountExcel, $rowcount); //No.
                    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCountExcel, $value['staff_name']);
                    foreach($projectsCount as $countprojects){
                        if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                                $exemptionCount = ($countprojects['no_of_exemption'] - $countprojects['project_count']);
                            }
                    }
                    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCountExcel, $exemptionCount);
                    $previousCellColumn = 'C';

                    //this could be the reason 
                    if(in_array($value['staff_id'], $exemptionList) == false){
                    foreach($projectsCount as $countprojects){
                          if(strcmp($value['staff_id'], $countprojects['staff_id']) == 0){
                                $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                            
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

                    $exemptionList[$rowcount] = $value['staff_id'];

                }

                $previousCellColumn++;
                $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, $value['project_id']);
                $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startColor' => array( 'rgb' => 'FFFFFF' )));
                $count++;
                $rowcount++;
                $staffProjectCount++;
                $previousRecord = $value['staff_id'];

                }


            }
            elseif($rowcount == 1){

                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCountExcel, $rowcount); //No.
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCountExcel, $value['staff_name']);
                foreach($projectsCount as $countprojects){
                    if(strcmp($countprojects['staff_name'], $value['staff_name']) == 0){
                            $exemptionCount = ($countprojects['no_of_exemption'] - $countprojects['project_count']);
                        }
                }
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCountExcel, $exemptionCount);

                $previousCellColumn = 'C';

                if(in_array($value['staff_id'], $exemptionList) == false){
                    foreach($projectsCount as $countprojects){
                        if(strcmp($value['staff_id'], $countprojects['staff_id']) == 0){
                             
                                $exemptionCount = $countprojects['no_of_exemption'] - $countprojects['project_count'];
                                
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
                    $exemptionList[$rowcount] = $value['staff_id'];
                }
                $previousCellColumn++;
                $objPHPExcel->getActiveSheet()->SetCellValue($previousCellColumn.$rowCountExcel, $value['project_id']);
                $objPHPExcel->getActiveSheet()->getStyle($previousCellColumn.$rowCountExcel)->getFill()->applyFromArray(array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                //'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startColor' => array( 'rgb' => 'FFFFFF' )));
                $rowcount++;
                $count++;
                $staffProjectCount++;
                $previousRecord = $value['staff_id'];              

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