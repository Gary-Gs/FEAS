<?php
require_once('../../../Connections/db_ntu.php');
require_once('./entity.php');
require_once('../../../Utility.php');

ini_set('max_execution_time', 600);
//redirect initialised as false first
$redirect = false;

$error_code = 0;

$debug = isset($_GET['debug']);

global $NO_OF_DAYS;
function CmpPriorityDesc($a, $b) {
	//Exceptions First
	$a1 = count($a->timeslotException);
	$b1 = count($b->timeslotException);

	if ($a1 == $b1) {
		//Assignment Next
		$a2 = count($a->assignment_list);
		$b2 = count($b->assignment_list);

		if ($a2 == $b2) {
			return 0;
		} else {
			return ($a2 < $b2) ? 1 : -1;
		}
	} else {
		return ($a1 < $b1) ? 1 : -1;
	}
}

$query_rsSettings = "select * from " . $TABLES['allocation_settings_general'] . " as g";
$query_rsOtherSettings = "select * from " . $TABLES['allocation_settings_others'] . " as o where type = 'FT'";

$query_rsStaff = "select s.id as staffid, s.name as staffname, s.position as salutation from " . $TABLES['staff'] . " as s";
$query_rsProject = "select r.project_id as pno, p.staff_id as staffid, r.examiner_id as examinerid from " . $TABLES['allocation_result'] . " as r left join " . $TABLES['fyp_assign'] . " as p on r.project_id = p.project_id";

$query_rsExceptions = "select * from " . $TABLES['fea_settings_availability'] . " as a";
try {
	$settings = $conn_db_ntu->query($query_rsSettings)->fetchAll();
	$otherSettings = $conn_db_ntu->query($query_rsOtherSettings)->fetch();

	$staffs = $conn_db_ntu->query($query_rsStaff)->fetchAll();
	$projects = $conn_db_ntu->query($query_rsProject)->fetchAll();
	$exceptions = $conn_db_ntu->query($query_rsExceptions)->fetchAll();
} catch (PDOException $e) {
	die($e->getMessage());
}

/* Parse Settings */
//Default Values
/*
$startTime = DateTime::createFromFormat('H:i:s', '08:30:00');
$endTime = DateTime::createFromFormat('H:i:s', '17:30:00');
$timeslotDuration = new DateInterval('PT30M');
$NO_OF_DAYS = 3;
$NO_OF_ROOMS = 8;
$NO_OF_TIMESLOTS = 16;
*/

try {
	$NO_OF_DAYS = $otherSettings['alloc_days'];
	//$NO_OF_DAYS = $settings['alloc_days'];
	//$startTime = DateTime::createFromFormat('H:i:s', $settings['alloc_start']);
	//$endTime = DateTime::createFromFormat('H:i:s', $settings['alloc_end']);
	//$timeslotDuration = new DateInterval('PT'. $settings['alloc_duration'].'M');
} catch (Exception $e) {
	//Default Values
	$NO_OF_DAYS = 3;

}

//$MAX_DAY_COMPRESSION = $NO_OF_DAYS-1;
$MAX_SLOTS = 0;
$NO_OF_TIMESLOTS = array(); //how many slots per day (for all rooms)
$timeslots_table = array();
$count = 0;
for ($dayIndex = 0; $dayIndex < $NO_OF_DAYS; $dayIndex++) {
	$timeslots_table[$dayIndex] = array();
	//Calculate Timeslot

	$startTime = DateTime::createFromFormat('H:i:s', $settings[$dayIndex]['alloc_start']);
	$endTime = DateTime::createFromFormat('H:i:s', $settings[$dayIndex]['alloc_end']);
	$timeslotDuration = new DateInterval('PT' . $settings[$dayIndex]['alloc_duration'] . 'M');

	$slot = 0;
	for ($curTime = $startTime; $curTime < $endTime;) {
		$t1 = clone $curTime;
		$t2 = clone $curTime->add($timeslotDuration);

		foreach ($exceptions as $exception) {
			if ($exception['staff_id'] == '*' && ($exception['day'] == ($dayIndex + 1) || $exception['day'] == '*')) //Affect all staffs
			{
				$exceptionStart = DateTime::createFromFormat('H:i:s', $exception['time_start']);
				$exceptionEnd = DateTime::createFromFormat('H:i:s', $exception['time_end']);

				if ($exceptionStart != null && $exceptionEnd != null) {
					$exceptionStart_str = $exceptionStart->format('H:i:s');
					$exceptionEnd_str = $exceptionEnd->format('H:i:s');

					$t1_str = $t1->format('H:i:s');
					$t2_str = $t2->format('H:i:s');

					if ($t1_str >= $t2_str || $exceptionStart_str >= $exceptionEnd_str) { //Invalid Time Range (Ignore)
						continue;
					}

					if (($t2_str <= $exceptionStart_str) || ($t1_str >= $exceptionEnd_str)) {
						//Okay
					} else {
						//Collide
						$curTime = clone $exceptionEnd;
						$t1 = clone $curTime;
						$t2 = clone $curTime->add($timeslotDuration);
					}
				}
			}
		}
		if ($t1 >= $endTime) continue;
		$timeslots_table[$dayIndex][] = new Timeslot($count + 1, $dayIndex + 1, $slot + 1, $t1, $t2);

		$slot++;
		$count++;
	}
	$NO_OF_TIMESLOTS[$dayIndex] = count($timeslots_table[$dayIndex]);
	if ($NO_OF_TIMESLOTS[$dayIndex] > $MAX_SLOTS) {
		$MAX_SLOTS = $NO_OF_TIMESLOTS[$dayIndex];
	}
} //for-loop :: $dayIndex -- assign timeslot

