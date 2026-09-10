<?php
// /admin/actions/admin_service_create.php
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

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $active = ($_POST['status'] ?? 'Active') === 'Inactive' ? 0 : 1;

    if (!empty($name) && $price !== false && $price >= 0) {
        $stmt = $pdo->prepare("INSERT INTO services (name, description, price, active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $active]);

        header('Location: ../admin_services.php?msg=Service+successfully+created');
        exit();
    }

    header('Location: ../admin_services.php?msg=Invalid+service+parameters');
    exit();
} else {
    header('Location: ../admin_services.php');
    exit();
}
