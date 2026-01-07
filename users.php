<?php
require_once 'database.php';

/**
 * Registers a new user with the provided credentials.
 * 
 * @param string $username The username chosen by the user
 * @param string $email The email address of the user
 * @param string $password The user's password in plain text
 * @return bool Returns true if user registration was successful, false otherwise
 * @throws Exception If there's a database error
 */
function registerUser($username, $email, $password) {
    global $conn; // Use global connection

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        return true;
    } else {
        throw new Exception("Error registering user: " . $stmt->error);
    }
}

/**
 * Attempts to log in a user with the provided credentials.
 * 
 * @param string $username The username of the user attempting to log in
 * @param string $password The password of the user in plain text
 * @return array|bool Returns the user array on successful login, false otherwise
 * @throws Exception If there's a database error
 */
function loginUser($username, $password) {
    global $conn; // Use global connection

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    } else {
        return false;
    }
}
?>