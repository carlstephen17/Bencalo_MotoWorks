<?php
// /admin/actions/admin_inventory_update.php
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
        header('Location: ../admin_inventory.php?msg=Invalid+Security+Token');
        exit();
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    if ($id && $stock !== false && $stock >= 0) {
        $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->execute([$stock, $id]);

        header('Location: ../admin_inventory.php?msg=Stock+quantity+successfully+updated');
        exit();
    }

    header('Location: ../admin_inventory.php?msg=Invalid+stock+parameters');
    exit();
} else {
    header('Location: ../admin_inventory.php');
    exit();
}
?>