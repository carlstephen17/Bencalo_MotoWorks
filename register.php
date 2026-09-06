<?php
// ==================== UPDATED REGISTRATION PROCESSOR (register.php) ====================
require_once 'config.php';
session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!empty($username) && !empty($first_name) && !empty($last_name) && !empty($email) && !empty($password)) {
        
        // Check if username or email already exists individually for specific error tracking
        $checkStmt = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        $existingUser = $checkStmt->fetch();

        if ($existingUser) {
            if (strcasecmp($existingUser['username'], $username) === 0) {
                $response['message'] = "Username '$username' is already taken. Please choose another.";
            } else {
                $response['message'] = "Email address '$email' is already registered.";
            }
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($insertStmt->execute([$username, $first_name, $last_name, $email, $phone, $hashedPassword])) {
                $response['success'] = true;
                $response['message'] = "Successfully signed up! You can now log in.";
            } else {
                $response['message'] = "Something went wrong. Please try again.";
            }
        }
    } else {
        $response['message'] = "Please fill in all required fields.";
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>