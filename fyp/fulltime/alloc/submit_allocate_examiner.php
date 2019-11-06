<?php
require_once('./entity.php');
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');
include('../../../simple_html_dom.php');


$stmt_selectPrefBasedOnResearchInterest = $conn_db_ntu->prepare("SELECT *, LOWER(staff_id) as staff_id  FROM " . $TABLES['staff_pref'] . " WHERE choice >= 100 and archive = 0");
$stmt_selectPrefBasedOnResearchInterest->execute();
$selectPrefBasedOnResearchInterest = $stmt_selectPrefBasedOnResearchInterest->fetchAll(PDO::FETCH_ASSOC);

$stmt_checkResearchInterestExists = $conn_db_ntu->prepare("SELECT * FROM " . $TABLES['research_interest']);
$stmt_checkResearchInterestExists->execute();
$checkResearchInterestExists = $stmt_checkResearchInterestExists->fetchAll(PDO::FETCH_ASSOC);

if ($selectPrefBasedOnResearchInterest == null || sizeof($selectPrefBasedOnResearchInterest) <= 0) {
	if ($checkResearchInterestExists == null || sizeof($checkResearchInterestExists) <= 0) {
		$html = file_get_html('https://www3.ntu.edu.sg/SCSE/moss_staffac.asp');
		$htmlData = trim($html);
		if (!empty($htmlData)) {
			$count = 0;

			// Find the table containing the staff table, followed by the table that contains individual staff details
			$outerTable = $html->find('table[class="style1"]', 1);
			$staffTable = $outerTable->find('table', 0);
			$table = $staffTable->find('table[class="style1"]');

			foreach ($table as $row) {
				$staffDetails = $row->find('span[class="parahead2"]', 0)->plaintext;
				$getStaffEmail = $row->find('a', -1)->plaintext;
				$staffEmail = preg_split('/[{|[]|@/', $getStaffEmail);
				$email = strtolower($staffEmail[0]);

				$researchInterestTable = $row->find('table', 0);
				$researchInterestRow = $researchInterestTable->find('tr');

				// No research interest
				if ($researchInterestTable == null || $researchInterestRow == null) {
					$empty = "";
					// Insert into database
					$stmt_insert = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['research_interest']." (staff_id, interest) VALUES (?, ?)");
					$stmt_insert->bindParam(1, $email);
					$stmt_insert->bindParam(2, $empty);
					$stmt_insert->execute();
				}
				else {
					foreach ($researchInterestRow as $researchInterest) {
						$interest = $researchInterest->find('td', 1)->plaintext;

						// Insert into database
						$stmt_insert = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['research_interest']." (staff_id, interest) VALUES (?, ?)");
						$stmt_insert->bindParam(1, $email);
						$stmt_insert->bindParam(2, $interest);
						$stmt_insert->execute();
					}
				}
				$count++;
			}
		}
	}

	// Retrieve those staff who has totally no Preference
	$query_rsStaffNoPreference		= "SELECT id FROM " . $TABLES["staff"] .
		" WHERE id NOT IN (SELECT LOWER(staff_id) as staff_id FROM " . $TABLES["staff_pref"] . " WHERE archive = 0)";

	$stmt_staffNoPreference = $conn_db_ntu->prepare($query_rsStaffNoPreference);
	$stmt_staffNoPreference->execute();
	$rsStaffNoPreference = $stmt_staffNoPreference->fetchAll(PDO::FETCH_ASSOC);

	// Foreach staff
	foreach ($rsStaffNoPreference as $row_rsStaffNoPreference) {
		// retrieve his research interest
		$query_rsGetStaffResearchInterest		= "SELECT * FROM " . $TABLES["research_interest"] .
			" WHERE staff_id = ?";

		$stmt_getStaffResearchInterest = $conn_db_ntu->prepare($query_rsGetStaffResearchInterest);
		$stmt_getStaffResearchInterest->bindParam(1, $row_rsStaffNoPreference["id"]);
		$stmt_getStaffResearchInterest->execute();
		$rsGetStaffResearchInterest = $stmt_getStaffResearchInterest->fetchAll(PDO::FETCH_ASSOC);

		// retrieve all area preference available
		$query_rsGetAllInterestArea		= "SELECT * FROM " . $TABLES["interest_area"];

		$stmt_getAllInterestArea = $conn_db_ntu->prepare($query_rsGetAllInterestArea);
		$stmt_getAllInterestArea->execute();
		$rsGetAllInterestArea = $stmt_getAllInterestArea->fetchAll(PDO::FETCH_ASSOC);

		$archive = 0;
		$choiceCount = 100;
		//$areaFound = false;
		$date   = new DateTime(); //this returns the current date time
		$currentDateTime = $date->format('Y-m-d H:i:s');

		// if no match above
		//if ($areaFound == false) {
		foreach ($rsGetStaffResearchInterest as $row_rsGetStaffResearchInterest) {
			$maxMatch = 0;
			$currentInterestMatch = "";

			foreach ($rsGetAllInterestArea as $row_rsGetAllInterestArea) {
				$currentMatch = 0;
				$keywordArray = explode(',', $row_rsGetAllInterestArea['keyword']);

				foreach ($keywordArray as $key) {
					if (stripos(($row_rsGetStaffResearchInterest['interest']), $key) !== false) {
						$currentMatch++;
					}
				}

				if ($currentMatch > $maxMatch) {
					$maxMatch = $currentMatch;
					$currentInterestMatch = $row_rsGetAllInterestArea['key'];
				}
			} // end foreach interest loop

			if ($currentInterestMatch != "") {
				// Check if the preference has already been added before
				$query_rsCheckPreferenceExist		= "SELECT *, LOWER(staff_id) as staff_id FROM " . $TABLES["staff_pref"] .
					" WHERE staff_id = ? AND prefer = ?";

				$stmt_checkPreferenceExist = $conn_db_ntu->prepare($query_rsCheckPreferenceExist);
				$stmt_checkPreferenceExist->bindParam(1, $row_rsStaffNoPreference["id"]);
				$stmt_checkPreferenceExist->bindParam(2, $currentInterestMatch);
				$stmt_checkPreferenceExist->execute();
				$rsCheckPreferenceExist = $stmt_checkPreferenceExist->fetchAll(PDO::FETCH_ASSOC);

				if ($rsCheckPreferenceExist == null || sizeof($rsCheckPreferenceExist) == 0) {
					// insert
					$insertStmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['staff_pref'] . " (staff_id, prefer, choice, choose_time, archive) VALUES (?, ?, ?, ?, ?)");
					$insertStmt->bindParam(1, $row_rsStaffNoPreference['id']);
					$insertStmt->bindParam(2, $currentInterestMatch);
					$insertStmt->bindParam(3, $choiceCount);
					$insertStmt->bindParam(4, $currentDateTime);
					$insertStmt->bindParam(5, $archive);
					$insertStmt->execute();

					$choiceCount++;
				}
			}
		}
	}
}



