<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bencalo_motoworks');

define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_FIRSTNAME', 'System');
define('DEFAULT_ADMIN_LASTNAME', 'Administrator');
define('DEFAULT_ADMIN_EMAIL', 'admin@bencalomotoworks.com');
define('DEFAULT_ADMIN_PHONE', '09123456789');
define('DEFAULT_ADMIN_PASSWORD', 'AdminSecure123!');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>