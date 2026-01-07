<?php
// Start session and include database connection
session_start();
require 'database.php'; // Assuming this is your secure database connection file

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method!']);
    exit;
}

// Retrieve and sanitize inputs
$order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
$user_id = isset($_SESSION['user_id']) ? filter_var($_SESSION['user_id'], FILTER_SANITIZE_NUMBER_INT) : null;

// Validate inputs
if (empty($order_id) || empty($user_id)) {
    http_response_code(400); // Bad Request
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid inputs!']);
    exit;
}

try {
    global $conn; // Assuming $conn is declared in database.php

    // Prepare SQL to verify the order
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Order is valid, proceed to deliver the product
        $order = $result->fetch_assoc();
        // Example: send the product code, assuming it's in the orders table or linked via another query
        if (isset($order['product_code'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'code' => $order['product_code']]);
        } else {
            // If product_code is not directly in the orders table, you might need another query
            throw new Exception("Product code not found for this order.");
        }
    } else {
        // Invalid order
        http_response_code(404); // Not Found
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Order not found!']);
    }
} catch (Exception $e) {
    // Log the error
    error_log("Error in verify-order-backend: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while processing your request.']);
}


// ... (previous code remains the same until here)

if ($result->num_rows > 0) {
    // Order is valid, proceed to fetch product details
    $order = $result->fetch_assoc();
    $product_id = $order['product_id'];

    try {
        // Fetch product details including product_code
        $stmt = $conn->prepare("SELECT product_code FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product_result = $stmt->get_result();

        if ($product_result->num_rows > 0) {
            $product = $product_result->fetch_assoc();
            if (isset($product['product_code'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'code' => $product['product_code']]);
            } else {
                throw new Exception("Product code not found for this product.");
            }
        } else {
            throw new Exception("Product not found for this order.");
        }
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Product fetch error: " . $e->getMessage());
        
        // Return a generic error message to the user
        http_response_code(500); // Internal Server Error
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while processing your request.']);
    }
} else {
    // Order not found
    http_response_code(404); // Not Found
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Order not found!']);
}
// ... (rest of the error handling remains the same)
?>
