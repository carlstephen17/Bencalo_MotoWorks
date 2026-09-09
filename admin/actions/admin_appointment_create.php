<?php
// /admin/actions/admin_appointment_create.php
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
        header('Location: ../admin_promo_history.php?msg=Invalid+Security+Token');
        exit();
    }

    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bundle_slug = trim($_POST['bundle_slug'] ?? '');
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');

    if (!empty($fullname) && !empty($appointment_date) && !empty($appointment_time)) {
        $stmt = $pdo->prepare("INSERT INTO promo_claims (fullname, phone, bundle_slug, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$fullname, $phone, $bundle_slug, $appointment_date, $appointment_time, $status]);

        header('Location: ../admin_promo_history.php?msg=Appointment+successfully+created');
        exit();
    }

    header('Location: ../admin_promo_history.php?msg=Invalid+appointment+parameters');
    exit();
} else {
    header('Location: ../admin_promo_history.php');
    exit();
}