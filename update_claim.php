<?php
// update_claim.php

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

// Validate CSRF Token if sent
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

require_once 'includes/config.php';

$userId = intval($_SESSION['user_id']);
$isAdmin = $_SESSION['is_admin'] ?? false;

$claimId = isset($_POST['claim_id']) ? intval($_POST['claim_id']) : 0;
$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
$appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'Pending';

if ($claimId <= 0 || empty($fullname) || empty($phone) || empty($appointment_date) || empty($appointment_time)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

try {
    if ($isAdmin) {
        // Admins can update any claim, including status
        $stmt = $pdo->prepare("
            UPDATE promo_claims 
            SET fullname = ?, phone = ?, appointment_date = ?, appointment_time = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([$fullname, $phone, $appointment_date, $appointment_time, $status, $claimId]);
    } else {
        // Regular users can only update their own claims and cannot change status to Completed/etc. arbitrarily unless restricted
        $stmt = $pdo->prepare("
            UPDATE promo_claims 
            SET fullname = ?, phone = ?, appointment_date = ?, appointment_time = ?
            WHERE id = ? AND users_id = ?
        ");
        $stmt->execute([$fullname, $phone, $appointment_date, $appointment_time, $claimId, $userId]);
    }

    if ($stmt->rowCount() > 0 || $pdo->query("SELECT id FROM promo_claims WHERE id = $claimId")->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Appointment updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Claim not found or you do not have permission to edit it.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>