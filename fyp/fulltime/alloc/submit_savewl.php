<?php require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php'); ?>

<?php


$csrf = new CSRFProtection();

$_REQUEST['validate'] = $csrf->cfmRequest();


$rsStaff = $conn_db_ntu->query("SELECT * FROM " . $TABLES['staff'] . " ORDER BY id ASC")->fetchAll();


/*$sem = 0;
if (isset($_REQUEST["filter_Sem"])) {
    $sem
}*/
/*
//Single Insert
foreach ($rsStaff as $curStaff)
{
    if(isset($_REQUEST['index_'.$curStaff['id']]))
    {
        $workload = GetSQLValueString($_REQUEST['workload_'.$curStaff['id']],"int");
        if($workload === "NULL") $workload = 0;

        $canExamine = isset($_REQUEST['examine_'.$curStaff['id']]);
        $updateQuery = sprintf("INSERT INTO %s (`staff_id`, `workload`, `examine`) VALUES ('%s', %d, %d) ON DUPLICATE KEY UPDATE `workload`=VALUES(`workload`), `examine`=VALUES(`examine`)",
                                $TABLES['staff_workload'], $curStaff['id'], $workload, $canExamine);
        $conn_db_ntu->exec($updateQuery);
    }
}*/

//Bulk Insert
$delete = array();
foreach ($rsStaff as $curStaff) {
    //$staffid = str_replace('.', '', $curStaff['id']);
    $staffid = $curStaff['id'];
    if (isset($_REQUEST['index_' . $staffid])) {


        $name = GetSQLValueString(trim($_REQUEST['name_' . $staffid]), "text");
        $name2 = GetSQLValueString(trim($_REQUEST['name2_' . $staffid]), "text");
        $email = strtolower(trim($_REQUEST['email_' . $staffid]));
        $canExamine = isset($_REQUEST['examine_' . $staffid]);
        $id = explode("@", $email)[0];

        // update staff id when email address is edited.
        if ($email != strtolower(trim($curStaff['email']))) {

            $query_Update = sprintf("UPDATE %s SET id = '%s', email='%s' where id ='%s'", $TABLES["staff"], $id, $email, $curStaff['id']);
            $DBOBJ_Result = $conn_db_ntu->prepare($query_Update);
            $DBOBJ_Result->execute();
        }

        $email = GetSQLValueString($email, "text");
        $id = GetSQLValueString($id, "text");

       /* $values[] = sprintf("(%s, %s, %s, %s, %d, %d)",
            $id, $email, $name, $name2, $exemption, $canExamine);
       */

        if (isset($_REQUEST['exemption_' . $staffid]) && !empty($_REQUEST['exemption_' . $staffid])) {
            $exemption = GetSQLValueString(trim($_REQUEST['exemption_' . $staffid]), "int");
            if ($exemption === "NULL") $exemption = 0;

            if (trim($_REQUEST['name_' . $staffid]) != $curStaff['name'] || trim($_REQUEST['name2_' . $staffid]) != $curStaff['name2'] || $canExamine != $curStaff['examine'] || $exemption != $curStaff['exemption']) {
                $query_Update = sprintf("UPDATE %s SET name=%s, name2=%s, examine=%d, exemption=%d where id =%s", $TABLES["staff"], $name, $name2, $canExamine, $exemption, $id);
                $DBOBJ_Result = $conn_db_ntu->prepare($query_Update);
                $DBOBJ_Result->execute();
            }
        }
        else if (isset($_REQUEST['exemptionS2_' . $staffid]) && !empty($_REQUEST['exemptionS2_' . $staffid])) {
            $exemptionS2 = GetSQLValueString(trim($_REQUEST['exemptionS2_' . $staffid]), "int");
            if ($exemptionS2 === "NULL") $exemptionS2 = 0;

            if (trim($_REQUEST['name_' . $staffid]) != $curStaff['name'] || trim($_REQUEST['name2_' . $staffid]) != $curStaff['name2'] || $canExamine != $curStaff['examine'] || $exemptionS2 != $curStaff['exemptionS2']) {
                $query_Update = sprintf("UPDATE %s SET name=%s, name2=%s, examine=%d, exemptionS2=%d where id =%s", $TABLES["staff"], $name, $name2, $canExamine, $exemptionS2, $id);
                $DBOBJ_Result = $conn_db_ntu->prepare($query_Update);
                $DBOBJ_Result->execute();
            }
        }
    } else {
        $staffid = GetSQLValueString($staffid, "text");
        echo $staffid;
        $delete[] = sprintf("id=%s", $staffid);
    }
}

if (!empty($delete) && isset($delete)) {
    $query_Delete = sprintf("DELETE FROM %s WHERE %s", $TABLES["staff"], implode(" OR ", $delete));
    $DBOBJ_Result = $conn_db_ntu->prepare($query_Delete);
    $DBOBJ_Result->execute();

}


$c = 0;
if (isset($_REQUEST['newEmail'])) {
    foreach ($_REQUEST['newEmail'] as $newRecord) {
        $newID = GetSQLValueString(explode("@", strtolower(trim($_REQUEST['newEmail'][$c])))[0], "text");
        $newEmail = GetSQLValueString(strtolower($_REQUEST['newEmail'][$c]), "text");
        $newName = GetSQLValueString(trim($_REQUEST['newName'][$c]), "text");
        $newName2 = GetSQLValueString(trim($_REQUEST['newName2'][$c]), "text");
        $newExemption = GetSQLValueString(trim($_REQUEST['newExemption'][$c]), "int");
        $newExamine = isset($_REQUEST['newExamine'][$c]);


        $query_Insert = sprintf("INSERT INTO %s (id, email, name, name2, exemption, examine) VALUES (%s, %s, %s, %s, %d, %d) 
		    ON DUPLICATE KEY UPDATE name=%s, name2=%s, exemption=%d, examine=%d ", $TABLES["staff"], $newID, $newEmail, $newName, $newName2, $newExemption, $newExamine, $newName, $newName2, $newExemption, $newExamine);
        $DBOBJ_Result = $conn_db_ntu->prepare($query_Insert);
        $DBOBJ_Result->execute();

        $c++;
    }
}

$conn_db_ntu = null;
unset($rsStaff);
//unset($values);
unset($delete);
unset($c);

if (isset ($_REQUEST['validate'])) {
    header("location:examiner_setting.php?validate=1");
} else {
    header("location:examiner_setting.php?save=1");
}
?>
