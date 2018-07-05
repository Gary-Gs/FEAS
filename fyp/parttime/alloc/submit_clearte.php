<?php require_once('../../../Connections/db_ntu.php'); ?>

<?php

		
		$conn_db_ntu->query("DELETE FROM ".$TABLES['fea_settings_availability_part_time']);
		$conn_db_ntu = null;
		header("location:timeslot_exception.php?call=1");
		exit;
?>
