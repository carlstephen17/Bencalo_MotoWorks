<?php
// get_claim.php - Fetches claim details & registered user details
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

require_once 'config.php';

$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;
$claimId = intval($_GET['id'] ?? 0);

if ($claimId <= 0) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid claim ID provided.']);
    exit;
}

try {
    // Fetch claim data along with registered user info as fallback
    $sql = "SELECT pc.*, 
                   u.fullname AS reg_fullname, 
                   u.phone AS reg_phone
            FROM promo_claims pc
            LEFT JOIN users u ON pc.user_id = u.id
            WHERE pc.id = ?";
    
    if (!$isAdmin) {
        $sql .= " AND pc.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$claimId, $userId]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$claimId]);
    }
    
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($claim) {
        // Fallback to registration details if claim fields are empty
        $claim['fullname'] = !empty($claim['fullname']) ? $claim['fullname'] : ($claim['reg_fullname'] ?? $_SESSION['fullname'] ?? $_SESSION['user_name'] ?? '');
        $claim['phone'] = !empty($claim['phone']) ? $claim['phone'] : ($claim['reg_phone'] ?? $_SESSION['phone'] ?? $_SESSION['contact'] ?? '');

        ob_clean();
        echo json_encode(['success' => true, 'claim' => $claim]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Record not found or unauthorized.']);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}