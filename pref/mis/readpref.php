<?php require_once('../../Connections/db_ntu.php');
require_once('../entity.php'); ?>

<?php
//this file is used for testing purposes
// do not use it for live 
ini_set('max_execution_time', 600);

$query_deleteStaffPref = "DELETE FROM ". $TABLES['staff_pref'];
$conn_db_ntu->query($query_deleteStaffPref);
$query_getInterestArea = "select * from ".$TABLES['interest_area'];
$interestAreas = $conn_db_ntu->query($query_getInterestArea)->fetchAll();

foreach (glob("txt_files/*.txt") as $file) {
    $prefAreasToInsert = array();
    $areaChoice = 1;
    echo "----------------------------------------------"."<br>";
    $info = pathinfo($file);
	$file_name =  basename($file,'.'.$info['extension']);

	echo $file_name; 
	echo "<br>";

	$file_handle = fopen($file, "r");
    while (!feof($file_handle)) {
        $line = rtrim(fgets($file_handle),"\r\n");
        //echo $line;
		//echo "<br>";
		if ($line != "") {
            $query_getStaff = "SELECT * FROM  " . $TABLES['staff']. " WHERE id = ?";
            $stmt1 = $conn_db_ntu->prepare ($query_getStaff);
            $stmt1->bindParam(1, $file_name);
            $stmt1->execute();
            $existStaff = $stmt1->fetch();
            //var_dump( $existStaff);
            ///echo "<br>";
            if ($existStaff) {
                //  process area pref
                if (substr( $line, 0, 2 ) === "||") {
                    $line = substr($line, 2);
                    $areas = explode("|",$line)[0];
                    $areas = explode(", ", $areas);
                    foreach ($areas as $area) {
                       foreach ($interestAreas as $interestArea) {
                           // case in-sensitive compare, return 0 if same
                           if (strcasecmp($interestArea["title"], $area)==0){
                               if (!in_array($interestArea["key"], $prefAreasToInsert)) {
                                   array_push($prefAreasToInsert, $interestArea["key"]);
                                   $query_InsertPref = "INSERT INTO " . $TABLES['staff_pref']. " (staff_id, prefer, choice) values(?, ?, ?)";
                                   $stmt1 = $conn_db_ntu->prepare ($query_InsertPref);
                                   $stmt1->bindParam(1, $file_name);
                                   $stmt1->bindParam(2, $interestArea["key"]);
                                   $stmt1->bindParam(3, $areaChoice);
                                   $stmt1->execute();
                                   echo "area pref = ".$interestArea["key"]. " (" . $interestArea["title"] . ") | ";
                                   echo "choice = ".$areaChoice;
                                   echo "<br>";
                                   echo "inserted"."<br>";
                                   $areaChoice++;
                               }
                           }
                       }
                    }

                } else {
                    // process project pref
                    $delimiter = "|"; //change delimiter accordingly
                    $explodeLine = explode($delimiter,$line);

                    list($staffName, $projectId, $studentName, $prefer,$choice) = $explodeLine;

                    echo "project pref = ".$prefer. " | ";
                    echo "choice = ".$choice;
                    echo "<br>";
                    if ($choice != "") {
                        echo "inserted"."<br>";
                        $query_InsertPref = "INSERT INTO " . $TABLES['staff_pref']. " (staff_id, prefer, choice) values(?, ?, ?)";
                        $stmt1 = $conn_db_ntu->prepare ($query_InsertPref);
                        $stmt1->bindParam(1, $file_name);
                        $stmt1->bindParam(2, $prefer);
                        $stmt1->bindParam(3, $choice);
                        $stmt1->execute();
                    }
        }

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