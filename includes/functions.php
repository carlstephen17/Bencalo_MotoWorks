<?php
/**
 * Fetch all available services mapped by their ID for quick lookup
 */
function getServicesMap(PDO $pdo): array {
    $services_map = [];
    try {
        $s_stmt = $pdo->query("SELECT * FROM services");
        $services_list = $s_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($services_list as $s) {
            $s_id = $s['service_id'] ?? $s['id'] ?? null;
            if ($s_id) {
                $services_map[$s_id] = [
                    'name'  => $s['service_name'] ?? $s['title'] ?? $s['name'] ?? ('Service #' . $s_id),
                    'price' => $s['price'] ?? $s['cost'] ?? $s['rate'] ?? 0
                ];
            }
        }
    } catch (Exception $e) {
        // Return empty map on error
    }
    return $services_map;
}

/**
 * Fetch all raw services list for populating form dropdowns
 */
function getAllServices(PDO $pdo): array {
    try {
        $s_stmt = $pdo->query("SELECT * FROM services");
        return $s_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Fetch all service appointments/bookings for a specific user, sorted oldest first
 */
function getUserAppointments(PDO $pdo, int|string $userId): array {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM service_bookings 
            WHERE user_id = ? 
            ORDER BY booking_date ASC, booking_time ASC, id ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

 
/**
 * Fetch all promo claims for a specific user, sorted oldest first (starting from ID #1)
 */
function getUserPromoClaims(PDO $pdo, int|string $userId): array
{
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM user_promos 
            WHERE user_id = ? 
            ORDER BY claimed_date DESC, id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Fetch all product orders for a specific user, sorted newest first
 */
function getUserOrders(PDO $pdo, int|string $userId): array
{
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, p.name AS product_name, p.image AS product_image 
            FROM orders o 
            LEFT JOIN products p ON o.product_id = p.id 
            WHERE o.user_id = ? 
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}