<?php

session_start();

require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';


// =========================================================
// ADMIN ACCESS
// =========================================================

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}


// =========================================================
// ONLY ALLOW POST
// =========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin_appointment.php');
    exit();
}


// =========================================================
// CSRF TOKEN
// =========================================================

$token = $_POST['csrf_token'] ?? '';

if (function_exists('verifyCSRFToken')) {

    if (!verifyCSRFToken($token)) {
        header(
            'Location: ../admin_appointment.php?msg=' .
            urlencode('Invalid Security Token')
        );
        exit();
    }

}


// =========================================================
// GET FORM DATA
// =========================================================

$appointment_id = filter_input(
    INPUT_POST,
    'appointment_id',
    FILTER_VALIDATE_INT
);

$full_name = trim($_POST['full_name'] ?? '');

$user_id = filter_input(
    INPUT_POST,
    'users_id',
    FILTER_VALIDATE_INT
);

$contact_number = trim(
    $_POST['contact_number'] ?? ''
);

$vehicle_model = trim(
    $_POST['vehicle_model'] ?? ''
);


// IMPORTANT:
// Your appointment table uses services_id
$services_id = filter_input(
    INPUT_POST,
    'services_id',
    FILTER_VALIDATE_INT
);

$booking_date = trim(
    $_POST['booking_date'] ?? ''
);

$booking_time = trim(
    $_POST['booking_time'] ?? ''
);

$status = trim(
    $_POST['status'] ?? 'Pending'
);

$notes = trim(
    $_POST['notes'] ?? ''
);


// =========================================================
// ALLOWED STATUS
// =========================================================

$allowed_statuses = [
    'Pending',
    'Confirmed',
    'Completed',
    'Cancelled'
];

if (!in_array($status, $allowed_statuses, true)) {
    $status = 'Pending';
}


// =========================================================
// REQUIRED FIELDS
// =========================================================

if (
    !$appointment_id ||
    !$user_id ||
    empty($full_name) ||
    !preg_match('/^09[0-9]{9}$/', $contact_number) ||
    !$services_id ||
    empty($booking_date) ||
    empty($booking_time)
) {

    header(
        'Location: ../admin_appointment.php?msg=' .
        urlencode('Please fill in all required fields')
    );

    exit();
}


// =========================================================
// CHECK IF SERVICE EXISTS
// =========================================================

try {

    $serviceCheck = $pdo->prepare("
        SELECT id
        FROM services
        WHERE id = ?
    ");

    $serviceCheck->execute([
        $services_id
    ]);

    if (!$serviceCheck->fetch()) {

        header(
            'Location: ../admin_appointment.php?msg=' .
            urlencode('Selected service does not exist')
        );

        exit();
    }


    // =====================================================
    // UPDATE APPOINTMENT
    // =====================================================

    $stmt = $pdo->prepare("
        UPDATE service_bookings
        SET
            user_id = ?,
            full_name = ?,
            contact_number = ?,
            vehicle_model = ?,
            services_id = ?,
            booking_date = ?,
            booking_time = ?,
            status = ?,
            notes = ?
        WHERE id = ?
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
        $notes,
        $appointment_id
    ]);


    // =====================================================
    // SUCCESS
    // =====================================================

    header(
        'Location: ../admin_appointment.php?msg=' .
        urlencode('Appointment successfully updated')
    );

    exit();


} catch (PDOException $e) {


    // =====================================================
    // ERROR
    // =====================================================

    header(
        'Location: ../admin_appointment.php?msg=' .
        urlencode(
            'Failed to update appointment: ' .
            $e->getMessage()
        )
    );

    exit();

}

?>