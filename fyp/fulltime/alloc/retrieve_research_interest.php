<?php

require_once('./entity.php');
require_once('../../../Connections/db_ntu.php');
require_once('../../../CSRFProtection.php');
require_once('../../../Utility.php');
include('../../../simple_html_dom.php');

$html = file_get_html('https://www3.ntu.edu.sg/SCSE/moss_staffac.asp');
$htmlData = trim($html);
if (!empty($htmlData)) {
  // Delete all data so that we can re-insert
  $stmt_delete = $conn_db_ntu->prepare("DELETE FROM " . $TABLES['research_interest']);
  $stmt_delete->execute();

  // Check if rows are indeed deleted
  $stmt_select = $conn_db_ntu->prepare("SELECT * FROM " . $TABLES['research_interest']);
  $stmt_select->execute();
  $retrieveInterest = $stmt_select->fetchAll();

  if ($retrieveInterest == null || sizeof($retrieveInterest) == 0) {
    $count = 0;

    // Find the table containing the staff table, followed by the table that contains individual staff details
    $outerTable = $html->find('table[class="style1"]', 1);
    $staffTable = $outerTable->find('table', 0);
    $table = $staffTable->find('table[class="style1"]');

    foreach ($table as $row) {
      $staffDetails = $row->find('span[class="parahead2"]', 0)->plaintext;
      //$staffName = substr($staffDetails, 0, strpos($staffDetails, ')') + 1);
      $getStaffEmail = $row->find('a', -1)->plaintext;
      //$staffEmail = strtolower(substr($getStaffEmail, 0, strpos($getStaffEmail, '{')));
      //$staffEmail = strtolower(substr($getStaffEmail, 0, (strlen($getStaffEmail) - 14)));
      //$staffEmail = preg_split('/[^a-zA-Z0-9]/i', $getStaffEmail);
      $staffEmail = preg_split('/[{|[]|@/', $getStaffEmail);
      $email = strtolower($staffEmail[0]);

      $researchInterestTable = $row->find('table', 0);
      $researchInterestRow = $researchInterestTable->find('tr');

      //echo $email;
      //echo "<br>";

      // No research interest
      if ($researchInterestTable == null || $researchInterestRow == null) {
        $empty = "";
        // Insert into database
        $stmt_insert = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['research_interest']." (staff_id, interest) VALUES (?, ?)");
        $stmt_insert->bindParam(1, $email);
        $stmt_insert->bindParam(2, $empty);
        $stmt_insert->execute();
      }
      else {
        foreach ($researchInterestRow as $researchInterest) {
          $interest = $researchInterest->find('td', 1)->plaintext;

          // Insert into database
          $stmt_insert = $conn_db_ntu->prepare("INSERT INTO ".$TABLES['research_interest']." (staff_id, interest) VALUES (?, ?)");
          $stmt_insert->bindParam(1, $email);
          $stmt_insert->bindParam(2, $interest);
          $stmt_insert->execute();
        }
      }
      $count++;
    }
  }
}

// Retrieve those staff who has totally no Preference
$query_rsStaffNoPreference		= "SELECT id FROM " . $TABLES["staff"] .
																" WHERE id NOT IN (SELECT staff_id FROM " . $TABLES["staff_pref"] . " WHERE archive = 0)";

$stmt_staffNoPreference = $conn_db_ntu->prepare($query_rsStaffNoPreference);
$stmt_staffNoPreference->execute();
$rsStaffNoPreference = $stmt_staffNoPreference->fetchAll(PDO::FETCH_ASSOC);

// Foreach staff
foreach ($rsStaffNoPreference as $row_rsStaffNoPreference) {
	// retrieve his research interest
	$query_rsGetStaffResearchInterest		= "SELECT * FROM " . $TABLES["research_interest"] .
																			" WHERE staff_id = ?";

	$stmt_getStaffResearchInterest = $conn_db_ntu->prepare($query_rsGetStaffResearchInterest);
	$stmt_getStaffResearchInterest->bindParam(1, $row_rsStaffNoPreference["id"]);
	$stmt_getStaffResearchInterest->execute();
	$rsGetStaffResearchInterest = $stmt_getStaffResearchInterest->fetchAll(PDO::FETCH_ASSOC);

	// retrieve all area preference available
	$query_rsGetAllInterestArea		= "SELECT * FROM " . $TABLES["interest_area"];

	$stmt_getAllInterestArea = $conn_db_ntu->prepare($query_rsGetAllInterestArea);
	$stmt_getAllInterestArea->execute();
	$rsGetAllInterestArea = $stmt_getAllInterestArea->fetchAll(PDO::FETCH_ASSOC);

  $archive = 0;
	$choiceCount = 100;
	//$areaFound = false;
	$date   = new DateTime(); //this returns the current date time
	$currentDateTime = $date->format('Y-m-d H:i:s');

	// if no match above
	//if ($areaFound == false) {
		foreach ($rsGetStaffResearchInterest as $row_rsGetStaffResearchInterest) {
			$maxMatch = 0;
			$currentInterestMatch = "";

			foreach ($rsGetAllInterestArea as $row_rsGetAllInterestArea) {
				$currentMatch = 0;
				$keywordArray = explode(',', $row_rsGetAllInterestArea['keyword']);

				foreach ($keywordArray as $key) {
					if (stripos(($row_rsGetStaffResearchInterest['interest']), $key) !== false) {
						$currentMatch++;
					}
				}

				if ($currentMatch > $maxMatch) {
          $maxMatch = $currentMatch;
					$currentInterestMatch = $row_rsGetAllInterestArea['key'];
				}
			} // end foreach interest loop

			if ($currentInterestMatch != "") {
        // Check if the preference has already been added before
        $query_rsCheckPreferenceExist		= "SELECT * FROM " . $TABLES["staff_pref"] .
      																	  " WHERE staff_id = ? AND prefer = ?";

      	$stmt_checkPreferenceExist = $conn_db_ntu->prepare($query_rsCheckPreferenceExist);
      	$stmt_checkPreferenceExist->bindParam(1, $row_rsStaffNoPreference["id"]);
        $stmt_checkPreferenceExist->bindParam(2, $currentInterestMatch);
      	$stmt_checkPreferenceExist->execute();
      	$rsCheckPreferenceExist = $stmt_checkPreferenceExist->fetchAll(PDO::FETCH_ASSOC);

        if ($rsCheckPreferenceExist == null || sizeof($rsCheckPreferenceExist) == 0) {
          // insert
          $insertStmt = $conn_db_ntu->prepare("INSERT INTO ". $TABLES['staff_pref'] . " (staff_id, prefer, choice, choose_time, archive) VALUES (?, ?, ?, ?, ?)");
          $insertStmt->bindParam(1, $row_rsStaffNoPreference['id']);
          $insertStmt->bindParam(2, $currentInterestMatch);
          $insertStmt->bindParam(3, $choiceCount);
          $insertStmt->bindParam(4, $currentDateTime);
          $insertStmt->bindParam(5, $archive);
          $insertStmt->execute();

          $choiceCount++;
        }
			}
		//}
	} // end foreach
}

$conn_db_ntu = null;

$_SESSION['scrape'] = $count;
header("location:allocation.php");
exit;

?>
