<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_admin('shipping');

header('Content-Type: application/json; charset=utf-8');

$pdo = medal_pdo();
if ($pdo === null) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Ensure schema columns exist
medal_ensure_column($pdo, 'shipping_cities', 'active', 'TINYINT(1) NOT NULL DEFAULT 1');
medal_ensure_column($pdo, 'shipping_cities', 'delivery_time', 'VARCHAR(100) NULL DEFAULT "1-3 أيام عمل"');

$action = $_POST['action'] ?? '';
admin_verify_csrf();

$allGovernorates = [
    ['name_en' => 'Cairo', 'name_ar' => 'القاهرة', 'sort' => 10, 'time' => '1-2 أيام عمل'],
    ['name_en' => 'Giza', 'name_ar' => 'الجيزة', 'sort' => 20, 'time' => '1-2 أيام عمل'],
    ['name_en' => 'Alexandria', 'name_ar' => 'الإسكندرية', 'sort' => 30, 'time' => '2-3 أيام عمل'],
    ['name_en' => 'Qalyubia', 'name_ar' => 'القليوبية', 'sort' => 40, 'time' => '2-3 أيام عمل'],
    ['name_en' => 'Sharqia', 'name_ar' => 'الشرقية', 'sort' => 50, 'time' => '2-3 أيام عمل'],
    ['name_en' => 'Dakahlia', 'name_ar' => 'الدقهلية', 'sort' => 60, 'time' => '2-3 أيام عمل'],
    ['name_en' => 'Gharbia', 'name_ar' => 'الغربية', 'sort' => 70, 'time' => '2-3 أيام عمل'],
    ['name_en' => 'Monufia', 'name_ar' => 'المنوفية', 'sort' => 80, 'time' => '2-3 أيام عمل'],
    ['name_en' => 'Beheira', 'name_ar' => 'البحيرة', 'sort' => 90, 'time' => '2-4 أيام عمل'],
    ['name_en' => 'Kafr El Sheikh', 'name_ar' => 'كفر الشيخ', 'sort' => 100, 'time' => '2-4 أيام عمل'],
    ['name_en' => 'Damietta', 'name_ar' => 'دمياط', 'sort' => 110, 'time' => '2-4 أيام عمل'],
    ['name_en' => 'Port Said', 'name_ar' => 'بورسعيد', 'sort' => 120, 'time' => '2-3 أيام عمل'],
    ['name_en' => 'Ismailia', 'name_ar' => 'الإسماعيلية', 'sort' => 130, 'time' => '2-3 أيام عمل'],
    ['name_en' => 'Suez', 'name_ar' => 'السويس', 'sort' => 140, 'time' => '2-3 أيام عمل'],
    ['name_en' => 'Faiyum', 'name_ar' => 'الفيوم', 'sort' => 150, 'time' => '2-4 أيام عمل'],
    ['name_en' => 'Beni Suef', 'name_ar' => 'بني سويف', 'sort' => 160, 'time' => '2-4 أيام عمل'],
    ['name_en' => 'Minya', 'name_ar' => 'المنيا', 'sort' => 170, 'time' => '3-5 أيام عمل'],
    ['name_en' => 'Asyut', 'name_ar' => 'أسيوط', 'sort' => 180, 'time' => '3-5 أيام عمل'],
    ['name_en' => 'Sohag', 'name_ar' => 'سوهاج', 'sort' => 190, 'time' => '3-5 أيام عمل'],
    ['name_en' => 'Qena', 'name_ar' => 'قنا', 'sort' => 200, 'time' => '3-5 أيام عمل'],
    ['name_en' => 'Luxor', 'name_ar' => 'الأقصر', 'sort' => 210, 'time' => '3-5 أيام عمل'],
    ['name_en' => 'Aswan', 'name_ar' => 'أسوان', 'sort' => 220, 'time' => '3-5 أيام عمل'],
    ['name_en' => 'Red Sea', 'name_ar' => 'البحر الأحمر (الغردقة)', 'sort' => 230, 'time' => '3-5 أيام عمل'],
    ['name_en' => 'South Sinai', 'name_ar' => 'جنوب سيناء (شرم الشيخ)', 'sort' => 240, 'time' => '3-5 أيام عمل'],
    ['name_en' => 'North Sinai', 'name_ar' => 'شمال سيناء', 'sort' => 250, 'time' => '3-6 أيام عمل'],
    ['name_en' => 'Matrouh', 'name_ar' => 'مطروح والساحل الشمالي', 'sort' => 260, 'time' => '3-5 أيام عمل'],
    ['name_en' => 'New Valley', 'name_ar' => 'الوادي الجديد', 'sort' => 270, 'time' => '4-7 أيام عمل'],
];

