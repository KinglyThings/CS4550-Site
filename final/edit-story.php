<?php

// Grab the session
session_start();

// Only let the user edit a story under two conditions:
// 1.) The user is logged in
// 2.) The user is logged in as the author of the story that they are trying to edit
$allowed_to_edit = false;
$story_id = -1;
$card_id = -1;
$parent_id = -1;
$story = array();
$card = array();
$parent = array();
$choices = array();
$error = "";
$new = false;

// Base error cases
if(!isset($_SESSION['logged_in'])) {
    // Only logged in users can edit stories, so redirect to the login page
    $redirect_url = "http://www.kinglythings.com/final/login.php";
    header("Location: " . $redirect_url);
    exit;
} else if (!isset($_GET['story_id'])) {
    // We can't edit a card without know which story it is in
    $error = "NO STORY ID";
}

if(isset($_SESSION['logged_in']) && isset($_GET['story_id']) && !isset($_POST['submit'])) {
    // Run a database query to check if the user is allowed to edit the current story
    require_once('../mysqli_connect.php');

    // Connect to the database
    $connect_query = "USE final;";
    $connect_result = @mysqli_query($final_dbc, $connect_query);

    $story_id = trim(stripslashes(htmlspecialchars($_GET['story_id'])));

    $author_id = trim(stripslashes(htmlspecialchars($_SESSION['user_id'])));

    // Now that we're connected to the database, we need to check
    // if the user in the session is the author of the story, and
    // if the story in question is editable
    $editable_query = "SELECT * from story WHERE story_id = ";
    $editable_query .= mysqli_real_escape_string($final_dbc, $story_id);
    $editable_query .= " AND author_id = " . mysqli_real_escape_string($final_dbc, $author_id);
    $editable_query .= " AND editable = 1;"
    $editable_result = @mysqli_query($final_dbc, $editable_query);

    // If we get a result, then the story is editable by this user
    if (mysqli_num_rows($editable_result)) {
        $allowed_to_edit = true;
    }
} else {
    // This user is not allowed to edit or the link is broken
    $allowed_to_edit = false;

    // TODO: Define a list of error codes and use integers for simplicity
    $error = "NOT ALLOWED TO EDIT";
}

// If the user is allowed to edit, let's get ready to start editing
// We edit one card at a time. If we have a card_id in the $_GET, we'll edit that card
// Otherwise, if we have a parent_id but no card_id, we need to open a blank card
// Otherwise,  we have no parent or card id. Check what the first card of the given story is in the database
// 
// If there is no first card in the story, just open the generic edit page
//    and set the card_id to null 
if ($allowed_to_edit) {
    if (isset($_GET['card_id'])) {
        // We have a specific card we want to edit, and we must have a story_id if we reach
        // this conditional, so we're good to go.
        $story_id = trim(stripslashes(htmlspecialchars($_GET['story_id'])));
        $card_id = trim(stripslashes(htmlspecialchars($_GET['card_id'])));
        $card_query = "SELECT * FROM card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
        $card_query .= " AND card_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
        $card_result = @mysqli_query($final_dbc, $card_query);

        // If we get any results, let's load the result into this card
        if (mysqli_num_rows($card_result)) {
            $card = mysqli_fetch_array($card_result);
        } else {
            // There were no results, so this is not a valid card ID for this story ID
            $error = "THE GIVEN CARD DOES NOT EXIST FOR THE GIVEN STORY";
        }

        // Now, we know if we have a card to load. If we do, we also need to load the card's parent
        // (This is so we can show the user what the parent's text was, so they don't have to refer back)
        if ($error === "") {
            $parent_query = "SELECT * FROM card WHERE card_id = (SELECT parent_id FROM parent WHERE child_id = ";
            $parent_query .= mysqli_real_escape_string($final_dbc, $card_id) . ") AND story_id = " ;
            $parent_query .= mysqli_real_escape_string($final_dbc, $story_id) . ";";
            $parent_result = @mysqli_query($final_dbc, $parent_query);

            // IF we have a result, that means this child has a parent
            // If we do not have a result, that just means that this card is the parent of the entire story
            if (mysqli_num_rows($parent_result)) {
                $parent = mysqli_fetch_array($parent_result);
            }
        }

        // Now, since this card already exists, we need to check if it has choices already
        if ($error === "") {
            $choice_query = "SELECT * FROM cardmap WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
            $choice_query = " AND card_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
            $choice_result = @mysqli_query($final_dbc, $choice_query);

            if (mysqli_num_rows($choice_result)) {
                // Choices exist for this card, add them
                while ($row = mysql_fetch_array($choice_result)) {
                    $choices[] = $row;
                }
            }
        }

        // Ok, we now have everything we need to make our form for this existing card

    } else if (isset($_GET['parent_id'])) {
        // This means that we're making a brand new card, not editing an existing one
        // The parent id for this card will be the parent_id in the GET
        // First off, let's make sure we have a valid parent ID (we must have a valid story ID to reach this block)
        $parent_id = trim(stripslashes(htmlspecialchars($_GET['parent_id'])));
        $parent_check_query = "SELECT * FROM card WHERE card_id = " . mysqli_real_escape_string($final_dbc, $parent_id) . ";";
        $parent_check_result = @mysqli_query($final_dbc, $parent_check_query);

        // If we get a result, this is a valid parent id
        // Set the parent array equal to the result
        if (mysqli_num_rows($parent_check_result)) {
            $parent = mysqli_fetch_array($parent_check_result);
            $new = true;
        } else {
            // If we reach this block, then the parent id is invalid
            $error = "INVALID PARENT CARD";
        }
    } else {
        // If we get here, then the user is logged in and able to edit the story, but
        // no card or parent id is set.
        // Open up the first card in the story if possible, otherwise error
        $first_card_query = "SELECT first_card_id FROM first_card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ";";
        $first_card_result = @mysqli_query($final_dbc, $first_card_query);

        // If we have a result, open it
        // Otherwise, error
        if (mysqli_num_rows($first_card_result)) {
            $first_card = array();
            $first_card = mysqli_fetch_array($first_card_result);
            $card_id = $first_card['first_card_id'];

            $grab_card_query = "SELECT * FROM card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
            $grab_card_query .= " AND card_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
            $grab_card_result = @mysqli_query($final_dbc, $grab_card_query);

            if (mysqli_num_rows($grab_card_result)) {
                $card = mysqli_fetch_array($grab_card_result);
            } else {
                // There was a first card, but we can't find that card
                $error = "INVALID CARD ID";
            }
        } else {
            // There are no cards in the story. Open up a new card.
            $new = true;
        }
    }

    // Ok, we have the data-grabbing logic in place
    // Now, let's handle what happens if the form has been submitted.
    if (isset($_POST['submit'])) {
        
    }
}
?>
<!DOCTYPE html>
<html>
<?php require 'header.php'; ?>
<?php
// This is where we generate the form. IT needs to include:
// A link to edit the parent card
// The option text to display in the choice pane
// The text that this choice leads to.
// Links to edit the child cards

?>
<?php require 'footer.php'; ?>
</html>