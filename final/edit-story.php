<?php

// Grab the session
session_start();

// Only let the user edit a story under two conditions:
// 1.) The user is logged in
// 2.) The user is logged in as the author of the story that they are trying to edit
$allowed_to_edit = false;
$story = array();
$card = array();
$parent = array();
$choices = array();
$error = "";
$new = false;
$revisit = false;

// This is a temporary measure until I've resolved the bug
header("Location: http://www.kinglythings.com/final/index.php");
exit;

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

// Connect to the database
require_once('../mysqli_connect.php');

// Connect to the database
$connect_query = "USE final;";
$connect_result = @mysqli_query($final_dbc, $connect_query);

if(isset($_SESSION['logged_in']) && isset($_GET['story_id']) && !isset($_POST['submit'])) {
    // Run a database query to check if the user is allowed to edit the current story
    

    $story_id = trim(stripslashes(htmlspecialchars($_GET['story_id'])));

    $author_id = trim(stripslashes(htmlspecialchars($_SESSION['user_id'])));

    // Now that we're connected to the database, we need to check
    // if the user in the session is the author of the story, and
    // if the story in question is editable
    $editable_query = "SELECT * from story WHERE story_id = ";
    $editable_query .= mysqli_real_escape_string($final_dbc, $story_id);
    $editable_query .= " AND author_id = " . mysqli_real_escape_string($final_dbc, $author_id);
    $editable_query .= " AND editable = 1;";
    $editable_result = @mysqli_query($final_dbc, $editable_query);

    // If we get a result, then the story is editable by this user
    if (mysqli_num_rows($editable_result)) {
        $allowed_to_edit = true;

        // Put the story information into the story array for later use
        $story = mysqli_fetch_array($editable_result);
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

        // Finally, since this card exists, if it has a parent, then grab the choice text that LEADS to it
        // Otherwise, there is no choice text that leads to it yet
        if ($error === "" && !empty($parent)) {
            $inverse_choice_query = "SELECT * FROM cardmap WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
            $inverse_choice_query .= " AND choice_id = " . mysqli_real_escape_string($final_dbc, $card_id) . " AND card_id = ";
            $inverse_choice_query .= mysqli_real_escape_string($final_dbc, $parent_id) . ";";
            $inverse_choice_result = @mysqli_query($final_dbc, $inverse_choice_query);

            if (mysqli_num_rows($inverse_choice_result)) {
                // We have a result, let's story it
                $inverse_choice = mysqli_fetch_array($inverse_choice_result);

                // All we need from this is the choice text
                $choice_text = $inverse_choice['choice_text'];
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
        // Open up the first card in the story if possible, otherwise start a new card that will be the first
        // card in the story
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
        $revisit = true;
        // We need a story id

        if (isset($_POST['story_id'])) {
            $story_id = trim(stripslashes(htmlspecialchars($_POST['story_id'])));

            // Check the database to make sure that this is a valid story
            $check_story_query = "SELECT * FROM story WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ";";
            $check_story_result = @mysqli_query($final_dbc, $check_story_query);

            if (mysqli_num_rows($check_story_result)) {
                // The story ID is valid, all is well
            } else {
                // The story ID is invalid
                $errors[] = "The story you are updating is invalid.";
            }
        } else {
            $errors[] = "The story you are updating does not exist.";
        }

        // Validation of the different form components (Text of this option, choice text)


        // If we have a card id AND a parent id, we'll use them both (but must validate that they are proper)
        // IF we have a card id ONLY, we will query for the parent ID
        // -  IF we find the parent ID in the database, all is well
        // -  IF we do not find a parent ID, we will query for the first card in this story
        //    -  IF we find that this card is the first card in this story, all is well
        //    -  IF we find that this is not the first card in this story, ERROR
        // IF we have a parent id ONLY, this means we are making a new card entirely
        // - Validate that the parent id is a real card in the same story as this story_id and all is well
        // IF we have neither, We are making the first card in this story (validate that this story has no first card)
        if (empty($errors) && isset($_POST['card_id']) && isset($_POST['parent_id'])) {
            $card_id = trim(stripslashes(htmlspecialchars($_POST['card_id'])));
            $parent_id = trim(stripslashes(htmlspecialchars($_POST['parent_id'])));

            // A card cannot be its own parent
            if ($card_id === $parent_id) {
                $errors[] = "This card is somehow its own parent. Recursion is unethical.";
            } else {

                // Check the database to ensure that these are valid
                // 1.) Card_id and parent_id both need to represent cards in the same story as story_id
                // 2.) There must be a row in the cardmap where parent_id and story_id map to card_id (Parent really is a parent)
                // 3.) There must be a row in the parentmap where parent_id is paired to card ID
                $check_query_one = "SELECT * FROM card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
                $check_query_one .= " AND card_id IN (" . mysqli_real_escape_string($final_dbc, $card_id) . ", ";
                $check_query_one .= mysqli_real_escape_string($final_dbc, $parent_id) . ");";
                $check_result_one = @mysqli_query($final_dbc, $check_query_one);

                // We need to get back EXACTLY two results
                if (mysqli_num_rows($check_result_one) === 2) {
                    // We've got a valid story ID and two valid card IDs, now let's make sure that
                    // the parent_id is really the parent of the card_id
                    $check_query_two = "SELECT * FROM cardmap WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
                    $check_query_two .= " AND card_id = " . mysqli_real_escape_string($final_dbc, $parent_id);
                    $check_query_two .= " AND choice_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
                    $check_result_two = @mysqli_query($final_dbc, $check_query_two);

                    // If we get a result back from this, then we just need to confirm that the parentmap row exists
                    if (mysqli_num_rows($check_result_two)) {
                        $check_query_three = "SELECT * FROM parentmap WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
                        $check_query_three .= " AND parent_id = " . mysqli_real_escape_string($final_dbc, $parent_id);
                        $check_query_three .= "AND child_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
                        $check_result_three = @mysqli_query($final_dbc, $check_query_three);

                        if (mysqli_num_rows($check_result_three)) {
                            // All is well, card id and parent id are fine, proceed with processing
                            // We need ()
                        } else {
                            $errors[] = "Improper parent card.";
                        }
                    } else {
                        $errors[] = "Improper parent card.";
                    }
                } else {
                    $errors[] = "Card ID not found";
                }
            }
        } else if (empty($errors) && isset($_POST['card_id'])) {
            // We have a card id, but we have to validate that it has a parent or is the first card
            $card_id = trim(stripslashes(htmlspecialchars($_POST['card_id'])));
        } else if (empty($errors) && isset($_POST['parent_id'])) {

            $parent_id = trim(stripslashes(htmlspecialchars($_POST['parent_id'])));
        } else {
            // We have no card id or parent id
            // We need to make this the first card in the story
            // First, validate that there are no cards in this story
            $empty_story_query = "SELECT * FROM card WHERE story_id = " . mysql_real_escape_string($final_dbc, $story_id) . ";";
            $empty_story_result = @mysqli_query($final_dbc, $empty_story_query);

            // If we get any results, then this is bad
            if (mysqli_num_rows($empty_story_result)) {
                $errors[] = "Your story has come unglued. God help you.";
            } else {
                // IF we're here, that means the story is empty and we're making the first entry. 
                // All is well
            }

            // If there are no errors yet, then this story is empty
        }
    } else if (isset($_POST['delete'])) {
        // If we're deleting the card, then things are easier
        // We know we're allowed to delete it, or we wouldn't reach this block
        // We just need the card ID to be able to delete this card
        // Afterwards, if we have a parent ID, redirect to the parent's page
        // Otherwise, if we have another card in the story, redirect to the first card in the story
        // If this the first and only card in the story, redirect to a blank edit page
        $redirect_url = "http://www.kinglythings.com/final/edit-story?story_id=" . htmlentities($story_id);

        // Only delete if we have a card ID
        if (!isset($_POST['card_id'])) {
            $errors[] = "The card could not be located to be deleted.";
        }

        // We need to determine the parent of this car
    }
}
?>
<!DOCTYPE html>
<html>
<?php require 'header.php'; ?>
<?php
    if ($revisit) {
        if (!empty($errors)) {

            // Log out the error message to an errors div
            echo '<div class="error-container">';
            foreach ($errors as $key) {
                echo '<span class="error">' . $key . '</span><br />';
            }
            echo '</div>';
        }
    }
?>
<?php
// This is where we generate the form. IT needs to include:
// A link to edit the parent card
// The option text to display in the choice pane
// The text that this choice leads to.
// Links to edit the child cards

// To start, only generate anything if there is no error with grabbing the story
if ($error === "") {
    // DO FORM STUFF

    // Start by generating the form and the hidden inputs
    // These will contain the story id, the card id (if applicable), and the parent id (if applicable)
    echo '<form class="form-horizontal" role="form" action="" method="post">';

    // If there's not a story_id, this page must have an error
    echo '<input type="hidden" name="story_id" value="' . htmlentities($story_id) . '">';

    // There may or may not be a card_id and parent_id
    // If a card id exists, add an input for it (proper validation will happen on form submission)
    // It must be a valid card id to get here in the first place, as otherwise an error will trigger
    if (isset($card_id) && $card_id !== "") {
        echo '<input type="hidden" name="card_id" value="' . htmlentities($card_id) . '">';
    }

    // If a parent id exists, add an input for it (proper validation will happen on form submission)
    if (isset($parent_id) && $parent_id !== "") {
        echo '<input type="idden" name="parent_id" value="' . htmlentities($parent_id) . '">';
    }

    // Show the story title at the top
    echo '<div class="container">';
    echo '<div class="row">';
    echo '<div id="title-pane" class="col-sm-12">' . htmlentities($story['title']) . '</div>';
    
    // Show the Text of the parent Card (if there is one)
    if (isset($parent_id)) {
        echo '<div id="parent-pane" class="col-sm-12">' . htmlentities($parent['text']) . '</div>';

        // Have a link to edit the parent card
        echo '<button type="button" role="button" id="edit-parent" class="btn btn-primary btn-lg">Edit Parent Card</button>';
        echo '<script> $("#edit-parent").on("click", function(e) { location.href = "http://www.kinglythings.com/final/edit-story.php?story_id=' . htmlentities($story_id) . '&card_id=' . htmlentities($parent_id) . '";});</script>';
    }

    // Show an option to edit the text of the choice that leads to this card, if it exists
    echo '<div id="edit_story_choice_text_group" class="form-group">'
    echo '<label for="edit_story_choice_text" class="col-sm-2 control-label">Choice Text: </label><div class="col-sm-4">';
    if (isset($card_id) && isset($parent_id) && isset($choice_text)) {
        echo '<input type="text" name="choice_text" class="form-control" id="edit_story_choice_text" placeholder="Enter the text that leads to this card here" maxlength="64" size="64" value=' . htmlentities($choice_text) . '>';
    } else {
        echo '<input type="text" name="choice_text" class="form-control" id="edit_story_choice_text" placeholder="Enter the text that leads to this card here" maxlength="64" size="64">';
    }
    echo '</div></div>';
    
    // Show An option to edit the text of THIS card
    echo '<div id="text-pane" class="col-sm-8"><div id="text-window">';
    echo '<div id="text_group" class="form-group">';
    if (isset($card_id) && !empty($card)) {
        echo '<textarea id="edit_story_text" name="text" class="form-control" placeholder="Enter story text here!">' . htmlentities($card['text']) . '</textarea>';
    } else {
        echo '<textarea id="edit_story_text" name="text" class="form-control" placeholder="Enter story text here!"></textarea>';
    }
    echo '</div></div></div>';

    // Show an option to delete THIS CARD (and all of its subcards)
    echo '<div class="form-group"><div class="col-sm-offset-2 col-sm-2>';
    echo '<button type="submit" name="delete" role="button" class="btn btn-danger btn-lg" id="delete-button">Delete This Card</button>';
    echo '</div></div>';

    // TODO: Add a script to force a confirmation dialog on deleting a card

    // For each of the possible choices from THIS card
    // - Show the Current choice Text
    // - Show a link to edit that card 
    // - Add a script to that link to confirm navigation because current data will be lost
    // - Add a link to delete this choice
    // - IF THERE ARE FEWER THAN FOUR CHOICES, add an option to add a new child card to this card

    // Spit out the button and end the form
    echo '<div class="form-group"><div class="col-sm-offset-2 col-sm-2">';
    echo '<button type="submit" name="submit" role="button" class="btn btn-primary btn-lg">';
    if ($new) {
        echo 'Add Card';
    } else {
        echo 'Save Your Changes';
    }
    echo '</button></div</div>';
    echo '</form>';

} else {
    echo '<div class="error-container"><span class="error">' . htmlentities($error) . '</span></div>';

}
?>
<?php require 'footer.php'; ?>
</html>