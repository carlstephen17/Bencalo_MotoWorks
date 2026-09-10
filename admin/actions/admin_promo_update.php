<?php
// /admin/actions/admin_promo_update.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Active');

    // Validate status against allowed options
    $allowed_statuses = ['Active', 'Pending', 'Confirmed', 'Completed', 'Cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'Active';
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE promo_claims SET status = ? WHERE id = ? AND status <> 'Completed'");
        if ($stmt->execute([$status, $id])) {
            header('Location: ../admin_promos.php?msg=Promo+claim+updated+successfully');
            exit();
        } else {
            header('Location: ../admin_promos.php?msg=Failed+to+update+promo+claim');
            exit();
        }
    }
}
header('Location: ../admin_promos.php');
exit();