<?php
// Use environment variables for sensitive information
$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'robo_user';
$password = getenv('DB_PASSWORD') ?: 'E4sT!2nD#';
$dbname = getenv('DB_NAME') ?: 'roboware_db';

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }