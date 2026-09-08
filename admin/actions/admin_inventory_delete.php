<?php
// /admin/actions/admin_inventory_delete.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // Optional: Delete physical image file from server if it exists
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product && !empty($product['image'])) {
        $imagePath = '../../' . $product['image'];
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }

    // Delete record from database
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: ../admin_inventory.php?msg=Item+successfully+deleted+from+inventory');
    exit();
}

header('Location: ../admin_inventory.php?msg=Invalid+product+ID');
exit();
?>