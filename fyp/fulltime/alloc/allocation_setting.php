<?php require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');
require_once('../../../restriction.php'); ?>
<?php
/* this code is for restricting access, can uncomment it later */
/*
if($_SESSION['index'] == "true"){

}
else{
	echo '<a href="../../../logout.php">Go back to logout page</a></br>';
	exit('You do not have access to this page');
}*/
$csrf = new CSRFProtection();
/* Prevent XSS input */
$_GET   = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
$MIN_ROOMS = 10;

$query_rsSettings = "SELECT * FROM " . $TABLES['allocation_settings_general'] . " as g";
// $query_rsRoom = "SELECT * FROM ".$TABLES['allocation_settings_room']." as r ORDER BY `id` ASC";
$query_rsRoom_day1 = "SELECT * FROM " . $TABLES['allocation_settings_room'] . " WHERE day = 1 ORDER BY `id` ASC";
$query_rsRoom_day2 = "SELECT * FROM " . $TABLES['allocation_settings_room'] . " WHERE day = 2 ORDER BY `id` ASC";
$query_rsRoom_day3 = "SELECT * FROM " . $TABLES['allocation_settings_room'] . " WHERE day = 3 ORDER BY `id` ASC";
// $query_numRoom = "SELECT count(*) as count FROM ".$TABLES['allocation_settings_room']." as r ORDER BY `id` ASC";
$query_projCount = "SELECT count(*) as count FROM " . $TABLES['fyp_assign'] . " WHERE complete = 0";
$query_otherSettings = "SELECT * FROM " . $TABLES['allocation_settings_others'] . " WHERE type = 'FT'";

try {
	$settings = $conn_db_ntu->query($query_rsSettings)->fetchall();
	// $rooms = $conn_db_ntu->query($query_rsRoom)->fetchall();
	$rooms_day1 = $conn_db_ntu->query($query_rsRoom_day1)->fetchall();
	$rooms_day2 = $conn_db_ntu->query($query_rsRoom_day2)->fetchall();
	$rooms_day3 = $conn_db_ntu->query($query_rsRoom_day3)->fetchall();
	$projCount = $conn_db_ntu->query($query_projCount)->fetch();
	// $numRoom_day1 = $conn_db_ntu->query($query_numRoom_day1)->fetch(); // day 1 no. of rooms
	// $numRoom_day2 = $conn_db_ntu->query($query_numRoom_day2)->fetch(); // day 2 no. of rooms
	// $numRoom_day3 = $conn_db_ntu->query($query_numRoom_day3)->fetch(); // day 3 no. of rooms
	$otherSettings = $conn_db_ntu->query($query_otherSettings)->fetch();
} catch (PDOException $e) {
	die($e->getMessage());
}
// $count_day1 = $numRoom_day1['count'];
// $count_day2 = $numRoom_day2['count'];
// $count_day3 = $numRoom_day3['count'];

if ($rooms_day1 != null || sizeof($rooms_day1) > 0) {
	$rooms_day1 = (array)json_decode($rooms_day1[0]["roomArray"]);
	$count_day1 = sizeof($rooms_day1);
} else {
	$count_day1 = 0;
}

if ($rooms_day2 != null || sizeof($rooms_day2) > 0) {
	$rooms_day2 = (array)json_decode($rooms_day2[0]["roomArray"]);
	$count_day2 = sizeof($rooms_day2);
} else {
	$count_day2 = 0;
}

if ($rooms_day3 != null || sizeof($rooms_day3) > 0) {
	$rooms_day3 = (array)json_decode($rooms_day3[0]["roomArray"]);
	$count_day3 = sizeof($rooms_day3);
} else {
	$count_day3 = 0;
}

// Exam Year & Sem settings
$YEAR_RANGE = 505;
$today = new DateTime();
$curWorkYear = $today->format('Y') % 100 - 1;

$examYearValue = $curWorkYear * 100 + ($curWorkYear + 1); // Default Year
$examYearStart = $examYearValue - $YEAR_RANGE; // Current Year
$examYearEnd = $examYearValue + $YEAR_RANGE;

$examSemValue = 1; // Default Sem
$examSemStart = 1; // Range
$examSemEnd = 2;