if ($action === 'seed_all') {
    $defaultCost = (float)($_POST['default_cost'] ?? 50.0);
    $inserted = 0;
    $updated = 0;

    foreach ($allGovernorates as $gov) {
        $st = $pdo->prepare('SELECT id FROM shipping_cities WHERE name_ar = ? OR name_en = ? LIMIT 1');
        $st->execute([$gov['name_ar'], $gov['name_en']]);
        $existingId = $st->fetchColumn();

        if ($existingId) {
            $up = $pdo->prepare('UPDATE shipping_cities SET sort_order = ?, delivery_time = COALESCE(delivery_time, ?) WHERE id = ?');
            $up->execute([$gov['sort'], $gov['time'], $existingId]);
            $updated++;
        } else {
            $ins = $pdo->prepare('INSERT INTO shipping_cities (name_en, name_ar, shipping_cost, sort_order, active, delivery_time) VALUES (?, ?, ?, ?, 1, ?)');
            $ins->execute([$gov['name_en'], $gov['name_ar'], $defaultCost, $gov['sort'], $gov['time']]);
            $inserted++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "تم إدراج {$inserted} محافظة جديدة وتحديث {$updated} محافظة بنجاح!"
    ]);
    exit;
}

if ($action === 'toggle_active') {
    $id = (int)($_POST['id'] ?? 0);
    $active = (int)($_POST['active'] ?? 1);

    $st = $pdo->prepare('UPDATE shipping_cities SET active = ? WHERE id = ?');
    $st->execute([$active ? 1 : 0, $id]);

    echo json_encode(['success' => true, 'id' => $id, 'active' => $active]);
    exit;
}

if ($action === 'update_cost') {
    $id = (int)($_POST['id'] ?? 0);
    $cost = max(0, (float)($_POST['cost'] ?? 0));

    $st = $pdo->prepare('UPDATE shipping_cities SET shipping_cost = ? WHERE id = ?');
    $st->execute([$cost, $id]);

    echo json_encode(['success' => true, 'id' => $id, 'cost' => $cost]);
    exit;
}

if ($action === 'update_time') {
    $id = (int)($_POST['id'] ?? 0);
    $time = trim((string)($_POST['time'] ?? ''));

    $st = $pdo->prepare('UPDATE shipping_cities SET delivery_time = ? WHERE id = ?');
    $st->execute([$time, $id]);

    echo json_encode(['success' => true, 'id' => $id, 'time' => $time]);
    exit;
}

if ($action === 'bulk_cost') {
    $cost = max(0, (float)($_POST['cost'] ?? 0));
    $applyTo = $_POST['apply_to'] ?? 'active'; // 'active' or 'all'

    if ($applyTo === 'all') {
        $st = $pdo->prepare('UPDATE shipping_cities SET shipping_cost = ?');
        $st->execute([$cost]);
    } else {
        $st = $pdo->prepare('UPDATE shipping_cities SET shipping_cost = ? WHERE active = 1');
        $st->execute([$cost]);
    }

    echo json_encode(['success' => true, 'message' => "تم تحديث سعر الشحن لجميع المحافظات بنجاح إلى {$cost} ج.م"]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
