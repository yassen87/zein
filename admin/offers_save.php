<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: offers.php');
    exit;
}

try {
    admin_verify_csrf();
    
    $pdo = medal_pdo();
    if (!$pdo) throw new Exception("Database connection failed.");

    // Auto-create table if missing
    $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_offers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image_key VARCHAR(128) NOT NULL,
        link_url VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB");

    $offersData = $_POST['offers'] ?? [];

    // Clear existing offers to refresh
    $pdo->exec("DELETE FROM homepage_offers");

    $st = $pdo->prepare("INSERT INTO homepage_offers (image_key, link_url, sort_order) VALUES (?, ?, ?)");

    foreach ($offersData as $data) {
        $img = trim($data['image_key'] ?? '');
        if (empty($img)) continue; // Skip empty slots

        $link = trim($data['link_url'] ?? '');
        $sort = (int)($data['sort_order'] ?? 0);

        $st->execute([$img, $link, $sort]);
    }

    header('Location: offers.php?success=1');
} catch (Throwable $e) {
    die("Error saving offers: " . $e->getMessage());
}
