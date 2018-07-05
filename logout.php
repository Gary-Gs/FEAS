<?php 
session_start();
session_destroy();

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php require_once('head.php'); ?>	
	<title>Logout </title>

</head>

<body>
<div id="bar"></div>
	<div id="wrapper">
		<div id="header"></div>
		<div id="left">
			
		</div>
	
	<div id="content">
	 <?php if(isset($_GET["session_expired"])) {
	           echo "<h2>Session has timed out! Please login again! </h2>";
           }else {?>
			   
		   
			<h2> You have logged out successfully.</h2>
			<?php }?>
	         <h2>Click <a href="login.php">here</a> to return to the login page</h2>
	 </div>
</body>
</html>