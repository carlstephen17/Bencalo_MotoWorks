<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to book a service appointment.']);
    exit;
}

require_once 'config.php';

$serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
$fullName = trim($_POST['fullname'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$vehicle = trim($_POST['vehicle'] ?? '');
$bookingDate = trim($_POST['booking_date'] ?? '');
$bookingTime = trim($_POST['booking_time'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (!$serviceId || empty($fullName) || empty($contact) || empty($vehicle) || empty($bookingDate) || empty($bookingTime)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required booking fields.']);
    exit;
}

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify service exists
    $stmt = $pdo->prepare("SELECT name FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Selected service not found.']);
        exit;
    }

    // Insert appointment into database (adjust table name if yours differs, e.g., 'appointments' or 'service_bookings')
    $orderStmt = $pdo->prepare("INSERT INTO service_bookings (user_id, service_id, full_name, contact_number, vehicle_model, booking_date, booking_time, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $orderStmt->execute([
        $_SESSION['user_id'],
        $serviceId,
        $fullName,
        $contact,
        $vehicle,
        $bookingDate,
        $bookingTime,
        $notes
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Service appointment booked successfully! We look forward to seeing you at Bencalo MotoWorks.'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}