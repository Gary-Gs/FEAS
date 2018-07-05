<?php require_once('../Connections/db_ntu.php');
      require_once('../Utility.php'); ?>

<?php
   
	try
	{
	
	$query_rsSettings = "SELECT * FROM ".$TABLES['allocation_settings_others']." WHERE type= 'FT'";
	
	$query_rsSettings_pt = "SELECT * FROM ".$TABLES['allocation_settings_others']." WHERE type= 'PT'";
	$settings 	= $conn_db_ntu->query($query_rsSettings)->fetch();
	$settings_pt 	= $conn_db_ntu->query($query_rsSettings_pt)->fetch();
	
	
	$setting_start_ft = $settings['pref_start'];
	$setting_end_ft = $settings['pref_end'];
	$setting_start_pt = $settings_pt['pref_start'];
	$setting_end_pt = $settings_pt['pref_end'];
	}
	catch (PDOException $e)
	{
		die($e->getMessage());
	}
	
	$pref_start_ft_temp = strtotime( $setting_start_ft);
	$pref_end_ft_temp = strtotime( $setting_end_ft);
	
	
	$pref_start_ft = date( 'Y/m/d', $pref_start_ft_temp );
	$pref_end_ft = date( 'Y/m/d', $pref_end_ft_temp );
	
	
	$pref_start_pt_temp = strtotime( $setting_start_pt);
	$pref_end_pt_temp = strtotime( $setting_end_pt);
	
	
	$pref_start_pt = date( 'Y/m/d', $pref_start_pt_temp );
	$pref_end_pt = date( 'Y/m/d', $pref_end_pt_temp );
	
	$today = date("Y/m/d"); 
	
	
?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Staff Preference</title>
     <?php require_once('../head.php'); ?>	
	
	<style>
	.container-fluid{
		background-color: #e1e1e1;
		max-width:390px;
		margin-right: auto;
		margin-left: auto; 
		float: center;
		text-align:left;
	}
	
	a {
		font-size : 20px; 
		text-decoration:none;
	}
	
	span{
		border-bottom: 2px solid;
	}
	</style>
</head>

<body>
	<div id="bar"></div>
	<div id="wrapper">
		
	
			<div id="logout">
				<a href="../../logout.php"><img src="../../images/logout.jpg" /></a>
			</div>
		<div id="header"></div>
	
			<div>
			
				<h1 style = "font-size: 30px; padding-top: 10px;">Welcome to Staff Preference Module</h1>
				<br>
				<h2 style = "font-size: 22px;">Please choose the following:</h2>
				<br>
				<div class = "container-fluid">
				<dt>
					<?php if($today <= $pref_end_ft && $today >= $pref_start_ft) { ?>
					<a href = "/pref/fulltime/staffpref_fulltime.php"><span>Full Time (System open period: 
					<?php 
					$startFTDT = date( 'd M Y', $pref_start_ft_temp );
					$endFTDT = date( 'd M Y', $pref_end_ft_temp );
					echo $startFTDT; 
					echo " - ".$endFTDT." )"; ?></span></a>
					<?php } else { ?>
					<a href = "/pref/staffpref_unavailable.php"><span>Full Time <?php echo "( System Not Available ) "; }?></span></a>
				</dt>
				</div>
				<br>
				<!--<div class = 'container-fluid'>
				<dt>
					<?php if($today <= $pref_end_pt && $today > $pref_start_pt) { ?>
					<a href = "/pref/parttime/staffpref_parttime.php"><span>Part Time (System open period: <?php
					$startPTDT = date( 'd M Y', $pref_start_pt_temp );
					$endPTDT = date( 'd M Y', $pref_end_pt_temp );
					echo $startPTDT; 
					echo " - ".$endPTDT." )"; ?></span></a>
					<?php } else { ?>
					<a href = "/pref/staffpref_unavailable.php"><span>Part Time <?php echo "( System Not Available ) "; }?></span></a>
				</dt>
				</div>-->
	
</body>
</html>