<?php
// /admin/admin_users.php
session_start();
require_once '../includes/config.php';
/** @var PDO $pdo */
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch all registered users/customers (excluding passwords)
$stmt = $pdo->query("SELECT id, username, first_name, last_name, email, phone, role, created_at FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Users & Customers Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_layout.css">
    <link rel="stylesheet" href="css/admin_products.css">
    <style>
        .action-btn { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            width: 30px; 
            height: 30px; 
            border: none; 
            border-radius: 4px; 
            color: #fff; 
            cursor: pointer; 
            font-size: 0.85rem; 
            margin-right: 4px; 
            transition: opacity 0.2s; 
        }
        .view-btn { background-color: #6c757d; }
        .edit-btn { background-color: #1976d2; }
        .delete-btn { background-color: #d32f2f; }
        .action-btn:hover { opacity: 0.85; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); justify-content: center; align-items: center; z-index: 1000; }
        .modal-box { background: #fff; padding: 25px 30px; border-radius: 8px; width: 100%; max-width: 450px; position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.15); color: #333; }
        .modal-box h3 { margin-bottom: 20px; font-size: 1.3rem; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem; color: #555; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-submit { background: #1976d2; color: #fff; padding: 8px 16px; border: none; border-radius: 4px; font-weight: 500; cursor: pointer; }
        .btn-secondary { background: #e0e0e0; color: #333; padding: 8px 16px; border: none; border-radius: 4px; font-weight: 500; cursor: pointer; }
        .btn-danger { background: #d32f2f; color: #fff; padding: 8px 16px; border: none; border-radius: 4px; font-weight: 500; cursor: pointer; }
        .close-modal { position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #777; }
        .detail-row { margin-bottom: 10px; font-size: 0.95rem; }
        .detail-row strong { display: inline-block; width: 110px; color: #555; }
    </style>
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
                <li class="active"><a href="actions/admin_users.php"><i class="fas fa-users"></i> Users / Customers</a></li>
                <li class="sidebar-logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="admin-main">
            <header class="main-header">
                <h2>Users & Customers Management</h2>
                <div class="admin-user-info">
                    <span>Account Directory</span>
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
                                <th>ID</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= $u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?: 'N/A' ?></td>
                                        <td><?= htmlspecialchars($u['email'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge" style="padding: 4px 8px; border-radius: 4px; background: 
                                                <?= ($u['role'] === 'admin') ? '#e3f2fd; color: #1976d2;' : '#f3e5f5; color: #7b1fa2;' ?>">
                                                <?= htmlspecialchars(ucfirst($u['role'] ?? 'user')) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($u['created_at'] ?? 'N/A') ?></td>
                                        <td>
                                            <button type="button" class="action-btn view-btn" title="View User" onclick='openViewModal(<?= json_encode($u) ?>)'><i class="fas fa-eye"></i></button>
                                            <button type="button" class="action-btn edit-btn" title="Edit User" onclick='openEditModal(<?= json_encode($u) ?>)'><i class="fas fa-pen-to-square"></i></button>
                                            <button type="button" class="action-btn delete-btn" title="Delete User" onclick='openDeleteModal(<?= $u['id'] ?>)'><i class="fas fa-trash"></i></button>
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

    <!-- VIEW MODAL -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal-box">
            <button type="button" class="close-modal" onclick="closeModals()">&times;</button>
            <h3>User Profile Details</h3>
            <div class="detail-row"><strong>User ID:</strong> <span id="view-id"></span></div>
            <div class="detail-row"><strong>First Name:</strong> <span id="view-firstname"></span></div>
            <div class="detail-row"><strong>Last Name:</strong> <span id="view-lastname"></span></div>
            <div class="detail-row"><strong>Username:</strong> <span id="view-username"></span></div>
            <div class="detail-row"><strong>Email:</strong> <span id="view-email"></span></div>
            <div class="detail-row"><strong>Phone:</strong> <span id="view-phone"></span></div>
            <div class="detail-row"><strong>Role:</strong> <span id="view-role"></span></div>
            <div class="detail-row"><strong>Joined Date:</strong> <span id="view-date"></span></div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModals()">Close</button>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <button type="button" class="close-modal" onclick="closeModals()">&times;</button>
            <h3>Edit User Account</h3>
            <form method="POST" action="actions/admin_users_update.php">
                <input type="hidden" name="user_id" id="edit-id">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" id="edit-firstname" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" id="edit-lastname" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="edit-username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="edit-email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" id="edit-phone" class="form-control">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit-role" class="form-control" required>
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModals()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE MODAL -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <button type="button" class="close-modal" onclick="closeModals()">&times;</button>
            <h3>Confirm User Deletion</h3>
            <p style="color: #555; margin-bottom: 20px;">Are you sure you want to delete this user account? This action is permanent and cannot be undone.</p>
            <form method="POST" action="actions/admin_users_delete.php">
                <input type="hidden" name="user_id" id="delete-id">
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModals()">Cancel</button>
                    <button type="submit" class="btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openViewModal(user) {
            document.getElementById('view-id').innerText = '#' + user.id;
            document.getElementById('view-firstname').innerText = user.first_name || 'N/A';
            document.getElementById('view-lastname').innerText = user.last_name || 'N/A';
            document.getElementById('view-username').innerText = user.username || 'N/A';
            document.getElementById('view-email').innerText = user.email || 'N/A';
            document.getElementById('view-phone').innerText = user.phone || 'N/A';
            document.getElementById('view-role').innerText = (user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'User');
            document.getElementById('view-date').innerText = user.created_at || 'N/A';
            document.getElementById('viewModal').style.display = 'flex';
        }

        function openEditModal(user) {
            document.getElementById('edit-id').value = user.id;
            document.getElementById('edit-firstname').value = user.first_name || '';
            document.getElementById('edit-lastname').value = user.last_name || '';
            document.getElementById('edit-username').value = user.username || '';
            document.getElementById('edit-email').value = user.email || '';
            document.getElementById('edit-phone').value = user.phone || '';
            document.getElementById('edit-role').value = user.role || 'customer';
            document.getElementById('editModal').style.display = 'flex';
        }

        function openDeleteModal(userId) {
            document.getElementById('delete-id').value = userId;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeModals() {
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.style.display = 'none';
            });
        }

        window.onclick = function(event) {
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>