<?php
// /admin/admin_inventory.php
session_start();
require_once '../includes/config.php';
/** @var PDO $pdo */
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch all products ordered by ID in ascending order for stock tracking
$stmt = $pdo->query("SELECT id, name, price, stock, image, active, featured FROM products ORDER BY id ASC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Inventory & Stock Management</title>
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
                <li class="active"><a href="admin_inventory.php"><i class="fas fa-clipboard-list"></i> Inventory / Stock</a></li>
                <li><a href="admin_services.php"><i class="fas fa-tools"></i> Services</a></li>
                <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users / Customers</a></li>
                <li class="sidebar-logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="admin-main">
            <header class="main-header">
                <h2>Inventory & Stock Management</h2>
            </header>

            <div class="admin-content-body">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert-toast"><?= htmlspecialchars($_GET['msg']) ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Stock Level</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No inventory items found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><?= $p['id'] ?></td>
                                        <td>
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="../<?= htmlspecialchars($p['image']) ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
                                            <?php else: ?>
                                                <span class="text-muted">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($p['name']) ?></td>
                                        <td>₱<?= number_format($p['price'] ?? 0, 2) ?></td>
                                        <td>
                                            <span class="badge <?= ($p['stock'] > 0) ? 'badge-success' : 'badge-danger' ?>">
                                                <?= $p['stock'] ?> units
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= ($p['active'] == 1) ? 'badge-success' : 'badge-danger' ?>">
                                                <?= ($p['active'] == 1) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-sm btn-warning" onclick="openViewModal(<?= htmlspecialchars(json_encode($p)) ?>)" title="View Stock Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-sm btn-info" onclick="openEditModal(<?= htmlspecialchars(json_encode($p)) ?>)" title="Update Stock"><i class="fas fa-edit"></i></button>
                                            <a href="actions/admin_product_delete.php?id=<?= $p['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product and its inventory?');" title="Delete Item"><i class="fas fa-trash"></i></a>
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

    <!-- VIEW INVENTORY MODAL -->
    <div class="modal-overlay" id="viewInventoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>View Stock Details</h3>
                <button class="close-btn" onclick="closeModal('viewInventoryModal')">&times;</button>
            </div>
            <div style="text-align: center; margin-bottom: 20px;">
                <img id="view_image" src="" alt="Product Image" style="width: 90px; height: 90px; object-fit: cover; border-radius: 8px; display: none; margin: 0 auto; border: 1px solid #e2e8f0;">
                <p id="view_no_image" class="text-muted" style="display: none; margin: 0;">No Image Available</p>
            </div>
            <div class="form-group">
                <label>ID:</label>
                <div id="view_id" class="form-control-static"></div>
            </div>
            <div class="form-group">
                <label>Product Name:</label>
                <div id="view_name" class="form-control-static"></div>
            </div>
            <div class="form-group">
                <label>Price:</label>
                <div id="view_price" class="form-control-static"></div>
            </div>
            <div class="form-group">
                <label>Current Stock Quantity:</label>
                <div id="view_stock" class="form-control-static" style="font-weight: 600; color: #2563eb;"></div>
            </div>
            <div class="form-group">
                <label>Active Status:</label>
                <div id="view_active" class="form-control-static"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('viewInventoryModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- EDIT INVENTORY MODAL -->
    <div class="modal-overlay" id="editInventoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Stock Quantity</h3>
                <button class="close-btn" onclick="closeModal('editInventoryModal')">&times;</button>
            </div>
            <form action="actions/admin_inventory_update.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Product Name:</label>
                    <input type="text" id="edit_name" class="form-control" disabled style="background-color: #f1f5f9; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label>Stock Quantity:</label>
                    <input type="number" name="stock" id="edit_stock" min="0" required class="form-control" autofocus>
                    <small class="text-muted" style="margin-top: 4px; display: block;">Enter the exact new stock count for this item.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('editInventoryModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Stock</button>
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

        function openViewModal(product) {
            document.getElementById('view_id').innerText = product.id;
            document.getElementById('view_name').innerText = product.name;
            document.getElementById('view_price').innerText = '₱' + parseFloat(product.price).toFixed(2);
            document.getElementById('view_stock').innerText = product.stock + ' units available';
            document.getElementById('view_active').innerText = product.active == 1 ? 'Active' : 'Inactive';

            const imgElement = document.getElementById('view_image');
            const noImgElement = document.getElementById('view_no_image');

            if (product.image && product.image.trim() !== '') {
                imgElement.src = '../' + product.image;
                imgElement.style.display = 'block';
                noImgElement.style.display = 'none';
            } else {
                imgElement.style.display = 'none';
                noImgElement.style.display = 'block';
            }

            openModal('viewInventoryModal');
        }

        function openEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_stock').value = product.stock;
            openModal('editInventoryModal');
        }
    </script>
</body>
</html>