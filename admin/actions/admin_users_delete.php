<?php
// /admin/actions/admin_users_delete.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Support both GET (link click) and POST (form submission)
$userId = intval($_GET['id'] ?? $_POST['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ../admin_users.php?msg=' . urlencode('Invalid user ID.'));
    exit();
}

if ($userId === intval($_SESSION['user_id'])) {
    header('Location: ../admin_users.php?msg=' . urlencode('Action restricted: You cannot delete your own active admin account.'));
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    header('Location: ../admin_users.php?msg=' . urlencode('User account deleted successfully.'));
    exit();
} catch (Exception $e) {
    header('Location: ../admin_users.php?msg=' . urlencode('Error deleting user from database.'));
    exit();
}