<?php
// /admin/admin_orders.php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch all orders with customer usernames
$stmt = $pdo->query("
    SELECT o.*, u.username as customer_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Orders Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_layout.css">
    <link rel="stylesheet" href="css/admin_products.css">
</head>
<body>

    <div class="admin-layout">
        <!-- SIDEBAR -->
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <h2>MotoWorks Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_index.php"><i class="fas fa-chart-bar"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="admin_inventory.php"><i class="fas fa-clipboard-list"></i> Inventory / Stock</a></li>
                <li><a href="admin_services.php"><i class="fas fa-tools"></i> Services</a></li>
                <li class="active"><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users / Customers</a></li>
                <li class="sidebar-logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="admin-main">
            <header class="main-header">
                <h2>Customer Orders</h2>
                <div class="admin-user-info">
                    <span>Order Tracking</span>
                </div>
            </header>

            <div class="admin-content-body">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert-toast" style="background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 4px; margin-bottom: 20px;"><?= htmlspecialchars($_GET['msg']) ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><?= htmlspecialchars($order['customer_name'] ?? 'Guest/Unknown') ?></td>
                                        <td>₱<?= number_format($order['total_amount'] ?? $order['total'] ?? 0, 2) ?></td>
                                        <td>
                                            <span class="badge" style="padding: 4px 8px; border-radius: 4px; background: 
                                                <?= ($order['status'] === 'Completed') ? '#e8f5e9; color: #2e7d32;' : (($order['status'] === 'Pending') ? '#fff3e0; color: #f57c00;' : '#ffebee; color: #c62828;') ?>">
                                                <?= htmlspecialchars($order['status'] ?? 'Pending') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($order['created_at'] ?? 'N/A') ?></td>
                                        <td>
                                            <button class="btn-sm btn-info" onclick="openOrderStatusModal(<?= $order['id'] ?>, '<?= htmlspecialchars($order['status'] ?? 'Pending') ?>')">
                                                <i class="fas fa-edit"></i> Update Status
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- UPDATE ORDER STATUS MODAL -->
    <div class="modal-overlay" id="updateOrderModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
        <div class="modal-content" style="background: #fff; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="modalOrderTitle">Update Order Status</h3>
                <button type="button" onclick="closeOrderModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form action="actions/admin_order_update.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="order_id" id="modal_order_id">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Status:</label>
                    <select name="status" id="modal_order_status" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;" required>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-secondary" onclick="closeOrderModal()" style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding: 8px 15px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openOrderStatusModal(orderId, currentStatus) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('modalOrderTitle').innerText = 'Update Order #' + orderId;
            document.getElementById('modal_order_status').value = currentStatus;
            document.getElementById('updateOrderModal').style.display = 'flex';
        }

        function closeOrderModal() {
            document.getElementById('updateOrderModal').style.display = 'none';
        }
    </script>
</body>
</html>