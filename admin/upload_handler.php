<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

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
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['image'];

// Validate file size (1MB max)
$maxSize = 1 * 1024 * 1024; // 1MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 1MB / 1 ميجابايت']);
    exit;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$mimeType = mime_content_type($file['tmp_name']) ?: '';
if (!in_array($mimeType, $allowedTypes, true) || @getimagesize($file['tmp_name']) === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
    exit;
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file extension. Only JPG, PNG, GIF, and WebP are allowed']);
    exit;
}

// Create upload directory if it doesn't exist
$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$filename = uniqid('img_', true) . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save uploaded file']);
    exit;
}

// Auto-convert to WebP
require_once __DIR__ . '/../includes/image_helper.php';
$webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
$webpFilepath = $uploadDir . $webpFilename;
if (convert_to_webp($filepath, $webpFilepath, 80)) {
    $filename = $webpFilename;
}

// Generate public URL using the app url() helper (handles subdirectory correctly)
$publicUrl = url('assets/uploads/' . $filename);

// Return success response
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'url' => $publicUrl,
    'size' => $file['size'],
    'type' => $mimeType
]);
?>
