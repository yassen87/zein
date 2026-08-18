<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

// Check for notifications permission
if (!admin_has_permission('notifications')) {
    echo json_encode(['error' => 'No permission']);
    exit;
}

require_once __DIR__ . '/../includes/config.php';

$type = $_POST['type'] ?? 'test';

if ($type === 'test') {
    add_admin_notification(
        'test',
        'إشعار تجريبي من الإعدادات',
        'Test Notification from Settings',
        'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.',
        'This is a test notification to ensure the alert system is working correctly.',
        'settings.php'
    );
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Invalid type']);
}
