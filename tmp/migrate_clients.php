<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$pdo = medal_pdo();
if ($pdo === null) {
    die("Connection failed\n");
}

try {
    // 1. Create clients table
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(64) DEFAULT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table 'clients' created or already exists.\n";

    // 2. Create students table
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id INT UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        level VARCHAR(128) DEFAULT NULL,
        status VARCHAR(64) DEFAULT 'active',
        notes TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_student_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        KEY idx_student_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table 'students' created or already exists.\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
