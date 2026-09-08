<?php
// update_claim.php - Backend handler to execute updates on promo appointments
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

require_once 'config.php';

$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;

$claimId = intval($_POST['claim_id'] ?? 0);
$fullname = trim($_POST['fullname'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$appointmentDate = trim($_POST['appointment_date'] ?? '');
$appointmentTime = trim($_POST['appointment_time'] ?? '');

// Validation
if ($claimId <= 0 || empty($fullname) || empty($phone) || empty($appointmentDate) || empty($appointmentTime)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

// Validate date is not in the past
if (strtotime($appointmentDate) < strtotime(date('Y-m-d'))) {
    echo json_encode(['success' => false, 'message' => 'Appointment date cannot be set in the past.']);
    exit;
}

try {
    if ($isAdmin) {
        $stmt = $pdo->prepare("UPDATE promo_claims SET fullname = ?, phone = ?, appointment_date = ?, appointment_time = ? WHERE id = ?");
        $stmt->execute([$fullname, $phone, $appointmentDate, $appointmentTime, $claimId]);
    } else {
        $stmt = $pdo->prepare("UPDATE promo_claims SET fullname = ?, phone = ?, appointment_date = ?, appointment_time = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$fullname, $phone, $appointmentDate, $appointmentTime, $claimId, $userId]);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Appointment details updated successfully!'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}