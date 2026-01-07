<?php
// Start session to check for logged-in user
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: log.html");
    exit();
}

require_once 'database.php';

try {
    // Fetch user data from the database
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, username, first_name, last_name, email, phone_number, points, user_level FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        throw new Exception("User not found.");
    }

    // Fetch purchased codes
    $stmt = $conn->prepare("SELECT id, code_name, purchase_date FROM purchased_codes WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result_codes = $stmt->get_result();

    // Fetch active chatbot access
    $stmt = $conn->prepare("SELECT expiration_time FROM chatbot_access WHERE user_id = ? AND expiration_time > NOW()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $chatbot_access = $stmt->get_result()->fetch_assoc();

    // Fetch tasks dynamically from task.php
    ob_start();
    include 'task.php'; // This way we can use PHP from task.php directly
    $tasks_html = ob_get_clean();

} catch (Exception $e) {
    error_log("Profile Error: " . $e->getMessage());
    header("Location: error.php?msg=An error occurred while loading your profile.");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="styles/profile.css"> <!-- Use an external CSS file -->
</head>
<body>
    <header>
        <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
    </header>
    
    <main>
        <!-- User Details -->
        <h2>Profile Details</h2>
        <p><strong>First Name:</strong> <?php echo htmlspecialchars($user['first_name'] ?? 'Not Set'); ?></p>
        <p><strong>Last Name:</strong> <?php echo htmlspecialchars($user['last_name'] ?? 'Not Set'); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'Not Set'); ?></p>
        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($user['phone_number'] ?? 'Not Set'); ?></p>
        <p><strong>Points:</strong> <?php echo $user['points'] ?? 0; ?></p>
        <p><strong>Level:</strong> <?php echo $user['user_level'] ?? 'Beginner'; ?></p>

        <!-- Tasks Section -->
        <h2>Tasks</h2>
        <div id="tasks">
            <?php echo $tasks_html; // Display tasks fetched from task.php ?>
        </div>

        <!-- Purchases Section -->
        <h2>Your Purchased Codes</h2>
        <?php if ($result_codes->num_rows > 0): ?>
            <ul>
                <?php while ($code = $result_codes->fetch_assoc()): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($code['code_name']); ?></strong>
                        (Purchased on: <?php echo htmlspecialchars($code['purchase_date']); ?>) - 
                        <a href="download.php?code_id=<?php echo htmlspecialchars($code['id']); ?>">Download</a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No codes purchased yet.</p>
        <?php endif; ?>

        <!-- Chatbot Access Section -->
        <h2>Chatbot Access</h2>
        <?php if ($chatbot_access): ?>
            <p>Active until: <?php echo htmlspecialchars($chatbot_access['expiration_time']); ?></p>
        <?php else: ?>
            <p>No active chatbot access. <a href="subscribe.php">Subscribe Now</a></p>
        <?php endif; ?>
    </main>

    <footer>
        <p>© 2024 Roboware. All Rights Reserved.</p>
    </footer>

    <script src="scripts/profile.js"></script> <!-- Add scripts if needed -->
</body>
</html>