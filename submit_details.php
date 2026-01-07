<?php
session_start();
require_once 'database.php'; // Assume this file securely sets up the database connection

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate user inputs
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $contact = filter_input(INPUT_POST, 'contact', FILTER_SANITIZE_NUMBER_INT);
    $portfolio = filter_input(INPUT_POST, 'portfolio', FILTER_SANITIZE_URL);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    $age = filter_input(INPUT_POST, 'age', FILTER_SANITIZE_NUMBER_INT);
    $skill = filter_input(INPUT_POST, 'skill', FILTER_SANITIZE_STRING);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $programming = filter_input(INPUT_POST, 'programming', FILTER_SANITIZE_STRING);
    $employment = filter_input(INPUT_POST, 'employment', FILTER_SANITIZE_STRING);
    $password = $_POST['password']; // Do not sanitize password before hashing

    try {
        global $conn; // Assuming $conn is set in database.php

        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // File upload handling
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $cv = $_FILES['cv'];
        $cvName = "cv_" . time() . "_" . basename($cv["name"]);
        $cvTargetPath = $targetDir . $cvName;

        $nin = $_FILES['nin'];
        $ninName = "nin_" . time() . "_" . basename($nin["name"]);
        $ninTargetPath = $targetDir . $ninName;

        $uploadOk = true;
        $errors = [];

        // Check file size
        $maxFileSize = 5 * 1024 * 1024; // 5 MB
        if ($cv["size"] > $maxFileSize || $nin["size"] > $maxFileSize) {
            $errors[] = "File size exceeds the limit (5MB).";
            $uploadOk = false;
        }

        // Check file types
        $allowedCvTypes = array("pdf", "doc", "docx");
        $allowedNinTypes = array("jpg", "jpeg", "png", "pdf");
        $cvFileType = strtolower(pathinfo($cvTargetPath, PATHINFO_EXTENSION));
        $ninFileType = strtolower(pathinfo($ninTargetPath, PATHINFO_EXTENSION));

        if (!in_array($cvFileType, $allowedCvTypes)) {
            $errors[] = "Sorry, only PDF, DOC, or DOCX files are allowed for CV.";
            $uploadOk = false;
        }

        if (!in_array($ninFileType, $allowedNinTypes)) {
            $errors[] = "Sorry, only JPG, JPEG, PNG, or PDF files are allowed for NIN.";
            $uploadOk = false;
        }

        if ($uploadOk) {
            if (move_uploaded_file($cv["tmp_name"], $cvTargetPath) && move_uploaded_file($nin["tmp_name"], $ninTargetPath)) {
                // Use prepared statements to insert into the database
                $stmt = $conn->prepare("INSERT INTO developers (name, email, contact, portfolio, company, age, skill, gender, programming, employment, password, cv_path, nin_path) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssssss", $name, $email, $contact, $portfolio, $company, $age, $skill, $gender, $programming, $employment, $passwordHash, $cvTargetPath, $ninTargetPath);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Details submitted successfully! Verification will take 12 to 24 hours. Please proceed to make the payment.";
                    header("Location: paymentdevtools.html");
                    exit();
                } else {
                    throw new Exception("Error saving details to the database. Please try again later.");
                }
            } else {
                throw new Exception("Error uploading files. Please ensure both files are valid and try again.");
            }
        } else {
            throw new Exception(implode(" ", $errors));
        }
    } catch (Exception $e) {
        error_log("Submit Details Error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
} else {
    http_response_code(405); // Method Not Allowed
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}
?>