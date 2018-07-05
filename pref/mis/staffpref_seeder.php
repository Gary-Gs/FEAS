<?php require_once('../../Connections/db_ntu.php');
require_once('../entity.php'); ?>

<?php
//this file is used for testing purposes
// do not use it for live 
ini_set('max_execution_time', 600);
$query_deleteStaffPref = "DELETE FROM ". $TABLES['staff_pref'];
$conn_db_ntu->query($query_deleteStaffPref);
$query_rsStaff	 	= "SELECT s.id as staffid, s.name as staffname, s.position as salutation FROM ".$TABLES['staff']." as s WHERE s.examine=1";
$rsStaff	= $conn_db_ntu->query($query_rsStaff);

$query_rsSettings = "SELECT * FROM ".$TABLES['allocation_settings_others'];
$settings 	= $conn_db_ntu->query($query_rsSettings)->fetch();

$examSemValue 		= $settings['exam_sem'];
$examYearValue 		= $settings['exam_year'];
foreach($rsStaff as $staff) { //Index Staff by staffid
$staffList[ $staff['staffid'] ] = new Staff($staff['staffid'],
			$staff['salutation'],
		$staff['staffname']);
}
$projectList = array();
$query_rsProject = "SELECT p3.project_id as pno, p2.staff_id as staffid, p1.title as ptitle FROM ".$TABLES['fea_projects']." as p3 LEFT JOIN ".$TABLES['fyp_assign']." as p2 ON p3.project_id = p2.project_id LEFT JOIN ".$TABLES['fyp']." as p1 ON p2.project_id = p1.project_id WHERE p2.complete = 0 and p3.examine_year = ".$examYearValue." and p3.examine_sem = ".$examSemValue." ORDER BY p3.project_id ASC ";
$rsProject 	= $conn_db_ntu->query($query_rsProject);

foreach($rsProject as $project) { //Index Project By pno
	$projectList[ $project['pno'] ] = new Project(	$project['pno'], 
																$project['staffid'],
						"",	//To be replaced if there's examiner already
																$project['ptitle'] );
}
$query_rsArea = "SELECT `key` from ". $TABLES ['interest_area']. " WHERE title <> '-'";
$areaList = $conn_db_ntu->query($query_rsArea)->fetchAll();



$projectList = custom_shuffle($projectList);
$areaList = custom_shuffle($areaList);

$projectCount = sizeof($projectList);
$areaCount = sizeof($areaList);
$noList = getRandomNumbers(1, 5, 5);

 $staffKeys= array_keys($staffList);
//var_dump($staffKeys);
//$projKeys= array_keys($projectList);
foreach ($projectList as $proj) { 
	for ($i=0;$i<10;$i++){		
			$noChoice = rand(1,10);
			$nopstaff= rand(1,78);
			$staffID = $staffKeys[$nopstaff];
			$projID = $proj->getID();
			$query_duplicateProject = "SELECT * FROM .". $TABLES['staff_pref']." WHERE `prefer` = ?"; 
			$stmt = $conn_db_ntu->prepare($query_duplicateProject);
			$stmt->bindParam(1, $projID);
			$stmt->execute();
			$fea_staff_pref = $stmt->fetchAll();
					
			$query_duplicateStaff = "SELECT * FROM .". $TABLES['staff_pref']." WHERE `prefer` = ? and `staff_id` = ?"; 
			$stmt = $conn_db_ntu->prepare($query_duplicateStaff);
			$stmt->bindParam(1, $projID);
			$stmt->bindParam(2,$staffID);
			$stmt->execute();
			$fea_staff_duplicate = $stmt->fetchAll();
			if (sizeof($fea_staff_duplicate)==0 && sizeof($fea_staff_pref)==0){
						$query_insert_staff_pref= sprintf("INSERT  INTO %s (`staff_id`,`prefer`, `choice`) VALUES('%s','%s',%d) ",$TABLES['staff_pref'], $staffID,$projID, $noChoice ); 
						$conn_db_ntu->exec($query_insert_staff_pref); 
					}
					else {
						echo "inserted proj preference";
						echo "<br>";
					}

	}
}
foreach ($areaList as $area) { 
	
	for ($i=0;$i<10;$i++){		
					$choiceNo = rand(1,10);
					$nopstaff= rand(1,78);
					$staffID = $staffKeys[$nopstaff];
					$areaID = $area["key"];
					
					
						$query_duplicateStaff = "SELECT * FROM .". $TABLES['staff_pref']." WHERE `prefer` = ? and `staff_id` = ?"; 
						$stmt = $conn_db_ntu->prepare($query_duplicateStaff);
						$stmt->bindParam(1, $areaID);
						$stmt->bindParam(2,$staffID);
						$stmt->execute();
						$fea_staff_duplicate = $stmt->fetchAll();
						if (sizeof($fea_staff_duplicate)==0){
							$query_insert_staff_pref = "INSERT  INTO ". $TABLES['staff_pref']." (`staff_id`,`prefer`, `choice`) VALUES(?,?,?) "; 
							$stmt = $conn_db_ntu->prepare($query_insert_staff_pref);
							$stmt->bindParam(1, $staffID);
							$stmt->bindParam(2,$areaID);
							$stmt->bindParam(3,$choiceNo);
						
							$stmt->execute();
							echo "inserted area preference";
							echo "<br>";
						}
						

		}
}	
function custom_shuffle($my_array = array()) {
  $copy = array();
  while (count($my_array)) {
    // takes a rand array elements by its key
    $element = array_rand($my_array);
    // assign the array and its value to an another array
    $copy[$element] = $my_array[$element];
    //delete the element from source array
    unset($my_array[$element]);
  }
  return $copy;
}
function getRandomNumbers($min, $max, $count){
    if ($count > (($max - $min)+1))
    {
        return false;
    }
    $values = range($min, $max);
    shuffle($values);
    return array_slice($values,0, $count);
}
	
  ?>