<?php
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
//require_once ('../../../PHPExcel/IOFactory.php');
require_once('../../../vendor/autoload.php');

ini_set('max_execution_time', 600);

$redirect = false;
$error_code = 0; // no error

$csrf = new CSRFProtection();

$_REQUEST['validate'] = $csrf->cfmRequest();


// initialise variables for file upload
$target_dir = "../../../uploaded_files/";
$target_file = "";
$inputFileType = "";
$inputFileName = "";


if (isset($_FILES["FileToUpload_ExaminerSettings"])) {
    // Count total files
    $Countfiles = count($_FILES["FileToUpload_ExaminerSettings"]["name"]);
    $CountFilesMoved = 0;
   // echo $Countfiles;
    // Looping all files and move to target dir
    for ($i = 0; $i < $Countfiles; $i++) {
        $target_file = $target_dir . basename($_FILES["FileToUpload_ExaminerSettings"]["name"][$i]);
        //echo $target_file;
        $inputFileType = pathinfo($target_file, PATHINFO_EXTENSION);
        $inputFileName = $_FILES["FileToUpload_ExaminerSettings"]["name"][$i];
        // echo $target_file;
        if ($inputFileType == "xlsx" || $inputFileType == "xls" || $inputFileType == "csv") {
            if (move_uploaded_file($_FILES["FileToUpload_ExaminerSettings"]["tmp_name"][$i], $target_file)) {
                // do nothing
            } else {
                $error_code = 3;    // File is open
            }
        } else {
            $error_code = 2; // Invalid file type
        }
    }
} else {
    $error_code = 1; // Cannot find uploaded file
}


if (isset($_REQUEST['filter_Sem'])) {

    $sem = $_REQUEST['filter_Sem'];


    if ($sem == 1) {

        $FilesInDir = glob("$target_dir" . "examiner_list.*");
        if (count($FilesInDir) == 1) {
            $error_code = HandleExcelData_ExaminerList($error_code, $FilesInDir[0],null);

            if ($error_code != 0) {
                echo "Error in HandleExcelData_ExaminableFacultyList : error_code=$error_code\n";
            }
        } else {
            $error_code = 4; // Cannot locate examinable_staff_list excel file
            echo "Cannot locate examiner_list\n";
        }

    } else if ($sem == 2) {

        // Assign examinable faculty into DB first
        $FilesInDir = glob("$target_dir" . "examiner_list.*");
        $FilesInDir_1 = glob("$target_dir" . "exemption.*");
        $FilesInDir_2 = glob("$target_dir" . "master.*");
        if (count($FilesInDir) == 1) {
            $error_code = HandleExcelData_ExaminerList($error_code, $FilesInDir[0],$FilesInDir_1[0]);
            if ($error_code == 0) { // no error
                if (count ($FilesInDir) == 1 && count($FilesInDir_1) == 1 && count($FilesInDir_2) == 1) {
                    $error_code = HandleExcelData_Exemption($error_code, $FilesInDir[0], $FilesInDir_1[0], $FilesInDir_2[0]);
                    if ($error_code != 0) {
                        echo "Error in exemption or master: error_code=$error_code\n";
                    }
                } else {
                    $error_code = 4; // Cannot locate excel files
                    echo "Cannot locate files\n";
                }
            } else {
                echo "Error in HandleExcelData_ExaminerList : error_code=$error_code\n";
            }
        } else {
            $error_code = 4; // Cannot locate examinable_staff_list excel file
            echo "Cannot locate examiner_list excel file\n";
        }
    }
}


$redirect = true;
if (isset ($_REQUEST['validate'])) {

    if (session_status() !==PHP_SESSION_ACTIVE) { session_start();}
    $_SESSION["semester"] = $_REQUEST["filter_Sem"];
    $_SESSION["year"] = $_REQUEST["filter_Year"];
    echo "validate=1";

} else if ($redirect) {
    //header("location:examiner_setting.php?uploaded=1");
    //header("Location: examiner_settings.php",true,302);
    if (session_status() !==PHP_SESSION_ACTIVE) { session_start();}
    $_SESSION["semester"] = $_REQUEST["filter_Sem"];
    $_SESSION["year"] = $_REQUEST["filter_Year"];
   echo ($error_code != 0) ? "error_code=$error_code" : "examiner_setting=1";

}
exit();


