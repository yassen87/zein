<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function all_locations(?array $types = null): array
{
    $sql = 'SELECT * FROM locations WHERE is_active = 1';
    $params = [];
    if ($types !== null) {
        $types = array_values(array_filter($types, fn ($type) => is_string($type) && $type !== ''));
        if ($types) {
            $sql .= ' AND type IN (' . implode(',', array_fill(0, count($types), '?')) . ')';
            $params = $types;
        }
    }
    $sql .= ' ORDER BY id';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function all_locations_with_inactive(): array
{
    return pdo()->query('SELECT * FROM locations ORDER BY is_active DESC, type, id DESC')->fetchAll();
}

function find_location_any(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT * FROM locations WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function validate_location_data(array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $type = (string) ($data['type'] ?? 'branch');
    $allowedTypes = ['warehouse', 'branch', 'online'];
    if ($name === '') {
        throw new RuntimeException('اسم الفرع أو الموقع مطلوب.');
    }
    if (!in_array($type, $allowedTypes, true)) {
        throw new RuntimeException('نوع الموقع غير صحيح.');
    }

    return [
        'name' => $name,
        'type' => $type,
        'latitude' => trim((string) ($data['latitude'] ?? '')),
        'longitude' => trim((string) ($data['longitude'] ?? '')),
        'geo_radius_m' => max(1, (int) ($data['geo_radius_m'] ?? 100)),
    ];
}

function add_location(array $data, int $userId): void
{
    $data = validate_location_data($data);
    $stmt = pdo()->prepare('INSERT INTO locations (name, type, latitude, longitude, geo_radius_m, is_active) VALUES (?, ?, ?, ?, ?, 1)');
    $stmt->execute([
        $data['name'],
        $data['type'],
        $data['latitude'] !== '' ? (float) $data['latitude'] : null,
        $data['longitude'] !== '' ? (float) $data['longitude'] : null,
        $data['geo_radius_m'],
    ]);
    log_audit($userId, 'create', 'location', (int) pdo()->lastInsertId(), 'إضافة فرع/موقع جديد: ' . $data['name']);
}

function update_location_data(array $data, int $userId): void
{
    $id = (int) ($data['id'] ?? 0);
    $location = find_location_any($id);
    if (!$location) {
        throw new RuntimeException('الفرع أو الموقع غير موجود.');
    }

    $data = validate_location_data($data);
    $stmt = pdo()->prepare('UPDATE locations SET name = ?, type = ?, latitude = ?, longitude = ?, geo_radius_m = ? WHERE id = ?');
    $stmt->execute([
        $data['name'],
        $data['type'],
        $data['latitude'] !== '' ? (float) $data['latitude'] : null,
        $data['longitude'] !== '' ? (float) $data['longitude'] : null,
        $data['geo_radius_m'],
        $id,
    ]);
    log_audit($userId, 'update', 'location', $id, 'تعديل بيانات الفرع/الموقع: ' . $data['name']);
}

function set_location_active(int $id, bool $isActive, int $userId): void
{
    $location = find_location_any($id);
    if (!$location) {
        throw new RuntimeException('الفرع أو الموقع غير موجود.');
    }

    $stmt = pdo()->prepare('UPDATE locations SET is_active = ? WHERE id = ?');
    $stmt->execute([$isActive ? 1 : 0, $id]);
    log_audit($userId, $isActive ? 'activate' : 'deactivate', 'location', $id, ($isActive ? 'تفعيل' : 'تعطيل') . ' الفرع/الموقع: ' . $location['name']);
}

function sale_locations(): array
{
    return all_locations(['branch']);
}

function stock_locations(): array
{
    return all_locations(['warehouse', 'branch']);
}

function attendance_locations(): array
{
    return all_locations(['warehouse', 'branch']);
}

function product_type_labels(): array
{
    return ['bottle' => 'زجاجة', 'perfume_gram' => 'عطر بالجرام', 'fixed' => 'منتج جاهز', 'recipe' => 'تركيبة'];
}

function product_type_label(?string $type): string
{
    $labels = product_type_labels();
    return $labels[$type ?? ''] ?? (string) $type;
}

function product_unit_label(?string $unit): string
{
    return $unit === 'gram' ? 'جرام' : 'قطعة';
}

function perfume_family_labels(): array
{
    return [
        'oriental' => 'شرقي',
        'french' => 'فرنسي',
        'niche' => 'نيش',
        'niche_liter' => 'نيش ليتر',
    ];
}

function perfume_family_label(?string $family): string
{
    $labels = perfume_family_labels();
    return $labels[$family ?? ''] ?? (string) $family;
}

function quality_grade_labels(): array
{
    return ['' => 'بدون', 'A' => 'A', 'A+' => 'A+', 'B' => 'B', 'X' => 'X'];
}
function all_products(?string $type = null): array
{
    $sql = "SELECT p.*, b.size_ml, d.perfume_family, d.quality_grade, d.price_per_gram
        FROM products p
        LEFT JOIN product_bottle_details b ON b.product_id = p.id
        LEFT JOIN product_perfume_details d ON d.product_id = p.id
        WHERE p.is_active = 1";
    $params = [];
    if ($type !== null) {
        $sql .= ' AND p.type = ?';
        $params[] = $type;
    }
    $sql .= ' ORDER BY p.created_at DESC, p.id DESC';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function product_rows(array $filters = []): array
{
    $sql = "SELECT p.*, b.size_ml, d.perfume_family, d.quality_grade, d.price_per_gram
        FROM products p
        LEFT JOIN product_bottle_details b ON b.product_id = p.id
        LEFT JOIN product_perfume_details d ON d.product_id = p.id
        WHERE p.is_active = 1";
    $params = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $sql .= ' AND (p.name LIKE ? OR p.barcode LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if (!empty($filters['type'])) {
        $sql .= ' AND p.type = ?';
        $params[] = (string) $filters['type'];
    }
    if (!empty($filters['unit'])) {
        $sql .= ' AND p.unit = ?';
        $params[] = (string) $filters['unit'];
    }
    if (!empty($filters['perfume_family'])) {
        $sql .= ' AND d.perfume_family = ?';
        $params[] = (string) $filters['perfume_family'];
    }
    if (isset($filters['quality_grade']) && $filters['quality_grade'] !== '') {
        $sql .= " AND COALESCE(d.quality_grade, '') = ?";
        $params[] = (string) $filters['quality_grade'];
    }
    if (!empty($filters['size_ml'])) {
        $sql .= ' AND b.size_ml = ?';
        $params[] = (int) $filters['size_ml'];
    }
    if (($filters['barcode_status'] ?? '') === 'with_barcode') {
        $sql .= " AND p.barcode IS NOT NULL AND p.barcode <> ''";
    } elseif (($filters['barcode_status'] ?? '') === 'without_barcode') {
        $sql .= " AND (p.barcode IS NULL OR p.barcode = '')";
    }
    $sql .= ' ORDER BY p.created_at DESC, p.id DESC';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function find_product(int $id): ?array
{
    $stmt = pdo()->prepare("SELECT p.*, b.size_ml, d.perfume_family, d.quality_grade, d.price_per_gram
        FROM products p
        LEFT JOIN product_bottle_details b ON b.product_id = p.id
        LEFT JOIN product_perfume_details d ON d.product_id = p.id
        WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function find_product_by_unique(?string $sku, ?string $barcode, ?int $excludeId = null): ?array
{
    $conditions = [];
    $params = [];

    if ($sku !== null && $sku !== '') {
        $conditions[] = 'p.sku = ?';
        $params[] = $sku;
    }
    if ($barcode !== null && $barcode !== '') {
        $conditions[] = 'p.barcode = ?';
        $params[] = $barcode;
    }
    if (!$conditions) {
        return null;
    }

    $sql = "SELECT p.*, b.size_ml, d.perfume_family, d.quality_grade, d.price_per_gram
        FROM products p
        LEFT JOIN product_bottle_details b ON b.product_id = p.id
        LEFT JOIN product_perfume_details d ON d.product_id = p.id
        WHERE (" . implode(' OR ', $conditions) . ')';
    if ($excludeId !== null) {
        $sql .= ' AND p.id <> ?';
        $params[] = $excludeId;
    }
    $sql .= ' ORDER BY p.is_active DESC, p.id DESC LIMIT 1';

    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

function find_product_by_barcode(string $barcode): ?array
{
    $stmt = pdo()->prepare("SELECT p.*, b.size_ml, d.perfume_family, d.quality_grade, d.price_per_gram
        FROM products p
        LEFT JOIN product_bottle_details b ON b.product_id = p.id
        LEFT JOIN product_perfume_details d ON d.product_id = p.id
        WHERE p.barcode = ? AND p.is_active = 1
        LIMIT 1");
    $stmt->execute([$barcode]);
    return $stmt->fetch() ?: null;
}

function save_product_details(PDO $db, int $productId, string $type, array $data): void
{
    if ($type === 'bottle' || ($type === 'fixed' && isset($data['size_ml']) && (int)$data['size_ml'] > 0)) {
        $stmt = $db->prepare('INSERT INTO product_bottle_details (product_id, size_ml) VALUES (?, ?) ON DUPLICATE KEY UPDATE size_ml = VALUES(size_ml)');
        $stmt->execute([$productId, (int) $data['size_ml']]);
        $db->prepare('DELETE FROM product_perfume_details WHERE product_id = ?')->execute([$productId]);
        return;
    }

    if ($type === 'perfume_gram') {
        $stmt = $db->prepare('INSERT INTO product_perfume_details (product_id, perfume_family, quality_grade, price_per_gram) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE perfume_family = VALUES(perfume_family), quality_grade = VALUES(quality_grade), price_per_gram = VALUES(price_per_gram)');
        $stmt->execute([$productId, $data['perfume_family'], $data['quality_grade'] ?: null, (float) $data['price_per_gram']]);
        $db->prepare('DELETE FROM product_bottle_details WHERE product_id = ?')->execute([$productId]);
        return;
    }

    $db->prepare('DELETE FROM product_bottle_details WHERE product_id = ?')->execute([$productId]);
    $db->prepare('DELETE FROM product_perfume_details WHERE product_id = ?')->execute([$productId]);
}

function reactivate_product(array $product, array $data): void
{
    if ($product['type'] !== $data['type']) {
        throw new RuntimeException('يوجد منتج محذوف بنفس الباركود لكن بنوع مختلف. عدل المنتج القديم أو استخدم رمزاً مختلفاً.');
    }

    $db = pdo();
    $type = $data['type'];
    $unit = $type === 'perfume_gram' ? 'gram' : 'unit';
    $salePrice = $type === 'perfume_gram' ? (float) $data['price_per_gram'] : (float) $data['sale_price'];
    $stmt = $db->prepare('UPDATE products SET sku = NULL, barcode = ?, name = ?, unit = ?, sale_price = ?, cost_price = ?, min_stock = ?, is_active = 1 WHERE id = ?');
    $stmt->execute([
        $data['barcode'] ?: $product['barcode'],
        $data['name'],
        $unit,
        $salePrice,
        $data['cost_price'] !== '' ? (float) $data['cost_price'] : null,
        (float) ($data['min_stock'] ?? 0),
        (int) $product['id'],
    ]);
    save_product_details($db, (int) $product['id'], $type, $data);

    $user = current_user();
    log_audit($user ? (int)$user['id'] : null, 'update', 'product', (int)$product['id'], 'إعادة تفعيل المنتج: ' . $data['name']);
}

function normalize_sizes_list(array|string|null $raw): array
{
    if (is_array($raw)) {
        $values = $raw;
    } else {
        $values = preg_split('/[\n,،]+/', (string) $raw) ?: [];
    }
    $sizes = [];
    foreach ($values as $v) {
        $size = (int) trim((string) $v);
        if ($size > 0) {
            $sizes[$size] = $size;
        }
    }
    return array_values($sizes);
}

function add_products_batch(array $data): array
{
    $type = (string) ($data['type'] ?? '');
    $baseName = trim((string) ($data['name'] ?? ''));
    if ($baseName === '') {
        throw new RuntimeException('اسم المنتج مطلوب.');
    }

    $variants = [];

    if ($type === 'bottle' || $type === 'fixed') {
        // New row-based input: variants[size][], variants[sale_price][], variants[cost_price][]
        $variantRows = $data['variants'] ?? [];
        $sizes = $variantRows['size'] ?? [];
        if (!is_array($sizes)) {
            $sizes = [];
        }
        foreach ($sizes as $idx => $rawSize) {
            $size = (int) $rawSize;
            if ($size <= 0) {
                continue;
            }
            $salePrice = (int) ($variantRows['sale_price'][$idx] ?? 0);
            $costPrice = $variantRows['cost_price'][$idx] ?? '';
            if ($salePrice < 0) {
                throw new RuntimeException('سعر البيع لا يمكن أن يكون بالسالب.');
            }
            $variant = $data;
            $variant['name'] = $baseName . ' ' . $size . 'ml';
            $variant['size_ml'] = $size;
            $variant['sale_price'] = $salePrice;
            $variant['cost_price'] = $costPrice;
            $variant['barcode'] = '';
            $variants[] = $variant;
        }
        if (!$variants) {
            throw new RuntimeException('يجب إدخال حجم واحد على الأقل.');
        }
    } elseif ($type === 'perfume_gram') {
        $family = (string)($data['perfume_family'] ?? 'oriental');
        if (!array_key_exists($family, perfume_family_labels())) {
            throw new RuntimeException('عائلة العطر غير صحيحة.');
        }
        // New row-based input: variants[quality][], variants[price_per_gram][], variants[cost_price][]
        $variantRows = $data['variants'] ?? [];
        $qualities = $variantRows['quality'] ?? [];
        if (!is_array($qualities)) {
            $qualities = [];
        }
        $allowed = array_keys(quality_grade_labels());
        foreach ($qualities as $idx => $qualityRaw) {
            $quality = trim((string) $qualityRaw);
            if (!in_array($quality, $allowed, true)) {
                continue;
            }
            $pricePerGram = (int) ($variantRows['price_per_gram'][$idx] ?? 0);
            $costPrice = $variantRows['cost_price'][$idx] ?? '';
            if ($pricePerGram < 0) {
                throw new RuntimeException('سعر الجرام لا يمكن أن يكون بالسالب.');
            }
            $variant = $data;
            $variant['name'] = $baseName . ' ' . perfume_family_label($family) . ' ' . ($quality !== '' ? $quality : 'بدون جودة');
            $variant['perfume_family'] = $family;
            $variant['quality_grade'] = $quality;
            $variant['price_per_gram'] = $pricePerGram;
            $variant['cost_price'] = $costPrice;
            $variant['barcode'] = '';
            $variants[] = $variant;
        }
        if (!$variants) {
            throw new RuntimeException('يجب إدخال جودة واحدة على الأقل.');
        }
    } else {
        throw new RuntimeException('نوع المنتج غير صحيح.');
    }

    $db = pdo();
    $db->beginTransaction();
    try {
        $ids = [];
        foreach ($variants as $variant) {
            $vType = (string) $variant['type'];
            $barcode = generate_unique_ean13($db);
            $unit = $vType === 'perfume_gram' ? 'gram' : 'unit';
            $salePrice = $vType === 'perfume_gram' ? (float) ($variant['price_per_gram'] ?? 0) : (float) ($variant['sale_price'] ?? 0);
            $stmt = $db->prepare('INSERT INTO products (sku, barcode, name, type, unit, sale_price, cost_price, min_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                null,
                $barcode,
                (string) $variant['name'],
                $vType,
                $unit,
                $salePrice,
                isset($variant['cost_price']) && $variant['cost_price'] !== '' ? (float) $variant['cost_price'] : null,
                (float) ($variant['min_stock'] ?? 0),
            ]);
            $productId = (int) $db->lastInsertId();
            save_product_details($db, $productId, $vType, $variant);
            $ids[] = $productId;
        }
        $user = current_user();
        log_audit($user ? (int)$user['id'] : null, 'create', 'product', null, 'إضافة منتجات متعددة: ' . $baseName . ' (' . count($ids) . ')');
        $db->commit();
        return $ids;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function add_product(array $data): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $type = $data['type'];
        $barcode = trim((string) ($data['barcode'] ?? ''));
        $existing = find_product_by_unique(null, $barcode !== '' ? $barcode : null);
        if ($existing) {
            if ((int) $existing['is_active'] === 0) {
                reactivate_product($existing, $data);
                $db->commit();
                return;
            }
            throw new RuntimeException('الباركود مستخدم بالفعل في منتج آخر: ' . $existing['name']);
        }

        $unit = $type === 'perfume_gram' ? 'gram' : 'unit';
        $salePrice = $type === 'perfume_gram' ? (float) $data['price_per_gram'] : (float) $data['sale_price'];
        $stmt = $db->prepare('INSERT INTO products (sku, barcode, name, type, unit, sale_price, cost_price, min_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            null,
            $barcode !== '' ? $barcode : generate_unique_ean13($db),
            $data['name'],
            $type,
            $unit,
            $salePrice,
        isset($data['cost_price']) && $data['cost_price'] !== '' ? (float) $data['cost_price'] : null,
            (float) ($data['min_stock'] ?? 0),
        ]);
        $productId = (int) $db->lastInsertId();
        save_product_details($db, $productId, $type, $data);
        $user = current_user();
        log_audit($user ? (int)$user['id'] : null, 'create', 'product', $productId, 'إضافة منتج جديد: ' . $data['name']);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function update_product(array $data): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $product = find_product((int) $data['id']);
        if (!$product) {
            throw new RuntimeException('المنتج غير موجود.');
        }
        $barcode = trim((string) ($data['barcode'] ?? ''));
        $existing = find_product_by_unique(null, $barcode !== '' ? $barcode : null, (int) $data['id']);
        if ($existing) {
            throw new RuntimeException('الباركود مستخدم بالفعل في منتج آخر: ' . $existing['name']);
        }

        $salePrice = $product['type'] === 'perfume_gram' ? (float) $data['price_per_gram'] : (float) $data['sale_price'];
        $stmt = $db->prepare('UPDATE products SET sku = NULL, barcode = ?, name = ?, sale_price = ?, cost_price = ?, min_stock = ? WHERE id = ?');
        $stmt->execute([
            $barcode !== '' ? $barcode : $product['barcode'],
            $data['name'],
            $salePrice,
            isset($data['cost_price']) && $data['cost_price'] !== '' ? (float) $data['cost_price'] : null,
            (float) ($data['min_stock'] ?? 0),
            (int) $data['id'],
        ]);
        save_product_details($db, (int) $data['id'], $product['type'], $data);
        $user = current_user();
        log_audit($user ? (int)$user['id'] : null, 'update', 'product', (int)$data['id'], 'تعديل بيانات المنتج: ' . $data['name']);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function settings_rows(): array
{
    return pdo()->query('SELECT * FROM app_settings ORDER BY setting_key')->fetchAll();
}

function update_settings(array $data, int $userId): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        foreach (($data['settings'] ?? []) as $key => $value) {
            $stmt = $db->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            $stmt->execute([$key, trim((string) $value)]);
        }
        $db->commit();
        log_audit($userId, 'update', 'settings', null, 'تعديل إعدادات النظام');
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function setting_value(string $key, mixed $default = null): mixed
{
    $stmt = pdo()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : $value;
}

function deactivate_product(int $id): void
{
    $stmt = pdo()->prepare('UPDATE products SET is_active = 0 WHERE id = ?');
    $stmt->execute([$id]);
}

function delete_product(int $id): string
{
    $db = pdo();
    $product = find_product($id);
    if (!$product) {
        throw new RuntimeException('المنتج غير موجود.');
    }
    
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $db->commit();
        $user = current_user();
        log_audit($user ? (int)$user['id'] : null, 'delete', 'product', $id, 'حذف المنتج نهائياً: ' . $product['name']);
        return 'permanently_deleted';
    } catch (Throwable $e) {
        $db->rollBack();
        deactivate_product($id);
        $user = current_user();
        log_audit($user ? (int)$user['id'] : null, 'deactivate', 'product', $id, 'تعطيل وإخفاء المنتج: ' . $product['name']);
        return 'deactivated';
    }
}

function generate_ean13(): string
{
    $base = '622' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int) $base[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    $check = (10 - ($sum % 10)) % 10;
    return $base . $check;
}

function generate_unique_ean13(PDO $db): string
{
    for ($i = 0; $i < 50; $i++) {
        $barcode = generate_ean13();
        $stmt = $db->prepare('SELECT COUNT(*) FROM products WHERE barcode = ?');
        $stmt->execute([$barcode]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $barcode;
        }
    }
    throw new RuntimeException('تعذر توليد باركود فريد. حاول مرة أخرى.');
}

function validate_supply_transfer(array $data): void
{
    $from = (int) $data['from_location_id'];
    $to = (int) $data['to_location_id'];
    require_location_access($from);
    require_location_type($from, ['warehouse'], 'أمر التوريد يجب أن يكون من المخزن الرئيسي فقط.');
    require_location_type($to, ['branch'], 'أمر التوريد يجب أن يكون إلى فرع فقط.');
    if ($from === $to) {
        throw new RuntimeException('لا يمكن التحويل لنفس الموقع.');
    }
}

function validate_branch_transfer(array $data): void
{
    $from = (int) $data['from_location_id'];
    $to = (int) $data['to_location_id'];
    require_location_access($from);
    require_location_type($from, ['branch'], 'التحويل بين الفروع يجب أن يكون من فرع.');
    require_location_type($to, ['branch'], 'التحويل بين الفروع يجب أن يكون إلى فرع.');
    if ($from === $to) {
        throw new RuntimeException('لا يمكن التحويل لنفس الموقع.');
    }
}

function require_transfer_location_types(PDO $db, array $transfer, array $fromTypes, array $toTypes): void
{
    $stmt = $db->prepare('SELECT fl.type AS from_type, tl.type AS to_type FROM locations fl JOIN locations tl ON tl.id = ? WHERE fl.id = ?');
    $stmt->execute([(int) $transfer['to_location_id'], (int) $transfer['from_location_id']]);
    $types = $stmt->fetch();
    if (!$types || !in_array($types['from_type'], $fromTypes, true) || !in_array($types['to_type'], $toTypes, true)) {
        throw new RuntimeException('نوع أمر التحويل لا يطابق الصفحة الحالية.');
    }
}

function transfer_line_items(array $data): array
{
    $items = [];
    foreach (($data['product_id'] ?? []) as $idx => $productId) {
        $productId = (int) $productId;
        $quantity = (float) ($data['quantity'][$idx] ?? 0);
        if ($productId > 0 && $quantity > 0) {
            $items[] = ['product_id' => $productId, 'quantity' => $quantity];
        }
    }
    return $items;
}

function assert_transfer_stock_available(PDO $db, int $fromLocationId, array $lineItems): void
{
    if (!$lineItems) {
        throw new RuntimeException('يجب إضافة صنف واحد على الأقل في التحويل.');
    }

    // Aggregate duplicate product rows to validate total requested quantity.
    $required = [];
    foreach ($lineItems as $item) {
        $productId = (int) $item['product_id'];
        $required[$productId] = ($required[$productId] ?? 0) + (float) $item['quantity'];
    }

    $stockStmt = $db->prepare('SELECT COALESCE(quantity, 0) FROM inventory_balances WHERE location_id = ? AND product_id = ? FOR UPDATE');
    foreach ($required as $productId => $quantity) {
        $stockStmt->execute([$fromLocationId, $productId]);
        $available = (float) ($stockStmt->fetchColumn() ?: 0);
        if ($available + 0.0001 < $quantity) {
            $product = find_product((int) $productId);
            throw new RuntimeException('لا يمكن تنفيذ التحويل. الرصيد غير كاف للصنف: ' . ($product['name'] ?? ('#' . $productId)) . ' — المتاح: ' . qty($available) . ' والمطلوب: ' . qty($quantity));
        }
    }
}

function inventory_rows(?int $locationId = null): array
{
    $sql = "SELECT 
                ib.id,
                l.id AS location_id,
                l.name AS location_name,
                p.id AS product_id,
                p.name AS product_name,
                p.type,
                p.unit,
                p.min_stock,
                COALESCE(ib.quantity, 0) AS quantity
            FROM locations l
            CROSS JOIN products p
            LEFT JOIN inventory_balances ib ON ib.location_id = l.id AND ib.product_id = p.id
            WHERE p.is_active = 1 AND l.is_active = 1";
    $params = [];
    if ($locationId) {
        $sql .= ' AND l.id = ?';
        $params[] = $locationId;
    }
    $sql .= ' ORDER BY l.id, p.name';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function inventory_movements_rows(int $limit = 100, ?int $locationId = null): array
{
    $sql = 'SELECT im.*, l.name AS location_name, p.name AS product_name, u.name AS user_name 
            FROM inventory_movements im 
            JOIN locations l ON l.id = im.location_id 
            JOIN products p ON p.id = im.product_id 
            JOIN users u ON u.id = im.created_by';
    $params = [];
    if ($locationId !== null) {
        $sql .= ' WHERE im.location_id = ?';
        $params[] = $locationId;
    }
    $sql .= ' ORDER BY im.created_at DESC, im.id DESC LIMIT ' . (int) $limit;
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function inventory_addition_rows(array $filters = [], ?int $locationId = null): array
{
    $sql = 'SELECT im.*, l.name AS location_name, p.name AS product_name, p.sale_price, p.cost_price, p.unit, u.name AS user_name 
            FROM inventory_movements im 
            JOIN locations l ON l.id = im.location_id 
            JOIN products p ON p.id = im.product_id 
            JOIN users u ON u.id = im.created_by 
            WHERE im.quantity_delta > 0 AND im.movement_type IN ("initial","manual_adjustment")';
    $params = [];
    if ($locationId !== null) {
        $sql .= ' AND im.location_id = ?';
        $params[] = $locationId;
    }
    if (!empty($filters['location_id'])) {
        $sql .= ' AND im.location_id = ?';
        $params[] = (int) $filters['location_id'];
    }
    if (!empty($filters['product_id'])) {
        $sql .= ' AND im.product_id = ?';
        $params[] = (int) $filters['product_id'];
    }
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $sql .= ' AND (p.name LIKE ? OR im.notes LIKE ? OR l.name LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if (!empty($filters['date_from'])) {
        $sql .= ' AND DATE(im.created_at) >= ?';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $sql .= ' AND DATE(im.created_at) <= ?';
        $params[] = $filters['date_to'];
    }
    $sql .= ' ORDER BY im.created_at DESC, im.id DESC';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function find_inventory_movement(int $movementId): ?array
{
    $stmt = pdo()->prepare('SELECT im.*, p.sale_price, p.cost_price FROM inventory_movements im JOIN products p ON p.id = im.product_id WHERE im.id = ?');
    $stmt->execute([$movementId]);
    return $stmt->fetch() ?: null;
}

function update_product_prices(int $productId, float $salePrice, ?float $costPrice): void
{
    $stmt = pdo()->prepare('UPDATE products SET sale_price = ?, cost_price = ? WHERE id = ?');
    $stmt->execute([$salePrice, $costPrice, $productId]);
}

function create_inventory_addition(array $data, int $userId): void
{
    $locationId = (int) ($data['location_id'] ?? 0);
    $productId = (int) ($data['product_id'] ?? 0);
    $quantity = (float) ($data['quantity'] ?? 0);
    $notes = trim((string) ($data['notes'] ?? 'إضافة مخزون'));
    $salePrice = (float) ($data['sale_price'] ?? 0);
    $costPrice = $data['cost_price'] !== '' ? (float) $data['cost_price'] : null;

    if ($locationId <= 0 || $productId <= 0 || $quantity <= 0) {
        throw new RuntimeException('الرجاء إدخال موقع، صنف، وكمية صالحة.');
    }

    update_product_prices($productId, $salePrice, $costPrice);

    $db = pdo();
    $db->beginTransaction();
    try {
        move_inventory($db, $locationId, $productId, $quantity, 'initial', $userId, 'manual', null, $notes);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function create_inventory_additions(array $data, int $userId): void
{
    $locationId = (int) ($data['location_id'] ?? 0);
    if ($locationId <= 0) {
        throw new RuntimeException('الرجاء تحديد موقع صالح.');
    }

    $productIds = array_values(array_filter((array) ($data['product_id'] ?? []), fn ($id) => is_numeric($id) && (int) $id > 0));
    $quantities = array_values((array) ($data['quantity'] ?? []));
    $salePrices = array_values((array) ($data['sale_price'] ?? []));
    $costPrices = array_values((array) ($data['cost_price'] ?? []));
    $notesList = array_values((array) ($data['notes'] ?? []));

    if (empty($productIds)) {
        throw new RuntimeException('يرجى إضافة صنف واحد على الأقل.');
    }

    $db = pdo();
    $db->beginTransaction();
    try {
        foreach ($productIds as $index => $productId) {
            $productId = (int) $productId;
            $quantity = (float) ($quantities[$index] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            $salePrice = isset($salePrices[$index]) ? (float) $salePrices[$index] : 0;
            $costPrice = isset($costPrices[$index]) && $costPrices[$index] !== '' ? (float) $costPrices[$index] : null;
            $notes = trim((string) ($notesList[$index] ?? 'إضافة مخزون'));
            if ($notes === '') {
                $notes = 'إضافة مخزون';
            }

            update_product_prices($productId, $salePrice, $costPrice);
            move_inventory($db, $locationId, $productId, $quantity, 'initial', $userId, 'manual', null, $notes);
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function update_inventory_addition(array $data, int $userId): void
{
    $movementId = (int) ($data['movement_id'] ?? 0);
    $movement = find_inventory_movement($movementId);
    if (!$movement) {
        throw new RuntimeException('حركة الإضافة غير موجودة.');
    }
    $locationId = (int) ($data['location_id'] ?? $movement['location_id']);
    $productId = (int) ($data['product_id'] ?? $movement['product_id']);
    $quantity = (float) ($data['quantity'] ?? 0);
    $notes = trim((string) ($data['notes'] ?? 'تعديل إضافة مخزون'));
    $salePrice = (float) ($data['sale_price'] ?? 0);
    $costPrice = $data['cost_price'] !== '' ? (float) $data['cost_price'] : null;

    if ($locationId <= 0 || $productId <= 0 || $quantity <= 0) {
        throw new RuntimeException('الرجاء إدخال موقع، صنف، وكمية صالحة.');
    }

    $db = pdo();
    $db->beginTransaction();
    try {
        $oldQuantity = (float) $movement['quantity_delta'];
        if ($oldQuantity !== 0.0) {
            move_inventory($db, (int) $movement['location_id'], (int) $movement['product_id'], -1 * $oldQuantity, 'manual_adjustment', $userId, 'manual', null, 'إلغاء إضافة مخزون #' . $movementId);
        }
        $stmt = $db->prepare('DELETE FROM inventory_movements WHERE id = ?');
        $stmt->execute([$movementId]);

        update_product_prices($productId, $salePrice, $costPrice);
        move_inventory($db, $locationId, $productId, $quantity, 'initial', $userId, 'manual', null, $notes);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function delete_inventory_addition(int $movementId, int $userId): void
{
    $movement = find_inventory_movement($movementId);
    if (!$movement) {
        throw new RuntimeException('حركة الإضافة غير موجودة.');
    }

    $db = pdo();
    $db->beginTransaction();
    try {
        $delta = -1 * (float) $movement['quantity_delta'];
        if ($delta !== 0.0) {
            move_inventory($db, (int) $movement['location_id'], (int) $movement['product_id'], $delta, 'manual_adjustment', $userId, 'manual', null, 'حذف إضافة مخزون #' . $movementId);
        }
        $stmt = $db->prepare('DELETE FROM inventory_movements WHERE id = ?');
        $stmt->execute([$movementId]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function get_stock(int $locationId, int $productId): float
{
    $stmt = pdo()->prepare('SELECT quantity FROM inventory_balances WHERE location_id = ? AND product_id = ?');
    $stmt->execute([$locationId, $productId]);
    return (float) ($stmt->fetchColumn() ?: 0);
}

function move_inventory(PDO $db, int $locationId, int $productId, float $delta, string $type, int $userId, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null): void
{
    $stmt = $db->prepare('SELECT quantity FROM inventory_balances WHERE location_id = ? AND product_id = ? FOR UPDATE');
    $stmt->execute([$locationId, $productId]);
    $current = $stmt->fetchColumn();
    $newQuantity = (float) ($current ?: 0) + $delta;
    if ($newQuantity < -0.0001) {
        $product = find_product($productId);
        throw new RuntimeException('المخزون غير كاف للصنف: ' . ($product['name'] ?? ('#' . $productId)));
    }

    if ($current === false) {
        $stmt = $db->prepare('INSERT INTO inventory_balances (location_id, product_id, quantity) VALUES (?, ?, ?)');
        $stmt->execute([$locationId, $productId, $newQuantity]);
    } else {
        $stmt = $db->prepare('UPDATE inventory_balances SET quantity = ? WHERE location_id = ? AND product_id = ?');
        $stmt->execute([$newQuantity, $locationId, $productId]);
    }

    $stmt = $db->prepare('INSERT INTO inventory_movements (location_id, product_id, movement_type, quantity_delta, reference_type, reference_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$locationId, $productId, $type, $delta, $referenceType, $referenceId, $notes, $userId]);
}

function adjust_inventory(int $locationId, int $productId, float $delta, int $userId, string $notes): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        move_inventory($db, $locationId, $productId, $delta, $delta >= 0 ? 'initial' : 'manual_adjustment', $userId, 'manual', null, $notes);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function batch_adjust_inventory(int $locationId, array $productIds, array $deltas, int $userId, array $notes): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        foreach ($productIds as $idx => $productId) {
            $productId = (int) $productId;
            $delta = (float) $deltas[$idx];
            $note = trim((string) ($notes[$idx] ?? 'تسوية مخزون'));
            if ($note === '') {
                $note = 'تسوية مخزون';
            }
            if ($productId > 0 && $delta !== 0.0) {
                move_inventory($db, $locationId, $productId, $delta, $delta >= 0 ? 'initial' : 'manual_adjustment', $userId, 'manual', null, $note);
            }
        }
        log_audit($userId, 'adjust', 'inventory', null, 'تسوية مخزون دفعة واحدة في موقع #' . $locationId);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function all_customers(): array
{
    return pdo()->query('SELECT * FROM customers WHERE is_active = 1 ORDER BY created_at DESC, id DESC')->fetchAll();
}

function all_users(): array
{
    return pdo()->query('SELECT u.*, r.name AS role_name, r.code AS role_code, l.name AS location_name FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN locations l ON l.id = u.location_id ORDER BY u.created_at DESC, u.id DESC')->fetchAll();
}

function all_roles(): array
{
    return pdo()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
}

function add_user(array $data): void
{
    $db = pdo();
    // Ensure column exists for backward compatibility
    $col = $db->query("SHOW COLUMNS FROM users LIKE 'working_days'")->fetch();
    if (!$col) {
        $db->exec('ALTER TABLE users ADD COLUMN working_days INT NOT NULL DEFAULT 0');
    }
    $stmt = $db->prepare('INSERT INTO users (name, username, password_hash, role_id, location_id, basic_salary, commission_percent, working_days, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['name'],
        $data['username'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        (int) $data['role_id'],
        $data['location_id'] !== '' ? (int) $data['location_id'] : null,
        (float) ($data['basic_salary'] ?? 0),
        (float) ($data['commission_percent'] ?? 0),
        (int) ($data['working_days'] ?? 0),
        isset($data['is_active']) ? (int) $data['is_active'] : 1
    ]);
}

function find_user(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT u.*, r.name AS role_name, r.code AS role_code, l.name AS location_name FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN locations l ON l.id = u.location_id WHERE u.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function update_user(array $data): void
{
    $db = pdo();
    // ensure working_days column exists
    $col = $db->query("SHOW COLUMNS FROM users LIKE 'working_days'")->fetch();
    if (!$col) {
        $db->exec('ALTER TABLE users ADD COLUMN working_days INT NOT NULL DEFAULT 0');
    }
    $db->beginTransaction();
    try {
        $id = (int) $data['id'];
        $user = find_user($id);
        if (!$user) throw new RuntimeException('الموظف غير موجود.');

        $passwordHash = $user['password_hash'];
        if (!empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $stmt = $db->prepare('UPDATE users SET name = ?, username = ?, password_hash = ?, role_id = ?, location_id = ?, basic_salary = ?, commission_percent = ?, working_days = ?, is_active = ? WHERE id = ?');
        $stmt->execute([
            $data['name'],
            $data['username'],
            $passwordHash,
            (int) $data['role_id'],
            $data['location_id'] !== '' ? (int) $data['location_id'] : null,
            (float) ($data['basic_salary'] ?? 0),
            (float) ($data['commission_percent'] ?? 0),
            (int) ($data['working_days'] ?? 0),
            isset($data['is_active']) ? (int) $data['is_active'] : 1,
            $id
        ]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function deactivate_user(int $id): void
{
    $stmt = pdo()->prepare('UPDATE users SET is_active = 0 WHERE id = ?');
    $stmt->execute([$id]);
}

function delete_user_permanently(int $id): void
{
    $user = find_user($id);
    if (!$user) {
        throw new RuntimeException('الموظف غير موجود.');
    }

    $stmt = pdo()->prepare('DELETE FROM users WHERE id = ?');
    try {
        $stmt->execute([$id]);
    } catch (Throwable $e) {
        throw new RuntimeException('لا يمكن حذف هذا الموظف نهائياً لوجود فواتير أو حركات أو سجلات مرتبطة به. استخدم التعطيل بدلاً من ذلك.');
    }
}

function add_customer(array $data): void
{
    $phone = trim((string) ($data['phone'] ?? ''));
    if ($phone !== '') {
        $existing = find_customer_by_phone($phone);
        if ($existing) {
            if ((int) $existing['is_active'] === 0) {
                $stmt = pdo()->prepare('UPDATE customers SET name = ?, source = ?, notes = ?, is_active = 1 WHERE id = ?');
                $stmt->execute([$data['name'], $data['source'], $data['notes'] ?: null, (int) $existing['id']]);
                $user = current_user();
                log_audit($user ? (int)$user['id'] : null, 'update', 'customer', (int) $existing['id'], 'إعادة تفعيل العميل: ' . $data['name']);
                return;
            }
            throw new RuntimeException('رقم الهاتف مسجل بالفعل للعميل: ' . $existing['name']);
        }
    }

    $stmt = pdo()->prepare('INSERT INTO customers (name, phone, source, notes) VALUES (?, ?, ?, ?)');
    $stmt->execute([$data['name'], $phone !== '' ? $phone : null, $data['source'], $data['notes'] ?: null]);
    $customerId = (int) pdo()->lastInsertId();
    $user = current_user();
    log_audit($user ? (int)$user['id'] : null, 'create', 'customer', $customerId, 'إضافة العميل الجديد: ' . $data['name']);
}

function find_customer_by_phone(string $phone): ?array
{
    $stmt = pdo()->prepare('SELECT * FROM customers WHERE phone = ? ORDER BY is_active DESC, id DESC LIMIT 1');
    $stmt->execute([$phone]);
    return $stmt->fetch() ?: null;
}

function find_customer(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function update_customer(array $data): void
{
    $phone = trim((string) ($data['phone'] ?? ''));
    if ($phone !== '') {
        $stmt = pdo()->prepare('SELECT * FROM customers WHERE phone = ? AND id <> ? LIMIT 1');
        $stmt->execute([$phone, (int) $data['id']]);
        $existing = $stmt->fetch();
        if ($existing) {
            throw new RuntimeException('رقم الهاتف مسجل بالفعل للعميل: ' . $existing['name']);
        }
    }

    $stmt = pdo()->prepare('UPDATE customers SET name = ?, phone = ?, source = ?, notes = ? WHERE id = ?');
    $stmt->execute([$data['name'], $phone !== '' ? $phone : null, $data['source'], $data['notes'] ?: null, (int) $data['id']]);
}

function deactivate_customer(int $id): void
{
    $stmt = pdo()->prepare('UPDATE customers SET is_active = 0 WHERE id = ?');
    $stmt->execute([$id]);
}

function delete_customer(int $id): string
{
    $db = pdo();
    $customer = find_customer($id);
    if (!$customer) {
        throw new RuntimeException('العميل غير موجود.');
    }
    
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('DELETE FROM customers WHERE id = ?');
        $stmt->execute([$id]);
        $db->commit();
        $user = current_user();
        log_audit($user ? (int)$user['id'] : null, 'delete', 'customer', $id, 'حذف العميل نهائياً: ' . $customer['name']);
        return 'permanently_deleted';
    } catch (Throwable $e) {
        $db->rollBack();
        deactivate_customer($id);
        $user = current_user();
        log_audit($user ? (int)$user['id'] : null, 'deactivate', 'customer', $id, 'تعطيل وإخفاء العميل: ' . $customer['name']);
        return 'deactivated';
    }
}

function customer_invoices(int $customerId): array
{
    $stmt = pdo()->prepare('SELECT i.*, l.name AS location_name, u.name AS user_name, c.name AS customer_name FROM invoices i JOIN locations l ON l.id = i.location_id JOIN users u ON u.id = i.user_id LEFT JOIN customers c ON c.id = i.customer_id WHERE i.customer_id = ? ORDER BY i.created_at DESC');
    $stmt->execute([$customerId]);
    return $stmt->fetchAll();
}

function customer_debts_rows(int $customerId): array
{
    $stmt = pdo()->prepare("SELECT d.*, i.invoice_number FROM customer_debts d JOIN invoices i ON i.id = d.invoice_id WHERE d.customer_id = ? ORDER BY d.created_at DESC");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll();
}

function customer_loyalty_rows(int $customerId): array
{
    return [];
}

function add_customer_payment(int $debtId, float $amount, string $method, int $userId): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT d.*, i.customer_id FROM customer_debts d JOIN invoices i ON i.id = d.invoice_id WHERE d.id = ? FOR UPDATE');
        $stmt->execute([$debtId]);
        $debt = $stmt->fetch();
        if (!$debt || $amount <= 0) {
            throw new RuntimeException('دفعة الدين غير صحيحة.');
        }
        $amount = min($amount, (float) $debt['remaining_amount']);
        $remaining = (float) $debt['remaining_amount'] - $amount;
        $status = $remaining <= 0.0001 ? 'paid' : 'open';
        $stmt = $db->prepare('UPDATE customer_debts SET paid_amount = paid_amount + ?, remaining_amount = ?, status = ?, closed_at = IF(? = "paid", NOW(), NULL) WHERE id = ?');
        $stmt->execute([$amount, $remaining, $status, $status, $debtId]);
        $stmt = $db->prepare('UPDATE invoices SET paid_total = paid_total + ?, due_total = GREATEST(0, due_total - ?) WHERE id = ?');
        $stmt->execute([$amount, $amount, $debt['invoice_id']]);
        $stmt = $db->prepare('INSERT INTO payments (invoice_id, customer_id, payment_type, method, amount, created_by) VALUES (?, ?, "debt_payment", ?, ?, ?)');
        $stmt->execute([$debt['invoice_id'], $debt['customer_id'], $method, $amount, $userId]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function formula_defaults_rows(): array
{
    return pdo()->query("
        SELECT f.*, p.name AS bottle_name
        FROM formula_defaults f
        LEFT JOIN products p ON p.id = f.bottle_product_id
        ORDER BY f.bottle_size_ml, f.perfume_family, f.quality_grade
    ")->fetchAll();
}

function upsert_formula_default(array $data): void
{
    $id = isset($data['id']) ? (int) $data['id'] : 0;
    $perfumeFamily = (string) ($data['perfume_family'] ?? 'oriental');
    $qualityGrade = trim((string) ($data['quality_grade'] ?? ''));
    $bottleSizeMl = (int) ($data['bottle_size_ml'] ?? 0);
    $defaultGrams = (int) ($data['default_grams'] ?? 0);
    $bottleProductId = !empty($data['bottle_id']) ? (int) $data['bottle_id'] : null;

    if (!array_key_exists($perfumeFamily, perfume_family_labels())) {
        throw new RuntimeException('عائلة العطر غير صحيحة.');
    }
    if (!in_array($qualityGrade, ['', 'A', 'A+', 'B', 'X'], true)) {
        throw new RuntimeException('درجة الجودة غير صحيحة.');
    }
    if ($bottleSizeMl <= 0) {
        throw new RuntimeException('حجم الزجاجة مطلوب.');
    }
    if ($defaultGrams <= 0) {
        throw new RuntimeException('الجرام الافتراضي يجب أن يكون أكبر من صفر.');
    }

    if ($id > 0) {
        $stmt = pdo()->prepare('UPDATE formula_defaults SET bottle_product_id = ?, perfume_family = ?, quality_grade = ?, bottle_size_ml = ?, default_grams = ? WHERE id = ?');
        $stmt->execute([$bottleProductId, $perfumeFamily, $qualityGrade, $bottleSizeMl, $defaultGrams, $id]);
    } else {
        $stmt = pdo()->prepare('INSERT INTO formula_defaults (bottle_product_id, perfume_family, quality_grade, bottle_size_ml, default_grams) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE default_grams = VALUES(default_grams), bottle_product_id = VALUES(bottle_product_id)');
        $stmt->execute([$bottleProductId, $perfumeFamily, $qualityGrade, $bottleSizeMl, $defaultGrams]);
    }
}

function saved_recipes(): array
{
    return pdo()->query('SELECT r.*, p.name AS bottle_name, b.size_ml FROM recipe_headers r JOIN products p ON p.id = r.bottle_product_id LEFT JOIN product_bottle_details b ON b.product_id = r.bottle_product_id WHERE r.is_active = 1 ORDER BY r.id DESC')->fetchAll();
}

function recipe_components_for(int $recipeId): array
{
    $stmt = pdo()->prepare('SELECT rc.*, p.name AS perfume_name FROM recipe_components rc JOIN products p ON p.id = rc.perfume_product_id WHERE rc.recipe_id = ? ORDER BY rc.id');
    $stmt->execute([$recipeId]);
    return $stmt->fetchAll();
}

function add_recipe(array $data): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO products (name, type, unit, sale_price, barcode) VALUES (?, "recipe", "unit", ?, ?)');
        $stmt->execute([$data['name'], (float) $data['default_sale_price'], generate_ean13()]);
        $productId = (int) $db->lastInsertId();
        $stmt = $db->prepare('INSERT INTO recipe_headers (product_id, name, bottle_product_id, default_sale_price) VALUES (?, ?, ?, ?)');
        $stmt->execute([$productId, $data['name'], (int) $data['bottle_product_id'], (float) $data['default_sale_price']]);
        $recipeId = (int) $db->lastInsertId();
        foreach (($data['perfume_product_id'] ?? []) as $idx => $perfumeId) {
            $grams = (float) ($data['grams'][$idx] ?? 0);
            if ((int) $perfumeId > 0 && $grams > 0) {
                $stmt = $db->prepare('INSERT INTO recipe_components (recipe_id, perfume_product_id, grams) VALUES (?, ?, ?)');
                $stmt->execute([$recipeId, (int) $perfumeId, $grams]);
            }
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function next_invoice_number(PDO $db, int $locationId): string
{
    $location = $db->prepare('SELECT type, id FROM locations WHERE id = ?');
    $location->execute([$locationId]);
    $row = $location->fetch();
    $prefix = $row && $row['type'] === 'branch' ? 'BR' . $row['id'] : strtoupper(substr((string) ($row['type'] ?? 'POS'), 0, 3));
    $date = date('Ymd');

    $stmt = $db->prepare('SELECT COUNT(*) FROM invoices WHERE location_id = ? AND DATE(created_at) = CURDATE()');
    $stmt->execute([$locationId]);
    $seq = ((int) $stmt->fetchColumn()) + 1;

    return sprintf('%s-%s-%04d', $prefix, $date, $seq);
}

function line_discount(float $gross, ?string $type, float $value): float
{
    if ($type === 'percent') {
        return min($gross, $gross * ($value / 100));
    }
    if ($type === 'amount') {
        return min($gross, $value);
    }
    return 0;
}

function create_invoice(array $data, array $user): int
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $locationId = (int) $data['location_id'];
        require_location_access($locationId);
        require_location_type($locationId, ['branch'], 'البيع مسموح من الفروع فقط. المخزن الرئيسي والأونلاين لا يسجلان فواتير بيع.');
        $customerId = $data['customer_id'] !== '' ? (int) $data['customer_id'] : null;
        $lines = [];
        $subtotal = 0.0;

        foreach (($data['product_id'] ?? []) as $idx => $productIdRaw) {
            $productId = (int) $productIdRaw;
            $quantity = (float) ($data['quantity'][$idx] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            $product = find_product($productId);
            if (!$product) {
                throw new RuntimeException('صنف غير موجود.');
            }
            $unitPrice = (float) ($data['unit_price'][$idx] ?? $product['sale_price']);
            $gross = $quantity * $unitPrice;
            $discountType = ($data['line_discount_type'][$idx] ?? '') ?: null;
            $discountValue = (float) ($data['line_discount_value'][$idx] ?? 0);
            $discount = line_discount($gross, $discountType, $discountValue);
            $total = $gross - $discount;
            $lines[] = [
                'kind' => 'product',
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discount,
                'line_total' => $total,
            ];
            $subtotal += $total;
        }

        // Handle instant mixes from cart (mix_data[])
        foreach (($data['mix_data'] ?? []) as $mixRaw) {
            $mix = json_decode($mixRaw, true);
            if (!is_array($mix) || empty($mix['bottle_id']) || empty($mix['components'])) {
                continue;
            }

            $bottleId = (int) $mix['bottle_id'];
            $mixSalePrice = (float) ($mix['sale_price'] ?? 0);
            if ($mixSalePrice <= 0) {
                continue;
            }

            $bottle = find_product($bottleId);
            if (!$bottle || $bottle['type'] !== 'bottle') {
                throw new RuntimeException('اختر زجاجة صحيحة للتركيبة: ' . ($bottle['name'] ?? ''));
            }

            $components = [['product' => $bottle, 'quantity' => 1.0]];
            $descriptionParts = [$bottle['name']];

            foreach ($mix['components'] as $comp) {
                $perfumeId = (int) ($comp['product_id'] ?? 0);
                $grams = (float) ($comp['grams'] ?? 0);
                $defaultGrams = (float) ($comp['default_grams'] ?? 0);
                if ($perfumeId <= 0 || $grams <= 0) {
                    continue;
                }
                $perfume = find_product($perfumeId);
                if (!$perfume || $perfume['type'] !== 'perfume_gram') {
                    throw new RuntimeException('اختر عطر صحيح داخل التركيبة.');
                }
                $components[] = ['product' => $perfume, 'quantity' => $grams];
                $gramsLabel = $defaultGrams > 0 && abs($grams - $defaultGrams) > 0.01 ? qty($grams) . 'جم (الأساسي ' . qty($defaultGrams) . 'جم)' : qty($grams) . 'جم';
                $descriptionParts[] = $perfume['name'] . ' ' . $gramsLabel;
            }

            if (count($components) > 1) {
                $lines[] = [
                    'kind' => 'custom_recipe',
                    'description' => 'تركيبة فورية: ' . implode(' + ', $descriptionParts),
                    'quantity' => 1.0,
                    'unit_price' => $mixSalePrice,
                    'discount_type' => null,
                    'discount_value' => 0.0,
                    'discount_amount' => 0.0,
                    'line_total' => $mixSalePrice,
                    'components' => $components,
                ];
                $subtotal += $mixSalePrice;
            }
        }

        foreach (($data['recipe_id'] ?? []) as $recipeIdRaw) {
            $recipeId = (int) $recipeIdRaw;
            if ($recipeId <= 0) {
                continue;
            }
            $stmt = $db->prepare('SELECT r.*, p.sale_price FROM recipe_headers r LEFT JOIN products p ON p.id = r.product_id WHERE r.id = ? AND r.is_active = 1');
            $stmt->execute([$recipeId]);
            $recipe = $stmt->fetch();
            if (!$recipe) {
                throw new RuntimeException('تركيبة جاهزة غير موجودة.');
            }
            $bottle = find_product((int) $recipe['bottle_product_id']);
            $components = [['product' => $bottle, 'quantity' => 1.0]];
            foreach (recipe_components_for($recipeId) as $component) {
                $perfume = find_product((int) $component['perfume_product_id']);
                $components[] = ['product' => $perfume, 'quantity' => (float) $component['grams']];
            }
            $price = (float) ($recipe['default_sale_price'] ?: $recipe['sale_price']);
            $lines[] = [
                'kind' => 'saved_recipe',
                'recipe_id' => $recipeId,
                'description' => 'تركيبة جاهزة: ' . $recipe['name'],
                'quantity' => 1.0,
                'unit_price' => $price,
                'line_total' => $price,
                'components' => $components,
            ];
            $subtotal += $price;
        }

        if (!$lines) {
            throw new RuntimeException('الفاتورة لا تحتوي على أصناف.');
        }

        $discountType = ($data['discount_type'] ?? '') ?: null;
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $discountAmount = line_discount($subtotal, $discountType, $discountValue);
        $total = $subtotal - $discountAmount;
        $paid = min($total, (float) ($data['paid_total'] ?? 0));
        $due = $total - $paid;
        if ($due > 0 && !$customerId) {
            throw new RuntimeException('يجب اختيار عميل عند وجود دفع جزئي أو دين.');
        }

        $stmt = $db->prepare('INSERT INTO invoices (invoice_number, location_id, user_id, customer_id, subtotal, discount_type, discount_value, discount_amount, total, paid_total, due_total, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            next_invoice_number($db, $locationId),
            $locationId,
            (int) $user['id'],
            $customerId,
            $subtotal,
            $discountType,
            $discountValue,
            $discountAmount,
            $total,
            $paid,
            $due,
            $data['notes'] ?: null,
        ]);
        $invoiceId = (int) $db->lastInsertId();

        foreach ($lines as $line) {
            if ($line['kind'] === 'product') {
                $stmt = $db->prepare('INSERT INTO invoice_lines (invoice_id, line_type, product_id, description, quantity, unit_price, discount_type, discount_value, discount_amount, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$invoiceId, 'product', $line['product']['id'], $line['product']['name'], $line['quantity'], $line['unit_price'], $line['discount_type'], $line['discount_value'], $line['discount_amount'], $line['line_total']]);
                $lineId = (int) $db->lastInsertId();
                move_inventory($db, $locationId, (int) $line['product']['id'], -1 * (float) $line['quantity'], 'sale', (int) $user['id'], 'invoice', $invoiceId, 'بيع فاتورة');
                $stmt = $db->prepare('INSERT INTO invoice_line_components (invoice_line_id, component_product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)');
                $stmt->execute([$lineId, $line['product']['id'], $line['quantity'], $line['product']['cost_price']]);
                continue;
            }

            $lineType = $line['kind'] === 'saved_recipe' ? 'saved_recipe' : 'custom_recipe';
            $stmt = $db->prepare('INSERT INTO invoice_lines (invoice_id, line_type, recipe_id, description, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$invoiceId, $lineType, $line['recipe_id'] ?? null, $line['description'], 1, $line['unit_price'], $line['line_total']]);
            $lineId = (int) $db->lastInsertId();
            foreach ($line['components'] as $component) {
                move_inventory($db, $locationId, (int) $component['product']['id'], -1 * (float) $component['quantity'], 'sale', (int) $user['id'], 'invoice', $invoiceId, 'مكون تركيبة فورية');
                $stmt = $db->prepare('INSERT INTO invoice_line_components (invoice_line_id, component_product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)');
                $stmt->execute([$lineId, $component['product']['id'], $component['quantity'], $component['product']['cost_price']]);
            }
        }

        if ($paid > 0) {
            $paymentMethod = $data['payment_method'] ?? 'cash';
            if ($paymentMethod === 'mixed_cash_instapay' || $paymentMethod === 'mixed_cash_vodafone') {
                $paidCash = (float) ($data['paid_cash'] ?? 0);
                
                if ($paymentMethod === 'mixed_cash_instapay') {
                    $secondaryMethod = 'instapay';
                    $paidSecondary = (float) ($data['paid_instapay'] ?? 0);
                } else {
                    $secondaryMethod = 'vodafone_cash';
                    $paidSecondary = (float) ($data['paid_vodafone_cash'] ?? 0);
                }
                
                $actualSecondary = min($paidSecondary, $total);
                $actualCash = max(0.0, $paid - $actualSecondary);
                
                if ($actualCash > 0) {
                    $stmt = $db->prepare('INSERT INTO payments (invoice_id, customer_id, payment_type, method, amount, created_by) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$invoiceId, $customerId, 'invoice_payment', 'cash', $actualCash, (int) $user['id']]);
                }
                if ($actualSecondary > 0) {
                    $stmt = $db->prepare('INSERT INTO payments (invoice_id, customer_id, payment_type, method, amount, created_by) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$invoiceId, $customerId, 'invoice_payment', $secondaryMethod, $actualSecondary, (int) $user['id']]);
                }
            } else {
                $stmt = $db->prepare('INSERT INTO payments (invoice_id, customer_id, payment_type, method, amount, created_by) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$invoiceId, $customerId, 'invoice_payment', $paymentMethod, $paid, (int) $user['id']]);
            }
        }
        if ($due > 0) {
            $stmt = $db->prepare('INSERT INTO customer_debts (customer_id, invoice_id, original_amount, paid_amount, remaining_amount) VALUES (?, ?, ?, 0, ?)');
            $stmt->execute([$customerId, $invoiceId, $due, $due]);
        }

        // Loyalty points system removed.

        log_audit((int)$user['id'], 'create', 'invoice', $invoiceId, 'إنشاء فاتورة مبيعات جديدة رقم #' . $invoiceId);
        $db->commit();
        return $invoiceId;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function create_transfer(array $data, int $userId): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $from = (int) $data['from_location_id'];
        $to = (int) $data['to_location_id'];
        $lineItems = transfer_line_items($data);
        require_location_access($from);
        require_location_type($from, ['warehouse', 'branch'], 'الأونلاين ليس مخزناً ولا يمكن التحويل منه.');
        require_location_type($to, ['warehouse', 'branch'], 'الأونلاين ليس مخزناً ولا يمكن التحويل إليه.');
        if ($from === $to) {
            throw new RuntimeException('لا يمكن التحويل لنفس الموقع.');
        }
        assert_transfer_stock_available($db, $from, $lineItems);
        $number = 'TR-' . date('Ymd-His') . '-' . random_int(100, 999);
        $transferDate = $data['transfer_date'] ? date('Y-m-d', strtotime($data['transfer_date'])) : date('Y-m-d');
        $senderName = trim((string) ($data['sender_name'] ?? '')) ?: null;
        $receiverName = trim((string) ($data['receiver_name'] ?? '')) ?: null;
        $stmt = $db->prepare('INSERT INTO inventory_transfers (transfer_number, from_location_id, to_location_id, notes, sender_name, receiver_name, transfer_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$number, $from, $to, $data['notes'] ?: null, $senderName, $receiverName, $transferDate, $userId]);
        $transferId = (int) $db->lastInsertId();
        foreach ($lineItems as $item) {
            move_inventory($db, $from, $item['product_id'], -1 * $item['quantity'], 'transfer_future', $userId, 'transfer', $transferId, 'خروج تحويل مخزون');
            $stmt = $db->prepare('INSERT INTO inventory_transfer_items (transfer_id, product_id, quantity) VALUES (?, ?, ?)');
            $stmt->execute([$transferId, $item['product_id'], $item['quantity']]);
        }
        log_audit($userId, 'create', 'transfer', $transferId, 'إنشاء أمر تحويل مخزني رقم ' . $number);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function update_transfer(array $data, int $userId, ?array $fromTypes = null, ?array $toTypes = null): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $transferId = (int) $data['transfer_id'];
        $stmt = $db->prepare('SELECT * FROM inventory_transfers WHERE id = ? FOR UPDATE');
        $stmt->execute([$transferId]);
        $transfer = $stmt->fetch();
        if (!$transfer || $transfer['status'] !== 'pending') {
            throw new RuntimeException('لا يمكن تعديل هذا التحويل أو أنه غير متاح.');
        }
        if ($fromTypes !== null && $toTypes !== null) {
            require_transfer_location_types($db, $transfer, $fromTypes, $toTypes);
        }

        $from = (int) $data['from_location_id'];
        $to = (int) $data['to_location_id'];
        $lineItems = transfer_line_items($data);
        require_location_access($from);
        require_location_type($from, ['warehouse', 'branch'], 'الأونلاين ليس مخزناً ولا يمكن التحويل منه.');
        require_location_type($to, ['warehouse', 'branch'], 'الأونلاين ليس مخزناً ولا يمكن التحويل إليه.');
        if ($from === $to) {
            throw new RuntimeException('لا يمكن التحويل لنفس الموقع.');
        }
        if (!$lineItems) {
            throw new RuntimeException('يجب إضافة صنف واحد على الأقل في التحويل.');
        }

        $stmt = $db->prepare('SELECT * FROM inventory_transfer_items WHERE transfer_id = ?');
        $stmt->execute([$transferId]);
        $oldItems = $stmt->fetchAll();
        foreach ($oldItems as $item) {
            move_inventory($db, (int)$transfer['from_location_id'], (int)$item['product_id'], (float)$item['quantity'], 'transfer_adjust', $userId, 'transfer', $transferId, 'تعديل تحويل مخزني - استرجاع كمية للمصدر');
        }

        $stmt = $db->prepare('DELETE FROM inventory_transfer_items WHERE transfer_id = ?');
        $stmt->execute([$transferId]);

        assert_transfer_stock_available($db, $from, $lineItems);

        foreach ($lineItems as $item) {
            move_inventory($db, $from, $item['product_id'], -1 * $item['quantity'], 'transfer_future', $userId, 'transfer', $transferId, 'خروج تحويل مخزون معدّل');
            $stmt = $db->prepare('INSERT INTO inventory_transfer_items (transfer_id, product_id, quantity) VALUES (?, ?, ?)');
            $stmt->execute([$transferId, $item['product_id'], $item['quantity']]);
        }

        $transferDate = $data['transfer_date'] ? date('Y-m-d', strtotime($data['transfer_date'])) : date('Y-m-d');
        $senderName = trim((string) ($data['sender_name'] ?? '')) ?: null;
        $receiverName = trim((string) ($data['receiver_name'] ?? '')) ?: null;
        $stmt = $db->prepare('UPDATE inventory_transfers SET from_location_id = ?, to_location_id = ?, notes = ?, sender_name = ?, receiver_name = ?, transfer_date = ? WHERE id = ?');
        $stmt->execute([$from, $to, $data['notes'] ?: null, $senderName, $receiverName, $transferDate, $transferId]);

        log_audit($userId, 'update', 'transfer', $transferId, 'تعديل أمر تحويل مخزني رقم ' . $transfer['transfer_number']);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function cancel_transfer(int $transferId, int $userId, ?array $fromTypes = null, ?array $toTypes = null): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT * FROM inventory_transfers WHERE id = ? FOR UPDATE');
        $stmt->execute([$transferId]);
        $transfer = $stmt->fetch();
        if (!$transfer || $transfer['status'] !== 'pending') {
            throw new RuntimeException('لا يمكن إلغاء هذا التحويل أو أنه غير متاح.');
        }
        if ($fromTypes !== null && $toTypes !== null) {
            require_transfer_location_types($db, $transfer, $fromTypes, $toTypes);
        }

        $stmt = $db->prepare('SELECT * FROM inventory_transfer_items WHERE transfer_id = ?');
        $stmt->execute([$transferId]);
        foreach ($stmt->fetchAll() as $item) {
            move_inventory($db, (int)$transfer['from_location_id'], (int)$item['product_id'], (float)$item['quantity'], 'transfer_adjust', $userId, 'transfer', $transferId, 'إلغاء تحويل مخزني - استرجاع كمية للمصدر');
        }

        $stmt = $db->prepare('UPDATE inventory_transfers SET status = "cancelled" WHERE id = ?');
        $stmt->execute([$transferId]);

        $stmt = $db->prepare('SELECT transfer_number FROM inventory_transfers WHERE id = ?');
        $stmt->execute([$transferId]);
        $transNum = $stmt->fetchColumn();
        log_audit($userId, 'cancel', 'transfer', $transferId, 'إلغاء أمر تحويل مخزني رقم ' . ($transNum ?: ('#' . $transferId)));
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function receive_transfer(int $transferId, int $userId, ?array $fromTypes = null, ?array $toTypes = null): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT * FROM inventory_transfers WHERE id = ? FOR UPDATE');
        $stmt->execute([$transferId]);
        $transfer = $stmt->fetch();
        if (!$transfer || $transfer['status'] !== 'pending') {
            throw new RuntimeException('أمر التحويل غير متاح للاستلام.');
        }
        if ($fromTypes !== null && $toTypes !== null) {
            require_transfer_location_types($db, $transfer, $fromTypes, $toTypes);
        }
        require_location_access((int) $transfer['to_location_id']);
        $stmt = $db->prepare('SELECT * FROM inventory_transfer_items WHERE transfer_id = ?');
        $stmt->execute([$transferId]);
        foreach ($stmt->fetchAll() as $item) {
            move_inventory($db, (int) $transfer['to_location_id'], (int) $item['product_id'], (float) $item['quantity'], 'transfer_future', $userId, 'transfer', $transferId, 'استلام تحويل مخزون');
        }
        $stmt = $db->prepare('UPDATE inventory_transfers SET status = "received", received_by = ?, received_at = NOW() WHERE id = ?');
        $stmt->execute([$userId, $transferId]);
        
        $stmtSelect = $db->prepare('SELECT transfer_number FROM inventory_transfers WHERE id = ?');
        $stmtSelect->execute([$transferId]);
        $transNum = $stmtSelect->fetchColumn();
        log_audit($userId, 'receive', 'transfer', $transferId, 'استلام شحنة تحويل رقم ' . ($transNum ?: ('#' . $transferId)));
        
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function create_supply_transfer(array $data, int $userId): void
{
    validate_supply_transfer($data);
    if (!transfer_line_items($data)) {
        throw new RuntimeException('يجب إضافة صنف واحد على الأقل في التحويل.');
    }
    create_transfer($data, $userId);
}

function create_branch_transfer(array $data, int $userId): void
{
    validate_branch_transfer($data);
    if (!transfer_line_items($data)) {
        throw new RuntimeException('يجب إضافة صنف واحد على الأقل في التحويل.');
    }
    create_transfer($data, $userId);
}

function update_supply_transfer(array $data, int $userId): void
{
    validate_supply_transfer($data);
    update_transfer($data, $userId, ['warehouse'], ['branch']);
}

function update_branch_transfer(array $data, int $userId): void
{
    validate_branch_transfer($data);
    update_transfer($data, $userId, ['branch'], ['branch']);
}

function transfers_rows(?int $locationId = null): array
{
    $sql = 'SELECT t.*, f.name AS from_name, tl.name AS to_name, u.name AS created_name, r.name AS received_name 
            FROM inventory_transfers t 
            JOIN locations f ON f.id = t.from_location_id 
            JOIN locations tl ON tl.id = t.to_location_id 
            JOIN users u ON u.id = t.created_by 
            LEFT JOIN users r ON r.id = t.received_by';
    $params = [];
    if ($locationId !== null) {
        $sql .= ' WHERE t.from_location_id = ? OR t.to_location_id = ?';
        $params[] = $locationId;
        $params[] = $locationId;
    }
    $sql .= ' ORDER BY t.created_at DESC';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function supply_transfers_rows(?int $locationId = null, array $filters = []): array
{
    $sql = "
        SELECT t.*, 
               fl.name AS from_name, 
               tl.name AS to_name,
               uc.name AS created_name,
               ur.name AS received_name
        FROM inventory_transfers t
        JOIN locations fl ON fl.id = t.from_location_id
        JOIN locations tl ON tl.id = t.to_location_id
        LEFT JOIN users uc ON uc.id = t.created_by
        LEFT JOIN users ur ON ur.id = t.received_by
        WHERE fl.type = 'warehouse' AND tl.type = 'branch'
    ";
    $params = [];
    if ($locationId !== null) {
        $sql .= ' AND (t.from_location_id = ? OR t.to_location_id = ?)';
        $params[] = $locationId;
        $params[] = $locationId;
    }
    if (!empty($filters['status'])) {
        $sql .= ' AND t.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['from_location_id'])) {
        $sql .= ' AND t.from_location_id = ?';
        $params[] = $filters['from_location_id'];
    }
    if (!empty($filters['to_location_id'])) {
        $sql .= ' AND t.to_location_id = ?';
        $params[] = $filters['to_location_id'];
    }
    if (!empty($filters['q'])) {
        $q = '%' . trim($filters['q']) . '%';
        $sql .= ' AND (t.transfer_number LIKE ? OR fl.name LIKE ? OR tl.name LIKE ? OR t.sender_name LIKE ? OR t.receiver_name LIKE ?)';
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
    }
    if (!empty($filters['date_from'])) {
        $sql .= ' AND t.transfer_date >= ?';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $sql .= ' AND t.transfer_date <= ?';
        $params[] = $filters['date_to'];
    }
    $sql .= ' ORDER BY t.created_at DESC';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function branch_transfers_rows(?int $locationId = null, array $filters = []): array
{
    $sql = "
        SELECT t.*, 
               fl.name AS from_name, 
               tl.name AS to_name,
               uc.name AS created_name,
               ur.name AS received_name
        FROM inventory_transfers t
        JOIN locations fl ON fl.id = t.from_location_id
        JOIN locations tl ON tl.id = t.to_location_id
        LEFT JOIN users uc ON uc.id = t.created_by
        LEFT JOIN users ur ON ur.id = t.received_by
        WHERE fl.type = 'branch' AND tl.type = 'branch'
    ";
    $params = [];
    if ($locationId !== null) {
        $sql .= ' AND (t.from_location_id = ? OR t.to_location_id = ?)';
        $params[] = $locationId;
        $params[] = $locationId;
    }
    if (!empty($filters['status'])) {
        $sql .= ' AND t.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['from_location_id'])) {
        $sql .= ' AND t.from_location_id = ?';
        $params[] = $filters['from_location_id'];
    }
    if (!empty($filters['to_location_id'])) {
        $sql .= ' AND t.to_location_id = ?';
        $params[] = $filters['to_location_id'];
    }
    if (!empty($filters['q'])) {
        $q = '%' . trim($filters['q']) . '%';
        $sql .= ' AND (t.transfer_number LIKE ? OR fl.name LIKE ? OR tl.name LIKE ? OR t.sender_name LIKE ? OR t.receiver_name LIKE ?)';
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
    }
    if (!empty($filters['date_from'])) {
        $sql .= ' AND t.transfer_date >= ?';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $sql .= ' AND t.transfer_date <= ?';
        $params[] = $filters['date_to'];
    }
    $sql .= ' ORDER BY t.created_at DESC';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_transfer(int $transferId): ?array
{
    $stmt = pdo()->prepare('SELECT t.*, f.name AS from_name, tl.name AS to_name, u.name AS created_name, r.name AS received_name 
                            FROM inventory_transfers t 
                            JOIN locations f ON f.id = t.from_location_id 
                            JOIN locations tl ON tl.id = t.to_location_id 
                            JOIN users u ON u.id = t.created_by 
                            LEFT JOIN users r ON r.id = t.received_by 
                            WHERE t.id = ?');
    $stmt->execute([$transferId]);
    $result = $stmt->fetch();
    return $result ?: null;
}

function get_transfer_items(int $transferId): array
{
    $stmt = pdo()->prepare('SELECT iti.*, p.name AS product_name, p.unit AS product_unit
                            FROM inventory_transfer_items iti
                            JOIN products p ON p.id = iti.product_id
                            WHERE iti.transfer_id = ?');
    $stmt->execute([$transferId]);
    return $stmt->fetchAll();
}

function create_return_invoice(int $invoiceId, string $method, string $reason, int $userId): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT * FROM invoices WHERE id = ? FOR UPDATE');
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            throw new RuntimeException('الفاتورة غير موجودة.');
        }
        $stmt = $db->prepare('SELECT COUNT(*) FROM return_invoices WHERE original_invoice_id = ?');
        $stmt->execute([$invoiceId]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('تم عمل مرتجع لهذه الفاتورة من قبل.');
        }
        $number = 'RT-' . date('Ymd-His') . '-' . $invoiceId;
        $stmt = $db->prepare('INSERT INTO return_invoices (original_invoice_id, return_number, refund_method, amount, reason, created_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$invoiceId, $number, $method, $invoice['total'], $reason ?: null, $userId]);
        $returnId = (int) $db->lastInsertId();
        $stmt = $db->prepare('SELECT * FROM invoice_line_components WHERE invoice_line_id IN (SELECT id FROM invoice_lines WHERE invoice_id = ?)');
        $stmt->execute([$invoiceId]);
        foreach ($stmt->fetchAll() as $component) {
            move_inventory($db, (int) $invoice['location_id'], (int) $component['component_product_id'], (float) $component['quantity'], 'return_future', $userId, 'return', $returnId, 'مرتجع فاتورة');
        }
        $stmt = $db->prepare('UPDATE invoices SET status = "void_future" WHERE id = ?');
        $stmt->execute([$invoiceId]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function recent_returnable_lines(): array
{
    return pdo()->query("SELECT il.id AS line_id, il.description, il.line_total, i.invoice_number, i.created_at, c.name AS customer_name FROM invoice_lines il JOIN invoices i ON i.id = il.invoice_id LEFT JOIN customers c ON c.id = i.customer_id WHERE i.status = 'completed' ORDER BY i.created_at DESC, il.id DESC LIMIT 150")->fetchAll();
}

function create_return_line_invoice(int $lineId, string $method, string $reason, int $userId): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT il.*, i.location_id, i.invoice_number FROM invoice_lines il JOIN invoices i ON i.id = il.invoice_id WHERE il.id = ? FOR UPDATE');
        $stmt->execute([$lineId]);
        $line = $stmt->fetch();
        if (!$line) {
            throw new RuntimeException('بند الفاتورة غير موجود.');
        }
        $number = 'RT-L-' . date('Ymd-His') . '-' . $lineId;
        $stmt = $db->prepare('INSERT INTO return_invoices (original_invoice_id, return_number, refund_method, amount, reason, created_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$line['invoice_id'], $number, $method, $line['line_total'], $reason ?: ('مرتجع بند: ' . $line['description']), $userId]);
        $returnId = (int) $db->lastInsertId();

        $stmt = $db->prepare('SELECT * FROM invoice_line_components WHERE invoice_line_id = ?');
        $stmt->execute([$lineId]);
        foreach ($stmt->fetchAll() as $component) {
            move_inventory($db, (int) $line['location_id'], (int) $component['component_product_id'], (float) $component['quantity'], 'return_future', $userId, 'return', $returnId, 'مرتجع بند فاتورة');
        }
        log_audit($userId, 'return_line', 'invoice_line', $lineId, 'مرتجع بند من فاتورة ' . $line['invoice_number']);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function returns_rows(): array
{
    return pdo()->query('SELECT r.*, i.invoice_number, u.name AS user_name FROM return_invoices r JOIN invoices i ON i.id = r.original_invoice_id JOIN users u ON u.id = r.created_by ORDER BY r.created_at DESC')->fetchAll();
}

function expected_cash_for(int $userId, int $locationId): float
{
    $stmt = pdo()->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN invoices i ON i.id = p.invoice_id WHERE p.created_by = ? AND i.location_id = ? AND p.method = 'cash' AND DATE(p.created_at) = CURDATE()");
    $stmt->execute([$userId, $locationId]);
    return (float) $stmt->fetchColumn();
}

function close_shift(int $locationId, float $actualCash, string $notes, array $user): void
{
    require_location_access($locationId);
    require_location_type($locationId, ['branch'], 'إغلاق الشيفت مسموح للفروع فقط.');
    $expected = expected_cash_for((int) $user['id'], $locationId);
    $stmt = pdo()->prepare('INSERT INTO shift_closures (user_id, location_id, shift_date, expected_cash, actual_cash, difference, notes) VALUES (?, ?, CURDATE(), ?, ?, ?, ?) ON DUPLICATE KEY UPDATE expected_cash = VALUES(expected_cash), actual_cash = VALUES(actual_cash), difference = VALUES(difference), notes = VALUES(notes)');
    $stmt->execute([(int) $user['id'], $locationId, $expected, $actualCash, $actualCash - $expected, $notes ?: null]);
}

function find_shift_closure(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT s.*, u.name AS user_name, l.name AS location_name FROM shift_closures s JOIN users u ON u.id = s.user_id JOIN locations l ON l.id = s.location_id WHERE s.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function update_shift_closure(int $id, float $actualCash, string $notes): void
{
    $shift = find_shift_closure($id);
    if (!$shift) {
        throw new RuntimeException('الشيفت غير موجود.');
    }
    require_location_access((int) $shift['location_id']);
    $difference = $actualCash - (float) $shift['expected_cash'];
    $stmt = pdo()->prepare('UPDATE shift_closures SET actual_cash = ?, difference = ?, notes = ? WHERE id = ?');
    $stmt->execute([$actualCash, $difference, $notes ?: null, $id]);
}

function delete_shift_closure(int $id): void
{
    $shift = find_shift_closure($id);
    if (!$shift) {
        throw new RuntimeException('الشيفت غير موجود.');
    }
    require_location_access((int) $shift['location_id']);
    $stmt = pdo()->prepare('DELETE FROM shift_closures WHERE id = ?');
    $stmt->execute([$id]);
}

function shift_rows(?int $locationId = null): array
{
    $sql = 'SELECT s.*, u.name AS user_name, l.name AS location_name FROM shift_closures s JOIN users u ON u.id = s.user_id JOIN locations l ON l.id = s.location_id';
    $params = [];
    if ($locationId !== null) {
        $sql .= ' WHERE s.location_id = ?';
        $params[] = $locationId;
    }
    $sql .= ' ORDER BY s.shift_date DESC, s.id DESC';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function add_attendance(array $data, int $createdBy): void
{
    $user = find_user((int) $data['user_id']);
    if ($user && current_user_location_id() !== null && (int) $user['id'] !== (int) current_user()['id']) {
        throw new RuntimeException('غير مسموح لك بتسجيل حضور لموظف خارج صلاحياتك.');
    }
    $locationId = (int) $data['location_id'];
    require_location_access($locationId);
    require_location_type($locationId, ['warehouse', 'branch'], 'الأونلاين لا يتم تسجيل حضور أو انصراف له.');
    $stmt = pdo()->prepare('INSERT INTO attendance_records (user_id, location_id, action, latitude, longitude, source, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([(int) $data['user_id'], $locationId, $data['action'], $data['latitude'] !== '' ? (float) $data['latitude'] : null, $data['longitude'] !== '' ? (float) $data['longitude'] : null, $data['source'] ?: 'manual', $data['notes'] ?: null, $createdBy]);
}

function attendance_rows(?int $userId = null): array
{
    $sql = "SELECT a.*, u.name AS user_name, l.name AS location_name,
        (SELECT MAX(created_at) 
         FROM attendance_records 
         WHERE user_id = a.user_id 
           AND action = 'check_in' 
           AND created_at < a.created_at
           AND created_at >= DATE_SUB(a.created_at, INTERVAL 24 HOUR)
        ) AS matching_check_in
        FROM attendance_records a 
        JOIN users u ON u.id = a.user_id 
        JOIN locations l ON l.id = a.location_id";
    
    $params = [];
    if ($userId !== null) {
        $sql .= " WHERE a.user_id = ?";
        $params[] = $userId;
    }
    
    $sql .= " ORDER BY a.created_at DESC LIMIT 100";
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function upsert_target(int $locationId, string $date, float $amount, int $userId): void
{
    require_location_access($locationId);
    require_location_type($locationId, ['branch'], 'التارجت يتم تسجيله للفروع فقط.');
    $stmt = pdo()->prepare('INSERT INTO branch_targets (location_id, target_date, target_amount, created_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE target_amount = VALUES(target_amount), created_by = VALUES(created_by)');
    $stmt->execute([$locationId, $date, $amount, $userId]);
}

function find_target(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT bt.*, l.name AS location_name FROM branch_targets bt JOIN locations l ON l.id = bt.location_id WHERE bt.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function update_target(int $id, int $locationId, string $date, float $amount, int $userId): void
{
    $target = find_target($id);
    if (!$target) {
        throw new RuntimeException('التارجت غير موجود.');
    }
    require_location_access((int) $target['location_id']);
    require_location_access($locationId);
    require_location_type($locationId, ['branch'], 'التارجت يتم تسجيله للفروع فقط.');
    $stmt = pdo()->prepare('UPDATE branch_targets SET location_id = ?, target_date = ?, target_amount = ?, created_by = ? WHERE id = ?');
    try {
        $stmt->execute([$locationId, $date, $amount, $userId, $id]);
    } catch (Throwable $e) {
        throw new RuntimeException('يوجد تارجت مسجل بالفعل لهذا الفرع في نفس اليوم.');
    }
}

function delete_target(int $id): void
{
    $target = find_target($id);
    if (!$target) {
        throw new RuntimeException('التارجت غير موجود.');
    }
    require_location_access((int) $target['location_id']);
    $stmt = pdo()->prepare('DELETE FROM branch_targets WHERE id = ?');
    $stmt->execute([$id]);
}

function target_rows(?int $locationId = null, ?string $date = null): array
{
    $sql = "SELECT bt.*, l.name AS location_name, COALESCE((SELECT SUM(total) FROM invoices i WHERE i.location_id = bt.location_id AND DATE(i.created_at) = bt.target_date), 0) AS achieved FROM branch_targets bt JOIN locations l ON l.id = bt.location_id";
    $params = [];
    $conditions = [];
    if ($locationId !== null) {
        $conditions[] = 'bt.location_id = ?';
        $params[] = $locationId;
    }
    if ($date !== null) {
        $conditions[] = 'bt.target_date = ?';
        $params[] = $date;
    }
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY bt.target_date DESC, l.id';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function auto_create_daily_targets(int $userId): void
{
    $db = pdo();
    $today = date('Y-m-d');
    
    // Get all active branches
    $branches = pdo()->query("SELECT id FROM locations WHERE type = 'branch' AND is_active = 1")->fetchAll();
    
    foreach ($branches as $branch) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM branch_targets WHERE location_id = ? AND target_date = ?');
        $stmt->execute([(int) $branch['id'], $today]);
        $exists = (int) $stmt->fetchColumn() > 0;
        
        if (!$exists) {
            // Create target with 0 amount (will be updated by manager)
            $stmt = $db->prepare('INSERT INTO branch_targets (location_id, target_date, target_amount, created_by) VALUES (?, ?, 0, ?)');
            $stmt->execute([(int) $branch['id'], $today, $userId]);
        }
    }
}

function expense_categories(): array
{
    return pdo()->query('SELECT * FROM expense_categories ORDER BY name')->fetchAll();
}

function add_expense(array $data, int $userId): void
{
    $locationId = $data['location_id'] !== '' ? (int) $data['location_id'] : null;
    if ($locationId !== null) {
        require_location_access($locationId);
    }
    $stmt = pdo()->prepare('INSERT INTO expenses (category_id, location_id, amount, expense_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([(int) $data['category_id'], $locationId, (float) $data['amount'], $data['expense_date'], $data['notes'] ?: null, $userId]);
}

function expense_rows(?int $locationId = null): array
{
    $sql = 'SELECT e.*, c.name AS category_name, l.name AS location_name, u.name AS user_name 
            FROM expenses e 
            JOIN expense_categories c ON c.id = e.category_id 
            LEFT JOIN locations l ON l.id = e.location_id 
            JOIN users u ON u.id = e.created_by';
    $params = [];
    if ($locationId !== null) {
        $sql .= ' WHERE e.location_id = ?';
        $params[] = $locationId;
    }
    $sql .= ' ORDER BY e.expense_date DESC, e.id DESC';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function add_supplier(array $data, int $userId): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO suppliers (name, phone, product_type, notes) VALUES (?, ?, ?, ?)');
        $stmt->execute([$data['name'], $data['phone'] ?: null, $data['product_type'] ?: null, $data['notes'] ?: null]);
        $supplierId = (int) $db->lastInsertId();
        if ((float) ($data['invoice_total'] ?? 0) > 0) {
            $total = (float) $data['invoice_total'];
            $paid = (float) ($data['invoice_paid'] ?? 0);
            $stmt = $db->prepare('INSERT INTO supplier_invoices (supplier_id, invoice_number, total, paid, due, invoice_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$supplierId, $data['invoice_number'] ?: null, $total, $paid, max(0, $total - $paid), $data['invoice_date'] ?: date('Y-m-d'), $data['notes'] ?: null, $userId]);
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function supplier_rows(): array
{
    return pdo()->query('SELECT s.*, COALESCE(SUM(si.total), 0) AS total_invoices, COALESCE(SUM(si.due), 0) AS total_due FROM suppliers s LEFT JOIN supplier_invoices si ON si.supplier_id = s.id GROUP BY s.id ORDER BY s.created_at DESC')->fetchAll();
}

function report_stats(array $filters = []): array
{
    $db = pdo();
    $startDate = !empty($filters['start_date']) ? $filters['start_date'] . ' 00:00:00' : null;
    $endDate = !empty($filters['end_date']) ? $filters['end_date'] . ' 23:59:59' : null;
    $locationId = !empty($filters['location_id']) ? (int) $filters['location_id'] : null;

    // 1. Sales by Location
    $sqlLocation = "SELECT l.name, COALESCE(SUM(i.total), 0) AS total 
                    FROM locations l 
                    LEFT JOIN invoices i ON i.location_id = l.id";
    $locParams = [];
    $locJoinWhere = [];
    if ($startDate) {
        $locJoinWhere[] = "i.created_at >= ?";
        $locParams[] = $startDate;
    }
    if ($endDate) {
        $locJoinWhere[] = "i.created_at <= ?";
        $locParams[] = $endDate;
    }
    if ($locationId) {
        $locJoinWhere[] = "i.location_id = ?";
        $locParams[] = $locationId;
    }
    if ($locJoinWhere) {
        $sqlLocation .= " AND " . implode(" AND ", $locJoinWhere);
    }
    if ($locationId) {
        $sqlLocation .= " WHERE l.id = ?";
        $locParams[] = $locationId;
    }
    $sqlLocation .= " GROUP BY l.id ORDER BY l.id";
    $stmtLoc = $db->prepare($sqlLocation);
    $stmtLoc->execute($locParams);
    $salesByLocation = $stmtLoc->fetchAll();

    // 2. Sales by Payment
    $sqlPayment = "SELECT p.method, COALESCE(SUM(p.amount), 0) AS total 
                   FROM payments p 
                   LEFT JOIN invoices i ON i.id = p.invoice_id";
    $payWhere = " WHERE 1=1";
    $payParams = [];
    if ($startDate) {
        $payWhere .= " AND p.created_at >= ?";
        $payParams[] = $startDate;
    }
    if ($endDate) {
        $payWhere .= " AND p.created_at <= ?";
        $payParams[] = $endDate;
    }
    if ($locationId) {
        $payWhere .= " AND i.location_id = ?";
        $payParams[] = $locationId;
    }
    $sqlPayment .= $payWhere . " GROUP BY p.method";
    $stmtPay = $db->prepare($sqlPayment);
    $stmtPay->execute($payParams);
    $salesByPayment = $stmtPay->fetchAll();

    // 3. Sales by User
    $sqlUser = "SELECT u.name, COALESCE(SUM(i.total), 0) AS total, COUNT(i.id) AS invoices_count 
                FROM users u 
                LEFT JOIN invoices i ON i.user_id = u.id";
    $userParams = [];
    $userJoinWhere = [];
    if ($startDate) {
        $userJoinWhere[] = "i.created_at >= ?";
        $userParams[] = $startDate;
    }
    if ($endDate) {
        $userJoinWhere[] = "i.created_at <= ?";
        $userParams[] = $endDate;
    }
    if ($locationId) {
        $userJoinWhere[] = "i.location_id = ?";
        $userParams[] = $locationId;
    }
    if ($userJoinWhere) {
        $sqlUser .= " AND " . implode(" AND ", $userJoinWhere);
    }
    if ($locationId) {
        $sqlUser .= " WHERE u.location_id = ?";
        $userParams[] = $locationId;
    }
    $sqlUser .= " GROUP BY u.id ORDER BY total DESC";
    $stmtUser = $db->prepare($sqlUser);
    $stmtUser->execute($userParams);
    $salesByUser = $stmtUser->fetchAll();

    // 4. Top Products
    $sqlTop = "SELECT l.description, SUM(l.quantity) AS qty_sold, SUM(l.line_total) AS total 
               FROM invoice_lines l 
               JOIN invoices i ON i.id = l.invoice_id";
    $topWhere = " WHERE 1=1";
    $topParams = [];
    if ($startDate) {
        $topWhere .= " AND i.created_at >= ?";
        $topParams[] = $startDate;
    }
    if ($endDate) {
        $topWhere .= " AND i.created_at <= ?";
        $topParams[] = $endDate;
    }
    if ($locationId) {
        $topWhere .= " AND i.location_id = ?";
        $topParams[] = $locationId;
    }
    $sqlTop .= $topWhere . " GROUP BY l.description ORDER BY total DESC LIMIT 5";
    $stmtTop = $db->prepare($sqlTop);
    $stmtTop->execute($topParams);
    $topProducts = $stmtTop->fetchAll();

    // 5. Perfume Usage
    $sqlUsage = "SELECT p.name, SUM(c.quantity) AS grams 
                 FROM invoice_line_components c 
                 JOIN products p ON p.id = c.component_product_id 
                 JOIN invoice_lines l ON l.id = c.invoice_line_id 
                 JOIN invoices i ON i.id = l.invoice_id";
    $useWhere = " WHERE p.type = 'perfume_gram'";
    $useParams = [];
    if ($startDate) {
        $useWhere .= " AND i.created_at >= ?";
        $useParams[] = $startDate;
    }
    if ($endDate) {
        $useWhere .= " AND i.created_at <= ?";
        $useParams[] = $endDate;
    }
    if ($locationId) {
        $useWhere .= " AND i.location_id = ?";
        $useParams[] = $locationId;
    }
    $sqlUsage .= $useWhere . " GROUP BY p.id ORDER BY grams DESC";
    $stmtUsage = $db->prepare($sqlUsage);
    $stmtUsage->execute($useParams);
    $perfumeUsage = $stmtUsage->fetchAll();

    // 6. New Customers
    $sqlCust = "SELECT name AS 'اسم العميل', phone AS 'الهاتف', source AS 'المصدر', DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS 'تاريخ التسجيل' 
                FROM customers";
    $custWhere = " WHERE is_active = 1";
    $custParams = [];
    if ($startDate) {
        $custWhere .= " AND created_at >= ?";
        $custParams[] = $startDate;
    }
    if ($endDate) {
        $custWhere .= " AND created_at <= ?";
        $custParams[] = $endDate;
    }
    $sqlCust .= $custWhere . " ORDER BY created_at DESC LIMIT 10";
    $stmtCust = $db->prepare($sqlCust);
    $stmtCust->execute($custParams);
    $newCustomers = $stmtCust->fetchAll();

    return [
        'sales_by_location' => $salesByLocation,
        'sales_by_payment' => $salesByPayment,
        'sales_by_user' => $salesByUser,
        'top_products' => $topProducts,
        'perfume_usage' => $perfumeUsage,
        'new_customers' => $newCustomers,
    ];
}

function report_rows(string $key, array $filters = []): array
{
    $reports = report_stats($filters);
    return $reports[$key] ?? [];
}

function output_csv(string $filename, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    if ($rows) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit;
}

function recent_invoices(int $limit = 50, ?int $locationId = null): array
{
    $sql = 'SELECT i.*, l.name AS location_name, u.name AS user_name, c.name AS customer_name 
            FROM invoices i 
            JOIN locations l ON l.id = i.location_id 
            JOIN users u ON u.id = i.user_id 
            LEFT JOIN customers c ON c.id = i.customer_id';
    $params = [];
    if ($locationId !== null) {
        $sql .= ' WHERE i.location_id = ?';
        $params[] = $locationId;
    }
    $sql .= ' ORDER BY i.created_at DESC, i.id DESC LIMIT ' . (int) $limit;
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function search_invoices(array $filters): array
{
    $db = pdo();
    $sql = 'SELECT i.*, l.name AS location_name, u.name AS user_name, c.name AS customer_name 
            FROM invoices i 
            JOIN locations l ON l.id = i.location_id 
            JOIN users u ON u.id = i.user_id 
            LEFT JOIN customers c ON c.id = i.customer_id ';

    // payment_method requires a subquery join on payments table
    if (!empty($filters['payment_method'])) {
        $sql .= ' INNER JOIN payments pm ON pm.invoice_id = i.id AND pm.method = ? ';
    }

    $sql .= ' WHERE 1=1';
    $params = [];

    // inject payment_method param early (it was added to FROM clause)
    if (!empty($filters['payment_method'])) {
        array_unshift($params, $filters['payment_method']);
    }

    if (!empty($filters['location_id'])) {
        $sql .= ' AND i.location_id = ?';
        $params[] = (int) $filters['location_id'];
    }

    if (!empty($filters['user_id'])) {
        $sql .= ' AND i.user_id = ?';
        $params[] = (int) $filters['user_id'];
    }

    if (!empty($filters['customer_id'])) {
        $sql .= ' AND i.customer_id = ?';
        $params[] = (int) $filters['customer_id'];
    }

    if (!empty($filters['start_date'])) {
        $sql .= ' AND i.created_at >= ?';
        $params[] = $filters['start_date'] . ' 00:00:00';
    }

    if (!empty($filters['end_date'])) {
        $sql .= ' AND i.created_at <= ?';
        $params[] = $filters['end_date'] . ' 23:59:59';
    }

    if (!empty($filters['q'])) {
        $sql .= ' AND (i.invoice_number LIKE ? OR c.name LIKE ? OR l.name LIKE ?)';
        $q = '%' . $filters['q'] . '%';
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
    }

    $userLocationId = current_user_location_id();
    if ($userLocationId !== null) {
        $sql .= ' AND i.location_id = ?';
        $params[] = $userLocationId;
    }

    $sql .= ' GROUP BY i.id ORDER BY i.created_at DESC, i.id DESC LIMIT 200';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function find_invoice(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT i.*, l.name AS location_name, u.name AS user_name, c.name AS customer_name, c.phone AS customer_phone FROM invoices i JOIN locations l ON l.id = i.location_id JOIN users u ON u.id = i.user_id LEFT JOIN customers c ON c.id = i.customer_id WHERE i.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function invoice_lines_rows(int $invoiceId): array
{
    $stmt = pdo()->prepare('SELECT * FROM invoice_lines WHERE invoice_id = ? ORDER BY id');
    $stmt->execute([$invoiceId]);
    return $stmt->fetchAll();
}

function invoice_components_rows(int $lineId): array
{
    $stmt = pdo()->prepare("SELECT c.*, p.name AS product_name, p.unit, b.size_ml 
        FROM invoice_line_components c 
        JOIN products p ON p.id = c.component_product_id 
        LEFT JOIN product_bottle_details b ON b.product_id = p.id 
        WHERE c.invoice_line_id = ? ORDER BY c.id");
    $stmt->execute([$lineId]);
    return $stmt->fetchAll();
}

function invoice_payments_rows(int $invoiceId): array
{
    $stmt = pdo()->prepare('SELECT p.*, u.name AS user_name FROM payments p JOIN users u ON u.id = p.created_by WHERE p.invoice_id = ? ORDER BY p.created_at');
    $stmt->execute([$invoiceId]);
    return $stmt->fetchAll();
}

function invoice_has_grams_warnings(int $invoiceId): bool
{
    $allFormulaDefaults = formula_defaults_rows();
    $lines = invoice_lines_rows($invoiceId);
    
    foreach ($lines as $line) {
        $components = invoice_components_rows((int) $line['id']);
        if (!$components) {
            continue;
        }
        
        $bottleSize = null;
        foreach ($components as $c) {
            if (!empty($c['size_ml'])) {
                $bottleSize = (int) $c['size_ml'];
                break;
            }
        }
        
        foreach ($components as $c) {
            if (($c['unit'] ?? '') !== 'gram' || !empty($c['size_ml'])) {
                continue;
            }
            
            $qty = (float) $c['quantity'];
            $defaultQty = 0;
            
            $stmt = pdo()->prepare('SELECT perfume_family, quality_grade FROM product_perfume_details WHERE product_id = ?');
            $stmt->execute([(int) $c['component_product_id']]);
            $perfumeDetails = $stmt->fetch();
            if (!$perfumeDetails) {
                continue;
            }
            
            $family = $perfumeDetails['perfume_family'];
            $grade = $perfumeDetails['quality_grade'] ?: '';
            
            foreach ($allFormulaDefaults as $fd) {
                if ($fd['perfume_family'] === $family
                    && ($fd['quality_grade'] ?: '') === $grade
                    && ($bottleSize === null || (int) $fd['bottle_size_ml'] === $bottleSize)
                ) {
                    $defaultQty = (float) $fd['default_grams'];
                    break;
                }
            }
            
            if ($defaultQty > 0 && abs($qty - $defaultQty) > 0.01) {
                return true;
            }
        }
    }
    
    return false;
}

function dashboard_stats(?int $locationId = null): array
{
    $db = pdo();
    $params = [];
    
    $todaySalesQuery = 'SELECT COALESCE(SUM(total), 0) FROM invoices WHERE DATE(created_at) = CURDATE()';
    $todayInvoicesQuery = 'SELECT COUNT(*) FROM invoices WHERE DATE(created_at) = CURDATE()';
    $todayCustomersQuery = 'SELECT COUNT(*) FROM customers WHERE DATE(created_at) = CURDATE()';
    $totalCustomersQuery = 'SELECT COUNT(*) FROM customers';
    $openDebtsQuery = "SELECT COALESCE(SUM(remaining_amount), 0) FROM customer_debts WHERE status = 'open'";
    $monthExpensesQuery = "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
    $lowStockCountQuery = 'SELECT COUNT(*) FROM inventory_balances ib JOIN products p ON p.id = ib.product_id WHERE p.min_stock > 0 AND ib.quantity <= p.min_stock';

    if ($locationId !== null) {
        $todaySalesQuery .= ' AND location_id = ?';
        $todayInvoicesQuery .= ' AND location_id = ?';
        $todayCustomersQuery = 'SELECT COUNT(DISTINCT customer_id) FROM invoices WHERE location_id = ? AND DATE(created_at) = CURDATE()';
        $totalCustomersQuery = 'SELECT COUNT(DISTINCT customer_id) FROM invoices WHERE location_id = ?';
        $openDebtsQuery = "SELECT COALESCE(SUM(d.remaining_amount), 0) FROM customer_debts d JOIN invoices i ON i.id = d.invoice_id WHERE d.status = 'open' AND i.location_id = ?";
        $monthExpensesQuery .= ' AND location_id = ?';
        $lowStockCountQuery .= ' AND ib.location_id = ?';
        
        $params = [$locationId];
    }

    $todaySalesStmt = $db->prepare($todaySalesQuery);
    $todaySalesStmt->execute($params);
    $todaySales = (float) $todaySalesStmt->fetchColumn();

    $todayInvoicesStmt = $db->prepare($todayInvoicesQuery);
    $todayInvoicesStmt->execute($params);
    $todayInvoices = (int) $todayInvoicesStmt->fetchColumn();

    $todayCustomersStmt = $db->prepare($todayCustomersQuery);
    $todayCustomersStmt->execute($params);
    $todayCustomers = (int) $todayCustomersStmt->fetchColumn();

    $totalCustomersStmt = $db->prepare($totalCustomersQuery);
    $totalCustomersStmt->execute($params);
    $totalCustomers = (int) $totalCustomersStmt->fetchColumn();

    $openDebtsStmt = $db->prepare($openDebtsQuery);
    $openDebtsStmt->execute($params);
    $openDebts = (float) $openDebtsStmt->fetchColumn();

    $monthExpensesStmt = $db->prepare($monthExpensesQuery);
    $monthExpensesStmt->execute($params);
    $monthExpenses = (float) $monthExpensesStmt->fetchColumn();

    $lowStockCountStmt = $db->prepare($lowStockCountQuery);
    $lowStockCountStmt->execute($params);
    $lowStockCount = (int) $lowStockCountStmt->fetchColumn();

    return [
        'today_sales' => $todaySales,
        'today_invoices' => $todayInvoices,
        'today_customers' => $todayCustomers,
        'customers' => $totalCustomers,
        'open_debts' => $openDebts,
        'month_expenses' => $monthExpenses,
        'low_stock_count' => $lowStockCount,
    ];
}

function dashboard_location_sales(?int $locationId = null): array
{
    $sql = 'SELECT l.name, l.type, COALESCE(SUM(CASE WHEN DATE(i.created_at) = CURDATE() THEN i.total ELSE 0 END), 0) AS today_sales FROM locations l LEFT JOIN invoices i ON i.location_id = l.id';
    $params = [];
    if ($locationId !== null) {
        $sql .= ' WHERE l.id = ?';
        $params[] = $locationId;
    }
    $sql .= ' GROUP BY l.id ORDER BY l.id';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function low_stock_rows(?int $locationId = null): array
{
    $sql = 'SELECT ib.*, l.name AS location_name, p.name AS product_name, p.unit, p.min_stock FROM inventory_balances ib JOIN products p ON p.id = ib.product_id JOIN locations l ON l.id = ib.location_id WHERE p.min_stock > 0 AND ib.quantity <= p.min_stock';
    $params = [];
    if ($locationId !== null) {
        $sql .= ' AND ib.location_id = ?';
        $params[] = $locationId;
    }
    $sql .= ' ORDER BY ib.quantity ASC';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function debts_rows(): array
{
    return pdo()->query("SELECT d.*, c.name AS customer_name, c.phone, i.invoice_number FROM customer_debts d JOIN customers c ON c.id = d.customer_id JOIN invoices i ON i.id = d.invoice_id WHERE d.status = 'open' ORDER BY d.created_at DESC")->fetchAll();
}

function notification_rows(): array
{
    $db = pdo();
    $rows = [];

    foreach (low_stock_rows() as $row) {
        $rows[] = [
            'type' => 'مخزون',
            'title' => 'صنف وصل للحد الأدنى',
            'details' => $row['product_name'] . ' في ' . $row['location_name'] . ' الرصيد ' . qty($row['quantity']) . ' والحد ' . qty($row['min_stock']),
            'severity' => 'warning',
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    $stmt = $db->query("SELECT d.*, c.name AS customer_name, DATEDIFF(CURDATE(), DATE(d.created_at)) AS age_days FROM customer_debts d JOIN customers c ON c.id = d.customer_id WHERE d.status = 'open' AND DATEDIFF(CURDATE(), DATE(d.created_at)) >= 14 ORDER BY d.created_at");
    foreach ($stmt->fetchAll() as $debt) {
        $rows[] = [
            'type' => 'ديون',
            'title' => 'دين متأخر',
            'details' => $debt['customer_name'] . ' عليه ' . money($debt['remaining_amount']) . ' منذ ' . $debt['age_days'] . ' يوم',
            'severity' => 'danger',
            'created_at' => $debt['created_at'],
        ];
    }

    $stmt = $db->query("SELECT bt.*, l.name AS location_name, COALESCE((SELECT SUM(total) FROM invoices i WHERE i.location_id = bt.location_id AND DATE(i.created_at) = bt.target_date), 0) AS achieved FROM branch_targets bt JOIN locations l ON l.id = bt.location_id WHERE bt.target_date = CURDATE()");
    foreach ($stmt->fetchAll() as $target) {
        $percent = (float) $target['target_amount'] > 0 ? ((float) $target['achieved'] / (float) $target['target_amount']) * 100 : 0;
        if ((float) $target['target_amount'] > 0 && $percent < 50) {
            $rows[] = [
                'type' => 'مبيعات',
                'title' => 'فرع أقل من التارجت اليومي',
                'details' => $target['location_name'] . ' حقق ' . number_format($percent, 1) . '% من تارجت اليوم',
                'severity' => 'warning',
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    $stmt = $db->query("SELECT u.name, l.name AS location_name FROM users u LEFT JOIN locations l ON l.id = u.location_id WHERE u.is_active = 1 AND u.id NOT IN (SELECT user_id FROM attendance_records WHERE action = 'check_in' AND DATE(created_at) = CURDATE()) ORDER BY u.name");
    foreach ($stmt->fetchAll() as $employee) {
        $rows[] = [
            'type' => 'موظفين',
            'title' => 'لم يسجل حضور اليوم',
            'details' => $employee['name'] . ' - ' . ($employee['location_name'] ?: 'عام'),
            'severity' => 'info',
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    return $rows;
}

function audit_rows(array $filters = [], int $limit = 200): array
{
    $db = pdo();
    $sql = 'SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE 1=1';
    $params = [];

    if (!empty($filters['q'])) {
        $sql .= ' AND (a.details LIKE ? OR a.entity_type LIKE ? OR a.action LIKE ?)';
        $q = '%' . $filters['q'] . '%';
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
    }

    if (!empty($filters['user_id'])) {
        $sql .= ' AND a.user_id = ?';
        $params[] = (int) $filters['user_id'];
    }

    if (!empty($filters['action_type'])) {
        $sql .= ' AND a.action = ?';
        $params[] = $filters['action_type'];
    }

    if (!empty($filters['start_date'])) {
        $sql .= ' AND DATE(a.created_at) >= ?';
        $params[] = $filters['start_date'];
    }

    if (!empty($filters['end_date'])) {
        $sql .= ' AND DATE(a.created_at) <= ?';
        $params[] = $filters['end_date'];
    }

    $sql .= ' ORDER BY a.created_at DESC, a.id DESC LIMIT ' . (int) $limit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function log_audit(?int $userId, string $action, string $entityType, ?int $entityId = null, ?string $details = null): void
{
    $stmt = pdo()->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $entityType, $entityId, $details]);
}

function create_online_order(array $data, int $userId): void
{
    if (current_user_location_type() === 'online') {
        throw new RuntimeException('الأونلاين قناة طلبات فقط وليس مخزناً أو فرع بيع.');
    }
    $db = pdo();
    $db->beginTransaction();
    try {
        $customerId = (int) $data['customer_id'];
        if ($customerId <= 0) {
            throw new RuntimeException('اختر عميل أونلاين.');
        }
        $total = 0.0;
        $items = [];
        foreach (($data['product_id'] ?? []) as $idx => $productIdRaw) {
            $productId = (int) $productIdRaw;
            $quantity = (float) ($data['quantity'][$idx] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            $product = find_product($productId);
            if (!$product) {
                continue;
            }
            $unitPriceRaw = trim((string) ($data['unit_price'][$idx] ?? ''));
            $unitPrice = $unitPriceRaw !== '' ? (float) $unitPriceRaw : (float) $product['sale_price'];
            $total += $quantity * $unitPrice;
            $items[] = ['product_id' => $productId, 'quantity' => $quantity, 'unit_price' => $unitPrice];
        }
        if (!$items) {
            throw new RuntimeException('الطلب لا يحتوي على منتجات.');
        }
        $number = 'ON-' . date('Ymd-His') . '-' . random_int(100, 999);
        $status = $data['status'] ?? 'preparing';
        $allowedStatus = in_array($status, ['preparing', 'shipped', 'delivered', 'cancelled'], true) ? $status : 'preparing';
        $discountType = ($data['discount_type'] ?? '') ?: null;
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $discountAmount = line_discount($total, $discountType, $discountValue);
        $netTotal = max(0, $total - $discountAmount);
        $stmt = $db->prepare('INSERT INTO online_orders (order_number, customer_id, status, total, payment_method, notes, discount_type, discount_value, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$number, $customerId, $allowedStatus, $netTotal, $data['payment_method'] ?? 'cash', $data['notes'] ?: null, $discountType, $discountValue, $discountAmount]);
        $orderId = (int) $db->lastInsertId();
        foreach ($items as $item) {
            $stmt = $db->prepare('INSERT INTO online_order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_price']]);
        }
        log_audit($userId, 'create', 'online_order', $orderId, 'إنشاء طلب أونلاين ' . $number);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function update_online_order_status(int $orderId, string $status, int $userId): void
{
    if (current_user_location_type() === 'online') {
        throw new RuntimeException('الأونلاين قناة طلبات فقط وليس مخزناً أو فرع بيع.');
    }
    $allowed = ['preparing', 'shipped', 'delivered', 'cancelled'];
    $status = in_array($status, $allowed, true) ? $status : 'preparing';
    $stmt = pdo()->prepare('UPDATE online_orders SET status = ? WHERE id = ?');
    $stmt->execute([$status, $orderId]);
    log_audit($userId, 'update_status', 'online_order', $orderId, $status);
}

function find_online_order(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT o.*, c.name AS customer_name, c.phone FROM online_orders o JOIN customers c ON c.id = o.customer_id WHERE o.id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }
    $stmt = pdo()->prepare('SELECT * FROM online_order_items WHERE order_id = ?');
    $stmt->execute([$id]);
    $order['items'] = $stmt->fetchAll();
    return $order;
}

function update_online_order(int $orderId, array $data, int $userId): void
{
    if (current_user_location_type() === 'online') {
        throw new RuntimeException('الأونلاين قناة طلبات فقط وليس مخزناً أو فرع بيع.');
    }
    $db = pdo();
    $db->beginTransaction();
    try {
        $order = find_online_order($orderId);
        if (!$order) {
            throw new RuntimeException('الطلب غير موجود.');
        }
        $customerId = (int) $data['customer_id'];
        if ($customerId <= 0) {
            throw new RuntimeException('اختر عميل أونلاين.');
        }
        $total = 0.0;
        $items = [];
        foreach (($data['product_id'] ?? []) as $idx => $productIdRaw) {
            $productId = (int) $productIdRaw;
            $quantity = (float) ($data['quantity'][$idx] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            $product = find_product($productId);
            if (!$product) {
                continue;
            }
            $unitPriceRaw = trim((string) ($data['unit_price'][$idx] ?? ''));
            $unitPrice = $unitPriceRaw !== '' ? (float) $unitPriceRaw : (float) $product['sale_price'];
            $total += $quantity * $unitPrice;
            $items[] = ['product_id' => $productId, 'quantity' => $quantity, 'unit_price' => $unitPrice];
        }
        if (!$items) {
            throw new RuntimeException('الطلب لا يحتوي على منتجات.');
        }
        $status = $data['status'] ?? 'preparing';
        $allowedStatus = in_array($status, ['preparing', 'shipped', 'delivered', 'cancelled'], true) ? $status : 'preparing';
        $discountType = ($data['discount_type'] ?? '') ?: null;
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $discountAmount = line_discount($total, $discountType, $discountValue);
        $netTotal = max(0, $total - $discountAmount);
        $stmt = $db->prepare('UPDATE online_orders SET customer_id = ?, status = ?, total = ?, payment_method = ?, notes = ?, discount_type = ?, discount_value = ?, discount_amount = ? WHERE id = ?');
        $stmt->execute([
            $customerId,
            $allowedStatus,
            $netTotal,
            $data['payment_method'] ?? 'cash',
            $data['notes'] ?: null,
            $discountType,
            $discountValue,
            $discountAmount,
            $orderId,
        ]);
        $stmt = $db->prepare('DELETE FROM online_order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        foreach ($items as $item) {
            $stmt = $db->prepare('INSERT INTO online_order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_price']]);
        }
        log_audit($userId, 'update', 'online_order', $orderId, 'تعديل طلب أونلاين ' . $order['order_number']);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function delete_online_order(int $orderId, int $userId): void
{
    $order = find_online_order($orderId);
    if (!$order) {
        throw new RuntimeException('الطلب غير موجود.');
    }
    $stmt = pdo()->prepare('DELETE FROM online_orders WHERE id = ?');
    $stmt->execute([$orderId]);
    log_audit($userId, 'delete', 'online_order', $orderId, 'حذف طلب أونلاين ' . $order['order_number']);
}

function online_order_rows(): array
{
    return pdo()->query('SELECT o.*, c.name AS customer_name, c.phone FROM online_orders o JOIN customers c ON c.id = o.customer_id ORDER BY o.created_at DESC, o.id DESC')->fetchAll();
}

function backup_database(int $userId): string
{
    $dir = __DIR__ . '/../storage/backups';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $file = $dir . '/backup-' . date('Ymd-His') . '.sql';
    $tables = pdo()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- Backup " . DB_NAME . " at " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n";
    foreach ($tables as $table) {
        $create = pdo()->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
        $sql .= "\nDROP TABLE IF EXISTS `$table`;\n" . $create['Create Table'] . ";\n";
        $rows = pdo()->query('SELECT * FROM `' . $table . '`')->fetchAll();
        foreach ($rows as $row) {
            $columns = array_map(fn($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row));
            $values = array_map(fn($v) => $v === null ? 'NULL' : pdo()->quote((string) $v), array_values($row));
            $sql .= 'INSERT INTO `' . $table . '` (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    file_put_contents($file, $sql);
    log_audit($userId, 'backup', 'database', null, basename($file));
    return $file;
}

function backup_files(): array
{
    $dir = __DIR__ . '/../storage/backups';
    if (!is_dir($dir)) {
        return [];
    }
    $files = glob($dir . '/*.sql') ?: [];
    rsort($files);
    return array_map(fn($file) => [
        'name' => basename($file),
        'size' => filesize($file),
        'created_at' => date('Y-m-d H:i:s', filemtime($file)),
    ], $files);
}

function backup_file_path(string $name): ?string
{
    $safe = basename($name);
    if (!str_ends_with($safe, '.sql')) {
        return null;
    }
    $file = __DIR__ . '/../storage/backups/' . $safe;
    return is_file($file) ? $file : null;
}

function update_recipe(int $recipeId, array $data): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        // Fetch recipe header to find product_id
        $stmt = $db->prepare('SELECT product_id FROM recipe_headers WHERE id = ?');
        $stmt->execute([$recipeId]);
        $productId = (int) $stmt->fetchColumn();

        if ($productId > 0) {
            // Update product
            $stmt = $db->prepare('UPDATE products SET name = ?, sale_price = ? WHERE id = ?');
            $stmt->execute([$data['name'], (float) $data['default_sale_price'], $productId]);
        }

        // Update recipe header
        $stmt = $db->prepare('UPDATE recipe_headers SET name = ?, bottle_product_id = ?, default_sale_price = ? WHERE id = ?');
        $stmt->execute([$data['name'], (int) $data['bottle_product_id'], (float) $data['default_sale_price'], $recipeId]);

        // Delete old components
        $stmt = $db->prepare('DELETE FROM recipe_components WHERE recipe_id = ?');
        $stmt->execute([$recipeId]);

        // Insert new components
        foreach (($data['perfume_product_id'] ?? []) as $idx => $perfumeId) {
            $grams = (float) ($data['grams'][$idx] ?? 0);
            if ((int) $perfumeId > 0 && $grams > 0) {
                $stmt = $db->prepare('INSERT INTO recipe_components (recipe_id, perfume_product_id, grams) VALUES (?, ?, ?)');
                $stmt->execute([$recipeId, (int) $perfumeId, $grams]);
            }
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function delete_recipe(int $id): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        // Fetch product_id to soft delete it
        $stmt = $db->prepare('SELECT product_id FROM recipe_headers WHERE id = ?');
        $stmt->execute([$id]);
        $productId = (int) $stmt->fetchColumn();
        
        // Soft delete recipe header
        $stmt = $db->prepare('UPDATE recipe_headers SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);

        if ($productId > 0) {
            $stmt = $db->prepare('UPDATE products SET is_active = 0 WHERE id = ?');
            $stmt->execute([$productId]);
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function delete_formula_default(int $id): void
{
    if ($id <= 0) {
        throw new RuntimeException('إعداد الجرامات الافتراضية غير صالح.');
    }
    $stmt = pdo()->prepare('DELETE FROM formula_defaults WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('لم يتم العثور على إعداد الجرامات الافتراضية للحذف.');
    }
}

function find_formula_default(int $id): ?array
{
    $stmt = pdo()->prepare('SELECT * FROM formula_defaults WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function all_wasted_products(?int $locationId = null): array
{
    $sql = 'SELECT w.*, p.name AS product_name, l.name AS location_name, u.name AS user_name 
            FROM wasted_products w 
            JOIN products p ON p.id = w.product_id 
            JOIN locations l ON l.id = w.location_id 
            JOIN users u ON u.id = w.created_by';
    $params = [];
    if ($locationId !== null) {
        $sql .= ' WHERE w.location_id = ?';
        $params[] = $locationId;
    }
    $sql .= ' ORDER BY w.created_at DESC';
    $stmt = pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function add_wasted_product(array $data, int $userId): void
{
    $db = pdo();
    $db->beginTransaction();
    try {
        $locationId = (int) $data['location_id'];
        $productId = (int) $data['product_id'];
        $quantity = (float) $data['quantity'];
        $reason = $data['reason'] ?: 'منتج تالف / هالك';

        if ($locationId <= 0 || $productId <= 0 || $quantity <= 0) {
            throw new RuntimeException('يرجى تحديد الموقع والمنتج والكمية بشكل صحيح.');
        }

        $currentStock = get_stock($locationId, $productId);
        if ($currentStock < $quantity) {
            $product = find_product($productId);
            throw new RuntimeException('المخزون غير كافٍ لتسجيل هذا الهالك للمنتج: ' . ($product['name'] ?? ('#' . $productId)) . '. المتاح حالياً: ' . qty($currentStock));
        }

        $stmt = $db->prepare('INSERT INTO wasted_products (location_id, product_id, quantity, reason, created_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$locationId, $productId, $quantity, $reason, $userId]);
        $wasteId = (int) $db->lastInsertId();

        move_inventory($db, $locationId, $productId, -1 * $quantity, 'manual_adjustment', $userId, 'waste', $wasteId, 'تسجيل هالك: ' . $reason);

        $product = find_product($productId);
        log_audit($userId, 'create', 'waste', $wasteId, 'تسجيل هالك للمنتج ' . ($product['name'] ?? '') . ' بكمية ' . qty($quantity) . ' في موقع #' . $locationId);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}
