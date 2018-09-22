<?php
   session_start();
   // users who are able to access all modules
   $verifiedUsers=["asfli", "sguo005", "audr0012", "jwong063", "lees0169", "ngxu0008"];
      
   if (isset ($_SESSION['login'])){
	   if (in_array($username, $verifiedUsers)) {
		header("location: index.php");
	   }
	   else {
		   header("location: pref/nav.php");
	 }
	   exit;
   }     

   if(isset($_POST['username']) && isset($_POST['pwd'])&& isset($_POST['domain'])){
		
		$domain =$_POST['domain'];
		$username = $_POST['username'];
		$password = $_POST['pwd'];
		if ($domain == "Student") {
			
			$ldaphost = "student10.student.main.ntu.edu.sg";  
			$ldaprdn = 'student' . "\\" . $username;
			$dn = "DC=student,DC=main,DC=ntu,DC=edu,DC=sg";
		}
		else if ($domain == "Staff") {
			$ldaphost = "staff10.staff.main.ntu.edu.sg";  
			$ldaprdn = 'staff' . "\\" . $username;
			$dn = "DC=staff,DC=main,DC=ntu,DC=edu,DC=sg";  
		}
		
       
		$ldapport = 389;                
		$ldap = ldap_connect($ldaphost, $ldapport)
        or die("Could not connect to $ldaphost");
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
	

        $bind = @ldap_bind($ldap, $ldaprdn, $password);
			
	    if ($bind) {
		
			$filter="(SAMAccountName=$username)";
       
			$searchResult=ldap_search($ldap, $dn, $filter);
			if ($searchResult && ($domain!="Student" || in_array(strtolower($username), $verifiedUsers))) {
				$info = ldap_get_entries($ldap, $searchResult);
		  
				$displayname = $info[0]["displayname"][0];
				session_regenerate_id(true);
				$_SESSION['id'] = $username;
				$_SESSION['displayname'] = $displayname;
				$_SESSION['login']  = "valid";
				$_SESSION['success'] = "You have logged in successfully!";
				$_SESSION['loginTime'] = time();


                if (in_array($username, $verifiedUsers)) {
					header("location: index.php");
				}
				else {
					header("location: pref/nav.php");
				}
				
				exit;
				@ldap_close($ldap);
			}
            echo '<script language="javascript">';
            echo 'alert("Access Denied.")';
            echo '</script>';
		} 
		else {
			 $loginError = "Your username/password is invalid!";	
		}
}	  
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php require_once('head.php'); ?>	
	<title>Login</title>

</head>

<body>
<div id="bar"></div>
	<div id="wrapper">
		<div id="header"></div>
		<div id="left">
			
		</div>
	
	<div id="content">
	<?php 
			if (isset ($loginError)) {
				    echo "<p class='warn'>[Login] ". $loginError ."</p>";	
			}?>
			<h1>Login Page for FYP Examiner Allocation System</h1>
			<br/>
			<br/>
			
	 <form class="form-horizontal" role="form" method="POST" action="login.php">
	   <label for="userName" class="control-label">UserName:</label>
	 <input id="userName" type="text" class="form-control" name="username" value="" required>
	 <br/><br/>
	 <label for="password" class="control-label">Password:</label>
	 <input id="passWord" type="password" name="pwd"  class="form-control" required autocomplete="off" > 
	 <br/><br/>
	 <label for="domain" class="control-label">Domain:</label>
	  <select name="domain">
		<option value="Student">Student</option>
		<option value="Staff">Staff</option>
  
	  </select> 
		
		<br/><br/>
	  <button type="submit" class="bt">Login</button>
	  <br/><br/>
	
	 </form>
	 <br/><br/>
</div>
</div>
</body>
</html>
	 