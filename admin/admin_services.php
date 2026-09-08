<?php
// /admin/admin_services.php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch all services
$stmt = $pdo->query("SELECT * FROM services ORDER BY id DESC");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Services Management</title>
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
                <li class="active"><a href="admin_services.php"><i class="fas fa-tools"></i> Services</a></li>
                <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users / Customers</a></li>
                <li class="sidebar-logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="admin-main">
            <header class="main-header">
                <h2>Services Management</h2>
                <button class="btn-primary" onclick="openModal('addServiceModal')">
                    <i class="fas fa-plus"></i> Add Service
                </button>
            </header>

            <div class="admin-content-body">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert-toast" style="background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 4px; margin-bottom: 20px;"><?= htmlspecialchars($_GET['msg']) ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Service Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($services)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No services found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($services as $s): ?>
                                    <tr>
                                        <td><?= $s['id'] ?></td>
                                        <td><?= htmlspecialchars($s['name'] ?? $s['service_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($s['description'] ?? '') ?></td>
                                        <td>₱<?= number_format($s['price'] ?? 0, 2) ?></td>
                                        <td>
                                            <button class="btn-sm btn-info" onclick="openEditServiceModal(<?= htmlspecialchars(json_encode($s)) ?>)"><i class="fas fa-edit"></i></button>
                                            <a href="actions/admin_service_delete.php?id=<?= $s['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this service?');"><i class="fas fa-trash"></i></a>
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

    <!-- ADD SERVICE MODAL -->
    <div class="modal-overlay" id="addServiceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
        <div class="modal-content" style="background: #fff; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Add Service</h3>
                <button type="button" onclick="closeModal('addServiceModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form action="actions/admin_service_create.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Service Name:</label>
                    <input type="text" name="name" required class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description:</label>
                    <textarea name="description" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;" rows="3"></textarea>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Price:</label>
                    <input type="number" step="0.01" name="price" required class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addServiceModal')" style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding: 8px 15px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Service</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT SERVICE MODAL -->
    <div class="modal-overlay" id="editServiceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
        <div class="modal-content" style="background: #fff; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Edit Service</h3>
                <button type="button" onclick="closeModal('editServiceModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form action="actions/admin_service_update.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="id" id="edit_service_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Service Name:</label>
                    <input type="text" name="name" id="edit_service_name" required class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description:</label>
                    <textarea name="description" id="edit_service_desc" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;" rows="3"></textarea>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Price:</label>
                    <input type="number" step="0.01" name="price" id="edit_service_price" required class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('editServiceModal')" style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding: 8px 15px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer;">Update Service</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openEditServiceModal(service) {
            document.getElementById('edit_service_id').value = service.id;
            document.getElementById('edit_service_name').value = service.name || service.service_name || '';
            document.getElementById('edit_service_desc').value = service.description || '';
            document.getElementById('edit_service_price').value = service.price || '';
            openModal('editServiceModal');
        }
    </script>
</body>
</html>