// CUSTOM CODE GOES HERE ---- do whatever you want
function HandleExcelData_ExaminerList($error_code, $InputFile_FullPath, $ExemptionFile_FullPath)
{
    $Contents = "********************************** LOADING examiner_list **********************************\n";
    //$PHPExcelObj = PHPExcel_IOFactory::load($InputFile_FullPath);
    $PHPExcelObj = \PhpOffice\PhpSpreadsheet\IOFactory::load($InputFile_FullPath);

    $EXCEL_AllData = $PHPExcelObj->getActiveSheet()->toArray(null, true, true, true);

    global $TABLES, $conn_db_ntu;
    try {



        $Stmt = sprintf("SELECT * FROM  %s", $TABLES["staff"]);
        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
        $DBOBJ_Result->execute();
        $Data = $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);


        // initialize all staff examinable to be 0
        if (isset($Data['id']) && !empty($Data['id'])) {
            $initialize = sprintf("UPDATE %s SET exemption = 0, examine = 0", $TABLES["staff"]);
            $conn_db_ntu->prepare($initialize)->execute();
        }

        $Offset = 1; // Exclude headers
        $Total_DataInSheet = count($EXCEL_AllData) - $Offset;
        $Total_DataEmpty = 0;
       // $staffWithoutEmail = array();
        $newExaminerWithoutName2 = array();
        $missingExaminerInExemption = array();

        $RowCount = 0;
        $RowCount_Updated = 0;
        $RowCount_Created = 0;

        if (isset($_REQUEST['filter_Year']))
            $year = $_REQUEST['filter_Year'];


        //$Stmt = sprintf("UPDATE %s SET exemption = %d,exemptionS2 = %d, examine = %d", $TABLES["staff"], 0,0,0);
        //$conn_db_ntu->prepare($Stmt)->execute();

        // Data starts at row 2
        for ($RowIndex = 2; $RowIndex <= $Total_DataInSheet + $Offset; $RowIndex++) {
            $RowCount++;

            if (isset($EXCEL_AllData[$RowIndex]["D"]) && !empty($EXCEL_AllData[$RowIndex]["D"])) {
                $EXCEL_StaffEmail = strtolower(trim($EXCEL_AllData[$RowIndex]["D"]));
                $EXCEL_StaffName = trim($EXCEL_AllData[$RowIndex]["B"]);
                $EXCEL_StaffID = explode("@", $EXCEL_StaffEmail)[0];
                $EXCEL_Loading = intval(explode("%", $EXCEL_AllData[$RowIndex]["G"])[0]);

                // Check if the staff in excel list is in staff table
                $Stmt = sprintf("SELECT * FROM  %s WHERE id = '%s'", $TABLES["staff"], $EXCEL_StaffID);
                $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                $DBOBJ_Result->execute();
                $Data = $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);

                $temp = (int)substr($year, -2);
                $acadyear = (int)((string)$temp . (string)($temp + 1));


                // count the number of projects in the selected year and sem
                // only updates if there is project for the selected year and semester.
                $stmt2 = sprintf("SELECT COUNT(*) FROM  %s WHERE examine_year = '%s' AND examine_sem = '%s'", $TABLES["fea_projects"], $acadyear, 1);
                $DBOBJ_Result = $conn_db_ntu->prepare($stmt2);
                $DBOBJ_Result->execute();
                $projects = $DBOBJ_Result->fetchColumn();

                $facultySize = $Total_DataInSheet;
                //echo '<script> alert("$Total_DataInSheet")</script>';
                $base = $projects * 4 / $facultySize;



                // calculate staff's contribution in sem 1
                $stmt3 = sprintf("SELECT COUNT(*) FROM %s LEFT JOIN %s ON fyp_assign.project_id = fea_projects.project_id WHERE fea_projects.examine_year = %d AND fea_projects.examine_sem = %d AND staff_id = '%s'", $TABLES["fyp_assign"], $TABLES["fea_projects"], $acadyear, 1, $EXCEL_StaffID);
                $DBOBJ_Result = $conn_db_ntu->prepare($stmt3);
                $DBOBJ_Result->execute();
                $contribution = $DBOBJ_Result->fetchColumn();

                $exemption = intval(floor(($base * (1 - ($EXCEL_Loading / 100))))) + $contribution * 3;


                if (isset($Data['id']) && !empty($Data['id'])) {
                    // Try to update the examine of the staff

                    $Stmt = sprintf("UPDATE %s SET workload = %d, exemption = %d, examine = %d WHERE id ='%s'", $TABLES["staff"], $EXCEL_Loading, $exemption, 1, $EXCEL_StaffID);
                    $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                    if ($DBOBJ_Result->execute()) {
                        $RowCount_Updated++;
                        $Contents = $Contents . sprintf("%03d. Staff exemption: %d : %-25s : %-35s . Examine and exemption updated successfully \n", $RowCount, $exemption, $Data['id'], $Data['name']);
                    } else {
                        $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Examine was not updated successfully \n", $RowCount, $Data['id'], $Data['name']);
                    }

                } else {


                    // Try to create the Examine of the staff
                     $Stmt = sprintf("INSERT INTO %s (id, email, name, workload,exemption, examine) VALUES('%s', '%s', '%s', %d, %d, %d)", $TABLES["staff"], $EXCEL_StaffID, $EXCEL_StaffEmail,$EXCEL_StaffName, $EXCEL_Loading, $exemption, 1);
                     $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                     if ($DBOBJ_Result->execute()) {
                         $RowCount_Created++;
                         $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s : Exemption : %2d . Examine created successfully! \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName, $exemption);
                     } else {
                         $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . Examine was not created successfully \n", $RowCount, $EXCEL_StaffID, $EXCEL_StaffName);
                     }

                    // to do let user input name2
                    $newExaminerWithoutName2[] = array("name" => $EXCEL_StaffName, "email" => $EXCEL_StaffEmail);

                }


                $sem =0;

                if (isset($_REQUEST['filter_Sem']))
                    $sem = $_REQUEST['filter_Sem'];


                if ($sem == 2) {
                    findExaminersInExemption($ExemptionFile_FullPath, $EXCEL_StaffName,$EXCEL_StaffEmail,$missingExaminerInExemption);
                }


            } else {
                $Total_DataEmpty++;
                $Contents = $Contents . sprintf("%03d. %-30s : %03d %-35s\n", $RowCount, "Empty email detected at row ", $RowCount, ". FAILED - No Email");
            }
        }





        // store new examiner without name2 into session var for modal input
        if (!empty($newExaminerWithoutName2)) {
            if (session_status() !==PHP_SESSION_ACTIVE) { session_start();}
            $_SESSION["newExaminerWithoutName2"] = $newExaminerWithoutName2;
        }
        // store new examiner not in exemption file into session var for modal input
        if (!empty($missingExaminerInExemption)) {
            if (session_status() !==PHP_SESSION_ACTIVE) { session_start();}
            $_SESSION["missingExaminerInExemption"] = $missingExaminerInExemption;


        }
        $Contents = $Contents . sprintf("%-35s : %04d/%04d\n", "Total staff examine updated", $RowCount_Updated, $Total_DataInSheet - $Total_DataEmpty);
        $Contents = $Contents . sprintf("%-35s : %04d\n", "Total staff examine created", $RowCount_Created);
        // RESULT
        $file = "submit_import_examiner_settings.txt";
        file_put_contents($file, $Contents, LOCK_EX);


        return $error_code;
    } catch (Exception $Ex) {
        echo $Ex->getMessage();
        return $error_code = 5; // General exception
    }
}

