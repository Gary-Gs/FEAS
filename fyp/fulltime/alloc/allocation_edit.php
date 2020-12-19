<?php require_once('../../../Connections/db_ntu.php');
require_once('./entity.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php'); ?>
<?php

$csrf = new CSRFProtection();

$error_code = -1;
$hasTimeslot = false;
if (isset ($_REQUEST['dayRoom'])) {
	$day = $_REQUEST['dayRoom'];
	return generateRoomSelect('exam_room', NULL, $day);
}

if (isset($_POST['allocate_edit_project_id'])) {
	$projectID = $_POST['allocate_edit_project_id'];
}
else if ($_COOKIE['temp_pid']) {
	//echo $_COOKIE['temp_pid'];
	$projectID = $_COOKIE['temp_pid'];
	unset($_COOKIE['temp_pid']);
	$_SESSION['allocate_edit_project'] = $projectID;
} else if (isset($_SESSION['allocate_edit_project'])) {
	$projectID = $_SESSION['allocate_edit_project'];
}
//$projectID = $_REQUEST['project'];

if ($projectID == null || empty($projectID)) {
	$error_code = 1;
} else {
	//$query_rsStaff= "SELECT s.id as staffid, s.name as staffname, s.position as salutation FROM ".$TABLES['staff']." as s ORDER BY staffname ASC";

	//get examinable staff instead of all staff
	$query_rsStaff = "SELECT s.id as staffid, s.name as staffname, s.position as salutation FROM " . $TABLES['staff'] . " as s WHERE s.examine =1 ORDER BY staffname ASC";

	$query_rsProjectData = "SELECT p2.project_id as pno, p2.staff_id as staffid, p1.title as ptitle FROM " . $TABLES['fyp_assign'] . " as p2 LEFT JOIN " . $TABLES['fyp'] . " as p1 ON p2.project_id=p1.project_id WHERE p2.complete = 0 AND p2.project_id = ?";

	$query_rsProjectAssign = "SELECT * from " . $TABLES['allocation_result'] . " WHERE project_id = ?";

	$query_rsRoom = "SELECT * FROM " . $TABLES['allocation_result_room'] . " ORDER BY `id` ASC";

	$query_rsDay = "SELECT max(`day`) as day FROM " . $TABLES['allocation_result_timeslot'];

	$query_rsTimeslot = "SELECT * FROM " . $TABLES['allocation_result_timeslot'] . " ORDER BY `id` ASC";

	try {
		$staffs = $conn_db_ntu->query($query_rsStaff);

		//$projData 	= $conn_db_ntu->query($query_rsProjectData)->fetch();
		$stmt = $conn_db_ntu->prepare($query_rsProjectData);
		$stmt->bindParam(1, $projectID);
		$stmt->execute();
		$projData = $stmt->fetch();

		//$projResult = $conn_db_ntu->query($query_rsProjectAssign)->fetch();
		$stmt = $conn_db_ntu->prepare($query_rsProjectAssign);
		$stmt->bindParam(1, $projectID);
		$stmt->execute();
		$projResult = $stmt->fetch();

		$rooms = $conn_db_ntu->query($query_rsRoom);
		$timeslots = $conn_db_ntu->query($query_rsTimeslot);
		$rsDay = $conn_db_ntu->query($query_rsDay)->fetch();
	} catch (PDOException $e) {
		die($e->getMessage());
	}

	if (!$projData || !$projResult)
		$error_code = 2;
	else {
		//Staff
		$staffList = array();
		foreach ($staffs as $staff) {
			$staffList[$staff['staffid']] = new Staff($staff['staffid'],
				$staff['salutation'],
				$staff['staffname']);
		}

		$number_of_days = $rsDay['day'];
		for ($day = 1; $day <= $number_of_days; $day++)
			$timeslots_table[$day] = array();

		foreach ($timeslots as $timeslot) {
			$timeslots_table[$timeslot['day']][$timeslot['slot']] = new Timeslot($timeslot['id'],
				$timeslot['day'],
				$timeslot['slot'],
				DateTime::createFromFormat('H:i:s', $timeslot['time_start']),
				DateTime::createFromFormat('H:i:s', $timeslot['time_end']));
		}

		$rooms_table = retrieveRooms(1, "allocation_result_room");


		$hasTimeslot = ($number_of_days > 0 && count($timeslots_table) > 0 && count($rooms_table) > 0);

		$error_code = 0;
	}

}
if (isset ($_REQUEST['dayTime'])) {
	$day = $_REQUEST['dayTime'];
	return generateTimeSelect('exam_slot', NULL, $day);
}
function generateStaffSelect($id, $selected) {
	global $staffList;
	echo '<select id="' . $id . '" name="' . $id . '">';
	echo '<option value="-1">-</option>';
	foreach ($staffList as $staff) {
		$isSelected = ($staff->getID() == $selected) ? " selected" : "";
		echo '<option value="' . $staff->getID() . '"' . $isSelected . '>' . $staff->toString() . '</option>';
	}
	echo '</select>';
}

