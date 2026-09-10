<?php
session_start();

require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$appointment_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$appointment_id) {
    header(
        'Location: ../admin_appointment.php?msg=Invalid+appointment+ID'
    );
    exit();
}

try {

    $stmt = $pdo->prepare("
        DELETE FROM service_bookings
        WHERE id = ?
    ");

    $stmt->execute([$appointment_id]);

    header(
        'Location: ../admin_appointment.php?msg=' .
        urlencode('Appointment deleted successfully')
    );
    exit();

} catch (PDOException $e) {

    header(
        'Location: ../admin_appointment.php?msg=' .
        urlencode('Failed to delete appointment: ' . $e->getMessage())
    );
    exit();
}
?>