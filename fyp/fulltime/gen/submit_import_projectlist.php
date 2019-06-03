<?php
require_once('../../../Connections/db_ntu.php');
//require_once ('../../../PHPExcel/IOFactory.php');
require_once('../../../CSRFProtection.php');
require_once ('../../../vendor/autoload.php');

session_start();
ini_set('max_execution_time', 600);

$redirect = false;
$error_code = -1;
$success = false;
$csrf = new CSRFProtection();

$_REQUEST['validate'] = $csrf->cfmRequest();

// initialise variables for file upload
$target_dir = "../../../uploaded_files/";
$target_file = "";
$inputFileType = "";
$inputFileName = "";
$inputFullPath = "";



// Check for file name
// $_FILES is a global PHP variable. This variable is an associate double dimension array and keeps all the information related to uploaded file.
// value assigned to the input's name attribute was "file"
// $_FILE["file"]["name"] - the actual name of the uploaded file
if(isset($_FILES["file"]["name"])){
	$target_file = $target_dir.basename($_FILES["file"]["name"]); //basename() will return "project.xlsx"
	//pathinfo() function returns an array that contains information about a path. Syntax - pathinfo(path,options)
	//The following options array elements are returned:
	//[PATHINFO_DIRNAME] - return only dirname
	//[PATHINFO_BASENAME] - return only basename
	//[PATHINFO_EXTENSION] - return only extension
	$inputFileType = pathinfo($target_file,PATHINFO_EXTENSION);
	$inputFileName = $_FILES["file"]["name"];
	// Check for excel file only
	if($inputFileType == "xlsx" || $inputFileType == "xls" || $inputFileType == "csv" ){
		//$_FILES['file']['tmp_name'] − the uploaded file in the temporary directory on the web server.
		//move the file from temporary directory to the designated folder
		if(move_uploaded_file($_FILES["file"]["tmp_name"], $target_file )){
			ReadExcelData($target_file);
			$error_code=0;

		}
		else{
			$error_code=3; // file is open
		}
	}
	else {
		$error_code=2; // invalid file
	}
}
else{
	$error_code = 1; // no file name
}

$redirect = true;
if (isset ($_REQUEST['validate'])) {
	echo "validate=1";
}
else if($redirect){
	$_SESSION['import_project_error'] = $error_code;
	//header("Location: fyp/fulltime/gen/project.php?$error_code=". $error_code);
	echo ($error_code != 0) ? "error_code=$error_code" : "import_project=1";
}
exit;


// Read excel file
function ReadExcelData($inputFullPath){
	try {
		//$objPHPExcel = PHPExcel_IOFactory::load($inputFullPath);
		// load $inputFullPath to a Spreadsheet Object
		$objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFullPath);
	}
	catch(Exception $e){
		die('Error loading file"'.pathinfo($inputFullPath,PATHINFO_BASENAME).'":'.$e->getMessage());
		$error_code=4; // Cannot load excel file
	}
	HandleExcelData($objPHPExcel);

}