function generateDaySelect($id, $selected) {
	global $number_of_days;
	echo '<select id="' . $id . '" name="' . $id . '">';
	echo '<option value="-1">-</option>';
	for ($i = 1; $i <= $number_of_days; $i++) {
		$isSelected = ($i == $selected) ? " selected" : "";
		echo '<option value="' . $i . '"' . $isSelected . '>' . $i . '</option>';
	}
	echo '</select>';
}

function generateRoomSelect($id, $selected, $day) {
	$rooms_table = retrieveRooms($day, "allocation_settings_room");
	echo '<select id="' . $id . '" name="' . $id . '">';
	echo '<option value="-1">-</option>';

	if ($day == NULL || $day == -1)    //Minor Error Correction
	{
		//pass
	} else {

		foreach ($rooms_table as $room) {
			$roomID = (int)$room->getID();

			$isSelected = ($roomID == $selected) ? " selected" : "";
			echo '<option value="' . $roomID . '"' . $isSelected . '>' . $room->toString() . '</option>';
		}
	}
	echo '</select>';
}

function generateTimeSelect($id, $selected, $day) {
	global $timeslots_table;

	echo '<select id="' . $id . '" name="' . $id . '">';
	echo '<option value="-1">-</option>';

	if ($day == NULL || $day == -1)    //Minor Error Correction
	{
		//pass
	} else {

		foreach ($timeslots_table[$day] as $curTime) {
			$isSelected = ($curTime->getSlot() == $selected) ? " selected" : "";
			echo '<option value="' . $curTime->getID() . '"' . $isSelected . '>' . $curTime->toString() . '</option>';
		}
	}
	echo '</select>';
}


?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Edit Project Details </title>
	<?php require_once('../../../head.php'); ?>


    <script type="text/javascript">
        var timeslots;

        //$(document).ready(function () {
        //    //Copy time selection
        //    var day = 0;
        //    timeslots[0] = '<?php //generateTimeSelect('exam_slot', NULL, NULL); ?>//';
        //    //Undefined Fix
        //	<?php //for ($day = 0; $day < $number_of_days; $day++) {  ?>
        //    timeslots[++day] = '<?php //generateTimeSelect('exam_slot', $day + 1, NULL); //?>//';
        //	<?php //}?>
        //});


        function regenerateTimeSelect(day) {
            //	var exam_slot = document.getElementById("exam_slot");
            //	if (day < 0) day = 0;

            //	exam_slot.innerHTML = timeslots[day];
            var projID = $("#project_id").val();
            var dataArr = {"dayTime": day, "project": projID};

            $.ajax({
                type: "GET",
                url: "allocation_edit.php",
                data: dataArr,
                success: function (msg) {
                    $("#exam_slot").html("");
                    $("#exam_slot").html(msg);
                    console.log(msg);
                },
                error: function (msg) {
                    alert("error occurred");
                }
            });
        }

        function regenerateRoomSelect(day) {

            var projID = $("#project_id").val();
            var dataArr = {"dayRoom": day, "project": projID};

            $.ajax({
                type: "GET",
                url: "allocation_edit.php",
                data: dataArr,
                success: function (msg) {
                    $("#exam_room").html("");
                    $("#exam_room").html(msg);
                },
                error: function (msg) {
                    alert("error occurred");
                }
            });
        }


    </script>
    <style>
        .tdTitle {
            padding: 5px;
        }
    </style>
</head>

