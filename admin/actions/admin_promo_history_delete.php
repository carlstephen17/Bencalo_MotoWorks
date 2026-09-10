<?php
// /admin/actions/admin_promo_history_delete.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $pdo->prepare('DELETE FROM promo_claims WHERE id = ?');
    $stmt->execute([$id]);

    header('Location: ../admin_promo_history.php?msg=Promo+claim+deleted+successfully');
    exit();
}

header('Location: ../admin_promo_history.php?msg=Invalid+promo+claim');
exit();
