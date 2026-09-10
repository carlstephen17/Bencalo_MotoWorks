    <?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the current logged-in user is an admin.
 * Assumes your users table has an 'is_admin' or 'role' column. Adjust if needed.
 */
function isAdmin($userId) {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user && isset($user['role']) && $user['role'] === 'admin') {
        return true;
    }

    // Alternative fallback if your database uses an 'is_admin' integer column (1 for admin, 0 for regular):
    // $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    // $stmt->execute([$userId]);
    // $user = $stmt->fetch();
    // return $user && ((int)$user['is_admin'] === 1);

    return false;
}

/**
 * Generate a CSRF token for forms.
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token.
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>