<?php 
require_once('../../../Connections/db_ntu.php'); 
require_once('../../../CSRFProtection.php');
//require_once ('../../../PHPExcel/IOFactory.php'); 
require_once ('../../../vendor/autoload.php');

ini_set('max_execution_time', 600);

$redirect = false;
$error_code = 0; // no error

$csrf = new CSRFProtection();

$_REQUEST['validate'] = $csrf->cfmRequest();


// initialise variables for file upload
$target_dir 		= "../../../uploaded_files/";
$target_file 		= ""; 
$inputFileType 		= "";
$inputFileName 		= "";


if(isset($_FILES["FileToUpload_ExaminerSettings"])){

 	// Count total files
	$Countfiles 		= count($_FILES["FileToUpload_ExaminerSettings"]["name"]);
	$CountFilesMoved	= 0;
 	// Looping all files and move to target dir
	for($i=0;$i<$Countfiles;$i++){
		$target_file 	= $target_dir.basename($_FILES["FileToUpload_ExaminerSettings"]["name"][$i]); 
		$inputFileType 	= pathinfo($target_file,PATHINFO_EXTENSION);
		$inputFileName 	= $_FILES["FileToUpload_ExaminerSettings"]["name"][$i];
		// echo $target_file;
		if($inputFileType == "xlsx" || $inputFileType == "xls" || $inputFileType == "csv" ){
			if(move_uploaded_file($_FILES["FileToUpload_ExaminerSettings"]["tmp_name"][$i], $target_file)){
				// do nothing
			}else{
				$error_code=3;	// File is open
			}
		}else{
			$error_code=2; // Invalid file type
		}
	}
} else{
	$error_code=1; // Cannot find uploaded file
}

// Assign examinable faculty into DB first
// Assign faculty workload into DB first
$FilesInDir = glob("$target_dir". "examinable_staff_list.*");
if (count($FilesInDir) == 1){
	$error_code = HandleExcelData_ExaminableFacultyList($error_code, $FilesInDir[0]);
	if($error_code == 0 ){ // no error

		$FilesInDir = glob("$target_dir". "workload_staff_list.*");
		if (count($FilesInDir) == 1){
			$error_code = HandleExcelData_WorkloadList($error_code, $FilesInDir[0]);
			if($error_code != 0){
				echo "Error in HandleExcelData_WorkloadList : error_code=$error_code\n";
			}
		}else{
			$error_code=4; // Cannot locate workload_staff_list excel file
			echo "Cannot locate examinable_staff_list excel file\n";
		}
	}else{
		echo "Error in HandleExcelData_ExaminableFacultyList : error_code=$error_code\n";
	}
} else {
	$error_code=4; // Cannot locate examinable_staff_list excel file
	echo "Cannot locate examinable_staff_list excel file\n";
}



$redirect =true;
if (isset ($_REQUEST['validate'])) {
	
	echo "validate=1";
	
}		 
else if($redirect){
	echo ($error_code != 0) ? "error_code=$error_code" : "examiner_setting=1";
	
}
exit;


