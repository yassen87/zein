<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

// File sharing handler for external links (mega.nz, etc.)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the file URL from the request
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['fileUrl'])) {
    http_response_code(400);
    echo json_encode(['error' => 'File URL is required']);
    exit;
}

$fileUrl = $data['fileUrl'];

// Validate URL
if (!filter_var($fileUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid URL format']);
    exit;
}

// Check if it's a supported file sharing service
$supportedDomains = [
    'mega.nz',
    'drive.google.com',
    'dropbox.com',
    'mediafire.com',
    'we.tl',
    '1drv.ms'
];

$domain = parse_url($fileUrl, PHP_URL_HOST);
$isSupported = false;

foreach ($supportedDomains as $supportedDomain) {
    if (strpos($domain, $supportedDomain) !== false) {
        $isSupported = true;
        break;
    }
}

if (!$isSupported) {
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported file sharing service. Supported: ' . implode(', ', $supportedDomains)]);
    exit;
}

// Generate a short ID for the file
$shortId = substr(md5($fileUrl . time()), 0, 8);

// Return success response
echo json_encode([
    'success' => true,
    'fileUrl' => $fileUrl,
    'shortId' => $shortId,
    'domain' => $domain,
    'message' => 'File sharing link added successfully'
]);
?>
