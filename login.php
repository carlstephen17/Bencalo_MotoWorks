<?php
// ==================== UPDATED LOGIN PROCESSOR (login.php) ====================
require_once 'config.php';
session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $remember   = isset($_POST['remember']);

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['role'] = $user['role'] ?? 'user';

            // Handle "Remember Me" securely via token
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                
                // Safe check or update for remember_token if column exists
                try {
                    $tokenStmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $tokenStmt->execute([$token, $user['id']]);
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
                } catch (PDOException $e) {
                    // Silently bypass if column hasn't been manually added yet to avoid crash
                }
            }

            $response['success'] = true;
            $response['message'] = 'Login successful!';
        } else {
            $response['message'] = 'Invalid username or password.';
        }
    } else {
        $response['message'] = 'Please fill in all fields.';
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>