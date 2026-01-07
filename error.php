<?php
// Start session to maintain error message through redirects
session_start();

// Get error message from URL parameters
$error_message = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'An unknown error occurred.';

// Clear the session error message if it exists
if (isset($_SESSION['error_message'])) {
    $error_message = htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .error-container { text-align: center; margin-top: 50px; }
        .error-message { color: #D8000C; font-size: 18px; }
        a { color: #4CAF50; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Error</h1>
        <p class="error-message"><?php echo $error_message; ?></p>
        <p><a href="hi.html">Return to Homepage</a></p>
    </div>
</body>
</html>