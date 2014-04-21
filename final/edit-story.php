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

        

        // Validate the body text
        if (isset($_POST['text'])) {
            $test = trim(stripslashes($_POST['text']));
        } else {
            $errors[] = "A choice MUST have text associated with it.";
        }

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
            }

            // If there are no errors, run the first check to see if the card_id is a valid card
            // If it is, store the card info in the $card array
            // Otherwise, error
            if (empty($errors)) {
                $card_query = "SELECT * FROM card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
                $card_query .= " AND card_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
                $card_result = @mysqli_query($final_dbc, $card_query);

                if (mysqli_num_rows($card_result)) {
                    $card = mysqli_fetch_array($card_result);
                } else {
                    $errors[] = "Invalid card to update";
                }
            }

            // If there are no errors, run the second check to see if the parent_id is a valid card
            // If it is, store the card info in the $parent array
            // Otherwise, erro
            if (empty($errors)) {
                $parent_query = "SELECT * FROM card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
                $parent_query .= " AND card_id = ". mysqli_real_escape_string($final_dbc, $parent_id) . ";";
                $parent_result = @mysqli_query($final_dbc, $parent_query);

                if (mysqli_num_rows($final_dbc, $parent_result)) {
                    $parent = mysqli_fetch_array($parent_result);
                } else {
                    $errors[] = "Invalid parent to update";
                }
            }

            // If there are no errors, confirm that the given parent is a parent of the given card
            if (empty($errors)) {
                $is_parent_query = "SELECT * FROM parentmap WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
                $is_parent_query .= " AND parent_id = " . mysqli_real_escape_string($final_dbc, $parent_id);
                $is_parent_query .= " AND child_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
                $is_parent_result = @mysqli_query($final_dbc, $is_parent_query);

                if (!mysqli_num_rows($is_parent_result)) {
                    $errors[] = "The given parent is not really a parent of the given child.";
                }
            }

            // If there are still no errors, confirm that the given child is a valid choice of the given parent
            if (empty($errors)) {
                $is_choice_query = "SELECT * FROM cardmap WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
                $is_choice_query .= " AND card_id = " . mysqli_real_escape_string($final_dbc, $parent_id);
                $is_choice_query .= " AND choice_id = " . mysqli_real_escape_string($final_dbc, $card_id);
                $is_choice_result = @mysqli_query($final_dbc, $is_choice_query);

                if (!mysqli_num_rows($is_choice_result)) {
                    $errors[] = "The given card is not a valid choice of its given parent.";
                }
            }

            // If there are no errors yet, validate the choice text that leads to this card
            // (This is done in this block because for first cards, we cannot do this)
            if (empty($errors)) {
                // Validate the choice text
                if (isset($_POST['choice_text'])) {
                    $choice_text = trim(stripslashes($_POST['choice_text']));
                } else {
                    $errors[] = "A choice must have text associated with it!";
                }
            }
            // If we reach this point without errors, the card and parent id are valid and we have valid changes to make
            // We will need to:
            // 1.) Update the card text in the card table
            // 2.) Update the choice text in cardmap table
            if (empty($errors)) {
                $update_queries = array();
                $update_queries[] = "UPDATE card SET text = " . mysqli_real_escape_string($final_dbc, $text) . " WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . " AND card_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
                $update_queries[] = "UPDATE cardmap SET choice_text = " . mysqli_real_escape_string($final_dbc, $choice_text) . " WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . " AND card_id = " . mysqli_real_escape_string($final_dbc, $parent_id) . " AND choice_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";

                // Exceute all of the update queries
                foreach ($update_queries as $query) {
                    $result = @mysqli_query($final_dbc, $query);
                    if (!$result) {
                        $errors[] = "There was an error with the update process. Please try again later.";
                    }
                }

                // If there are still no errors after performing all the updates, reload the page with the new information
                // TODO: Do this by AJAX
                $redirect_url = "http://www.kinglythings.com/final/edit-story.php?story_id=" . htmlentities($story_id);
                $redirect_url .= "&card_id=" . htmlentities($card_id) . "&parent_id=" . htmlentities($parent_id);
                header("Location: " . $redirect_url);
                exit;
            }
        } else if (empty($errors) && isset($_POST['card_id'])) {
            // We have a card id, but we have to validate that it has a parent or is the first card
            $card_id = trim(stripslashes(htmlspecialchars($_POST['card_id'])));

            $parent_query = "SELECT * FROM parentmap WHERE child_id = " . mysqli_real_escape_string($final_dbc, $card_id);
            $parent_query .= " AND story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ";";
            $parent_result = @mysqli_query($final_dbc, $parent_query);
            
            $is_first_card = false;

            if (mysqli_num_rows($parent_result)) {
                // Store the result into the $parent array
                $row = mysqli_fetch_array($parent_result);
                $parent_id = $row['parent_id'];
            } else {
                // There is no parent to this card
                // If it is the first card, all is well
                // If not, error
                $first_card_query = "SELECT * FROM first_card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ';';
                $first_card_result = @mysqli_query($final_dbc, $first_card_query);
                

                if (mysqli_num_rows($parent_result)) {
                    // This is the first card, all is well
                    $is_first_card = true;
                } else {
                    // This is not the first card and it has no parent, but it exists. This is impossible.
                    $errors[] = "Impossible card variant.";
                }
            }

            // If we have no errors, we now have a valid card+parent combo, or a valid card that is the first card
            // as well as valid input
            // Update the database accordingly
            if (empty($errors) && $is_first_card) {
                // We're updating the first card
                // We just need to change the card database
                $update_query = "UPDATE card SET text = " . mysqli_real_escape_string($final_dbc, $text) . ";";
                $update_result = @mysqli_query($final_dbc, $update_query);

                if (!$result) {
                    $errors[] = "There was an error updating the first card in the story.";
                }

                if (empty($errors)) {
                    $redirect_url = "http://www.kinglythings.com/final/edit-story.php?story_id=" . htmlentities($story_id);
                    header("Location: " . $redirect_url);
                    exit;
                }
            } else if (empty($errors) && !$is_first_card) {
                // We're updating a child card
                // We need to update all the things

                // Validate the choice text
                if (isset($_POST['choice_text'])) {
                    $choice_text = trim(stripslashes($_POST['choice_text']));
                } else {
                    $errors[] = "A choice must have text associated with it!";
                }

                if (empty($errors)) {
                    $update_queries = array();
                    $update_queries[] = "UPDATE card SET text = " . mysqli_real_escape_string($final_dbc, $text) . " WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . " AND card_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
                    $update_queries[] = "UPDATE cardmap SET choice_text = " . mysqli_real_escape_string($final_dbc, $choice_text) . " WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . " AND card_id = " . mysqli_real_escape_string($final_dbc, $parent_id) . " AND choice_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";

                    foreach ($update_queries as $query) {
                        $result = @mysqli_query($final_dbc, $query);
                        if (!$result) {
                            $errors[] = "There was a problem with the update process for this existing card with an existing parent, but no parent id initially provided";
                        }
                    }
                }

                if (empty($errors)) {
                    // If the update was successfully, reload the page with a get request
                    $redirect_url = "http://www.kinglythings.com/final/edit-story.php?story_id=" . htmlentities($story_id);
                    header("Location: " . $redirect_url);
                    exit;
                }
            }
        } else if (empty($errors) && isset($_POST['parent_id'])) {
            // This is a new card being created for the parent
            $parent_id = trim(stripslashes(htmlspecialchars($_POST['parent_id'])));

            // Ensure the parent ID is a real card
            $check_parent_query = "SELECT * FROM card where story_id = " . mysqli_real_escape_string($final_dbc, $story_id);
            $check_parent_query .= " AND card_id = " . mysqli_real_escape_string($final_dbc, $parent_id) . ";";
            $check_parent_result = @mysqli_query($final_dbc, $check_parent_query);

            if (mysqli_num_rows($check_parent_result)) {
                $parent = mysqli_fetch_array($check_parent_result);
            } else {
                $errors[] = "The card cannot be created because the parent provided was invalid.";
            }

            // Validate the choice text
            if (isset($_POST['choice_text'])) {
                $choice_text = trim(stripslashes($_POST['choice_text']));
            } else {
                $errors[] = "A choice must have text associated with it!";
            }

            // If there are no errors, create the card
            if (empty($errors)) {
                // TODO: Do all of these queries in one transaction so that it can be rolled back
                $insert_queries = array();
                $create_query = "INSERT INTO card (story_id, text) VALUES ( " . mysqli_real_escape_string($final_dbc, $story_id) . ", " . mysqli_real_escape_string($final_dbc, $text) . ");";
                // Since we need the card id to do everything else, create this first and then use the resuling insert id
                $create_result = @mysqli_query($final_dbc, $create_query);

                if ($create_result) {
                    $card_id = mysqli_insert_id($final_dbc);
                } else {
                    $errors[] = "Unable to create card. Please try again later.";
                }
            }

            if (empty($errors)) {
                // The card is now created, let's add everything else
                $insert_queries[] = "INSERT INTO parentmap (story_id, parent_id, child_id) VALUES (" . mysqli_real_escape_string($final_dbc, $story_id) . ", " . mysqli_real_escape_string($final_dbc, $parent_id) . ", " . mysqli_real_escape_string($final_dbc, $card_id) . ");";
                $insert_queries[] = "INSERT INTO cardmap (story_id, card_id, choice_id, choice_text) VALUES (" . mysqli_real_escape_string($final_dbc, $story_id) . ", " . mysqli_real_escape_string($final_dbc, $parent_id) . ", " . mysqli_real_escape_string($final_dbc, $card_id) . ", " . mysqli_real_escape_string($final_dbc, $choice_text) . ");";

                // Run the queries
                foreach($insert_queries as $query) {
                    $result = @mysqli_query($final_dbc, $query);
                    if (!$result) {
                        $errors[] = "Something went wrong with the meta-information insertion"
                    }
                }
            }

            // Now that everything has been added, reload the page if there are no errors
            if (empty($errors)) {
                $redirect_url = "http://www.kinglythings.com/final/edit-story.php?story_id=" . htmlentities($story_id);
                $redirect_url .= "&card_id=" . htmlentities($card_id) . "&parent_id=" . htmlentities($parent_id);
                header("Location: " . $redirect_url);
                exit;
            }

        } else if (empty($errors)) {
            // We have no card id or parent id
            // We need to make this the first card in the story
            // First, validate that there are no cards in this story
            $empty_story_query = "SELECT * FROM card WHERE story_id = " . mysql_real_escape_string($final_dbc, $story_id) . ";";
            $empty_story_result = @mysqli_query($final_dbc, $empty_story_query);

            // If we get any results, then this is bad
            if (mysqli_num_rows($empty_story_result)) {
                $errors[] = "Your story has come unglued. God help you.";
            }

            // If there are still no errors, then this will be the first card in the story
            // Choice text is irrelvant, so add the card are add it to the first_card table
            if (empty($errors)) {
                $insert_query = "INSERT INTO card (story_id, text) VALUES (" . mysqli_real_escape_string($final_dbc, $story_id) . ", " . mysqli_real_escape_string($final_dbc, $text) . ");";
                $insert_result = @mysqli_query($final_dbc, $insert_query);

                if ($insert_result) {
                    $card_id = mysqli_insert_id($final_dbc);
                } else {
                    $errors[] = "Unable to insert the first card in this story. Please try again later.";
                }
            }

            // Now, all that's left is to add this to the first_card database table
            if (empty($errors)) {
                $insert_query = "INSERT INTO first_card (story_id, card_id) VALUES (" . mysqli_real_escape_string($final_dbc, $story_id) . ", " . mysqli_real_escape_string($final_dbc, $card_id) . ");";
                $insert_result = @mysqli_query($final_dbc, $insert_query);

                if (!$insert_result) {
                    $errors[] = "Unable to set this card as the first card in the story";
                }
            }

            // If there are still no errors, redirect to this page
            if (empty($errors)) {
                $redirect_url = "http://www.kinglythings.com/final/edit-story.php?story_id=" . htmlentities($story_id) . "&card_id=" . htmlentities($card_id);
                header("Location: " . $redirect_url);
                exit;
            }

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
        $card_id = trim(stripslashes(htmlspecialchars($_POST['card_id'])));

        // Here are the things we need to delete for the card
        // 1.) The actual card from the card table (found through card_id)
        // 2.) All references in the parentmap table where this card is the child of another card
        //     - It no longer exists, it can no longer be a child
        // 3.) All references in the parentmap table where this card is the parent of another card
        //     - It no longer exists, it can no longer be a parent
        // 4.) All references in the cardmap table where this card is the parent of another card
        //     - This card no longer has any choices that lead from it because it no longer exists
        // 5.) All references in the cardmap table where this card is a choice of another card
        //     - This card no longer exists, it can no longer be a valid choice
        // 6.) All references in the first_card table (there will be exactly one reference if this is the first card of a story)
        //     - This card no longer exists, it can no longer be a first card of a story
        //     - If we are deleting the first card of a story, that means there are no more cards in the story
        //     - In this event, delete ALL cards from the story


        // OK, first, we need to check if this card is the first card in the story
        $first_card_query = "SELECT * FROM first_card WHERE first_card_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
        $first_card_result = @mysqli_query($final_dbc, $first_card_query);
        $delete_queries = array();
        if (mysqli_num_rows($first_card_result)) {
            // This is the first card, delete everything

            $delete_queries[] = "DELETE FROM card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ";";
            $delete_queries[] = "DELETE FROM parentmap WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ";";
            $delete_queries[] = "DELETE FROM cardmap WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ";";
            $delete_queries[] = "DELETE FROM first_card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ";";
        } else {
            // This is not the first card, only delete things relating to this card
            $delete_queries[] = "DELETE FROM card WHERE story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . " AND card_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ";";
            $delete_queries[] = "DELETE FROM parentmap WHERE (story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ") AND ( child_id = " . mysqli_real_escape_string($final_dbc, $card_id) . " OR parent_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ");";
            $delete_queries[] = "DELETE FROM cardmap WHERE (story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ") AND ( card_id = " . mysqli_real_escape_string($final_dbc, $card_id) . " OR choice_id = " . mysqli_real_escape_string($final_dbc, $card_id) . ");";
            
            // Since this is not the first card, it must have a parent
            // Find that parent and redirect to it upon completion of the delete
            if (isset($_POST['parent_id'])) {
                $parent_id = trim(stripslashes(htmlspecialchars($_POST['parent_id'])));
            } else {
                $find_parent_query = "SELECT * FROM parentmap WHERE child_id = " . mysqli_real_escape_string($final_dbc, $card_id) . " AND story_id = " . mysqli_real_escape_string($final_dbc, $story_id) . ";";
                $find_parent_result = @mysqli_query($final_dbc, $find_parent_query);

                if (mysqli_num_rows($find_parent_result)) {
                    // We found the parent id
                    $parent = mysqli_fetch_array($find_parent_result);
                    $parent_id = $parent['parent_id'];
                } else {
                    $errors[] = "Could not find this parented card's parent for redirection purposes. Please try again later.";
                }
            }
            if (empty($errors)) {
                $redirect_url .= "&card_id=" . htmlentities($parent_id);
            }
        }

        // Now that we know all of the delete queries we will need to run, run each of them
        foreach($delete_queries as $query) {
            $result = @mysqli_query($final_dbc, $query);
            if (!$result) {
                $errors[] = "Something went wrong with the deletion process. Please try again later.";
            }
        }

        // Now, the delete is done. If there are still no errors, redirect to the parent card if it exists
        // If the parent card does not exist, redirect to the first page of the story
        header("Location: " . $redirect_url);
        exit;
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
    echo '<form class="form-horizontal" role="form" action="" method="post" accept-charset="UTF-8">';

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
    echo '<div id="edit_story_choice_text_group" class="form-group">';
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