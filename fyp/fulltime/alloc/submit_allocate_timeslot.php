<?php
require_once('../../../Connections/db_ntu.php');
require_once('./entity.php');   
require_once('../../../Utility.php');
	  
ini_set('max_execution_time', 600);
//redirect initialised as false first
$redirect = false;

$error_code = 0;

global $NO_OF_DAYS;
function CmpPriorityDesc($a, $b)
{
    //Exceptions First

    $a1 = count($a->timeslotException);
    $b1 = count($b->timeslotException);

    if ($a1 == $b1)
    {
        //Assignment Next
        $a2 = count($a->assignment_list);

        $b2 = count($b->assignment_list);

        if ($a2 == $b2)	{

            return 0;
        }
        else {
            return ($a2 < $b2) ? 1 : -1;
        }
    }
    else {
        return ($a1 < $b1) ? 1 : -1;
    }
}

$query_rsSettings		 = "SELECT * FROM ".$TABLES['allocation_settings_general']." as g";
$query_rsOtherSettings   = "SELECT * FROM ".$TABLES['allocation_settings_others']." as o WHERE type = 'FT'";

$query_rsStaff	    = "SELECT s.id as staffid, s.name as staffname, s.position as salutation FROM ".$TABLES['staff']." as s";
$query_rsProject    = "SELECT  r.project_id as pno, p.staff_id as staffid, r.examiner_id as examinerid FROM ".$TABLES['allocation_result']." as r LEFT JOIN ".$TABLES['fyp_assign']." as p ON r.project_id = p.project_id";

$query_rsExceptions = "SELECT * FROM ".$TABLES['fea_settings_availability']." as a";
try
{
    $settings 		= $conn_db_ntu->query($query_rsSettings)->fetchAll();
    $otherSettings 	= $conn_db_ntu->query($query_rsOtherSettings)->fetch();

    $staffs			= $conn_db_ntu->query($query_rsStaff)->fetchAll();
    $projects 		= $conn_db_ntu->query($query_rsProject)->fetchAll();
    $exceptions		= $conn_db_ntu->query($query_rsExceptions)->fetchAll();
}
catch (PDOException $e)
{
    die($e->getMessage());
}

/* Parse Settings */
//Default Values
/*$startTime = DateTime::createFromFormat('H:i:s', '08:30:00');
$endTime = DateTime::createFromFormat('H:i:s', '17:30:00');
$timeslotDuration = new DateInterval('PT30M');
$NO_OF_DAYS 		= 3;
$NO_OF_ROOMS 		= 8;
$NO_OF_TIMESLOTS 	= 16;*/

try
{
    $NO_OF_DAYS 		= $otherSettings['alloc_days'];
    //$NO_OF_DAYS 		= $settings['alloc_days'];
    //$startTime 		 	= DateTime::createFromFormat('H:i:s', $settings['alloc_start']);
    //$endTime 			= DateTime::createFromFormat('H:i:s', $settings['alloc_end']);
    //$timeslotDuration 	= new DateInterval('PT'.$settings['alloc_duration'].'M');
}
catch (Exception $e)
{


    //Default Values
    $NO_OF_DAYS 		= 3;

}

//$MAX_DAY_COMPRESSION = $NO_OF_DAYS-1;

