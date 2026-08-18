<?php
declare(strict_types=1);

require_once __DIR__ . '/smtp_mailer.php';

/**
 * Sends an OTP email using the built-in mail() via XAMPP sendmail (Gmail SMTP).
 */
function send_otp_email(string $toEmail, string $otp, string $type = 'register'): bool
{
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Zain Perfumes';
    $isAr = (function_exists('current_lang') && current_lang() === 'ar');

    if ($type === 'register') {
        $subjectRaw = $isAr ? "رمز التحقق الخاص بك - $siteName" : "Your Verification Code - $siteName";
        $heading    = $isAr ? "رمز التحقق" : "Verification Code";
        $body       = $isAr
            ? "شكراً لتسجيلك! رمز التحقق الخاص بك هو:"
            : "Thank you for registering! Your verification code is:";
    } elseif ($type === 'reset') {
        $subjectRaw = $isAr ? "إعادة تعيين كلمة المرور - $siteName" : "Password Reset Code - $siteName";
        $heading    = $isAr ? "رمز إعادة التعيين" : "Password Reset Code";
        $body       = $isAr
            ? "لقد طلبت إعادة تعيين كلمة مرورك. استخدم الرمز التالي:"
            : "You requested a password reset. Use the following code:";
    } else {
        $subjectRaw = $isAr ? "رمز الدخول الخاص بك - $siteName" : "Your Login Code - $siteName";
        $heading    = $isAr ? "رمز الدخول" : "Login Code";
        $body       = $isAr
            ? "رمز الدخول الخاص بك هو:"
            : "Your login code is:";
    }

    // Properly encode subject for UTF-8 / Arabic support
    $subject = '=?UTF-8?B?' . base64_encode($subjectRaw) . '?=';

    $dir = $isAr ? 'rtl' : 'ltr';
    $noteText = $isAr ? "هذا الرمز صالح لمدة 15 دقيقة فقط." : "This code is valid for 15 minutes only.";

    // Get dynamic absolute URL of the logo
    $logoSrc = function_exists('url') ? url('assets/img/logo.png') : 'https://zeinperfumes.com/assets/img/logo.png';

    $htmlMessage = <<<HTML
<!DOCTYPE html>
<html dir="{$dir}" lang="{$dir}">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:20px; }
  .card { max-width:480px; margin:0 auto; background:#fff; border-radius:12px;
          padding:40px; box-shadow:0 4px 20px rgba(0,0,0,.1); text-align:center; }
  .brand { font-size:26px; font-weight:800; color:#d4af37; letter-spacing:.1em; margin-bottom:8px; }
  h2 { color:#333; margin:0 0 16px; }
  p { color:#555; margin:0 0 24px; }
  .otp { font-size:42px; font-weight:900; letter-spacing:.4em; color:#d4af37;
         background:#fdfaf0; border:2px dashed #d4af37; border-radius:8px;
         padding:16px 24px; display:inline-block; margin:16px 0; }
  .note { font-size:13px; color:#999; margin-top:16px; }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">
        <img src="{$logoSrc}" alt="Zein Perfumes" style="height: 60px; width: auto; margin-bottom: 20px;">
    </div>
    <h2>{$heading}</h2>
    <p>{$body}</p>
    <div class="otp">{$otp}</div>
    <p class="note">{$noteText}</p>
  </div>
</body>
</html>
HTML;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$siteName} <zeinperfume83@gmail.com>\r\n";
    $headers .= "Reply-To: zeinperfume83@gmail.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    return smtp_send($toEmail, $subjectRaw, $htmlMessage);
}

/**
 * Sends an order confirmation email.
 */
function send_order_confirmation_email(string $toEmail, string $orderNumber, float $total, string $customerName): bool
{
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Zain Perfumes';
    $isAr = (function_exists('current_lang') && current_lang() === 'ar');
    
    $subjectRaw = $isAr ? "في انتظار تأكيد طلبك #$orderNumber - $siteName" : "Pending Confirmation for Order #$orderNumber - $siteName";
    $heading    = $isAr ? "طلبك في انتظار التأكيد" : "Your Order is Pending Confirmation";
    $body       = $isAr 
        ? "مرحباً {$customerName}، لقد استلمنا طلبك بنجاح وهو الآن **في انتظار التأكيد**. رقم الطلب هو:" 
        : "Hello {$customerName}, we have successfully received your order and it is now **Pending Confirmation**. Your order number is:";
    
    $totalLabel = $isAr ? "إجمالي الطلب:" : "Order Total:";
    $formattedTotal = number_format($total, 2) . ' ' . ($isAr ? 'ج.م' : 'LE');

    $subject = '=?UTF-8?B?' . base64_encode($subjectRaw) . '?=';
    $dir = $isAr ? 'rtl' : 'ltr';

    $logoSrc = function_exists('url') ? url('assets/img/logo.png') : 'https://zeinperfumes.com/assets/img/logo.png';

    $htmlMessage = <<<HTML
<!DOCTYPE html>
<html dir="{$dir}" lang="{$dir}">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:20px; }
  .card { max-width:550px; margin:0 auto; background:#fff; border-radius:12px;
          padding:40px; box-shadow:0 4px 20px rgba(0,0,0,.1); text-align:center; }
  .brand { margin-bottom: 20px; }
  h2 { color:#333; margin:0 0 16px; }
  p { color:#555; margin:0 0 24px; line-height: 1.6; }
  .order-number { font-size:24px; font-weight:700; color:#d4af37;
                  background:#fdfaf0; border:1px solid #d4af37; border-radius:8px;
                  padding:12px 20px; display:inline-block; margin:10px 0; }
  .total-box { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 18px; font-weight: 600; }
  .footer-note { font-size:13px; color:#999; margin-top:30px; }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">
        <img src="{$logoSrc}" alt="{$siteName}" style="height: 60px; width: auto;">
    </div>
    <h2>{$heading}</h2>
    <p>{$body}</p>
    <div class="order-number">#{$orderNumber}</div>
    <div class="total-box">
        {$totalLabel} <span style="color: #d4af37;">{$formattedTotal}</span>
    </div>
    <p class="footer-note">
        {$siteName} - Luxury Perfumes
    </p>
  </div>
</body>
</html>
HTML;

    return smtp_send($toEmail, $subjectRaw, $htmlMessage);
}

/**
 * Sends a notification email to the administrator about a new order.
 */
function send_admin_new_order_notification(string $orderNumber, float $total, string $customerName, array $orderLines, string $phone, string $address, string $city, ?string $notes = null): bool
{
    $adminEmail = 'zeinperfume83@gmail.com';
    $siteName   = defined('SITE_NAME') ? SITE_NAME : 'Zain Perfumes';
    $subjectRaw = "طلب جديد #$orderNumber - New Order";
    
    $formattedTotal = number_format($total, 2) . ' EGP';
    
    // Build items HTML list
    $itemsHtml = '';
    foreach ($orderLines as $ln) {
        $itemsHtml .= "<li><strong>" . esc($ln['name']) . "</strong> (x" . (int)$ln['qty'] . ") - " . number_format((float)$ln['unit_price'], 2) . " EGP</li>";
    }

    $logoSrc = function_exists('url') ? url('assets/img/logo.png') : 'https://zeinperfumes.com/assets/img/logo.png';
    $notesText = $notes ? esc($notes) : 'لا يوجد';

    $htmlMessage = <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:20px; text-align: right; }
  .card { max-width:600px; margin:0 auto; background:#fff; border-radius:12px;
          padding:30px; box-shadow:0 4px 20px rgba(0,0,0,.1); }
  .brand { text-align: center; margin-bottom: 20px; }
  h2 { color:#333; margin:0 0 16px; border-bottom: 2px solid #d4af37; padding-bottom: 10px; }
  p { color:#555; margin:0 0 12px; line-height: 1.6; }
  .order-number { font-size:20px; font-weight:700; color:#d4af37; display:inline-block; }
  .details-box { background:#f9f9f9; border-radius:8px; padding:15px; margin: 20px 0; border-right: 4px solid #d4af37; }
  .items-list { padding-right: 20px; margin: 15px 0; }
  .total-box { font-size: 18px; font-weight: 700; color: #d4af37; margin-top: 15px; }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">
        <img src="{$logoSrc}" alt="{$siteName}" style="height: 55px; width: auto;">
    </div>
    <h2>طلب جديد رقم <span class="order-number">#{$orderNumber}</span></h2>
    
    <p>لقد تم تقديم طلب جديد على الموقع بالتفاصيل التالية:</p>
    
    <div class="details-box">
        <p>👤 <strong>العميل:</strong> {$customerName}</p>
        <p>📞 <strong>الهاتف:</strong> {$phone}</p>
        <p>📍 <strong>العنوان:</strong> {$address}، {$city}</p>
        <p>📝 <strong>ملاحظات:</strong> {$notesText}</p>
    </div>

    <h3>🛍️ المنتجات:</h3>
    <ul class="items-list">
        {$itemsHtml}
    </ul>

    <div class="total-box">
        💰 إجمالي الطلب: {$formattedTotal}
    </div>
  </div>
</body>
</html>
HTML;

    return smtp_send($adminEmail, $subjectRaw, $htmlMessage);
}

