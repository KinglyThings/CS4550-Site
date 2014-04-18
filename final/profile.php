<?php

// Grab the session
session_start();

// This page will display a user's profile
// A user's profile consists of:
// - Their username
// - Their create date (Been a user since X
// - Their email
// - How many stories they have written
// - Their stories

// Anything that gets outputted should be decoded, even though no entity characters
// should be in them anyway
$author_id = trim(stripslashes(htmlspecialchars($_GET['author_id'])));
$num_stories = 0;
$author_exists = false;
$author = array();

// Now, let's pull the list of stories associated with this user from the database
require_once('../mysqli_connect.php');

$connect_query = "USE final;";
$connect_result = @mysqli_query($final_dbc, $connect_query);

// Grab the user from the database
$user_query = "SELECT user_id, username, email FROM user WHERE user_id = " . mysqli_real_escape_string($final_dbc, $author_id) . ";";
$user_result = @mysqli_query($final_dbc, $user_query);

if (mysqli_num_rows($user_result)) {
    // The user exists and will be the first row returned
    $author_exists = true;
    $author = mysqli_fetch_array($user_result);
}

// If the user exists, continue
// Otherwise, stop and alert the user
if ($author_exists) {
    // Create the query to grab the user's stories
    $story_query = "SELECT * FROM story WHERE author_id = " . mysqli_real_escape_string($final_dbc, $author_id);

    // If the user is trying to look themselves up, grab private stories as well
    if (isset($_SESSION['user_id'])) {
        $session_user_id = trim(stripslashes(htmlspecialchars($_SESSION['user_id'])));
        if ($session_user_id === "" || $session_user_id !== $author_id) {
            $story_query .= " AND private = 0";
        }
    } else {
        $story_query .= " AND private = 0";
    }
    $story_query .= ";";
    $story_result = @mysqli_query($final_dbc, $story_query);

    // If we get stories back, put them into the stories array
    // If we don't, display an empty stories list
    $stories = array();
    if (mysqli_num_rows($story_result)) {
        while ($row = mysqli_fetch_array($story_result)) {
            $stories[] = $row;
        }
        $num_stories = count($stories);
    }
}
?>

<!DOCTYPE html>
<html>
<?php require 'header.php'; ?>
<?php

if (!$author_exists) {
    echo '<div class="error-container"><span class="error">This user does not exist!</span></div>';
} else {
    echo '<h1>' . $author['username'] . '</h1><br />';
    echo '<h3> Email: ' . $author['email'] . '</h3><br />';
    // Show the stories
    if (empty($stories)) {
        echo '<div class="error-container">This user has not created any stories.</div>';
    } else {
        if ($num_stories === 1) {
            echo '<h3> This user has written 1 story!</h3><br />';
        } else {
            echo '<h3> This user has written ' . $num_stories . ' stories!</h3><br />';
        }
        // We'll have each row hold three stories
        for ($i = 0; $i < count($stories) / 3; $i++) {
            echo '<div class="row">';
            for ($j = 0; $j < 3; $j++) {
                if ( ((3 * $i) + $j) <= (count($stories) - 1) ) {
                    $story = $stories[(3 * $i) + $j];
                    // Modify the genre to match the image names
                    // Source for images: openclipart.org
                    if ($story['genre'] === "science fiction") {
                        $genre = "science-fiction";
                    } else {
                        $genre = $story['genre'];
                    }
                    echo '<div class="col-sm-6 col-md-4">';
                    echo '<div class="thumbnail storycard">';
                    echo '<img src="/static/images/' . htmlentities($genre) . '.jpg">';
                    echo '<div class="caption">';
                    echo '<h3>' . htmlentities($story['title']) . '</h3>';
                    echo '<p>By: <a href="http://www.kinglythings.com/final/profile.php?author_id=' . htmlentities($story['author_id']) . '">' . htmlentities($story['author']) . '</a></p>';
                    echo '<p><a href="http://www.kinglythings.com/final/read-story.php?story_id=' . htmlentities($story['story_id']) . '" class="btn-lg btn-primary" role="button">Read Now</a></p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            echo '</div>';
        }
    }
}
?>
<?php require 'footer.php'; ?>
</html>
