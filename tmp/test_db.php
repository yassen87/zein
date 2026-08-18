<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = new PDO(MEDAL_DB_DSN, MEDAL_DB_USER, MEDAL_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Connected successfully!\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}