/* Converting DB to Object Models */
$staffList = array();
$projectList = array();
$roomsNewArray = array();

$allocate_code = 0;

// -----------------------------------------------------------------start of allocation---------------------------------------------------------------
if (count($projects) == 0 || count($staffs) == 0) {
	$error_code = 1;
} //else if($NO_OF_DAYS <= 0 || $MAX_SLOTS <= 0 || $NO_OF_ROOMS <= 0)
else if ($NO_OF_DAYS <= 0 || $MAX_SLOTS <= 0) {
	$error_code = 2;
} else {
	// prepare staff list with respective time exceptions
	$staffList = indexStaff($staffs, $exceptions);

	// prepare project list (sorted by supervising count >=4 , then from the rest sorted by examiner count) (to reduce movements)
	$projectList = indexProjects($staffList, $projects, $projectList);

	//start new assigning method
	assignTimeslot($debug, $projectList, $staffList, $timeslots_table, $NO_OF_DAYS, $NO_OF_TIMESLOTS);

	return; //ignore following code

//	// first round allocation (sequential)
//	for ($dayIndex = 0; ($projectList != 0) && $dayIndex < $NO_OF_DAYS; $dayIndex++) {
//		$optOut = $settings[$dayIndex]["opt_out"];
//		if ($optOut == 0) {
//			$projectList = allocateTimeSlotsByDay($dayIndex, $staffList, $projectList, $MAX_SLOTS, $NO_OF_TIMESLOTS);
//			insertValuesIntoDB($dayIndex, $projectList);
//			$projectList = removeAssignedProjects($projectList);
//			$skipping = skipDecision($dayIndex, $projectList);
//			if ($skipping) {
//				$projectList = assignSkipping($projectList, $staffList, $overallTimeTable, $dayIndex);
//				insertValuesIntoDB($dayIndex, $projectList);
//				$projectList = removeAssignedProjects($projectList);
//			}
//		}
//	}
//
//	// second round allocation (by remaining vacancies)
//	$attempts = 100;
//	while (count($projectList) > 0 && $attempts != 0) {
//
////        // debugging
////         if (array_values($projectList)[0]->getID() === "SCE17-0373") {
////             $test = 1;
////         }
//
//		//find day index of vacancy day
//		$vacantDay = null;
//		for ($i = 0; $i < $NO_OF_DAYS && $vacantDay == null; $i++) {
//			$currentSlot = array();
//			$roomSlot = 0;
//			$rooms_table = retrieveRooms($i + 1, "allocation_settings_room");
//			$NO_OF_ROOMS = count($rooms_table);
//			$currentSlot = array_fill(0, 1, array_fill(0, $NO_OF_ROOMS, 0));
//			for ($k = 0; $k < $NO_OF_ROOMS && $vacantDay == null; $k++) {
//				$currentSlot[0][$k] = 0;
//				for ($z = 0; $z < $MAX_SLOTS && $vacantDay == null; $z++) {
//					//if slot of the room is empty, locate vacant day
//					if ($overallTimeTable[$i][$k][$z] == null) {
//						$vacantDay = $i;
//						$roomSlot = $k;
//					} else {
//						$currentSlot[0][$k]++;
//						$roomSlot = $k;
//					}
//				}
//			}
//			// try every slots in vacant day
//			while ($vacantDay != null && $currentSlot[0][$roomSlot] < $MAX_SLOTS) {
//				$projectList = assignRooms($projectList, $staffList, $overallTimeTable[$vacantDay], $currentSlot, $vacantDay, $NO_OF_ROOMS, $NO_OF_TIMESLOTS);
//				insertValuesIntoDB($vacantDay, $projectList);
//				$projectList = removeAssignedProjects($projectList);
//				$currentSlot[0][$roomSlot]++;
//			}
//			if (count($projectList) > 0) {
//				$vacantDay = null;
//			}
//		}
//		$attempts--;
//	}
//
//	//Check if there are leftover projects
//	$allocate_code = 1;
//	foreach ($projectList as $project) {
//		if (!$project->isAssignedTimeslot()) {  //Incomplete allocation
//			$allocate_code = 0;
//		}
//	}
//	//exit;
//	//debugging statements
//	//echo "<br>";
//	//echo "end, left over projects count: ";
//	//echo count($projectList);
} // -- allocate timeslot
//End of Allocation

