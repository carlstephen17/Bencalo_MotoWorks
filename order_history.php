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
$message = '';
$messageType = '';

// Handle Delete / Cancel Request
if (isset($_GET['delete_id'])) {
    $deleteId = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);
    if ($deleteId) {
        try {
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ? AND status = 'Pending'");
            $stmt->execute([$deleteId, $userId]);
            if ($stmt->rowCount() > 0) {
                $message = "Order successfully cancelled and deleted.";
                $messageType = "success";
            } else {
                $message = "Cannot delete this order. It may already be processed or completed.";
                $messageType = "error";
            }
        } catch (Exception $e) {
            $message = "Error deleting order: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Handle Update Request from Modal POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_order') {
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!$orderId || empty($fullName) || empty($phone) || empty($address)) {
        $message = "All fields are required for updating the order.";
        $messageType = "error";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET full_name = ?, phone = ?, address = ? WHERE id = ? AND user_id = ? AND status = 'Pending'");
            $stmt->execute([$fullName, $phone, $address, $orderId, $userId]);

            $message = "Order #{$orderId} updated successfully!";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Database Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Fetch user's orders joined with products table
try {
    $stmt = $pdo->prepare("
        SELECT o.*, p.name AS product_name, p.image AS product_image 
        FROM orders o 
        LEFT JOIN products p ON o.product_id = p.id 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Order History - Bencalo MotoWorks</title>
    <link rel="stylesheet" href="styles/index/style.css">
    <link rel="stylesheet" href="styles/index/components.css">
    <link rel="stylesheet" href="styles/index/layout.css">
    <link rel="stylesheet" href="styles/index/modals.css">
    <link rel="stylesheet" href="styles/order_history.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php require_once 'components/header.php'; ?>

    <div class="history-wrapper">
        <div class="history-container">
            <h2>My Order History</h2>

            <?php if (!empty($message)): ?>
                <div class="history-alert <?= $messageType === 'success' ? 'history-alert-success' : 'history-alert-error' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <p style="color: #9ca3af;">You haven't placed any orders yet.</p>
                <a href="index.php" class="btn-row-action btn-edit" style="margin-top: 15px; display: inline-block; text-decoration: none;">Browse Shop</a>
            <?php else: ?>
                <div class="order-table-wrapper">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Date Placed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): ?>
                                <tr>
                                    <td>#<?= $ord['id'] ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php if (!empty($ord['product_image'])): ?>
                                                <img src="<?= htmlspecialchars($ord['product_image']) ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #374151;">
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($ord['product_name'] ?? 'Custom Product') ?></span>
                                        </div>
                                    </td>
                                    <td>₱<?= number_format($ord['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $ord['status'] == 'Pending' ? 'badge-pending' : 'badge-completed' ?>">
                                            <?= htmlspecialchars($ord['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($ord['created_at']) ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="btn-row-action btn-view" onclick="openViewModal(
                                                <?= $ord['id'] ?>, 
                                                '<?= htmlspecialchars($ord['product_name'] ?? 'Custom Product', ENT_QUOTES) ?>',
                                                '₱<?= number_format($ord['total_amount'], 2) ?>',
                                                '<?= htmlspecialchars($ord['status'], ENT_QUOTES) ?>',
                                                '<?= htmlspecialchars($ord['created_at'], ENT_QUOTES) ?>',
                                                '<?= htmlspecialchars($ord['full_name'], ENT_QUOTES) ?>', 
                                                '<?= htmlspecialchars($ord['phone'], ENT_QUOTES) ?>', 
                                                '<?= htmlspecialchars($ord['address'], ENT_QUOTES) ?>'
                                            )">
                                                <i class="fa-solid fa-eye"></i> View
                                            </button>

                                            <?php if ($ord['status'] == 'Pending'): ?>
                                                <button type="button" class="btn-row-action btn-edit" onclick="openEditModal(
                                                    <?= $ord['id'] ?>, 
                                                    '<?= htmlspecialchars($ord['full_name'], ENT_QUOTES) ?>', 
                                                    '<?= htmlspecialchars($ord['phone'], ENT_QUOTES) ?>', 
                                                    '<?= htmlspecialchars($ord['address'], ENT_QUOTES) ?>'
                                                )">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </button>

                                                <button type="button" class="btn-row-action btn-cancel" onclick="if(confirm('Are you sure you want to cancel and delete this order?')) { window.location.href='order_history.php?delete_id=<?= $ord['id'] ?>'; }">
                                                    <i class="fa-solid fa-trash"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Shop</a>
        </div>
    </div>

    <!-- View Order Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Order Details <span id="viewModalOrderId"></span></h3>
                <button type="button" class="modal-close" onclick="closeViewModal()">&times;</button>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.95rem; color: #f0f6fc;">
                <div><strong style="color: #8b949e;">Product:</strong> <span id="viewModalProduct"></span></div>
                <div><strong style="color: #8b949e;">Total Amount:</strong> <span id="viewModalTotal"></span></div>
                <div><strong style="color: #8b949e;">Status:</strong> <span id="viewModalStatus"></span></div>
                <div><strong style="color: #8b949e;">Date Placed:</strong> <span id="viewModalDate"></span></div>
                <div><strong style="color: #8b949e;">Full Name:</strong> <span id="viewModalFullName"></span></div>
                <div><strong style="color: #8b949e;">Phone Number:</strong> <span id="viewModalPhone"></span></div>
                <div><strong style="color: #8b949e;">Delivery Address:</strong> <span id="viewModalAddress"></span></div>
            </div>
            <div class="modal-actions" style="margin-top: 24px;">
                <button type="button" class="btn-modal-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Order Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Edit Order <span id="modalOrderIdDisplay"></span></h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form action="order_history.php" method="POST">
                <input type="hidden" name="action" value="edit_order">
                <input type="hidden" name="order_id" id="modalOrderId">

                <div class="modal-form-group">
                    <label for="modalFullName">Full Name</label>
                    <input type="text" id="modalFullName" name="full_name" class="modal-form-control" required>
                </div>

                <div class="modal-form-group">
                    <label for="modalPhone">Phone Number</label>
                    <input type="text" id="modalPhone" name="phone" class="modal-form-control" required>
                </div>

                <div class="modal-form-group">
                    <label for="modalAddress">Delivery Address</label>
                    <textarea id="modalAddress" name="address" rows="3" class="modal-form-control" required></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-modal-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <?php require_once 'components/footer.php'; ?>

    <script>
        function openViewModal(id, product, total, status, date, fullName, phone, address) {
            document.getElementById('viewModalOrderId').innerText = '#' + id;
            document.getElementById('viewModalProduct').innerText = product;
            document.getElementById('viewModalTotal').innerText = total;
            document.getElementById('viewModalStatus').innerText = status;
            document.getElementById('viewModalDate').innerText = date;
            document.getElementById('viewModalFullName').innerText = fullName;
            document.getElementById('viewModalPhone').innerText = phone;
            document.getElementById('viewModalAddress').innerText = address;

            document.getElementById('viewModal').style.display = 'flex';
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function openEditModal(id, fullName, phone, address) {
            document.getElementById('modalOrderId').value = id;
            document.getElementById('modalOrderIdDisplay').innerText = '#' + id;
            document.getElementById('modalFullName').value = fullName;
            document.getElementById('modalPhone').value = phone;
            document.getElementById('modalAddress').value = address;

            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            let editModal = document.getElementById('editModal');
            let viewModal = document.getElementById('viewModal');
            if (event.target == editModal) {
                closeEditModal();
            }
            if (event.target == viewModal) {
                closeViewModal();
            }
        }
    </script>
</body>

</html>