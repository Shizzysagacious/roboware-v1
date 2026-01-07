<?php
/**
 * Database Configuration
 * 
 * This file should be stored outside the web root for security reasons. 
 * Use environment variables or a secure configuration management system in production.
 */

// Error reporting settings for production
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

// Database connection details
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'roboware_db';
$username = getenv('DB_USERNAME') ?: 'robo_user';
$password = getenv('DB_PASSWORD') ?: 'E4sT!2nD#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    die('Database Connection Error'); // In production, this might be a redirect to an error page or silent fail
}

// Other configurations (e.g., for other services or settings)
define('SITE_URL', getenv('SITE_URL') ?: 'https://example.com');
define('CSRF_SECRET', getenv('CSRF_SECRET') ?: 'your-secret-here-for-hashing');

// Session settings for security
session_name('secure_session');
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Use only if SSL is available
ini_set('session.cookie_httponly', 1);
session_start();