/* Parse Settings */
try {
	// Exam Sem, Exam Days and No of Days are the same regardless of how many entries
	// $NO_OF_DAYS = $settings[0]['alloc_days'];
	if ($otherSettings || sizeof($otherSettings) > 0) {
		$NO_OF_DAYS = $otherSettings ['alloc_days'];
	} else {
		$NO_OF_DAYS = 0;
	}

	// $settings_examyear = $settings[0]['exam_year'];
	$settings_examyear = $otherSettings['exam_year'];
	if ($settings_examyear >= $examYearStart && $settings_examyear <= $examYearEnd) { // In Range Year
		$examYearValue = $settings_examyear;
	}

	// $settings_examsem = $settings[0]['exam_sem'];
	$settings_examsem = $otherSettings['exam_sem'];
	if ($settings_examsem >= $examSemStart && $settings_examsem <= $examSemEnd) { // In Range Sem
		$examSemValue = $settings_examsem;
	}
} catch (Exception $e) {
	die($e->getMessage());
}

$proj_to_assign = $projCount['count'];

// $roomCount = max($count, $MIN_ROOMS);
$roomCount_day1 = max($count_day1, $MIN_ROOMS);
$roomCount_day2 = max($count_day2, $MIN_ROOMS);
$roomCount_day3 = max($count_day3, $MIN_ROOMS);

function yearToStr($yearInput) {
	$yr1 = round($yearInput / 100, 0, PHP_ROUND_HALF_DOWN);
	$yr2 = $yearInput % 100;
	return $yr1 . '/' . $yr2;
}

function generateYearSelect($id, $selected) {
	global $examYearStart, $examYearEnd;

	echo '<select id = "' . $id . '" name = "' . $id . '">';
	for ($curYear = $examYearStart; $curYear <= $examYearEnd; $curYear += 101) {
		$isSelected = ($curYear == $selected) ? "selected" : "";
		echo '<option value = "' . $curYear . '"' . $isSelected . '>' . yearToStr($curYear) . '</option>';
	}
	echo '</select>';
}

function generateSemSelect($id, $selected) {
	global $examSemStart, $examSemEnd;

	echo '<select id = "' . $id . '" name = "' . $id . '">';
	for ($curSem = $examSemStart; $curSem <= $examSemEnd; $curSem++) {
		$isSelected = ($curSem == $selected) ? " selected" : "";
		echo '<option value = "' . $curSem . '"' . $isSelected . '>' . $curSem . '</option>';
	}
	echo '</select>';
}

function generateTimeSelect($id, $i, $start, $end, $interval, $selected) {
	$start_time = DateTime::createFromFormat('H:i:s', $start);
	$end_time = DateTime::createFromFormat('H:i:s', $end);
	$time_interval = new DateInterval('PT' . $interval . 'M');
	$timeSelectStr = "";
	$timeSelectStr .= '<select id = "' . $id . ($i + 1) . '" name = "' . $id . '[]" onChange = "checkTime(\'' . $id . '\',' . ($i + 1) . ')">';

	for ($curTime = $start_time; $curTime <= $end_time; $curTime->add($time_interval)) {
		$isSelected = ($curTime == $selected) ? " selected" : "";
		$timeSelectStr .= '<option value = "' . $curTime->format('H:i:s') . '"' . $isSelected . ' >' . $curTime->format('H:i') . '</option>';
	}
	$timeSelectStr .= '</select>';
	return $timeSelectStr;
}

function generateDurationSelect($id, $i, $start, $end, $interval, $selected) {
	$start_time = DateTime::createFromFormat('i', $start);
	$end_time = DateTime::createFromFormat('i', $end);
	$time_interval = new DateInterval('PT' . $interval . 'M');
	$durationSelectStr = "";
	$durationSelectStr .= '<select id = "' . $id . ($i + 1) . '" name = "' . $id . '[]">';
	for ($curTime = $start_time; $curTime <= $end_time; $curTime->add($time_interval)) {
		$isSelected = ($curTime->format('i') == $selected) ? " selected" : "";
		$durationSelectStr .= '<option value = "' . $curTime->format('i') . '"' . $isSelected . '>' . $curTime->format('i') . ' Minutes</option>';
	}
	$durationSelectStr .= '</select>';
	return $durationSelectStr;
}