//redirect initialised as false first
$redirect = false;
$error_code = -1;
$_SESSION['error_examiner'] = -1;
$staff_workload = 0;
$WORKLOAD_PER_PROJECT_EXAMINED = 1;
$WORKLOAD_TOTALPROJECTS = 0;

function CmpWorkloadAsc($a, $b) {
	if ($a->getWorkload() == $b->getWorkload())
		return 0;
	else
		return ($a->getWorkload() < $b->getWorkload()) ? -1 : 1;
}

//Assignment Settings
try {

	$query_rsOtherSettings = "select * from " . $TABLES['allocation_settings_others'] . " where type = 'FT'";
	$otherSettings = $conn_db_ntu->query($query_rsOtherSettings)->fetch();

} catch (PDOException $e) {
	die($e->getMessage());
}

//Exam Year & Sem settings
$today = new DateTime();

/* Parse Settings */
try {
	$examYearValue = $otherSettings['exam_year'];
	$examSemValue = $otherSettings['exam_sem'];
} catch (Exception $e) {
	//Default Values
	$examYearValue = $today->format('Y');     // Current Year (Default)
	$examSemValue = 0; // Default Sem = 0
}

//$_SESSION['examSemValue']  = $examSemValue;
//$_SESSION['examYearValue'] = $examYearValue;
/* Converting DB to Object Models */
$query_rsInterestArea = "select * from " . $TABLES["interest_area"];

$exempt_sem = ($examSemValue == 2) ? "s.exemptionS2" : "s.exemption";
$query_rsStaff = "select s.id as staffid, s.name as staffname, s.position as salutation, s.exemption as exemption, s.exemptionS2 as exemptionS2, coalesce(" . $exempt_sem . ", 0) as workload, coalesce(s.examine, 1) as examine from " . $TABLES['staff'] . " as s where s.examine=1 order by " . $exempt_sem . " asc, staffid asc";

$query_rsProjPref = "select *, LOWER(staff_id) as staff_id  from " . $TABLES['staff_pref'] . " where (prefer like 'SCE%' or prefer like 'SCSE%') and archive = 0 order by choice asc";

//$query_rsAreaPref = "select * from " . $TABLES['staff_pref'] . " where prefer not like 'SCE%' order by choice asc";
$query_rsAreaPref = "select *, LOWER(staff_id) as staff_id  from " . $TABLES['staff_pref'] . " as sp inner join " . $TABLES['interest_area'] . " as ia on sp.prefer= ia.key  and  archive =0 order by choice asc";

$query_rsExaminableProject = "select t1.pno, t1.staffid, t1.exam_year, t1.exam_sem, t1.ptitle, t1.parea1, t1.parea2, t1.parea3, t1.parea4, t1.parea5, count(t2.projects) as chosen from (select p3.project_id as pno, p2.staff_id as staffid, p3.examine_year as exam_year, p3.examine_sem as exam_sem, p1.title as ptitle, p1.Area1 as parea1 , p1.Area2 as parea2 , p1.Area3 as parea3 , p1.Area4 as parea4 , p1.Area5 as parea5 from `fea_projects` as p3 left join `fyp_assign` as p2 on p3.project_id=p2.project_id left join `fyp` as p1 on p2.project_id=p1.project_id where p2.complete = 0 and p3.examine_year = " . $examYearValue . " and p3.examine_sem = " . $examSemValue . ") as t1 left join (select prefer as projects from `fea_staff_pref` where archive = 0) as t2 on t1.pno=t2.projects group by t1.pno order by chosen asc";

$query_rsTotalProject = "select p3.project_id as pno, p2.staff_id as staffid, p3.examine_year as exam_year, p3.examine_sem as exam_sem, p1.title as ptitle, p1.Area1 as parea1 , p1.Area2 as parea2 , p1.Area3 as parea3 , p1.Area4 as parea4 , p1.Area5 as parea5 from " . $TABLES['fea_projects'] . " as p3 left join " . $TABLES['fyp_assign'] . " as p2 on p3.project_id=p2.project_id  left join " . $TABLES['fyp'] . " as p1 on p2.project_id=p1.project_id where p2.complete = 0 ";

// Need to get the last 2 sems project list
if ($examSemValue == 1) {
	$searchCurrentYear = $examYearValue;
	$searchLastYear = $examYearValue - 101;
	// 17/18 Sem1 + 16/17 Sem2
	$query_rsTotalProject .= " and p3.examine_year = " . $searchCurrentYear . " and p3.examine_sem = " . $examSemValue .
		" or p3.examine_year = " . $searchLastYear . " and p3.examine_sem = " . ($examSemValue + 1);
} else if ($examSemValue == 2) {
	// 17/18 Sem2+ 17/18 Sem1
	$query_rsTotalProject .= "and p3.examine_year = " . $examYearValue;
}
$query_rsTotalProject .= " order by p3.project_id asc ";

try {
	$interestAreas = $conn_db_ntu->query($query_rsInterestArea);
	$staffs = $conn_db_ntu->query($query_rsStaff);
	$projPrefs = $conn_db_ntu->query($query_rsProjPref);
	$areaPrefs = $conn_db_ntu->query($query_rsAreaPref);
	$totalProjects = $conn_db_ntu->query($query_rsTotalProject);
	$examinableProject = $conn_db_ntu->query($query_rsExaminableProject);
} catch (PDOException $e) {
	die($e->getMessage());
}

// Used for workload calculation
$WORKLOAD_TOTALPROJECTS = $totalProjects->rowCount();
// No examinableProject or staff

