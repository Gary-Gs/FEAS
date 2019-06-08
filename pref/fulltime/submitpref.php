<?php
require_once('../../Connections/db_ntu.php');
require_once('../../CSRFProtection.php');
require_once('../../Utility.php');?>
<?php
	$localHostDomain = 'http://localhost';
	$ServerDomainHTTP = 'http://155.69.100.32';
	$ServerDomainHTTPS = 'https://155.69.100.32';
	$ServerDomain = 'https://fypExam.scse.ntu.edu.sg';
	if(isset($_SERVER['HTTP_REFERER'])) {
		try {
				// If referer is correct
				if ((strpos($_SERVER['HTTP_REFERER'], $localHostDomain) !== false) || (strpos($_SERVER['HTTP_REFERER'], $ServerDomainHTTP) !== false) || (strpos($_SERVER['HTTP_REFERER'], $ServerDomainHTTPS) !== false) || (strpos($_SERVER['HTTP_REFERER'], $ServerDomain) !== false)) {
						//echo "<script>console.log( 'Debug: " . "Correct Referer" . "' );</script>";
				}
				else {
						throw new Exception($_SERVER['Invalid Referer']);
						//echo "<script>console.log( 'Debug: " . "Incorrect Referer" . "' );</script>";
				}
		}
		catch (Exception $e) {
				die ("Invalid Referer.");
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_SERVER['QUERY_STRING'])) {
			header('location: staffpref_fulltime.php');
	}

	$csrf = new CSRFProtection();

	$_REQUEST['validate'] =	$csrf->cfmRequest();

	$staffid = $_REQUEST['staffid'];

	if (!isset($_POST['clearChanges'])) {
		try
		{
			//$conn_db_ntu->exec("DELETE FROM ".$TABLES['staff_pref']." WHERE staff_id LIKE $sid");
			$stmt = $conn_db_ntu->prepare("DELETE FROM ".$TABLES['staff_pref']." WHERE staff_id = ? and archive = 0");
			$stmt->bindParam(1, $staffid );
			$stmt->execute();

		}
		catch (PDOException $e)
		{
			die($e->getMessage());
		}

		$i=1;
		$j=1;
		$cid = "";

		while(isset($_REQUEST['projpref'.$i])) {
			echo "<br/>";
			$projectPref = $_REQUEST['projpref'.$i];


			if($projectPref=='blank')
				echo "Empty";
			else {

				$stmt = $conn_db_ntu->prepare("SELECT * FROM ".$TABLES['staff_pref']." WHERE staff_id= ? and prefer= ?");
				$stmt->bindParam(1, $staffid);
				$stmt->bindParam(2, $projectPref);
				$stmt->execute();
				$existProjectPrefs= $stmt->fetchAll();
				if (sizeof ($existProjectPrefs) == 0)
				{
					$stmt = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['staff_pref']." (staff_id, prefer, choice) VALUES (?, ?, ?)");
					$stmt->bindParam(1, $staffid);
					$stmt->bindParam(2, $projectPref);
					$stmt->bindParam(3, $j);
					$stmt->execute();

					$j++;
					//echo "C:".$check.":";
				}
			}
			$i++;
		}

		$i=1;
		$j=1;

		while(isset($_REQUEST['areapref'.$i])) {
			echo "<br/>";
			$areaPref = $_REQUEST['areapref'.$i];
			if($areaPref=='blank')
				echo "Empty";
			else {
				//$exists = $conn_db_ntu->query("SELECT * FROM ".$TABLES['staff_pref']." WHERE //`staff_id`=$sid and `prefer`=$check");
				$stmt = $conn_db_ntu->prepare("SELECT * FROM ".$TABLES['staff_pref']." WHERE staff_id= ? and prefer= ?");
				$stmt->bindParam(1, $staffid);
				$stmt->bindParam(2, $areaPref);
				$stmt->execute();
				$existAreaPrefs = $stmt->fetchAll();

				if (sizeof ($existAreaPrefs) == 0)
				{
					$stmt = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['staff_pref']." (staff_id, prefer, choice) VALUES (?, ?, ?)");
					$stmt->bindParam(1, $staffid);
					$stmt->bindParam(2, $areaPref);
					$stmt->bindParam(3, $j);
					$stmt->execute();

					$j++;
					//echo "C:".$check.":";
				}
			}
			$i++;
		}
		$conn_db_ntu = null;

		$_SESSION['saveChanges'] = 'save';
	}
	else {
		$_SESSION['clearChanges'] = 'clear';
	}
?>
<?php
if(isset ($_REQUEST['validate'])){
	header("location:staffpref_fulltime.php?validate=1");
	}
else{
	echo '<script> location.href="staffpref_fulltime.php"; </script>';
	//header("location:staffpref_fulltime.php?save=1");
	}
	exit;
?>
