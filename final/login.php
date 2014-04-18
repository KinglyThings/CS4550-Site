<?php 

// Let's make this form submit to itself

// First off, let's check if the user is already logged in
// Users should not see the login screen if they are already logged in
// Automatically redirect them to the homepage instead
if (isset($_SESSION['logged_in'])) {
    $redirect_url = "http://www.kinglythings.com/final/";
    header("Location: " . $redirect_url);
    exit;
} else {
    // Start by checking if the form has been submitted
    $revisit = false;
    if (isset($_POST['submit'])) {
        $revisit = true;
        // Initialize the errors array
        // $errors will contain the error message, while $problems will contain
        // the input ids to highlight if there is something wrong
        $errors = array();
        $problems = array();

        // Ok, this user has attempted to login. Begin validation

        // Check that there is a username submitted
        if (empty($_POST['username'])) {
            // There is no username, display appropriate error message
            $errors[] = "Please enter a username.";
            $problems[] = "login_username";
        }

        // Check that there is a password submitted
        if (empty($_POST['password'])) {
            // There is no password, display appropriate error message
            $errors[] = "Please enter a password.";
            $problems[] = "login_password";
        }

        // Now that everything is guaranteed to be here (even if incorrect), sanitize input
        $username = trim(stripslashes(htmlspecialchars($_POST['username'])));
        $password = trim(stripslashes(htmlspecialchars($_POST['password'])));

        // Use regular expression to validate the username and password are valid
        if ( !empty($_POST['username']) && preg_match("/^[a-zA-Z0-9_]+$/", $username) !== 1) {
            $errors[] = "The username you entered contains an invalid character.";
            $problems[] = "login_username";
        }

        if ( !empty($_POST['password']) && preg_match("/^[a-zA-Z0-9_]+$/", $password) !== 1) {
            $errors[] = "The password you entered contains an invalid character.";
            $problems[] = "login_password";
        }

        // Initial error checking is done
        // If there are no errors yet, check if this is a real, valid user in the database
        $user = array();

        if (empty($errors)) {
            // Connect to the database
            require_once('../mysqli_connect.php');

            $connect_query = "USE final;";
            $connect_result = @mysqli_query($final_dbc, $connect_query);

            // We will do a lookup by both username and a hash of the password
            $hashed_password = md5($password);

            // Create the login query
            $login_query = "SELECT * FROM user WHERE username = '";
            $login_query .= mysqli_real_escape_string($final_dbc, $username) . "' AND password = '";
            $login_query .= mysqli_real_escape_string($final_dbc, $hashed_password) . "';";

            // Check if the user is valid
            $login_result = @mysqli_query($final_dbc, $login_query);
            
            // If the user is valid, there will be one value returned by this query
            if (mysqli_num_rows($login_result)) {
                // This username/password combo is valid
                $user = mysqli_fetch_array($login_result, MYSQLI_ASSOC);
            } else {
                // This username/password combo is valid, so record the information
                $errors[] = "You provided an invalid username/password.";
                $problems[] = "login_username";
                $problems[] = "login_password";
            }
        }

        // Start the session
        if (empty($user)) {
            // The user didn't exist, skip this part
        } else {
            session_start();
            $_SESSION['username'] = $user['username'];
            $_SESSION['create_date'] = $user['create_date'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['logged_in'] = true;

        }
        // If there weren't errors, redirect to the index page
        if (empty($errors)) {
            $redirect_url = 'http://www.kinglythings.com/final/';
            header('Location: ' . $redirect_url);
            exit;
        }

        // Removing duplicate keys in problems (so we don't add the same css class five times)
        $problems = array_unique($problems);


    } else {
        // First time on the page
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
    <div id="login_username_group" class="form-group">
        <label for="login_username" class="col-sm-2 control-label">Username</label>
        <div class="col-sm-2">
            <input type="text" name="username" class="form-control" id="login_username" placeholder="MattAttack443">
        </div>
    </div>
    <div id="login_password_group" class="form-group">
        <label for="login_password" class="col-sm-2 control-label">Password</label>
        <div class="col-sm-2">
            <input type="password" name="password" class="form-control" id="login_password" placeholder="password">
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-2">
            <button type="submit" name="submit" class="btn btn-default">Log in</button>
        </div>
    </div>
</form>

<button type="button" id="redirect-register" class="btn btn-primary btn-lg col-sm-offset-2">
    Don't have an account? Click here to register!
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
    // The click handler for the button that redirects to the register page
    $('#redirect-register').on('click', function(e) {
        location.href = "http://www.kinglythings.com/final/register.php";
    });
</script>
<?php include 'footer.php'; ?>
</html>