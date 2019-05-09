<?php require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php'); ?>

<?php
$csrf = new CSRFProtection();
/* Prevent XSS input */
foreach ($_GET as $name => $value) {
    $name = htmlentities($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$_GET   = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

/* for server */
if($_SERVER['HTTP_REFERER'] != null){
	$urlString = explode('/', $_SERVER['HTTP_REFERER']);
	$foldername = $urlString[0];
	if(strcmp($foldername, 'https:') == 0){
		if(strcmp($_SERVER['HTTP_REFERER'], 'https://155.69.100.32/fyp/fulltime/alloc/allocation_setting.php') != 0){
			throw new Exception($_SERVER['Invalid referer']);
		}
	}
	elseif(strcmp($foldername,'http:') == 0){
		if(strcmp($_SERVER['HTTP_REFERER'], 'http://155.69.100.32/fyp/fulltime/alloc/allocation_setting.php') != 0){
			throw new Exception($_SERVER['Invalid referer']);
		}
	}
}

/* this is for testing in localhost 
if($_SERVER['HTTP_REFERER'] != null && strcmp($_SERVER['HTTP_REFERER'], 'http://localhost/fyp/fulltime/alloc/allocation_setting.php') != 0){
	throw new Exception("Invalid referer");
} */

$_REQUEST['validate'] = $csrf->cfmRequest();
try {
	$conn_db_ntu->exec("DELETE FROM " . $TABLES['allocation_settings_general']);
} catch (PDOException $e) {
	die($e->getMessage());
}
if (isset($_REQUEST['exam_year'])) {
	$examYear = $_REQUEST['exam_year'];

	// $updateQuery = sprintf("INSERT INTO %s (`id`, `exam_year`) VALUES (1, '%s') ON DUPLICATE KEY UPDATE `exam_year`=VALUES(`exam_year`)",
	// $TABLES['allocation_settings_general'], $check);
	// $conn_db_ntu->exec($updateQuery);
	// $stmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['allocation_settings_general'] . " (`id`, `exam_year`) VALUES (1 , ?) ON DUPLICATE KEY UPDATE `exam_year`= VALUES(`exam_year`)");
	$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_others'] . " (`id`, `exam_year`) VALUES (1 , ?) ON DUPLICATE KEY UPDATE `exam_year`= VALUES(`exam_year`)");
	$stmt->bindParam(1, $examYear);
	$stmt->execute();
}
if (isset($_REQUEST['exam_sem'])) {
	$examSem = $_REQUEST['exam_sem'];

	$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_others'] . " (`id`, `exam_sem`) VALUES (1 , ?) ON DUPLICATE KEY UPDATE `exam_sem`= VALUES(`exam_sem`)");
	$stmt->bindParam(1, $examSem);
	$stmt->execute();
}
if (isset($_REQUEST['number_of_days'])) {
	$noOfDays = $_REQUEST['number_of_days'];
	$id = $i + 1;

	$stmt = $conn_db_ntu->prepare("UPDATE " . $TABLES['allocation_settings_others'] . " SET alloc_days= ? " . "WHERE type= 'FT'");
	$stmt->bindParam(1, $noOfDays);
	$stmt->execute();
}
if (isset($_REQUEST['alloc_days'])) {
// include for loop for the 3 different days
	for ($i = 0; $i < sizeof($_REQUEST['alloc_days']); $i++) {
		$check = $_REQUEST['alloc_days'][$i];
		$id = $i + 1;

		$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general'] . " (`id`, `alloc_date`) VALUES (? , ?) ON DUPLICATE KEY UPDATE `alloc_date`= VALUES(`alloc_date`)");
		$stmt->bindParam(1, $id);
		$stmt->bindParam(2, $check);
		$stmt->execute();
	}
}
if (isset($_REQUEST['start_time'])) {
	// include for loop for the 3 different days
	for ($i = 0; $i < sizeof($_REQUEST['start_time']); $i++) {
		$check = $_REQUEST['start_time'][$i];
		$id = $i + 1;

		$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general'] . " (`id`, `alloc_start`) VALUES (? , ?) ON DUPLICATE KEY UPDATE `alloc_start`= VALUES(`alloc_start`)");
		$stmt->bindParam(1, $id);
		$stmt->bindParam(2, $check);
		$stmt->execute();
	}
}
if (isset($_REQUEST['end_time'])) {
	// include for loop for the 3 different days
	for ($i = 0; $i < sizeof($_REQUEST['end_time']); $i++) {
		$check = $_REQUEST['end_time'][$i];
		$id = $i + 1;

		$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general'] . " (`id`, `alloc_end`) VALUES (? , ?) ON DUPLICATE KEY UPDATE `alloc_end`= VALUES(`alloc_end`)");
		$stmt->bindParam(1, $id);
		$stmt->bindParam(2, $check);
		$stmt->execute();
	}
}
if (isset($_REQUEST['duration'])) {
	// include for loop into the 3 different days
	for ($i = 0; $i < sizeof($_REQUEST['duration']); $i++) {
		$check = $_REQUEST['duration'][$i];
		$id = $i + 1;

		$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general'] . " (`id`, `alloc_duration`) VALUES (? , ?) ON DUPLICATE KEY UPDATE `alloc_duration`= VALUES(`alloc_duration`)");
		$stmt->bindParam(1, $id);
		$stmt->bindParam(2, $check);
		$stmt->execute();
	}
}

// opt out option
// delete all opt-out values for all 3 days
// re-insert values back to the database; if no values are re-inserted, by default all values are 0
if (isset($_REQUEST['opt_out'])) { // there some checkbox value selected
	for ($i = 0; $i < sizeof($_REQUEST['opt_out']); $i++) {
		$elementNo = $_REQUEST['opt_out'][$i];
		$id = $elementNo + 1;
		$value = 1;

		$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general'] . " (`id`, `opt_out`) VALUES ( ? , ?) ON DUPLICATE KEY UPDATE `opt_out`= VALUES(`opt_out`)");
		$stmt->bindParam(1, $id);
		$stmt->bindParam(2, $value);
		$stmt->execute();
	}
}

// Set Values (Room)
try {
	//delete everything from table
	$conn_db_ntu->exec("DELETE FROM " . $TABLES['allocation_settings_room']);
} catch (PDOException $e) {
	die($e->getMessage());
}

/*
$i = 1;
$j = 1;
while (isset($_REQUEST['room_' . $i])) {
	echo "<br/>";
	$check = $_REQUEST['room_' . $i];
	if (empty($check))
		echo "Empty";
	else {
		// $check = GetSQLValueString(trim($check),"text");
		if ($check != "NULL") {
			// double check if the records exist in the database aldr
			// if exist then do not need to insert again
			// $exists = $conn_db_ntu->query("SELECT * FROM ".$TABLES['allocation_settings_room']." WHERE `roomName`= $check");
			$stmt = $conn_db_ntu->prepare("SELECT * FROM " . $TABLES['allocation_settings_room'] . " WHERE roomName like ?");
			$stmt->bindParam(1, $check);
			$stmt->execute();
			if ($stmt->rowCount() == 0) // if ($exists->rowCount() == 0) {
			{
				// re-insert the values into database
				// $conn_db_ntu->exec("INSERT INTO ".$TABLES['allocation_settings_room']." (`id`, `roomName`) VALUES ($j, $check)");
				$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_room'] . " (`id`, `roomName`) VALUES (?, ?)");
				$stmt->bindParam(1, $j);
				$stmt->bindParam(2, $check);
				$stmt->execute();
				$j++;
				echo "C:" . $check . ":";
			}
		}
	}
	$i++;
}

// update (id, roomName2) in the event have
// scenario 2, what if day 1 0 entries, then do checking, then do insert instead of update. Repeat for Day 3
// update database - for day 2 room allocation
$i = 1;
$j = 1;
while (isset($_REQUEST['room1_' . $i])) {
	echo "<br/>";
	$check = $_REQUEST['room1_' . $i];
	if (empty($check))
		echo "Empty";
	else {
		// $check = GetSQLValueString(trim($check),"text");
		if ($check != "NULL") {
			// double check if the records exist in the database aldr
			// if exist then do not need to insert again
			$stmt = $conn_db_ntu->prepare("SELECT * FROM " . $TABLES['allocation_settings_room'] . " WHERE roomName2 like ?");
			$stmt->bindParam(1, $check);
			$stmt->execute();
			if ($stmt->rowCount() == 0) // if ($exists->rowCount() == 0) {
			{
				// check if id exist before inserting into the database
				// $id_exists = $conn_db_ntu->query("SELECT * FROM ".$TABLES['allocation_settings_room']." WHERE `id`=$j");
				$stmt = $conn_db_ntu->prepare("SELECT * FROM " . $TABLES['allocation_settings_room'] . " WHERE id like ?");
				$stmt->bindParam(1, $j);
				$stmt->execute();
				// if id does not exist insert into database
				if ($stmt->rowCount() == 0) // if ($id_exists->rowCount() == 0) {
				{
					// $conn_db_ntu->exec("INSERT INTO ".$TABLES['allocation_settings_room']." (`id`, `roomName2`) VALUES ($j, $check)");
					$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_room'] . " (`id`, `roomName2`) VALUES (? , ?)");
					$stmt->bindParam(1, $j);
					$stmt->bindParam(2, $check);
					$stmt->execute();
					$j++;
				} else // if id exist then update the record
				{
					// re-insert the values into database
					// assuming that id exist then can update
					// $conn_db_ntu->exec("UPDATE ".$TABLES['allocation_settings_room']." SET roomname2 =$check where id = $j");
					$stmt = $conn_db_ntu->prepare("UPDATE " . $TABLES['allocation_settings_room'] . " SET roomname2 = ? where id = ?");
					$stmt->bindParam(1, $check);
					$stmt->bindParam(2, $j);
					$stmt->execute();
					$j++;
				}
				echo "C:" . $check . ":";
			}
		}
	}
	$i++;
}
// Repeat for day 3
$i = 1;
$j = 1;
while (isset($_REQUEST['room2_' . $i])) {
	echo "<br/>";
	$check = $_REQUEST['room2_' . $i];
	if (empty($check))
		echo "Empty";
	else {
		// $check = GetSQLValueString(trim($check),"text");
		if ($check != "NULL") {
			// double check if the records exist in the database aldr
			// if exist then do not need to insert again
			$stmt = $conn_db_ntu->prepare("SELECT * FROM " . $TABLES['allocation_settings_room'] . " WHERE roomName3 like ?");
			$stmt->bindParam(1, $check);
			$stmt->execute();
			if ($stmt->rowCount() == 0) // if ($exists->rowCount() == 0) {
			{
				// check if id exist before inserting into the database
				// $id_exists = $conn_db_ntu->query("SELECT * FROM ".$TABLES['allocation_settings_room']." WHERE `id`=$j");
				$stmt = $conn_db_ntu->prepare("SELECT * FROM " . $TABLES['allocation_settings_room'] . " WHERE id like ?");
				$stmt->bindParam(1, $j);
				$stmt->execute();
				// if id does not exist insert into database
				if ($stmt->rowCount() == 0) // if ($id_exists->rowCount() == 0) {
				{
					// $conn_db_ntu->exec("INSERT INTO ".$TABLES['allocation_settings_room']." (`id`, `roomName2`) VALUES ($j, $check)");
					$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_room'] . " (`id`, `roomName3`) VALUES (? , ?)");
					$stmt->bindParam(1, $j);
					$stmt->bindParam(2, $check);
					$stmt->execute();
					$j++;
				} else // if id exist then update the record
				{
					// re-insert the values into database
					// assuming that id exist then can update
					// $conn_db_ntu->exec("UPDATE ".$TABLES['allocation_settings_room']." SET roomname2 =$check where id = $j");
					$stmt = $conn_db_ntu->prepare("UPDATE " . $TABLES['allocation_settings_room'] . " SET roomName3 = ? where id = ?");
					$stmt->bindParam(1, $check);
					$stmt->bindParam(2, $j);
					$stmt->execute();
					$j++;
				}
				echo "C:" . $check . ":";
			}
			// double check if the records exist in the database aldr
			// if exist then do not need to insert again
			// $exists = $conn_db_ntu->query("SELECT * FROM ".$TABLES['allocation_settings_room']." WHERE `roomName3`=$check");
			// if ($exists->rowCount() == 0)
			// {
			//         // check if id exist before inserting into the database
			//         $id_exists = $conn_db_ntu->query("SELECT * FROM ".$TABLES['allocation_settings_room']." WHERE `id`=$j");
			//         // if id does not exist insert into database
			//         if ($id_exists->rowCount() == 0)
			//         {
			//             $conn_db_ntu->exec("INSERT INTO ".$TABLES['allocation_settings_room']." (`id`, `roomName3`) VALUES ($j, $check)");
			//             $j++;
			//         }
			//         else // if id exist then update the record
			//         {
			//				// re-insert the values into database
			//				// assuming that id exist then can update
			//             $conn_db_ntu->exec("UPDATE ".$TABLES['allocation_settings_room']." SET roomname3 =$check where id = $j");
			//             $j++;
			//         }
			// 	echo "C:".$check.":";
			// }
		}
	}
	$i++;
}
// end of inserting for room allocation
*/
$i = 1;
$j = 1;
$roomDay1Array = array();
$roomDay2Array = array();
$roomDay3Array = array();

while (isset($_REQUEST['room1_' . $i])) {
	$roomName = $_REQUEST['room1_' . $i];
	if (empty($roomName)) {
		echo "Empty1";
	} else {
		$roomDay1Array[$i] = $roomName;
	}
	$i++;
}

$i = 1;
while (isset($_REQUEST['room2_' . $i])) {
	$roomName2 = $_REQUEST['room2_' . $i];
	if (empty($roomName2))
		echo "Empty2";
	else {
		$roomDay2Array[$i] = $roomName2;
	}
	$i++;
}

$i = 1;
while (isset($_REQUEST['room3_' . $i])) {
	$roomName3 = $_REQUEST['room3_' . $i];
	if (empty($roomName3))
		echo "Empty3";
	else {
		$roomDay3Array[$i] = $roomName3;
	}
	$i++;
}

// find a way to run a loop?
if (sizeof($roomDay1Array) > 0) {
	insUpdateRoom($roomDay1Array, 1);
}
if (sizeof($roomDay2Array) > 0) {
	insUpdateRoom($roomDay2Array, 2);
}
if (sizeof($roomDay3Array) > 0) {
	insUpdateRoom($roomDay3Array, 3);
}

function insUpdateRoom($roomDayArray, $day) {
	global $conn_db_ntu, $TABLES;
	$roomArr = json_encode($roomDayArray);
	if ($roomArr != null) {

		$stmt1 = $conn_db_ntu->prepare("SELECT * FROM " . $TABLES['allocation_settings_room'] . " WHERE day = ?");
		$stmt1->bindParam(1, $day);
		$stmt1->execute();

		if ($stmt1->rowCount() == 0) {

			$stmt1 = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_room'] . " ( `day`, `roomArray`) VALUES (? , ?)");
			$stmt1->bindParam(1, $day);
			$stmt1->bindParam(2, $roomArr);
			$stmt1->execute();

		} else {

			$stmt1 = $conn_db_ntu->prepare("UPDATE " . $TABLES['allocation_settings_room'] . " SET roomArray = ? where day = ?");
			$stmt1->bindParam(1, $roomArr);
			$stmt1->bindParam(2, $day);
			$stmt1->execute();

		}
	}
}// end of inserting for room allocation


// apply to all
if (isset($_REQUEST['apply_to_all'])) {
	// loop 3 days
	for ($i = 0; $i < 3; $i++) {
		$id = $i + 1;

		$checkDuration = $_REQUEST['duration'][0];
		$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general'] . " (`id`, `alloc_duration`) VALUES ( ? , ? ) ON DUPLICATE KEY UPDATE `alloc_duration`= VALUES(`alloc_duration`)");
		$stmt->bindParam(1, $id);
		$stmt->bindParam(2, $checkDuration);
		$stmt->execute();

		$checkStart = $_REQUEST['start_time'][0];
		$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general'] . " (`id`, `alloc_start`) VALUES ( ? , ? ) ON DUPLICATE KEY UPDATE `alloc_start`= VALUES(`alloc_start`)");
		$stmt->bindParam(1, $id);
		$stmt->bindParam(2, $checkStart);
		$stmt->execute();

		$checkEnd = $_REQUEST['end_time'][0];
		$stmt = $conn_db_ntu->prepare("INSERT INTO " . $TABLES['allocation_settings_general'] . " (`id`, `alloc_end`) VALUES ( ? , ? ) ON DUPLICATE KEY UPDATE `alloc_end`= VALUES(`alloc_end`)");
		$stmt->bindParam(1, $id);
		$stmt->bindParam(2, $checkEnd);
		$stmt->execute();

		// execute apply to all time alloc queries  <---- already executed in each if-else clause
		// delete all room allocation
		$conn_db_ntu->exec("DELETE FROM " . $TABLES['allocation_settings_room']);

		$roomNo = 1;
		$roomArray = array();
		while (isset($_REQUEST['room1_' . $roomNo])) {
			$roomName = $_REQUEST['room1_' . $roomNo];
			if (empty($roomName)) {
				echo "Empty";
			} else {
				$roomArray[$roomNo] = $roomName;
			}
			$roomNo++;
		}
		if (isset ($roomArray)) {
			$noOfDays = $_REQUEST['number_of_days'];
			for ($m = 1; $m <= $noOfDays; $m++) {
				insUpdateRoom($roomArray, $m);
			}
		}
	}
}
$conn_db_ntu = null;
?>
<?php
if (isset ($_REQUEST['validate'])) {
	header("location:allocation_setting.php?validate=1");
} else {
	header("location:allocation_setting.php?save=1");
}
exit;
?>
