<?php
require_once('../../../Connections/db_ntu.php');
//require_once ('../../../PHPExcel/IOFactory.php');
require_once('../../../CSRFProtection.php');
require_once ('../../../vendor/autoload.php');

session_start();
ini_set('max_execution_time', 600);

$redirect = false;
$error_code = -1; // no error

$csrf = new CSRFProtection();


$_REQUEST['validate'] = $csrf->cfmRequest();

// initialise variables for file upload
$target_dir = "../../../uploaded_files/";
$target_file = "";
$inputFileType = "";
$inputFileName = "";
$inputFullPath = "";



// Check for file name
if(isset($_FILES["file"]["name"])){
	$target_file = $target_dir.basename($_FILES["file"]["name"]);
	$inputFileType = pathinfo($target_file,PATHINFO_EXTENSION);
	$inputFileName = $_FILES["file"]["name"];
	// Check for excel file only
	if($inputFileType == "xlsx" || $inputFileType == "xls" || $inputFileType == "csv" ){
		if(move_uploaded_file($_FILES["file"]["tmp_name"], $target_file )){
			ReadExcelData($target_file);
			$error_code = 0;
		} else{
			$error_code=3; // file is open
		}
	} else {
		$error_code=2; // invalid file
	}
} else{
	$error_code=1; // no file name
}
$redirect = true;
if (isset ($_REQUEST['validate'])) {

	echo "validate=1";
}
else if($redirect){
	echo ($error_code != 0) ? $_SESSION['error_code'] = $error_code : $_SESSION["import_examiner"] = "import_examiner";
}
exit;


// Read excel file
function ReadExcelData($inputFullPath){
	try {
		//$objPHPExcel = PHPExcel_IOFactory::load($inputFullPath);
		$objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFullPath);
	}
	catch(Exception $e){
		die('Error loading file"'.pathinfo($inputFullPath,PATHINFO_BASENAME).'":'.$e->getMessage());
		$error_code=4; // Cannot load excel file
	}
	HandleExcelData($objPHPExcel);
}

// Do whatever you want
function HandleExcelData($objPHPExcel){
	$Contents = ""; // Stats tracking
	// CUSTOM CODE GOES HERE
	$AllDataInSheet = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

	// EXCEL: Faculty data starts from row 2 onwards
	$Excel_FacultyList = array();
	//for ($index = 2; $index <=  count($AllDataInSheet) +1 ; $index ++) {
	for ($index = 2; $index <=  count($AllDataInSheet) ; $index ++) {

		if ($AllDataInSheet[$index]["C"] != "" && $AllDataInSheet[$index]["C"] != null && $AllDataInSheet[$index]["A"] != "" && $AllDataInSheet[$index]["A"] != null) {
			$EXCEL_FacultyEmail 	= strtolower($AllDataInSheet[$index]["C"]);
			$EXCEL_FacultyName 		= $AllDataInSheet[$index]["A"];
			$EXCEL_FacultyID		= explode("@", $EXCEL_FacultyEmail)[0];
			$EXCEL_Faculty 			= sprintf("%s;%s;%s", $EXCEL_FacultyID,$EXCEL_FacultyEmail,$EXCEL_FacultyName);
			$Excel_FacultyList[$EXCEL_FacultyID] 	= $EXCEL_Faculty;
		}
	}
	global $TABLES, $conn_db_ntu;
	try
	{
		// DB: DELETE ALL FACULTY FROM STAFF TABLE
		$query_DeleteFaculties 	= "DELETE FROM ".$TABLES['staff'];
		$DBObj_Result 			= $conn_db_ntu->prepare($query_DeleteFaculties);
		if($DBObj_Result->execute()){
			$Contents = $Contents . "All staff removed (SUCCESS)\n";
			$Total_FacultyInExcel 	= count($Excel_FacultyList);
			$Faculty_Created 		= array();
			$Faculty_NotCreated 	= array();

			// DB: ADD FACULTY FROM EXCEL INTO DB
			foreach ($Excel_FacultyList as $FacultyString) {
				$Faculty 		= explode(";", $FacultyString);		// "id,email;name;..."
				$insert_Faculty = sprintf("INSERT INTO %s (id,email,name) VALUES('%s','%s','%s');",$TABLES["staff"],$Faculty[0],$Faculty[1],$Faculty[2]);
				try
				{
					$DBObj_Result = $conn_db_ntu->prepare($insert_Faculty);
					if($DBObj_Result->execute()){
						//echo "CREATED $Faculty\n";
						$Faculty_Created[$Faculty[0]] = $FacultyString;
					}else{
						//echo "CREATED $Faculty FAILED\n";
						$Faculty_NotCreated[$Faculty[0]] = $FacultyString;
					}
				}
				catch(PDOException $Ex)
				{
					$Faculty_NotCreated[$Faculty[0]] = $FacultyString;
					echo $Ex->getMessage();
				}
			}

		} else{
			$Contents = $Contents . "All staff removed (FAILED)\n";
		}
	}
	catch(PDOException $Ex)
	{
		echo $Ex->getMessage();
		die(" error : " . $Ex->getMessage());
		$Contents = $Contents . sprintf("%s. $s\n","Error removing staff", $Ex->getMessage());
	}


	$Total_FacultyCreated 		= count($Faculty_Created);
	$Total_FacultyNotCreated 	= count($Faculty_NotCreated);
			// States Tracking
	$Contents = $Contents . sprintf("%-15s : %04d\n", " Total Faculty In Excel", $Total_FacultyInExcel);
	$Contents = $Contents . sprintf("%-15s : %04d\n", " Total Faculty Created", $Total_FacultyCreated);
	$Contents = $Contents . sprintf("%-15s : %04d\n", " Total Faculty Not Created", $Total_FacultyNotCreated);
	$file = "submit_import_facultylist_result.txt";
	file_put_contents($file, $Contents, LOCK_EX);
	//echo $Contents;
}

?>
