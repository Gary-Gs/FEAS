<?php require_once('../../Connections/db_ntu.php'); 
       require_once('../../CSRFProtection.php');
	   require_once('../../Utility.php');?>
<?php
	$csrf = new CSRFProtection();
	$_REQUEST['csrf'] = $csrf->cfmRequest();

	
?>

<?php
		 if (isset ($_REQUEST['csrf'])) {
           header("location:staffpref_parttime.php?csrf=1");
		   exit;
         }


		$staffID = $_SESSION['id'];
	
		$stmt1 = $conn_db_ntu->prepare("DELETE FROM " . $TABLES['staff_pref_part_time']." WHERE staff_id = ?");
		$stmt1->bindParam(1, $staffID);
		$stmt1->execute();
		
		$conn_db_ntu = null;
		header("location:staffpref_parttime.php?call=1");
		exit;
		
		
?>
