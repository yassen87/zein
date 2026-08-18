<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
admin_verify_csrf();

$id  = (int) ($_POST['order_id'] ?? 0);
$pdo = medal_pdo();

if ($pdo && $id > 0) {
    $st = $pdo->prepare('SELECT id, order_number, customer_name, customer_email, subtotal FROM orders WHERE id = ?');
    $st->execute([$id]);
    $order = $st->fetch();

    if ($order && !empty($order['customer_email'])) {
        require_once dirname(__DIR__) . '/order_confirm_mail.php';
        $sent = send_order_confirmation($order);
        if ($sent) {
            $pdo->prepare('UPDATE orders SET email_conf_sent = 1 WHERE id = ?')->execute([$id]);
            header('Location: ' . admin_url('orders.php?msg=email_sent'));
        } else {
            header('Location: ' . admin_url('orders.php?msg=email_failed'));
        }
        exit;
    }
}

header('Location: ' . admin_url('orders.php'));
exit;
