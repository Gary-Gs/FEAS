<?php
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');
require_once('../../../vendor/autoload.php');


$redirect = false;
$error_code = 0; // no error


$csrf = new CSRFProtection();

$_REQUEST['validate'] = $csrf->cfmRequest();



$c = 0;
if (isset($_POST['emailList'])) {
    foreach ($_POST['emailList'] as $newRecord) {
        $newID = GetSQLValueString(explode("@", strtolower(trim($_POST['emailList'][$c])))[0], "text");
        $newEmail = GetSQLValueString(trim(strtolower($_POST['emailList'][$c])), "text");
        $newName = GetSQLValueString(trim($_POST['nameList'][$c]), "text");
        $newName2 = GetSQLValueString(trim($_POST['name2List'][$c]), "text");
        // $newExemption = GetSQLValueString(trim($_REQUEST['exemptionList'][$c]), "int");
        $newExamine = isset($_POST['examineList'][$c]);


        $query = sprintf("INSERT INTO %s (id, email, name, name2, examine) VALUES (%s, %s, %s, %s, %d)
        ON DUPLICATE KEY UPDATE name=%s, name2=%s, examine=%d ", $TABLES["staff"], $newID, $newEmail, $newName, $newName2, $newExamine, $newName, $newName2, $newExamine);
        $DBOBJ_Result = $conn_db_ntu->prepare($query);
        $DBOBJ_Result->execute();

        $c++;
    }
}

if (isset($_POST["mname2List"]) && !empty($_POST["mname2List"])) {
    $FilesInDir = glob("$target_dir" . "exemption.*");
    $FilesInDir_1 = glob("$target_dir" . "master.*");
    if (count($FilesInDir) == 1 && count($FilesInDir_1) == 1) {
        $error_code = HandleExcelData_Exemption($error_code, $FilesInDir[0], $FilesInDir_1[0]);
        if ($error_code != 0) {
            echo "Error in exemption or master: error_code=$error_code\n";
        }
    } else {
        $error_code = 4; // Cannot locate excel files
        echo "Cannot locate files\n";
    }
}


function HandleExcelData_Exemption($error_code, $ExemptionFile_FullPath, $MasterFile_FullPath)
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


        $RowCount_Updated = 0;
        $RowCount_Created = 0;

        // Data starts at row 3
        $counter = 0;
        foreach($_POST["mname2List"] as $nameList) {
            for ($RowIndex = 3; $RowIndex <= $Total_DataInSheet + $Offset; $RowIndex++) {
                if (isset($EXCEL_AllData[$RowIndex]["B"]) && !empty($EXCEL_AllData[$RowIndex]["B"])) {
                    // $EXCEL_StaffWorkLoad	= is_numeric ($EXCEL_AllData[$RowIndex]["N"]) && $EXCEL_AllData[$RowIndex]["N"] >= 0 ? $EXCEL_AllData[$RowIndex]["N"] : 0;
                    //$EXCEL_StaffID 		= strtolower($EXCEL_AllData[$RowIndex]["B"]);
                    $EXCEL_StaffName = trim($EXCEL_AllData[$RowIndex]["B"]);
                    //$EXCEL_StaffID			= explode('@', $EXCEL_StaffEmail)[0];
                    //$EXCEL_StaffEmail       = strtolower($EXCEL_StaffID) . "@ntu.edu.sg";


                    // Check if the user input name is in staff table
                    $Stmt = sprintf("SELECT * FROM  %s WHERE examine=1 AND (name = '%s' OR name2 ='%s')", $TABLES["staff"], $nameList[$counter], $nameList[$counter]);
                    $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                    $DBOBJ_Result->execute();
                    $Data = $DBOBJ_Result->fetch(PDO::FETCH_ASSOC);

                    if (isset($Data['id']) && !empty($Data['id']) && $EXCEL_StaffName == $Data['name']) {
                        $ProjectsSupervised = is_numeric($EXCEL_AllData[$RowIndex]["H"]) && $EXCEL_AllData[$RowIndex]["H"] >= 0 ? $EXCEL_AllData[$RowIndex]["H"] : 0;
                        $ProjectsExaminedinS1 = is_numeric($EXCEL_AllData[$RowIndex]["E"]) ? $EXCEL_AllData[$RowIndex]["E"] : 0;
                        $MScSupervised = 0;
                        $MScExamined = 0;
                        HandleExcelData_Master($error_code, $MasterFile_FullPath, $nameList[$counter], $_POST["mnameList"][$counter] , $MScSupervised, $MScExamined);

                        // exemption formula for sem 2
                        $exemption = intval(floor(doubleval($_SESSION["S2base"]) * (1 - ($Data['workload'] / 100)) + (($ProjectsSupervised + $MScSupervised) * 3) + $ProjectsExaminedinS1 + $MScExamined));

                        $MScContribution = intval($MScSupervised * 3 + $MScExamined);

                        $Stmt = sprintf("UPDATE %s SET exemptionS2 = %d, msc_contri = %d WHERE id='%s'", $TABLES["staff"], $exemption, $MScContribution, $Data['id']);
                        $DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
                        if ($DBOBJ_Result->execute()) {
                            $RowCount_Updated++;
                            $Contents = $Contents . sprintf("Staff : %-25s  . ExemptionS2 and msc_contri updated successfully \n",  $Data['name']);
                        } else {
                            $Contents = $Contents . sprintf(" Staff : %-25s  . ExemptionS2 and msc_contri was not updated successfully \n", $Data['name']);
                        }


                    }
                }else {
                    $Total_DataEmpty++;
                    //$EXCEL_StaffName = $EXCEL_AllData[$RowIndex]["B"];
                    //$AL_StaffWithoutEmail[$EXCEL_StaffName] = $EXCEL_StaffName;
                    $Contents = $Contents . sprintf("%-30s : %-35s %-35s\n", "Empty name detected at row ", $RowIndex-2, ". FAILED - No Name");
                }
            }

            $counter++;
        }

        $Contents = $Contents . sprintf("%-35s : %04d/%04d\n", "Total staff exemption updated", $RowCount_Updated, $Total_DataInSheet - $Total_DataEmpty);
        // RESULT
        $file = "submit_import_examiner_settings.txt";
        file_put_contents($file, $Contents, FILE_APPEND | LOCK_EX);

        return $error_code;
    }
    catch (Exception $Ex) {
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
        $RowCount = 1;


        // Data starts at row 3
        for ($RowIndex = 2; $RowIndex <= $Total_DataInSheet + $Offset; $RowIndex++) {
            $RowCount++;

            // Check if name is empty or null
            if (isset($EXCEL_AllData[$RowIndex]["A"]) && !empty($EXCEL_AllData[$RowIndex]["A"])) {
                $EXCEL_StaffName = $EXCEL_AllData[$RowIndex]["A"];
                $EXCEL_Supervision = is_numeric($EXCEL_AllData[$RowIndex]["B"]) && $EXCEL_AllData[$RowIndex]["B"] > 0 ? $EXCEL_AllData[$RowIndex]["B"] : 0;
                $EXCEL_Examined = is_numeric($EXCEL_AllData[$RowIndex]["C"]) && $EXCEL_AllData[$RowIndex]["C"] > 0 ? $EXCEL_AllData[$RowIndex]["C"] : 0;


                if ($EXCEL_StaffName == $staffName || $EXCEL_StaffName == $staffName2) {
                    $supervision = $EXCEL_Supervision;
                    $examined = $EXCEL_Examined;

                    $Contents = $Contents . sprintf("%03d. Staff : %-25s : %d, %d . Master contribution matched successfully \n", $RowCount, $EXCEL_StaffName, $EXCEL_Supervision, $EXCEL_Examined);
                }

            } else {
                $Contents = $Contents . sprintf("No Name at row " . $RowCount);
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



$conn_db_ntu = null;
unset($c);
unset($_SESSION["missingExaminerInExemption"]);
unset($_SESSION["newExaminerWithoutName2"]);
unset($_SESSION["S2base"]);
unset($_POST["mname2List"]);
unset($_POST["mnameList"]);
unset($_POST["memailList"]);
unset($_POST["mexamineList"]);
unset($_POST["nameList"]);
unset($_POST["name2List"]);
unset($_POST["emailList"]);
unset($_POST["examineList"]);



if (isset ($_REQUEST['validate'])) {
    header("location:examiner_setting.php?validate=1");
} else {
    header("location:examiner_setting.php?verified=1");
}


/**
 * Created by PhpStorm.
 * User: Luke
 * Date: 14/2/2019
 * Time: 9:55 AM
 */