if (isset ($_REQUEST["no_of_days_room"])) {
	$noOfDays = $_REQUEST["no_of_days_room"];
	initRoomTable($noOfDays);
	return;
}

if (isset ($_REQUEST["no_of_days_timeslot"])) {
	$noOfDays = $_REQUEST["no_of_days_timeslot"];
	generateTSTable($noOfDays);
	return;
}

function generateAllocDate($id, $i) {
	global $settings;
	$formattedStartDate = "";
	if (sizeof($settings) > 0) {
		if ($i < sizeof($settings)) {
			$startDate = DateTime::createFromFormat('Y-m-d', $settings[$i]['alloc_date']);
		} else {
			$startDate = new DateTime();
		}
		$formattedStartDate = $startDate->format('Y-m-d');
	}
	$allocDateInputStr = '<input type = "text" id = "' . $id . ($i + 1) . '" name = "' . $id . '[]" value = "' . $formattedStartDate . '" required />';
	return $allocDateInputStr;
}

function generateTSTable($noOfDays) {
	global $settings;
	$timeSlotTableStr = "";
	$chkStr = "";

	for ($i = 0; $i < $noOfDays; $i++) {

		$actualDay = $i + 1;
		if ($i < sizeof($settings)) {
			$startDate = DateTime::createFromFormat('Y-m-d', $settings[$i]['alloc_date']);
			$startTime = DateTime::createFromFormat('H:i:s', $settings[$i]['alloc_start']);
			$endTime = DateTime::createFromFormat('H:i:s', $settings[$i]['alloc_end']);
			$timeslotDuration = new DateInterval('PT' . $settings[$i]['alloc_duration'] . 'M');
			if ($settings[$i]['opt_out'] == 1) {
				$chkStr = "checked";
			} else {
				$chkStr = "";
			}

		} else {
			$startDate = new DateTime();
			$startTime = DateTime::createFromFormat('H:i:s', '08:30:00');
			$endTime = DateTime::createFromFormat('H:i:s', '17:30:00');
			$timeslotDuration = new DateInterval('PT30M');

		}
		$duration = $timeslotDuration->format('%i');
		$id_name = "tab-" . $actualDay;

		$timeSlotTableStr .= '';
		$class = "tab-content";
		if ($i == 0) {
			$class .= " current";
		}
		$timeSlotTableStr .= '<tbody id = "' . $id_name . '" class = "' . $class . '">';

		$timeSlotTableStr .= '<tr><td style = "padding:5px;">Date:</td>';
		$allocDaysID = "alloc_days";
		$timeSlotTableStr .= '<td>' . generateAllocDate($allocDaysID, $i) . '</td></tr>';

		$timeSlotTableStr .= '<tr><td style = "padding:5px;">Start Time:</td>';
		$startTimeID = "start_time";
		$timeSlotTableStr .= '<td>' . generateTimeSelect($startTimeID, $i, '08:30:00', '17:00:00', '30', $startTime) . '</td></tr>';

		$timeSlotTableStr .= '<tr><td style = "padding:5px;">End Time:</td>';
		$endTimeID = "end_time";
		$timeSlotTableStr .= '<td>' . generateTimeSelect($endTimeID, $i, '09:00:00', '17:30:00', '30', $endTime) . '</td></tr>';

		$timeSlotTableStr .= '<tr><td style = "padding:5px;">Timeslot Duration:</td>';
		$durationID = "duration";
		$timeSlotTableStr .= '<td>' . generateDurationSelect($durationID, $i, '20', '40', '10', $duration) . '</td></tr>';

		$timeSlotTableStr .= '</tbody>';
	}
	echo $timeSlotTableStr;

	echo "<script>allocate_datepicker($noOfDays)</script>";
}

