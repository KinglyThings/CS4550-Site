<html>
<head>
</head>	
<body>
Thank you for submitting a test form for Room Reservation.<br />
The system is currently offline. <br /><br />
<h1>Reservation Status</h1>
<?php 
if ($_POST['reservation_type'] === 'room') {
	echo "Thank you for reserving a room.";
} else if ($_POST['reservation_type'] === 'hall') {
	echo "Thank you for reserving a conference hall.";
} else {
	echo "You did not select a type of room to reserve.";
} ?>
<br />
<?php
if ($_POST['reservation_type'] === 'room') {
	if ($_POST['room_type'] === 'nano') {
		echo "We hope you enjoy your Nano-Room.";
	} else if ($_POST['room_type'] === 'micro') {
		echo "We hope you enjoy your Micro-Room.";
	} else if ($_POST['room_type'] === 'drone') {
		echo "We hope you enjoy your Standard Drone Room.";
	} else if ($_POST['room_type'] === 'mega') {
		echo "We hope you enjoy your Mega-Industrial Suite";
	} else {
		echo "You did not select a valid room to reserve.";
	}
} else if ($_POST['reservation_type'] === 'hall') {
	if ($_POST['hall_type'] === 'cyber') {
		echo "We hope you enjoy the Cyber Meeting Hall.";
	} else if ($_POST['hall_type'] === 'silicon') {
		echo "We hope you enjoy the Silicon Room.";
	} else if ($_POST['hall_type'] === 'robo') {
		echo "We hope you enjoy the Robo-Basilica.";
	} else {
		echo "You did not select a valid conference hall to reserve.";
	}
}
?>
<br />
<?php
if ($_POST['time_increment'] === '1 day') {
	echo "You booked this room for 1 day.";
} else if ($_POST['time_increment'] === '3 days') {
	echo "You booked this room for 3 days.";
} else if ($_POST['time_increment'] === '1 week') {
	echo "You booked this room for 1 week.";
} else {
	echo "You did not select a valid time increment.";
}
?>
<br /><br />

Please return to the main home page: <a href="http://www.kinglythings.com/reservation">Robot Hotel</a>
</body>
</html>