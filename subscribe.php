<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: log.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle subscription when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Here you would implement actual payment processing logic
        // For this example, we'll simulate a successful payment

        // Duration of subscription in seconds (e.g., 1 month = 30 days)
        $duration = 30 * 24 * 60 * 60; // 30 days in seconds
        $expiration_time = date('Y-m-d H:i:s', strtotime('+' . $duration . ' seconds'));

        // Insert subscription into database
        $stmt = $conn->prepare("INSERT INTO chatbot_access (user_id, expiration_time) VALUES (?, ?) ON DUPLICATE KEY UPDATE expiration_time = ?");
        $stmt->bind_param("iss", $user_id, $expiration_time, $expiration_time);
        $stmt->execute();

        // Log subscription
        error_log("User {$user_id} subscribed to chatbot until {$expiration_time}.");

        // Redirect to success page
        header("Location: subscription_success.php");
        exit;
    } catch (Exception $e) {
        error_log("Subscription Error: " . $e->getMessage());
        header("Location: error.php?msg=An error occurred during subscription.");
        exit;
    }
}
?>