function initRoomTable($noOfDays) {
	global $settings;
	$roomTableHTMLStr = "";
	$disabledStr = "";
	for ($i = 0; $i < $noOfDays; $i++) {
		$actualDay = $i + 1;
		if ($i == 0) {
			$roomTableHTMLStr .= '<div id = "day-' . $actualDay . '" class = "room-content current">';
		} else {
			$roomTableHTMLStr .= '<div id = "day-' . $actualDay . '" class = "room-content">';
		}
		if ($i < sizeof($settings)) {
			if ($settings[$i]['opt_out'] == 1) {
				$disabledStr = "disabled";
			} else {
				$disabledStr = "";
			}
		}
		$roomTableHTMLStr .= '<table id = "room_table' . $actualDay . '" border = "0" style = "text-align:left;">';
		$roomTableHTMLStr .= '<col width = "30"/>';
		$roomTableHTMLStr .= '<col width = "380"/>';
		$roomTableHTMLStr .= initRooms($i, $noOfDays);
		$roomTableHTMLStr .= '</table><input id = "addRoomBtn' . $actualDay . '"';
		$roomTableHTMLStr .= 'type = "button" class = "bt" title = "Add more rooms" value = "Add Rooms"' . $disabledStr;
		$roomTableHTMLStr .= '/></div>';
	}
	echo $roomTableHTMLStr;
}

function initRooms($dayIndex, $noOfDays) {
	// global $rooms;
	global $MIN_ROOMS, $settings, $TABLES, $conn_db_ntu;
	$actualDay = $dayIndex + 1;
	$query_rsRoom = "SELECT * FROM " . $TABLES['allocation_settings_room'] . " WHERE day = ? ORDER BY `id` ASC";

	$stmt = $conn_db_ntu->prepare($query_rsRoom);
	$stmt->bindParam(1, $actualDay);
	$stmt->execute();
	$rooms_day = $stmt->fetch();


	if ($rooms_day && sizeof($rooms_day) > 0) {
		$rooms_day = (array)json_decode($rooms_day["roomArray"]);
		$rmcount_day = sizeof($rooms_day);
	} else {
		$rmcount_day = 0;
	}

	$roomCount = 1;
	$roomContentStr = "";
	$disabledStr = "";
	for ($i = 1; $i <= (($rmcount_day > $MIN_ROOMS) ? $rmcount_day : $MIN_ROOMS); $i++) {
		$roomContentStr .= '<tr><td class = "room_td">' . $roomCount . '.</td>';
//		if (sizeof($settings) >= $noOfDays) {
//			if ($settings[$dayIndex]['opt_out'] == 1) {
//				$disabledStr = "disabled";
//				$roomContentStr .= '<td class = "room_td"><input style = "width:200px; background:#ededed;" id = "room' . $actualDay . '_' . $roomCount . '" name = "room' . $actualDay . '_' . $roomCount . '" value = "' . $rooms_day[$i] . '"readonly = "readonly" />';
//			} else {
//				$disabledStr = "";
//			}
//		} else {
//			$roomContentStr .= '<td class = "room_td"><input style = "width:200px;" id = "room' . $actualDay . '_' . $roomCount . '" name = "room' . $actualDay . '_' . $roomCount . '" value = "' . $rooms_day[$i] . '" />';
//		}
		$roomContentStr .= '<td class = "room_td"><input type = "text" style = "width:200px;" //id = "room' . $actualDay . '_' . $roomCount . '" name = "room' . $actualDay . '_' . $roomCount . '" value = "';
		if (!empty($rooms_day[$i])) {
			$roomContentStr .= $rooms_day[$i];
		}
		$roomContentStr .= '" ' . $disabledStr . '/></td></tr>';
		$roomCount++;
	}

//	// Fill Gaps
//	while ($roomCount <= $MIN_ROOMS) { // min rooms = 10, if less than 10 then fill in with empty text boxes until 10
//		$roomContentStr .= '<tr><td class = \"room_td\">' . $roomCount . '.</td>';
//		if (sizeof($settings) >= $noOfDays) {
//			if ($settings[$dayIndex]['opt_out'] == 1) {
//				$disabledStr = "disabled";
//				$roomContentStr .= '<td class = "room_td"><input style = "width:200px; background:#ededed;" id = "room' . $actualDay . '_' . $roomCount . '" name = "room' . $actualDay . '_' . $roomCount . '" readonly = "readonly" />';
//			} else {
//				$disabledStr = "";
//				$roomContentStr .= '<td class = "room_td"><input style = "width:200px;" id = "room' . $actualDay . '_' . $roomCount . '" name = "room' . $actualDay . '_' . $roomCount . '" />';
//			}
//		}
//		$roomContentStr .= '<td class = "room_td"><input type = "text" style = "width:200px;" //id = "room' . $actualDay . '_' . $roomCount . '" name = "room' . $actualDay . '_' . $roomCount . '" ' . $disabledStr . '/></td></tr>';
//		$roomCount++;
//	}
	return $roomContentStr;
}

