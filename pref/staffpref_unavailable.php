<?php
require_once('../Connections/db_ntu.php');
require_once('../Utility.php'); 
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	
	<title>Staff Preference Unavailable</title>
    <?php require_once('../head.php'); ?>	
	
</head>

<body style="background: url('../images/background2.png');">

	<?php require_once('../php_css/header.php');?> 
	<div class="float-right">
		<?php
		if (isset($_SESSION['success'])) {
				 //echo "<p class='success'>[Login] ".$_SESSION['success']."</p>";
				 unset ($_SESSION['success']);
				 }
				if (isset($_SESSION['displayname'])){
					$displayname = trim($_SESSION['displayname'], '#');
					echo "<p class='credentials' style='font-size: 15px;'>Welcome, ".$displayname. "<a href='../logout.php' title='Logout'><img src='../images/logout1.png' width='25px' height='25px' alt='Logout'/></a></p>"; 
				}
		?>

	</div>
	<br /><br />
	<div class="container-fluid text-center" style="min-height: 790px;">
		<h4>Sorry, The staff preference module is not opened yet!</h4>
	</div>

	<?php require_once('../footer.php'); ?>
	
</body>
</html>