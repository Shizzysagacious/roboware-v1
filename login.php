<?php
session_start(); // Start the session

// Database connection details using environment variables with fallbacks
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'roboware_db';
$username = getenv('DB_USERNAME') ?: 'robo_user';
$password = getenv('DB_PASSWORD') ?: 'E4sT!2nD';

// Connect to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Do not sanitize password here

    // SQL to check if user exists
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Password is correct, start a session or set cookie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $email;
            
            // Redirect to developers-tools-verified.html
            header("Location: developers-tools-verified.html");
            exit();
        } else {
            // Incorrect password
            echo "<script>alert('Incorrect password. Please try again.'); window.history.back();</script>";
        }
    } else {
        // User does not exist
        echo "<script>alert('User not found. Please check your email or sign up.'); window.history.back();</script>";
    }
    $stmt->close();
} else {
    // If not POST, redirect back to signin page
    echo "<script>alert('Invalid request method.'); window.history.back();</script>";
}

// Close the database connection
$conn->close();
?>