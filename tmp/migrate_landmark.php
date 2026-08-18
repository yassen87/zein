<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';

$pdo = medal_pdo();
if (!$pdo) {
    die("No DB connection\n");
}

try {
    // 1. Add address_landmark
    $pdo->exec("ALTER TABLE orders ADD COLUMN address_landmark TEXT DEFAULT NULL AFTER shipping_address");
    echo "Added address_landmark column.\n";

    // 2. Drop country
    $pdo->exec("ALTER TABLE orders DROP COLUMN country");
    echo "Dropped country column.\n";

    echo "Migration successful!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
