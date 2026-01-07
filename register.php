<?php
// Connect to database
$conn = mysqli_connect("localhost", "username", "password", "database");

// Register user
if (isset($_POST['register'])) {
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $query = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
  mysqli_query($conn, $query);
}
?>