<?php require_once('../../../Connections/db_ntu.php'); 
	 require_once('../../../CSRFProtection.php');
	 require_once('../../../Utility.php');?>

<?php
	
		
	$csrf = new CSRFProtection();
	
	$_REQUEST['validate']=$csrf->cfmRequest();
	
	
	
	$rsStaff = $conn_db_ntu->query("SELECT * FROM ".$TABLES['staff']." ORDER BY id ASC")->fetchAll();

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
	$values = array();
	foreach ($rsStaff as $curStaff)
	{
		$staffid = str_replace('.', '', $curStaff['id']);
		
		if(isset($_REQUEST['index_'.$staffid]))
		{
			
			$workload = GetSQLValueString($_REQUEST['workload_'.$staffid],"int");
			if($workload === "NULL") $workload = 0;

			$canExamine = isset($_REQUEST['examine_'.$staffid]);
			
			$values[] = sprintf("('%s', %d, %d)",
								$curStaff['id'], $workload, $canExamine);
		}
	}

	
	$query_UpdateWorkload  = sprintf("INSERT INTO %s (`id`, `workload`, `examine`) VALUES %s ON DUPLICATE KEY UPDATE `workload`=VALUES(`workload`), `examine`=VALUES(`examine`)",
							$TABLES['staff'],
							implode(",", $values));
							
	
	$conn_db_ntu = null;
	unset($rsStaff);
	unset($values);

	if(isset ($_REQUEST['validate'])) {
		   header("location:examiner_setting.php?validate=1");
	}
    else {
		header("location:examiner_setting.php?save=1");
    }
?>
