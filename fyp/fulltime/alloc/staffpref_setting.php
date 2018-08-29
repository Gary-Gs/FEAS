<?php require_once('../../../Connections/db_ntu.php'); 
	  require_once('../../../CSRFProtection.php');
	  require_once('../../../Utility.php');?>

<?php

	$csrf = new CSRFProtection();
	
	
	//$query_rsSettings = "SELECT * FROM ".$TABLES['allocation_settings_general']." as g";
	//staff pref time period moved to fea_settings_others table 
	$query_rsSettings = "SELECT * FROM ".$TABLES['allocation_settings_others']." WHERE type= 'FT'";
	
	try
	{
		$settings 	= $conn_db_ntu->query($query_rsSettings)->fetch();
	}
	catch (PDOException $e)
	{
		die($e->getMessage());
	}
	
	/* Parse Settings */
	try
	{
		$startDate 		 	= DateTime::createFromFormat('Y-m-d H:i:s', $settings['pref_start']);
		$endDate 			= DateTime::createFromFormat('Y-m-d H:i:s', $settings['pref_end']);
	}
	catch(Exception $e)
	{
		$startDate = null;
		$endDate = null;
	}
	
	//Default Values
	if ($startDate == null)
		$startDate = new DateTime();
	
	if ($endDate == null)
		$endDate = new DateTime();
	
	$conn_db_ntu = null;
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Staff Pref Settings</title>
	<?php require_once('../../../head.php'); ?>
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
	<script type="text/javascript">		
		
   $(function() {
         $( "#start_date" ).datepicker({

			dateFormat: "yy-mm-dd",
			
         });
		  $( "#end_date" ).datepicker({ 

			dateFormat: "yy-mm-dd",
			
         });
    }); 
	 
	
	
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
			<h1>Staff Preference Settings for Full Time Projects</h1>
			<?php 
			if (isset($_REQUEST['error'])) {
				$error_no = $_REQUEST['error'];
				if($error_no == 1){
					echo "<p class='warn'> Please enter a valid date for Start Date!</p>";
				}
				if($error_no == 2){
					echo "<p class='warn'> Please enter a valid date for End Date!</p>";
				}
				if($error_no == 3){
					echo "<p class='warn'> Start date cannot be greater than end date!</p>";
				}
			}

			if(isset($_REQUEST['save']))
				echo "<p class='success'> Staff Preference settings saved.</p>";
			if(isset($_REQUEST['clear']))
				echo "<p class='warn'> Staff Preference settings changes cleared.</p>";
			if (isset($_REQUEST['validate'])) {
				    echo "<p class='warn'> CSRF validation failed.</p>";
			}
			else  {?>
			
			<div id="topcon">
				<form action="submit_savesp.php" method="post">
				<?php $csrf->echoInputField();?>
					<h3 style="padding-bottom:10px;">Open Period</h3>
					<table id="timeslot_table" border="0" width="300" style="text-align:left;">
						<col width="120" />
						<col width="180" />
						<tr>
							<td style="padding:5px;">Start Date:</td>
							<td><input type="text" id="start_date" name="start_date" value="<?php echo $startDate->format('Y-m-d'); ?>" required /></td>
						</tr>

						<tr>
							<td style="padding:5px;">End Date:</td>
							<td><input type="text" id="end_date" name="end_date" value="<?php echo $endDate->format('Y-m-d'); ?>" required  /></td>
						</tr>
					</table>
					
					<div style="float:left; padding-top:25px; padding-left:185px;">
						
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
