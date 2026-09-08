<?php
// /admin/actions/admin_user_update.php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        header('Location: ../admin_users.php?msg=Invalid+Security+Token');
        exit();
    }

    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $role = trim($_POST['role'] ?? '');

    // Validate role option
    $allowedRoles = ['customer', 'admin'];

    if ($userId && in_array($role, $allowedRoles, true)) {
        // Prevent admin from accidentally demoting themselves if they are the target
        if ($userId === (int)$_SESSION['user_id'] && $role !== 'admin') {
            header('Location: ../admin_users.php?msg=Error:+You+cannot+demote+your+own+admin+account');
            exit();
        }

        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $userId]);

        header('Location: ../admin_users.php?msg=User+role+successfully+updated');
        exit();
    }

    header('Location: ../admin_users.php?msg=Invalid+user+role+parameters');
    exit();
} else {
    header('Location: ../admin_users.php');
    exit();
}
?>