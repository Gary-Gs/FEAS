<?php

if(isset($_SESSION['version'])) {
	$database_db_ntu_student = $_SESSION['version'];
}
else {
	$hostname_db_ntu_student = "155.69.100.34";
	//$hostname_db_ntu_student = "localhost";
	$database_db_ntu_student = "ntu";
	$username_db_ntu_student = "root";
	$password_db_ntu_student = "";
	$password_db_ntu_student = "Password1";
	$port_db_ntu_student = 3306;

	$STUDENTTABLES = array();
	$STUDENTTABLES['fyp'] = 'fyp';						//All FYPs
	//$STUDENTTABLES['staff'] = 'staff';

	//Connection
	$conn_db_ntu_student = new PDO("mysql:host=$hostname_db_ntu_student;port=$port_db_ntu_student;dbname=$database_db_ntu_student", $username_db_ntu_student, $password_db_ntu_student);

	//$conn_db_ntu = new PDO("mysql:host=$hostname_db_ntu;port=$port_db_ntu;dbname=$database_db_ntu", $username_db_ntu, $password_db_ntu);
	$conn_db_ntu_student->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn_db_ntu_student->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
}
?>
