<?php
// promo_history.php - Backend and HTML markup for promo_claims table
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;

// Fallback user details from session
$sessionUserName = $_SESSION['user_name'] ?? $_SESSION['fullname'] ?? $_SESSION['full_name'] ?? $_SESSION['name'] ?? '';
$sessionUserPhone = $_SESSION['phone'] ?? $_SESSION['contact'] ?? '';

// Handle Deletion (CRUD Delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $claimId = intval($_GET['id']);
    if ($isAdmin) {
        $stmt = $pdo->prepare("DELETE FROM promo_claims WHERE id = ?");
        $stmt->execute([$claimId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM promo_claims WHERE id = ? AND user_id = ?");
        $stmt->execute([$claimId, $userId]);
    }
    header('Location: promo_history.php');
    exit;
}

// Fetch records cleanly from promo_claims
if ($isAdmin) {
    $stmt = $pdo->query("SELECT * FROM promo_claims ORDER BY appointment_date ASC, appointment_time ASC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM promo_claims WHERE user_id = ? ORDER BY appointment_date ASC, appointment_time ASC");
    $stmt->execute([$userId]);
}
$claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo Claim & Appointment History - Bencalo MotoWorks</title>
    <link rel="stylesheet" href="styles/index/style.css">
    <link rel="stylesheet" href="styles/index/components.css">
    <link rel="stylesheet" href="styles/index/layout.css">
    <link rel="stylesheet" href="styles/promo_history.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alert-toast {
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            padding: 15px 20px; border-radius: 6px; color: #fff; font-weight: 500;
            display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .alert-success { background-color: #2ed573; }
        .alert-error { background-color: #ff4757; }
        
        .modal-details p {
            margin-bottom: 10px;
            font-size: 0.95rem;
            color: #ddd;
        }
        .modal-details strong {
            color: #fff;
        }

        /* Unified Uniform Sizing for Badges and Action Buttons */
        .badge,
        .table-actions .btn-view,
        .table-actions .btn-edit,
        .table-actions .btn-cancel {
            box-sizing: border-box;
            height: 36px;
            min-width: 95px;
            padding: 0 14px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            white-space: nowrap;
            transition: opacity 0.2s;
        }

        /* Status Badge Colors */
        .badge-pending {
            background-color: transparent;
            color: #ff9f43;
            border: 1px solid #ff9f43 !important;
        }
        .badge-completed {
            background-color: transparent;
            color: #2ed573;
            border: 1px solid #2ed573 !important;
        }

        /* Table Action Buttons Colors */
        .table-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .table-actions .btn-view {
            background-color: #2b2f38;
            border: 1px solid #3f4451;
            color: #fff;
        }
        .table-actions .btn-edit {
            background-color: #0b5ed7;
            color: #fff;
        }
        .table-actions .btn-cancel {
            background-color: #dc3545;
            color: #fff;
        }
        .table-actions button:hover,
        .table-actions a:hover {
            opacity: 0.85;
        }
    </style>
</head>

<body>
    <?php require_once 'components/header.php'; ?>

    <div id="alertToast" class="alert-toast"></div>

    <main class="history-wrapper">
        <div class="history-container">
            <h2 class="history-title">Promo Claim & Appointment History</h2>
            <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>

            <div class="order-table-wrapper">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Bundle Slug</th>
                            <th>Selected Option</th>
                            <th>Date & Time</th>
                            <th>Contact Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($claims)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">No promo claims found.</td>
                            </tr>
                        <?php else: ?>
                            <?php $index = 1;
                            foreach ($claims as $claim): 
                                $claimJson = htmlspecialchars(json_encode($claim), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr>
                                    <td><strong><?= $index++ ?></strong></td>
                                    <td><span class="bundle-name"><?= htmlspecialchars($claim['bundle_slug'] ?? 'N/A') ?></span></td>
                                    <td><?= htmlspecialchars($claim['selected_option'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= !empty($claim['appointment_date']) ? date('F j, Y', strtotime($claim['appointment_date'])) : 'N/A' ?>
                                        <span class="appointment-meta"><?= htmlspecialchars($claim['appointment_time'] ?? '') ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($claim['phone'] ?? $sessionUserPhone ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge <?= strtolower($claim['status'] ?? '') === 'completed' ? 'badge-completed' : 'badge-pending' ?>">
                                            <?= htmlspecialchars($claim['status'] ?? 'Pending') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="btn-view" onclick='openViewModal(<?= $claimJson ?>)'><i class="fa-solid fa-eye"></i> View</button>
                                            <button type="button" class="btn-edit" onclick='openEditModal(<?= $claimJson ?>)'><i class="fa-solid fa-pen"></i> Edit</button>
                                            <a href="promo_history.php?action=delete&id=<?= $claim['id'] ?>" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel and delete this promo claim?');"><i class="fa-solid fa-trash"></i> Cancel</a>
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

    <!-- View Modal Structure -->
    <div id="viewModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:1000;">
        <div class="modal-content" style="background:#1e2124; padding:30px; border-radius:8px; width:450px; color:#fff; position:relative;">
            <h3 style="margin-bottom: 20px; color:#00FFFF;"><i class="fa-solid fa-circle-info"></i> Promo Claim Details</h3>
            
            <div id="viewModalContent" class="modal-details" style="background: #111; padding: 20px; border-radius: 6px; border: 1px solid #333;">
                <!-- Populated via JavaScript -->
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 20px;">
                <button type="button" onclick="closeViewModal()" style="padding:10px 20px; background:#333; border:none; color:#fff; border-radius:4px; cursor:pointer;">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Modal Structure -->
    <div id="editModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:1000;">
        <div class="modal-content" style="background:#1e2124; padding:30px; border-radius:8px; width:450px; color:#fff; position:relative;">
            <h3 style="margin-bottom: 15px; color:#00FFFF;"><i class="fa-solid fa-pen-to-square"></i> Edit Claim Appointment</h3>
            <form id="editForm" onsubmit="submitEditClaim(event)">
                <input type="hidden" id="editClaimId" name="claim_id">
                
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Full Name</label>
                    <input type="text" id="editFullname" name="fullname" required style="width:100%; padding:10px; background:#111; border:1px solid #333; color:#fff; border-radius:4px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Contact Phone</label>
                    <input type="text" id="editPhone" name="phone" required style="width:100%; padding:10px; background:#111; border:1px solid #333; color:#fff; border-radius:4px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Appointment Date</label>
                    <input type="date" id="editDate" name="appointment_date" required min="<?= date('Y-m-d') ?>" style="width:100%; padding:10px; background:#111; border:1px solid #333; color:#fff; border-radius:4px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Preferred Time Slot</label>
                    <select id="editTime" name="appointment_time" required style="width:100%; padding:10px; background:#111; border:1px solid #333; color:#fff; border-radius:4px;">
                        <option value="09:00 AM - 10:30 AM">09:00 AM - 10:30 AM</option>
                        <option value="10:30 AM - 12:00 PM">10:30 AM - 12:00 PM</option>
                        <option value="01:00 PM - 02:30 PM">01:00 PM - 02:30 PM</option>
                        <option value="04:00 PM - 05:30 PM">04:00 PM - 05:30 PM</option>
                    </select>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeEditModal()" style="padding:10px 20px; background:#333; border:none; color:#fff; border-radius:4px; cursor:pointer;">Cancel</button>
                    <button type="submit" id="submitEditBtn" style="padding:10px 20px; background:#00FFFF; border:none; color:#000; font-weight:bold; border-radius:4px; cursor:pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <?php require_once 'components/footer.php'; ?>

    <script>
        const sessionDefaultName = "<?= htmlspecialchars($sessionUserName, ENT_QUOTES) ?>";
        const sessionDefaultPhone = "<?= htmlspecialchars($sessionUserPhone, ENT_QUOTES) ?>";

        function showAlert(message, type) {
            const toast = document.getElementById('alertToast');
            toast.textContent = message;
            toast.className = 'alert-toast ' + (type === 'success' ? 'alert-success' : 'alert-error');
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 4000);
        }

        function openViewModal(claim) {
            const modalContent = document.getElementById('viewModalContent');
            modalContent.innerHTML = `
                <p><strong>Bundle Slug:</strong> ${claim.bundle_slug || 'N/A'}</p>
                <p><strong>Selected Option:</strong> ${claim.selected_option || 'N/A'}</p>
                <hr style="border-color: #333; margin: 10px 0;">
                <p><strong>Full Name:</strong> ${claim.fullname || sessionDefaultName || 'N/A'}</p>
                <p><strong>Contact Phone:</strong> ${claim.phone || sessionDefaultPhone || 'N/A'}</p>
                <p><strong>Appointment Date:</strong> ${claim.appointment_date || 'N/A'}</p>
                <p><strong>Time Slot:</strong> ${claim.appointment_time || 'N/A'}</p>
                <p><strong>Current Status:</strong> <span style="color: #00FFFF; font-weight: bold;">${claim.status || 'Pending'}</span></p>
            `;
            document.getElementById('viewModal').style.display = 'flex';
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function openEditModal(claim) {
            document.getElementById('editClaimId').value = claim.id;
            document.getElementById('editFullname').value = claim.fullname || sessionDefaultName;
            document.getElementById('editPhone').value = claim.phone || sessionDefaultPhone;
            document.getElementById('editDate').value = claim.appointment_date || '';
            
            if (claim.appointment_time) {
                document.getElementById('editTime').value = claim.appointment_time;
            }
            
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function submitEditClaim(event) {
            event.preventDefault();
            const btn = document.getElementById('submitEditBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            const formData = new FormData(document.getElementById('editForm'));

            fetch('update_claim.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = 'Save Changes';
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeEditModal();
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.textContent = 'Save Changes';
                showAlert('An unexpected network error occurred.', 'error');
            });
        }

        window.onclick = function(event) {
            const viewModal = document.getElementById('viewModal');
            const editModal = document.getElementById('editModal');
            if (event.target === viewModal) closeViewModal();
            if (event.target === editModal) closeEditModal();
        }
    </script>
</body>

</html>