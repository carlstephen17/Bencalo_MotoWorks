<?php
// /admin/actions/admin_service_delete.php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: ../admin_services.php?msg=Service+successfully+deleted');
    exit();
}

header('Location: ../admin_services.php?msg=Invalid+service+ID');
exit();
?>