<?php
// claim_promo.php - Handles promo bundle claims

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to avail a promo.'
    ]);
    exit;
}

require_once 'includes/config.php';

$userId = intval($_SESSION['user_id']);

try {
    // Make sure the logged-in user actually exists
    $userStmt = $pdo->prepare("SELECT id, first_name, last_name, phone FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Your user account could not be found. Please log in again.'
        ]);
        exit;
    }

    // Get submitted values
    $bundleSlug = trim($_POST['bundle_slug'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $selectedOption = trim($_POST['selected_option'] ?? '');
    $appointmentDate = trim($_POST['appointment_date'] ?? '');
    $appointmentTime = trim($_POST['appointment_time'] ?? '');

    // Validate required fields
    if (
        $bundleSlug === '' ||
        $fullname === '' ||
        $phone === '' ||
        $selectedOption === '' ||
        $appointmentDate === '' ||
        $appointmentTime === ''
    ) {
        echo json_encode([
            'success' => false,
            'message' => 'Please complete all required fields.'
        ]);
        exit;
    }

    // Validate appointment date
    $dateObject = DateTime::createFromFormat('Y-m-d', $appointmentDate);

    if (!$dateObject || $dateObject->format('Y-m-d') !== $appointmentDate) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid appointment date.'
        ]);
        exit;
    }

    if ($appointmentDate < date('Y-m-d')) {
        echo json_encode([
            'success' => false,
            'message' => 'Appointment date cannot be in the past.'
        ]);
        exit;
    }

    // Insert using users_id because this is the actual FK column
    $stmt = $pdo->prepare("
        INSERT INTO promo_claims
        (
            users_id,
            bundle_slug,
            fullname,
            phone,
            selected_option,
            appointment_date,
            appointment_time,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
    ");

    $stmt->execute([
        $userId,
        $bundleSlug,
        $fullname,
        $phone,
        $selectedOption,
        $appointmentDate,
        $appointmentTime
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Promo bundle successfully claimed!'
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>