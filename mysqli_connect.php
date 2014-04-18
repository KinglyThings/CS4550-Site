<?php

// Let's get the database connections started

DEFINE ('DB_RESERVATION_HOST', 'kinglythingscom.ipagemysql.com');
DEFINE ('DB_RESERVATION_USER', 'reservebot');
DEFINE ('DB_RESERVATION_PASSWORD', 'reservebot');
DEFINE ('DB_RESERVATION_NAME', 'reservation');

// Remember: The @ in front of mysqli_connect will suppress error messages
$reservation_dbc = @mysqli_connect (DB_RESERVATION_HOST, DB_RESERVATION_USER, DB_RESERVATION_PASSWORD);
if (!$reservation_dbc) {
	die('Could not connect: ' . mysql_error());
}
mysqli_set_charset($reservation_dbc, 'utf8');

// Ok, now the database connection should be set up correctly
// And now for the second one
DEFINE ('DB_FINAL_HOST', 'kinglythingscom.ipagemysql.com');
DEFINE ('DB_FINAL_USER', 'finalbot');
DEFINE ('DB_FINAL_PASSWORD', 'finalbot');
DEFINE ('DB_FINAL_NAME', 'final');

$final_dbc = @mysqli_connect (DB_FINAL_HOST, DB_FINAL_USER, DB_FINAL_PASSWORD);
if (!$final_dbc) {
	die('Could not connect: ' . mysql_error());
}
mysqli_set_charset($final_dbc, 'utf8');

?>