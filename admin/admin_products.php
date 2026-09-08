<?php
// /admin/admin_products.php
session_start();
require_once '../includes/config.php';
/** @var PDO $pdo */
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch all products ordered by ID in ascending order
$stmt = $pdo->query("SELECT id, name, price, image, active, featured FROM products ORDER BY id ASC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Products Management</title>
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
                <li class="active"><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="admin_inventory.php"><i class="fas fa-clipboard-list"></i> Inventory / Stock</a></li>
                <li><a href="admin_services.php"><i class="fas fa-tools"></i> Services</a></li>
                <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users / Customers</a></li>
                <li class="sidebar-logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="admin-main">
            <header class="main-header">
                <h2>Products Management</h2>
                <button class="btn-primary" onclick="openModal('addProductModal')">
                    <i class="fas fa-plus"></i> Add Product
                </button>
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
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No products found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><?= $p['id'] ?></td>
                                        <td>
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="../<?= htmlspecialchars($p['image']) ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <span class="text-muted">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($p['name']) ?></td>
                                        <td>₱<?= number_format($p['price'] ?? 0, 2) ?></td>
                                        <td>
                                            <span class="badge <?= ($p['active'] == 1) ? 'badge-success' : 'badge-danger' ?>">
                                                <?= ($p['active'] == 1) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge" style="<?= ($p['featured'] == 1) ? 'background: #dcfce7; color: #166534;' : 'background: #e2e8f0; color: #475569;' ?>">
                                                <?= ($p['featured'] == 1) ? 'Yes' : 'No' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-sm btn-warning" onclick="openViewModal(<?= htmlspecialchars(json_encode($p)) ?>)" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn-sm btn-info" onclick="openEditModal(<?= htmlspecialchars(json_encode($p)) ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                            <a href="actions/admin_product_delete.php?id=<?= $p['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?');" title="Delete"><i class="fas fa-trash"></i></a>
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

    <!-- VIEW PRODUCT MODAL -->
    <div class="modal-overlay" id="viewProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>View Product Details</h3>
                <button class="close-btn" onclick="closeModal('viewProductModal')">&times;</button>
            </div>
            <div style="text-align: center; margin-bottom: 15px;">
                <img id="view_image" src="" alt="Product Image" style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; display: none; margin: 0 auto;">
                <p id="view_no_image" class="text-muted" style="display: none; margin: 0;">No Image Available</p>
            </div>
            <div class="form-group">
                <label><strong>ID:</strong></label>
                <p id="view_id" class="form-control-static" style="margin: 0; color: #1e293b;"></p>
            </div>
            <div class="form-group">
                <label><strong>Product Name:</strong></label>
                <p id="view_name" class="form-control-static" style="margin: 0; color: #1e293b;"></p>
            </div>
            <div class="form-group">
                <label><strong>Price:</strong></label>
                <p id="view_price" class="form-control-static" style="margin: 0; color: #1e293b;"></p>
            </div>
            <div class="form-group">
                <label><strong>Active Status:</strong></label>
                <p id="view_active" class="form-control-static" style="margin: 0; color: #1e293b;"></p>
            </div>
            <div class="form-group">
                <label><strong>Featured:</strong></label>
                <p id="view_featured" class="form-control-static" style="margin: 0; color: #1e293b;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('viewProductModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- ADD PRODUCT MODAL -->
    <div class="modal-overlay" id="addProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Product</h3>
                <button class="close-btn" onclick="closeModal('addProductModal')">&times;</button>
            </div>
            <form action="actions/admin_product_create.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group">
                    <label>Product Name:</label>
                    <input type="text" name="name" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Price:</label>
                    <input type="number" step="0.01" name="price" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Image Path / File:</label>
                    <input type="file" name="image" class="form-control">
                </div>
                <div class="form-group">
                    <label>Active Status:</label>
                    <select name="active" class="form-control">
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Featured:</label>
                    <select name="featured" class="form-control">
                        <option value="0" selected>No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('addProductModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT PRODUCT MODAL -->
    <div class="modal-overlay" id="editProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Product</h3>
                <button class="close-btn" onclick="closeModal('editProductModal')">&times;</button>
            </div>
            <form action="actions/admin_product_update.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Product Name:</label>
                    <input type="text" name="name" id="edit_name" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Price:</label>
                    <input type="number" step="0.01" name="price" id="edit_price" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Image (Leave blank to keep current):</label>
                    <input type="file" name="image" class="form-control">
                </div>
                <div class="form-group">
                    <label>Active Status:</label>
                    <select name="active" id="edit_active" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Featured:</label>
                    <select name="featured" id="edit_featured" class="form-control">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('editProductModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Control Helper Functions
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
            document.getElementById('view_active').innerText = product.active == 1 ? 'Active' : 'Inactive';
            document.getElementById('view_featured').innerText = product.featured == 1 ? 'Yes' : 'No';

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

            openModal('viewProductModal');
        }

        function openEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_active').value = product.active;
            document.getElementById('edit_featured').value = product.featured;
            openModal('editProductModal');
        }
    </script>
</body>
</html>