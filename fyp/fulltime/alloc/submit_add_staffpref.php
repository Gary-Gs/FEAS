<?php 
require_once('../../../Connections/db_ntu.php'); 

session_start();



// DELETE ALL STAFF PREF
$Stmt = sprintf("DELETE FROM %s", $TABLES["staff_pref"]);
$DB_Stmt = $conn_db_ntu->prepare($Stmt);
$DB_Stmt->execute();


// GET EXAMINABLE STAFF
$AL_Examinable_Staff=array();
$Stmt = sprintf("SELECT * FROM %s WHERE EXAMINE = 1", $TABLES["staff_workload"] );
$DB_Stmt = $conn_db_ntu->prepare($Stmt);
if($DB_Stmt->execute()){
	$DB_Result = $DB_Stmt->fetchAll(\PDO::FETCH_ASSOC);
	
	foreach ($DB_Result as $ExaminableStaffObj) {
		$AL_Examinable_Staff[$ExaminableStaffObj['staff_id']] = $ExaminableStaffObj;
		// echo $ExaminableStaffObj['staff_id'];
	}
	echo sprintf("%-30s : %d\n", "Total Examinable Staff", count($AL_Examinable_Staff));
	// print_r($AL_Examinable_Staff);
}


// SET EXAMINABLE STAFF 
$AL_BulkStmt = array();
$Max_PreferenceSelection = 3; // max selection for each examinable staff
$Choice = 0;
$AreaPrefer;
foreach ($AL_Examinable_Staff as $ExaminableStaff) {
	$Staff_ID = $ExaminableStaff['staff_id'];
	
	if($ExaminableStaff['staff_id'] == 'asjfcai'){
		$AL_BulkStmt[]= sprintf("('%s','%s',%d)", $Staff_ID, "SCE17-0012", 1);
		$AL_BulkStmt[]= sprintf("('%s','%s',%d)", $Staff_ID, "SCE17-0037", 2);
		$AL_BulkStmt[]= sprintf("('%s','%s',%d)", $Staff_ID, "k20", 3);
	}else{
		while ($Choice < $Max_PreferenceSelection) {
			$Choice++;
			$AreaPrefer = "k" . rand(1, 71);
			$AL_BulkStmt[]= sprintf("('%s','%s',%d)", $Staff_ID, $AreaPrefer, $Choice);
		}
	}
	
	$Choice = 0;

}
$BulkStmt = implode(",", $AL_BulkStmt);

// BULK INSERT
$Stmt = sprintf("INSERT INTO %s (staff_id,prefer,choice) VALUES %s", $TABLES["staff_pref"], $BulkStmt);
$DB_Stmt = $conn_db_ntu->prepare($Stmt);
echo $DB_Stmt->execute();

?>