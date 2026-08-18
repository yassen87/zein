<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

header('Content-Type: application/json; charset=utf-8');

// Enable CORS for API requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['image']['error'] ?? 'NO_FILE';
    $errMap = [
        UPLOAD_ERR_INI_SIZE   => 'حجم الصورة أكبر من الحد المسموح به في السيرفر (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'حجم الصورة أكبر من الحد المسموح به في النموذج.',
        UPLOAD_ERR_PARTIAL    => 'تم رفع الصورة بشكل جزئي فقط.',
        UPLOAD_ERR_NO_FILE    => 'لم يتم اختيار أي صورة للرفع.',
        UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة غير موجود في السيرفر.',
        UPLOAD_ERR_CANT_WRITE => 'فشل في حفظ الصورة على القرص (تأكد من صلاحيات مجلد uploads).',
    ];
    $errMsg = $errMap[$errCode] ?? 'فشل في رفع الصورة (رمز الخطأ: ' . $errCode . ')';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $errMsg]);
    exit;
}

$file = $_FILES['image'];

// Validate file size (15MB max)
$maxSize = 15 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'حجم الصورة كبير جداً. الحد الأقصى هو 15 ميجابايت.']);
    exit;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
$mimeType = @mime_content_type($file['tmp_name']) ?: '';
if (!empty($mimeType) && !in_array($mimeType, $allowedTypes, true) && @getimagesize($file['tmp_name']) === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'نوع الملف غير مدعوم. الصيغ المدعومة هي: JPG, PNG, GIF, WebP.']);
    exit;
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
    $extension = 'jpg';
}

// Create upload directory if it doesn't exist
$uploadDir = dirname(__DIR__) . '/assets/uploads/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
    @chmod($uploadDir, 0777);
}

// Generate unique filename
$filename = uniqid('img_', true) . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!@move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'تعذر حفظ الصورة في مجلد assets/uploads. يرجى التأكد من صلاحيات الكتابة للمجلد.']);
    exit;
}

// Auto-convert to WebP if GD is available and not already webp/svg
if ($extension !== 'webp' && $extension !== 'svg') {
    require_once dirname(__DIR__) . '/includes/image_helper.php';
    $webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
    $webpFilepath = $uploadDir . $webpFilename;
    if (function_exists('convert_to_webp') && @convert_to_webp($filepath, $webpFilepath, 82)) {
        $filename = $webpFilename;
    }
}

// Generate public URL
$publicUrl = storefront_url('assets/uploads/' . $filename);

// Return success response
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'url' => $publicUrl,
    'size' => $file['size'],
    'type' => $mimeType
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