// CUSTOM CODE GOES HERE ---- do whatever you want
function HandleExcelData_ExaminableFacultyList($error_code, $InputFile_FullPath){
    $Contents = "********************************** LOADING EXAMINABLE_STAFF_LIST **********************************\n";
    //$PHPExcelObj = PHPExcel_IOFactory::load($InputFile_FullPath);
    $PHPExcelObj = \PhpOffice\PhpSpreadsheet\IOFactory::load($InputFile_FullPath);
    $EXCEL_AllData = $PHPExcelObj->getActiveSheet()->toArray(null,true,true,true);

    global $TABLES, $conn_db_ntu;
    try{

        // initialize all staff examinable to be 0

        $Stmt = sprintf("SELECT * FROM  %s", $TABLES["staff"]);
        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
        $DBOBJ_Result->execute();
        $Data = $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);

        if (isset($Data['id']) && !empty($Data['id'])) {
            $initialize = sprintf("UPDATE %s SET exemption = 0,exemptionS2 = 0, examine = 0", $TABLES["staff"]);
            $conn_db_ntu->prepare($initialize)->execute();
        }

        $Offset						= 2; // Exclude headers
        $Total_DataInSheet 			= count($EXCEL_AllData) - $Offset;
        $Total_DataEmpty			= 0;
        $AL_StaffWithoutEmail 		= array();
        $AL_StaffNotInWorkLoadDB	= array();
        $RowCount 					= 0;
        $RowCount_Updated			= 0;
        $RowCount_Created			= 0;


        $sem = $_REQUEST['filter_Sem'];
        $year = $_REQUEST['filter_Year'];


        //$Stmt = sprintf("UPDATE %s SET exemption = %d,exemptionS2 = %d, examine = %d", $TABLES["staff"], 0,0,0);
        //$conn_db_ntu->prepare($Stmt)->execute();

        // Data starts at row 3
        for ($RowIndex = 3; $RowIndex <=  $Total_DataInSheet + $Offset; $RowIndex ++) {
            $RowCount++;

            if(isset($EXCEL_AllData[$RowIndex]["B"]) && !empty($EXCEL_AllData[$RowIndex]["B"])) {
                $EXCEL_StaffEmail = strtolower($EXCEL_AllData[$RowIndex]["B"]);
                $EXCEL_StaffName = $EXCEL_AllData[$RowIndex]["C"];
                $EXCEL_StaffID = explode("@", $EXCEL_StaffEmail)[0];
                $EXCEL_Loading = intval(explode("%", $EXCEL_AllData[$RowIndex]["E"])[0]);

                // Check if the staff in excel list is in staff table
                $Stmt = sprintf("SELECT * FROM  %s WHERE id = '%s'", $TABLES["staff"], $EXCEL_StaffID);
                $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                $DBOBJ_Result->execute();
                $Data = $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);

                // count the number of projects in the selected year and sem


                // only updates if there is project for the selected year and semester.
                $stmt2 = sprintf("SELECT COUNT(*) FROM  %s WHERE acad_year = '%s' AND sem = '%s'", $TABLES["fyp"], $year, 1);
                $DBOBJ_Result = $conn_db_ntu->prepare($stmt2);
                $DBOBJ_Result->execute();
                $projects = $DBOBJ_Result->fetchColumn();

                $facultySize = $Total_DataInSheet;
                //echo '<script> alert("$Total_DataInSheet")</script>';
                $base = $projects * 4 / $facultySize;
                $exemption = intval(floor(($base * (1 - ($EXCEL_Loading / 100)))));

                if (isset($Data['id']) && !empty($Data['id'])) {
                    // Try to update the examine of the staff

                    if ($sem == 2) {
                        $Stmt = sprintf("UPDATE %s SET exemption = %d, exemptionS2 = %d, examine = %d WHERE id = '%s'", $TABLES["staff"],$exemption, $EXCEL_Loading, 1, $EXCEL_StaffID);
                        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                        if ($DBOBJ_Result->execute()) {
                            $RowCount_Updated++;
                            $Contents = $Contents . sprintf("%03d. projects: %d Staff : %-25s : %-35s . Examine and exemption updated successfully \n", $RowCount,$projects, $Data['id'], $Data['name']);
                        } else {
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Examine was not updated successfully \n", $RowCount, $Data['id'], $Data['name']);
                        }
                    }

                    else {

                        $Stmt = sprintf("UPDATE %s SET exemption = %d, examine = %d WHERE id = '%s'", $TABLES["staff"], $exemption, 1, $EXCEL_StaffID);
                        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                        if ($DBOBJ_Result->execute()) {
                            $RowCount_Updated++;
                            $Contents = $Contents . sprintf("%03d. projects: %d Staff : %-25s : %-35s . Examine and exemption updated successfully \n", $RowCount, $projects, $Data['id'], $Data['name']);
                        } else {
                            $Contents = $Contents . sprintf("%03d. sem: %d. %d Staff : %-25s : %-35s . Examine was not updated successfully \n", $RowCount, $sem, $projects, $Data['id'], $Data['name']);
                        }
                    }
                } else {

                    if ($sem == 2) {
                        // Try to create the Examine of the staff
                        $Stmt = sprintf("INSERT INTO %s (id, email, name, workload, exemption, exemptionS2, examine) VALUES('%s', '%s', '%s', %d,%d, %d, %d)", $TABLES["staff"], $EXCEL_StaffID, $EXCEL_StaffEmail, $EXCEL_StaffName, 0, $exemption, $EXCEL_Loading, 1);
                        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                        if ($DBOBJ_Result->execute()) {
                            $RowCount_Created++;
                            $Contents = $Contents . sprintf("%03d. exe: %d sem: %d Staff : %-25s : %-35s : Exemption : %2d . Examine created successfully! \n", $RowCount,$exemption,$sem, $EXCEL_StaffID, $EXCEL_StaffName, $exemption);
                        } else {
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Examine was not created successfully \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName);
                        }
                    }
                    else {
                        // Try to create the Examine of the staff
                        $Stmt = sprintf("INSERT INTO %s (id, email, name, workload,exemption, examine) VALUES('%s', '%s', '%s', %d, %d, %d)", $TABLES["staff"], $EXCEL_StaffID, $EXCEL_StaffEmail, $EXCEL_StaffName, 0, $exemption, 1);
                        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                        if ($DBOBJ_Result->execute()) {
                            $RowCount_Created++;
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s : Exemption : %2d . Examine created successfully! \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName, $exemption);
                        } else {
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Examine was not created successfully \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName);
                        }
                    }
                }


                /*  $stmt2 = sprintf("SELECT COUNT(*) FROM  %s WHERE acad_year = '%s'", $TABLES["fyp"], $year);
                  $DBOBJ_Result = $conn_db_ntu->prepare($stmt2);
                  $DBOBJ_Result->execute();
                  $projects = $DBOBJ_Result->fetchColumn();

                  $facultySize = $Total_DataInSheet;
                  //echo '<script> alert("$Total_DataInSheet")</script>';
                  $base = $projects * 4 / $facultySize;
                */

                /*if ($sem == 2) {
                    /*$stmt2 = sprintf("SELECT COUNT(*) FROM  %s WHERE acad_year = '%s' AND sem = '%s'", $TABLES["fyp"], $year, $sem);
                    $DBOBJ_Result = $conn_db_ntu->prepare($stmt2);
                    $DBOBJ_Result->execute();
                    $projects = $DBOBJ_Result->fetchColumn()

                    $facultySize = $Total_DataInSheet;
                    //echo '<script> alert("$Total_DataInSheet")</script>';
                    $base = $projects * 4 / $facultySize;
                    $exemption = (int)floor($base * (1 - ($EXCEL_Loading / 100)));
                    */

                    // Check if the staff in excel list is in staff table
                   /* $Stmt = sprintf("SELECT * FROM  %s WHERE id = '%s'", $TABLES["staff"], $EXCEL_StaffID);
                    $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                    $DBOBJ_Result->execute();
                    $Data = $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);

                    if (isset($Data['id']) && !empty($Data['id'])) {
                        // Try to update the examine of the staff

                        $Stmt = sprintf("UPDATE %s SET exemptionS2 = %d, examine = %d WHERE id = '%s'", $TABLES["staff"], $EXCEL_Loading, 1, $EXCEL_StaffID);
                        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                        if ($DBOBJ_Result->execute()) {
                            $RowCount_Updated++;
                            $Contents = $Contents . sprintf("%03d. sem: %d. Staff : %-25s : %-35s . Examine updated successfully \n", $RowCount,$sem, $Data['id'], $Data['name']);
                        } else {
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Examine was not updated successfully \n", $RowCount, $Data['id'], $Data['name']);
                        }
                    } else {
                        // Try to create the Examine of the staff
                        $Stmt = sprintf("INSERT INTO %s (id, email, name, workload, exemptionS2, examine) VALUES('%s', '%s', '%s', %d, %d, %d)", $TABLES["staff"], $EXCEL_StaffID, $EXCEL_StaffEmail, $EXCEL_StaffName, 0, $EXCEL_Loading, 1);
                        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                        if ($DBOBJ_Result->execute()) {
                            $RowCount_Created++;
                            $Contents = $Contents . sprintf("%03d. sem: %d Staff : %-25s : %-35s : Exemption : %2d . Examine created successfully! \n", $RowCount,$sem, $EXCEL_StaffID, $EXCEL_StaffName, $exemption);
                        } else {
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Examine was not created successfully \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName);
                        }
                    }
                    /*  if (isset($Data['id']) && !empty($Data['id'])) {
                          $Stmt = sprintf("UPDATE %s SET exemptionS2 = %d, examine = %d WHERE id = '%s'", $TABLES["staff"], $EXCEL_Loading, 1, $EXCEL_StaffID);
                          $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                          if ($DBOBJ_Result->execute()) {
                              $RowCount_Updated++;

                              $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Sem : %d . Examine updated successfully \n", $RowCount, $Data['id'], $Data['name'], $sem);
                          } else {
                              $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Sem: %d. Examine was not updated successfully \n", $RowCount, $Data['id'], $Data['name'], $sem);

                          }
                      } else {
                          // Try to create the Examine of the staff
                          $Stmt = sprintf("INSERT INTO %s (id, email, name, workload,exemptionS2, examine) VALUES('%s', '%s', '%s', %d, %d, %d, %d)", $TABLES["staff"], $EXCEL_StaffID, $EXCEL_StaffEmail, $EXCEL_StaffName, 0, $EXCEL_Loading, 1);
                          $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                          if ($DBOBJ_Result->execute()) {
                              $RowCount_Created++;
                              $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s : Exemption : %2d Sem : %d . Examine created successfully! \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName, $EXCEL_Loading, $sem);
                          } else {
                              $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Sem: %d Examine was not created successfully \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName, $sem);
                          }
                      }
                    */


            } else{
                $Total_DataEmpty++;
                $EXCEL_StaffName 	= $EXCEL_AllData[$RowIndex]["C"];
                $AL_StaffWithoutEmail[$EXCEL_StaffName] = $EXCEL_StaffName;
                $Contents 	= $Contents . sprintf("%03d. %-30s : %-35s %-35s\n", $RowCount, "Empty email detected at row ", $RowCount, ". FAILED - No Email");
            }
        }

        // $Stmt = sprintf("SELECT COUNT(*) FROM %s WHERE examine=1;", $TABLES["staff_workload"]);
        // $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
        // $DBOBJ_Result->execute();
        // $RowCount = $DBOBJ_Result->fetchColumn();

        $Contents 	= $Contents . sprintf("%-35s : %04d/%04d\n", "Total staff examine updated", $RowCount_Updated, $Total_DataInSheet-$Total_DataEmpty);
        $Contents 	= $Contents . sprintf("%-35s : %04d\n", "Total staff examine created", $RowCount_Created);
        // RESULT
        $file = "submit_import_examiner_settings.txt";
        file_put_contents($file, $Contents, LOCK_EX);
        return $error_code;
    } catch(Exception $Ex){
        echo  $Ex->getMessage();
        return $error_code=5; // General exception
    }
}


