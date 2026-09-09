<?php
// /admin/actions/admin_promo_delete.php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM promo_claims WHERE id = ?");
    if ($stmt->execute([$id])) {
        header('Location: ../admin_promos.php?msg=Promo+claim+deleted+successfully');
        exit();
    } else {
        header('Location: ../admin_promos.php?msg=Failed+to+delete+promo+claim');
        exit();
    }
}

header('Location: ../admin_promos.php');
exit();