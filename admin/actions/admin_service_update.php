<?php
// /admin/actions/admin_service_update.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        header('Location: ../admin_services.php?msg=Invalid+Security+Token');
        exit();
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $active = ($_POST['status'] ?? 'Active') === 'Inactive' ? 0 : 1;

    if ($id && !empty($name) && $price !== false && $price >= 0) {
        $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, price = ?, active = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $active, $id]);

        header('Location: ../admin_services.php?msg=Service+successfully+updated');
        exit();
    }

    header('Location: ../admin_services.php?msg=Invalid+service+parameters');
    exit();
} else {
    header('Location: ../admin_services.php');
    exit();
}
