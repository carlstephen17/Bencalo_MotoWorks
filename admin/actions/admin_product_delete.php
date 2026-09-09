<?php
// /admin/actions/admin_product_delete.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;

if ($id) {
    // Fetch image to delete file from uploads directory
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product && !empty($product['image'])) {
        $filePath = '../../' . $product['image'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $deleteStmt->execute([$id]);

    header('Location: ../admin_products.php?msg=Product+successfully+deleted');
    exit();
}

header('Location: ../admin_products.php?msg=Invalid+product+ID');
exit();
?>