<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: log.html");
    exit();
}

// Assuming we have a function to fetch user details or we fetched them during login
$userDetails = getUserDetails($_SESSION['user_id']);

// Redirect to login if user details couldn't be fetched
if (!$userDetails) {
    session_destroy();
    header("Location: log.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="hi.css">
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($userDetails['username']); ?></h1>
    <p>You are now logged in.</p>
    <p>Email: <?php echo htmlspecialchars($userDetails['email']); ?></p>
</body>
</html>

<?php
// Hypothetical function to fetch user details from the database
function getUserDetails($user_id) {
    $conn = new mysqli("localhost", "robo_user", "E4sT!2nD#", "roboware_db");
    if ($conn->connect_error) {
        error_log("Failed to connect to MySQL: " . $conn->connect_error);
        return false;
    }

    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();

    return $user;
}
?>