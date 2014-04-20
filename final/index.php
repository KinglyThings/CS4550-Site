<!DOCTYPE html>
<html>
	<?php include 'header.php'; ?>
<?php

// Grab the most popular story from the database
// TODO: Don't use a hard coded value, but determine this dynamically
require_once('../mysqli_connect.php');
$connect_query = "USE final;";
$connect_result = @mysqli_query($final_dbc, $connect_query);

$feature_story_query = "SELECT story_id, title, username, genre, editable, author_id FROM story LEFT JOIN user ON ";
$feature_story_query .= " story.author_id = user.user_id WHERE story.private = 0 AND story.story_id = 1;";
$feature_story_result = @mysqli_query($final_dbc, $feature_story_query);

// If we get a result, load it into the featured story div
// and load its author into the feature author div
$feature_story = array();
echo '<div class="row">';
echo '<h1>Welcome to StoryMaker</h1>';
echo '<h2>Start Reading and Writing your own CYOA Stories Now!</h2>';

if (mysqli_num_rows($feature_story_result)) {
    $feature_story = mysqli_fetch_array($feature_story_result);
    $feature_story['author'] = $feature_story['username'];
    $genre = "adventure";
    if ($feature_story['genre'] === "science fiction") {
        $genre = "science-fiction";
    } else {
        $genre = $feature_story['genre'];
    }

    // Print out the featured STORY div
    echo '<div id="featured_story" class="col-sm-6 col-md-6">';
    echo '<div class="thumbnail storycard">';
    echo '<img src="/static/images/' . htmlentities($genre) . '.jpg">';
    echo '<div class="caption">';
    echo '<h3>' . htmlentities($feature_story['title']) . '</h3>';
    echo '<p>This story is our featured story for April 2014!</p>';
    echo '<p>By: <a href="http://www.kinglythings.com/final/profile.php?author_id=' . htmlentities($feature_story['author_id']) . '">' . htmlentities($feature_story['author']) . '</a></p>';
    echo '<p><a href="http://www.kinglythings.com/final/read-story.php?story_id=' . htmlentities($feature_story['story_id']) . '" class="btn-lg btn-primary" role="button">Read Now</a></p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Print out the featured AUTHOR DIV
    echo '<div id="featured_author" class="col-sm-6 col-md-6">';
    echo '<div class="thumbnail storycard">';
    // TODO: Allow users to choose an avatar to represent themselves
    echo '<img src="/static/images/writer.jpg">';
    echo '<div class="caption">';
    echo '<h3>' . htmlentities($feature_story['author']) . '</h3>';
    echo '<p>Congratulations to the featured author of the month for April 2014!</p>';
    echo '<p> Visit their profile to see their works!</p>';
    echo '<p><a href="http://www.kinglythings.com/final/profile.php?author_id=' . htmlentities($feature_story['author_id']) . '" class="btn-lg btn-primary" role="button">PROFILE</a></p>';
    echo '</div></div></div></div>';
} else {
    echo '<div class="error-container"><span class="error">UNABLE TO RETREIVE FEATURED STORY</span></div>';
}

?>
<?php include 'footer.php'; ?>
</html>