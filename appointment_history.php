<?php
session_start();

// 1. Load Database Config
if (file_exists('config.php')) {
    require_once 'config.php';
} elseif (file_exists('includes/config.php')) {
    require_once 'includes/config.php';
}

// 2. Load Functions & Auth Helpers
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
}
if (file_exists('includes/auth.php')) {
    require_once 'includes/auth.php';
}

// 3. Normalize Database Connection Object ($conn or $pdo)
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

// 4. Resolve Logged-in User ID across standard session keys
$user_id = $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? $_SESSION['user']['id']
    ?? $_SESSION['user_id_pk']
    ?? null;

if (!$user_id) {
    header('Location: login.php');
    exit();
}

$feedback = '';

// 5. Handle CRUD POST Actions (Update & Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crud_action']) && isset($pdo)) {
    $action = $_POST['crud_action'];

    if ($action === 'update') {
        $booking_id     = $_POST['booking_id'] ?? null;
        $service_id     = $_POST['service_id'] ?? null;
        $contact_number = $_POST['contact_number'] ?? '';
        $vehicle_model  = $_POST['vehicle_model'] ?? '';
        $booking_date   = $_POST['booking_date'] ?? '';
        $booking_time   = $_POST['booking_time'] ?? '';
        $notes          = $_POST['notes'] ?? '';

        try {
            $stmt = $pdo->prepare("
                UPDATE service_bookings 
                SET service_id = ?, contact_number = ?, vehicle_model = ?, booking_date = ?, booking_time = ?, notes = ? 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$service_id, $contact_number, $vehicle_model, $booking_date, $booking_time, $notes, $booking_id, $user_id]);
            $feedback = 'updated';
        } catch (PDOException $e) {
            $feedback = 'error';
        }
    } elseif ($action === 'delete') {
        $booking_id = $_POST['booking_id'] ?? null;

        try {
            $stmt = $pdo->prepare("DELETE FROM service_bookings WHERE id = ? AND user_id = ?");
            $stmt->execute([$booking_id, $user_id]);
            $feedback = 'deleted';
        } catch (PDOException $e) {
            $feedback = 'error';
        }
    }

    header("Location: appointment_history.php?msg={$feedback}");
    exit();
}

// Fetch data cleanly using functions from includes/functions.php
$services_list = isset($pdo) ? getAllServices($pdo) : [];
$services_map  = isset($pdo) ? getServicesMap($pdo) : [];
$appointments  = isset($pdo) ? getUserAppointments($pdo, $user_id) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment History - Bencalo Motoworks</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles/index/style.css">
    <link rel="stylesheet" href="styles/index/components.css">
    <link rel="stylesheet" href="styles/index/layout.css">
    <link rel="stylesheet" href="styles/index/modals.css">
    <link rel="stylesheet" href="styles/appointment_history.css">
</head>

