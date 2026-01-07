<?php
require 'paypal-php-sdk/autoload.php';
require_once 'config.php'; // Assume this file contains the database connection setup

use PayPal\Api\WebhookEvent;

// Use environment variables for sensitive data
$clientId = getenv('PAYPAL_CLIENT_ID') ?: 'your-fallback-client-id';
$clientSecret = getenv('PAYPAL_CLIENT_SECRET') ?: 'your-fallback-client-secret';

try {
    $apiContext = new \PayPal\Rest\ApiContext(
        new \PayPal\Auth\OAuthTokenCredential(
            $clientId,
            $clientSecret
        )
    );

    // Enable logging for better error tracking
    $apiContext->setConfig([
        'mode' => getenv('PAYPAL_MODE') ?: 'sandbox', // Sandbox or live mode
        'log.LogEnabled' => true,
        'log.FileName' => __DIR__ . '/PayPal.log',
        'log.LogLevel' => 'DEBUG'
    ]);

    // Handle webhook event
    $webhookEvent = new WebhookEvent();
    $webhookEvent->fromJson(file_get_contents('php://input'));

    // Verify webhook event
    if ($webhookEvent->verify($apiContext)) {
        // Extract necessary information from the event
        $eventType = $webhookEvent->getResourceType();
        $eventData = $webhookEvent->getResource();

        // Assuming the event data has payment details or transaction ID
        $transactionId = $eventData->id ?? null;

        if ($transactionId) {
            // Begin transaction for database safety
            $pdo->beginTransaction();

            try {
                // Update order status in database
                $stmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE transaction_id = :transaction_id");
                $stmt->execute([':transaction_id' => $transactionId]);

                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    // If the update was successful, commit the transaction
                    $pdo->commit();
                    // Redirect to success page
                    header('Location: payment-successful.html');
                    exit();
                } else {
                    // If no rows were updated, there was no matching transaction_id
                    $pdo->rollBack();
                    throw new Exception('No matching order found for this transaction ID.');
                }
            } catch (PDOException $e) {
                // If any database error occurs, roll back the transaction
                $pdo->rollBack();
                error_log("Database Error: " . $e->getMessage());
                header('Status: 500 Internal Server Error');
                echo json_encode(['error' => 'An error occurred while processing your payment. Please try again.']);
                exit();
            }
        } else {
            // Log the issue and handle accordingly
            error_log("No transaction ID found in webhook event.");
            header('Status: 400 Bad Request');
            echo json_encode(['error' => 'Invalid payment data received.']);
            exit();
        }
    } else {
        // Webhook event verification failed
        header('Status: 400 Bad Request');
        echo json_encode(['error' => 'Webhook verification failed.']);
        exit();
    }
} catch (Exception $e) {
    // Log errors and provide a generic error message for clients
    error_log("Webhook Processing Error: " . $e->getMessage());
    header('Status: 500 Internal Server Error');
    echo json_encode(['error' => 'An error occurred while processing the payment. Please try again.']);
    exit();
}
?>