/***new algo start**/
function assignTimeslot($debug, $projectList, $staffList, $timeslots_table, $NO_OF_DAYS, $NO_OF_TIMESLOTS) {
	$final_timetable = array();

	ksort($projectList);
	$temp_projects = $projectList; //duplicate the original project list
	debug_displayproject($temp_projects, $debug);
	for ($dayIndex = 0; (count($temp_projects) > 0) && ($dayIndex < $NO_OF_DAYS); $dayIndex++) {
		$rooms_count = count(retrieveRooms($dayIndex + 1, "allocation_settings_room"));
		$rooms = array_fill(0, $rooms_count, array_fill(0, $NO_OF_TIMESLOTS[$dayIndex], null)); //$rooms stores the room, with the respective time slots

		$ignore_staff = 0;
//		for ($attempts = 0; ($attempts < 100 && $ignore_staff < 50); $attempts++) {
		while (true) {
			$this_staff = getFirstStaff($staffList, $temp_projects, $ignore_staff, $debug);
			if ($this_staff == null) break;
			$this_projects = getFirstProjects($this_staff, $temp_projects);
			if (count($this_projects) < 2) break; //if less than 2 go 2nd round
			$ignore_slot = 0;
			for ($r = 0; $r < $rooms_count; $r++) {
				$slot_arr = getFreeSlot($rooms[$r], count($this_projects), $ignore_slot);
				$slot_at = $slot_arr[0];    //start of vacancy slots
				$slot_space = $slot_arr[1]; //if projects is more than available timeslot, returns timeslot count, else return project count
				if ($slot_at != -1) {
					if ($this_staff->isAvailable($dayIndex,
						$timeslots_table[$dayIndex][$slot_at]->getStartTime(),
						$timeslots_table[$dayIndex][$slot_at + $slot_space - 1]->getEndTime()
					)) {
						$other_staffs = getSecondStaff($this_staff, $staffList, $this_projects);
						$slots_assign = getSecondAssigned($other_staffs, $dayIndex, $slot_at, $slot_space);
						if (count(array_filter($slots_assign, function ($x) {
								return $x != null;
							})) == 0) { //checking if there is at least 1 slot occupied by the other staffs
							$ignore_staff++;
							break;
						}

						//actions to be performed when schedule is set
						$staffList = addStaffException($temp_projects, $staffList, $slots_assign, $dayIndex, $slot_at);
						$temp_projects = removeProjects($temp_projects, $slots_assign);
						$rooms[$r] = updateRoomList($rooms[$r], $slots_assign, $slot_at);

						$projectList = assignProjectList($projectList, $slots_assign, $dayIndex, $r, $slot_at); //affects actual project list
						$ignore_staff = 0;

						debug_displayroom($rooms, $dayIndex, $debug);
						debug_displayassigned($this_staff, $slots_assign, $debug);
						break;
					} //check if first staff is free within period
					else {
						$ignore_slot++;
						if ($slot_at < count($rooms[$r])) {
							$r--;
						}
					}
				} //check if room have enough free slot for groups
				else {
					$ignore_slot = 0;
				}
			} //for-loop :: $rooms
			if ($r == $rooms_count) {
				$ignore_staff++;
			}
		} //for-loop :: $attempts
		insertValuesIntoDB($dayIndex, $projectList);
		$projectList = removeAssignedProjects($projectList);
		$final_timetable[$dayIndex] = $rooms;
	} //for-loop :: $dayIndex

	debug_displayproject($temp_projects, $debug);
	for ($dayIndex = 0; (count($temp_projects) > 0) && ($dayIndex < $NO_OF_DAYS); $dayIndex++) {
		$rooms = $final_timetable[$dayIndex];
		$ignore_slot = 0;
		for ($r = 0; $r < count($rooms);) {
			$slot_arr = getFreeSlot($rooms[$r], 1, $ignore_slot);
			$slot_at = $slot_arr[0];
			if ($slot_at != -1) {
				for (; current($temp_projects) != null; next($temp_projects)) {
					if (array_key_exists(current($temp_projects)->getStaff(), $staffList) && array_key_exists(current($temp_projects)->getExaminer(), $staffList)) {
						$staff_free = $staffList[current($temp_projects)->getStaff()]->isAvailable($dayIndex,
							$timeslots_table[$dayIndex][$slot_at]->getStartTime(),
							$timeslots_table[$dayIndex][$slot_at]->getEndTime()
						);
						$examiner_free = $staffList[current($temp_projects)->getExaminer()]->isAvailable($dayIndex,
							$timeslots_table[$dayIndex][$slot_at]->getStartTime(),
							$timeslots_table[$dayIndex][$slot_at]->getEndTime()
						);
						if ($staff_free && $examiner_free) {
							$slots_assign = array(current($temp_projects)->getID());

							$staffList = addStaffException($temp_projects, $staffList, $slots_assign, $dayIndex, $slot_at);
							$temp_projects = removeProjects($temp_projects, $slots_assign);
							$rooms[$r] = updateRoomList($rooms[$r], $slots_assign, $slot_at);

							$projectList = assignProjectList($projectList, $slots_assign, $dayIndex, $r, $slot_at); //affects actual project list

							debug_displayroom($rooms, $dayIndex, $debug);
							debug_displayassigned(null, $slots_assign, $debug);
							break;
						} //check if both staff are free for that slot
					} //for all available projects
				}
				reset($temp_projects);
				if ($rooms[$r][$slot_at] == null) {
					$ignore_slot++;
				}
			} //check if room have enough free slot
			else {
				$r++;
				$ignore_slot = 0;
			}
		} //for-loop :: $rooms
		insertValuesIntoDB($dayIndex, $projectList);
		$projectList = removeAssignedProjects($projectList);
		$final_timetable[$dayIndex] = $rooms;
	} //for-loop :: $dayIndex

	debug_displaytimetable($final_timetable, $debug);
}

function debug_displaytimetable($final_timetable, $debug) {
	if (!$debug) return;

	for ($day = 0; $day < count($final_timetable); $day++) {
		echo "<br>Day " . ($day + 1) . "<br>";

		debug_displayroom($final_timetable[$day], $day, $debug);
	}
}

function debug_displayroom($rooms, $day, $debug) {
	if (!$debug) return;

	global $timeslots_table;

	$string = array();
	foreach ($timeslots_table[$day] as $time) {
		array_push($string, $time->toString());
	}
	for (; current($rooms) != null; next($rooms)) {
		for ($r = 0; $r < count(current($rooms)); $r++) {
			if (($p = current($rooms)[$r]) == null) $p = "xxxxx-xxxx";
			$string[$r] .= "|" . $p;
		}
	}
	foreach ($string as $s) {
		echo $s . "<br>";
	}
}

function debug_displaystaff($staffs, $debug) {
	if (!$debug) return;

	for (; current($staffs) != null; next($staffs)) {
		echo key($staffs) . "|" . current($staffs) . "<br>";
	}
	echo "====================" . "<br>";
}