<body>

    <?php include_once 'components/header.php'; ?>

    <main class="appointment-history-container container">
        <div class="section-title">
            <h2>Appointment History</h2>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert-toast">
                <?php
                if ($_GET['msg'] === 'updated') echo "Appointment updated successfully!";
                if ($_GET['msg'] === 'deleted') echo "Appointment cancelled/deleted!";
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($appointments)): ?>
            <div class="empty-history-card">
                <p>No appointments recorded yet.</p>
                <a href="services.php" class="btn-buy-now">Book a Service</a>
            </div>
        <?php else: ?>
            <div class="appointment-table-wrapper">
                <table class="appointment-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Vehicle</th>
                            <th>Date & Time</th>
                            <th>Contact</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th><span style="position: relative; left: -35px;">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $app):
                            $s_id = $app['service_id'] ?? null;
                            $service_info = $services_map[$s_id] ?? ['name' => 'Service #' . ($s_id ?? 'N/A'), 'price' => 0];
                            $status = $app['status'] ?? 'Pending';

                            $app_data = array_merge($app, [
                                'service_name' => $service_info['name'],
                                'service_price' => number_format((float)$service_info['price'], 2)
                            ]);
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($service_info['name']) ?></strong></td>
                                <td><?= htmlspecialchars($app['vehicle_model'] ?? 'N/A') ?></td>
                                <td>
                                    <div><?= date('F j, Y', strtotime($app['booking_date'])) ?></div>
                                    <small style="color: #8b949e;"><?= htmlspecialchars($app['booking_time']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($app['contact_number']) ?></td>
                                <td>₱<?= number_format((float)$service_info['price'], 2) ?></td>
                                <td>
                                    <span class="badge-status-<?= strtolower(htmlspecialchars($status)) ?>">
                                        <?= ucfirst(htmlspecialchars($status)) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions" style="justify-content: flex-end;">
                                        <button class="btn-row-action btn-view" onclick='openViewModal(<?= json_encode($app_data) ?>)'>
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn-row-action btn-edit" onclick='openEditModal(<?= json_encode($app) ?>)'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                            <input type="hidden" name="crud_action" value="delete">
                                            <input type="hidden" name="booking_id" value="<?= $app['id'] ?>">
                                            <button type="submit" class="btn-row-action btn-delete">
                                                <i class="fas fa-trash"></i> Cancel
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <!-- VIEW MODAL -->
    <div class="modal-overlay" id="viewModal">
        <div class="crud-modal-content">
            <div class="crud-modal-header">
                <h3>Appointment Details</h3>
                <button class="close-modal-btn" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="view-detail-group">
                <span class="detail-label">Service</span>
                <span class="detail-value highlight" id="view_service_name">-</span>
            </div>
            <div class="view-detail-group">
                <span class="detail-label">Status</span>
                <span id="view_status_badge">-</span>
            </div>
            <div class="view-detail-group">
                <span class="detail-label">Vehicle Model</span>
                <span class="detail-value" id="view_vehicle_model">-</span>
            </div>
            <div class="view-detail-group">
                <span class="detail-label">Booking Date & Time</span>
                <span class="detail-value" id="view_date_time">-</span>
            </div>
            <div class="view-detail-group">
                <span class="detail-label">Contact Number</span>
                <span class="detail-value" id="view_contact_number">-</span>
            </div>
            <div class="view-detail-group">
                <span class="detail-label">Total Price</span>
                <span class="detail-value highlight" id="view_price">-</span>
            </div>
            <div class="view-detail-group">
                <span class="detail-label">Notes</span>
                <span class="detail-value" id="view_notes" style="font-style: italic;">-</span>
            </div>
            <button class="btn-row-action btn-view" style="width:100%; justify-content:center; margin-top:20px; padding:10px;" onclick="closeModal('viewModal')">
                Close
            </button>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal-overlay" id="editModal">
        <div class="crud-modal-content">
            <div class="crud-modal-header">
                <h3>Update Appointment</h3>
                <button class="close-modal-btn" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="crud_action" value="update">
                <input type="hidden" name="booking_id" id="edit_booking_id">

                <div class="form-group">
                    <label>Service</label>
                    <select name="service_id" id="edit_service_id" class="form-control" required>
                        <?php foreach ($services_list as $svc):
                            $svc_id = $svc['service_id'] ?? $svc['id'];
                            $svc_name = $svc['service_name'] ?? $svc['title'] ?? $svc['name'];
                        ?>
                            <option value="<?= $svc_id ?>"><?= htmlspecialchars($svc_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Vehicle Model</label>
                    <input type="text" name="vehicle_model" id="edit_vehicle_model" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" id="edit_contact_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Booking Date</label>
                    <input type="date" name="booking_date" id="edit_booking_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Time Slot</label>
                    <select name="booking_time" id="edit_booking_time" class="form-control" required>
                        <option value="09:00 AM - 10:30 AM">09:00 AM - 10:30 AM</option>
                        <option value="10:30 AM - 12:00 PM">10:30 AM - 12:00 PM</option>
                        <option value="01:30 PM - 03:00 PM">01:30 PM - 03:00 PM</option>
                        <option value="03:00 PM - 04:30 PM">03:00 PM - 04:30 PM</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn-row-action btn-edit" style="width:100%; justify-content:center; margin-top:10px; padding:10px;">
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    <?php include_once 'components/footer.php'; ?>

    <script>
        function openViewModal(booking) {
            document.getElementById('view_service_name').textContent = booking.service_name;
            document.getElementById('view_vehicle_model').textContent = booking.vehicle_model || 'N/A';
            document.getElementById('view_date_time').textContent = booking.booking_date + ' (' + booking.booking_time + ')';
            document.getElementById('view_contact_number').textContent = booking.contact_number;
            document.getElementById('view_price').textContent = '₱' + booking.service_price;
            document.getElementById('view_notes').textContent = booking.notes || 'No notes provided.';

            const status = booking.status || 'Pending';
            const statusBadge = document.getElementById('view_status_badge');
            statusBadge.className = 'badge-status-' + status.toLowerCase();
            statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);

            document.getElementById('viewModal').classList.add('active');
        }

        function openEditModal(booking) {
            document.getElementById('edit_booking_id').value = booking.id;
            document.getElementById('edit_service_id').value = booking.service_id;
            document.getElementById('edit_vehicle_model').value = booking.vehicle_model || '';
            document.getElementById('edit_contact_number').value = booking.contact_number || '';
            document.getElementById('edit_booking_date').value = booking.booking_date;
            document.getElementById('edit_booking_time').value = booking.booking_time;
            document.getElementById('edit_notes').value = booking.notes || '';
            document.getElementById('editModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
    </script>

</body>

</html>