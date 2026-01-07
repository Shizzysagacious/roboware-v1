<?php
// This is an example PHP backend endpoint for logging PayPal payments.
// It should be connected to your database to store the payment details.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse PayPal response
    $data = json_decode(file_get_contents('php://input'), true);

    // Extract relevant data
    $transactionID = $data['id'];
    $payerName = $data['payer']['name']['given_name'];
    $payerEmail = $data['payer']['email_address'];
    $productName = "Advanced Robotics Code"; // This would be dynamic in a real-world app
    $productPrice = "49.99"; // Also dynamic

    // Store in database (MySQL example, update with your DB credentials)
    $conn = new mysqli("localhost", "robo_user", "E4sT!2nD#", "roboware_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO transactions (transaction_id, payer_name, payer_email, product_name, product_price) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $transactionID, $payerName, $payerEmail, $productName, $productPrice);
    
    if ($stmt->execute()) {
        echo "Payment logged successfully";
    } else {
        echo "Error: " . $stmt->error; // In production, log this error rather than echoing it
    }

    $stmt->close();
    $conn->close();
}
?>