<body>
<?php require_once('../../../php_css/headerwnav.php'); ?>
<div style="margin-left: -15px;">
    <div class="container-fluid">
		<?php require_once('../../nav.php'); ?>
        <div class="container-fluid">
            <h3><?php echo ($error_code == 0) ? "$projectID" : "Edit Allocation"; ?></h3>
			<?php
			if (isset ($_REQUEST['validate'])) {
				echo "<p class='warn'> CSRF validation failed.</p>";
			}
			else if ($error_code != 0)
			{
				switch ($error_code) {
					case 1:
						echo "<p class='error'>[Edit Allocation] Failed: No Project Requested.</p>";
						break;
					case 2:
						echo "<p class='error'>[Edit Allocation] Failed: Invalid Project.</p>";
						break;
					default:
						echo "<p class='error'>[Edit Allocation] Failed: Unknown Error has occurred. </p>";
						break;
				}

				echo '<p><a href="allocation.php" class="bt" style="width:130px;" title="< Back to Allocations">&#60;&#60; Back to Allocations</a></p>';
			}
			else{
			?>
            <h4><?php echo ($projData['ptitle'] != null) ? $projData['ptitle'] : "-"; ?></h4>
			<?php
			//new message using session
			if (isset($_SESSION['allocate_edit_msg'])) {
				switch ($_SESSION['allocate_edit_msg']) {
					case "save":
						echo "<p class='success'> Allocation saved.</p>";
						break;
					case "warn":
						echo "<p class='warn'> Allocation saved. Clashes detected.</p>";
						break;
					case "clear":
						echo "<p class='warn'> Allocation changes cleared.</p>";
						break;
				}
				unset($_SESSION['allocate_edit_msg']);
			}
			//old message using url
			//if (isset($_REQUEST['save']))
			//    echo "<p class='success'> Allocation saved.</p>";
			//if (isset($_REQUEST['warn']))
			//    echo "<p class='warn'> Allocation saved. Clashes detected.</p>";
			//if (isset($_REQUEST['clear']))
			//    echo "<p class='warn'> Allocation changes cleared.</p>";

			echo '';
			?>
            <p><a href="allocation.php" class="btn bg-dark text-white" style="font-size: 12px;"
                  title="< Back to Allocations">&#60;&#60; Back to Allocations</a></p>
            <form action="submit_allocate_edit.php" method="get">
				<?php $csrf->echoInputField(); ?>
                <input type="hidden" id="user_id" name="user_id" value="<?php echo $_SESSION['id']; ?>"/>
                <input type="hidden" id="project_id" name="project_id" value="<?php echo $projectID; ?>"/>
                <div id="timeslot_settings">
                    <table id="timeslot_table" border="0" width="360" style="text-align:left;">
                        <col width="150"/>
                        <col width="210"/>

                        <tr>
                            <td class="tdTitle">Supervisor:</td>
                            <td><?php
								if (array_key_exists($projData['staffid'], $staffList))
									echo $staffList[$projData['staffid']]->toString();
								else{
									echo $projData['staffid'];
								}
								?>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:5px;">Examiner:</td>
                            <td><?php generateStaffSelect('examiner', $projResult['examiner_id']); ?></td>
                        </tr>

						<?php if ($hasTimeslot) { ?>
                            <tr>
                                <td class="tdTitle">Examination Day:</td>
                                <td><?php generateDaySelect('exam_day', $projResult['day']); ?></td>
                            </tr>

                            <tr>
                                <td class="tdTitle">Examination Time:</td>
                                <td><?php generateTimeSelect('exam_slot', $projResult['slot'], $projResult['day']); ?></td>
                            </tr>

                            <tr>
                                <td class="tdTitle">Examination Room:</td>
                                <td><?php generateRoomSelect('exam_room', $projResult['room'], $projResult['day']); ?></td>
                            </tr>
						<?php } ?>
                    </table>
                </div>

                <div style="float:right; padding-top:25px;">
                    <input type="submit" title="Save all changes" value="Save Changes" class="btn bg-dark text-white"
                           style="font-size:12px !important;"/>
                </div>
            </form>
        </div>
    <br/>
	<?php } ?>

    </div>

    <!-- InstanceEndEditable -->
    <script type="text/javascript">
        $('#exam_day').change(function () {
            regenerateRoomSelect(this.value);
            regenerateTimeSelect(this.value);
        });
    </script>

    <!-- closing navigation div in nav.php -->
</div>

<?php
require_once('../../../footer.php');
$conn_db_ntu = null; ?>
</body>
</html>
