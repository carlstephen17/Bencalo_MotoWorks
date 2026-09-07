<?php
session_start();
require_once 'config.php';
require_once 'includes/validation.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read csrf_token from POST (with fallback to form_security_token)
    $token    = $_POST['csrf_token'] ?? $_POST['form_security_token'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Read csrf_token from SESSION (with fallback to form_security_token)
    $sessionToken = $_SESSION['csrf_token'] ?? $_SESSION['form_security_token'] ?? null;

    // Check Form Security Token
    $tokenError = validateFormSecurityToken($token, $sessionToken);
    if ($tokenError) {
        echo json_encode(['success' => false, 'message' => $tokenError]);
        exit;
    }

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['first_name']  = $user['first_name'];
            $_SESSION['role']        = $user['role'] ?? 'user';

            if ($remember) {
                $rememberToken = bin2hex(random_bytes(32));
                try {
                    $tokenStmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $tokenStmt->execute([$rememberToken, $user['id']]);
                    setcookie('remember_token', $rememberToken, time() + (86400 * 30), '/', '', true, true);
                } catch (PDOException $e) {
                    // Silently bypass if column does not exist
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

    echo json_encode($response);
    exit;
}
?>