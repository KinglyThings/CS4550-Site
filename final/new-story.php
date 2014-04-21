<?php

// Grab the session
session_start();

// First off, let's check if the user is already logged in
// Users should not see the new story screen if they aren't already logged in
// Automatically redirect them to the homepage instead
if (!isset($_SESSION['logged_in'])) {
    $redirect_url = "http://www.kinglythings.com/final/";
    header("Location: " . $redirect_url);
    exit;
} else {
    // The user is logged in
    // Let's check if they've already submitted this form
    $revisit = false;
    if (isset($_POST['submit'])) {
        $revisit = true;
        // The user has submitted the form, let's validate it

        // Let's initialize the error arrays
        $errors = array();
        $problems = array();

        // Let's make sure all the elements we need are there
        if (empty($_POST['story_title'])) {
            $errors[] = "Your story must have a title.";
            $problems[] = "new_story_title";
        }

        if (empty($_POST['story_genre'])) {
            $errors[] = "Your story must have a genre.";
            $problems[] = "new_story_genre";
        }

        // Let's make sure that the RECAPTCHA is working correctly
        require_once('recaptchalib.php');
        $privatekey = "6LfrEfISAAAAAEFGJPNkjwX5FOgThc5ysyZNZKLW";
        $resp = recaptcha_check_answer($privatekey,
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);

        if (!$resp->is_valid) {
            // What happens when the CAPTCHA was entered incorrectly
            $errors[] = "The RECAPTCHA answer you provided was incorrect.";
        }

        // Ok, everything essential is here
        // Let's clean it up and then validate it further
        $title = trim(stripslashes(htmlspecialchars($_POST['story_title'])));
        $genre = trim(stripslashes(htmlspecialchars($_POST['story_genre'])));
        $private = false;
        if (!empty($_POST['story_private'])) {
            $temp = trim(stripslashes(htmlspecialchars($_POST['story_private'])));
            if ($temp === "on") {
                $private = true;
            }
        }
        // Title Validation
        if (!empty($_POST['story_title']) && preg_match("/^[a-zA-Z0-9_: ,]+$/", $title) !== 1) {
            $errors[] = "Your story title contains illegal characters.";
            $problems[] = "new_story_title";
        } else if (!empty($_POST['story_title']) && strlen($title) > 64) {
            $errors[] = "Your story title is too long. Valid titles are between 2 and 64 characters in length.";
            $problems[] = "new_story_title";
        } else if (!empty($_POST['story_title']) && strlen($title) < 2) {
            // This shouldn't be a problem for now, but I'll keep it in case the minimum story length increases
            $errors[] = "Your story title is too short. Valid titles are between 2 and 64 characers in length.";
            $problems[] = "new_story_title";
        }

        // Genre validation
        $valid_genres = array('adventure', 'comedy', 'drama', 'mystery', 'other', 'science fiction', 'non-fiction' ,'fantasy', 'romance');
        if (!empty($_POST['story_genre']) && !in_array($genre, $valid_genres)) {
            $errors[] = "You selected an invalid story genre. (How did you even do that?)";
            $problems[] = "new_story_genre";
        }

        // One last check - we need a user to create a story, so check that there is a username
        if (empty($_SESSION['username'])) {
            $errors[] = "You don't seem to be logged in. Log in and try again.";
        }

        $username = trim(stripslashes(htmlspecialchars($_SESSION['username'])));
        
        // Ok, all the data we need is there, let's start hitting the database if there are no errors yet
        if (empty($errors)) {
            require_once('../mysqli_connect.php');

            $connect_query = "USE final;";
            $connect_result = @mysqli_query($final_dbc, $connect_query);

            // First, check to make sure that we've got a valid username
            $check_username_query = "SELECT * FROM user WHERE username = '";
            $check_username_query .= mysqli_real_escape_string($final_dbc, $username) . "';";
            $check_username_result = @mysqli_query($final_dbc, $check_username_query);

            if (mysqli_num_rows($check_username_result)) {
                // The username is valid
            } else {
                // The username is invalid
                $errors[] = "Your username is invalid. Try logging out and logging back in to resolve the issue.";

            }

            if (empty($errors)) {
                // The username is valid, let's create the story
                $create_query = "INSERT INTO story (title, author_id, genre, private) VALUES (";
                $create_query .= mysqli_real_escape_string($final_dbc, $title) . ", (SELECT user_id FROM user WHERE username = ";
                $create_query .= mysqli_real_escape_string($final_dbc, $username) . "), ";
                $create_query .= mysqli_real_escape_string($final_dbc, $genre) . ", ";
                if ($private) {
                    $create_query .= "1);";
                } else {
                    $create_query .= "0);";
                }

                $create_result = @mysqli_query($final_dbc, $create_query);

                if ($create_result) {
                    // The story creation was a success
                    // Go to the story edit page

                    // Grab the ID of the story that we just created (so we know what story to open)
                    $story_id = mysqli_insert_id($final_dbc);
                    $redirect_url = "http://www.kinglythings.com/final/edit-story.php?story_id=" . $story_id;
                    header("Location: " . $redirect_url);
                    exit;
                } else {
                    $errors[] = "There was a problem with the story creation process. Plesae try again later or contact our support team.";
                }
            }

        }
    } else {
        // They have not submitted the form yet
        $revisit = false;
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
<form class="form-horizontal" role="form" action="" method="post">
    <div id="new_story_title_group" class="form-group">
        <label for="new_story_title" class="col-sm-2 control-label">Story Title</label>
        <div class="col-sm-2">
            <input type="text" name="story_title" class="form-control" id="new_story_title" placeholder="A Tale of Two Cities">
        </div>
    </div>
    <div id="new_story_genre_group" class="form-group">
        <label for="new_story_genre" class="col-sm-2 control-label">Genre</label>
        <div class="col-sm-2">
            <select class="form-control" name="story_genre" id="new_story_genre">
                <option value="adventure" selected="selected">adventure</option>
                <option value="drama">drama</option>
                <option value="comedy">comedy</option>
                <option value="romance">romance</option>
                <option value="mystery">mystery</option>
                <option value="fantasy">fantasy</option>
                <option value="science fiction">science fiction</option>
                <option value="non-fiction">non-fiction</option>
                <option value="other">other</option>
            </select>
        </div>
    </div>
    <div id="new_story_private_group" class="form-group">
        <label for="new_story_private" class="col-sm-2 control-label">Make this story private?</label>
        <div class="col-sm-2">
            <input type="checkbox" name="story_private" id="new_story_private">
        </div>
    </div>
    <?php 
        // Recaptcha Code
        require_once('recaptchalib.php');
        $publickey = "6LfrEfISAAAAAFlJ4tENrMcHBhB_rMsDgwdonsuP";
        echo recaptcha_get_html($publickey);
    ?>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-2">
            <button type="submit" name="submit" class="btn btn-primary btn-lg">Begin Writing!</button>
        </div>
    </div>
</form>
<?php
    // Highlight the input fields that need correction
    if ($revisit && !empty($problems)) {
        echo '<script>';
        foreach ($problems as $key) {
            echo "$('#" . $key . "_group').addClass('has-error');";
            echo "$('#" . $key . "').on('change', function(e) { $('#" . $key . "_group').removeClass('has-error'); });";
        }
        echo '</script>';
    }
?>
<script>
    $('#recaptcha_widget_div').addClass('col-sm-offset-2');
</script>
<?php require 'footer.php'; ?>
</html>