<?php require_once('../../../Connections/db_ntu.php');
	  require_once('./entity.php');  
	  //require_once('../../../PHPExcel.php');
      require_once('../../../CSRFProtection.php');
	  require_once ('../../../vendor/autoload.php');?>

<?php
	
	$csrf = new CSRFProtection();
	
	$FILENAME = "AllocationOutput_" . date('d_M_Y'). ".xlsx";
	
	/* Prepare data from database */
	$staffList = array();
	$projectList = array();
	$unallocated_projects = array();
	
	$query_rsSettings 	= "SELECT * FROM ".$TABLES['allocation_settings_general_part_time']." as g";
	//$query_rsRoom		= "SELECT * FROM ".$TABLES['allocation_result_room_part_time']." ORDER BY `id` ASC";
	//$query_rsDay 		= "SELECT max(`day`) as day FROM ".$TABLES['allocation_result_timeslot_part_time'];
	$query_rsDay 		= "SELECT count(*) as number_of_days FROM ".$TABLES['allocation_settings_general_part_time']. " WHERE opt_out = 0";
	$query_rsTimeslot  	= "SELECT * FROM ".$TABLES['allocation_result_timeslot_part_time']." ORDER BY `id` ASC";
	$query_rsStaff		= "SELECT s.id as staffid, s.name as staffname, s.position as salutation FROM ".$TABLES['staff']." as s";
	$query_rsProject = "SELECT r.project_id as pno, p.staff_id as staffid, r.examiner_id as examinerid, r.day as day, r.slot as slot, r.room as room FROM ".$TABLES['allocation_result_part_time']." as r LEFT JOIN ".$TABLES['fyp_assign_part_time']." as p ON r.project_id = p.project_id";

	try
	{
		$settings 	= $conn_db_ntu->query($query_rsSettings)->fetch();
		//$rooms		= $conn_db_ntu->query($query_rsRoom);
		$timeslots	= $conn_db_ntu->query($query_rsTimeslot);
		$rsDay		= $conn_db_ntu->query($query_rsDay)->fetch();
		$staffs		= $conn_db_ntu->query($query_rsStaff);
		$projects 	= $conn_db_ntu->query($query_rsProject);
	}
	catch (PDOException $e)
	{
		die($e->getMessage());
	}
	
	//Parse Alloc Date
	try
	{
		$startDate 		 	= DateTime::createFromFormat('Y-m-d', $settings['alloc_date']);
	}
	catch(Exception $e)
	{
		//Default Values
		$startDate 			= new DateTime();
	}
	
	//Timeslots
	$NO_OF_DAYS = $rsDay['number_of_days'];
	for($day=1; $day<=$NO_OF_DAYS; $day++)
		$timeslots_table[$day] = array();

	foreach ($timeslots as $timeslot)
	{
		$timeslots_table[ $timeslot['day'] ][ $timeslot['slot'] ] 	= new Timeslot( $timeslot['id'],
																					$timeslot['day'],
																					$timeslot['slot'],
																					DateTime::createFromFormat('H:i:s', $timeslot['time_start']), 
																					DateTime::createFromFormat('H:i:s', $timeslot['time_end']));													  
	}
	
	//Rooms
	$rooms_table = array();
	//foreach($rooms as $room)
	//{
	//	$rooms_table[ $room['id'] ] = new Room(	$room['id'],
	//											$room['roomName']);
	//}
	
	//Staff
	foreach($staffs as $staff) { //Index Staff by staffid
		$staffList[ $staff['staffid'] ] = new Staff($staff['staffid'],
													$staff['salutation'],
													$staff['staffname']);
	}
	
	//Projects
	foreach($projects as $project) { //Index Project By pno
		$projectList[ $project['pno'] ] = new Project(	$project['pno'], 
														$project['staffid'],
														$project['examinerid'],
														'-' );
														
		$projectList [ $project['pno'] ]->assignTimeslot( $project['day'],
														  $project['room'],
														  $project['slot']);
	}
	
	//Unallocated Projects
	foreach($projectList as $project) {
		if ( !$project->hasValidTimeSlot() && array_key_exists ($project->getID(), $projectList) )
		{
			$unallocated_projects[] = $projectList [ $project->getID() ];
		}
	}
	
	

	/* Write to Excel */
	//$objPHPExcel = new PHPExcel();
	$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	//Set properties
	
	
	
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
	
	function getDay($day)
	{
		global $startDate;
		
		if ($day === null || $day == -1) return "-";
		
		$calculatedDate = clone $startDate;
		$day_interval	= new DateInterval('P'.($day-1).'D');	//Offset -1 because day 1 falls on startDate
		$calculatedDate->add($day_interval);
		
		return $calculatedDate->format('d/m/Y');
	}
	
	//Default Styles
	$objPHPExcel->getDefaultStyle()	->getFont()
									->setName('Arial')
									->setSize(10);
	
	//Sheet 1 - Projects Allocated
	//Create Header
	$objPHPExcel->createSheet();
	$objPHPExcel->setActiveSheetIndex(0);
	$objPHPExcel->getActiveSheet()->setTitle('ProjectsAllocated');
	
	$headers = ['SNo.', 'Project Code', 'Supervisor', 'Sup Network A/C', 'Examiner', 'Examiner Network A/C', 'Room Number', 'Day No', 'Date', 'Timeslot'];
	$objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
	$objPHPExcel->getActiveSheet()->getStyle('A1:J1')->getFont()->setBold(true);
	
	$rowCount = 2;	//First Data Row, excluding header
	foreach ($projectList as $project)
	{
		if ( !$project->hasValidTimeSlot() ) continue;

		$cur_supervisor = $project->getStaff();
		$cur_supervisor_name = $cur_supervisor;
		
		if ( array_key_exists($cur_supervisor, $staffList) )
			$cur_supervisor_name = $staffList[ $cur_supervisor ]->getName();
		
		$cur_examiner = $project->getExaminer();
		$cur_examiner_name = $cur_examiner;
		
		if ( array_key_exists($cur_examiner, $staffList) )
			$cur_examiner_name = $staffList[ $cur_examiner ]->getName();
		
		
		
		$cur_day = $project->getAssigned_Day();
		$cur_slot = $project->getAssigned_Time();
		if ( array_key_exists($cur_day, $timeslots_table) && array_key_exists($cur_slot, $timeslots_table[$cur_day]) )
			$cur_slot = $timeslots_table[ $cur_day ][ $cur_slot ]->toExcelString();
		else
			$cur_slot = '-';
		
		if ($cur_day <= 0)
		{
			$cur_day = '-';
			$cur_date = '-';
		}
		else {
			$cur_date = getDay($cur_day);
		}
		
		
		$rooms_table = retrieveRooms ($cur_day, "allocation_result_room_part_time");
		if (!isset ($rooms_table)) {

			echo "room table null";
			exit;
		}
		$cur_room = $project->getAssigned_Room();
		$curIndex = $cur_room -1;
		if ( array_key_exists($curIndex , $rooms_table) ) {
			
			$cur_room = $rooms_table[$curIndex]->toString();
			
		}
		else {
			$cur_room = '-';
		}
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $rowCount-1);					//SNo
		$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $project->getID());				//Project Code
		$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $cur_supervisor_name);			//Supervisor
		$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $cur_supervisor);				//Supervisor Network Account
		$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, $cur_examiner_name);			//Examiner
		$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, $cur_examiner);					//Examiner
		$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, $cur_room);						//Room Number
		$objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, $cur_day);						//Day No
		$objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, $cur_date);						//Date
		$objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount, $cur_slot);						//Timeslot
		
		if (($rowCount%2) == 0)	//Even Rows
			cellColor('A'.$rowCount.':J'.$rowCount, 'FFFF99');
		else
			cellColor('A'.$rowCount.':J'.$rowCount, 'CCFFCC');
		
		$rowCount++;
	}

	//Autosize Sheet 1
	autosize_currentSheet();
	
	//Sheet 2 - Projects UnAllocated
	//Create Header
	$objPHPExcel->setActiveSheetIndex(1);
	$objPHPExcel->getActiveSheet()->setTitle('ProjectsUnallocated');
	
	$headers = ['SNo.', 'Project Code', 'Supervisor', 'Sup Network A/C', 'Examiner', 'Examiner Network A/C'];
	$objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
	$objPHPExcel->getActiveSheet()->getStyle('A1:F1')->getFont()->setBold(true);
	
	$rowCount = 2;	//First Data Row, excluding header
	foreach ($unallocated_projects as $project)
	{
		$cur_supervisor = $project->getStaff();
		$cur_supervisor_name = $cur_supervisor;
		if ( array_key_exists($cur_supervisor, $staffList) )
			$cur_supervisor_name = $staffList[ $cur_supervisor ]->getName();
		
		$cur_examiner = $project->getExaminer();
		$cur_examiner_name = $cur_examiner;
		if ( array_key_exists($cur_examiner, $staffList) )
			$cur_examiner_name = $staffList[ $cur_examiner ]->getName();
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $rowCount-1);					//SNo
		$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $project->getID());				//Project Code
		$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $cur_supervisor_name);			//Supervisor
		$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $cur_supervisor);				//Supervisor Network Account
		$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, $cur_examiner_name);			//Examiner
		$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, $cur_examiner);					//Examiner Network Account
		
		if (($rowCount%2) == 0)	//Even Rows
			cellColor('A'.$rowCount.':F'.$rowCount, 'FFFF99');
		else
			cellColor('A'.$rowCount.':F'.$rowCount, 'CCFFCC');
		
		$rowCount++;
	}

	//Autosize Sheet 2
	autosize_currentSheet();
	
	//Switch back to active sheet
	$objPHPExcel->setActiveSheetIndex(0);
	
	// Save Excel 2007 file
	//$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
	$objWriter =  \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, "Xlsx");
	$objWriter->save($FILENAME);
	
	//Download File
	header('Content-disposition: attachment; filename='.$FILENAME);
	header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Length: ' . filesize($FILENAME));
	header('Content-Transfer-Encoding: binary');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	ob_clean();
	flush(); 
	readfile($FILENAME);
	exit;
?>