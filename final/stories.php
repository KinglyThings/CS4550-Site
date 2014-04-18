<?php

// Load the session
session_start();

// Initialize the stories array
$stories = array();

// We need to load the available stories from the database
require_once('../mysqli_connect.php');
$connect_query = "USE final;";
$connect_result = @mysqli_query($final_dbc, $connect_query);

$grab_stories_query = "SELECT story_id, title, username, genre, editable, author_id FROM story ";
$grab_stories_query .= "LEFT JOIN user ON story.author_id = user.user_id WHERE story.private = 0;";
$grab_stories_result = @mysqli_query($final_dbc, $grab_stories_query);

// If there are stories, load them into the stories array
if (mysqli_num_rows($grab_stories_result)) {
    while ($row = mysqli_fetch_array($grab_stories_result)) {
        $story = array();
        $story['story_id'] = $row['story_id'];
        $story['title'] = $row['title'];
        $story['editable'] = $row['editable'];
        $story['genre'] = $row['genre'];
        $story['author'] = $row['username'];
        $story['author_id'] = $row['author_id'];
        $stories[] = $story;
    }
}
?>

<!DOCTYPE html>
<html>
<?php require 'header.php'; ?>

<?php 
// If there are no stories, display a message
if (empty($stories)) {
    echo "<div class='error-container'> Sorry, there are no stories available to read at this time.</div>";
} else {

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
?>
<?php require 'footer.php'; ?>
</html>