function getFacultySize($ExaminerFile_FullPath) {
    $PHPExcelObj = \PhpOffice\PhpSpreadsheet\IOFactory::load($ExaminerFile_FullPath);
    $EXCEL_AllData = $PHPExcelObj->getActiveSheet()->toArray(null, true, true, true);

    try {

        $Offset = 1; // Exclude headers
        $Total_DataInSheet = count($EXCEL_AllData) - $Offset;


        return $Total_DataInSheet;
    }

    catch (Exception $Ex) {
        echo $Ex->getMessage();
        return -1; // General exception
    }



}

// finds missing examiners in exemption file to prompt
function findExaminersInExemption($ExemptionFile_FullPath,$staffName,$staffEmail,&$missingExaminerInExemption) {

    $PHPExcelObj = \PhpOffice\PhpSpreadsheet\IOFactory::load($ExemptionFile_FullPath);
    $EXCEL_AllData = $PHPExcelObj->getActiveSheet()->toArray(null, true, true, true);

    try {
        $Offset = 2; // Exclude headers
        $Total_DataInSheet = count($EXCEL_AllData) - $Offset;
        $found = false;


        for ($RowIndex = 3; $RowIndex <= $Total_DataInSheet + $Offset; $RowIndex++) {

            // Check if name is empty or null
            if (isset($EXCEL_AllData[$RowIndex]["B"]) && !empty($EXCEL_AllData[$RowIndex]["B"])) {
                if (strtolower(trim($staffName)) == strtolower(trim($EXCEL_AllData[$RowIndex]["B"]))) {
                    $found = true;
                }

            }
        }
        if ($found == false) {
            $missingExaminerInExemption[] = array("name" => $staffName, "email" => $staffEmail);
        }
        return 0;
    }
    catch (Exception $Ex) {
        echo $Ex->getMessage();
        return -1; // General exception
    }

}