function debug_displayproject($projects, $debug) {
	if (!$debug) return;

	echo "<br>";
	foreach ($projects as $project) {
		echo $project->getID() . "|" . $project->getStaff() . "|" . $project->getExaminer() . "<br>";
	}
	echo "<br>";
}

function debug_displayassigned($staff, $projects, $debug) {
	if (!$debug) return;

	if ($staff != null) echo $staff->getID() . "=" . count($projects) . "-" . count(array_filter($projects)) . "==";
	foreach ($projects as $value) echo "=" . $value;
	echo "<br><br>";
}

function getFreeSlot($slots, $vacancy, $ignore) {
	$start_slot = -1;
	$count = 0;
	$expect = $vacancy + $ignore;
	for ($i = 0; $i < count($slots); $i++) {
		if ($slots[$i] == null) {
			$count++;
			if ($start_slot == -1) $start_slot = $i;
			if ($count == $expect) break;
		} else {
			$count = 0;
			$start_slot = -1;
		}
	}
	// if count is not as expected and number of slots is more than required
	if (($count != $expect && $vacancy <= count($slots)) ||  //
		($count != $expect && $vacancy > count($slots) && ($ignore > 0 || $count != count($slots)))) {
		$start_slot = -1;
	} else {
		$start_slot += $ignore;
	}
	return array($start_slot, $count - $ignore);
}

function getFirstStaff($stafflist, $projectlist, $ignore, $debug) {
	$temp_staff = array();
	foreach ($stafflist as $staff) {
		$count = 0;
		foreach ($projectlist as $project) {
			if (($staff->getID() == $project->getStaff()) || ($staff->getID() == $project->getExaminer())) {
				$count++;
			}
		}
		$temp_staff[$staff->getID()] = $count;
	}

	//remove those staff with no projects
	$temp_staff = array_filter($temp_staff, function ($x) {
		return $x != 0;
	});
	arsort($temp_staff);
	debug_displaystaff($temp_staff, $debug);

	for (; current($temp_staff) != null; next($temp_staff)) {
		if ($ignore == 0) return $stafflist[key($temp_staff)];
		$ignore--;
	}
	return null;
}

function getFirstProjects($staff, $projectlist) {
	$new_project = array();
	foreach ($projectlist as $project) {
		if (($staff->getID() == $project->getStaff()) || ($staff->getID() == $project->getExaminer())) {
			$new_project[$project->getID()] = $project;
		}
	}
	return $new_project;
}

function getSecondStaff($first, $stafflist, $projects) {
	$new_staff = array();
	foreach ($projects as $project) {
		if (array_key_exists($project->getStaff(), $stafflist) && array_key_exists($project->getExaminer(), $stafflist)) {
			if ($project->getStaff() == $first->getID()) {
				$new_staff[$project->getID()] = $stafflist[$project->getExaminer()];
			} else {
				$new_staff[$project->getID()] = $stafflist[$project->getStaff()];
			}
		}
	}
	return $new_staff;
}

function getSecondAssigned($staffs, $day, $start_slot, $slot_space) {
	global $timeslots_table;

	$avail_staff = array();
	for (; current($staffs) != null; next($staffs)) {
		$avail_slot = array();
		for ($i = 0; $i < $slot_space; $i++) {
			if (current($staffs)->isAvailable($day,
				$timeslots_table[$day][$start_slot + $i]->getStartTime(),
				$timeslots_table[$day][$start_slot + $i]->getEndTime()
			)) {
				$avail_slot[$i] = true;
			} else {
				$avail_slot[$i] = false;
			}
		}
		$avail_staff[key($staffs)] = $avail_slot;
	}

	$avail_staff = array_filter($avail_staff, function ($x) {
		return count(array_keys($x, true)) > 1;
	});

	uasort($avail_staff, function ($a, $b) {
		$a_count = count(array_keys($a, true));
		$b_count = count(array_keys($b, true));

		return $b_count - $a_count;
	});

	$new_staff = array_fill(0, $slot_space, null);
	for ($i = 0; $i < $slot_space; $i++) {
		for (; current($avail_staff) != null; next($avail_staff)) {
			$key = key($avail_staff);
			if ($new_staff[$i] != null) continue; //slot is filled
			if ($avail_staff[$key][$i]) {
				$new_staff[$i] = $key;
				unset($avail_staff[$key]);
				break;
			}
		}
		reset($avail_staff);
	}
	ksort($new_staff);
	return $new_staff;
}

function addStaffException($projectlist, $stafflist, $order, $day, $start_slot) {
	global $timeslots_table;

	for ($i = 0; $i < count($order); $i++) {
		if ($order[$i] == null) continue;
		$project = $projectlist[$order[$i]];

		$stafflist[$project->getStaff()]->addTimeslotException($day,
			$timeslots_table[$day][$start_slot + $i]->getStartTime(),
			$timeslots_table[$day][$start_slot + $i]->getEndTime()
		);

		$stafflist[$project->getExaminer()]->addTimeslotException($day,
			$timeslots_table[$day][$start_slot + $i]->getStartTime(),
			$timeslots_table[$day][$start_slot + $i]->getEndTime()
		);
	}
	return $stafflist;
}

function removeProjects($projectlist, $remove_project) {
	foreach ($remove_project as $r) {
		unset($projectlist[$r]);
	}
	return $projectlist;
}

function updateRoomList($room, $order, $start_slot) {
	for ($i = 0; $i < count($order); $i++) {
		$room[$start_slot + $i] = $order[$i];
	}
	return $room;
}

function assignProjectList($projectlist, $project, $day, $room, $start_slot) {
	for ($i = 0; $i < count($project); $i++) {
		if ($project[$i] == null) continue;
		$projectlist[$project[$i]]->assignTimeslot($day, $room, $start_slot + $i);
	}
	return $projectlist;
}

