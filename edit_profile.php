<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: log.html");
    exit();
}

require_once 'database.php';

$user_id = $_SESSION['user_id'];

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user data
try {
    $stmt = $conn->prepare("SELECT username, email, first_name, last_name, phone_number FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception("User data not found");
    }

    // Update user data if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token");
        }

        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
        $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
        $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check for username uniqueness
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->bind_param("si", $username, $user_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("Username already exists");
        }

        // Use prepared statement to update the database
        $sql = "UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, phone_number = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $username, $email, $first_name, $last_name, $phone_number, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();
        } else {
            throw new Exception("Error updating profile. Please try again later.");
        }
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: edit_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
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
    <h1>Edit Your Profile</h1>

    <?php 
    if (isset($_SESSION['error_message'])): 
        echo "<p class='message'>" . htmlspecialchars($_SESSION['error_message']) . "</p>";
        unset($_SESSION['error_message']);
    endif;

    if (isset($_SESSION['success_message'])): 
        echo "<p class='message' style='color: green;'>" . htmlspecialchars($_SESSION['success_message']) . "</p>";
        unset($_SESSION['success_message']);
    endif;
    ?>

    <form method="POST" action="edit_profile.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>

        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required><br>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required><br>

        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required><br>

        <button type="submit">Save Changes</button>
    </form>
</body>
</html>
