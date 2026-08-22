<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/receipt_ocr_matcher.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$clientOcrText = trim((string)($_POST['ocr_text'] ?? ''));

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order ID is required']);
    exit;
}

$savedFilename = '';

// Handle Image Upload
if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['receipt_image']['tmp_name'];
    $fileName = $_FILES['receipt_image']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'نوع الملف غير مدعوم، يرجى رفع صورة بصيغة JPG أو PNG أو WEBP']);
        exit;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/receipts';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $savedFilename = 'ocr_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $savedFilename;

    if (!move_uploaded_file($fileTmp, $targetPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'فشل حفظ صورة الإيصال']);
        exit;
    }
}

// Extract metadata from OCR text
$ocrData = ReceiptOcrMatcher::parseOcrText($clientOcrText);

// Reconcile with Bank Transactions in DB
$reconcileResult = ReceiptOcrMatcher::reconcileOrder($orderId, $ocrData, $savedFilename);

echo json_encode([
    'success' => true,
    'order_id' => $orderId,
    'receipt_file' => $savedFilename,
    'ocr_extracted' => $ocrData,
    'reconciliation' => $reconcileResult
], JSON_UNESCAPED_UNICODE);
