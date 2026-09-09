<?php
// /admin/admin_index.php
session_start();
require_once '../includes/config.php';
/** @var PDO $pdo */
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch metrics for dashboard summary cards (using promo_claims instead of promos)
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalServices = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$totalPromos = $pdo->query("SELECT COUNT(*) FROM promo_claims")->fetchColumn();

// Fetch recent orders
$recentOrdersStmt = $pdo->query("
    SELECT o.*, u.username as customer_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC LIMIT 5
");
$recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_layout.css">
    <link rel="stylesheet" href="css/admin_products.css">
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>

<body>

    <div class="admin-layout">
        <?php include 'includes/admin_sidebar.php'; ?>

        <!-- MAIN CONTENT AREA -->
        <main class="admin-main">
            <header class="main-header">
                <h2>Dashboard Overview</h2>
                <div class="admin-user-info">
                    <span>Welcome back, Admin</span>
                </div>
            </header>

            <div class="admin-content-body">
                <!-- STATS CARDS -->
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-products">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $totalProducts ?></h3>
                            <p>Total Products</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-orders">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $totalOrders ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $totalUsers ?></h3>
                            <p>Registered Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-services">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $totalServices ?></h3>
                            <p>Active Services</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-promos">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $totalPromos ?></h3>
                            <p>Promo Claims</p>
                        </div>
                    </div>
                </div>

                <!-- RECENT ORDERS CARD -->
                <div class="card-container">
                    <div class="card-header-flex">
                        <h3>Recent Orders</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center" style="padding: 30px; color: #64748b;">No recent orders found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td><strong>#<?= $order['id'] ?></strong></td>
                                            <td><?= htmlspecialchars($order['customer_name'] ?? 'Guest/Unknown') ?></td>
                                            <td>₱<?= number_format($order['total_amount'] ?? $order['total'] ?? 0, 2) ?></td>
                                            <td>
                                                <span class="badge" style="padding: 5px 10px; border-radius: 6px; font-weight: 600; background: 
                                                    <?= ($order['status'] === 'Completed') ? '#dcfce7; color: #166534;' : (($order['status'] === 'Pending') ? '#fef3c7; color: #b45309;' : '#fee2e2; color: #991b1b;') ?>">
                                                    <?= htmlspecialchars($order['status'] ?? 'Pending') ?>
                                                </span>
                                            </td>
                                            <td style="color: #64748b; font-size: 0.9rem;"><?= htmlspecialchars($order['created_at'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>

</html>