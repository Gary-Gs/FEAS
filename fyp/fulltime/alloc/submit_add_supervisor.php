<?php 
require_once('../../../Connections/db_ntu.php');
require_once('../../../Utility.php'); ?>

<?php
// add new staff into database
$newID = GetSQLValueString(trim($_POST['id']), "text");
$newEmail = GetSQLValueString($_POST['id'] . '@ntu.edu.sg', "text");
$newName = GetSQLValueString(trim($_POST['name']), "text");


$query_Insert = sprintf("INSERT INTO %s (id, email, name) VALUES (%s, %s, %s) ", $TABLES["staff"], $newID, $newEmail, $newName);
$DBOBJ_Result = $conn_db_ntu->prepare($query_Insert);
$DBOBJ_Result->execute();

$conn_db_ntu = null;

?>
