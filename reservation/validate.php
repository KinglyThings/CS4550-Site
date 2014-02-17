<html>
<head>
</head>	
<body>
Thank you for submitting a test form for Room Reservation.<br />
The system is currently offline. <br /><br />
<h1>Reservation Status</h1>
<?php 
// Get the email address to send things to
$email = "";

// Start the message to send in the email
$message = "";

// Start the errors array.
$errors = array();

if (isset($_POST['email_address'])) {
	// Run a validation filter on the email to ensure it is really an email
	$email = trim(stripslashes(htmlspecialchars($_POST['email_address'])));
}

if ($_POST['reservation_type'] === 'room') {
	$message .= "Thank you for reserving a room. ";
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
if ($_POST['time_increment'] === '1 day') {
	$message .= "You booked this room for 1 day. ";
} else if ($_POST['time_increment'] === '3 days') {
	$message .= "You booked this room for 3 days. ";
} else if ($_POST['time_increment'] === '1 week') {
	$message .= "You booked this room for 1 week. ";
} else {
	$errors[] = "You did not select a valid time increment.";
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
	echo "The reservation confirmation failed to send because there was no valid email address.\n";
} else {
	mail($email, "Robot Hotel Reservation Confirmation", $message);
}
?>
Please return to the main home page: <a href="http://www.kinglythings.com/reservation">Robot Hotel</a>
</body>
</html>