function HandleExcelData_Exemption($error_code, $ExaminerFile_FullPath, $ExemptionFile_FullPath, $MasterFile_FullPath)
{
    $Contents = "********************************** LOADING exemption **********************************\n";
    //$PHPExcelObj = PHPExcel_IOFactory::load($InputFile_FullPath);
    $PHPExcelObj = \PhpOffice\PhpSpreadsheet\IOFactory::load($ExemptionFile_FullPath);
    $EXCEL_AllData = $PHPExcelObj->getActiveSheet()->toArray(null, true, true, true);

    global $TABLES, $conn_db_ntu;
    try {
        $Offset = 2; // Exclude headers
        $Total_DataInSheet = count($EXCEL_AllData) - $Offset;
        $Total_DataEmpty = 0;
        $newStaff = array();
        $RowCount = 0;
        $RowCount_Updated = 0;
        $RowCount_Created = 0;
        $specialNameCount = 0;

        $projects = 0;
        $base = 0;

        $sem =0;

        if (isset($_REQUEST['filter_Sem']))
            $sem = $_REQUEST['filter_Sem'];

        $Stmt = sprintf("SELECT * FROM  %s", $TABLES["staff"]);
        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
        $DBOBJ_Result->execute();
        $Data = $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);

        // reset staff's exemptionS2
        if (isset($Data['id']) && !empty($Data['id'])) {
            $initialize = sprintf("UPDATE %s SET exemptionS2 = 0, msc_contri = 0", $TABLES["staff"]);
            $conn_db_ntu->prepare($initialize)->execute();
        }

        $c = 1;
        if ($sem == 2) {
            for ($RowIndex = 3; $RowIndex <= $Total_DataInSheet + $Offset; $RowIndex++) {
                $EXCEL_ProjectsSupervised = is_numeric($EXCEL_AllData[$RowIndex]["H"]) && $EXCEL_AllData[$RowIndex]["H"] > 0 ? $EXCEL_AllData[$RowIndex]["H"] : 0;
                $projects = $projects + $EXCEL_ProjectsSupervised;
                //$EXCEL_MScSupervised = is_numeric($EXCEL_AllData[$RowIndex]["K"]) ? $EXCEL_AllData[$RowIndex]["K"] : 0;

            }
            $EXCEL_MScSupervised = 0;
            HandleExcelData_Master($error_code, $MasterFile_FullPath, $Data['name'], $Data['name2'], $EXCEL_MScSupervised, $c);
            $projects = $projects + $EXCEL_MScSupervised;
            $facultySize = getFacultySize($ExaminerFile_FullPath);
            $base = $projects * 4 / $facultySize;
            if (session_status() !==PHP_SESSION_ACTIVE) { session_start();}
            $_SESSION["S2base"] = $base;
        }


        // Data starts at row 3
        for ($RowIndex = 3; $RowIndex <= $Total_DataInSheet + $Offset; $RowIndex++) {
            $RowCount++;
            // Check if name is empty or null
            if (isset($EXCEL_AllData[$RowIndex]["B"]) && !empty($EXCEL_AllData[$RowIndex]["B"])) {
                $EXCEL_StaffName = trim($EXCEL_AllData[$RowIndex]["B"]);

                //name splitting
                $splitname = explode(" ",$EXCEL_StaffName);
                //remove additional commas
                for($index = 0; $index<sizeof($splitname); $index++){
                    if (strpos($splitname[$index],',') !== false) {
                        //str_replace( ',', '', $splitname[$index]);
                        //echo "<script>console.log( 'Debug0->: " . $splitname[$index] . "' );</script>";
                        $splitname[$index] = str_replace(',', '', $splitname[$index]);
                        //echo "<script>console.log( 'Debug->: " . $splitname[$index] . "' );</script>";

                    }

                }

                //special treatment for Eg: A B Adam
                for($index = 0; $index<sizeof($splitname); $index++){

                    if(strlen($splitname[$index]) == 1){
                        $specialNameCount++;
                    }
                }

                // Check if the staff in exemption excel list is in staff table
                // get staff details if the name1 or name2 is the same as the name in exemption file
                //for surname + name, length = 2 Eg: Tang Xueyan
                if(sizeof($splitname) == 2){
                    $Stmt = sprintf("SELECT * FROM  %s WHERE examine=1 AND ((name LIKE '%s%%' AND name LIKE '%%%s%%') OR (name2 LIKE '%s%%' AND name2 LIKE '%%%s%%') OR (name LIKE '%s%%' AND name LIKE '%%%s%%') OR (name2 LIKE '%s%%' AND name2 LIKE '%%%s%%'))",
                        $TABLES["staff"],$splitname[0], $splitname[1],$splitname[0], $splitname[1],$splitname[1], $splitname[0],$splitname[1], $splitname[0]);
                }else if($specialNameCount < 2){ // for those name that have 1 or no single character
                    $Stmt = sprintf("SELECT * FROM  %s WHERE examine=1 AND ((name LIKE '%%%s%%' AND name LIKE '%%%s%%' AND name LIKE '%%%s%%') OR (name2 LIKE '%%%s%%' AND name2 LIKE '%%%s%%') OR (name2 LIKE '%%%s%%' AND name2 LIKE '%%%s%%') OR (name2 LIKE '%%%s%%' AND name2 LIKE '%%%s%%' AND name2 LIKE '%%%s%%') OR (name2 LIKE '%%%s%%' AND name2 LIKE '%%%s%%') OR (name2 LIKE '%%%s%%' AND name2 LIKE '%%%s%%')) ", $TABLES["staff"],$splitname[0], $splitname[1],$splitname[2],$splitname[0], $splitname[1],$splitname[0],  $splitname[2],$splitname[0], $splitname[1],$splitname[2],$splitname[0], $splitname[1],$splitname[0],$splitname[2]);
                }else{
                    $Stmt = sprintf("SELECT * FROM  %s WHERE examine=1 AND (name LIKE '%%%s%%' AND name LIKE '%%%s%%' AND name LIKE '%%%s%%'  OR (name2 LIKE '%s%%' AND name2 LIKE '%%%s%%' AND name2 LIKE '%%%s%%'))", $TABLES["staff"],$splitname[0], $splitname[1],$splitname[2],  $splitname[0], $splitname[1],$splitname[2]);
                }

                $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                $DBOBJ_Result->execute();
                $Data = $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);
                //reset count for new name
                $specialNameCount = 0;
                if (isset($Data['id']) && !empty($Data['id'])) {

                    if ($sem == 2) {
                        $ProjectsSupervised = is_numeric($EXCEL_AllData[$RowIndex]["H"]) && $EXCEL_AllData[$RowIndex]["H"] >= 0 ? $EXCEL_AllData[$RowIndex]["H"] : 0;
                        $ProjectsExaminedinS1 = is_numeric($EXCEL_AllData[$RowIndex]["E"]) ? $EXCEL_AllData[$RowIndex]["E"] : 0;
                        $MScSupervised = 0;
                        $MScExamined = 0;
                        HandleExcelData_Master($error_code, $MasterFile_FullPath, $Data['name'], $Data['name2'], $MScSupervised, $MScExamined);

                        // exemption formula for sem 2
                        $exemption = intval(floor($base * (1 - ($Data['workload'] / 100)) + (($ProjectsSupervised + $MScSupervised) * 3) + $ProjectsExaminedinS1 + $MScExamined));

                        $MScContribution = intval($MScSupervised * 3 + $MScExamined);

                        $Stmt = sprintf("UPDATE %s SET exemptionS2 = %d, msc_contri = %d WHERE id='%s'", $TABLES["staff"], $exemption, $MScContribution, $Data['id']);
                        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                        if ($DBOBJ_Result->execute()) {
                            $RowCount_Updated++;
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . ExemptionS2 and Workload updated successfully \n", $RowCount, $Data['id'], $Data['name']);
                        } else {
                            $Contents = $Contents . sprintf("%03d. Staff : %-25s : %-35s . ExemptionS2 and Workload was not updated successfully \n", $RowCount, $Data['id'], $Data['name']);
                        }

                    }

                }
            } else {
                $Total_DataEmpty++;
                $Contents = $Contents . sprintf("%03d. %-30s : %-35s %-35s\n", $RowCount, "Empty name detected at row ", $RowCount, ". FAILED - No Name");
            }
        }

        $Contents = $Contents . sprintf("%-35s : %04d/%04d\n", "Total staff exemption updated", $RowCount_Updated, $Total_DataInSheet - $Total_DataEmpty);
        // RESULT
        $file = "submit_import_examiner_settings.txt";
        file_put_contents($file, $Contents, FILE_APPEND | LOCK_EX);
        return $error_code;
    } catch (Exception $Ex) {
        echo $Ex->getMessage();
        return $error_code = 5; // General exception
    }
}