/***new algo end**/

function skipDecision($dayIndex, $projectList) {
	if (count($projectList) == 0) {
		return false;
	}
	global $overallTimeTable;
	$count = 0;
	// for all rooms
	for ($i = 0; $i < count($overallTimeTable[$dayIndex]); $i++) {
		// for all time slots
		for ($t = 0; $t < count($overallTimeTable[$dayIndex][$i]); $t++) {
			if ($overallTimeTable[$dayIndex][$i][$t] == null) {
				$count++;
			}
		}
	}
	if ($count > count($projectList)) {
		return true;
	}
	return false;
}

function assignSkipping($projectList, $staffList, $overallTimeTable, $dayIndex) {
	global $timeslots_table;
	// assigning remaining project by skipping time slot/room
	for ($r = 0; $r < count($projectList); $r++) {
		$nextProject = false;
		// for all rooms
		for ($i = 0; !$nextProject && $i < count($overallTimeTable[$dayIndex]); $i++) {
			// for all time slots
			for ($t = 0; !$nextProject && $t < count($overallTimeTable[$dayIndex][$i]); $t++) {
				$current_slot = $overallTimeTable[$dayIndex][$i][$t];
				// if room not occupied, proceed
				if ($current_slot == null) {
					$current_project = array_values($projectList)[$r];
					$collision = false;
					//Check for supervisor/examiner time exceptions
					$current_supervisor = $current_project->getStaff();
					$current_examiner = $current_project->getExaminer();
					$supervisor_available = $staffList[$current_supervisor]->isAvailable($dayIndex, $timeslots_table[$dayIndex][$t]->getStartTime(), $timeslots_table[$dayIndex][$t]->getEndTime());
					$examiner_available = $staffList[$current_examiner]->isAvailable($dayIndex, $timeslots_table[$dayIndex][$t]->getStartTime(), $timeslots_table[$dayIndex][$t]->getEndTime());
					// constraint checking
					for ($c = 0; !$collision && $c < count($overallTimeTable[$dayIndex]); $c++) {
						if ($overallTimeTable[$dayIndex][0][$c][$t] == null) {
							break;
						}
						if ($overallTimeTable[$dayIndex][0][$c][$t] != null) {
							$adjacent_supervisor = $overallTimeTable[$dayIndex][0][$c][$t]->getStaff();
							$adjacent_examiner = $overallTimeTable[$dayIndex][0][$c][$t]->getExaminer();
							if ($current_supervisor == $adjacent_supervisor ||
								$current_supervisor == $adjacent_examiner ||
								$current_examiner == $adjacent_supervisor ||
								$current_examiner == $adjacent_examiner) {
								$collision = true;
							}
						}
					}
					//Collision Detected. Abort current allocation cycle. (Try Next Slot)
					if (!$supervisor_available || !$examiner_available || $collision) {
						continue;
					}
					//Assign current project to current slot
					if (!$current_project->isAssignedTimeslot()) {
						$current_project->assignTimeslot(0, $i, $t);
						$overallTimeTable[$dayIndex][$i][$t] = $current_project;
						$nextProject = true;
						break;
					}
				}
			}
		}
	}
	return $projectList;
}

function removeAssignedProjects($projectList) {

	foreach ($projectList as $key => $project) {
		if ($project->isAssignedTimeslot()) {
			unset($projectList[$key]);
		}
	}
	return $projectList;
}

function indexStaff($staffs, $exceptions) {
	//Staff
	foreach ($staffs as $staff) { //Index Staff by staffid
		$staffList[$staff['staffid']] = new Staff($staff['staffid'], $staff['salutation'], $staff['staffname']);

		foreach ($exceptions as $exception) {
			if ($exception['staff_id'] == $staff['staffid']) {
				$cur_day = ($exception['day'] == '*') ? -1 : $exception['day'] - 1;
				$staffList[$staff['staffid']]->addTimeslotException($cur_day,
					DateTime::createFromFormat('H:i:s', $exception['time_start']),
					DateTime::createFromFormat('H:i:s', $exception['time_end']));

				//echo "[Exception] " . $staff['staffid']. ": Day " . $cur_day. " [ " .current($staffList[ $staff['staffid'] ]->timeslotException)->toString(). " ]<br/>";
			}
		}

	}
	return $staffList;
}

