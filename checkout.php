<?php
session_start();
require_once 'config.php'; // Assume this file securely handles database connection

// Validate and sanitize input
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_NUMBER_INT);
$product_id = filter_input(INPUT_GET, 'product_id', FILTER_SANITIZE_NUMBER_INT);

if (!$order_id || !$product_id) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request data.']);
    exit;
}

try {
    // Transaction handling to ensure atomicity
    $pdo->beginTransaction();

    // Retrieve product details securely
    $stmt = $pdo->prepare('SELECT id, name, price FROM products WHERE id = :product_id');
    $stmt->execute([':product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $pdo->rollBack();
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Product not found.']);
        exit;
    }

    // Check if the order exists and if the product is part of this order
    $orderStmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = :order_id AND product_id = :product_id');
    $orderStmt->execute([':order_id' => $order_id, ':product_id' => $product_id]);
    $count = $orderStmt->fetchColumn();

    if ($count === 0) {
        $pdo->rollBack();
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Product not associated with this order.']);
        exit;
    }

    // Generate or retrieve CSRF token
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Commit the transaction if all checks pass
    $pdo->commit();

    // Render checkout form
    header('Content-Type: text/html');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Checkout</title>
        <link rel="stylesheet" href="/css/checkout.css"> <!-- Assuming you have a CSS file -->
    </head>
    <body>
        <h1>Checkout</h1>
        <p>Product: <?php echo htmlspecialchars($product['name']); ?></p>
        <p>Price: $<?php echo number_format((float)$product['price'], 2); ?></p>

        <form action="/process-transaction.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
            <button type="submit">Proceed to Payment</button>
        </form>
    </body>
    </html>
    <?php
} catch (PDOException $e) {
    // Roll back transaction on exception
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'A database error occurred. Please try again later.']);
    exit;
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'An error occurred. Please try again later.']);
    exit;
}
?>