<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$userId = $_SESSION['user_id'];
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$error = '';
$success = '';

if (!$orderId) {
    header('Location: order_history.php');
    exit;
}

// Handle Form Submission (Update action)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($fullName) || empty($phone) || empty($address)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET full_name = ?, phone = ?, address = ? WHERE id = ? AND user_id = ? AND status = 'Pending'");
            $stmt->execute([$fullName, $phone, $address, $orderId, $userId]);
            
            if ($stmt->rowCount() > 0 || $pdo) {
                $success = "Order details updated successfully!";
            } else {
                $error = "Update failed. Order may not be pending or doesn't exist.";
            }
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch order data for the form
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || $order['status'] !== 'Pending') {
        header('Location: order_history.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: order_history.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Order #<?= $orderId ?> - Bencalo MotoWorks</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 600px; padding: 40px 20px;">
        <h2>Edit Order #<?= $orderId ?></h2>

        <?php if (!empty($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 5px;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="edit_order.php?id=<?= $orderId ?>" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
            <input type="hidden" name="id" value="<?= $orderId ?>">
            
            <div>
                <label style="display: block; margin-bottom: 5px;">Full Name:</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($order['full_name']) ?>" required style="width: 100%; padding: 8px;">
            </div>

            <div>
                <label style="display: block; margin-bottom: 5px;">Phone Number:</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($order['phone']) ?>" required style="width: 100%; padding: 8px;">
            </div>

            <div>
                <label style="display: block; margin-bottom: 5px;">Delivery Address:</label>
                <textarea name="address" rows="3" required style="width: 100%; padding: 8px;"><?= htmlspecialchars($order['address']) ?></textarea>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Update Order</button>
                <a href="order_history.php" class="btn" style="margin-left: 10px; text-decoration: none;">Back to History</a>
            </div>
        </form>
    </div>
</body>
</html>