function indexProjects($staffList, $projects, $projectList) {
	global $TABLES, $conn_db_ntu;

	$query_createSupervisingCountView = "create or replace view v_supervising_count as select count(fa.staff_id) as supervising_count,fa.staff_id as staff_id from " . $TABLES['allocation_result'] . " as r left join " . $TABLES['fyp_assign'] . " as fa on r.project_id = fa.project_id group by fa.staff_id order by supervising_count desc";
	$conn_db_ntu->query($query_createSupervisingCountView);

	//sort projects by supervising count (>=4) so as to minimise supervisor movement
	//$query_sortProjectBySupervisingCount = "select r.project_id, r.examiner_id, fa.staff_id, vc.supervising_count from " . $TABLES['allocation_result'] . " as r left join " . $TABLES['fyp_assign']. " as fa on r.project_id = fa.project_id left join v_supervising_count as vc on fa.staff_id = vc.staff_id where vc.supervising_count >= 4 order by vc.supervising_count desc, fa.staff_id, r.examiner_id , r.project_id";
	//$projectsSortedBySupervisingCount = $conn_db_ntu->query( $query_sortProjectBySupervisingCount )->fetchAll();

	$query_createExaminerCountView = "create or replace view v_examiner_count as select count(r.examiner_id) as examiner_count, r.examiner_id as examiner_id from " . $TABLES['allocation_result'] . " as r left join " . $TABLES['fyp_assign'] . " as fa on r.project_id = fa.project_id group by r.examiner_id order by examiner_count desc";
	$conn_db_ntu->query($query_createExaminerCountView);

	//sort projects by examiner count (supervising count <4 ) so as to minimise examiner movement
	//$query_sortProjectByExaminerCount = "select r.project_id, r.examiner_id, fa.staff_id,vec.examiner_count from " . $TABLES['allocation_result'] . " as r left join " . $TABLES['fyp_assign']. " as fa on r.project_id = fa.project_id left join v_supervising_count as vc on fa.staff_id = vc.staff_id left join v_examiner_count as vec on vec.examiner_id = r.examiner_id where vc.supervising_count < 4 order by vec.examiner_count desc, r.examiner_id ,fa.staff_id, r.project_id";
	//$projectsSortedByExaminerCount = $conn_db_ntu->query( $query_sortProjectByExaminerCount )->fetchAll();

	$query_createProjectsInvolvementCountView = "create or replace view projects_involvement_count as 
                                                    select * from v_supervising_count as sc 
                                                    left join v_examiner_count as ec 
                                                    on sc.staff_id=ec.examiner_id 
                                                    union 
                                                    select * from v_supervising_count as sc 
                                                    right join v_examiner_count as ec 
                                                    on sc.staff_id=ec.examiner_id";
	$conn_db_ntu->query($query_createProjectsInvolvementCountView);

	$query_createCountView = "create or replace view `count` as
                                select coalesce(staff_id, examiner_id) as staff_id, coalesce(supervising_count+examiner_count,supervising_count,examiner_count) as total_count
                                from projects_involvement_count
                                order by total_count desc";
	$conn_db_ntu->query($query_createCountView);

	$query_sortProjectsByInvolvementCount = "select r.project_id, fa.staff_id, r.examiner_id, c.total_count from " . $TABLES['allocation_result'] . " as r left join " . $TABLES['fyp_assign'] . " as fa on r.project_id = fa.project_id right join count as c on fa.staff_id = c.staff_id or c.staff_id = r.examiner_id";
	$projectsSortedByCount = $conn_db_ntu->query($query_sortProjectsByInvolvementCount)->fetchAll();
	$projectsSortedByCount = array_unique($projectsSortedByCount, SORT_REGULAR);
	//$projectsCombined = array_merge($projectsSortedBySupervisingCount, $projectsSortedByExaminerCount);

	foreach ($projectsSortedByCount as $project) {
		$projectList[$project['project_id']] = new Project($project['project_id'], $project['staff_id'], $project['examiner_id'], '-');
	}
	/*foreach ($projectList as $project) {
		  //var_dump($project);

		  echo ("project id: " . $project->getID());
		  echo ("<br>");
		  echo ("staff id: " . $project->getStaff());
		  echo ("<br>");
		  echo ("examiner id: " . $project->getExaminer());
		  echo ("<br>");
	 }*/

	return $projectList;
}

