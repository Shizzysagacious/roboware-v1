<?php
require_once 'config.php'; // Assuming config.php contains database connection details

// Secure headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust based on your CORS policy
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

// CSRF Protection
session_start(); // Start the session for CSRF token validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403); // Forbidden if CSRF token does not match
    echo json_encode(['success' => false, 'message' => 'CSRF validation failed']);
    exit;
}

// Rate Limiting (Simple Implementation)
$rateLimitKey = 'rate_limit_' . session_id();
if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = 0;
}
if ($_SESSION[$rateLimitKey] > 100) { // Example threshold, adjust as needed
    http_response_code(429); // Too Many Requests
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded. Please try again later.']);
    exit;
}
$_SESSION[$rateLimitKey]++;

try {
    // User Authentication (Assuming you have a user system)
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'You must be logged in to access this feature.']);
        exit;
    }

    // Sanitize input
    $userMessage = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING) ?: '';
    
    // Extract product ID if present
    if (preg_match('/product (\d+)/', $userMessage, $matches)) {
        $productId = intval($matches[1]); // Ensure product ID is an integer

        // Subscription Check (Assuming you have a subscription system)
        if (!checkSubscription($_SESSION['user_id'])) {
            http_response_code(402); // Payment Required
            echo json_encode(['success' => false, 'message' => 'You need an active subscription to access this information. Please subscribe.']);
            exit;
        }

        // Caching (Simple Memcached example)
        $cacheKey = 'product_' . $productId;
        $memcache = new Memcached();
        $memcache->addServer('localhost', 11211); // Adjust server details
        $cachedData = $memcache->get($cacheKey);

        if ($cachedData !== false) {
            // Use cached data if available
            $response = ['success' => true, 'message' => $cachedData];
        } else {
            // Query the database with prepared statements to prevent SQL injection
            $stmt = $conn->prepare("SELECT description, price, stock FROM products WHERE id = ? AND id BETWEEN 1 AND 10000"); // Example range for product ID validation
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();

            if ($product) {
                $productInfo = "Product ID: {$productId}, Description: {$product['description']}, Price: {$product['price']}, Stock: {$product['stock']}";
                $response = ['success' => true, 'message' => $productInfo];
                // Cache the result for future queries
                $memcache->set($cacheKey, $productInfo, 300); // Cache for 5 minutes
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Product not found'
                ];
            }
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Invalid product inquiry'
        ];
    }
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Chatbot error: " . $e->getMessage(), 0);
    $response = [
        'success' => false,
        'message' => 'An error occurred while processing your request.'
    ];
    http_response_code(500); // Internal Server Error
}

// Output response
echo json_encode($response);

// Helper function to check subscription status
function checkSubscription($userId) {
    // This is a placeholder function, implement actual logic to check user subscription status
    $stmt = $conn->prepare("SELECT active FROM subscriptions WHERE user_id = ? AND active = 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}