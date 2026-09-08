<?php
// /admin/actions/admin_order_update.php
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
        header('Location: ../admin_orders.php?msg=Invalid+Security+Token');
        exit();
    }

    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';

    $allowedStatuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];

    if ($orderId && in_array($status, $allowedStatuses)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orderId]);

        header('Location: ../admin_orders.php?msg=Order+status+successfully+updated');
        exit();
    }

    header('Location: ../admin_orders.php?msg=Invalid+order+parameters');
    exit();
} else {
    header('Location: ../admin_orders.php');
    exit();
}
?>