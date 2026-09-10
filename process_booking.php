<?php
// Suppress direct error output to keep JSON clean
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering immediately to catch any stray whitespace, warnings, or notices
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        throw new Exception('Invalid CSRF token. Please refresh the page and try again.');
    }

    // Support both regular user sessions and admin sessions
    $userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
    if (!$userId) {
        throw new Exception('Please log in to book a service appointment.');
    }

    require_once 'includes/config.php';
    if (!isset($pdo)) {
        throw new Exception('Database connection unavailable in config.php.');
    }

    // Automatically create table if it doesn't exist to prevent database errors
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        services_id INT NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        contact_number VARCHAR(50) NOT NULL,
        vehicle_model VARCHAR(255) NOT NULL,
        booking_date DATE NOT NULL,
        booking_time VARCHAR(50) NOT NULL,
        notes TEXT,
        booking_reference VARCHAR(32) NULL,
        status VARCHAR(50) DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $referenceColumn = $pdo->query(
        "SHOW COLUMNS FROM service_bookings LIKE 'booking_reference'"
    );

    if (!$referenceColumn->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec(
            "ALTER TABLE service_bookings ADD booking_reference VARCHAR(32) NULL AFTER notes"
        );
    }

    $serviceId = filter_input(
        INPUT_POST,
        'services_id',
        FILTER_VALIDATE_INT
    );

    if (!$serviceId) {
        $serviceId = filter_input(
            INPUT_POST,
            'service_id',
            FILTER_VALIDATE_INT
        );
    }
    $fullName = trim($_POST['fullname'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $vehicle = trim($_POST['vehicle'] ?? '');
    $bookingDate = trim($_POST['booking_date'] ?? '');
    $bookingTime = trim($_POST['booking_time'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$serviceId || empty($fullName) || empty($contact) || empty($vehicle) || empty($bookingDate) || empty($bookingTime)) {
        throw new Exception('Please fill in all required booking fields.');
    }

    $bookingReference =
        'MWB-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

    $stmtService = $pdo->prepare("
    SELECT id, name
    FROM services
    WHERE id = ?
      AND active = 1
");

    $stmtService->execute([$serviceId]);

    $service = $stmtService->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        throw new Exception('Selected service was not found.');
    }

    $stmt = $pdo->prepare("INSERT INTO service_bookings (user_id, services_id, full_name, contact_number, vehicle_model, booking_date, booking_time, notes, booking_reference, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->execute([
        $userId,
        $serviceId,
        $fullName,
        $contact,
        $vehicle,
        $bookingDate,
        $bookingTime,
        $notes,
        $bookingReference
    ]);

    // Clear any unintended output buffer content
    ob_clean();

    echo json_encode([
        'success' => true,
        'message' => 'Service appointment booked successfully!',
        'booking_reference' => $bookingReference,
        'customer_name' => $fullName,
        'service_name' => $service['name'],
        'vehicle' => $vehicle,
        'booking_date' => $bookingDate,
        'booking_time' => $bookingTime,
        'phone' => $contact,
        'notes' => $notes
    ]);
    exit;
} catch (Throwable $e) {
    // Clear buffer and return JSON error message safely
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}
