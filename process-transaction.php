<?php
session_start();
require_once 'config.php'; // Assume this file sets up the $pdo connection

// CSRF protection
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403); // Forbidden
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid CSRF token.']);
    exit;
}

// Validate and sanitize input
$order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);

if (!$order_id || !$product_id) {
    http_response_code(400); // Bad Request
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request. Missing required parameters.']);
    exit;
}

// Retrieve price from database
$price = calculateAmount($product_id);

// Placeholder for payment integration
// Here you would integrate with your payment gateway, e.g., Stripe, PayPal
$payment_success = processPayment($order_id, $product_id, $price);

if ($payment_success) {
    // Redirect to success page
    header('Location: payment-successful.html?order_id=' . urlencode($order_id) . '&product_id=' . urlencode($product_id));
    exit;
} else {
    http_response_code(402); // Payment Required
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Payment failed. Please try again.']);
    exit;
}

/**
 * Fetch the product price from the database
 * 
 * @param int $product_id The ID of the product to fetch the price for
 * @return float The price of the product
 * @throws Exception If product not found or database error occurs
 */
function calculateAmount($product_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = :product_id");
        $stmt->execute([':product_id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && isset($product['price'])) {
            return (float) $product['price'];
        } else {
            throw new Exception("Product not found for ID: $product_id");
        }
    } catch (PDOException $e) {
        error_log("Database Error in calculateAmount: " . $e->getMessage());
        throw new Exception("An error occurred while fetching product price.");
    }
}

/**
 * Placeholder function for payment gateway integration. 
 * This function should be replaced with actual payment processing logic.
 * 
 * @param int $order_id The ID of the order being paid for
 * @param int $product_id The ID of the product associated with this order
 * @param float $amount The price to charge
 * @return bool Whether the payment was successful
 */
function processPayment($order_id, $product_id, $amount) {
    // Here you would integrate with your payment gateway, e.g., Stripe, PayPal
    // Example with Stripe:
    // require 'vendor/autoload.php';
    // \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
    // try {
    //     $charge = \Stripe\Charge::create([
    //         'amount' => (int) ($amount * 100), // Stripe expects amount in cents
    //         'currency' => 'usd',
    //         'source' => $_POST['stripeToken'], // From client-side form submission
    //         'description' => "Charge for order $order_id"
    //     ]);
    //     return $charge->status === 'succeeded';
    // } catch (\Stripe\Exception\CardException $e) {
    //     // Handle card errors
    //     error_log("Stripe Card Error: " . $e->getMessage());
    //     return false;
    // } catch (\Stripe\Exception\InvalidRequestException $e) {
    //     // Invalid parameters were supplied to Stripe's API
    //     error_log("Stripe Invalid Request: " . $e->getMessage());
    //     return false;
    // } catch (\Stripe\Exception\AuthenticationException $e) {
    //     // Authentication with Stripe's API failed
    //     error_log("Stripe Authentication Error: " . $e->getMessage());
    //     return false;
    // } catch (\Stripe\Exception\ApiConnectionException $e) {
    //     // Network communication with Stripe failed
    //     error_log("Stripe API Connection Error: " . $e->getMessage());
    //     return false;
    // } catch (\Stripe\Exception\ApiErrorException $e) {
    //     // Display a very generic error to the user, and maybe send yourself an email
    //     error_log("Stripe API Error: " . $e->getMessage());
    //     return false;
    // }

    // For simulation:
    return true; // or false based on actual payment outcome
}
?>