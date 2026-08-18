<?php
declare(strict_types=1);

if (!function_exists('medal_pdo')) {
    require_once __DIR__ . '/includes/config.php';
}
require_once __DIR__ . '/includes/smtp_mailer.php';

/**
 * Sends an order confirmation email to the customer.
 * Can be called from cron (auto) or admin (manual).
 */
if (!function_exists('send_order_confirmation')) {
function send_order_confirmation(array $order): bool
{
    $toEmail  = $order['customer_email'] ?? '';
    if ($toEmail === '') {
        return false;
    }

    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Zain Perfumes';
    $orderNum = $order['order_number'] ?? '#';
    $name     = $order['customer_name'] ?? '';
    $total    = number_format((float)($order['subtotal'] ?? 0), 2);
    $currency = 'EGP';

    $subjectRaw = "Thank you for your order #{$orderNum} - {$siteName}";

    // Convert logo to base64 for local testing support
    $logoBase64 = '';
    $logoPath = __DIR__ . '/assets/img/logo.png';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    }
    $logoSrc = !empty($logoBase64) ? $logoBase64 : 'https://zeinperfumes.com/assets/img/logo.png';

    $html = <<<HTML
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head><meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:20px; }
  .card { max-width:520px; margin:0 auto; background:#fff; border-radius:12px; padding:40px; box-shadow:0 4px 20px rgba(0,0,0,.1); }
  .brand { text-align:center; font-size:26px; font-weight:800; color:#d4af37; letter-spacing:.1em; margin-bottom:16px; }
  h2 { text-align:center; color:#333; margin-bottom:8px; }
  p { color:#555; line-height:1.7; }
  .order-box { background:#fdfaf0; border:2px solid #d4af37; border-radius:8px; padding:16px 20px; margin:20px 0; }
  .order-box strong { color:#8a6a00; }
  .footer { font-size:12px; color:#999; text-align:center; margin-top:24px; }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">
        <img src="{$logoSrc}" alt="Zein Perfumes" style="height: 60px; width: auto; margin-bottom: 20px;">
    </div>
    <h2>Order Confirmation ✅</h2>
    <p>Hi <strong>{$name}</strong>,</p>
    <p>We wanted to follow up on your order. Is everything going well? We'd love to know if you have any questions!</p>
    <div class="order-box">
      <strong>Order Number:</strong> #{$orderNum}<br>
      <strong>Total:</strong> {$total} {$currency}
    </div>
    <p>If you need any assistance, feel free to reply to this email or contact us anytime.</p>
    <p>Thank you for choosing <strong>{$siteName}</strong>! 🌹</p>
    <div class="footer">{$siteName} — Your Luxury Perfume Destination</div>
  </div>
</body>
</html>
HTML;

    return smtp_send($toEmail, $subjectRaw, $html);
}
} // end function_exists check

// ---- AUTO MODE (Cron): orders older than 6 hours without confirmation sent ----
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === 'cron')) {
    $pdo = medal_pdo();
    if ($pdo) {
        $st = $pdo->query(
            "SELECT id, order_number, customer_name, customer_email, subtotal
             FROM orders
             WHERE created_at <= NOW() - INTERVAL 6 HOUR
               AND (email_conf_sent IS NULL OR email_conf_sent = 0)
               AND status NOT IN ('cancelled', 'delivered')
             LIMIT 20"
        );
        $orders = $st->fetchAll();
        foreach ($orders as $order) {
            $sent = send_order_confirmation($order);
            if ($sent) {
                $pdo->prepare('UPDATE orders SET email_conf_sent = 1 WHERE id = ?')
                    ->execute([$order['id']]);
                echo "✅ Sent to: " . $order['customer_email'] . "\n";
            } else {
                echo "❌ Failed for: " . $order['customer_email'] . "\n";
            }
        }
        echo "Done. " . count($orders) . " order(s) processed.\n";
    }
    exit;
}
