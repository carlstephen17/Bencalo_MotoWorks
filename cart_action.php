<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to your cart.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    $currentStock = (int)$product['stock'];
    
    // Check existing cart quantity for this item
    $existingQty = isset($_SESSION['cart'][$product_id]) ? (is_array($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : $_SESSION['cart'][$product_id]) : 0;
    
    if (($existingQty + $quantity) > $currentStock) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available. Maximum: ' . $currentStock]);
        exit;
    }

    // Initialize or update cart session structure
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$product_id]) && is_array($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'quantity' => ($existingQty + $quantity)
        ];
    }

    // Calculate total cart count badge
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += is_array($item) ? ($item['quantity'] ?? 1) : 1;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart successfully!',
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
?>