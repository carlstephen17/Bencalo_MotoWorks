<?php
// /admin/actions/admin_promo_history_update.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $bundleSlug = trim($_POST['bundle_slug'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');
    $allowedStatuses = ['Pending', 'Confirmed', 'Used', 'Completed', 'Cancelled'];

    if ($id && $bundleSlug !== '' && in_array($status, $allowedStatuses, true)) {
        $stmt = $pdo->prepare(
            "UPDATE promo_claims
             SET bundle_slug = ?, status = ?
             WHERE id = ? AND status <> 'Completed'"
        );
        $stmt->execute([$bundleSlug, $status, $id]);

        if ($stmt->rowCount() > 0) {
            header('Location: ../admin_promo_history.php?msg=Promo+claim+updated+successfully');
            exit();
        }

        header('Location: ../admin_promo_history.php?msg=Completed+promo+claims+cannot+be+edited');
        exit();
    }

    header('Location: ../admin_promo_history.php?msg=Invalid+promo+claim+parameters');
    exit();
}

header('Location: ../admin_promo_history.php');
exit();
