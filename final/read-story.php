<?php

// Grab the session
session_start();

// Ok, we should have a story ID passed in.
// If we don't or we have an invalid one, just display a generic error
$story_id = trim(stripslashes(htmlspecialchars($_GET['story_id'])));

// Default story card is variable (the first card in a story)
$card_id = -1;
$first_card = array();
$first_card_id = -1;
$story_error = "";

// Connect to the database
require_once('../mysqli_connect.php');

$connect_query = "USE final;";
$connect_result = @mysqli_query($final_dbc, $connect_query);

// Let's check if this is a real story and a valid card id
$story_query = "SELECT * FROM story WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ";";
$story_result = @mysqli_query($final_dbc, $story_query);

// If we get a story back, this is a real story
$story = array();
if (mysqli_num_rows($story_result)) {
    // There should only be one story per story id, so the first one is the one we need
    $story = mysqli_fetch_array($story_result);
} else {
    $story_error = "NO STORY";
}


// We need to grab the first card from the DB
$first_card_query = "SELECT first_card_id FROM first_card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ";";
$first_card_result = @mysqli_query($final_dbc, $first_card_query);

// If there is a first card, use it
// Otherwise, this story has no cards, so set story_error to "BLANK STORY"
if (mysqli_num_rows($first_card_result)) {
    $first_card = mysqli_fetch_array($first_card_result);
    $first_card_id = $first_card['first_card_id'];
} else {
    $story_error = "NO CARDS";
}

// If we should start the story on a different story card, we need to grab that
// If we don't have a card_id, we need to fetch the first card from the DB
if ($story_error === "" && isset($_GET['card_id'])) {
    $card_id = trim(stripslashes(htmlspecialchars($_GET['card_id'])));
} else if ($story_error === "") {
    $card_id = $first_card_id;
}

// If there is a story with this id, check if there is a story card for this 
// story corresponding to the card ID
$card = array();
if ($story_error === "") {
    $card_query = "SELECT * FROM card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);

    // If we had a card id, use that
    // Otherwise, use the id of the first card in the story that we grabbed earlier
    $card_query .= " AND card_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
    $card_result = @mysqli_query($final_dbc, $card_query);

    // If there is a card, then store it in $card
    // If not, set the story error to "NO CARD"
    if (mysqli_num_rows($card_result)) {
        // The card we want will always be the first card
        $card = mysqli_fetch_array($card_result);
    } else {
        $story_error = "NO CARD";
    }
}

// If there is a story and a card, then we are ready to go.
// We have the current story data and the current card data already
// What we still need is the text of the options and the card id's that they redirect to
$choices = array();
if ($story_error === "") {
    $choice_query = "SELECT * FROM cardmap WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
    $choice_query .= " AND card_id = " . mysqli_real_escape_string($final_dbc, $card_id);
    $choice_query .= ";";

    $choice_result = @mysqli_query($final_dbc, $choice_query);

    // If we get options back, then we will add them to the options array
    // Otherwise, this is a terminal node of the story
    $end = false;
    if (mysqli_num_rows($choice_result)) {
        // There are choices! Read them into the choices array
        while ($row = mysqli_fetch_array($choice_result)) {
            $choices[] = $row;
        }
        $end = false;

    } else {
        // This is a terminal node to the story
        $end = true;
    }

    // Ok, we have now read in the entire story data. We are ready to start processing the page
}

?>

<!DOCTYPE html>
<html>
<?php require 'header.php'; ?>
<?php 
// Handle errors
if ($story_error === "NO STORY") {
    echo '<div class="error-container><span class="error">Story Not Found</span></div>';
} else if ($story_error === "NO CARDS") {
    echo '<div class="error-container><span class="error">This Story is Empty!</span></div>';
} else if ($story_error === "NO CARD") {
    echo '<div class="error-container><span class="error">Story Card Not Found</span></div>';
} else {
    // No errors in reading in the story
    echo '<div class="container">';
    echo '<div class="row">';
    echo '<div id="title-pane" class="col-sm-12">' . htmlentities($story['title']) . '</div>';
    echo '<div id="text-pane" class="col-sm-8"><div id="text-window">';
    echo htmlentities($card['text']);
    echo '</div></div>';
    echo '<div id="choice-pane" class="col-sm-4">';
    for ($i = 0; $i < count($choices); $i++) {
        // Set up the choice buttons
        echo '<button type="button" role="button" class="btn btn-lg btn-primary col-sm-12 btn-choice" id="option';
        echo htmlentities($choices[$i]['choice_id']) . '">' . htmlentities($choices[$i]['choice_text']) . '</button>';
    }
    if (count($choices) === 0) {
        // We only hit this if there are no choices left
        echo '<span id="the_end">THE END</span>';
    }
    echo '<button type="button" role="button" class="btn btn-lg btn-danger col-sm-6 col-sm-offset-3 btn-choice" id="reset_button">Reset</button>';
    echo '</div></div></div>';
    echo '<script>';
    for ($i = 0; $i < count($choices); $i++) {
        // Set up the button click handlers
        echo '$("#option' . htmlentities($choices[$i]['choice_id']) . '").on("click", function (e)';
        echo ' { location.href = "http://www.kinglythings.com/final/read-story.php?story_id=' . htmlentities($story['story_id']);
        echo '&card_id=' . htmlentities($choices[$i]['choice_id']) . '"; });';
    }
    echo '$("#reset_button").on("click", function (e) { location.href = "http://www.kinglythings.com/final/read-story.php?story_id=';
    echo htmlentities($story['story_id']) . '&card_id=' . htmlentities($first_card_id) . '"; });';
    echo '</script>';
}

?>
<!--
<div class="container">
    <div class="row">
        <div id="title-pane" class="col-sm-12">Story Title</div>
        <div id="text-pane" class="col-sm-8">
            <div id="text-window">
                LOREM IPSUM BLAH BLAH BLAH BLAH BLAH BLAH BLAH BLAH BLAH BLAH BLAH BLAH BLAH BLAH BLAH BLAH
            </div>
        </div>
        <div id="choice-pane" class="col-sm-4">
            <button type="button" role="button" class="btn btn-lg btn-primary col-sm-12 btn-choice" id="option4">Option 1</button>
            <button type="button" role="button" class="btn btn-lg btn-primary col-sm-12 btn-choice" id="option2">Option 2</button>
            <button type="button" role="button" class="btn btn-lg btn-primary col-sm-12 btn-choice" id="option3">Option 3</button>
            <button type="button" role="button" class="btn btn-lg btn-primary col-sm-12 btn-choice" id="option4">Option 4</button>
            <button type="button" role="button" class="btn btn-lg btn-danger col-sm-6 col-sm-offset-3 btn-choice" id ="reset_button">Reset</button>
        </div>
    </div>
</div>
<script>
// Bind the script events for the various buttons
</script>
-->
<?php require 'footer.php'; ?>
</html>