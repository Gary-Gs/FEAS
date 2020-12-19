<?php
// users who are able to access all modules
$verifiedUsers=["asfli", "SNKoh", "c170155", "c170178", "c170098", "teew0007", "hoang009", "weet0011"];

// only verified users can navigate to all modules
if (!in_array($_SESSION['id'], $verifiedUsers)) {
    header("location: ../../../pref/nav.php");
    exit;
}
?>