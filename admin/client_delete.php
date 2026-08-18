<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
admin_verify_csrf();

$pdo = medal_pdo();
if (!$pdo) {
    exit('DB error');
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $pdo->beginTransaction();
        
        // Delete the client
        $st2 = $pdo->prepare('DELETE FROM clients WHERE id = ?');
        $st2->execute([$id]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        exit('خطأ: ' . $e->getMessage());
    }
}

header('Location: clients.php?deleted=1');
exit;
