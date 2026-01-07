<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: log.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $thread_id = htmlspecialchars($_POST['thread_id']);
    $content = htmlspecialchars($_POST['comment']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO comments (thread_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$thread_id, $user_id, $content]);

    header("Location: community.html");
} else {
    // If the request is not POST, redirect to avoid direct access to this script
    header("Location: community.html");
}
?>