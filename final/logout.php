<?php
// This script will log the user out of the site

// Grab the session
session_start();

// If the user is logged in, log them out by destroying their session
if (isset($_SESSION['logged_in'])) {
    session_destroy();
}

// Redirect the user to the home page
$redirect_url = "http://www.kinglythings.com/final/";
header("Location: " . $redirect_url);
exit;


?>