<?php require_once('../../../Connections/db_ntu.php'); 
	  require_once('../../../CSRFProtection.php');
	  require_once('./entity.php');
	  require_once('../../../Utility.php');?>

<?php
	
	$csrf = new CSRFProtection();
   
	
	$_REQUEST['csrf'] = $csrf->cfmRequest();
	
	$MIN_ENTRIES		= 10;
	
	$query_rsExceptions	  = "SELECT * FROM ".$TABLES['fea_settings_availability']." as a";
	$query_rsStaff	  	  = "SELECT s.id as staffid, s.name as staffname, s.position as salutation FROM ".$TABLES['staff']." as s ORDER BY staffname ASC";
	
	
	try
	{
		$staffs			= $conn_db_ntu->query($query_rsStaff);
		$exceptions		= $conn_db_ntu->query($query_rsExceptions)->fetchAll();
	}
	catch (PDOException $e)
	{
		die($e->getMessage());
	}
	
	$NO_OF_DAYS = 5;
	
	$exceptionCount	= max(count($exceptions), $MIN_ENTRIES)+1;
	
	//Staff
	$staffList = array();
	foreach($staffs as $staff) {
		$staffList[ $staff['staffid'] ] = new Staff($staff['staffid'],
													$staff['salutation'],
													$staff['staffname']);
	}
	
	function generateTimeSelect($id, $start, $end, $interval, $selected)
	{
		$start_time 	= DateTime::createFromFormat('H:i:s', $start);
		$end_time		= DateTime::createFromFormat('H:i:s', $end);
		$time_interval	= new DateInterval('PT'.$interval.'M');
		
		echo '<select id="'.$id.'" name="'.$id.'">';
		echo '<option value=""></option>';
		for ($curTime=$start_time; $curTime <= $end_time; $curTime->add($time_interval))
		{
			$isSelected = ($curTime == $selected) ? "selected" : "";
			echo '<option value="'.$curTime->format('H:i:s').'"'.$isSelected.'>'.$curTime->format('H:i').'</option>';
		}
		echo '</select>';
	}
	
	function generateStaffSelect($id, $selected)
	{
		global $staffList;
		echo '<select id="'.$id.'" name="'.$id.'">';
		echo '<option value=""></option>';
		
		if ($selected == "*")
			echo '<option value="*" selected>*</option>';
		else
			echo '<option value="*">*</option>';
		
		foreach ($staffList as $staff)
		{
			$isSelected = ($staff->getID() == $selected) ? "selected" : "";
			echo '<option value="'.$staff->getID().'"'.$isSelected.'>'.$staff->toString().'</option>';
		}
		echo '</select>';
	}
	
	function generateDaySelect($id, $selected)
	{
		global $NO_OF_DAYS;
		echo '<select id="'.$id.'" name="'.$id.'">';
		echo '<option value=""></option>';
		
		if ($selected == "*")
			echo '<option value="*" selected>*</option>';
		else
			echo '<option value="*">*</option>';
		
		for ($i=1; $i<=$NO_OF_DAYS; $i++)
		{
			$isSelected = ($i == $selected) ? "selected" : "";
			echo '<option value="'.$i.'"'.$isSelected.'>'.$i.'</option>';
		}
		echo '</select>';
	}
	
	function initEntries()
	{
		global $exceptions, $MIN_ENTRIES;
		
		$exceptionCount = 1;
		foreach($exceptions as $exception)
		{
			echo '<tr>';

				//Staff
				echo '<td class="exception_td">';
					generateStaffSelect('staff_'.$exceptionCount, $exception['staff_id']);
				echo '</td>';
				
				//Day
				echo '<td class="exception_td">';
					generateDaySelect('day_'.$exceptionCount, $exception['day']);
				echo '</td>';
				
				//Start Time
				echo '<td class="exception_td">';
					$startTime = DateTime::createFromFormat('H:i:s', $exception['time_start']);
					generateTimeSelect('timestart_'.$exceptionCount, '08:30:00', '17:30:00', '30', $startTime);
				echo '</td>';
				
				//End Time
				echo '<td class="exception_td">';
					$endTime = DateTime::createFromFormat('H:i:s', $exception['time_end']);
					generateTimeSelect('timeend_'.$exceptionCount, '08:30:00', '17:30:00', '30', $endTime);
				echo '</td>';
			
			echo '</tr>';
			
			$exceptionCount++;
		}
		
		//Fill Gaps
		while ($exceptionCount<=$MIN_ENTRIES)
		{
			echo '<tr>';

			//Staff
			echo '<td class="exception_td">';
				generateStaffSelect('staff_'.$exceptionCount, NULL);
			echo '</td>';
			
			//Day
			echo '<td class="exception_td">';
				generateDaySelect('day_'.$exceptionCount, NULL);
			echo '</td>';
			
			//Start Time
			echo '<td class="exception_td">';
				generateTimeSelect('timestart_'.$exceptionCount, '08:30:00', '17:30:00', '30', NULL);
			echo '</td>';
			
			//End Time
			echo '<td class="exception_td">';
				generateTimeSelect('timeend_'.$exceptionCount, '08:30:00', '17:30:00', '30', NULL);
			echo '</td>';
			
			echo '</tr>';
			
			$exceptionCount++;
		}
	}
	
	$conn_db_ntu = null;
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>TimeSlot Exceptions</title>
	<?php require_once('../../../head.php'); ?>
	<style>
		.exception_td
		{
			padding:5px;
		}
	</style>
	
	<script type="text/javascript">
		var exceptionCount;
		
		$(document).ready(function(){
			exceptionCount = <?php echo $exceptionCount; ?>;
			
		});
		
		function addException(val)
		{
			var table = document.getElementById("exception_table");
			
			for(var i=0;i<val;i++)
			{
				var row = table.insertRow(table.rows.length-1);
				var staff = row.insertCell(0);
				var day = row.insertCell(1);
				var starttime = row.insertCell(2);
				var endtime = row.insertCell(3);
				
				staff.innerHTML = '<?php generateStaffSelect('new_staff', NULL); ?>';
				staff.className = 'exception_td';
				document.getElementById("new_staff").name = 'staff_' + exceptionCount;
				document.getElementById("new_staff").id = 'staff_' + exceptionCount;
				
				day.innerHTML = '<?php generateDaySelect('new_day', NULL); ?>';
				day.className = 'exception_td';
				document.getElementById("new_day").name = 'day_' + exceptionCount;
				document.getElementById("new_day").id = 'day_' + exceptionCount;
				
				starttime.innerHTML = '<?php generateTimeSelect('new_start', '08:30:00', '17:30:00', '30', NULL); ?>';
				starttime.className = 'exception_td';
				document.getElementById("new_start").name = 'timestart_' + exceptionCount;
				document.getElementById("new_start").id = 'timestart_' + exceptionCount;
				
				endtime.innerHTML = '<?php generateTimeSelect('new_end', '08:30:00', '17:30:00', '30', NULL); ?>';
				endtime.className = 'exception_td';
				document.getElementById("new_end").name = 'timeend_' + exceptionCount;
				document.getElementById("new_end").id = 'timeend_' + exceptionCount;
				
				exceptionCount++;
			}
		}
	</script>
