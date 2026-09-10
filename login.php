<?php
session_start();
header('Content-Type: application/json');

$configFile = __DIR__ . '/includes/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?admin_login=1');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $isAdmin = ($user['role'] ?? '') === 'admin';
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['first_name'] = $user['first_name'] ?? $user['username'];
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['is_admin'] = $isAdmin;

        if ($isAdmin) {
            $_SESSION['admin_id'] = $user['id'];
        } else {
            unset($_SESSION['admin_id']);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Login successful!',
            'is_admin' => $isAdmin,
            'user_name' => $_SESSION['first_name'],
            'redirect' => $isAdmin ? 'admin/admin_index.php' : 'index.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error occurred.']);
}