<?php
session_start();

require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin_appointment.php');
    exit();
}

$token = $_POST['csrf_token'] ?? '';

if (!verifyCSRFToken($token)) {
    header('Location: ../admin_appointment.php?msg=Invalid+Security+Token');
    exit();
}

$full_name = trim($_POST['full_name'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$vehicle_model = trim($_POST['vehicle_model'] ?? '');
$user_id = filter_input(INPUT_POST, 'users_id', FILTER_VALIDATE_INT);
$services_id = filter_input(INPUT_POST, 'services_id', FILTER_VALIDATE_INT);
$booking_date = trim($_POST['booking_date'] ?? '');
$booking_time = trim($_POST['booking_time'] ?? '');
$status = trim($_POST['status'] ?? 'Pending');
$notes = trim($_POST['notes'] ?? '');

$allowed_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

if (
    empty($full_name) ||
    !$user_id ||
    !$services_id ||
    !preg_match('/^09[0-9]{9}$/', $contact_number) ||
    empty($booking_date) ||
    empty($booking_time)
) {
    header('Location: ../admin_appointment.php?msg=Please+fill+in+all+required+fields');
    exit();
}

if (!in_array($status, $allowed_statuses)) {
    $status = 'Pending';
}

try {

    $stmt = $pdo->prepare("
        INSERT INTO service_bookings
        (
            user_id,
            full_name,
            contact_number,
            vehicle_model,
            services_id,
            booking_date,
            booking_time,
            status,
            notes
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user_id,
        $full_name,
        $contact_number,
        $vehicle_model,
        $services_id,
        $booking_date,
        $booking_time,
        $status,
        $notes
    ]);

    header('Location: ../admin_appointment.php?msg=Appointment+successfully+created');
    exit();

} catch (PDOException $e) {

    header(
        'Location: ../admin_appointment.php?msg=' .
        urlencode('Failed to create appointment: ' . $e->getMessage())
    );
    exit();
}
?>