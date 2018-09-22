<?php require_once('../../../Connections/db_ntu.php');
	  require_once('./entity.php'); 
	  require_once('../../../CSRFProtection.php');
	  require_once('../../../Utility.php');?>

<?php
	$csrf = new CSRFProtection(); 
	
	/* Converting DB to Object Models */
	$staffList = array();
	$projectList = array();
	$unallocated_projects = array();
	
	$query_rsSettings 	= "SELECT * FROM ".$TABLES['allocation_settings_general']." as g";
	$query_rsLimit	  	= "SELECT max(day) as days, max(slot) as slots, max(room) as rooms FROM ".$TABLES['allocation_result'];
	//$query_rsRoom		= "SELECT * FROM ".$TABLES['allocation_result_room']." ORDER BY `id` ASC";
	$query_rsDay 		= "SELECT count(*) as number_of_days FROM ".$TABLES['allocation_settings_general']. " WHERE opt_out = 0";
	$query_rsTimeslot  	= "SELECT * FROM ".$TABLES['allocation_result_timeslot']." ORDER BY `id` ASC";
	$query_rsStaff		= "SELECT s.id as staffid, s.name as staffname, s.position as salutation FROM ".$TABLES['staff']." as s";
	$query_rsProject 	= "SELECT r.project_id as pno, p.staff_id as staffid, r.examiner_id as examinerid, r.day as day, r.slot as slot, r.room as room FROM ".$TABLES['allocation_result']." as r LEFT JOIN ".$TABLES['fyp_assign']." as p ON r.project_id = p.project_id";
	
	try
	{
		$settings 	= $conn_db_ntu->query($query_rsSettings)->fetchAll();
		$limits		= $conn_db_ntu->query($query_rsLimit)->fetch();
		//$rooms		= $conn_db_ntu->query($query_rsRoom);
		$rsDay		= $conn_db_ntu->query($query_rsDay)->fetch();
		$timeslots	= $conn_db_ntu->query($query_rsTimeslot);
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
		$startDate 		 	= DateTime::createFromFormat('Y-m-d', $settings[0]['alloc_date']);
	}
	catch(Exception $e)
	{
		//Default Values
		$startDate 			= new DateTime();
	}
	
	//Prepare headers
	$NO_OF_DAYS = $rsDay['number_of_days'];
	
	for($day=1; $day<=$NO_OF_DAYS; $day++) {
		$timeslots_table[$day] = array();
	}
	$maxslots = 0;
	foreach ($timeslots as $timeslot)
	{
		$timeslots_table[ $timeslot['day'] ][ $timeslot['slot'] ] 	= new Timeslot( $timeslot['id'],
																					$timeslot['day'],
																					$timeslot['slot'],
																					DateTime::createFromFormat('H:i:s', $timeslot['time_start']), 
																					DateTime::createFromFormat('H:i:s', $timeslot['time_end']));
	}
	
	//Recalculate limit of timeslots
	if (isset($timeslots_table))
	{
		foreach ($timeslots_table as $timeslots) {
			if (count($timeslots) > $maxslots)
				$maxslots = count($timeslots);
		}
	}
	
	//change to dynamic
	//$rooms_table = array();
	//foreach($rooms as $room)
	//{
	//	$rooms_table[ $room['id'] ] = new Room(	$room['id'],
	//											$room['roomName']);
	//}
	
	//Init Timetable
	$NO_OF_DAYS 		= $limits['days'];
	$NO_OF_TIMESLOTS 	= $maxslots;
	$overallTimeTable = array();
	for($dayIndex = 0; $dayIndex < $NO_OF_DAYS; $dayIndex++){
		   
			$actualDay = $dayIndex+1;
			$optOut = $settings[$dayIndex]["opt_out"] ;
			if ($optOut == 0) {
				$rooms_table = retrieveRooms($actualDay, "allocation_result_room");
				$NO_OF_ROOMS = count($rooms_table);
				$timetable = initTimeTableArray($actualDay, $NO_OF_ROOMS, $NO_OF_TIMESLOTS);
		    
				for($room = 0; $room < $NO_OF_ROOMS; $room++) {
						for($timeslot = 0; $timeslot < $NO_OF_TIMESLOTS; $timeslot++) {
							$timetable[$day][$room][$timeslot] = [];
							
						}
				}
			}
	}			
	if ($projects->rowCount() == 0 || $staffs->rowCount() == 0){
		$error_code = 1;
		//echo "<p class='warn'>[Error] Problem loading staff/project list.</p>";
	}
	else {
	
	
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

			$projectList [ $project['pno'] ]->assignTimeslot($project['day'],
															 $project['room'],
															 $project['slot']);
															 
			//Regenerate Timetable
			if ($projectList [ $project['pno'] ]->hasValidTimeSlot())
			{
				//Offset index
				$day  = $projectList [ $project['pno'] ]->getAssigned_Day()-1;
				$room = $projectList [ $project['pno'] ]->getAssigned_Room()-1;
				$slot = $projectList [ $project['pno'] ]->getAssigned_Time()-1;
				
				$timetable[$day][$room][$slot][] = $projectList [ $project['pno'] ];
				$overallTimeTable[$day] = $timetable;
				
			}
			else
				{
				$unallocated_projects[] = $projectList [ $project['pno'] ];
				}
			}
			
	}
	
	
	function initTimeTableArray($day, $NO_OF_ROOMS, $MAX_SLOTS) {
		$timetable= array_fill(0, $day,array_fill(0, $NO_OF_ROOMS, array_fill(0, $MAX_SLOTS, NULL)));
		return $timetable;
	}	
	function getTimeslotHeader($d, $s)
	{
		global $timeslots_table;
		if ($d == null || $d == -1 || $s === null || $s == -1 || !array_key_exists($d, $timeslots_table) || !array_key_exists($s, $timeslots_table[$d])) return "Slot $s";
		return $timeslots_table[$d][$s]->toString();
	}
	
	function getRoomHeader($s)
	{
		global $rooms_table;
		$s= $s-1;
		if ($s === null || $s == -1 || !array_key_exists($s, $rooms_table)) return "Room $s";
		return $rooms_table[$s]->toString();
	}
	
	
	function getActualDate($day)
	{
		global $startDate;
		
		if ($day === null || $day == -1) return "-";
		
		$calculatedDate = clone $startDate;
		$day_interval	= new DateInterval('P'.($day-1).'D');	//Offset -1 because day 1 falls on startDate
		$calculatedDate->add($day_interval);
		
		return $calculatedDate->format('d/m/Y');
	}

