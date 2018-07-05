<?php require_once('../../Connections/db_ntu.php');
require_once('../entity.php'); ?>

<?php
//this file is used for testing purposes
// do not use it for live 
ini_set('max_execution_time', 600);

$query_deleteStaffPref = "DELETE FROM ". $TABLES['staff_pref'];
$conn_db_ntu->query($query_deleteStaffPref);
foreach (glob("txt_files/*.txt") as $file) {
    $info = pathinfo($file);
	$file_name =  basename($file,'.'.$info['extension']);

	echo $file_name; 
	echo "<br>";
	
	$file_handle = fopen($file, "r");
    while (!feof($file_handle)) {
        $line = fgets($file_handle);
        //echo $line;
		//echo "<br>";
		
		$query_getStaff = "SELECT * FROM  " . $TABLES['staff']. " WHERE id = ?";
		$stmt1 = $conn_db_ntu->prepare ($query_getStaff);
		$stmt1->bindParam(1, $file_name);
		$stmt1->execute();
		$existStaff = $stmt1->fetch();
		//var_dump( $existStaff);
		///echo "<br>";
		if ($existStaff) {
			$delimiter = "|"; //change delimiter accordingly
			$explodeLine = explode($delimiter,$line);
			
			
				list($staffname, $projectId, $studentname, $prefer,$choice) = $explodeLine;
		    
				echo "pref = ".$prefer;
				echo "<br>";
				echo "choice = ".$choice;
				echo "<br>";
				if ($choice != "") {
					
					echo "inserted";
					$query_InsertPref = "INSERT INTO " . $TABLES['staff_pref']. " (staff_id, prefer, choice) values(?, ?, ?)";
					$stmt1 = $conn_db_ntu->prepare ($query_InsertPref);
					$stmt1->bindParam(1, $file_name);
					$stmt1->bindParam(2, $prefer);
					$stmt1->bindParam(3, $choice);
					$stmt1->execute();
					/*$query_SelectArea = "SELECT * FROM " . $TABLES['interest_area']. " WHERE title LIKE ?";
					$stmt1 = $conn_db_ntu->prepare ($query_SelectArea);
		
					$stmt1->bindParam(1, $prefer);
		
					$stmt1->execute();
					$existArea = $stmt1->fetch();
		
					if ($existArea) {
						$areaKey = $areaResults["key"];
						var_dump($areaKey);
						$query_InsertArea = "INSERT INTO " . $TABLES['staff_pref']. " (staff_id, prefer, choice) values(?, ?, ?)";
						$stmt1 = $conn_db_ntu->prepare ($query_InsertArea );
						$stmt1->bindParam(1, $staff_id);
						$stmt1->bindParam(2, $areaKey);
						$stmt1->bindParam(3, $choice);
						//$stmt1->execute();
				}*/
				}
			
		 }
		}
    fclose($file_handle);
}
  ?>