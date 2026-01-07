<?php
session_start();
require 'database.php'; // Assume this file connects to the database securely

/**
 * Completes a task for the authenticated user.
 * 
 * @return void Sends JSON response with status
 */
function completeTask() {
    global $conn;

    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in to complete tasks.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $task_id = filter_input(INPUT_GET, 'task_id', FILTER_SANITIZE_NUMBER_INT);

    if (empty($task_id)) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Invalid task ID.']);
        exit;
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // Check if task exists
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();

        if (!$task) {
            $conn->rollBack();
            http_response_code(404); // Not Found
            echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
            exit;
        }

        // Check if the task has already been completed by this user
        $stmt = $conn->prepare("SELECT COUNT(*) FROM user_completed_tasks WHERE user_id = ? AND task_id = ?");
        $stmt->bind_param("ii", $user_id, $task_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];

        if ($count > 0) {
            $conn->rollBack();
            http_response_code(409); // Conflict
            echo json_encode(['status' => 'error', 'message' => 'Task already completed.']);
            exit;
        }

        // Mark task as completed for the user
        $stmt = $conn->prepare("INSERT INTO user_completed_tasks (user_id, task_id, completed_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $user_id, $task_id);
        $stmt->execute();

        // Update user points
        $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->bind_param("ii", $task['points'], $user_id);
        $stmt->execute();

        // Commit transaction if all operations are successful
        $conn->commit();

        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Task completed successfully!', 'points_earned' => $task['points']]);
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollBack();
        error_log("Error completing task: " . $e->getMessage());

        http_response_code(500); // Internal Server Error
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while processing your request.']);
    }
}

// Call the function when the script is accessed
completeTask();
?>