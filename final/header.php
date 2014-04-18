<?php 
// Grab the session
session_start(); 
?>
<head>
	<title>Create Your Own Adventure</title>

	<!-- TODO: Add more META Tags -->
	<meta charset="utf8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- Use Bootstrap and jQuery UI CSS to simplify the CSS needs -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" />
	<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
    <link rel="stylesheet" href="/static/css/final.css" />

	<!-- Use jQuery and jQuery UI to simplify the creation of the user interface -->
	<!-- TODO: Look into using a javascript framework (Ember, Angular!) -->
	<!-- TODO: Get a local copy of all of these resources that's up-to-date and remove the network calls -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
	<script>
		//TODO: Move this all into a separate javascript file.

	</script>
</head>

<body>
	<!-- Layout of the site will involve:
	     - A Nav Bar at the top with the Logo/Name and the Log In/Register Buttons
	     - Content below the bar with action options on the left (Create/Update/Read)
	     - Different content for logged in users
    TODO: Consider using Bootstrap's built-in nav instead
    -->
    <div id="header_container">

    	<a href="http://www.kinglythings.com/final"><img id="main_logo" src="/static/images/final_logo.jpg" /></a>
    	<h1 id="title">StoryMaker</h1>
	</div>
    <div class="row" id="navbar">
        <a href="http://www.kinglythings.com/final/stories.php">
            <div id="view_stories" class="col-offset-md-1 col-md-3">
                Read Stories
            </div>
        </a>
        <?php if (isset($_SESSION['logged_in'])) {
            require_once('logged-in-nav.php');
        } else {
            require_once('logged-out-nav.php');
        }
        ?>
    </div>