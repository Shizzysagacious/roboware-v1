<?php
require_once 'database.php';

/**
 * Creates a new order for a user with the specified product and total.
 *
 * @param int $user_id The ID of the user placing the order
 * @param int $product_id The ID of the product being ordered
 * @param float $total The total cost of the order
 * @return array An associative array with status and either order_id or error message
 * @throws Exception If there's a database error
 */
function createOrder($user_id, $product_id, $total) {
    global $conn; // Use the global connection from database.php

    try {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, order_date, total) VALUES (?, ?, NOW(), ?)");
        $stmt->bind_param("iis", $user_id, $product_id, $total);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return ['status' => 'success', 'order_id' => $stmt->insert_id];
        } else {
            throw new Exception("Failed to create order. No rows were affected.");
        }
    } catch (Exception $e) {
        error_log("Error in createOrder: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'An error occurred while creating the order.'];
    }
}

/**
 * Retrieves all orders for a specific user.
 *
 * @param int $user_id The ID of the user whose orders are to be retrieved
 * @return array An associative array with status and either the list of orders or error message
 * @throws Exception If there's a database error
 */
function getOrders($user_id) {
    global $conn; // Use the global connection from database.php

    try {
        $stmt = $conn->prepare("SELECT id, product_id, order_date, total FROM orders WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        return ['status' => 'success', 'orders' => $orders];
    } catch (Exception $e) {
        error_log("Error in getOrders: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'An error occurred while fetching orders.'];
    }
}
?>