// Do whatever you want
function HandleExcelData($objPHPExcel){
	$Contents = ""; // Stats tracking
	// CUSTOM CODE GOES HERE
	$AllDataInSheet = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);


	global $TABLES, $conn_db_ntu;


	$highestRow 		= $objPHPExcel->getActiveSheet()->getHighestRow();
	$highestColumn 		= $objPHPExcel->getActiveSheet()->getHighestColumn();
	$col_proj_id 		= "";
	//checking header variables
	$checkprojheader = trim($AllDataInSheet[1]["B"]);
	$checksupervisor = trim($AllDataInSheet[1]["G"]);
	$checkarea5 = trim($AllDataInSheet[1]["Q"]);
	//check for the correct format, if it's not correct we will not upload into the database
	if($checkprojheader=="Proj_id" && $checksupervisor=="Supervisor" && $checkarea5=="Area5"){

		for($row = 2; $row <= $highestRow ; $row++ )
		{
				$proj_id 		= trim($AllDataInSheet[$row]["B"]);

				$startYear_tmp 	= trim($AllDataInSheet[$row]["C"]);
				$startYear_1 	= (int)(substr($startYear_tmp, -2));
				$startYear_2 	= $startYear_1 + 1;
				$startYear 		= $startYear_1 . $startYear_2;

				$startSem 		= trim($AllDataInSheet[$row]["D"]);

				$examineYear_tmp 	= trim($AllDataInSheet[$row]["E"]);
				$examineYear_1 		= (int)(substr($examineYear_tmp, -2));
				$examineYear_2 		= $examineYear_1 + 1;
				$examineYear 		= $examineYear_1 . $examineYear_2;

				$examineSem 	= trim($AllDataInSheet[$row]["F"]);

				$supervisor 	= trim($AllDataInSheet[$row]["G"]);

				$staffID_tmp 	= trim($AllDataInSheet[$row]["H"]);
				$staffID 		= substr($staffID_tmp, 0, strpos($staffID_tmp, "@"));
				$staffID 		= strtolower($staffID);

				$student_id 	= trim($AllDataInSheet[$row]["I"]);
				$student_status = trim($AllDataInSheet[$row]["K"]);

				$title 			= trim($AllDataInSheet[$row]["L"]);
				$title 			= str_replace("'", " ", $title); // replace "'" in $title with empty space

				$area1 			= trim($AllDataInSheet[$row]["M"]);
				$area2 			= trim($AllDataInSheet[$row]["N"]);
				$area3 			= trim($AllDataInSheet[$row]["O"]);
				$area4 			= trim($AllDataInSheet[$row]["P"]);
				$area5 			= trim($AllDataInSheet[$row]["Q"]);

				try {
					$query_duplicateProject = "SELECT * FROM .". $TABLES['fea_projects']." WHERE `project_id` = ?";
					$stmt = $conn_db_ntu->prepare($query_duplicateProject);
					$stmt->bindParam(1, $proj_id);
					$stmt->execute();
					$fea_projects = $stmt->fetchAll();

					$query_duplicateFypAssign = "SELECT * FROM .".$TABLES['fyp_assign'] ." WHERE `project_id` = ?";
					$stmt = $conn_db_ntu->prepare($query_duplicateFypAssign);
					$stmt->bindParam(1, $proj_id);
					$stmt->execute();
					$fyp_assigns = $stmt->fetchAll();

					$query_fyp = "SELECT * FROM .".$TABLES['fyp'] ." WHERE `project_id` = ?";
					$stmt = $conn_db_ntu->prepare($query_fyp);
					$stmt->bindParam(1, $proj_id);
					$stmt->execute();
					$fyps = $stmt->fetchAll();
				}
				catch(PDOException $e)
				{
					die($e->getMessage());
				}

				if($proj_id == "") {
					continue; //it will jump back to the for loop instead of going to the else statement
				}
				else {
					if($student_status == "Leave of Absence"  || $student_status == "Graduated" || $student_status == "Withdrawn") {

						$query_delete_proj = sprintf("DELETE FROM %s WHERE `project_id` = '%s' ",$TABLES['fyp_assign'],$proj_id);
						$conn_db_ntu->exec($query_delete_proj);
					}
					else{
						if ($fea_projects != null || sizeof($fea_projects) >0 ) {
								//update values of fea_projects table if record exist
							$query_update_fea_projects =sprintf("UPDATE %s SET `examine_year` = '%s', `examine_sem` = '%s' WHERE `project_id` like '%s' " ,$TABLES['fea_projects'], $examineYear, $examineSem, $proj_id);
							$conn_db_ntu->exec($query_update_fea_projects);
						}
						else {
								//inserting new projects into fea projects table
							$query_insert_fea_projects = sprintf("INSERT IGNORE INTO %s (`project_id`, `examine_year`, `examine_sem`) VALUES ('%s','%s','%s') ", $TABLES['fea_projects'],$proj_id,$examineYear,$examineSem);
							$conn_db_ntu->exec($query_insert_fea_projects);
						}
						if ($fyp_assigns != null || sizeof($fyp_assigns) >0) {
								//update values of fyp_assign table if record exist
							$query_update_fyp_assign =sprintf("UPDATE %s SET `staff_id` = '%s', `student_id` = '%s', `year`='%s', `sem`='%s' WHERE `project_id` = '%s' " ,$TABLES['fyp_assign'], $staffID, $student_id,$startYear,$startSem, $proj_id);
							$conn_db_ntu->exec($query_update_fyp_assign);
						}
						else {
								// inserting new projects into fyp_assign table
							$query_insert_fyp_assign= sprintf("INSERT IGNORE INTO %s (`staff_id`,`project_id`,`student_id`,`year`,`sem`) VALUES('%s','%s','%s','%s','%s') ",$TABLES['fyp_assign'], $staffID,$proj_id,$student_id,$startYear,$startSem);
							$conn_db_ntu->exec($query_insert_fyp_assign);
						}
						if ($fyps != null|| sizeof($fyps) >0) {
								//update values of fyp table if record exist
							$query_update_fyp =sprintf("UPDATE %s SET `title` = '%s',`supervisor` = '%s',`Area1` = '%s', `Area2` = '%s',`Area3` = '%s',`Area4` = '%s',`Area5` = '%s'WHERE `project_id` like '%s' ",$TABLES['fyp'], $title,$supervisor, $area1,$area2,$area3,$area4,$area5,$proj_id);
							$conn_db_ntu->exec($query_update_fyp);
						}
						else {
								//insert new records into fyp table
							$query_insert_fyp = sprintf("INSERT IGNORE INTO %s (`project_id`, `acad_year`, `sem`, `title`,`Supervisor`,`staff_id`, `Area1`,`Area2`,`Area3`,`Area4`,`Area5`) VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s') ",$TABLES['fyp'], $proj_id, $startYear_tmp,$startSem, $title, $supervisor,$staffID,$area1,$area2,$area3,$area4,$area5);
							$conn_db_ntu->exec($query_insert_fyp);
						}


					}

				}
		}

	}
	//echo $Contents;
}


?>