if ($examinableProject->rowCount() <= 0 || $staffs->rowCount() <= 0) {
	// echo "[Error] Problem loading staff/project list.";
	$error_code = 1;
	$_SESSION['error_examiner'] = 1;
} else {
	// Covert DB objects into arraylist

	// Interest Area
	$interestAreaList = array();
	foreach ($interestAreas as $interestArea) { //Index Staff by staffid
		$interestAreaList[$interestArea["key"]] = $interestArea["title"];
	}

	// Project
	$examinableProjectList = array();
	foreach ($examinableProject as $project) { //Index Project By pno
		$examinableProjectList[$project['pno']] = new Project($project['pno'], $project['staffid'], "", $project['ptitle']);
		//Assign Project Area
		for ($i = 1; $i <= 5; $i++) {
			if ($project['parea' . $i] == null) continue;
			$examinableProjectList[$project['pno']]->addProjectArea($project['parea' . $i]);
		}
	}

	// Staff
	$staffList = array();
	foreach ($staffs as $staff) { //Index Staff by staffid
		$staffList[$staff['staffid']] = new StaffAllocate($staff['staffid'], $staff['salutation'], $staff['staffname'], $staff['exemption'], $staff['exemptionS2'], $staff['workload']);
		$staff_workload += $staff['workload'];
	}

	// Staff Area Preference (Ordered by staff's choice)
	foreach ($areaPrefs as $areaPref) { //Area Preference
		if (!array_key_exists($areaPref['staff_id'], $staffList))
			continue;
		$staffList[$areaPref['staff_id']]->addInterestArea($areaPref['choice'], $areaPref['prefer']);
	}

	$projCount = array();
	// Staff Project Preference (Ordered by staff's choice)
	foreach ($projPrefs as $projPref) { //Project Preference
		if (!array_key_exists($projPref['staff_id'], $staffList))
			continue;
		$staffList[$projPref['staff_id']]->addInterestProject($projPref['choice'], $projPref['prefer']);

		if (array_key_exists($projPref['prefer'], $examinableProjectList)) {
			if (array_key_exists($projPref['prefer'], $projCount)) {
				$projCount[$projPref['prefer']]++;
			} else {
				$projCount[$projPref['prefer']] = 1;
			}
		}
	}

	//sort project list based on how many staff selected it (ascending)
	$sorted_projectlist = mergesort($projCount, $examinableProjectList);

	// Workload Sorting (asc) Auto-Correction (Used to patch the workload correction)
	uasort($staffList, "CmpWorkloadAsc");

	Algorithm_Random($staffList, $sorted_projectlist, $interestAreaList, $WORKLOAD_PER_PROJECT_EXAMINED, $WORKLOAD_TOTALPROJECTS); //if all no issue
	try {
		$conn_db_ntu->exec("delete from " . $TABLES['allocation_result']);
		$conn_db_ntu->exec("delete from " . $TABLES['allocation_result_room']);
		$conn_db_ntu->exec("delete from " . $TABLES['allocation_result_timeslot']);
	} catch (PDOException $e) {
		die($e->getMessage());
	}

	//Bulk Insert
	$values = array();
	foreach ($examinableProjectList as $project) {
		$values[] = sprintf("('%s', '%s', NULL, NULL, NULL)", $project->getID(), $project->getAssignedStaff());
	}

	$updateQuery = sprintf("insert into %s (`project_id`, `examiner_id`, `day`, `slot`, `room`) values %s on duplicate key update `examiner_id`=values(`examiner_id`), `day`=NULL, `slot`=NULL, `room`=NULL", $TABLES['allocation_result'], implode(",", $values));
	$conn_db_ntu->exec($updateQuery);
	unset($values);

	$conn_db_ntu->exec("delete from " . $TABLES['staff_pref'] . " where choice >= 100 and archive = 0");

	//echo "[PDO] Results Saved.<br/>";
	$error_code = 0;
	$_SESSION['error_examiner'] = 0;
	$_SESSION['allocate_examiner'] = 'allocated';

}

$conn_db_ntu = null;

//redirect set to true at the end to ensure everything has been executed successfully
$redirect = true;



function mergesort($count, $proj_info) {
	$final_list = array();

	$count += $proj_info;
	foreach (array_keys($count) as $key) {
		if (!is_numeric($count[$key])) $count[$key] = 0;
	}
	asort($count);
	foreach (array_keys($count) as $key) {
		$final_list[$key] = $proj_info[$key];
	}
	return $final_list;
}

