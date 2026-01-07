<?php
session_start();
require_once 'database.php'; // Assumes you have a secure database connection setup

// Function to generate a random token
function generateToken() {
    return bin2hex(random_bytes(16));
}

// Send password reset link
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $userId = $result->fetch_assoc()['id'];
            $token = generateToken();

            $stmt = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $stmt->bind_param("si", $token, $userId);
            $stmt->execute();

            // Here you would integrate with an email service for better reliability
            $to = $email;
            $subject = "Password Reset";
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . urlencode($token);
            $message = "Click this link to reset your password: " . $resetLink;
            mail($to, $subject, $message); // Use a proper mailing library in production

            $_SESSION['message'] = "Password reset link sent to your email.";
        } else {
            $_SESSION['error'] = "User not found.";
        }
    } catch (Exception $e) {
        error_log("Error in password recovery: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred. Please try again.";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'], $_POST['token'])) {
    $new_password = $_POST['new_password'];
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);

    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expiry > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $userId = $result->fetch_assoc()['id'];
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expiry = NULL WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            $stmt->execute();

            $_SESSION['message'] = "Password reset successful.";
        } else {
            $_SESSION['error'] = "Invalid or expired token.";
        }
    } catch (Exception $e) {
        error_log("Error in password reset: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred. Please try again.";
    }
    header('Location: login.php'); // Redirect to login page after password reset
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery</title>
    <style>
        body { font-family: Arial, sans-serif; }
        form { max-width: 500px; margin: 0 auto; }
        label { display: block; margin-top: 10px; }
        input { width: 100%; padding: 10px; margin-top: 5px; }
        button { margin-top: 15px; padding: 10px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .message { color: #D8000C; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Password Recovery</h1>

    <?php 
    if (isset($_SESSION['message'])): 
        echo "<p class='message' style='color: green;'>" . htmlspecialchars($_SESSION['message']) . "</p>";
        unset($_SESSION['message']);
    elseif (isset($_SESSION['error'])):
        echo "<p class='message'>" . htmlspecialchars($_SESSION['error']) . "</p>";
        unset($_SESSION['error']);
    endif;
    ?>

    <form action="password_recovery.php" method="post">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>
        <button type="submit">Send Reset Link</button>
    </form>

    <!-- This form would typically be on a different page or accessed via the reset link -->
    <form action="password_recovery.php" method="post">
        <label for="new_password">New Password:</label>
        <input type="password" id="new_password" name="new_password" required><br><br>
        <label for="token">Token:</label>
        <input type="hidden" id="token" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>