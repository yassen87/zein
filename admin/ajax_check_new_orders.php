<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

header('Content-Type: application/json');

$pdo = medal_pdo();
if (!$pdo) {
    echo json_encode(['error' => 'No DB connection']);
    exit;
}

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

try {
    $st = $pdo->prepare('SELECT id, order_number, customer_name, total FROM orders WHERE id > ? ORDER BY id DESC LIMIT 1');
    $st->execute([$last_id]);
    $order = $st->fetch();

    if ($order) {
        echo json_encode([
            'new_order' => true,
            'id' => (int)$order['id'],
            'number' => $order['order_number'],
            'customer' => $order['customer_name'],
            'total' => number_format((float)$order['total'], 2)
        ]);
    } else {
        echo json_encode(['new_order' => false]);
    }
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
