<?php
// /admin/actions/admin_stock_update.php
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
        header('Location: ../admin_inventory.php?msg=Invalid+Security+Token');
        exit();
    }

    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $actionType = $_POST['action_type'] ?? '';
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if ($productId && $quantity !== false && $quantity >= 0) {
        // Fetch current stock first
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product) {
            $currentStock = (int)$product['stock'];
            $newStock = $currentStock;

            if ($actionType === 'add') {
                $newStock = $currentStock + $quantity;
            } elseif ($actionType === 'subtract') {
                $newStock = max(0, $currentStock - $quantity); // Prevent negative stock
            } elseif ($actionType === 'set') {
                $newStock = $quantity;
            }

            // Update database
            $updateStmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $updateStmt->execute([$newStock, $productId]);

            header('Location: ../admin_inventory.php?msg=Stock+successfully+updated');
            exit();
        }
    }

    header('Location: ../admin_inventory.php?msg=Invalid+stock+parameters');
    exit();
} else {
    header('Location: ../admin_inventory.php');
    exit();
}
?>