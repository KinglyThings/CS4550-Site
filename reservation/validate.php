<html>
<head>
</head>	
<body>
Thank you for submitting a form for Room Reservation.<br />
<h1>Reservation Status</h1>
<?php 

// Start a database connection.
// We'll need it to check reservation statuses
// NOTE: In a perfect world, this would be somewhere that no one could access it (outside of the document root)
//       I can't seem to make that work with iPage yet (God, I hate iPage)
require_once('../mysqli_connect.php');

// Connect to the Database
$connect_query = "USE reservation";	
$connect_result = @mysqli_query ($reservation_dbc, $connect_query);

// Get the email address to send things to
$email = "";
$serial_number = "0";
$start_date_temp = "1000-01-01";
$end_date_temp = "9999-01-01";

// Start the message to send in the email
$message = "";

// Start the errors array.
$errors = array();

if (isset($_POST['email_address'])) {
	// Run a validation filter on the email to ensure it is really an email
	$email = trim(stripslashes(htmlspecialchars($_POST['email_address'])));
}

if (isset($_POST['serial_number'])) {
	// Check that the number contains only digits
	$serial_number = trim($_POST['serial_number']);
	if (preg_match("^[0-9]+$", $serial_number) === 0) {
		// The serial number was not all digits. Error
		$errors[] = "The serial number you provided was invalid. ";
	} 
} else {
	$errors[] = "You did not provide a serial number. ";
}

// Regardless of room/hall, grab the room name of the room being reserved
$room_for_id = "";
if ($_POST['reservation_type'] === 'room') {
	$message .= "Thank you for reserving a room. ";
	$room_for_id = $_POST['room_type'];
	if ($_POST['room_type'] === 'nano') {
		$message .= "We hope you enjoy your Nano-Room. ";
	} else if ($_POST['room_type'] === 'micro') {
		$message .= "We hope you enjoy your Micro-Room. ";
	} else if ($_POST['room_type'] === 'drone') {
		$message .= "We hope you enjoy your Standard Drone Room. ";
	} else if ($_POST['room_type'] === 'mega') {
		$message .= "We hope you enjoy your Mega-Industrial Suite. ";
	} else {
		$errors[] = "You did not select a valid room to reserve. ";
	}
} else if ($_POST['reservation_type'] === 'hall') {
	$message .= "Thank you for reserving a conference hall. ";
	$room_for_id = $_POST['hall_type'];
	if ($_POST['hall_type'] === 'cyber') {
		$message .= "We hope you enjoy the Cyber Meeting Hall. ";
	} else if ($_POST['hall_type'] === 'silicon') {
		$message .= "We hope you enjoy the Silicon Room. ";
	} else if ($_POST['hall_type'] === 'robo') {
		$message .= "We hope you enjoy the Robo-Basilica. ";
	} else {
		$errors[] = "You did not select a valid conference hall to reserve. ";
	}
} else {
	$errors[] = "You did not select a type of room to reserve.";
}

// Check that there are start and end dates
// I really miss javascript regexes that actually work
if (isset($_POST['start_date'])) {
	$start_date_temp = trim($_POST['start_date']);
} else {
	$errors[] = "You did not provide a start date for your reservation. ";
}

// Check that there are start and end dates
if (isset($_POST['end_date'])) {
	$end_date_temp = trim($_POST['end_date']);
} else {
	$errors[] = "You did not provide a end date for your reservation. ";
}

// Convert the dates to datetimes
$start_date = new DateTime($start_date_temp);
$end_date = new DateTime($end_date_temp);


// Ensure that the end date occurs after the start date
if ($start_date > $end_date) {
	$errors[] = "The start date occurs after the end date.";
}


$start_date_string = date_format($start_date, 'Y-m-d');
$end_date_string = date_format($end_date, 'Y-m-d');


// Update the message with the reservation time
$message .= "Your reservation runs from $start_date_string to $end_date_string. ";

$test_query = "SELECT room_id FROM rooms WHERE name = '" . mysql_real_escape_string(trim($room_for_id)) . "'";
$test_results = @mysqli_query($reservation_dbc, $test_query);

$room_id = 1; // Default for testing

while ($row = mysqli_fetch_array($test_results, MYSQLI_ASSOC)) {
	if (isset($row['room_id'])) {
		$room_id = $row["room_id"];
	}
}

// Ensure that the room being reserved is actually available during the reservation period
// iPage is unkind to prepared queries, hence this scariness
$check_query = "SELECT * FROM reservations WHERE room_id = $room_id AND ";
$check_query .= "((start_date <= $start_date_string AND end_date >= $start_date_string ) ";
$check_query .= " OR (start_date <= $end_date_string AND end_date >= $end_date_string))";

// This query will return all reservations of the desired room that overlap the desired reservation period
$check_results = @mysqli_query($reservation_dbc, $check_query);
$num_rows = mysqli_num_rows($check_results);

if ($num_rows !== 0) {
	// This means that there is a conflicting reservation
	$errors[] = "There is a reservation for the desired room already scheduled for this time.";
}

// Validation done, time to send the email
if (!empty($errors)) {
	echo "This reservation failed because there were errors:\n";
	for ($i = 0; $i < count($errors); $i++) {
		echo $errors[$i];
		echo "<br />";
	}
// Rough validation for valid emails. filter_var doesn't seem to want to work on my server
} else if (preg_match('/^(.)*@(.)+\.(.)+', $email) === 0) {
	echo "The reservation confirmation failed because there was no valid email address.\n";
} else {
	// There are no errors if we've reached this point
	// Make the reservation and send the confirmation
	$reserve_query = "INSERT INTO reservations (room_id, start_date, end_date, email) VALUES ( $room_id, '$start_date_string', '$end_date_string', '$email' )";
	
	$update_results = @mysqli_query ($reservation_dbc, $reserve_query);
	mail($email, "Robot Hotel Reservation Confirmation", $message);
}
?>
Please return to the main home page: <a href="http://www.kinglythings.com/reservation">Robot Hotel</a>
</body>
</html>