$MAX_SLOTS = 0;
$NO_OF_TIMESLOTS = array();
$timeslots_table = array();
$count = 0;
for($dayIndex=0; $dayIndex<$NO_OF_DAYS; $dayIndex++)
{
    $timeslots_table[$dayIndex] = array();
    //Calculate Timeslot

    $startTime 		 	= DateTime::createFromFormat('H:i:s', $settings[$dayIndex]['alloc_start']);
    $endTime 			= DateTime::createFromFormat('H:i:s', $settings[$dayIndex]['alloc_end']);
    $timeslotDuration 	= new DateInterval('PT'.$settings[$dayIndex]['alloc_duration'].'M');

    $slot				= 0;
    for ($curTime=$startTime; $curTime < $endTime;)
    {
        $t1 = clone $curTime;
        $t2 = clone $curTime->add($timeslotDuration);

        foreach ($exceptions as $exception)
        {
            if ($exception['staff_id'] == '*' && ($exception['day'] == ($dayIndex+1) || $exception['day'] == '*'))	//Affect all
            {
                $exceptionStart	 	= DateTime::createFromFormat('H:i:s', $exception['time_start']);
                $exceptionEnd		= DateTime::createFromFormat('H:i:s', $exception['time_end']);

                if ($exceptionStart != null && $exceptionEnd != null)
                {
                    $exceptionStart_str = $exceptionStart->format('H:i:s');
                    $exceptionEnd_str = $exceptionEnd->format('H:i:s');

                    $t1_str = $t1->format('H:i:s');
                    $t2_str = $t2->format('H:i:s');

                    if ($t1_str >= $t2_str || $exceptionStart_str >= $exceptionEnd_str){ //Invalid Time Range (Ignore)
                        continue;
                    }

                    if ( ($t2_str <= $exceptionStart_str) ||
                         ($t1_str >= $exceptionEnd_str) ){
                        //Okay
                    }
                    else
                    {
                        //Collide
                        $curTime = clone $exceptionEnd;
                        $t1 = clone $curTime;
                        $t2 = clone $curTime->add($timeslotDuration);
                    }
                }
            }
        }

        if ($t1 >= $endTime) continue;

        $timeslots_table[$dayIndex][] = new Timeslot($count+1,$dayIndex+1,$slot+1,$t1,$t2);

        $slot++;
        $count++;
    }

    $NO_OF_TIMESLOTS[$dayIndex] = count($timeslots_table[$dayIndex]);

    if ( $NO_OF_TIMESLOTS[$dayIndex] > $MAX_SLOTS) {
        $MAX_SLOTS = $NO_OF_TIMESLOTS[$dayIndex];
    }

}

/* Converting DB to Object Models */
$staffList = array();
$projectList = array();
$roomsNewArray  = array();

$allocate_code =0;

// -----------------------------------------------------------------start of allocation---------------------------------------------------------------
if (count($projects) == 0 || count($staffs) == 0)
{
     $error_code = 1;
}
//else if($NO_OF_DAYS <= 0 || $MAX_SLOTS <= 0 || $NO_OF_ROOMS <= 0)
else if($NO_OF_DAYS <= 0 || $MAX_SLOTS <= 0 )
{
    $error_code = 2;
}
else
{
    // prepare staff list with respective time exceptions
    $staffList= indexStaff ($staffs, $exceptions);

    // prepare project list (sorted by supervising count >=4 , then from the rest sorted by examiner count) (to reduce movements)
    $projectList = indexProjects($staffList, $projects, $projectList);

    $overallTimeTable = array();

    // first round allocation (sequential)
    for($dayIndex =0;$dayIndex<$NO_OF_DAYS;$dayIndex++) {
        $optOut = $settings[$dayIndex]["opt_out"];
        if ($optOut ==0) {
        $projectList = allocateTimeSlotsByDay($dayIndex, $staffList,$projectList,$MAX_SLOTS, $NO_OF_TIMESLOTS);

        insertValuesIntoDB ($dayIndex, $projectList);
        $projectList = removeAssignedProjects ($projectList);
        }
    }

    // second round allocation (by remaining vacancies)
     if (count($projectList)>0) {
        //find day index of vacancy day
         $vacantDay = null;
         for ($i=0; $i<$NO_OF_DAYS && $vacantDay==null;$i++) {
             $currentSlot = array();
             $roomSlot = 0;
             $rooms_table = retrieveRooms ($i+1, "allocation_settings_room" );
             $NO_OF_ROOMS  = count($rooms_table);
             $currentSlot = array_fill(0, 1, array_fill(0,$NO_OF_ROOMS,0));
             for ($k=0; $k<$NO_OF_ROOMS && $vacantDay==null;$k++) {
                 $currentSlot[0][$k] = 0;
                 for ($z=0;$z<$MAX_SLOTS && $vacantDay==null;$z++) {
                     if ($overallTimeTable[$i][0][$k][$z] == null) {
                        $vacantDay = $i;
                     } else {
                         $currentSlot[0][$k]++;
                         $roomSlot = $k;
                     }
                 }
             }
             // try every vacant day
             while ($vacantDay != null && $currentSlot[0][$roomSlot] < $MAX_SLOTS) {
                 $projectList= assignRooms($projectList, $staffList, $overallTimeTable[$vacantDay], $currentSlot, $vacantDay, $NO_OF_ROOMS, $NO_OF_TIMESLOTS);
                 insertValuesIntoDB ($vacantDay, $projectList);
                 $projectList = removeAssignedProjects ($projectList);
                 $currentSlot[0][$roomSlot]++;
             }
             if (count($projectList)>0) {
                 $vacantDay = null;
             }
         }
    }

    //Check if there are leftover projects
    $allocate_code = 1;
    foreach($projectList as $project)
    {
        if (!$project->isAssignedTimeslot())	//Incomplete allocation
        {
            $allocate_code = 0;
        }
    }
    //exit;
    //debugging statements
    //echo "<br>";
    //echo "end, left over projects count: ";
    //echo count($projectList);
}	
//End of Allocation
	
