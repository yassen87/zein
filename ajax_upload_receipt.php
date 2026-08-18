<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = medal_pdo();
if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'لم يتم إرفاق ملف صالح أو حدث خطأ أثناء الرفع']);
    exit;
}

$file = $_FILES['receipt'];
$maxSize = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'حجم الصورة كبير جداً. الحد الأقصى 10 ميجابايت']);
    exit;
}

$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];
$origName = basename($file['name']);
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExts, true)) {
    echo json_encode(['success' => false, 'error' => 'صيغة الملف غير مدعومة. الصيغ المسموحة: JPG, PNG, WEBP, PDF']);
    exit;
}

$uploadDir = __DIR__ . '/assets/uploads/receipts/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

$filename = 'receipt_' . $orderId . '_' . time() . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['success' => false, 'error' => 'فشل حفظ الملف على السيرفر']);
    exit;
}

try {
    // Update order
    $st = $pdo->prepare(
        "UPDATE orders SET 
            payment_receipt = ?, 
            payment_status = 'receipt_uploaded', 
            is_confirmed = 1,
            bot_step = 'receipt_received',
            confirmed_at = COALESCE(confirmed_at, NOW()) 
         WHERE id = ?"
    );
    $st->execute([$filename, $orderId]);

    // Send admin notification
    try {
        $notif = $pdo->prepare(
            "INSERT INTO admin_notifications (type, title_ar, title_en, message_ar, message_en, link, is_read, created_at)
             VALUES ('payment_receipt', 'إيصال دفع جديد', 'New Payment Receipt', ?, ?, ?, 0, NOW())"
        );
        $notif->execute([
            "تم رفع صورة إيصال تحويل للطلب رقم #{$orderId}",
            "Payment receipt uploaded for order #{$orderId}",
            "order_view.php?id={$orderId}"
        ]);
    } catch (\Throwable $e) {}

    echo json_encode([
        'success' => true,
        'message' => 'تم رفع صورة إيصال التحويل بنجاح! جاري مراجعة طلبك.',
        'filename' => $filename,
        'url' => url('assets/uploads/receipts/' . $filename)
    ]);
} catch (\Throwable $e) {
    error_log('Error saving receipt in database: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'حدث خطأ أثناء تحديث بيانات الطلب']);
}
