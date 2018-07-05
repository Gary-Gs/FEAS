<?php require_once('../../../Connections/db_ntu.php');?>
<?php
		
		
		$conn_db_ntu->exec("DELETE FROM ".$TABLES['allocation_result_part_time']);
		$conn_db_ntu->exec("DELETE FROM ".$TABLES['allocation_result_room_part_time']);
		$conn_db_ntu->exec("DELETE FROM ".$TABLES['allocation_result_timeslot_part_time']);
		$conn_db_ntu = null;
		echo "call=1";
		exit;
?>
