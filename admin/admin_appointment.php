<?php
// /admin/admin_appointment.php
session_start();
require_once '../includes/config.php';
/** @var PDO $pdo */
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Handle status update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        header('Location: admin_appointment.php?msg=Invalid+Security+Token');
        exit();
    }

    $apt_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $new_status = $_POST['status'] ?? '';
    $allowed_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

    if ($apt_id && in_array($new_status, $allowed_statuses)) {
        $stmt = $pdo->prepare("UPDATE service_bookings SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $apt_id]);
        header('Location: admin_appointment.php?msg=Appointment+status+updated+successfully');
        exit();
    } else {
        header('Location: admin_appointment.php?msg=Invalid+appointment+parameters');
        exit();
    }
}

// Handle creation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appointment'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        header('Location: admin_appointment.php?msg=Invalid+Security+Token');
        exit();
    }

    $full_name = trim($_POST['full_name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $vehicle_model = trim($_POST['vehicle_model'] ?? '');
    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $booking_date = trim($_POST['booking_date'] ?? '');
    $booking_time = trim($_POST['booking_time'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($full_name) && $service_id && !empty($booking_date) && !empty($booking_time)) {
        $stmt = $pdo->prepare("INSERT INTO service_bookings (full_name, contact_number, vehicle_model, service_id, booking_date, booking_time, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $contact_number, $vehicle_model, $service_id, $booking_date, $booking_time, $status, $notes]);
        header('Location: admin_appointment.php?msg=Appointment+successfully+created');
        exit();
    } else {
        header('Location: admin_appointment.php?msg=Please+fill+in+all+required+fields');
        exit();
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $apt_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($apt_id) {
        $stmt = $pdo->prepare("DELETE FROM service_bookings WHERE id = ?");
        $stmt->execute([$apt_id]);
        header('Location: admin_appointment.php?msg=Appointment+deleted+successfully');
        exit();
    }
}

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$query = "SELECT sb.*, s.name as service_name, s.price as service_price, u.email as user_email 
          FROM service_bookings sb 
          LEFT JOIN services s ON sb.service_id = s.id 
          LEFT JOIN users u ON sb.user_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (sb.full_name LIKE ? OR sb.contact_number LIKE ? OR sb.vehicle_model LIKE ? OR s.name LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($status_filter)) {
    $query .= " AND sb.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY sb.booking_date DESC, sb.booking_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch services for the create modal dropdown
$services_stmt = $pdo->query("SELECT id, name, price FROM services ORDER BY name ASC");
$services_list = $services_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MotoWorks Admin - Appointments Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_layout.css">
    <link rel="stylesheet" href="css/admin_products.css">
</head>
<body>

    <div class="admin-layout">
        <?php include 'includes/admin_sidebar.php'; ?>

        <!-- MAIN CONTENT AREA -->
        <main class="admin-main">
            <header class="main-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Appointments Management</h2>
                <button type="button" class="btn-primary" onclick="openModal('createAppointmentModal')" style="background-color: #2563eb; color: #fff; padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.9rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <i class="fas fa-plus"></i> Add Appointment
                </button>
            </header>

            <div class="admin-content-body">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert-toast" style="background: #e0f2fe; color: #0369a1; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #bae6fd; font-weight: 500;"><?= htmlspecialchars($_GET['msg']) ?></div>
                <?php endif; ?>

                <!-- Live Search & Status Filter Form -->
                <div style="background: #fff; padding: 16px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                    <form method="GET" action="admin_appointment.php" id="filterForm" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                        <input type="text" name="search" id="searchInput" placeholder="Type to search customer, vehicle, service..." value="<?= htmlspecialchars($search) ?>" class="form-control" style="flex: 1; min-width: 220px; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;" autocomplete="off">
                        <select name="status" id="statusSelect" class="form-control" style="width: 180px; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background-color: #fff;">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Confirmed" <?= $status_filter === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </form>
                </div>

                <div class="table-responsive" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden;">
                    <table class="admin-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                <th style="padding: 14px 16px;">ID</th>
                                <th style="padding: 14px 16px;">Customer</th>
                                <th style="padding: 14px 16px;">Vehicle</th>
                                <th style="padding: 14px 16px;">Service</th>
                                <th style="padding: 14px 16px;">Price</th>
                                <th style="padding: 14px 16px;">Date & Time</th>
                                <th style="padding: 14px 16px;">Status</th>
                                <th style="padding: 14px 16px; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                                <tr>
                                    <td colspan="8" style="padding: 30px; text-align: center; color: #64748b;">No appointments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($appointments as $apt): ?>
                                    <?php
                                    $status_bg = '#e2e8f0';
                                    $status_color = '#475569';
                                    if (($apt['status'] ?? '') === 'Pending') { 
                                        $status_bg = '#fff3e0'; 
                                        $status_color = '#f57c00'; 
                                    } elseif (($apt['status'] ?? '') === 'Confirmed') { 
                                        $status_bg = '#bae6fd'; 
                                        $status_color = '#0369a1'; 
                                    } elseif (($apt['status'] ?? '') === 'Completed') { 
                                        $status_bg = '#dcfce7'; 
                                        $status_color = '#166534'; 
                                    } elseif (($apt['status'] ?? '') === 'Cancelled') { 
                                        $status_bg = '#fee2e2'; 
                                        $status_color = '#991b1b'; 
                                    }
                                    ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9; font-size: 0.9rem;">
                                        <td style="padding: 14px 16px; color: #64748b;">#<?= $apt['id'] ?></td>
                                        <td style="padding: 14px 16px;">
                                            <strong style="color: #1e293b;"><?= htmlspecialchars($apt['full_name']) ?></strong><br>
                                            <small style="color: #64748b;"><?= htmlspecialchars($apt['contact_number'] ?? 'No contact') ?></small>
                                        </td>
                                        <td style="padding: 14px 16px; color: #334155;"><?= htmlspecialchars($apt['vehicle_model'] ?? 'N/A') ?></td>
                                        <td style="padding: 14px 16px; color: #334155;"><?= htmlspecialchars($apt['service_name'] ?? 'N/A') ?></td>
                                        <td style="padding: 14px 16px; color: #334155; font-weight: 600;">₱<?= number_format($apt['service_price'] ?? 0, 2) ?></td>
                                        <td style="padding: 14px 16px; color: #334155;">
                                            <div style="display: flex; align-items: center; gap: 5px;"><i class="far fa-calendar-alt" style="color: #64748b;"></i> <?= htmlspecialchars($apt['booking_date']) ?></div>
                                            <div style="display: flex; align-items: center; gap: 5px; margin-top: 3px;"><i class="far fa-clock" style="color: #64748b;"></i> <?= htmlspecialchars($apt['booking_time']) ?></div>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span class="badge" style="padding: 5px 10px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; display: inline-block; background: <?= $status_bg ?>; color: <?= $status_color ?>;">
                                                <?= htmlspecialchars($apt['status'] ?? 'Pending') ?>
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px; text-align: right;">
                                            <div style="display: inline-flex; gap: 6px;">
                                                <button class="btn-sm" style="background: #64748b; color: #fff; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;" onclick="openViewModal(<?= htmlspecialchars(json_encode($apt)) ?>)" title="View Details"><i class="fas fa-eye"></i></button>
                                                <button class="btn-sm" style="background: #2563eb; color: #fff; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;" onclick="openStatusModal(<?= htmlspecialchars(json_encode($apt)) ?>)" title="Update Status"><i class="fas fa-edit"></i></button>
                                                <a href="admin_appointment.php?action=delete&id=<?= $apt['id'] ?>" class="btn-sm" style="background: #dc2626; color: #fff; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;" onclick="return confirm('Are you sure you want to delete this appointment?');" title="Delete"><i class="fas fa-trash"></i></a>
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

    <!-- CREATE APPOINTMENT MODAL -->
    <div class="modal-overlay" id="createAppointmentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
        <div class="modal-content" style="background: #fff; padding: 25px; border-radius: 10px; width: 480px; max-width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.2rem;">Add New Appointment</h3>
                <button class="close-btn" onclick="closeModal('createAppointmentModal')" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <form action="admin_appointment.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #334155;">Customer Full Name:</label>
                    <input type="text" name="full_name" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;" required>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #334155;">Contact Number:</label>
                    <input type="text" name="contact_number" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #334155;">Vehicle Model:</label>
                    <input type="text" name="vehicle_model" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #334155;">Service:</label>
                    <select name="service_id" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background-color: #fff;" required>
                        <option value="">Select Service</option>
                        <?php foreach ($services_list as $srv): ?>
                            <option value="<?= $srv['id'] ?>"><?= htmlspecialchars($srv['name']) ?> (₱<?= number_format($srv['price'], 2) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #334155;">Booking Date:</label>
                    <input type="date" name="booking_date" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;" required>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #334155;">Booking Time:</label>
                    <input type="time" name="booking_time" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;" required>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #334155;">Status:</label>
                    <select name="status" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background-color: #fff;" required>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #334155;">Notes:</label>
                    <textarea name="notes" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; resize: vertical; font-size: 0.9rem;" rows="3"></textarea>
                </div>

                <div class="modal-footer" style="display: flex; gap: 10px; justify-content: flex-end; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('createAppointmentModal')" style="padding: 8px 16px; background: #64748b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="submit" name="create_appointment" class="btn-primary" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Create Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW APPOINTMENT MODAL -->
    <div class="modal-overlay" id="viewAppointmentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
        <div class="modal-content" style="background: #fff; padding: 25px; border-radius: 10px; width: 450px; max-width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.2rem;">Appointment Details</h3>
                <button class="close-btn" onclick="closeModal('viewAppointmentModal')" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <div style="margin-bottom: 10px; font-size: 0.9rem;"><strong style="color: #64748b;">Customer Name:</strong> <p id="v_name" style="margin: 2px 0; color: #1e293b; font-weight: 500;"></p></div>
            <div style="margin-bottom: 10px; font-size: 0.9rem;"><strong style="color: #64748b;">Contact Number:</strong> <p id="v_contact" style="margin: 2px 0; color: #1e293b; font-weight: 500;"></p></div>
            <div style="margin-bottom: 10px; font-size: 0.9rem;"><strong style="color: #64748b;">Vehicle Model:</strong> <p id="v_vehicle" style="margin: 2px 0; color: #1e293b; font-weight: 500;"></p></div>
            <div style="margin-bottom: 10px; font-size: 0.9rem;"><strong style="color: #64748b;">Service:</strong> <p id="v_service" style="margin: 2px 0; color: #1e293b; font-weight: 500;"></p></div>
            <div style="margin-bottom: 10px; font-size: 0.9rem;"><strong style="color: #64748b;">Price:</strong> <p id="v_price" style="margin: 2px 0; color: #1e293b; font-weight: 500;"></p></div>
            <div style="margin-bottom: 10px; font-size: 0.9rem;"><strong style="color: #64748b;">Date & Time:</strong> <p id="v_datetime" style="margin: 2px 0; color: #1e293b; font-weight: 500;"></p></div>
            <div style="margin-bottom: 10px; font-size: 0.9rem;"><strong style="color: #64748b;">Status:</strong> <p id="v_status" style="margin: 2px 0; color: #1e293b; font-weight: 500;"></p></div>
            <div style="margin-bottom: 15px; font-size: 0.9rem;"><strong style="color: #64748b;">Notes:</strong> <p id="v_notes" style="margin: 2px 0; color: #1e293b; font-weight: 500;"></p></div>
            <div class="modal-footer" style="text-align: right; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                <button type="button" class="btn-secondary" onclick="closeModal('viewAppointmentModal')" style="padding: 8px 16px; background: #64748b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Close</button>
            </div>
        </div>
    </div>

    <!-- UPDATE STATUS MODAL -->
    <div class="modal-overlay" id="statusModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
        <div class="modal-content" style="background: #fff; padding: 25px; border-radius: 10px; width: 400px; max-width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.2rem;">Update Appointment Status</h3>
                <button class="close-btn" onclick="closeModal('statusModal')" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <form action="admin_appointment.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="appointment_id" id="s_apt_id">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #334155;">Status:</label>
                    <select name="status" id="s_status" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background-color: #fff;" required>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="modal-footer" style="display: flex; gap: 10px; justify-content: flex-end; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('statusModal')" style="padding: 8px 16px; background: #64748b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="submit" name="update_status" class="btn-primary" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Save Changes</button>
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

        function openViewModal(apt) {
            document.getElementById('v_name').innerText = apt.full_name;
            document.getElementById('v_contact').innerText = apt.contact_number || 'N/A';
            document.getElementById('v_vehicle').innerText = apt.vehicle_model || 'N/A';
            document.getElementById('v_service').innerText = apt.service_name || 'N/A';
            document.getElementById('v_price').innerText = '₱' + parseFloat(apt.service_price || 0).toFixed(2);
            document.getElementById('v_datetime').innerText = apt.booking_date + ' at ' + apt.booking_time;
            document.getElementById('v_status').innerText = apt.status;
            document.getElementById('v_notes').innerText = apt.notes || 'None';
            openModal('viewAppointmentModal');
        }

        function openStatusModal(apt) {
            document.getElementById('s_apt_id').value = apt.id;
            document.getElementById('s_status').value = apt.status;
            openModal('statusModal');
        }

        // Live Auto-Search & Auto-Filter Logic
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const statusSelect = document.getElementById('statusSelect');
        const filterForm = document.getElementById('filterForm');

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 400); // Wait 400ms after typing stops before submitting
        });

        statusSelect.addEventListener('change', function() {
            filterForm.submit();
        });

        // Preserve input focus and cursor position after auto-submit reloads the page
        window.addEventListener('DOMContentLoaded', () => {
            if (searchInput && searchInput.value) {
                searchInput.focus();
                searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
            }
        });
    </script>
</body>
</html>