<?php
require_once 'includes/config.php';
header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!empty($email)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $resetToken = bin2hex(random_bytes(32));

                // Let MySQL handle the 1-hour expiry using its own clock
                $updateStmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
                $updateStmt->execute([$resetToken, $user['id']]);

                $response['success'] = true;
                $response['message'] = 'Password reset token generated! Check your database for testing.';
            } else {
                $response['success'] = true;
                $response['message'] = 'If that email exists, reset instructions have been sent.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Please enter your email address.';
    }

    echo json_encode($response);
    exit;
}
?>