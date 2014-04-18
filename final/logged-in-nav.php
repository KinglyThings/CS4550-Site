<?php

// This will be used for the logged-in header elements.
// This current consists of:
// - Profile
// - Make Story

// When we get here, the user will be logged in
// Make the My Profile link to the profile.php page that shows their profile
?>
<a href="http://www.kinglythings.com/final/new-story.php">
    <div id="new_story" class="col-offset-md-1 col-md-2">
        Make a Story
    </div>
</a>
<?php echo '<a href="http://www.kinglythings.com/final/profile.php?author_id=' . $_SESSION['user_id'] . '">'; ?>
    <div id="register" class="col-offset-md-1 col-md-2">
        My Profile
    </div>
</a>
<a href="http://www.kinglythings.com/final/help.php">
    <div id="register" class="col-offset-md-1 col-md-2">
        Help
    </div>
</a>
<a href="http://www.kinglythings.com/final/logout.php">
    <div id="logout" class="col-offset-md-1 col-md-2">
        Logout
    </div>
</a>