/*function indexProjects ($staffList, $projects, $projectList) {
	//Projects
	global $TABLES, $conn_db_ntu;
    $query_getSupervisingProjects = "select count(fa.staff_id) as supervising_count, fa.staff_id from " . $TABLES['allocation_result'] . " as r left join " . $TABLES['fyp_assign'] . " as fa on r.project_id = fa.project_id group by fa.staff_id order by supervising_count desc";
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
	uasort($staffList, "CmpPriorityDesc"); //Calculate Staff Priority
	$projectList = array(); //Flush Current Project List
	foreach($staffList as $staff) //Regenerate the Project List according to new priority model
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
	$timetable = array_fill(0, $NO_OF_ROOMS, array_fill(0, $MAX_SLOTS, NULL));
	return $timetable;
}

function createSlotUsed($day, $NO_OF_ROOMS) {
	$slotused = array_fill(0, $NO_OF_ROOMS, 0);
	return $slotused; //each room, has how many timeslots occupied (sum-up should be the <= $NO_OF_TIMESLOTS)
}

function allocateTimeSlotsByDay($dayIndex, $staffList, $projectList, $MAX_SLOTS, $NO_OF_TIMESLOTS) {
	global $timeslots_table, $NO_OF_ROOMS;

	$actualDay = $dayIndex + 1;
	$rooms_table = retrieveRooms($actualDay, "allocation_settings_room");
	$NO_OF_ROOMS = count($rooms_table);
	$timetable = createTimeTable($actualDay, $NO_OF_ROOMS, $MAX_SLOTS);

	//Counter to determine up to which slot has been occupied (Speeds up allocation process)
	$slotused = createSlotUsed($actualDay, $NO_OF_ROOMS);

//	$totalTimeTableSlots = 0;
//	if (sizeof($timetable) > 0) {
//		$totalTimeTableSlots = array_sum(array_map("count", $timetable[0]));
//	}

	$projectList = assignRooms($projectList, $staffList, $timetable, $slotused, $dayIndex, $NO_OF_ROOMS, $NO_OF_TIMESLOTS);
	return $projectList;
}

function assignRooms($projectList, $staffList, $timetable, $slotused, $dayIndex, $NO_OF_ROOMS, $NO_OF_TIMESLOTS) {
	global $timeslots_table, $overallTimeTable, $MAX_SLOTS;

	//Phase 2.1: Sequential Assignment
	//Check if timeslot available
	$collisionCount = 0;
	for ($att = 0; $att < 100; $att++) { //loops for 100 times
		for ($i = 0; $i < count($projectList); $i++) {
			// break out of project list loop when room/slots for the day is full
			if ($timetable[$NO_OF_ROOMS - 1][$MAX_SLOTS - 1] != null) { //if last slot of the day is taken, break
				break;
			}
			$current_project = array_values($projectList)[$i];

			for ($room = 0; $room < $NO_OF_ROOMS; $room++) {
				$current_slot = $slotused[$room];
				// if room full
				if ($current_slot >= $NO_OF_TIMESLOTS[$dayIndex]) {
					continue;
				} // if room not full
				else {
					// if slot occupied, break
					if ($timetable[$room][$current_slot] != null) {
						break;
					}
					//Check for supervisor/examiner time exceptions
					$collision = false;

					//check if the staff and examiner is available during this timeslot | exemptions
					$current_supervisor = $current_project->getStaff();
					$current_examiner = $current_project->getExaminer();
					$supervisor_available = $staffList[$current_supervisor]->isAvailable($dayIndex, $timeslots_table[$dayIndex][$current_slot]->getStartTime(), $timeslots_table[$dayIndex][$current_slot]->getEndTime());
					$examiner_available = $staffList[$current_examiner]->isAvailable($dayIndex, $timeslots_table[$dayIndex][$current_slot]->getStartTime(), $timeslots_table[$dayIndex][$current_slot]->getEndTime());

					// constraint checking | check at that timeslot if clash with another room?
					for ($r = 0; !$collision && $r < $NO_OF_ROOMS; $r++) {
						if ($timetable[$r][$current_slot] == null) {
							break;
						}
						if ($timetable[$r][$current_slot] != null) {
							$adjacent_supervisor = $timetable[$r][$current_slot]->getStaff();
							$adjacent_examiner = $timetable[$r][$current_slot]->getExaminer();
							if ($current_supervisor == $adjacent_supervisor ||
								$current_supervisor == $adjacent_examiner ||
								$current_examiner == $adjacent_supervisor ||
								$current_examiner == $adjacent_examiner) {
								$collision = true;
							}
						}
					}

					//Collision Detected. Abort current allocation cycle. (Try Next Slot)
					if (!$supervisor_available || !$examiner_available || $collision) {
						//echo "<br>";
						//echo $collision ? 'true' : 'false';
						//echo "<br/> collision = " . $collision;
						//echo "<br/> ";
						$collisionCount++;
						break;
					}

					//Assign current project to current slot
					if (!$current_project->isAssignedTimeslot()) {
						$timetable[$room][$current_slot] = $current_project;
						$current_project->assignTimeslot($dayIndex, $room, $current_slot);
						$slotused[$room]++;
						break;
					}
				}
			}
		}
	}

	$overallTimeTable[$dayIndex] = $timetable;
	return $projectList;
}

function insertValuesIntoDB($dayIndex, $projectList) {
	//Bulk Insert
	//Timeslot
	global $conn_db_ntu, $TABLES, $timeslots_table;
	$solution_found = false;
	$actualDay = $dayIndex + 1;
	$stmt1 = $conn_db_ntu->prepare("delete from " . $TABLES['allocation_result_timeslot'] . " where day = ? ");
	$stmt1->bindParam("1", $actualDay);
	$stmt1->execute();
	$values = array();

	foreach ($timeslots_table[$dayIndex] as $timeslot) {
		//var_dump( $timeslot);
		$timeSlotID = $timeslot->getID();
		$timeSlotDay = $timeslot->getDay();
		$slot = $timeslot->getSlot();
		$timeSlotST = $timeslot->getStartTime()->format('H:i:s');
		$timeSlotET = $timeslot->getEndTime()->format('H:i:s');
		$stmt1 = $conn_db_ntu->prepare("select * from " . $TABLES['allocation_result_timeslot'] . " where id = ?");
		$stmt1->bindParam("1", $timeSlotID);
		$stmt1->execute();


		$existingRecords = $stmt1->fetchAll();
		if (sizeof($existingRecords) > 0) {
			$stmt1 = $conn_db_ntu->prepare("update " . $TABLES['allocation_result_timeslot'] . " set day = ?, slot = ?, time_start = ?, time_end = ? where id = ?");
			$stmt1->bindParam(1, $timeSlotDay);
			$stmt1->bindParam(2, $slot);
			$stmt1->bindParam(3, $timeSlotST);
			$stmt1->bindParam(4, $timeSlotET);
			$stmt1->bindParam(5, $timeSlotID);
			$stmt1->execute();
		} else {
			$stmt1 = $conn_db_ntu->prepare("insert into " . $TABLES['allocation_result_timeslot'] . " (`id`, `day`, `slot`, `time_start`, `time_end`) VALUES (?, ?, ?, ?, ?)");
			$stmt1->bindParam(1, $timeSlotID);
			$stmt1->bindParam(2, $timeSlotDay);
			$stmt1->bindParam(3, $slot);
			$stmt1->bindParam(4, $timeSlotST);
			$stmt1->bindParam(5, $timeSlotET);
			$stmt1->execute();
		}
	}

	//Rooms
	$stmt1 = $conn_db_ntu->prepare("delete from " . $TABLES['allocation_result_room'] . " where day = ? ");
	$stmt1->bindParam(1, $actualDay);
	$stmt1->execute();

	$stmt1 = $conn_db_ntu->prepare("select roomArray from " . $TABLES['allocation_settings_room'] . " where day = ? ");
	$stmt1->bindParam("1", $actualDay);
	$stmt1->execute();
	$rooms = $stmt1->fetchAll();

	if (sizeof($rooms) > 0) {
		$roomsArr = $rooms[0]["roomArray"];

		$stmt1 = $conn_db_ntu->prepare("insert into " . $TABLES['allocation_result_room'] . " ( `day`, `roomArray`) VALUES (? , ?)");
		$stmt1->bindParam("1", $actualDay);

		//var_dump($roomArr);
		$stmt1->bindParam("2", $roomsArr);
		$stmt1->execute();
	}
	foreach ($projectList as $project) {
		$dayUs = -1;
		$time = -1;
		$room = -1;

		if ($project->isAssignedTimeslot()) {
			$dayUs = $project->getAssigned_Day() + 1; //Offset to database
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
		$stmt1 = $conn_db_ntu->prepare("update " . $TABLES['allocation_result'] . " set day=0, slot =NULL, room =NULL where project_id = ? ");
		$stmt1->bindParam("1", $projectID);
		$stmt1->execute();

		$stmt1 = $conn_db_ntu->prepare("select * from " . $TABLES['allocation_result'] . " where project_id = ? ");
		$stmt1->bindParam("1", $projectID);
		$stmt1->execute();
		$existingRes = $stmt1->fetchAll();

		if (sizeof($existingRes) > 0) {
			$stmt1 = $conn_db_ntu->prepare("update " . $TABLES['allocation_result'] . " set day = ? , slot = ?, room =?, clash=0 where project_id = ? ");
			//$stmt1->bindParam("1",$examinerID);
			$stmt1->bindParam("1", $dayUs);
			$stmt1->bindParam("2", $time);
			$stmt1->bindParam("3", $room);
			$stmt1->bindParam("4", $projectID);
			$stmt1->execute();
		} else {
			$stmt1 = $conn_db_ntu->prepare("insert into  " . $TABLES['allocation_result'] . " (`project_id`, `examiner_id`, `day`, `slot`, `room`, `clash`) VALUES(?,?,?,?,?,0");
			$stmt1->bindParam("1", $projectID);
			$stmt1->bindParam("2", $examinerID);
			$stmt1->bindParam("3", $dayUs);
			$stmt1->bindParam("4", $time);
			$stmt1->bindParam("5", $room);
			$stmt1->execute();

		}
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

if ($redirect) {
	echo ($error_code != 0) ? "error_timeslot=$error_code" : "allocate_timeslot=$allocate_code";
	return;
}

?>
    <!DOCTYPE html >
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>FYP Examiner Allocation System </title>

    </head>
    <body>
	<?php
	//Print Project List Table
	function printTimeTable($projectList, $overallTimeTable, $NO_OF_DAYS, $NO_OF_TIMESLOTS) {

		$index = 0;
		echo "<h3>PROJECT LIST</h3>";
		echo '<table style="text-align:center;" border="1"><tr>
				<th>ProjectID</th>
				<th>Staff</th>
				<th>Examiner</th>
				</tr>';
		foreach ($projectList as $project) {
			echo '<tr>';
			echo '<td>' . $project->getID() . '</td>';

			if ($project->getStaff() !== '')
				echo '<td>' . $project->getStaff() . '</td>';
			else
				echo '<td> - </td>';

			if ($project->getExaminer() !== '')
				echo '<td>' . $project->getExaminer() . '</td>';
			else
				echo '<td> - </td>';

			echo '</tr>';
		}
		echo '</table>';

		//Print Allocation Results
		echo "<h3>TIMETABLE PLAN</h3>";
		echo($NO_OF_DAYS);
		for ($dayIndex = 0; $dayIndex < $NO_OF_DAYS; $dayIndex++) {
			$actualDay = $dayIndex + 1;
			echo '<b>Day ' . intval($actualDay) . '</b>';
			echo '<table style="text-align:center;" border="1"><tr>';

			//Header
			echo '<tr>';
			echo '<th></th>';
			for ($timeslot = 0; $timeslot < $NO_OF_TIMESLOTS[$dayIndex]; $timeslot++) {
				echo '<th>Slot ' . intval($timeslot + 1) . '</th>';
			}
			echo '</tr>';

			//Body
			$timetable = $overallTimeTable[$dayIndex];
			//echo '<br>Timetable for index ' . $day;
			//echo '<br>';
			//var_dump ($overallTimeTable[$day]);

			$rooms_table = retrieveRooms($actualDay, "allocation_settings_room");
			$NO_OF_ROOMS = count($rooms_table);
			echo '<br>';
			echo(" room count: " . $NO_OF_ROOMS);
			echo '<br>';
			echo("timeslot count: " . $NO_OF_TIMESLOTS[$dayIndex] . "day: " . $dayIndex);
			//echo '<br>Actual Timetable:' . $day;
			echo '<br>';
			//var_dump ($timetable);
			for ($room = 0; $room < $NO_OF_ROOMS; $room++) {

				echo '<tr>';
				echo '<td>Room ' . intval($room + 1) . '</td>';

				for ($timeslot = 0; $timeslot < $NO_OF_TIMESLOTS[$dayIndex]; $timeslot++) {
					echo '<br>';

					//echo ("day : " . $day . " room : " . $room . " timeslot. " . $timeslot);

					if ($timetable[$index][$room][$timeslot] != null) {
						$details = "Supervisor : " . $timetable[$index][$room][$timeslot]->getStaff() . "\nExaminer: " . $timetable[$index][$room][$timeslot]->getExaminer();
					} else {
						$details = "";
					}
					echo '<td title="' . $details . '">';
					if ($timetable[$index][$room][$timeslot] !== null) {
						echo $timetable[$index][$room][$timeslot]->getID();
					} else {
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
		foreach ($projectList as $project) {
			if ($project->isAssignedTimeslot())
				$assignedProjects++;
			else
				echo $project->getID() . "<br/>";
		}

		echo "<h4> Assignment Statistics </h4>";
		echo "<p>";
		echo "Projects Allocated: " . intval($assignedProjects) . " / " . count($projectList) . "<br/>";
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