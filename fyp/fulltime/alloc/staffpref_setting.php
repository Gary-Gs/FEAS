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
	<!-- for datepicker -->
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://jqueryui.com/resources/demos/style.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- end of datepicker -->

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
	<?php require_once('../../../php_css/headerwnav.php');?>

	<div style="margin-left: -15px;">
		<div class="container-fluid">
			<?php require_once('../../nav.php'); ?>
			<div class="container-fluid">
				<form autocomplete="off" action="submit_savesp.php" method="post">
					<?php $csrf->echoInputField();?>
						<h3>Staff Preference Settings for Full Time Projects</h3>
						<?php if (isset($_SESSION['error'])) {
								if($_SESSION['error'] == 1){
									echo "<p class='warn'> Please enter a valid date for Start Date!</p>";
								}
								if($_SESSION['error'] == 2){
									echo "<p class='warn'> Please enter a valid date for End Date!</p>";
								}
								if($_SESSION['error'] == 3){
									echo "<p class='warn'> Start date cannot be greater than end date!</p>";
								}
								if($_SESSION['error'] == 4){
									echo "<p class='warn'> Start date and end date are not within in the same semester!</p>";
								}
								if($_SESSION['error'] == 5){
									echo "<p class='warn'> Start date and end date cannot be less than current date!</p>";
								}
								unset($_SESSION['error']);
							}
							?>
						<h4 style="padding-bottom:10px;">Open Period</h4>
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

								<input type="submit" title="Save all changes" value="Save Changes" class="btn bg-dark text-white text-center" style="font-size:12px !important;"/>
							</div>
				</form>
			</div>
			 <!-- closing navigation div in nav.php -->
	         </div>
		</div>
	</div>

	<?php require_once('../../../footer.php'); ?>
</body>
</html>

<?php
	unset($settings);
	unset($rooms);
?>
