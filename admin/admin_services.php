<?php
// /admin/admin_services.php
session_start();
require_once '../includes/config.php';
/** @var PDO $pdo */
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch all services in ascending order by ID
$stmt = $pdo->query("SELECT * FROM services ORDER BY id ASC");
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
                                <th>Image</th>
                                <th>Service Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($services)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No services found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($services as $s): ?>
                                    <tr>
                                        <td><?= $s['id'] ?></td>
                                        <td>
                                            <?php if (!empty($s['image'])): ?>
                                                <img src="../<?= htmlspecialchars($s['image']) ?>" alt="Service" width="40" height="40" style="object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 0.8rem;">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($s['name'] ?? $s['service_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($s['description'] ?? '') ?></td>
                                        <td>₱<?= number_format($s['price'] ?? 0, 2) ?></td>
                                        <td>
                                            <button class="btn-sm btn-secondary" title="View Service" onclick="openViewServiceModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)"><i class="fas fa-eye"></i></button>
                                            <button class="btn-sm btn-info" title="Edit Service" onclick="openEditServiceModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)"><i class="fas fa-edit"></i></button>
                                            <a href="actions/admin_service_delete.php?id=<?= $s['id'] ?>" class="btn-sm btn-danger" title="Delete Service" onclick="return confirm('Are you sure you want to delete this service?');"><i class="fas fa-trash"></i></a>
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

    <!-- VIEW SERVICE MODAL -->
    <div class="modal-overlay" id="viewServiceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
        <div class="modal-content" style="background: #fff; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Service Details</h3>
                <button type="button" onclick="closeModal('viewServiceModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 15px;">
                <div id="view_service_image_wrapper" style="text-align: center; margin-bottom: 5px; display: none;">
                    <img id="view_service_image" src="" alt="Service Image" style="max-width: 100%; height: 140px; object-fit: contain; border-radius: 4px; border: 1px solid #ddd; padding: 5px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Service Name:</label>
                    <p id="view_service_name" style="margin: 0; font-size: 1rem; color: #333; font-weight: 500;"></p>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Description:</label>
                    <p id="view_service_desc" style="margin: 0; font-size: 0.95rem; color: #333; white-space: pre-wrap; line-height: 1.4;"></p>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Price:</label>
                    <p id="view_service_price" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1976d2;"></p>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 25px;">
                <button type="button" class="btn-secondary" onclick="closeModal('viewServiceModal')" style="padding: 8px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Close</button>
            </div>
        </div>
    </div>

    <!-- ADD SERVICE MODAL -->
    <div class="modal-overlay" id="addServiceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
        <div class="modal-content" style="background: #fff; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Add Service</h3>
                <button type="button" onclick="closeModal('addServiceModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form action="actions/admin_service_create.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Service Name:</label>
                    <input type="text" name="name" required class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description:</label>
                    <textarea name="description" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;" rows="3"></textarea>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Price:</label>
                    <input type="number" step="0.01" name="price" required class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Service Image:</label>
                    <input type="file" name="image" accept="image/*" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addServiceModal')" style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding: 8px 15px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Service</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT SERVICE MODAL -->
    <div class="modal-overlay" id="editServiceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
        <div class="modal-content" style="background: #fff; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Edit Service</h3>
                <button type="button" onclick="closeModal('editServiceModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form action="actions/admin_service_update.php" method="POST" enctype="multipart/form-data">
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
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Price:</label>
                    <input type="number" step="0.01" name="price" id="edit_service_price" required class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Service Image (Leave blank to keep current):</label>
                    <input type="file" name="image" accept="image/*" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px;">
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

        function openViewServiceModal(service) {
            document.getElementById('view_service_name').textContent = service.name || service.service_name || '';
            document.getElementById('view_service_desc').textContent = service.description || 'No description provided.';
            document.getElementById('view_service_price').textContent = '₱' + parseFloat(service.price || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            const imgWrapper = document.getElementById('view_service_image_wrapper');
            const imgElement = document.getElementById('view_service_image');
            if (service.image) {
                imgElement.src = '../' + service.image;
                imgWrapper.style.display = 'block';
            } else {
                imgWrapper.style.display = 'none';
            }
            
            openModal('viewServiceModal');
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