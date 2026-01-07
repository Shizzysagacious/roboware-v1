<?php
session_start();
require 'database.php'; // Assume this file connects to the database securely

/**
 * Fetches tasks from the database.
 *
 * @param mysqli $conn Database connection object
 * @return array List of tasks as associative arrays or an empty array if an error occurs
 */
function getTasks($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, name, description, points FROM tasks");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching tasks: " . $e->getMessage());
        return [];
    }
}

// Fetch tasks from database
global $conn; // Assuming $conn is set in database.php
$tasks = getTasks($conn);

// Check if user is logged in (you might want to implement this further)
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view tasks.");
}

// Display tasks
if (!empty($tasks)) {
    foreach ($tasks as $task) {
        echo "<div>";
        echo "<strong>" . htmlspecialchars($task['name']) . "</strong>: " . htmlspecialchars($task['description']) . " (Points: " . htmlspecialchars($task['points']) . ") ";
        echo "<a href='complete_task.php?task_id=" . htmlspecialchars($task['id']) . "'>Complete Task</a>";
        echo "</div>";
    }
} else {
    echo "<p>No tasks available at this time.</p>";
}
?>