<?php
// /admin/actions/admin_users_update.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? 'customer');

    if ($userId <= 0) {
        header('Location: ../admin_users.php?msg=' . urlencode('Invalid user ID.'));
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, phone = ?, role = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $username, $email, $phone, $role, $userId]);
        header('Location: ../admin_users.php?msg=' . urlencode('User account updated successfully.'));
        exit();
    } catch (Exception $e) {
        header('Location: ../admin_users.php?msg=' . urlencode('Error updating user information.'));
        exit();
    }
}

header('Location: ../admin_users.php');
exit();