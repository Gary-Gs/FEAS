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
	
</head>

<body style="background: url('../images/background2.png'); background-size: 100% 100%;">
	<?php require_once('../php_css/headerwonav.php');?>
	
	</div>
	<br/><br/>
	<div class="container" style="min-height: 750px;">
			<div class="container col-sm-8 col-md-8">
				<div class="text-center">
					<h3>Welcome to Staff Preference Module</h3>
				    <h4>Please choose the following:</h4>
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
					
			</div>
	</div>
	
	<?php require_once('../footer.php'); ?>
</body>
</html>