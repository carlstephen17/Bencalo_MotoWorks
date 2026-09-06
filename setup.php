<?php
// setup.php
require_once 'config.php';

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'customer') DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([DEFAULT_ADMIN_USERNAME]);

    if (!$stmt->fetch()) {
        $hashedPassword = password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT);

        $insert = $pdo->prepare("
            INSERT INTO users (username, first_name, last_name, email, phone, password, role) 
            VALUES (?, ?, ?, ?, ?, ?, 'admin')
        ");
        $insert->execute([
            DEFAULT_ADMIN_USERNAME,
            DEFAULT_ADMIN_FIRSTNAME,
            DEFAULT_ADMIN_LASTNAME,
            DEFAULT_ADMIN_EMAIL,
            DEFAULT_ADMIN_PHONE,
            $hashedPassword
        ]);
        echo "<h3>Success: Database initialized and default administrator seeded securely!</h3>";
    } else {
        echo "<h3>Notice: Admin account already exists in the database.</h3>";
    }

    echo "<br><a href='login.php'>Go to Login</a> | <a href='index.php'>Go to Home</a>";

} catch (PDOException $e) {
    echo "Setup error: " . $e->getMessage();
}
?>