<?php
// Start the session so we can destroy it
session_start();
// Destroy all session data — logs the user out completely
session_destroy();
// Send the user back to the login page
header("Location: login.php");
exit;