function Algorithm_Random($staffList, $examinableProjectList, $interestAreaList, $WORKLOAD_PER_PROJECT_EXAMINED, $WORKLOAD_TOTALPROJECTS) {

	// Assignment Initialize
	$String01 = "RUNNING RANDOM-RANDOM ALOGRITHM\n";
	$constant = 4;
	$Total_Projects = $WORKLOAD_TOTALPROJECTS; // sum of current and last sem
	$Total_ExaminableProjects = sizeof($examinableProjectList);
	$Total_Examinable_Staffs = sizeof($staffList);
	$Total_BufferProjects = $_GET["Total_BufferProjects"];
//	$Total_Workload = ($Total_BufferProjects + $Total_Projects) * $constant;
//	$Total_Workload = ($Total_BufferProjects + $Total_ExaminableProjects) * $constant;
//	$Target_Workload01 = ($Total_Examinable_Staffs > 0) ? ceil($Total_Workload / $Total_Examinable_Staffs) : 1;
	$WorkingStaffList = $staffList;
	$WorkingProjectList = $examinableProjectList;
	$Total_Examinable_StaffsAssigned = $Total_ProjectAssigned = $Total_Examinable_Staffs_Overload = $Total_ProjPrefCount = $Total_AreaPrefCount = 0;
	$Count_StillAssignableStaff = 0;
	foreach ($staffList as $staff) $staff->initProjectAssignment();
	foreach ($examinableProjectList as $project) $project->initProjectAssignment();
	// Step01: Seperate Staff (project pref|area pref|no pref) then check staff workload
	$AL_StaffWithPref_Project = array();
	$AL_StaffWithPref_Area = array();
	$AL_StaffWithPref_NoSelection = array();
	$AL_StaffOverLoad = array();
	$examinersList = array();
	$projExaminersList = array();
	$newExaminersList = array();
	$areaExaminersList = array();
	$noPrefExaminersList = array();
	$unassignedList = array();
	$refloodList = array();
	$exemptS1 = "getExemption";
	$exemptS2 = "getExemption2";
	$getExemptions;
	if (date("n") < 12 && date("n") > 7)
		$getExemptions = $exemptS1;
	else
		$getExemptions = $exemptS2;

//	echo count($WorkingStaffList) . "\n";
	$indexcount = 0;
	foreach ($WorkingStaffList as $WorkingStaff) {
		$indexcount++;
//		if ($WorkingStaff->getWorkload() < $Target_Workload01) {
//			// Staffs that are underload
		// Staff with Proj preference
		if (count($WorkingStaff->assignment_project) > 0) {
			$AL_StaffWithPref_Project[$WorkingStaff->getID()] = $WorkingStaffList[$WorkingStaff->getID()];
		}
		// Staff with area preference
		if (count($WorkingStaff->assignment_area) > 0) {
			$AL_StaffWithPref_Area[$WorkingStaff->getID()] = $WorkingStaffList[$WorkingStaff->getID()];
		}
		// Staff with no preference
		if (count($WorkingStaff->assignment_project) <= 0 && count($WorkingStaff->assignment_area) <= 0) {
			// DEBUG
			// echo sprintf("%002d.processing : %s : %s \n", $indexcount, $WorkingStaff->getID(), "no preference");
			$AL_StaffWithPref_NoSelection[$WorkingStaff->getID()] = $WorkingStaffList[$WorkingStaff->getID()];
		} else {
			// DEBUG
			// echo sprintf("%002d.processing : %s \n", $indexcount, $WorkingStaff->getID());
		}
//		} else {
//			// Ignore those staffs that are overloaded
//			$AL_StaffOverLoad[$WorkingStaff->getID()] = $WorkingStaffList[$WorkingStaff->getID()];
//		}
	} // End of foreach

	// Stats Tracking
//	$Total_Examinable_Staffs_Overload = count($AL_StaffOverLoad);

	$count = sizeof($WorkingStaffList);
	while ($count > 0) {
		$RandomStaffObj = $WorkingStaffList[array_rand($WorkingStaffList)]; // Convert to staff obj by key
		$CurrentStaff_Workload = $RandomStaffObj->getWorkload();
		$CurrentStaff_ProjPrefCount = count($RandomStaffObj->assignment_project);
		$CurrentStaff_AreaPrefCount = count($RandomStaffObj->assignment_area);
		$Total_ProjPrefCount = $Total_ProjPrefCount + $CurrentStaff_ProjPrefCount;
		$Total_AreaPrefCount = $Total_AreaPrefCount + $CurrentStaff_AreaPrefCount;
//		unset($WorkingStaffList[$RandomStaffObj->getID()]); // remove staff after reading
		$count--;
	} // End of while
	$Total_Examinable_StaffsWithPref_Proj = count($AL_StaffWithPref_Project);
	$Total_Examinable_StaffsWithPref_Area = count($AL_StaffWithPref_Area);
	$Total_Examinable_StaffsWithPref_NoSelection = count($AL_StaffWithPref_NoSelection);

	$String01 .= sprintf("%-28s : %04d \n", "Total Examinable Staffs", $Total_Examinable_Staffs);
	$String01 .= sprintf("%-28s : %04d \n", "Total Projects", $Total_Projects);
	$String01 .= sprintf("%-28s : %04d \n", "Total Examinable Projects", $Total_ExaminableProjects);
	$String01 .= sprintf("%-28s : %04d \n", "Total Buffer Projects", $Total_BufferProjects);
//	$String01 .= sprintf("%-28s : %04d = (%04d + %04d) * 4\n", "Total Workload", $Total_Workload, $Total_Projects, $Total_BufferProjects);
//	$String01 .= sprintf("%-28s : %04d = %04d / %04d\n", "Target Workload", $Target_Workload01, $Total_Workload, $Total_Examinable_Staffs);
	$String01 .= sprintf("%-44s : (%04d|%04d) \n", "Number of Staff (Overload|Underload)", $Total_Examinable_Staffs_Overload, $Total_Examinable_Staffs - $Total_Examinable_Staffs_Overload);
	$String01 .= sprintf("%-44s : (%04d|%04d|%04d) --- from underload staff only\n", "Number of Staff (ProjPref|AreaPref|NoPref) ",
		$Total_Examinable_StaffsWithPref_Proj, $Total_Examinable_StaffsWithPref_Area, $Total_Examinable_StaffsWithPref_NoSelection);
	$String01 = $String01 . sprintf("%-44s :  %04d = %04d + %04d\n\n", "Total Preferences (ProjPref|AreaPref)",
			($Total_ProjPrefCount + $Total_AreaPrefCount), $Total_ProjPrefCount, $Total_AreaPrefCount);
	// print_r($staffList);
	// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------
	// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------
	// Step02: Allocate staff with project preference first (in random order)

	// Start of flooding algorithm
	$margin = 1;
	while (count($WorkingProjectList) != count($examinersList)) {
		foreach ($WorkingStaffList as $staff) {
			while ($staff->$getExemptions < $margin && count($WorkingProjectList) != count($examinersList)) {
				// All examiners
				array_push($examinersList, $staff);
				break;
			}
		}
		$margin++;
	}

	// Preferred projects
	foreach ($examinersList as $staff) {
		if (count($staff->assignment_project) > 0)
			array_push($projExaminersList, $staff);
	}

	while (count($projExaminersList) > 0) {
		foreach ($projExaminersList as $staff) {

			while (count($projExaminersList) != 0) {
				if (count($staff->assignment_project) == 0) {
					array_shift($projExaminersList);
					break;
				}
				$randomProjectPreferenceKey = key($staff->assignment_project);
				//print_r($randomProjectPreferenceKey);
				$randomProjectPreferenceValue = $staff->assignment_project[$randomProjectPreferenceKey];
				//echo $randomProjectPreferenceValue;
				if (array_key_exists($randomProjectPreferenceValue, $WorkingProjectList)) {
					if (!$WorkingProjectList[$randomProjectPreferenceValue]->isAssignedStaff()
						&& $WorkingProjectList[$randomProjectPreferenceValue]->getStaff() != $staff->getID()
//							&& $staff->getWorkload() < $Target_Workload01
					) {
						$WorkingProjectList[$randomProjectPreferenceValue]->assignStaff($staff->getID(), "Workload Assignment");
						$Workload_New = $staff->getWorkload() + $WORKLOAD_PER_PROJECT_EXAMINED;
						$staff->setWorkload($Workload_New);
						$Total_ProjectAssigned++;
						//print_r($staff->getID());
						unset($WorkingProjectList[$randomProjectPreferenceValue]);
						unset($staff->assignment_project[$randomProjectPreferenceKey]);
						array_push($unassignedList, $staff);
						array_shift($projExaminersList);
						break;
					}
				} else {
					unset($staff->assignment_project[$randomProjectPreferenceKey]);
					if (count($staff->assignment_project) == 0){
						array_shift($projExaminersList);
						break;
					}
				}
				continue;
			}
		}
	}


	// Preferred areas & no preferences
	for ($i=0; $i<count($examinersList); $i++){
		if (current($examinersList) !== reset($unassignedList)){
			array_push($newExaminersList, current($examinersList));
		}
		else{array_shift($unassignedList);}
		next($examinersList);
	}

	// Flooding algorithm again to put back staff that didn't get their preferred projects
	$margin = 1;
	while (count($newExaminersList) != 0) {
		foreach ($newExaminersList as $staff) {
			while ($staff->$getExemptions < $margin && count($WorkingProjectList) != count($refloodList)) {
				array_push($refloodList, $staff);
				array_shift($newExaminersList);
				break;
			}
			if ($staff->$getExemptions >= $margin){
				array_push($newExaminersList, $staff);
				array_shift($newExaminersList);
			}

		}
		$margin++;
	}

	foreach ($refloodList as $staff) {
		if (count($staff->assignment_area) > 0)
			array_push($areaExaminersList, $staff);
	}

	foreach ($refloodList as $staff) {
		if (count($staff->assignment_area) == 0)
			array_push($noPrefExaminersList, $staff);
	}

	// Preferred areas
	$noIntersect=0;
	foreach ($areaExaminersList as $staff) {
		$noIntersect=0;
		foreach ($WorkingProjectList as $workList){
			$randomProject = $workList;
			//$randomProject = $WorkingProjectList[array_rand($WorkingProjectList)]
			$PROJECT_AreaKeyCode = array_intersect($interestAreaList, $randomProject->getProjectArea());
			$AL_ConvertRandomStaffAreaPref_To_ssKeyList = array();
			foreach ($staff->assignment_area as $AreaPrefInKeyCode) {
				$AL_ConvertRandomStaffAreaPref_To_ssKeyList[$AreaPrefInKeyCode] = $interestAreaList[$AreaPrefInKeyCode];
			} // End of foreach
			$IntersectResult = array_intersect($AL_ConvertRandomStaffAreaPref_To_ssKeyList, $PROJECT_AreaKeyCode);
			//print_r($IntersectResult);
			if (count($IntersectResult) > 0) {
				if (!$randomProject->isAssignedStaff()
					&& $randomProject->getStaff() != $staff->getID()
				) {
					$randomProject->assignStaff($staff->getID(), "Workload Assignment");
					$Workload_New = $staff->getWorkload() + $WORKLOAD_PER_PROJECT_EXAMINED;
					$staff->setWorkload($Workload_New);
					$Total_ProjectAssigned++;
					//print_r($staff->getID());
					$noIntersect++;
					unset($WorkingProjectList[$randomProject->getID()]);
					array_shift($areaExaminersList);
					break;
				}
			}
		}
		if ($noIntersect == 0){
			array_unshift($noPrefExaminersList, $staff);
			array_shift($areaExaminersList);
		}
	}
	// End of area preference

	// No preferences or failed to get preferred projects/areas
	while (count($WorkingProjectList) != 0) {
		foreach ($noPrefExaminersList as $staff) {
			$ignore_project = 0;
			reset($WorkingProjectList);
			for ($i = 0; $i < $ignore_project; $i++) {
				next($WorkingProjectList);
			}
			if (!current($WorkingProjectList)) return; //no more projects

			$randomProj = $WorkingProjectList[array_rand($WorkingProjectList)];
			$randomProject = $randomProj->getID();
			if (!$WorkingProjectList[$randomProject]->isAssignedStaff()
				&& $WorkingProjectList[$randomProject]->getStaff() != $staff->getID()
//				&& $staff->getWorkload() < $Target_Workload01
			) {
				$WorkingProjectList[$randomProject]->assignStaff($staff->getID(), "Workload Assignment");
				$Workload_New = $staff->getWorkload() + $WORKLOAD_PER_PROJECT_EXAMINED;
				$staff->setWorkload($Workload_New);
				$Total_ProjectAssigned++;
				//print_r($staff->getID());
				unset($WorkingProjectList[$randomProject]);
				array_shift($noPrefExaminersList);
				break;
			} else if ($WorkingProjectList[$randomProject]->getStaff() != $staff->getID()) {
				break;
			} else {
				$ignore_project++;
			}
		}
	}
	// end of no preference

	return;

	// Old examiner algorithm

	// start of flooding algorithm
	// $margin = 1;
	// while (true) {
	// if (!count($WorkingProjectList) > 0) {
	// break;
	// }
	// foreach ($WorkingStaffList as $staff) {

	// $ignore_project = 0;
	// while ($staff->getWorkload() < $margin && count($WorkingProjectList) > 0) {
	// $noIntersect = 0;
	// if (!count($staff->assignment_project) > 0) $AL_StaffWithPref_NoSelection[$staff->getID()] = $staff;
	// // project assignment until margin
	// // project preference
	// print_r($staff->getID());
	// if (array_key_exists($staff->getID(), $AL_StaffWithPref_Project)) {
	// $randomProjectPreferenceKey = key($staff->assignment_project);
	// print_r($randomProjectPreferenceKey);
	// $randomProjectPreferenceValue = $staff->assignment_project[$randomProjectPreferenceKey];
	// if (array_key_exists($randomProjectPreferenceValue, $WorkingProjectList)) {
	// if (!$WorkingProjectList[$randomProjectPreferenceValue]->isAssignedStaff()
	// && $WorkingProjectList[$randomProjectPreferenceValue]->getStaff() != $staff->getID()
// //							&& $staff->getWorkload() < $Target_Workload01
	// ) {
	// $WorkingProjectList[$randomProjectPreferenceValue]->assignStaff($staff->getID(), "Workload Assignment");
	// $Workload_New = $staff->getWorkload() + $WORKLOAD_PER_PROJECT_EXAMINED;
	// $staff->setWorkload($Workload_New);
	// $Total_ProjectAssigned++;
	// unset($WorkingProjectList[$randomProjectPreferenceValue]);
	// unset($staff->assignment_project[$randomProjectPreferenceKey]);

	// if (count($staff->assignment_project) == 0)
	// unset($AL_StaffWithPref_Project[$staff->getID()]);
	// continue;
	// }
	// } else {
	// unset($staff->assignment_project[$randomProjectPreferenceKey]);

	// if (count($staff->assignment_project) == 0)
	// unset($AL_StaffWithPref_Project[$staff->getID()]);
	// }
	// }
	// // end of project preference
	// if (!count($staff->assignment_project) > 0) {
	// if (count($staff->assignment_area) > 0) {
	// // area preference
	// if (array_key_exists($staff->getID(), $AL_StaffWithPref_Area)) {
	// foreach ($WorkingProjectList as $workList){
	// $randomProject = $workList;
	// //$randomProject = $WorkingProjectList[array_rand($WorkingProjectList)];
	// $PROJECT_AreaKeyCode = array_intersect($interestAreaList, $randomProject->getProjectArea());
	// $AL_ConvertRandomStaffAreaPref_To_ssKeyList = array();
	// foreach ($staff->assignment_area as $AreaPrefInKeyCode) {
	// $AL_ConvertRandomStaffAreaPref_To_ssKeyList[$AreaPrefInKeyCode] = $interestAreaList[$AreaPrefInKeyCode];
	// } // End of foreach
	// $IntersectResult = array_intersect($AL_ConvertRandomStaffAreaPref_To_ssKeyList, $PROJECT_AreaKeyCode);
	// $noIntersect++;
	// //print_r($IntersectResult);
	// if (count($IntersectResult) > 0) {
	// if (!$randomProject->isAssignedStaff()
	// && $randomProject->getStaff() != $staff->getID()
	// //									&& $staff->getWorkload() < $Target_Workload01
	// ) {
	// $randomProject->assignStaff($staff->getID(), "Workload Assignment");
	// $Workload_New = $staff->getWorkload() + $WORKLOAD_PER_PROJECT_EXAMINED;
	// $staff->setWorkload($Workload_New);
	// $Total_ProjectAssigned++;
	// unset($WorkingProjectList[$randomProject->getID()]);
	// break;
	// }
	// }
	// }
	// }
	// // end of area preference
	// }
	// // no preference
	// if ($noIntersect == 0 || $noIntersect == count($WorkingProjectList)) {
	// if (array_key_exists($staff->getID(), $AL_StaffWithPref_NoSelection)) {
	// //						$randomProject = reset($WorkingProjectList)->getID();
	// reset($WorkingProjectList);
	// for ($i = 0; $i < $ignore_project; $i++) {
	// next($WorkingProjectList);
	// }
	// if (!current($WorkingProjectList)) return; //no more projects
	// $randomProject = current($WorkingProjectList)->getID();

	// if (!$WorkingProjectList[$randomProject]->isAssignedStaff()
	// && $WorkingProjectList[$randomProject]->getStaff() != $staff->getID()
	// //							&& $staff->getWorkload() < $Target_Workload01
	// ) {
	// $WorkingProjectList[$randomProject]->assignStaff($staff->getID(), "Workload Assignment");
	// $Workload_New = $staff->getWorkload() + $WORKLOAD_PER_PROJECT_EXAMINED;
	// $staff->setWorkload($Workload_New);
	// $Total_ProjectAssigned++;
	// unset($WorkingProjectList[$randomProject]);
	// continue;
	// } else if ($WorkingProjectList[$randomProject]->getStaff() != $staff->getID()) {
	// continue;
	// } else {
	// $ignore_project++;
	// }
	// }
	// // end of no preference
	// }
	// }
	// }
	// }
	// $margin++;
	// }

	// return;


	//ignore following code
	// end of flooding algorithm

//    $String01 = $String01 . sprintf("%s\n", "********************** Start of Project Pref Allocation ********************** ");
//	while (count($AL_StaffWithPref_Project) > 0) {
//		$RandomStaffObj = $AL_StaffWithPref_Project[array_rand($AL_StaffWithPref_Project)]; // convert to staff obj
//		$IsRandStaffAssigned = false;
//		$InitialWorkload = $RandomStaffObj->getWorkload();
//
//		// Get all the project pref of this staff
//		foreach ($RandomStaffObj->assignment_project as $SelectedProject) {
//			// Check if selected project exists in the project list
//			if (array_key_exists($SelectedProject, $WorkingProjectList)) {
//				// 1. Check if the selected project pref is assigned ?
//				// 2. Check if the selected project pref superviser is himself/herself ?
//				// 3. Check if the random staff is still assignable
//				if (!$WorkingProjectList[$SelectedProject]->isAssignedStaff() && $WorkingProjectList[$SelectedProject]->getStaff() != $RandomStaffObj->getID() && $RandomStaffObj->getWorkload() < $Target_Workload01) {
//					$WorkingProjectList[$SelectedProject]->assignStaff($RandomStaffObj->getID(), "Workload Assignment");
//					$Workload_New = $RandomStaffObj->getWorkload() + $WORKLOAD_PER_PROJECT_EXAMINED;
//					$RandomStaffObj->setWorkload($Workload_New);    // Set new workload to the current staff
//					unset($WorkingProjectList[$SelectedProject]);                       // Remove selected project from the working project list
//					$IsRandStaffAssigned = true;
//					$Total_ProjectAssigned++;
//				} else {
//					// Selected project pref has already assigned to other staff
//					// Selected project pref is himself/herself
//					// Random staff is overload
//				}
//			} else {
//				// Project does not exists for current and last sems
//				// Do nothing... Go to next project pref
//			}
//		} // End of foreach
//
//		// Check if random staff is assigned to any project pref
//		if (!$IsRandStaffAssigned) { // Not assigned = add random staff to area pref array list
//			// 1. Check if random staff exists in the area pref array list
//			if (!array_key_exists($RandomStaffObj->getID(), $AL_StaffWithPref_Area)) {
//				// 1. Check if random staff have any area pref
//				if (count($RandomStaffObj->assignment_area) > 0) {
//					// add random staff to area pref array list
//					$AL_StaffWithPref_Area[$RandomStaffObj->getID()] = $RandomStaffObj;
//					// Stats Tracking
//					$String01 = $String01 . sprintf("%s : %-15s ; Workload (Initial|After) : (%02d|%02d) ;", "Random Staff",
//							$RandomStaffObj->getID(), $InitialWorkload, $RandomStaffObj->getWorkload());
//					$String01 = $String01 . sprintf(" Added %-15s to Area Pref Array List.\n", $RandomStaffObj->getID());
//				} else {
//					// random staff does not have area pref, so treat random staff have no pref
//					$AL_StaffWithPref_NoSelection[$RandomStaffObj->getID()] = $RandomStaffObj;
//
//					// Stats Tracking
//					$String01 = $String01 . sprintf("%s : %-15s ; Workload (Initial|After) : (%02d|%02d) ;", "Random Staff",
//							$RandomStaffObj->getID(), $InitialWorkload, $RandomStaffObj->getWorkload());
//					$String01 = $String01 . sprintf(" Added %-15s to No Pref Array List.\n", $RandomStaffObj->getID());
//				}
//			} else {
//				// Random staff already exists in area pref array list
//				// do nothing...
//			}
//		} else {
//			$Total_Examinable_StaffsAssigned++;
//			if ($RandomStaffObj->getWorkload() < $Target_Workload01) { // staff is still assignable
//				// Add to no pref list
//				$AL_StaffWithPref_NoSelection[$RandomStaffObj->getID()] = $RandomStaffObj;
//				$String01 = $String01 . sprintf("%s : %-15s ; Workload (Initial|After) : (%02d|%02d) ;", "Random Staff",
//						$RandomStaffObj->getID(), $InitialWorkload, $RandomStaffObj->getWorkload());
//				$String01 = $String01 . sprintf(" Added %-15s to No Pref Array List.\n", $RandomStaffObj->getID());
//				$Count_StillAssignableStaff++;
//			} else {
//				// Stats Tracking
//				$String01 = $String01 . sprintf("%s : %-15s ; Workload (Initial|After) : (%02d|%02d) ; Workload Full\n", "Random Staff",
//						$RandomStaffObj->getID(), $InitialWorkload, $RandomStaffObj->getWorkload());
//			}
//
//		}
//		unset($AL_StaffWithPref_Project[$RandomStaffObj->getID()]); // Remove staff regardless if she is assign to any project
//	} // End of while
//	// End of project pref allocation
//	$String01 = $String01 . sprintf("%s\n", "********************** End of Project Pref Allocation ********************** ");
//	$String01 = $String01 . sprintf("%-28s : %-5d\n", "Staff Allocated", $Total_Examinable_StaffsWithPref_Proj - count($AL_StaffWithPref_Project));
//	$String01 = $String01 . sprintf("%-44s : (%04d|%04d) \n", "Number of Staff (Overload|Underload)", $Total_Examinable_Staffs_Overload, $Total_Examinable_Staffs - $Total_Examinable_Staffs_Overload);
//	$String01 = $String01 . sprintf("%-44s : (%04d|%04d|%04d) --- from underload staff only\n\n", "Number of Staff (ProjPref|AreaPref|NoPref) ",
//			count($AL_StaffWithPref_Project), count($AL_StaffWithPref_Area), count($AL_StaffWithPref_NoSelection));
//
//	// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//	// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//	// Step03: Allocate staff with area preference (in random order)
//	$String01 = $String01 . sprintf("%s\n", "********************** Start of Area Pref Allocation ********************** ");
//
//	if (count($AL_StaffWithPref_Area) > 0) {
//		$Max_ExaminableProjectsToAllocate_PerFaculty = floor(($Total_ExaminableProjects - $Total_ProjectAssigned) / count($AL_StaffWithPref_Area));
//		while (count($AL_StaffWithPref_Area) > 0) {
//
//			$RandomStaffObj = $AL_StaffWithPref_Area[array_rand($AL_StaffWithPref_Area)]; // Convert to staff obj by key
//			$IsRandStaffAssigned = false;
//			$InitialWorkload = $RandomStaffObj->getWorkload();
//
//			$Current_ExaminableProjectAllocated_Count = 0;
//			$String02 = "";
//			$String03 = "";
//			$String02 .= $RandomStaffObj->getID() . " => ";
//			foreach ($RandomStaffObj->assignment_area as $Key) {
//				$String02 .= $interestAreaList[$Key] . ", ";
//			}
//			$String02 .= "\n";
//
//			// Get the area of the project from the project list
//			foreach ($WorkingProjectList as $ThisProject) {
//				// Due to three different types of array structure used to determine area pref for faculty, project and keylist (the category of project preference),
//				// First do an intersect between the project from project list and the KeyList
//				$PROJECT_AreaKeyCode = array_intersect($interestAreaList, $ThisProject->getProjectArea());
//				// Convert random staff area pref into the structure of interestAreaList
//				$AL_ConvertRandomStaffAreaPref_To_ssKeyList = array();
//				foreach ($RandomStaffObj->assignment_area as $AreaPrefInKeyCode) {
//					$AL_ConvertRandomStaffAreaPref_To_ssKeyList[$AreaPrefInKeyCode] = $interestAreaList[$AreaPrefInKeyCode];
//				} // End of foreach
//				// Then do an intersection between the area pref of random staff with the project
//				$IntersectResult = array_intersect($AL_ConvertRandomStaffAreaPref_To_ssKeyList, $PROJECT_AreaKeyCode);
//				// 1. Found a matching project with area pref of random staff
//				// 2. Allocation count lesser than max (to balance out allocation across all staff )
//				if (count($IntersectResult) > 0 && $Current_ExaminableProjectAllocated_Count < $Max_ExaminableProjectsToAllocate_PerFaculty) {
//					$String03 .= $ThisProject->getID() . " => " . implode(',', $ThisProject->getProjectArea()) . " \n";
//					// 1. Check if the area pref of the selected project is assigned ?
//					// 2. Check if the area pref of the selected project superviser is himself/herself ?
//					// 3. Check if the random staff is still assignable
//					if (!$ThisProject->isAssignedStaff() && $ThisProject->getStaff() != $RandomStaffObj->getID() && $RandomStaffObj->getWorkload() < $Target_Workload01) {
//						$ThisProject->assignStaff($RandomStaffObj->getID(), "Workload Assignment");
//						$Workload_New = $RandomStaffObj->getWorkload() + $WORKLOAD_PER_PROJECT_EXAMINED;
//						$RandomStaffObj->setWorkload($Workload_New);        // Set new workload to the current staff
//						unset($WorkingProjectList[$ThisProject->getID()]);  // Remove project of the selected area from the working project list
//						$IsRandStaffAssigned = true;                        // Keep track if the random staff has been assigned to any progject
//						$Total_ProjectAssigned++;
//						$Current_ExaminableProjectAllocated_Count++;
//					} else {
//						// Project has already assigned to other staff
//						// Project is himself/herself
//						// Random staff is overload
//					}
//				} else {
//				}
//			} // End of foreach
//
//			// Check if random staff is assigned to any area pref
//			if (!$IsRandStaffAssigned) { // Not assigned = add random staff to no pref array list
//				// 1. Check if random staff exists in the no pref array list
//				if (!array_key_exists($RandomStaffObj->getID(), $AL_StaffWithPref_NoSelection)) {
//					$AL_StaffWithPref_NoSelection[$RandomStaffObj->getID()] = $RandomStaffObj;
//					// Stats Tracking
//					$String01 = $String01 . sprintf("\n%s : %-15s ; Workload (Initial|After) : (%02d|%02d) ", "Random Staff",
//							$RandomStaffObj->getID(), $InitialWorkload, $RandomStaffObj->getWorkload());
//					$String01 = $String01 . sprintf("; Added %-15s to No Pref Array List.\n", $RandomStaffObj->getID());
//				} else {
//					// Random staff already exists in no pref array list
//				}
//			} else {
//				$Total_Examinable_StaffsAssigned++;
//				if ($RandomStaffObj->getWorkload() < $Target_Workload01) { // random staff is still assignable = move random staff to no pref selection
//					// Add to no pref list
//					$AL_StaffWithPref_NoSelection[$RandomStaffObj->getID()] = $RandomStaffObj;
//					$String01 = $String01 . sprintf("\n%s : %-15s ; Workload (Initial|After) : (%02d|%02d) ;", "Random Staff",
//							$RandomStaffObj->getID(), $InitialWorkload, $RandomStaffObj->getWorkload());
//					$String01 = $String01 . sprintf(" Added %-15s to No Pref Array List.\n", $RandomStaffObj->getID());
//					$Count_StillAssignableStaff++;
//				} else {
//					// Stats Tracking
//					$String01 = $String01 . sprintf("\n%s : %-15s ; Workload (Initial|After) : (%02d|%02d) ; Workload Full\n", "Random Staff",
//							$RandomStaffObj->getID(), $InitialWorkload, $RandomStaffObj->getWorkload());
//				}
//			}
//			$String01 .= $String02;
//			$String01 .= $String03;
//			unset($AL_StaffWithPref_Area[$RandomStaffObj->getID()]); // Remove staff regardless if she is assign to any project
//		} // End of while
//	} else {
//		// Skip area pref allocation
//	}
//	// End of area pref allocation
//	$String01 = $String01 . sprintf("%s\n", "********************** End of Area Pref Allocation ********************** ");
//	$String01 = $String01 . sprintf("%-28s : %-5d\n", "Staff Allocated", $Total_Examinable_StaffsWithPref_Area - count($AL_StaffWithPref_Area));
//	$String01 = $String01 . sprintf("%-44s : (%04d|%04d) \n", "Number of Staff (Overload|Underload)", $Total_Examinable_Staffs_Overload, $Total_Examinable_Staffs - $Total_Examinable_Staffs_Overload);
//	$String01 = $String01 . sprintf("%-44s : (%04d|%04d|%04d) --- from underload staff only\n\n", "Number of Staff (ProjPref|AreaPref|NoPref) ",
//			count($AL_StaffWithPref_Project), count($AL_StaffWithPref_Area), count($AL_StaffWithPref_NoSelection));
//
//	// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//	// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//	// Step04: Allocate staff with no preference (in random order)
//	$String01 = $String01 . sprintf("%s\n", "********************** Start of No Pref Allocation ********************** ");
//
//	try {
//		$Count_Round = 0;
//		$AL_Staff_Allocated = array();
//		while (count($WorkingProjectList) > 0) {
//			$Count_Round++;
//			$String01 = $String01 . sprintf("%s\n", "-------------------- Round $Count_Round, ProjectList :" . count($WorkingProjectList) . "--------------------");
//			$RowCount = 0;
//			foreach ($AL_StaffWithPref_NoSelection as $key => $ThisStaff) {
//				$RowCount++;
//				$InitialWorkload = $ThisStaff->getWorkload();
//				if (count($WorkingProjectList) > 0) {
//					$RandomProjectObj = $WorkingProjectList[array_rand($WorkingProjectList)];
//				} else {
//					break; // no more projects to assign
//				}
//
//				if ($ThisStaff->getWorkload() < $Target_Workload01 && $RandomProjectObj->getStaff() != $ThisStaff->getID()) {
//					$RandomProjectObj->assignStaff($ThisStaff->getID(), "Workload Assignment");
//					$Workload_New = $ThisStaff->getWorkload() + $WORKLOAD_PER_PROJECT_EXAMINED;
//					$ThisStaff->setWorkload($Workload_New);                 // Set new workload to the current staff
//					unset($WorkingProjectList[$RandomProjectObj->getID()]); // Remove project from the working project list
//					$Total_ProjectAssigned++;
//
//					if (!array_key_exists($ThisStaff->getID(), $AL_Staff_Allocated)) {
//						$AL_Staff_Allocated[$ThisStaff->getID()] = $ThisStaff;
//					}
//
//					// Stats Tracking
//					$String01 = $String01 . sprintf("%03d.%s : %-15s ; Workload (Initial|After) : (%02d|%02d) ; Random Project : %s (ASSIGNED)\n", $RowCount, "Random Staff",
//							$ThisStaff->getID(), $InitialWorkload, $ThisStaff->getWorkload(), $RandomProjectObj->getID());
//				} else {
//					// Stats Tracking
//					$String01 = $String01 . sprintf("%03d.%s : %-15s ; Workload (Initial|After) : (%02d|%02d) ; Random Project : %s (NOT ASSIGNED)\n", $RowCount, "Random Staff",
//							$ThisStaff->getID(), $InitialWorkload, $ThisStaff->getWorkload(), $RandomProjectObj->getID());
//				}
//			} // End of Foreach
//		} // End of While
//
//		// End of no pref allocation
//		$String01 = $String01 . sprintf("%s\n", "********************** End of No Pref Allocation ********************** ");
//		$String01 = $String01 . sprintf("%-28s : %-5d\n", "Staff Allocated", count($AL_Staff_Allocated));
//		$String01 = $String01 . sprintf("%-44s : (%04d|%04d) \n", "Number of Staff (Overload|Underload)", $Total_Examinable_Staffs_Overload, $Total_Examinable_Staffs - $Total_Examinable_Staffs_Overload);
//		$String01 = $String01 . sprintf("%-44s : (%04d|%04d|%04d) --- from underload staff only\n\n", "Number of Staff (ProjPref|AreaPref|NoPref) ",
//				count($AL_StaffWithPref_Project), count($AL_StaffWithPref_Area), count($AL_StaffWithPref_NoSelection) - count($AL_Staff_Allocated));
//
//		// Stats Tracking
//		$String01 = $String01 . sprintf("%s\n", "********************** Summary ********************** ");
//		$String01 = $String01 . sprintf("%-28s : %04d \n", "Total Examinable Staffs", $Total_Examinable_Staffs);
//		$String01 = $String01 . sprintf("%-28s : %04d \n", "Total Projects", $Total_Projects);
//		$String01 = $String01 . sprintf("%-28s : %04d \n", "Total Examinable Projects", $Total_ExaminableProjects);
//		$String01 = $String01 . sprintf("%-28s : %04d \n", "Total Buffer Projects", $Total_BufferProjects);
//		$String01 = $String01 . sprintf("%-28s : %04d = (%04d + %04d) * 4\n", "Total Workload", $Total_Workload, $Total_Projects, $Total_BufferProjects);
//		$String01 = $String01 . sprintf("%-28s : %04d = %04d / %04d\n", "Target Workload", $Target_Workload01, $Total_Workload, $Total_Examinable_Staffs);
//		$String01 = $String01 . sprintf("%-44s : (%04d|%04d) \n", "Number of Staff (Overload|Underload)", $Total_Examinable_Staffs_Overload, $Total_Examinable_Staffs - $Total_Examinable_Staffs_Overload);
//		$String01 = $String01 . sprintf("%-44s : (%04d|%04d|%04d) --- from underload staff only\n\n", "Number of Staff (ProjPref|AreaPref|NoPref) ",
//				count($AL_StaffWithPref_Project), count($AL_StaffWithPref_Area), count($AL_StaffWithPref_NoSelection) - count($AL_Staff_Allocated));
//		$String01 = $String01 . sprintf("%s\n", "********************** Result ********************** ");
//		$String01 = $String01 . sprintf("%-28s : %04d/%04d\n", "Total Staff Assigned", $Total_Examinable_StaffsAssigned + count($AL_Staff_Allocated) - $Count_StillAssignableStaff, $Total_Examinable_Staffs, $Count_StillAssignableStaff);
//		$String01 = $String01 . sprintf("%-28s : %04d\n", "Total Staff Not Assigned", count($AL_StaffWithPref_NoSelection) - count($AL_Staff_Allocated));
//		$String01 = $String01 . sprintf("%-28s : %04d/%04d \n", "Total Projects Assigned", $Total_ProjectAssigned, $Total_ExaminableProjects);
//		$String01 = $String01 . sprintf("%-28s : %04d\n", "Total Projects UnAssigned", $Total_ExaminableProjects - $Total_ProjectAssigned);
//		// Re-assign project list with assigned staff
//		$examinableProjectList = $WorkingProjectList;
//		// Convert stats into text file
//		$Contents = $String01;
//		$file = "submit_allocate_examiner_result.txt";
//		file_put_contents($file, $Contents, LOCK_EX);
//		// echo $Contents;
//	} catch (Exception $Ex) {
//		echo $Ex->getMessage();
//	}

}

?>
