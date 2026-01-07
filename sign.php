<?php
session_start();

// Use environment variables for sensitive data
$clientId = getenv('PAYPAL_CLIENT_ID') ?: 'your-fallback-client-id';
$clientSecret = getenv('PAYPAL_CLIENT_SECRET') ?: 'your-fallback-client-secret';
$redirectUri = getenv('REDIRECT_URI') ?: 'your-fallback-redirect-uri';

// Connect to database
require_once 'config.php'; // Assuming you have a config file with secure DB connection details

// Check connection
if (!$pdo) {
    http_response_code(500);
    error_log("Database Connection Error");
    die(json_encode(['error' => 'Database connection error. Please try again later.']));
}

// Validate user input
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);

    // Initialize error array for multiple errors
    $errors = [];

    // Validate username
    if (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = "Username must be between 3 and 20 characters.";
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    // Validate password
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        $errors[] = "Password must be at least 8 characters, contain uppercase, lowercase, numbers, and special characters.";
    }

    // Check password match
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already exists.";
        }

        // If no errors, proceed with registration
        if (empty($errors)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $initialPoints = 0;
            $initialLevel = 'Beginner';

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, points, user_level) VALUES (:username, :email, :password, :firstName, :lastName, :points, :level)");
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $passwordHash,
                ':firstName' => $firstName,
                ':lastName' => $lastName,
                ':points' => $initialPoints,
                ':level' => $initialLevel
            ]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Registration successful! Please log in.";
                header("Location: log.php");
                exit();
            } else {
                $errors[] = "Registration failed. Please try again later.";
            }
        }
    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        $errors[] = "An error occurred during registration. Please try again.";
    }

    // Store errors in session for displaying on the sign-up form
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: " . $_SERVER['HTTP_REFERER']); // Redirect back to the form to show errors
        exit();
    }
} else {
    // Not a POST request, might be a GET to show the form or an invalid request
    if (isset($_SESSION['errors'])) {
        $errors = $_SESSION['errors'];
        unset($_SESSION['errors']);
    } else {
        $errors = [];
    }
}

// If there's a GET request, you might want to show the form again with any error messages
if (!headers_sent()) {
    header("Location: sign.php"); // Redirect back to signup form
    exit();
}
?>