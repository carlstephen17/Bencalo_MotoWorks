<?php
// /admin/actions/admin_order_update.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

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
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $totalAmount = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);
    $status = $_POST['status'] ?? '';
    $createdAt = $_POST['created_at'] ?? '';

    $allowedStatuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];

    if ($orderId && $userId && $productId && $totalAmount !== false && in_array($status, $allowedStatuses) && !empty($createdAt)) {
        $stmt = $pdo->prepare("UPDATE orders SET user_id = ?, product_id = ?, total_amount = ?, status = ?, created_at = ? WHERE id = ? AND status <> 'Completed'");
        $stmt->execute([$userId, $productId, $totalAmount, $status, $createdAt, $orderId]);

        if ($stmt->rowCount() === 0) {
            header('Location: ../admin_orders.php?msg=Completed+orders+cannot+be+edited');
            exit();
        }

        header('Location: ../admin_orders.php?msg=Order+successfully+updated');
        exit();
    }

    header('Location: ../admin_orders.php?msg=Invalid+order+parameters');
    exit();
} else {
    header('Location: ../admin_orders.php');
    exit();
}
?>