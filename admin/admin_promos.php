<?php
// /admin/admin_promos.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/config.php';
/** @var PDO $pdo */
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$successMessage = '';
$errorMessage = '';

// Handle status update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $claimId = intval($_POST['claim_id']);
    $newStatus = trim($_POST['status']);
    
    $allowedStatuses = ['Pending', 'Approved', 'Completed', 'Cancelled'];
    if (in_array($newStatus, $allowedStatuses)) {
        try {
            $updateStmt = $pdo->prepare("UPDATE promo_claims SET status = ? WHERE id = ?");
            $updateStmt->execute([$newStatus, $claimId]);
            $successMessage = "Promo claim #{$claimId} status updated to {$newStatus}.";
        } catch (PDOException $e) {
            $errorMessage = "Error updating status: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Invalid status selection.";
    }
}

// Fetch all promo claims with user account details
$claimsStmt = $pdo->query("
    SELECT pc.*, u.username as account_username 
    FROM promo_claims pc 
    LEFT JOIN users u ON pc.user_id = u.id 
    ORDER BY pc.id DESC
");
$claims = $claimsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Promo Claims & Appointments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_layout.css">
    <link rel="stylesheet" href="css/admin_products.css">
    <link rel="stylesheet" href="css/admin_dashboard.css">
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
                <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users / Customers</a></li>
                <li class="active"><a href="admin_promos.php"><i class="fas fa-tags"></i> Promos & Claims</a></li>
                <li class="sidebar-logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="admin-main">
            <header class="main-header">
                <h2>Promo Claims & Appointments</h2>
                <div class="admin-user-info">
                    <span>Welcome back, Admin</span>
                </div>
            </header>

            <div class="admin-content-body">
                
                <?php if (!empty($successMessage)): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0; font-weight: 500;">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca; font-weight: 500;">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <!-- CLAIMS TABLE CONTAINER -->
                <div class="card-container">
                    <div class="card-header-flex">
                        <h3>Customer Promo & Service Claims</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Bundle / Promo</th>
                                    <th>Customer / Phone</th>
                                    <th>Selected Option</th>
                                    <th>Appointment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($claims)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center" style="padding: 30px; color: #64748b; text-align: center;">No promo claims found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($claims as $claim): ?>
                                        <tr>
                                            <td><strong>#<?= $claim['id'] ?></strong></td>
                                            <td>
                                                <code style="background: #f1f5f9; padding: 3px 6px; border-radius: 4px; color: #0f172a; font-weight: 600;">
                                                    <?= htmlspecialchars($claim['bundle_slug']) ?>
                                                </code>
                                            </td>
                                            <td>
                                                <div><strong><?= htmlspecialchars($claim['fullname']) ?></strong></div>
                                                <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($claim['phone']) ?></div>
                                                <div style="font-size: 0.75px; color: #94a3b8;">User: <?= htmlspecialchars($claim['account_username'] ?? 'ID: '.$claim['user_id']) ?></div>
                                            </td>
                                            <td style="max-width: 220px; word-break: break-word;">
                                                <?= htmlspecialchars($claim['selected_option']) ?>
                                            </td>
                                            <td>
                                                <div><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($claim['appointment_date']) ?></div>
                                                <div style="font-size: 0.8rem; color: #64748b;"><i class="far fa-clock"></i> <?= htmlspecialchars($claim['appointment_time']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge" style="padding: 5px 10px; border-radius: 6px; font-weight: 600; display: inline-block; background: 
                                                    <?= ($claim['status'] === 'Completed' || $claim['status'] === 'Approved') ? '#dcfce7; color: #166534;' : (($claim['status'] === 'Pending') ? '#fef3c7; color: #b45309;' : '#fee2e2; color: #991b1b;') ?>">
                                                    <?= htmlspecialchars($claim['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: flex; gap: 6px; align-items: center;">
                                                    <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">
                                                    <select name="status" style="padding: 6px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.85rem; background: #fff;">
                                                        <option value="Pending" <?= $claim['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Approved" <?= $claim['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                                        <option value="Completed" <?= $claim['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                        <option value="Cancelled" <?= $claim['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                    </select>
                                                    <button type="submit" name="update_status" style="background: #1976d2; color: #fff; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </form>
                                            </td>
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