?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Allocation Timetable</title>
	<?php require_once('../../../head.php'); ?>
	<style>
	.clash_td {
		background: #FFFF00;
		color: #FF0000;
		font-weight: bold;
	}
	</style>
</head>

<body>
<div id="bar"></div>
	<div id="wrapper">
		<div id="header"></div>
		
		<div id="left">
			<div id="nav">
				<?php require_once('../../nav.php'); ?>
			</div>
		</div>
		
		<div id="logout">
				<a href="../../../logout.php"><img src="../../../images/logout.jpg" /></a>
		</div>
      
		<!-- InstanceBeginEditable name="Content" -->
		<div id="content">
			<h1>Examiner Allocation Timetable Plan for Full Time Projects</h1>
			<br/>
			<div id="topcon">
				<div style="float:left;">
					<a href="allocation.php" class="bt" style="width:130px;" title="< Back to Allocations">&#60;&#60; Back to Allocations</a>
					<a href="submit_download_timetable.php" class="bt" style="width:125px;" title="Download Timetable">Download Timetable</a>
					
				</div>
			</div>
			<br/>
			<br/>
			<?php
			
				for($dayIndex = 0; $dayIndex  < $NO_OF_DAYS; $dayIndex ++)  {
					$actualDay = $dayIndex+1;
					$optOut = $settings[$dayIndex]["opt_out"] ;
					if ($optOut == 0) {
						$timetable = $overallTimeTable[$dayIndex ];
					
						$rooms_table = retrieveRooms($actualDay, "allocation_result_room");
						$NO_OF_ROOMS = count($rooms_table);
						echo 	'<br/><h3>Day '.($actualDay).' - '.getActualDate($actualDay).'</h3><br/>';
						echo 	'<table border=1 >';
						echo	'<tr>';
						
						//Header
						echo '<tr class="heading">';
						echo '<th style="width:100px" ></th>';
						for($room = 0; $room < $NO_OF_ROOMS; $room++)
						{
							echo '<th width="85">'.getRoomHeader($room+1).'</th>';
						}
						echo '</tr>';
					
						//Body
						for($timeslot = 0; $timeslot < count($timeslots_table[$actualDay]); $timeslot++)
						{
							echo '<tr>';
							echo '<td style="width:100px">'.getTimeslotHeader($actualDay, $timeslot+1).'</td>';
							for($room = 0; $room < $NO_OF_ROOMS; $room++)
							{
								$cur_data = $timetable[$dayIndex][$room][$timeslot];
								//echo "<br>";
								//echo "timetable";
								//var_dump ($cur_data);
								//echo "<br>";
								$cell_info = [];
								$details = "";
								if ($cur_data != null) {
								foreach ($cur_data as $data) {
									if ($data !== null)
									{
										//$details = 	"Supervisor : ". $timetable[$dayIndex][$room][$timeslot]->getStaff()."\nExaminer: ". $timetable[$dayIndex][$room][$timeslot]->getExaminer();
										$supervisor = $data->getStaff();
										if (array_key_exists($supervisor, $staffList)) {
											$supervisor = $staffList[ $supervisor ]->toString();
										}
										$examiner = $data->getExaminer();
										if (array_key_exists($examiner, $staffList)) {
											$examiner = $staffList[ $examiner ]->toString();
										}
									$details = 	"Supervisor : ". $supervisor."\nExaminer: ". $examiner;
									}	
									else {
										$details = "";
									}
								$cell_info[] = '<a href="allocation_edit.php?project='.$data->getID().'">'.$data->getID()."</a>";
								}
							}
							if (count($cell_info) > 1)		//Clashing projects
							{
								echo '<td class="clash_td" title="[Warning] Clash Detected">';
							}
							else
								echo '<td title="'.$details.'">';
							
								if (count($cell_info) > 0) {
									echo implode("\n", $cell_info);
								}
								else {
									echo '-';
								}
								echo '</td>';
							
								unset($cell_info);
							}
							echo '</tr>';
						}
						echo '</table><br/>';
					}
				}

		
				
				//Unallocated Projects
				if (count($unallocated_projects) > 0)
				{
					echo "<br/><h4> Unallocated Projects </h4>"; 
					//echo "Projects Allocated: " . intval($assignedProjects). " / " . count($projectList) . "<br/>";
					$i = 0;
					foreach ($unallocated_projects as $project)
					{
						echo (++$i).') <a href="allocation_edit.php?project='.$project->getID().'">'.$project->getID().'</a><br/>';
					}
				}
				//Statistics
				/*$assignedProjects = 0;
				echo "<h4> Unallocated Projects </h4>";
				foreach ($projectList as $project)
				{
					if ($project->isAssignedTimeslot() && $project->hasValidTimeSlot())
						$assignedProjects++;
					else
						echo $project->getID()."<br/>";
				}
				
				echo "<h4> Assignment Statistics </h4>";
				echo "<p>";
				echo "Projects Allocated: " . intval($assignedProjects). " / " . count($projectList) . "<br/>";
				echo "</p>";*/
			?>
		</div>
		
		
		<?php require_once('../../../footer.php'); ?>
	</div>
</body>

</html>

<?php
	unset($limits);
	unset($rooms);
	unset($timeslots);
	
	unset($staffs);
	unset($projects);
	
	unset($staffList);
	unset($projectList);
	
	unset($timetable);
	
	unset($rooms_table);
	unset($timeslots_table);
	$conn_db_ntu = null;
?>