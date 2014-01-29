/* reservationCalculator.js */
/* NEEDS: jQuery */

// Defines basic rates
var roomTypeBaseCost = {
	nano: 200,
	micro: 300,
	drone: 500,
	mega: 2000
};

var hallTypeBaseCost = {
	silicon: 5000,
	cyber: 20000,
	robo: 7500
};

var timeBaseMultiplier = {
	"1 day": 1,
	"3 days": 3,
	"1 week": 7
};


// Opens the Calculator from the home page
function openCalculator() {
	$('.main-info').hide();
	$('#calculator').show();

	// Step One Stuff
	$('#calc_step_one').show();
	$('#room').hide();
	$('#hall').hide();
	$('#time').hide();
	$('#step_one_button').hide();

	// TODO: Step two stuff (amenities, differing cost by robot model)
}

// Changes the reservation type in Step One
function setReservationType() {
	var $radio = $('#calc_step_one>input[type=radio][name=reservation_type]:checked');
	if ($radio.val() === "room") {
		$('#room').show();
		$('#hall').hide();
	} else {
		$('#room').hide();
		$('#hall').show();
	}

	// When selecting for the first time, show the time dialog
	$('#time').show();
}

// Changes the room type in Step One
// TODO: Give this real functionality
function setRoomType() {
	var $select = $('#room_type');
	console.log($select.val());
}

// Changes the hall type in Step One
// TODO: Give this real functionality
function setHallType() {
	var $select = $('#hall_type');
	console.log($select.val());
}

// Sets the time increment in Step One
// TODO: Use a Date/Time Picker to give more discrete control
function setTimeIncrement() {

	// If this is the first time we're making a selection,
	// Show the Calculate Button
	var $select = $('#time_increment');
	if ($select.val() !== "default") {
		$('#step_one_button').show();
	}
}

// Calculates the cost of the room
// TODO: Split into one function that calculates and one function that
// displays, with the display function as a callback to the calculate function
function calculateAndDisplay() {
	// First, let's grab the variables that matter

	var time_type = $('#time_increment').val();
	console.log(time_type);
	var time = timeBaseMultiplier[time_type];
	console.log(time);

	var type = $('#calc_step_one>input[type=radio][name=reservation_type]:checked').val();
	console.log(type);

	var booking;
	var booking_type;
	if (type === "room") {
		booking_type = $('#room_type').val();
		booking = roomTypeBaseCost[booking_type];
	} else {
		booking_type = $('#hall_type').val();
		booking = hallTypeBaseCost[booking_type];
	}

	console.log(booking);

	var cost = time * booking;

	// Hide the calculator and show the results screen
	$('#calculator').hide();
	var output = "Booking a " + $('#room_type:selected').text() + " " + type + " for " + $('#time_increment :selected').text();
	output += " will cost you " + cost + " Energonic Credits";

	$('#results').text(output);
	$('#results').show();
}