function removeAssignedProjects ($projectList)  {
	
	foreach($projectList as $key=>$project) {
		if ($project->isAssignedTimeslot())	{
			unset($projectList[$key]);
		}
	}
	return $projectList;
}
function indexStaff  ($staffs, $exceptions) {
	//Staff
	foreach($staffs as $staff) { //Index Staff by staffid
				$staffList[ $staff['staffid'] ] = new Staff($staff['staffid'],  $staff['salutation'], $staff['staffname']);

				foreach ($exceptions as $exception)
				{
					if ($exception['staff_id'] == $staff['staffid'])
					{
						$cur_day = ($exception['day'] == '*') ? -1 : $exception['day'] - 1;
						$staffList[ $staff['staffid'] ]->addTimeslotException(	$cur_day,
																				DateTime::createFromFormat('H:i:s', $exception['time_start']),
																				DateTime::createFromFormat('H:i:s', $exception['time_end']));
																				
						//echo "[Exception] ".$staff['staffid'].": Day ".$cur_day." [ ".current($staffList[ $staff['staffid'] ]->timeslotException)->toString()." ]<br/>";
					}
				}
				
			}
	return $staffList;
}
function indexProjects ($staffList, $projects, $projectList) {

	global $TABLES, $conn_db_ntu;

    $query_createSupervisingCountView 	= "CREATE OR REPLACE VIEW v_supervising_count  as  SELECT COUNT(fa.staff_id) AS supervising_count,fa.staff_id as staff_id FROM ". $TABLES['allocation_result'] ." as r LEFT JOIN " . $TABLES['fyp_assign']. " as fa on r.project_id = fa.project_id GROUP BY fa.staff_id ORDER BY supervising_count DESC";
    $conn_db_ntu->query($query_createSupervisingCountView);
    
	//sort projects by supervising count (>=4) so as to minimise supervisor movement
//    $query_sortProjectBySupervisingCount = "SELECT r.project_id, r.examiner_id, fa.staff_id, vc.supervising_count FROM " .$TABLES['allocation_result'] ." as r LEFT JOIN " . $TABLES['fyp_assign']. " as fa on r.project_id = fa.project_id LEFT JOIN v_supervising_count as vc on fa.staff_id = vc.staff_id WHERE vc.supervising_count >= 4 ORDER BY vc.supervising_count DESC, fa.staff_id, r.examiner_id , r.project_id";
//	$projectsSortedBySupervisingCount		= $conn_db_ntu->query( $query_sortProjectBySupervisingCount )->fetchAll();
	
	$query_createExaminerCountView 	= "CREATE OR REPLACE VIEW v_examiner_count as SELECT COUNT(r.examiner_id) as examiner_count, r.examiner_id as examiner_id FROM ". $TABLES['allocation_result'] ." as r LEFT JOIN " . $TABLES['fyp_assign']. " as fa on r.project_id = fa.project_id GROUP BY r.examiner_id ORDER BY examiner_count DESC";
    $conn_db_ntu->query($query_createExaminerCountView);
	
	//sort projects by examiner count (supervising count <4 ) so as to minimise examiner movement
//	$query_sortProjectByExaminerCount = "SELECT r.project_id, r.examiner_id, fa.staff_id,vec.examiner_count  FROM " .$TABLES['allocation_result'] ." as r LEFT JOIN " . $TABLES['fyp_assign']. " as fa on r.project_id = fa.project_id LEFT JOIN v_supervising_count as vc on fa.staff_id = vc.staff_id  LEFT JOIN v_examiner_count as vec on vec.examiner_id = r.examiner_id  WHERE vc.supervising_count < 4 ORDER BY vec.examiner_count DESC, r.examiner_id ,fa.staff_id, r.project_id";
//	$projectsSortedByExaminerCount		= $conn_db_ntu->query( $query_sortProjectByExaminerCount )->fetchAll();

	$query_createProjectsInvolvementCountView = "create or replace view projects_involvement_count as 
                                                                                    SELECT * FROM v_supervising_count as sc 
                                                                                    left join v_examiner_count as ec 
                                                                                    on sc.staff_id=ec.examiner_id 
                                                                                    UNION 
                                                                                    SELECT * FROM v_supervising_count as sc 
                                                                                    right join v_examiner_count as ec 
                                                                                    on sc.staff_id=ec.examiner_id";
    $conn_db_ntu->query($query_createProjectsInvolvementCountView);

    $query_createCountView = "create or replace view `count` as
                                                select coalesce(staff_id, examiner_id) as staff_id, coalesce(supervising_count+examiner_count,supervising_count,examiner_count) as total_count
                                                from projects_involvement_count
                                                order by total_count desc";
    $conn_db_ntu->query($query_createCountView);

    $query_sortProjectsByInvolvementCount = "select r.project_id, r.examiner_id, fa.staff_id, c.total_count from " .$TABLES['allocation_result'] ." as r left join " . $TABLES['fyp_assign']. " as fa on r.project_id = fa.project_id right join count as c on fa.staff_id = c.staff_id or c.staff_id = r.examiner_id";
    $projectsSortedByCount	 = $conn_db_ntu->query( $query_sortProjectsByInvolvementCount )->fetchAll();
    $projectsSortedByCount = array_unique($projectsSortedByCount, SORT_REGULAR);

//	$projectsCombined = array_merge($projectsSortedBySupervisingCount, $projectsSortedByExaminerCount);
	
	foreach($projectsSortedByCount as $project) {
		
		$projectList[ $project['project_id'] ] = new Project($project['project_id'],$project['staff_id'],$project['examiner_id'],'-' );
		
	}
	/*foreach ($projectList as $project) {
		//var_dump($project);
		
		echo ("project id: ". $project->getID());
		echo ("<br>");
		echo ("staff id: ". $project->getStaff());
		echo ("<br>");
		echo ("examiner id: ". $project->getExaminer());
		echo ("<br>");
	}*/

	return $projectList;
}
/*function indexProjects ($staffList, $projects, $projectList) {
	//Projects
	global $TABLES, $conn_db_ntu;
    $query_getSupervisingProjects = "SELECT count(fa.staff_id) as supervising_count, fa.staff_id FROM ". $TABLES['allocation_result'] ." as r LEFT JOIN ". $TABLES['fyp_assign'] ." as fa on r.project_id = fa.project_id group by fa.staff_id order by supervising_count desc";
    $supervisingProjectsInfo		= $conn_db_ntu->query($query_getSupervisingProjects)->fetchAll();
    foreach ($staffList as $staff) {
		foreach($supervisingProjectsInfo as $info ) {
				if ($info["staff_id"] == $staff->getID()) {
						//echo ("staff: " . $info["staff_id"] );
						//echo ("<br>");
						//echo ("count: " . $info["supervising_count"] );
						//echo ("<br>");
                        $staff->setSupervisingNo( $info["supervising_count"]);
				}
		}
    }	
	foreach($projects as $project) { //Index Project By pno											
			$projectList[ $project['pno'] ] = new Project($project['pno'],$project['staffid'],$project['examinerid'],'-' );
			//Assuming Perfect Data where all staff are found in StaffList
			$staffList[ $project['staffid'] ] -> assignment_list[ $project['pno'] ] = $projectList [$project ['pno']];
			//$staffList[ $project['examinerid'] ] -> assignment_list[ $project['pno'] ] = $projectList [$project ['pno']];
			}
	//Phase 1: Recalculate Priority Model
	uasort($staffList, "CmpPriorityDesc");	//Calculate Staff Priority
	$projectList = array();					//Flush Current Project List
	foreach($staffList as $staff)			//Regenerate the Project List according to new priority model
	{
		foreach($staff->assignment_list as $project)
		{
			$projectList[ $project->getID() ] = $project;
			//var_dump($projectList[ $project->getID() ]);
		}
	}
	;
	return $projectList;
}*/
function createTimeTable($day, $NO_OF_ROOMS, $MAX_SLOTS) {
	$timetable= array_fill(0, $day,array_fill(0, $NO_OF_ROOMS, array_fill(0, $MAX_SLOTS, NULL)));
			
	return $timetable;
}
function createSlotUsed($day, $NO_OF_ROOMS) {
	$slotused = array_fill(0, $day,array_fill(0, $NO_OF_ROOMS, 0));
	
	return $slotused;
}
function allocateTimeSlotsByDay ($dayIndex,$staffList, $projectList, $MAX_SLOTS, $NO_OF_TIMESLOTS){
	
	global $timeslots_table, $NO_OF_ROOMS;
	
	$actualDay = $dayIndex+1;
	$rooms_table = retrieveRooms ($actualDay, "allocation_settings_room" );
	
	$NO_OF_ROOMS  = count($rooms_table);
	
	$timetable = createTimeTable($actualDay , $NO_OF_ROOMS, $MAX_SLOTS);

	//Counter to determine up to which slot has been occupied (Speeds up allocation process)
	$slotused = createSlotUsed($actualDay,$NO_OF_ROOMS);
	$totalTimeTableSlots =0;
	if (sizeof($timetable)>0 ) {
		$totalTimeTableSlots = array_sum(array_map("count", $timetable[0]));
	}

	$projectList= assignRooms($projectList, $staffList, $timetable, $slotused, $dayIndex, $NO_OF_ROOMS, $NO_OF_TIMESLOTS);

	return $projectList;
	
}
function assignRooms ($projectList,$staffList, $timetable, $slotused, $dayIndex, $NO_OF_ROOMS, $NO_OF_TIMESLOTS) {
	global $timeslots_table, $overallTimeTable, $MAX_SLOTS ;

	//Phase 2.1: Sequential Assignment
	//Check if timeslot available	
	$collisionCount=0;
	$index=0;
	for($i = 0; $i < count($projectList); $i++) {
        // break out of project list loop when room/slots for the day is full
        if ($timetable[$index][$NO_OF_ROOMS-1][$MAX_SLOTS-1] != null) {
            break;
        }

		$current_project = array_values($projectList)[$i];

		for($room = 0; $room < $NO_OF_ROOMS; $room++){
			$current_slot = $slotused[$index][$room];
            // if room full
			if ( $current_slot >= $NO_OF_TIMESLOTS[$dayIndex] )
			{
                continue;
			}
            // if room not full
			else {
                //Check for supervisor/examiner time exceptions
                $collision = false;

                $current_supervisor = $current_project->getStaff();
                $current_examiner = $current_project->getExaminer();

                $supervisor_available = $staffList[$current_supervisor]->isAvailable($dayIndex, $timeslots_table[$dayIndex][$current_slot]->getStartTime(), $timeslots_table[$dayIndex][$current_slot]->getEndTime());

                $examiner_available = $staffList[$current_examiner]->isAvailable($dayIndex, $timeslots_table[$dayIndex][$current_slot]->getStartTime(), $timeslots_table[$dayIndex][$current_slot]->getEndTime());

                // constraint checking
                for($r = 0; !$collision && $r < $NO_OF_ROOMS; $r++)
                {
                    if ($timetable[$index][$r][$current_slot] == null) {
                        break;
                    }
                    if ($timetable[$index][$r][$current_slot] != null)
                    {
                        $adjacent_supervisor = $timetable[$index][$r][$current_slot]->getStaff();
                        $adjacent_examiner = $timetable[$index][$r][$current_slot]->getExaminer();
                        if ($current_supervisor == $adjacent_supervisor ||
                            $current_supervisor == $adjacent_examiner ||
                            $current_examiner == $adjacent_supervisor ||
                            $current_examiner == $adjacent_examiner)
                        {
                            $collision = true;
                        }
                    }
                }
								
                //Collision Detected. Abort current allocation cycle. (Try Next Slot)
                if (!$supervisor_available || !$examiner_available || $collision)
                {
                    //echo "<br>";
                    //echo $collision  ? 'true' : 'false';
                    //echo "<br/> collision = ". $collision;
                    //echo "<br/> ";
                    $collisionCount++;
                    break;
                }

                //Assign current project to current slot
                if (!$current_project->isAssignedTimeslot()) {
                    $timetable[$index][$room][$current_slot] = $current_project;
                    $current_project->assignTimeslot($index, $room, $current_slot);
                    $slotused[$index][$room]++;
                    break;
                 }
            }
        }
    }

    $overallTimeTable[$dayIndex] = $timetable;
    return $projectList;
}
function insertValuesIntoDB ($dayIndex, $projectList) {
			//Bulk Insert
			//Timeslot
			global $conn_db_ntu, $TABLES, $timeslots_table;
			$solution_found = false;
			$actualDay = $dayIndex+1;
			$stmt1 = $conn_db_ntu->prepare("DELETE FROM ".$TABLES['allocation_result_timeslot']." WHERE day = ? ");
			$stmt1->bindParam("1", $actualDay);
			$stmt1->execute();
			$values = array();
			
				foreach($timeslots_table[$dayIndex] as $timeslot)
				{
					//var_dump( $timeslot);
					$timeSlotID = $timeslot->getID();
					$timeSlotDay = $timeslot->getDay();
					$slot = $timeslot->getSlot();
					$timeSlotST = $timeslot->getStartTime()->format('H:i:s');
					$timeSlotET = $timeslot->getEndTime()->format('H:i:s');
					$stmt1 = $conn_db_ntu->prepare("SELECT * FROM ".$TABLES['allocation_result_timeslot']." WHERE id = ?");
					$stmt1->bindParam("1", $timeSlotID );
					$stmt1->execute();
					
					
					$existingRecords = $stmt1->fetchAll();
					if (sizeof ($existingRecords) > 0) {
						$stmt1 = $conn_db_ntu->prepare("UPDATE ".$TABLES['allocation_result_timeslot']." SET day= ?, slot = ?, time_start = ?, time_end = ? WHERE id = ?");
						
						$stmt1->bindParam(1, $timeSlotDay);
						$stmt1->bindParam(2, $slot);
						$stmt1->bindParam(3, $timeSlotST);
						$stmt1->bindParam(4, $timeSlotET);
						$stmt1->bindParam(5, $timeSlotID );
						$stmt1->execute();
					}
					else {
						$stmt1 = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['allocation_result_timeslot']." (`id`, `day`, `slot`, `time_start`, `time_end`) VALUES (?, ?, ?, ?, ?)");
						$stmt1->bindParam(1, $timeSlotID );
						$stmt1->bindParam(2, $timeSlotDay);
						$stmt1->bindParam(3, $slot);
						$stmt1->bindParam(4, $timeSlotST);
						$stmt1->bindParam(5, $timeSlotET);
						$stmt1->execute();		
					}
				}

			//Rooms
			$stmt1 = $conn_db_ntu->prepare("DELETE FROM ".$TABLES['allocation_result_room']." WHERE day = ? ");
			$stmt1->bindParam(1, $actualDay);
			$stmt1->execute();

			$stmt1 = $conn_db_ntu->prepare("SELECT roomArray FROM ".$TABLES['allocation_settings_room']." WHERE day = ? ");
			$stmt1->bindParam("1", $actualDay);
			$stmt1->execute();
			$rooms = $stmt1->fetchAll();
			
			if (sizeof($rooms) >0 ) {
				$roomsArr = $rooms[0]["roomArray"];
			
				$stmt1 = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['allocation_result_room']." ( `day`, `roomArray`) VALUES (? , ?)");
				$stmt1->bindParam("1", $actualDay);
			
				//var_dump($roomArr);
				$stmt1->bindParam("2",$roomsArr);
				$stmt1->execute(); 
			}
			foreach($projectList as $project)
			{
				$dayUs = -1;
				$time= -1;
				$room =-1;
				
				if ($project->isAssignedTimeslot())
				{
					$dayUs = $project->getAssigned_Day() + 1;		//Offset to database
					$dayUs = $actualDay;
					$time = $project->getAssigned_Time() + 1;
					$room = $project->getAssigned_Room() + 1;
				}
				
				//echo("project day: ");
				//echo($actualDay);
				//echo ("<br>");	
				$projectID = $project->getID();
				//echo("prid: ");
				
				//echo($projectID);
				$examinerID = $project->getExaminer();
				
				//Assignment Results
				//Clear previous data first
				$stmt1 = $conn_db_ntu->prepare("UPDATE ".$TABLES['allocation_result']." SET day=0, slot =NULL, room =NULL WHERE  project_id = ? ");
				$stmt1->bindParam("1", $projectID);
				$stmt1->execute();
				
				$stmt1 = $conn_db_ntu->prepare("SELECT * FROM ".$TABLES['allocation_result']." WHERE project_id = ? ");
				$stmt1->bindParam("1",$projectID );
				$stmt1->execute();
				$existingRes = $stmt1->fetchAll();
				
				if (sizeof($existingRes) > 0) {
					$stmt1 = $conn_db_ntu->prepare("UPDATE ".$TABLES['allocation_result']." SET day = ? , slot = ?, room =?, clash=0  WHERE project_id = ? ");
					//$stmt1->bindParam("1",$examinerID);
					$stmt1->bindParam("1", $dayUs);
					$stmt1->bindParam("2",$time );
					$stmt1->bindParam("3",$room );
					$stmt1->bindParam("4",$projectID );
					$stmt1->execute();
				}
				else {
					$stmt1 = $conn_db_ntu->prepare("INSERT INTO  ".$TABLES['allocation_result']." (`project_id`, `examiner_id`, `day`, `slot`, `room`, `clash`) VALUES(?,?,?,?,?,0");
					$stmt1->bindParam("1",$projectID);
					$stmt1->bindParam("2",$examinerID);
					$stmt1->bindParam("3",$dayUs );
					$stmt1->bindParam("4",$time );
					$stmt1->bindParam("5",$room);
					$stmt1->execute();
		
				}
				
				//echo "<br>";	
				
			}	
}

