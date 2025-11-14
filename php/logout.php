<?php
// Start the session and unset the variables
session_start();
session_unset();

// Destroy the current session
session_destroy();

// Redirect to the index page
header('Location: ../index.php');

// Prevents any further execution of this script
exit();
?>