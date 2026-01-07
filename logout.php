<?php
// Start the session to ensure we can manipulate session data
session_start();

// Destroy all data registered to this session
session_destroy();

// Redirect the user to the login page
header('Location: log.html');
// Ensure script execution stops here to prevent further code from running
exit;
?>