function HandleExcelData_Master($error_code, $MasterFile_FullPath, $staffName, $staffName2, &$supervision, &$examined)
{
    $Contents = "********************************** LOADING master **********************************\n";
    //$PHPExcelObj = PHPExcel_IOFactory::load($InputFile_FullPath);
    $PHPExcelObj = \PhpOffice\PhpSpreadsheet\IOFactory::load($MasterFile_FullPath);
    $EXCEL_AllData = $PHPExcelObj->getActiveSheet()->toArray(null, true, true, true);
    try {

        $Offset = 1; // Exclude headers
        $Total_DataInSheet = count($EXCEL_AllData) - $Offset;
        $Total_DataEmpty = 0;
        $AL_StaffWithoutEmail = array();
        $RowCount = 0;
        $RowCount_Updated = 0;

        // Data starts at row 3
        for ($RowIndex = 2; $RowIndex <= $Total_DataInSheet + $Offset; $RowIndex++) {
            $RowCount++;

            // Check if name is empty or null
            if (isset($EXCEL_AllData[$RowIndex]["A"]) && !empty($EXCEL_AllData[$RowIndex]["A"])) {
                $EXCEL_StaffName = $EXCEL_AllData[$RowIndex]["A"];
                $EXCEL_Supervision = is_numeric($EXCEL_AllData[$RowIndex]["B"]) && $EXCEL_AllData[$RowIndex]["B"] > 0 ? $EXCEL_AllData[$RowIndex]["B"] : 0;
                $EXCEL_Examined = is_numeric($EXCEL_AllData[$RowIndex]["C"]) && $EXCEL_AllData[$RowIndex]["C"] > 0 ? $EXCEL_AllData[$RowIndex]["C"] : 0;


                if ($examined == 1) {
                    $supervision = $supervision + $EXCEL_Supervision;
                } else if ($EXCEL_StaffName == $staffName || $EXCEL_StaffName == $staffName2) {
                    $supervision = $EXCEL_Supervision;
                    $examined = $EXCEL_Examined;

                    $RowCount_Updated++;
                    $Contents = $Contents . sprintf("%03d. Staff : %-25s : %d, %d . Master contribution matched successfully \n", $RowCount, $EXCEL_StaffName, $EXCEL_Supervision, $EXCEL_Examined);
                }

            } else {
                $Contents = $Contents . sprintf("No Data");
            }

            $file = "submit_import_examiner_settings.txt";
            file_put_contents($file, $Contents, FILE_APPEND | LOCK_EX);

        }
        return $error_code;
    } catch (Exception $Ex) {
        echo $Ex->getMessage();
        return $error_code = 5; // General exception
    }


}