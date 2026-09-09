<?php
// /admin/actions/admin_promo_create.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bundle_slug = trim($_POST['bundle_slug'] ?? '');
    $selected_option = trim($_POST['selected_option'] ?? '');
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    if (!empty($fullname) && !empty($phone) && !empty($bundle_slug) && !empty($appointment_date)) {
        $stmt = $pdo->prepare("INSERT INTO promo_claims (fullname, phone, bundle_slug, selected_option, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$fullname, $phone, $bundle_slug, $selected_option, $appointment_date, $appointment_time, $status])) {
            header('Location: ../admin_promos.php?msg=Promo+claim+added+successfully');
            exit();
        } else {
            header('Location: ../admin_promos.php?msg=Failed+to+add+promo+claim');
            exit();
        }
    } else {
        header('Location: ../admin_promos.php?msg=Please+fill+in+all+required+fields');
        exit();
    }
}
header('Location: ../admin_promos.php');
exit();