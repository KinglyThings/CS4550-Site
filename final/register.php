<?php

session_start();

// If the user is already logged in, they obviously don't need to register
// Redirect them to the home page
if (isset($_SESSION['logged_in'])) {
    $redirect_url = "http://www.kinglythings.com/final/";
    header("Location: " . $redirect_url);
    exit;
} else {
    // User is not already logged in
    // Check if they have already submitted the form
    // If they have and they're still here, that means they have errors
    $revisit = false;
    if (isset($_POST['submit'])) {
        // The user has submitted the form
        $revisit = true;

        // Initialize the error and problems arrays
        $errors = array();
        $problems = array();

        // Validate that the necessary components are there
        if (empty($_POST['username'])) {
            $errors[] = "Please enter a desired username.";
            $problems[] = "register_username";
        }

        if (empty($_POST['password'])) {
            $errors[] = "Please enter a desired password.";
            $problems[] = "register_password";
        }

        if (empty($_POST['confirm_password'])) {
            $errors[] = "Please confirm your password choice.";
            $problems[] = "register_confirm_password";
        }

        if (empty($_POST['email'])) {
            $errors[] = "Please enter an email address.";
            $problems[] = "register_email";
        }

        // Validate that the necessary components are proper input

        // Basic filtering on input
        $username = trim(stripslashes(htmlspecialchars($_POST['username'])));
        $password = trim(stripslashes(htmlspecialchars($_POST['password'])));
        $confirm_password = trim(stripslashes(htmlspecialchars($_POST['confirm_password'])));
        $email = trim(stripslashes(htmlspecialchars($_POST['email'])));

        // Username validation (Avoidance of duplicate username occurs later to avoid unnecessary DB calls)
        if (!empty($_POST['username']) && preg_match("/^[a-zA-Z0-9_]+$/", $username) !== 1) {
            $errors[] = "The username you entered contains invalid characters. Valid characters are alphanumeric and underscore.";
            $problems[] = "register_username";
        } else if (!empty($_POST['username']) && strlen($username) < 5) {
            $errors[] = "The username you entered is too short. Usernames must be between 5 and 32 characters.";
            $problems[] = "register_username";
        } else if (!empty($_POST['username']) && strlen($username) > 32) {
            $errors[] = "The username you entered is too long. Usernames must be between 5 and 32 characters.";
            $problems[] = "register_username";
        }

        // Password validation
        if (!empty($_POST['password']) && preg_match("/^[a-zA-Z0-9]+$/", $password) !== 1) {
            // TODO: Add broader password rules
            $errors[] = "The password you provided contained invalid characters. Valid characters are alphanumeric.";
            $problems[] = "register_password";
        } else if (!empty($_POST['password']) && strlen($password) < 10) {
            $errors[] = "The password you provided is too short. Passwords must be at least 10 characters in length.";
            $problems[] = "register_password";
        } else if (!empty($_POST['password']) && strlen($password) > 128) {
            $errors[] = "The password you provided is too long. Passwords must be no more than 128 characters in length.";
            $problems[] = "register_password";
        } else if (!empty($_POST['password']) && preg_match("/[a-z]/", $password) !== 1) {
            $errors[] = "The password you entered does not contain a lowercase letter. Passwords must contain at least one lowercase letter.";
            $problems[] = "register_password";
        } else if (!empty($_POST['password']) && preg_match("/[A-Z]/", $password) !== 1) {
            $errors[] = "The password you entered does not contain an uppercase letter. Passwords must contain at least one uppercase letter.";
            $problems[] = "register_password";
        } else if (!empty($_POST['password']) && preg_match("/[0-9]/", $password) !== 1) {
            $errors[] = "The password you entered does not contain a digit. Passwords muts contain at least one digit.";
            $problems[] = "register_password";
        } else if (!empty($_POST['password']) && !empty($_POST['confirm_password']) && $password !== $confirm_password) {
            $errors[] = "The passwords you entered do not match.";
            $problems[] = "register_password";
            $problems[] = "register_confirm_password";
        }

        // Email validation
        if (!empty($_POST['email']) && preg_match("/^(.)+@(.)+(.)+/", $email) !== 1) {
            // This isn't REAL email validation, but filter_var doesn't want to work on iPage
            $errors[] = "The email address you entered is invalid.";
            $problems[] = "register_email";
        }

        // Validation done, let's open up the database if we don't have errors yet
        if (empty($errors)) {
            require_once('../mysqli_connect.php');

            $connect_query = "USE final;";
            $connect_result = @mysqli_query($final_dbc, $connect_query);

            // NOW validate that the username isn't already taken
            $username_check_query = "SELECT * FROM user WHERE username = '";
            $username_check_query .= mysqli_real_escape_string($final_dbc, $username) . "';";
            $username_check_result = @mysqli_query($final_dbc, $username_check_query);

            if (mysql_num_rows($username_check_result)) {
                // The username is already taken
                $errors[] = "The username you selected is not available.";
                $problems[] = "register_username";
            }

            // If there are still no errors, add the user to the database
            if (empty($errors)) {
                // TODO: Add salts
                $hashed_password = md5($password);
                $register_query = "INSERT INTO user (username, password, email) VALUES ('";
                $register_query .= mysqli_real_escape_string($final_dbc, $username) . "', '";
                $register_query .= mysqli_real_escape_string($final_dbc, $hashed_password) . "', '";
                $register_query .= mysqli_real_escape_string($final_dbc, $email) . "');";
                $register_result = @mysqli_query($final_dbc, $register_query);

                // If the registration is successful, email the user to let them know 
                // Also, start the session and redirect them
                if ($register_result) {
                    $from = "registerbot@kinglythings.com";
                    $to = $email;
                    $subject = "Welcome to StoryMaker!";
                    $message = "We hope you enjoy your time with StoryMaker!";
                    mail($to, $subject, $message, "From: " . $from . "\n");

                    // Initialize the sesion
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    // This create_date won't necessarily be exactly accurate, but it should be close enouugh
                    // for our purposes for the first session to avoid a DB call to grab it
                    $_SESSION['create_date'] = date("Y-m-d H:i:s");

                    // Let's grab the user id that was just created to put it into the session
                    $_SESSION['user_id'] = mysqli_insert_id($final_dbc);
                    
                    $_SESSION['logged_in'] = true;

                    // Redirect to the home page
                    $redirect_url = "http://www.kinglythings.com/final";
                    header("Location: " . $redirect_url);
                    exit;
                } else {
                    // The registration failed for some reason
                    // Alert the user to the failure
                    $errors[] = "The registration failed to process. Please try again later or contact our support team.";
                }
            }
        }

        // Remove duplicates from the problems array if there are problems
        // (If we reach this line, there are likely problems)
        $problems = array_unique($problems);

    } else {
        // The user is visiting this page for the first time
        // Make sure revisit is set to false
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

<!-- TODO: Add Ajax validation if it seems useful -->
<form class="form-horizontal" role="form" action="" method="post">
    <div id="register_username_group" class="form-group">
        <label for="register_username" class="col-sm-2 control-label">Desired Username</label>
        <div class="col-sm-2">
            <input type="text" name="username" class="form-control" id="register_username" placeholder="MattAttack443">
        </div>
    </div>
    <div id="register_password_group" class="form-group">
        <label for="register_password" class="col-sm-2 control-label">Password</label>
        <div class="col-sm-2">
            <input type="password" name="password" class="form-control" id="register_password" placeholder="password">
        </div>
    </div>
    <div id="register_confirm_password_group" class="form-group">
        <label for="register_confirm_password" class="col-sm-2 control-label">Confirm Password</label>
        <div class="col-sm-2">
            <input type="password" name="confirm_password" class="form-control" id="register_confirm_password" placeholder="password">
        </div>
    </div>
    <div id="register_email" class="form-group">
        <label for="register_email" class="col-sm-2 control-label">Email</label>
        <div class="col-sm-2">
            <input type="email" name="email" class="form-control" id="register_email" placeholder="matt@example.com">
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-2">
            <button type="submit" name="submit" class="btn btn-default">Register</button>
        </div>
    </div>
</form>

<button type="button" id="redirect-login" class="btn btn-primary btn-lg col-sm-offset-2">
    Already have an account? Click here to login!
</button>
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
    $('#redirect-login').on('click', function (e) {
        location.href = "http://www.kinglythings.com/final/login.php";
    });
</script>
<?php require 'footer.php'; ?>
</html>