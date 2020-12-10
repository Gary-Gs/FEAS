
<?php
 function requestedByTheSameDomain() {
    $myDomain       = $_SERVER['155.69.100.32'];
    $requestsSource = $_SERVER['HTTP_REFERER'];

    return parse_url($myDomain, PHP_URL_HOST) === parse_url($requestsSource, PHP_URL_HOST);
    }
?>

<?php

$key = "w!5C'zUT.<-FG76";

$localHostDomain = 'http://localhost';
$ServerDomainHTTP = 'http://155.69.100.32';
$ServerDomainHTTPS = 'https://155.69.100.32';
$ServerDomain = 'https://fypexam.scse.ntu.edu.sg';
if(isset($_SERVER['HTTP_REFERER'])) {
  try {
      // If referer is correct
      if ((strpos($_SERVER['HTTP_REFERER'], $localHostDomain) !== false) || (strpos($_SERVER['HTTP_REFERER'], $ServerDomainHTTP) !== false) || (strpos($_SERVER['HTTP_REFERER'], $ServerDomainHTTPS) !== false) || (strpos($_SERVER['HTTP_REFERER'], $ServerDomain) !== false)) {
      }
      else {
          throw new Exception($_SERVER['Invalid Referer']);
      }
  }
  catch (Exception $e) {
      header("HTTP/1.1 400 Bad Request");
      die ("Invalid Referer.");
  }
}

if ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") && $_SERVER['HTTP_HOST'] === "155.69.100.32") {
    $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://155.69.100.32');
    exit;
}
if ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") && $_SERVER['HTTP_HOST'] === "fypexam.scse.ntu.edu.sg") {
    $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://fypexam.scse.ntu.edu.sg');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_SERVER['QUERY_STRING'])) {
    header("HTTP/1.1 400 Bad Request");
    exit("Bad Request");
}

 foreach ($_POST as $name => $value) {
     $name = htmlentities($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
     $value = strip_tags($value);
 }
 $_GET   = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
 $_GET   = filter_input_array(INPUT_GET, FILTER_SANITIZE_ENCODED);
 $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

?>

<?php

   session_start();

   // users who are able to access all modules
   $verifiedUsers=["asfli", "sguo005", "audr0012", "jwong063", "lees0169", "ngxu0008", "c170155", "c170178", "SNKoh", "c170098", "teew0007", "hoang009", "weet0011"];

   //to check if the domain if is ours
   if(isset($_SESSION['login']) && isset($username)){
	   if (in_array($username, $verifiedUsers)) {
       header("location: index.php");
	   }else {
		   header("location: pref/nav.php");
	   }
	   exit;
   }

   if(isset($_POST['username']) && isset($_POST['pwd'])&& isset($_POST['domain'])){
        include_once('GibberishAES.php');

        $gib = new GibberishAES();
        $decrypted_pw =  $gib->decrypt($_POST['pwd'], $key);

  		  $domain =$_POST['domain'];
  		  $username = $_POST['username'];
  		  $password = $decrypted_pw; //$password_decrypted;//$_POST['pwd'];

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
    		else
    			 $loginError = "Your username/password is invalid!";
         }
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Login</title>
</head>

<body style="background-image: url('images/The_Arc.jpg'); background-size: 100% 100%;">
	<div id="content">

    <?php require_once('php_css/headerwologin.php'); ?>

	  <form class="form-horizontal" action ="login.php" onsubmit="encrypttest()" method="post" role="form" style="min-height: 84vh;">
  		 <div class="container col-sm-4 col-md-4 rounded" style="background: white; opacity: 0.9; filter: alpha(opacity=90); margin-top:2%;">

  		 	<div class="text-center">
  		 		<!-- pt = padding top, pb = padding bottom -->
  		 		<h3 class="pt-4 pb-4 display-5">WELCOME</h3>
  		 	</div>

  		 	<?php
  			if (isset($loginError)) {
  				    echo "<p class='warn'>[Login] ". $loginError ."</p>";
  			}
        ?>

  			<label for="userName" class="control-label">USERNAME:</label>
  			<input id="userName" type="text" class="form-control" name="username" value="" required>
  			<br/>

  			<label for="password" class="control-label">PASSWORD:</label>
  			<input id="password" type="password" name="pwd" data-toggle="password" class="form-control" required autocomplete="off">
  			<div class="float-right"><input type="checkbox" onclick="myFunction()">Show Password</div>
  			<br/>

  			<label for="domain" class="control-label">DOMAIN:</label>
  			<select class="form-control" name="domain">
  				<option value="Staff">Staff</option>
  				<option value="Student">Student</option>
  			</select>
  			<div class="float-right"><a href="https://pwd.ntu.edu.sg/">Forgot Password?</a></div>
  			<br/><br/>
  			<button type="submit" id="submit-btn"  class="btn bg-dark text-white" style="width: 100%;">Login</button>
  			<br/><br/>
  		</div>
	 </form>

   <div id="content">
    <form class="form-horizontal" role="form" style="min-height: 84vh;">
      <input type=hidden id ="fid" name="fid" value="<?php echo $_POST['pwd']?>">
    </form>
   </div>

	 <br/><br/>
	</div>

	</div>

<script type="text/javascript" src="../../../scripts/gibberish-aes-1.0.0.min.js"></script>
<script type="text/javascript">
  function encrypttest(){
    var encrypted = GibberishAES.enc(document.getElementById("password").value, <?php echo(json_encode($key)); ?>);
    document.getElementById("password").value=encrypted;
  }

	function myFunction() {
	  var x = document.getElementById("password");
	  if (x.type === "password") {
	    x.type = "text";
	  } else {
	    x.type = "password";
	  }
	}
</script>

</body>
</html>
