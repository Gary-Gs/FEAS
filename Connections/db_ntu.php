<?php

if(isset($_SESSION['version'])) {
	$database_db_ntu = $_SESSION['version'];
}
else {
	
	
	$hostname_db_ntu = "localhost";
	$database_db_ntu = "ntu_fyp";
	$username_db_ntu = "root"; 
	$password_db_ntu = "";
	$port_db_ntu = 3306;
	//to be used on actual server 
	//$config = parse_ini_file($_SERVER["DOCUMENT_ROOT"]."/private/config.ini"); 
	//$hostname_db_ntu = $config['dbservername'];
	//$database_db_ntu = $config['dbname'];
	//$username_db_ntu = $config['dbusername'];
	//$password_db_ntu = $config['dbpassword'];
	
	
	
	$TABLES = array();
	$TABLES['fyp'] = 'fyp';						//All FYPs
	$TABLES['fyp_assign'] = 'fyp_assign';		//Assigned FYPs
	$TABLES['fyp_assign_part_time'] = 'fyp_assign_part_time'; // Assigned Part Time FYPs
	$TABLES['fea_projects'] = 'fea_projects';	//Active FYPs
	$TABLES['fea_projects_part_time'] = 'fea_projects_part_time';
	$TABLES['staff'] = 'staff';
	
	// ADDED
	$TABLES['student'] = 'student';
	$TABLES['interest_area'] = 'ss_keys';
	 
	
	
	//Entity Tables
	$TABLES['staff_pref'] = 'fea_staff_pref';
	$TABLES['staff_pref_part_time'] = 'fea_staff_pref_part_time';
	$TABLES['staff_workload'] = 'fea_staff_workload';
	$TABLES['allocation_result'] = 'fea_result';
	$TABLES['allocation_result_part_time'] = 'fea_result_part_time'; // for part time 
	$TABLES['allocation_result_room'] = 'fea_result_room';
	$TABLES['allocation_result_room_part_time'] = 'fea_result_room_part_time';// for part time 
	$TABLES['allocation_result_timeslot'] = 'fea_result_timeslot';
	$TABLES['allocation_result_timeslot_part_time'] = 'fea_result_timeslot_part_time';// for part time 
	
	//Setting Tables
	$TABLES['allocation_settings_general'] = 'fea_settings_general';
	$TABLES['allocation_settings_general_part_time'] = 'fea_settings_general_part_time';// for part time 
	$TABLES['allocation_settings_room'] = 'fea_settings_room';
	$TABLES['allocation_settings_room_part_time'] = 'fea_settings_room_part_time';// for part time 
	
	//Exception Table (TODO)
	$TABLES['fea_settings_availability'] = 'fea_settings_availability';
	$TABLES['fea_settings_availability_part_time'] = 'fea_settings_availability_part_time';// for part time 
	
	//storing the staff pref period, alloc days, exam yr and sem (as storing in fea_settings_general table has duplicates)
	$TABLES['allocation_settings_others']='fea_settings_others';
	
	
	//Log Table 
	$TABLES['fea_log'] = 'fea_log';
	
	//Connection
	
	$conn_db_ntu = new PDO("mysql:host=$hostname_db_ntu;port=$port_db_ntu;dbname=$database_db_ntu", $username_db_ntu, $password_db_ntu);
	$conn_db_ntu->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn_db_ntu->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
}
?>