//echo "<br/>[PDO] Results Saved.<br/>";
//if(!$solution_found)
//	$solution_found = ($allocate_code==1);
//if ($NO_OF_DAYS > 1) $NO_OF_DAYS--;
//printTimeTable ($projectList, $overallTimeTable, 3,$NO_OF_TIMESLOTS);
//redirect set to true at the end to ensure everything has been executed successfully

$redirect = true;
	
?>
<?php

		if ($redirect)
		{	
			echo ($error_code != 0) ? "error_timeslot=$error_code" : "allocate_timeslot=$allocate_code";
			return;
		}
		
	?>
<!DOCTYPE html >
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>FYP Examiner Allocation System </title>
	
</head>
<body>
<?php
	//Print Project List Table
	function printTimeTable ( $projectList, $overallTimeTable, $NO_OF_DAYS,$NO_OF_TIMESLOTS) {
	  
	  $index=0;
	echo	"<h3>PROJECT LIST</h3>";
	echo 	'<table style="text-align:center;" border="1"><tr>
				<th>ProjectID</th>
				<th>Staff</th>
				<th>Examiner</th>
				</tr>';
	foreach($projectList as $project) {
		echo 	'<tr>';
		echo	'<td>'.$project->getID().'</td>';

		if ($project->getStaff() !== '')
			echo	'<td>'.$project->getStaff().'</td>';
		else
			echo	'<td> - </td>';

		if ($project->getExaminer() !== '')
			echo	'<td>'.$project->getExaminer().'</td>';
		else
			echo	'<td> - </td>';

		echo	'</tr>';
	}
	echo '</table>';

	//Print Allocation Results
	echo	"<h3>TIMETABLE PLAN</h3>";
	echo($NO_OF_DAYS);
	for($dayIndex = 0; $dayIndex < $NO_OF_DAYS; $dayIndex++)
	{
		$actualDay =$dayIndex+1;
		echo 	'<b>Day '.intval($actualDay).'</b>';
		echo 	'<table style="text-align:center;" border="1"><tr>';
          
		//Header
		echo '<tr>';
		echo '<th></th>';
		for($timeslot = 0; $timeslot < $NO_OF_TIMESLOTS[$dayIndex]; $timeslot++)
		{
			echo '<th>Slot '.intval($timeslot+1).'</th>';
		}
		echo '</tr>';

		//Body
		$timetable = $overallTimeTable[$dayIndex];
		//echo '<br>Timetable for index '. $day;
		//echo '<br>';
		//var_dump ($overallTimeTable[$day]);
		
		$rooms_table=retrieveRooms($actualDay, "allocation_settings_room");
		$NO_OF_ROOMS  = count($rooms_table);
		echo '<br>';
		echo (" room count: ".$NO_OF_ROOMS);
		echo '<br>';
		echo ("timeslot count: ".$NO_OF_TIMESLOTS[$dayIndex]. "day: ". $dayIndex );
		//echo '<br>Actual Timetable:'. $day;
		echo '<br>';
		//var_dump ($timetable);
		for($room = 0; $room < $NO_OF_ROOMS; $room++)
		{
			
			echo '<tr>';
			echo '<td>Room '.intval($room+1).'</td>';
			
			for($timeslot = 0; $timeslot < $NO_OF_TIMESLOTS[$dayIndex]; $timeslot++)
			{
				echo '<br>';
				
				//echo ("day : ". $day . " room : ". $room . "  timeslot. ". $timeslot);
				
				if ($timetable[$index][$room][$timeslot] !=  null) {
					$details = 	"Supervisor : ". $timetable[$index][$room][$timeslot]->getStaff()."\nExaminer: ". $timetable[$index][$room][$timeslot]->getExaminer();
				}
				else {
					$details = "";
				}
				echo '<td title="'.$details.'">';
				if ($timetable[$index][$room][$timeslot] !== null) {
					echo $timetable[$index][$room][$timeslot]->getID();
				}
				else {
					echo '-';
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</table><br/>';
	}

	//Statistics
	$assignedProjects = 0;
	echo "<h4> Unallocated Projects </h4>";
	foreach ($projectList as $project)
	{
		if ($project->isAssignedTimeslot())
			$assignedProjects++;
		else
			echo $project->getID()."<br/>";
	}

	echo "<h4> Assignment Statistics </h4>";
	echo "<p>";
	echo "Projects Allocated: " . intval($assignedProjects). " / " . count($projectList) . "<br/>";
	echo "</p>";
	echo "end of file ";
}	
?>
</body>
</html>
<?php
	unset($staffs);
	unset($projects);

	unset($staffList);
	unset($projectList);

	unset($timetable);
?>