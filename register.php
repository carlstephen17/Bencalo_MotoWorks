<?php
session_start();
header('Content-Type: application/json');

$configFile = __DIR__ . '/includes/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$password  = $_POST['password'] ?? '';

if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($phone) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!preg_match('/^09[0-9]{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number must be 11 digits and start with 09.']);
    exit;
}

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}

try {
    // Check if user exists
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->execute([$username, $email]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists.']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
    $stmt->execute([$firstName, $lastName, $username, $email, $phone, $hashedPassword]);

    $userId = $pdo->lastInsertId();
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;
    $_SESSION['phone'] = $phone;
    $_SESSION['role'] = 'user';

    echo json_encode(['success' => true, 'message' => 'Account created successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}