<?php
session_start();
require_once 'config.php';
require_once 'includes/validation.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token      = $_POST['csrf_token'] ?? '';
    $username   = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';

    // 1. Pre-check database for existing username or email so we can order them top-to-bottom
    $usernameExists = false;
    $emailExists = false;

    if (!empty($username) && !empty($email)) {
        $checkStmt = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        while ($row = $checkStmt->fetch()) {
            if (strcasecmp($row['username'], $username) === 0) {
                $usernameExists = true;
            }
            if (strcasecmp($row['email'], $email) === 0) {
                $emailExists = true;
            }
        }
    }

    // 2. Run input validation rules strictly from top to bottom
    $errors = array_values(array_filter([
        validateFormSecurityToken($token, $_SESSION['csrf_token'] ?? null),
        validateRequired($username, 'Username'),
        $usernameExists ? "Username '$username' is already taken." : null,
        validateRequired($first_name, 'First Name'),
        validateRequired($last_name, 'Last Name'),
        validateRequired($email, 'Email Address'),
        validateEmailFormat($email),
        $emailExists ? "Email address '$email' is already registered." : null,
        validateRequired($phone, 'Phone Number'),
        validatePhoneNumber($phone),
        validateRequired($password, 'Password'),
        validatePasswordStrength($password)
    ]));

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => $errors[0]]);
        exit;
    }

    // 3. Insert new user if all validations pass
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $insertStmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");

    if ($insertStmt->execute([$username, $first_name, $last_name, $email, $phone, $hashedPassword])) {
        $response['success'] = true;
        $response['message'] = "Successfully signed up! You can now log in.";
    } else {
        $response['message'] = "Something went wrong. Please try again.";
    }

    echo json_encode($response);
    exit;
}
?>