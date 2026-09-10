<?php
// /admin/admin_promos.php
session_start();
require_once '../includes/config.php';
/** @var PDO $pdo */
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$query = "SELECT * FROM promo_claims ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Promo Claims Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_layout.css">
    <link rel="stylesheet" href="css/admin_products.css">
</head>
<body>

    <div class="admin-layout">
        <?php include 'includes/admin_sidebar.php'; ?>

        <!-- MAIN CONTENT AREA -->
        <main class="admin-main">
            <header class="main-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Promo Claims Management</h2>
                <button type="button" class="btn-primary" onclick="openModal('addClaimModal')" style="background: #3b82f6; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i> Add New Claim
                </button>
            </header>

            <div class="admin-content-body">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert-toast" style="background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 4px; margin-bottom: 20px;"><?= htmlspecialchars($_GET['msg']) ?></div>
                <?php endif; ?>

                <!-- Live Search & Filter Bar -->
                <div style="background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <input type="text" id="liveSearchInput" placeholder="Search customer name, phone, or bundle..." class="form-control" style="flex: 1; min-width: 220px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;">
                    <select id="statusFilterSelect" class="form-control" style="width: 180px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;">
                        <option value="">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="table-responsive" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
                    <table class="admin-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 12px 16px; color: #475569; font-size: 0.85rem;">ID</th>
                                <th style="padding: 12px 16px; color: #475569; font-size: 0.85rem;">CUSTOMER</th>
                                <th style="padding: 12px 16px; color: #475569; font-size: 0.85rem;">BUNDLE / OPTIONS</th>
                                <th style="padding: 12px 16px; color: #475569; font-size: 0.85rem;">APPOINTMENT</th>
                                <th style="padding: 12px 16px; color: #475569; font-size: 0.85rem;">STATUS</th>
                                <th style="padding: 12px 16px; color: #475569; font-size: 0.85rem;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($claims)): ?>
                                <tr id="noClaimsRow">
                                    <td colspan="6" class="text-center" style="padding: 40px; color: #64748b; text-align: center;">No promo claims found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($claims as $claim): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;" class="claim-row">
                                        <td style="padding: 14px 16px;"><strong>#<?= $claim['id'] ?></strong></td>
                                        <td style="padding: 14px 16px;">
                                            <span style="font-weight: 600; color: #1e293b;" class="search-target"><?= htmlspecialchars($claim['fullname']) ?></span><br>
                                            <small style="color: #64748b;" class="search-target"><?= htmlspecialchars($claim['phone']) ?></small>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="font-weight: 600; color: #0ea5e9;" class="search-target"><?= htmlspecialchars($claim['bundle_slug']) ?></span><br>
                                            <small style="color: #64748b;" class="search-target"><?= htmlspecialchars($claim['selected_option']) ?></small>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="color: #334155; font-weight: 500;" class="search-target"><?= htmlspecialchars($claim['appointment_date']) ?></span><br>
                                            <small style="color: #64748b;" class="search-target"><i class="far fa-clock"></i> <?= htmlspecialchars($claim['appointment_time']) ?></small>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <?php 
                                                $st = $claim['status'] ?? 'Pending';
                                                $badgeStyle = 'background: #fff3e0; color: #f57c00;';
                                                if ($st === 'Active' || $st === 'Confirmed' || $st === 'Completed' || $st === 'Approved') {
                                                    $badgeStyle = 'background: #dcfce7; color: #166534;';
                                                } elseif ($st === 'Cancelled') {
                                                    $badgeStyle = 'background: #fee2e2; color: #991b1b;';
                                                }
                                            ?>
                                            <span class="badge status-badge" style="padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; <?= $badgeStyle ?>">
                                                <?= htmlspecialchars($st) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <div style="display: flex; gap: 6px;">
                                                <button class="btn-sm" style="background: #64748b; border: none; color: #fff; padding: 6px 10px; border-radius: 4px; cursor: pointer;" title="View Details" onclick="openViewClaimModal(<?= htmlspecialchars(json_encode($claim), ENT_QUOTES, 'UTF-8') ?>)"><i class="fas fa-eye"></i></button>
                                                <button class="btn-sm" style="background: #3b82f6; border: none; color: #fff; padding: 6px 10px; border-radius: 4px; cursor: pointer;" title="Update Status" onclick="openEditClaimModal(<?= htmlspecialchars(json_encode($claim), ENT_QUOTES, 'UTF-8') ?>)"><i class="fas fa-edit"></i></button>
                                                <a href="actions/admin_promo_delete.php?id=<?= $claim['id'] ?>" class="btn-sm" style="background: #ef4444; border: none; color: #fff; padding: 6px 10px; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center;" title="Delete Claim" onclick="return confirm('Are you sure you want to delete this claim record?');"><i class="fas fa-trash"></i></a>
                                            </div>
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

    <!-- ADD CLAIM MODAL -->
    <div class="modal-overlay" id="addClaimModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
        <div class="modal-content" style="background: #fff; padding: 30px; border-radius: 8px; width: 450px; max-width: 90%;">
            <form action="actions/admin_promo_create.php" method="POST">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Add New Promo Claim</h3>
                    <button type="button" onclick="closeModal('addClaimModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                <div class="modal-body" style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Full Name:</label>
                        <input type="text" name="fullname" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Phone Number:</label>
                        <input type="tel" name="phone" inputmode="numeric" pattern="09[0-9]{9}" minlength="11" maxlength="11" title="Enter an 11-digit Philippine mobile number starting with 09" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Bundle Slug / Name:</label>
                        <input type="text" name="bundle_slug" required placeholder="e.g. summer-promo-bundle" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Selected Option / Details:</label>
                        <input type="text" name="selected_option" placeholder="e.g. Option A / Add-on specs" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Appointment Date:</label>
                            <input type="date" name="appointment_date" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Appointment Time:</label>
                            <input type="time" name="appointment_time" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Initial Status:</label>
                        <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="Active">Active</option>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addClaimModal')" style="padding: 8px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding: 8px 20px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Claim</button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW CLAIM MODAL -->
    <div class="modal-overlay" id="viewClaimModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
        <div class="modal-content" style="background: #fff; padding: 30px; border-radius: 8px; width: 450px; max-width: 90%;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Claim Details</h3>
                <button type="button" onclick="closeModal('viewClaimModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 12px;">
                <div>
                    <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Customer Name:</label>
                    <p id="view_claim_name" style="margin: 0; font-size: 1rem; color: #333; font-weight: 500;"></p>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Phone Number:</label>
                    <p id="view_claim_phone" style="margin: 0; font-size: 1rem; color: #333;"></p>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Bundle Slug:</label>
                    <p id="view_claim_bundle" style="margin: 0; font-size: 1rem; color: #0ea5e9; font-weight: 500;"></p>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Selected Options / Details:</label>
                    <p id="view_claim_option" style="margin: 0; font-size: 0.95rem; color: #333; background: #f8fafc; padding: 8px; border-radius: 4px;"></p>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Appointment Schedule:</label>
                    <p id="view_claim_appointment" style="margin: 0; font-size: 0.95rem; color: #333;"></p>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Status:</label>
                    <p id="view_claim_status" style="margin: 0; font-size: 0.95rem; font-weight: 600;"></p>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 25px;">
                <button type="button" class="btn-secondary" onclick="closeModal('viewClaimModal')" style="padding: 8px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Close</button>
            </div>
        </div>
    </div>

    <!-- UPDATE CLAIM STATUS MODAL -->
    <div class="modal-overlay" id="editClaimModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
        <div class="modal-content" style="background: #fff; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <form action="actions/admin_promo_update.php" method="POST">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Update Claim Status</h3>
                    <button type="button" onclick="closeModal('editClaimModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                <div class="modal-body" style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="hidden" name="id" id="edit_claim_id">
                    <div>
                        <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Customer:</label>
                        <p id="edit_claim_name_display" style="margin: 0; font-weight: 500; color: #333;"></p>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 3px;">Status:</label>
                        <select name="status" id="edit_claim_status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="Active">Active</option>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('editClaimModal')" style="padding: 8px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding: 8px 20px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Changes</button>
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

        function openViewClaimModal(claim) {
            document.getElementById('view_claim_name').textContent = claim.fullname || 'N/A';
            document.getElementById('view_claim_phone').textContent = claim.phone || 'N/A';
            document.getElementById('view_claim_bundle').textContent = claim.bundle_slug || 'N/A';
            document.getElementById('view_claim_option').textContent = claim.selected_option || 'None';
            document.getElementById('view_claim_appointment').textContent = (claim.appointment_date || 'N/A') + ' at ' + (claim.appointment_time || 'N/A');
            document.getElementById('view_claim_status').textContent = claim.status || 'Pending';

            openModal('viewClaimModal');
        }

        function openEditClaimModal(claim) {
            document.getElementById('edit_claim_id').value = claim.id;
            document.getElementById('edit_claim_name_display').textContent = claim.fullname || 'N/A';
            
            const statusSelect = document.getElementById('edit_claim_status');
            const currentStatus = claim.status || 'Pending';
            for (let option of statusSelect.options) {
                if (option.value === currentStatus) {
                    option.selected = true;
                    break;
                }
            }

            openModal('editClaimModal');
        }

        // Live Search and Instant Filtering Script
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('liveSearchInput');
            const statusSelect = document.getElementById('statusFilterSelect');
            const claimRows = document.querySelectorAll('.claim-row');

            function liveFilter() {
                const query = searchInput.value.toLowerCase().trim();
                const selectedStatus = statusSelect.value.toLowerCase();

                claimRows.forEach(row => {
                    const textContent = row.textContent.toLowerCase();
                    const statusBadge = row.querySelector('.status-badge');
                    const statusText = statusBadge ? statusBadge.textContent.toLowerCase() : '';

                    const matchesSearch = query === '' || textContent.includes(query);
                    const matchesStatus = selectedStatus === '' || statusText === selectedStatus;

                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', liveFilter);
            }
            if (statusSelect) {
                statusSelect.addEventListener('change', liveFilter);
            }
        });
    </script>
</body>
</html>