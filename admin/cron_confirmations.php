<?php
declare(strict_types=1);

// This script can be run via CLI: php admin/cron_confirmations.php
// Or via Browser: http://localhost:8000/admin/cron_confirmations.php

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

$pdo = medal_pdo();
if (!$pdo) {
    die("Database connection failed.\n");
}

// Fetch orders older than 6 hours
$sixHoursAgo = date('Y-m-d H:i:s', strtotime('-6 hours'));
$twoDaysAgo = date('Y-m-d H:i:s', strtotime('-48 hours'));

$st = $pdo->prepare("SELECT id, order_number, customer_name, customer_email FROM orders WHERE wa_conf_sent = 0 AND created_at <= ? AND created_at >= ? AND status NOT IN ('cancelled', 'delivered') LIMIT 10");
$st->execute([$sixHoursAgo, $twoDaysAgo]);
$orders = $st->fetchAll();

if (empty($orders)) {
    echo "No pending email confirmations found.\n";
    exit;
}

foreach ($orders as $order) {
    $mailSent = false;
    $customerEmail = (string) $order['customer_email'];

    if (empty($customerEmail)) {
        echo "Skipping " . $order['order_number'] . " (No Email)\n";
        continue;
    }

    $msg = (current_lang() === 'ar' ) 
        ? "أهلاً بك يا " . $order['customer_name'] . "، نود التأكيد بخصوص طلبك رقم " . $order['order_number'] . " من متجر زين للعطور. هل تود الاستمرار في الطلب؟"
        : "Hello " . $order['customer_name'] . ", we'd like to confirm your order #" . $order['order_number'] . " from Meda Perfumes. Would you like to proceed?";

    $subject = (current_lang() === 'ar') ? "تأكيد طلبك من زين للعطور" : "Confirm your order from Meda Perfumes";
    $headers = "From: no-reply@medaperfumes.com\r\n";
    $headers .= "Reply-To: support@medaperfumes.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    echo "Sending Email to " . $customerEmail . " (" . $order['order_number'] . ")... ";
    
    $mailSent = mail($customerEmail, $subject, $msg, $headers);
    
    if ($mailSent) {
        $upd = $pdo->prepare("UPDATE orders SET wa_conf_sent = 1 WHERE id = ?");
        $upd->execute([$order['id']]);
        echo "SUCCESS\n";
    } else {
        echo "FAILED\n";
    }
}
