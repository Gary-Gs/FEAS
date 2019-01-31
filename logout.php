<?php 
session_start();
session_destroy();

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php require_once('head.php'); ?>	
	<title>Logout</title>

</head>

<body style="background-image: url('images/The_Arc.jpg'); background-size: 100% 100%;">
<?php require_once('php_css/headerwologin.php');?>
		
	<div style="min-height: 87vh;">
		<div class="container-fluid text-center" style="background: white; opacity: 0.8; filter: alpha(opacity=80); margin-top: -8px;">
			
				 <?php if(isset($_GET["session_expired"])) {
				           echo "<h3>Session has timed out! Please login again! </h3>";
			           }else {?>
					   
					<h3> You have logged out successfully.</h3>
					<?php }?>
			         <h3>Click <a href="login.php">here</a> to return to the login page</h3>
		   
		 </div>
	 </div>
	 <?php require_once('footer.php'); ?>	
</body>
</html>