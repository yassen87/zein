<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

// Check for notifications permission
if (!admin_has_permission('notifications')) {
    echo json_encode(['error' => 'No permission']);
    exit;
}

$pdo = medal_pdo();
if (!$pdo) {
    echo json_encode(['error' => 'DB error']);
    exit;
}

$st = $pdo->prepare('SELECT * FROM admin_notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5');
$st->execute();
$notifications = $st->fetchAll();

// Mark as read if requested
if (isset($_POST['mark_read'])) {
    $id = (int)$_POST['mark_read'];
    $upd = $pdo->prepare('UPDATE admin_notifications SET is_read = 1 WHERE id = ?');
    $upd->execute([$id]);
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'count' => count($notifications)
]);
