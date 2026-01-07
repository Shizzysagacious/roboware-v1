<?php
session_start();
require_once 'config.php'; // Assuming config.php sets up $pdo

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, password, email, role FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Update last login time
            $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);

            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid username or password.";
            header("Location: log.html");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        $_SESSION['login_error'] = "An error occurred during login. Please try again.";
        header("Location: log.html");
        exit();
    }
}