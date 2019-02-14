<?php
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');


$c = 0;
if (isset($_REQUEST['emailList'])) {
    foreach ($_REQUEST['emailList'] as $newRecord) {
        $newID = GetSQLValueString(explode("@", strtolower(trim($_REQUEST['emailList'][$c])))[0], "text");
        $newEmail = GetSQLValueString(strtolower($_REQUEST['emailList'][$c]), "text");
        $newName = GetSQLValueString(trim($_REQUEST['nameList'][$c]), "text");
        $newName2 = GetSQLValueString(trim($_REQUEST['name2List'][$c]), "text");
        $newExemption = GetSQLValueString(trim($_REQUEST['exemptionList'][$c]), "int");
        $newExamine = isset($_REQUEST['examineList'][$c]);


        $query_Insert = sprintf("INSERT INTO %s (id, email, name, name2, exemption, examine) VALUES (%s, %s, %s, %s, %d, %d)
        ON DUPLICATE KEY UPDATE name=%s, name2=%s, exemption=%d, examine=%d ", $TABLES["staff"], $newID, $newEmail, $newName, $newName2, $newExemption, $newExamine, $newName, $newName2, $newExemption, $newExamine);
        $DBOBJ_Result = $conn_db_ntu->prepare($query_Insert);
        $DBOBJ_Result->execute();

        $c++;
    }
}

$conn_db_ntu = null;
unset($c);
unset($_SESSION["staffWithoutEmail"]);
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