function HandleExcelData_WorkloadList($error_code, $InputFile_FullPath){
	$Contents = "********************************** LOADING WORKLOAD_STAFF_LIST **********************************\n";
	//$PHPExcelObj = PHPExcel_IOFactory::load($InputFile_FullPath);
	$PHPExcelObj = \PhpOffice\PhpSpreadsheet\IOFactory::load($InputFile_FullPath);
	$EXCEL_AllData = $PHPExcelObj->getActiveSheet()->toArray(null,true,true,true);

	global $TABLES, $conn_db_ntu; 
	try{
		$Offset						= 2; // Exclude headers
		$Total_DataInSheet 			= count($EXCEL_AllData) - $Offset;	
		$Total_DataEmpty			= 0;
		$AL_StaffWithoutEmail 	= array();
		$RowCount 					= 0;
		$RowCount_Updated			= 0;
		$RowCount_Created			= 0;


		$projects= 0;
		$base = 0;

        $sem = $_REQUEST['filter_Sem'];

        $Stmt = sprintf("SELECT * FROM  %s", $TABLES["staff"]);
        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
        $DBOBJ_Result->execute();
        $Data = $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);
        if (isset($Data['id']) && !empty($Data['id'])) {
            $initialize = sprintf("UPDATE %s SET workload = 0", $TABLES["staff"]);
            $conn_db_ntu->prepare($initialize)->execute();
        }

        if ($sem == 2) {
            for ($RowIndex = 3; $RowIndex <= $Total_DataInSheet + $Offset; $RowIndex++) {
                $EXCEL_ProjectsSupervised = is_numeric($EXCEL_AllData[$RowIndex]["H"]) && $EXCEL_AllData[$RowIndex]["H"] >= 0 ? $EXCEL_AllData[$RowIndex]["H"] : 0;
                $EXCEL_MScSupervised = is_numeric($EXCEL_AllData[$RowIndex]["K"]) ? $EXCEL_AllData[$RowIndex]["K"] : 0;
                $projects = $projects + $EXCEL_ProjectsSupervised + $EXCEL_MScSupervised;
            }
            $facultySize = $Total_DataInSheet;
            $base = $projects * 4 / $facultySize;
        }


		// Data starts at row 3
		for ($RowIndex = 3; $RowIndex <=  $Total_DataInSheet + $Offset; $RowIndex ++) {
			$RowCount++;
			// Check if email is empty or null
			if(isset($EXCEL_AllData[$RowIndex]["B"]) && !empty($EXCEL_AllData[$RowIndex]["B"])){
				$EXCEL_StaffWorkLoad	= is_numeric ($EXCEL_AllData[$RowIndex]["N"]) && $EXCEL_AllData[$RowIndex]["N"] >= 0 ? $EXCEL_AllData[$RowIndex]["N"] : 0;
				$EXCEL_StaffID 		= strtolower($EXCEL_AllData[$RowIndex]["B"]);
				$EXCEL_StaffName 		= $EXCEL_AllData[$RowIndex]["A"];
				//$EXCEL_StaffID			= explode('@', $EXCEL_StaffEmail)[0];
                $EXCEL_StaffEmail       = strtolower($EXCEL_StaffID) . "@ntu.edu.sg";



				// Check if the staff in excel list is in staff table
				$Stmt 			= sprintf("SELECT * FROM  %s WHERE id = '%s'", $TABLES["staff"], $EXCEL_StaffID);
				$DBOBJ_Result 	= $conn_db_ntu->prepare($Stmt);
				$DBOBJ_Result->execute();
				$Data 			= $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);

				if(isset($Data['id']) && !empty($Data['id'])){
					// Try to update the workload of the staff

                    if ($sem == 2) {
                        $ProjectsSupervised   = is_numeric($EXCEL_AllData[$RowIndex]["H"]) && $EXCEL_AllData[$RowIndex]["H"] >= 0 ? $EXCEL_AllData[$RowIndex]["H"] : 0;
                        $MScSupervised = is_numeric($EXCEL_AllData[$RowIndex]["K"]) ? $EXCEL_AllData[$RowIndex]["K"] : 0;
                        $ProjectsExaminedinS1 = is_numeric($EXCEL_AllData[$RowIndex]["E"]) ? $EXCEL_AllData[$RowIndex]["E"] : 0;
                        $exemption = intval(floor($base * (1 - ($Data['exemptionS2'] / 100)) +  (($ProjectsSupervised + $MScSupervised) * 3) + $ProjectsExaminedinS1));

                        $Stmt 			= sprintf("UPDATE %s SET workload = %d, exemptionS2 = %d WHERE id = '%s'", $TABLES["staff"], $EXCEL_StaffWorkLoad, $exemption, $EXCEL_StaffID);
                        $DBOBJ_Result 	= $conn_db_ntu->prepare($Stmt);
                        if($DBOBJ_Result->execute()){
                            $RowCount_Updated++;
                            $Contents 	= $Contents . sprintf("%03d. Staff : %-25s : %-35s . ExemptionS2 and Workload updated successfully \n", $RowCount, $Data['id'], $Data['name']);
                        }else{
                            $Contents 	= $Contents . sprintf("%03d. Staff : %-25s : %-35s . ExemptionS2 and Workload was not updated successfully \n", $RowCount, $Data['id'], $Data['name']);
                        }

                    }
                    else {
                        $Stmt = sprintf("UPDATE %s SET workload = %d WHERE id = '%s'", $TABLES["staff"], $EXCEL_StaffWorkLoad, $EXCEL_StaffID);
                        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                        if ($DBOBJ_Result->execute()) {
                            $RowCount_Updated++;
                            $Contents = $Contents . sprintf("%03d. sem: %d. Staff : %-25s : %-35s . Workload updated successfully \n", $RowCount, $sem, $Data['id'], $Data['name']);
                        } else {
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Workload was not updated successfully \n", $RowCount, $Data['id'], $Data['name']);
                        }
                    }


					
				}else{

                    if ($sem == 2) {
                        $ProjectsSupervised   = is_numeric($EXCEL_AllData[$RowIndex]["H"]) && $EXCEL_AllData[$RowIndex]["H"] >= 0 ? $EXCEL_AllData[$RowIndex]["H"] : 0;
                        $MScSupervised = is_numeric($EXCEL_AllData[$RowIndex]["K"]) ? $EXCEL_AllData[$RowIndex]["K"] : 0;
                        $ProjectsExaminedinS1 = is_numeric($EXCEL_AllData[$RowIndex]["E"]) ? $EXCEL_AllData[$RowIndex]["E"] : 0;
                        $exemption = intval(floor($base * (1 - ($Data['exemptionS2'] / 100)) +  (($ProjectsSupervised + $MScSupervised) * 3) + $ProjectsExaminedinS1));
                        $Stmt 			= sprintf("INSERT INTO %s (id, email, name, workload, exemptionS2, examine) VALUES('%s', '%s', '%s', %d, %d, %d)", $TABLES["staff"], $EXCEL_StaffID, $EXCEL_StaffEmail, $EXCEL_StaffName, $EXCEL_StaffWorkLoad, $exemption, 0);
                        $DBOBJ_Result 	= $conn_db_ntu->prepare($Stmt);
                        if($DBOBJ_Result->execute()){
                            $RowCount_Created++;
                            $Contents 	= $Contents . sprintf("%03d. Staff : %-25s : %-35s . ExemptionS2 and Workload created successfully! \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName);
                        }else{
                            $Contents 	= $Contents . sprintf("%03d. Staff : %-25s : %-35s . ExemptionS2 was not created successfully \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName);
                        }

                    }
                    else {
                        // Try to create the workload of the staff
                        $Stmt = sprintf("INSERT INTO %s (id, email, name, workload, examine) VALUES('%s', '%s', '%s', %d, %d)", $TABLES["staff"], $EXCEL_StaffID, $EXCEL_StaffEmail, $EXCEL_StaffName, $EXCEL_StaffWorkLoad, 0);
                        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                        if ($DBOBJ_Result->execute()) {
                            $RowCount_Created++;
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Workload created successfully! \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName);
                        } else {
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Workload was not created successfully \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName);
                        }
                    }
				}


				//if()
			}else{
				$Total_DataEmpty++;
				$EXCEL_StaffName 		= $EXCEL_AllData[$RowIndex]["C"];
				$AL_StaffWithoutEmail[$EXCEL_StaffName] = $EXCEL_StaffName;
				$Contents 	= $Contents . sprintf("%03d. %-30s : %-35s %-35s\n", $RowCount, "Empty email detected at row ", $RowCount, ". FAILED - No Email");
			}
		}

		/*
        if ($sem == 2) {
            for ($RowIndex = 3; $RowIndex <= $Total_DataInSheet + $Offset; $RowIndex ++ ) {
                $EXCEL_ProjectsSupervised   = is_numeric($EXCEL_AllData[$RowIndex]["H"]) && $EXCEL_AllData[$RowIndex]["H"] >= 0 ? $EXCEL_AllData[$RowIndex]["H"] : 0;
                $EXCEL_MScSupervised = is_numeric($EXCEL_AllData[$RowIndex]["K"]) ? $EXCEL_AllData[$RowIndex]["K"] : 0;
                $projects = $projects + $EXCEL_ProjectsSupervised + $EXCEL_MScSupervised;
            }
            $facultySize = $Total_DataInSheet;
            $base = $projects * 4 / $facultySize;

            for ($RowIndex = 3; $RowIndex <= $Total_DataInSheet + $Offset; $RowIndex ++ ) {
                $EXCEL_StaffID 		= strtolower($EXCEL_AllData[$RowIndex]["B"]);
                $stmt3 = sprintf("SELECT * FROM  %s WHERE id = '%s'", $TABLES["staff"], $EXCEL_StaffID);
                $DBOBJ_Result = $conn_db_ntu->prepare($stmt3);
                $DBOBJ_Result->execute();
                $Data = $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);


                if (isset($Data['id']) && !empty($Data['id'])) {
                    $ProjectsSupervised   = is_numeric($EXCEL_AllData[$RowIndex]["H"]) && $EXCEL_AllData[$RowIndex]["H"] >= 0 ? $EXCEL_AllData[$RowIndex]["H"] : 0;
                    $MScSupervised = is_numeric($EXCEL_AllData[$RowIndex]["K"]) ? $EXCEL_AllData[$RowIndex]["K"] : 0;
                    $ProjectsExaminedinS1 = is_numeric($EXCEL_AllData[$RowIndex]["E"]) ? $EXCEL_AllData[$RowIndex]["E"] : 0;
                    $exemption = intval(floor($base * (1 - ($Data['exemptionS2'] / 100)) +  (($ProjectsSupervised + $MScSupervised) * 3) + $ProjectsExaminedinS1));

                    $Stmt 			= sprintf("UPDATE %s SET exemptionS2 = %d WHERE id = '%s'", $TABLES["staff"], $exemption, $EXCEL_StaffID);
                    $DBOBJ_Result 	= $conn_db_ntu->prepare($Stmt);
                    if($DBOBJ_Result->execute()){
                        $RowCount_Updated++;
                        $Contents 	= $Contents . sprintf("%03d. Staff : %-25s : %-35s . ExemptionS2 updated successfully \n", $RowCount, $Data['id'], $Data['name']);
                    }else{
                        $Contents 	= $Contents . sprintf("%03d. Staff : %-25s : %-35s . ExemptionS2 was not updated successfully \n", $RowCount, $Data['id'], $Data['name']);
                    }


                }

            }

        }*/

		$Contents 	= $Contents . sprintf("%-35s : %04d/%04d\n", "Total staff workload updated", $RowCount_Updated, $Total_DataInSheet-$Total_DataEmpty);
		$Contents 	= $Contents . sprintf("%-35s : %04d\n", "Total staff workload created", $RowCount_Created);
		// RESULT
		$file = "submit_import_examiner_settings.txt";
		file_put_contents($file, $Contents, FILE_APPEND | LOCK_EX);
		return $error_code;
	} catch(Exception $Ex){
		echo  $Ex->getMessage();
		return $error_code=5; // General exception
	}
}




?>