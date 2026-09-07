<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to complete your purchase.']);
    exit;
}

require_once 'config.php';

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$fullName = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? 'cod');

if (!$productId || empty($fullName) || empty($phone) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required checkout fields.']);
    exit;
}

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify product exists and get price securely
    $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // If product is missing from database for any reason, auto-seed it on the fly
    if (!$product) {
        $defaultProducts = [
            [1, "GIVI HPS 50.6 Stuttgart Solid Black Medium (H506FSBK)", 250.00, "images/featured_products/helmet.png"],
            [2, "MOTUL 300V 4T Factory Line 10W40 1L", 320.00, "images/featured_products/synthetic_oil.png"],
            [3, "KOSO Side Mirror", 320.00, "images/featured_products/h3420_side_mirror.png"],
            [4, "Michelin Pilot Sport 4 225/40 ZR18 92Y XL", 325.00, "images/featured_products/tire.webp"],
            [5, "NGK Iridium IX Spark Plug", 45.00, "images/featured_products/spark_plug.png"],
            [6, "Brembo High Performance Brake Pads", 180.00, "images/featured_products/brake_pads.png"]
        ];

        $syncStmt = $pdo->prepare("INSERT INTO products (id, name, price, image, active, featured) VALUES (?, ?, ?, ?, 1, 0) ON DUPLICATE KEY UPDATE name=VALUES(name), price=VALUES(price), image=VALUES(image)");
        foreach ($defaultProducts as $p) {
            $syncStmt->execute($p);
        }

        // Retry the lookup after auto-seeding
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$product) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => "Product ID '{$productId}' not found in database."]);
        exit;
    }

    $price = $product['price'];

    // Insert order into database
    $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, product_id, total_amount, full_name, phone, address, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $orderStmt->execute([
        $_SESSION['user_id'],
        $productId,
        $price,
        $fullName,
        $phone,
        $address,
        $paymentMethod
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully! Thank you for shopping with Bencalo MotoWorks.'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}