</head>

<body>
    <div id="bar"></div>
	<div id="wrapper">
		<div id="header"></div>
		
		<div id="left">
			<div id="nav">
				<?php require_once('../../nav.php'); ?>
			</div>
		</div>
		
		<div id="logout">
				<a href="../../../logout.php"><img src="../../../images/logout.jpg" /></a>
		</div>
		
		<!-- InstanceBeginEditable name="Content" -->
		<div id="content">
			<h1>Timeslot Exception for Full Time Projects</h1>
			<br/>
			<?php 
			if(isset($_REQUEST['save']))
				echo "<p class='success'> Timeslot exception settings saved.</p>";
			if(isset($_REQUEST['clear']))
				echo "<p class='warn'> Timeslot exceptions changes cleared.</p>";
			if(isset($_REQUEST['call']))
				echo "<p class='warn'> All timeslot exceptions cleared.</p>";
			if (isset($_REQUEST['validate']) || isset($_REQUEST['csrf'])) {
				    echo "<p class='warn'> CSRF validation failed.</p>";
			}
			else  {?>
			
			<div id="topcon">
				<form action="submit_savete.php" method="post">
				<?php $csrf->echoInputField();?>
					<table id="exception_table" border="1" cellpadding="0" cellspacing="0" width="100%">
						<col width="40%" />
						<col width="20%" />
						<col width="20%" />
						<col width="20%" />

						<tr class="heading">
							<td>Staff Name</td>
							<td>Day</td>
							<td>Start Time</td>
							<td>End Time</td>
						</tr>
					
						<?php initEntries(); ?>
						
						<tr>
							<td class="exception_td"></td>
							<td class="exception_td"></td>
							<td class="exception_td"></td>
							<td class="exception_td"><a onclick="javascript:addException(5);" class="bt" title="Add more exception"/>Add Exception</a></td>
						</tr>
					</table>
					 
					<div style="float:right; padding-top:25px;">
						
						<a href="timeslot_exception.php?clear=1" class="bt" title="Clear all changes">Clear Changes</a>
						<input type="submit" title="Save all changes" value="Save Changes" class="bt" style="font-size:12px !important;"/>
					</div>
				</form>
			</div>
			<?php }?>
			<br/>
		</div>
		<!-- InstanceEndEditable --> 
		
		<?php require_once('../../../footer.php'); ?>
	</div>
</body>
</html>

<?php
	unset($settings);
	unset($rooms);
?>
