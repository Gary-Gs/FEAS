<?php require_once('../../../Connections/db_ntu.php'); 
	 require_once('../../../CSRFProtection.php');
	 require_once('../../../Utility.php');?>

<?php
	
		
	$csrf = new CSRFProtection();
	
	$_REQUEST['validate']=$csrf->cfmRequest();
	
	
	
	$rsStaff = $conn_db_ntu->query("SELECT * FROM ".$TABLES['staff']." ORDER BY id ASC")->fetchAll();


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
	$values = array();
	$delete = array();
	foreach ($rsStaff as $curStaff)
	{
		//$staffid = str_replace('.', '', $curStaff['id']);
		$staffid = $curStaff['id'];
		if(isset($_REQUEST['index_'.$staffid]))
		{
			
			$exemption = GetSQLValueString(trim($_REQUEST['exemption_'.$staffid]),"int");
			if($exemption === "NULL") $exemption = 0;
			$name = GetSQLValueString(trim($_REQUEST['name_' .$staffid]),"text");
			$name2 = GetSQLValueString(trim($_REQUEST['name2_' .$staffid]),"text");
			$email = str_replace(' ' , '',$_REQUEST['email_' .$staffid]);
			$canExamine = isset($_REQUEST['examine_'.$staffid]);
			$id = explode("@",$email)[0];

			// update staff id when email address is edited.
			if ($email != str_replace(" ","",$curStaff['email'])) {

				$Stmt = sprintf("UPDATE %s SET id = '%s', email='%s' where id ='%s'", $TABLES["staff"], $id,$email,$curStaff['id']);
				$DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
				$DBOBJ_Result->execute();
			}

			$email = GetSQLValueString($email,"text");
			$id = GetSQLValueString($id,"text");


			$values[] = sprintf("(%s, %s, %s, %s, %d, %d)",
								$id,$email, $name, $name2, $exemption, $canExamine);

			if (trim($_REQUEST['name_' .$staffid]) != $curStaff['name'] || trim($_REQUEST['name2_' .$staffid]) != $curStaff['name2'] || $canExamine != $curStaff['examine'] || $exemption != $curStaff['exemption']) {
				$Stmt = sprintf("UPDATE %s SET name=%s, name2=%s, examine=%d, exemption=%d where id =%s", $TABLES["staff"],$name,$name2,$canExamine,$exemption,$id);
				$DBOBJ_Result = $conn_db_ntu->prepare($Stmt);
				$DBOBJ_Result->execute();
			}

		}
		else {

			$staffid = GetSQLValueString($staffid,"text");
			$delete[] = sprintf("id=%s" , $staffid);
		}
	}

	if (!empty($delete)) {
		$query_Delete = sprintf("DELETE FROM %s WHERE %s", $TABLES["staff"], implode(" OR ", $delete));
		$conn_db_ntu->exec($query_Delete);

	}


	if (isset($_REQUEST['newEmail'])) {
		echo "ccccc";
	}

	//$query_UpdateExemption = sprintf("UPDATE %s SET email=%s, name=%s, name2=%s, exemption=%d, examine=%d WHERE id=%s", $TABLES["staff"], )

	//adding new staff.
	/*$c = 1;
	while (isset($_REQUEST['newStaffID_'. strval($c)])) {
		$name = GetSQLValueString(trim($_REQUEST['newName_' . strval($c)]),"text");
		$name2 = GetSQLValueString(trim($_REQUEST['newName2_' . strval($c)]),"text");
		$email = str_replace(' ' , '',$_REQUEST['newEmail_' . strval($c)]);
		$canExamine = isset($_REQUEST['newExamine_'. strval($c)]);
		$id = explode("@",$email)[0];
		//$id = GetSQLValueString(trim($_REQUEST['newStaffID_' . strval($c)]),"text");
		$exemption = GetSQLValueString(trim($_REQUEST['newExemption_'. strval($c)]),"int");
		if($exemption === "NULL") $exemption = 0;

		$email = GetSQLValueString($email,"text");
		$id = GetSQLValueString($id,"text");


		$values[] = sprintf("(%s, %s, %s, %s, %d, %d)",
			$id,$email, $name, $name2, $exemption, $canExamine);

		$c++;
	}
	*/

	
	/*$query_UpdateExemption  = sprintf("INSERT INTO %s (`id`,`email`,`name`,`name2`,`exemption`,`examine`) VALUES %s
ON DUPLICATE KEY UPDATE `email`=VALUES(`email`),`name`=VALUES(`name`),`name2`=VALUES(`name2`),`exemption`=VALUES(`exemption`), `examine`=VALUES(`examine`)",
							$TABLES['staff'],
							implode(",", $values));

	$conn_db_ntu->exec($query_UpdateExemption);*/
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