function enoughSlots() {
	global $proj_to_assign;
	// return $proj_to_assign;
	return true;
} ?>

    <!DOCTYPE html>
    <html lang="en" xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Allocation Settings</title>
		<?php require_once('../../../head.php'); ?>
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
        <style>
            td .room_td {
                padding-bottom: 10px;
            }

            tbody {
                width: 350px;
                height: 200px;
                margin: 0 auto;
            }

            ul.tabs {
                margin: 0px;
                padding: 0px;
                list-style: none;
            }

            ul.tabs li {
                background: none;
                color: #444;
                display: inline-block;
                padding: 10px 15px;
                cursor: pointer;
            }

            ul.tabs li.current {
                background: #ededed;
                color: #444;
            }

            .tab-content {
                display: none;
                background: #ededed;
                padding: 15px;
            }

            .tab-content.current {
                display: inherit;
            }

            ul.room_tabs {
                margin: 0px;
                padding: 0px;
                list-style: none;
            }

            ul.room_tabs li {
                background: none;
                color: #222;
                display: inline-block;
                padding: 10px 15px;
                cursor: pointer;
            }

            ul.room_tabs li.current {
                background: #ededed;
                color: #222;
            }

            .room-content {
                display: none;
                background: #ededed;
                padding: 15px;
            }

            .room-content.current {
                display: inherit;
            }

            table tr {
                height: 40px;
            }

            #settings_table {
                width: 100%;
            }

						td{
						  display:block;
							width:auto;
						}

						@media only screen and (min-width: 70em) {
						  td{
							display:table-cell;
							margin-bottom:0px;
						  }
						}
        </style>

        <script type="text/javascript">
            // global variables to keep track of no of rooms(textboxes) for each day
            var roomCount_Day1, roomCount_Day2, roomCount_Day3;

            $(document).ready(function () {
                roomCount_Day1 = <?php echo $roomCount_day1; ?>;
                roomCount_Day2 = <?php echo $roomCount_day2; ?>;
                roomCount_Day3 = <?php echo $roomCount_day3; ?>;

                var no_of_days = <?php echo $NO_OF_DAYS; ?>;
                generateTabs(no_of_days);
            });

            function allocate_datepicker($noOfDays) {
                for (var day = 1; day <= $noOfDays; day++) {
                    $("#alloc_days" + day).datepicker({
                        dateFormat: "yy-mm-dd",
                    });
                }

								$("#alloc_days1").datepicker("option", "onSelect", function (dateText, inst) {
								var count = 2;
								while (count <= $noOfDays) {
										var date = $('#alloc_days' + (count-1)).datepicker('getDate');
										date.setDate(date.getDate() + 1);

										while (date.getDay() == 0 || date.getDay() == 6) {
											date.setDate(date.getDate() + 1);
										}
										$('#alloc_days' + count).datepicker('setDate', date);
										count++;
								}
						});

						$("#alloc_days2").datepicker("option", "onSelect", function (dateText, inst) {
								var count = 3;
								while (count <= $noOfDays) {
										var date = $('#alloc_days' + (count-1)).datepicker('getDate');
										date.setDate(date.getDate() + 1);

										while (date.getDay() == 0 || date.getDay() == 6) {
											date.setDate(date.getDate() + 1);
										}
										$('#alloc_days' + count).datepicker('setDate', date);
										count++;
								}
						});
          }

            // function calculateNextDate() {
            //     var start_date = $("#alloc_date").val();
            //
            //     var date = new Date(start_date);
            //     var day_1 = new Date(date.setDate(date.getDate()));
            //     var day_2 = new Date(date.setDate(date.getDate() + 1));
            //     var day_3 = new Date(date.setDate(date.getDate() + 1.5));
            //
            //     var day1_string = day_1.getDate() + '/' + (day_1.getMonth() + 1) + '/' + day_1.getUTCFullYear();
            //     var day2_string = day_2.getDate() + '/' + (day_2.getMonth() + 1) + '/' + day_2.getUTCFullYear();
            //     var day3_string = day_3.getDate() + '/' + (day_3.getMonth() + 1) + '/' + day_3.getUTCFullYear();
            //
            //     $("#next_date0").html(day1_string);
            //     $("#next_date1").html(day2_string);
            //     $("#next_date2").html(day3_string);
            //
            //     var day1_value = day_1.getUTCFullYear() + '-' + (day_1.getMonth() + 1) + '-' + day_1.getDate();
            //     var day2_value = day_2.getUTCFullYear() + '-' + (day_2.getMonth() + 1) + '-' + day_2.getDate();
            //     var day3_value = day_3.getUTCFullYear() + '-' + (day_3.getMonth() + 1) + '-' + day_3.getDate();
            //
            //     $("#day0").val(day1_value);
            //     $("#day1").val(day2_value);
            //     $("#day2").val(day3_value);
            // }
            //
            // $(function () {
            //     $("#alloc_date").on("change", function () {
            //         calculateNextDate();
            //     });
            // })

            $(function () { // tabs function for both time alloc and room
                // when you click the ul for timeslot
                $('ul.tabs').on("click", "li", function () {

                    var tab_id = $(this).attr('data-tab'),
                        day;

                    // remove current from ul and div for tabs
                    $('ul.tabs li').removeClass('current');
                    $('.tab-content').removeClass('current');

                    // remove current from ul and div for rooms
                    $('ul.room_tabs li').removeClass('current');
                    $('.room-content').removeClass('current');

                    switch (tab_id) {
                        case 'tab-1':
                            day = 1
                            break;
                        case 'tab-2':
                            day = 2;
                            break;
                        case 'tab-3':
                            day = 3;
                            break;
                    }

                    // for the room side - assign current
                    $("#room_day" + day).addClass('current');
                    $("#day-" + day).addClass('current');

                    // for the tabs side - assign current
                    $(this).addClass('current');
                    $("#" + tab_id).addClass('current');
                });

                // when you click rooms setting tabs
                $('ul.room_tabs').on("click", "li", function () {
                    var room_id = $(this).attr('data-tab'),
                        day;

                    // remove current from ul and div for rooms
                    $('ul.room_tabs li').removeClass('current');
                    $('.room-content').removeClass('current');

                    // remove current from ul and div for tabs
                    $('ul.tabs li').removeClass('current');
                    $('.tab-content').removeClass('current');

                    switch (room_id) {
                        case 'day-1':
                            day = 1;
                            break;
                        case 'day-2':
                            day = 2;
                            break;
                        case 'day-3':
                            day = 3;
                            break;
                    }

                    // for the tabs side - assign current
                    $("#tab" + day).addClass('current');
                    $("#tab-" + day).addClass('current');

                    // for the room side - assign current
                    $(this).addClass('current');
                    $("#" + room_id).addClass('current');
                });
            });

            function generateTabs(noOfDays) {
                var roomTableTabHTMLStr = "";
                var timeSlotTabHTMLStr = "";
                for (var i = 1; i <= noOfDays; i++) {
                    if (i == 1) {
                        roomTableTabHTMLStr += "<li data-tab = \"day-" + i + "\" class = \"room-link current\" id = \"room_day" + i + "\"><b>Day " + i + "</b></li>";
                        timeSlotTabHTMLStr += "<li data-tab = \"tab-" + i + "\" class = \"tab-link current\" id = \"tab" + i + "\"><b>Day " + i + "</b></li>";
                    } else {
                        roomTableTabHTMLStr += "<li data-tab = \"day-" + i + "\" class = \"room-link\" id = \"room_day" + i + "\"><b>Day " + i + "</b></li>";
                        timeSlotTabHTMLStr += "<li data-tab = \"tab-" + i + "\" class = \"tab-link\" id = \"tab" + i + "\"><b>Day " + i + "</b></li>";
                    }
                }
                $("#roomTabs").html(roomTableTabHTMLStr);
                $("#timeSlotTabs").html(timeSlotTabHTMLStr);
            }

            function regenerateRoomTable(no_of_days) {
                var dataArr = {"no_of_days_room": no_of_days};
                $.ajax({
                    type: "GET",
                    url: "allocation_setting.php",
                    data: dataArr,
                    success: function (data) {
                        $("#roomTableGroup").html("");
                        $("#roomTableGroup").html(data);
                    },
                    error: function (msg) {
                        alert("error occurred");
                    }
                });
            }

            function regenerateTimeSlotTable(no_of_days) {
                var dataArr = {"no_of_days_timeslot": no_of_days};
                $.ajax({
                    type: "GET",
                    url: "allocation_setting.php",
                    data: dataArr,
                    success: function (data) {
                        $("#tsSettingsBody").html("");
                        $("#tsSettingsBody").html(data);
                    },
                    error: function (data) {
                        alert("error occurred");
                    }
                });
            }
        </script>
    </head>
    <body>
	<?php require_once('../../../php_css/headerwnav.php'); ?>

    <div style="margin-left: -15px;">
        <div class="container-fluid">
			<?php require_once('../../nav.php'); ?>
            <!-- Page Content Holder -->
            <div class="container-fluid">
                <h3>Allocation Settings for Full Time Projects</h3>

				<?php
				if (!enoughSlots()) {
					echo "<p class = 'warn'> Your current settings do not provide sufficient rooms/slots for timetable allocation!</p>";
				}

				//new message using session
				if (isset($_SESSION['allocate_setting_msg'])) {
					switch ($_SESSION['allocate_setting_msg']) {
						case "save":
							echo "<p class = 'success'> Allocation settings saved.</p>";
							break;
						case "clear":
							echo "<p class = 'warn'> Allocation settings changes cleared.</p>";
							break;
						case "reset":
							echo "<p class = 'warn'> Allocation settings reset to default.</p>";
							break;
					}
					unset($_SESSION['allocate_setting_msg']);
				}
				//old message using url
				//if (isset($_REQUEST['save'])) {
				//    echo "<p class = 'success'> Allocation settings saved.</p>";
				//}
				//if (isset($_REQUEST['clear'])) {
				//    echo "<p class = 'warn'> Allocation settings changes cleared.</p>";
				//}
				//if (isset($_REQUEST['reset'])) {
				//    echo "<p class = 'warn'> Allocation settings reset to default.</p>";
				//}

				if (isset ($_REQUEST['validate'])) {
					echo "<p class = 'warn'> CSRF validation failed.</p>";
				} else { ?>
                    <div id="topcon" class="table-responsive">
                    <form action="submit_saveas.php" method="post">
										<?php $csrf->echoInputField(); ?>
                            <table id="settings_table" border="0" style="margin-top:15px;">
                                <tr>
                                    <td valign="top" style="text-align:left;">
                                        <div id="exam_settings">
                                            <u><h4 style="padding-bottom:10px;">Exam Settings</h4></u>
                                            <table id="examsettings_table" border="0" width="406"
                                                   style="background-color: #ededed; text-align:left;">
                                                <col width="110"/>
                                                <col width="220"/>
                                                <tr>
                                                    <td style="padding:5px;">Exam Year:</td>
                                                    <td><?php generateYearSelect('exam_year', $examYearValue); ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:5px;">Exam Sem:</td>
                                                    <td><?php generateSemSelect('exam_sem', $examSemValue); ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:5px;">Number of Days:</td>
                                                    <td>
                                                        <input type="number" id="number_of_days" name="number_of_days"
                                                               min="1" max="3" value="<?php echo $NO_OF_DAYS; ?>"
                                                               required/><br/>
                                                        <span id="dayErrorMsg" class="errorMsg"></span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                    <td valign="top" style="text-align:left;" rowspan="2">
                                        <u><h4 style="padding-bottom:10px;">Room Settings</h4></u>
                                        <ul id="roomTabs" class="room_tabs"></ul>
                                        <div id="roomTableGroup">
											<?php initRoomTable($NO_OF_DAYS) ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" style="text-align:left;">
                                        <div id="timeslot_settings">
                                            <u><h4 style="padding-bottom:10px;">Timeslot Settings</h4></u>
                                            <table id="timeslot_table" border="0" width="406" style="text-align:left;">
                                                <col width="110"/>
                                                <col width="220"/>
                                                <!--<tr>-->
                                                <!--<td style = "padding:5px;">Allocation<br> Open Date:</td>-->
                                                <!---->
                                                <!--<td>--><?php // generateAllocDate() ?><!--</td>-->
                                                <!--</tr>-->
                                                <!--<tr>-->
                                                <!--<td style = "padding:5px;">Allocation<br> Close Date:</td>-->
                                                <!---->
                                                <!--<td>pending</td>-->
                                                <!--</tr>-->
                                                <tr>
                                                    <td colspan="2">
                                                        <ul id="timeSlotTabs" class="tabs"></ul>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <table id="tsSettingsBody">
															<?php generateTSTable($NO_OF_DAYS); ?>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                            <label><input type="checkbox" name="apply_to_all[]"/> Apply to all</label>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <div style="float:right; padding-top:25px;">
                                <a href="submit_resetas.php" class="btn bg-dark text-white" title="Reset to default" style="width:130px;font-size:12px;" onclick="return confirm('Reset to Default?')">Reset to default</a>
                                <input type="submit" title="Save all changes" value="Save Changes"
                                       class="btn bg-dark text-white" style="font-size:12px !important;"/>
                                <br/><br/>
                            </div>
                        </form>
                    </div>
				<?php } ?>

                <script>
                    function addRoom_Day(roomCount, val, elementId, day) { // add rooms
                        var table = document.getElementById(elementId);

                        for (var i = 0; i < val; i++) {
                            var row = table.insertRow(table.rows.length),
                                td_index = row.insertCell(0),
                                td_field = row.insertCell(1);
                            roomCount++;
                            td_index.innerHTML = roomCount + ".";
                            td_index.className = 'room_td';

                            td_field.innerHTML = "<input style = \"width:200px;\" id = \"room" + day + "_" + roomCount + "\" name = \"room" + day + "_" + roomCount + "\">";
                            td_field.className = 'room_td';
                        }
                        return roomCount;
                    }

                    $("#number_of_days").change(function () {
                        $("#dayErrorMsg").html("");
                        if (this.value < 1 || this.value > 3) {
                            $("#dayErrorMsg").html("Please enter a valid number between 1 and 3!");
                        } else {
                            generateTabs(this.value);
                            regenerateRoomTable(this.value);
                            regenerateTimeSlotTable(this.value);
                            // reset room count for each day since the no of days change
                            roomCount_Day1 = <?php echo $roomCount_day1; ?>;
                            roomCount_Day2 = <?php echo $roomCount_day2; ?>;
                            roomCount_Day3 = <?php echo $roomCount_day3; ?>;
                        }
                    });

                    $("#roomTableGroup").on("click", "#addRoomBtn1", function () {
                        roomCount_Day1 = addRoom_Day(roomCount_Day1, 5, "room_table1", 1);
                    });
                    $("#roomTableGroup").on("click", "#addRoomBtn2", function () {
                        roomCount_Day2 = addRoom_Day(roomCount_Day2, 5, "room_table2", 2);
                    });
                    $("#roomTableGroup").on("click", "#addRoomBtn3", function () {
                        roomCount_Day3 = addRoom_Day(roomCount_Day3, 5, "room_table3", 3);
                    });

                    function checkTime(id, day) {
                        var date_start = new Date("1-1-2018 " + $('#start_time' + day + ' option:selected').val()),
                            date_end = new Date("1-1-2018 " + $('#end_time' + day + ' option:selected').val());

                        if (date_start >= date_end) {
                            if (id == "start_time")
                                $("#" + id + +day).val('08:30:00');
                            else
                                $("#" + id + +day).val('17:30:00');
                            alert("Start Time must be before End Time.");
                        }
                    }
                </script>
            </div>
            <!-- closing navigation div in nav.php -->
        </div>
    </div>
    <!-- InstanceEndEditable -->
	<?php require_once('../../../footer.php'); ?>
    </body>
    </html>
<?php
$conn_db_ntu = null;
unset($settings);
unset($rooms);
?>
