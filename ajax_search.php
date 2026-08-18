<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');

if (mb_strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$pdo = medal_pdo();
$results = [];

if ($pdo) {
    try {
        $like = '%' . $query . '%';

        $sql = 'SELECT p.id, p.slug, p.name_ar, p.name_en, p.primary_image_key AS image,
                pv_default.price AS price,
                GROUP_CONCAT(DISTINCT c.name_ar, \'|||\'  , c.name_en, \'|||\'  , c.slug SEPARATOR \'@@@\') AS cat_data
                FROM products p
                LEFT JOIN product_variants pv_default ON pv_default.id = (
                    SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
                )
                LEFT JOIN product_categories pc ON pc.product_id = p.id
                LEFT JOIN categories c ON c.slug = pc.category_slug
                WHERE p.active = 1
                AND (
                    p.name_ar LIKE ? OR p.name_en LIKE ?
                    OR p.description_ar LIKE ? OR p.description_en LIKE ?
                    OR p.notes_ar LIKE ? OR p.notes_en LIKE ?
                    OR c.name_ar LIKE ? OR c.name_en LIKE ?
                )
                GROUP BY p.id, pv_default.id, pv_default.price
                ORDER BY p.sort_order ASC, p.id ASC
                LIMIT 8';

        $st = $pdo->prepare($sql);
        $st->execute([$like, $like, $like, $like, $like, $like, $like, $like]);
        $rows = $st->fetchAll();

        // Use the language the user typed in, not the site language
        $searchLang = detect_query_lang($query);
        $isAr       = $searchLang === 'ar';

        foreach ($rows as $r) {
            $name  = $isAr ? (string) $r['name_ar'] : (string) $r['name_en'];
            $price = (float) $r['price'];
            $image = (string) $r['image'];

            $category = '';
            $catData  = $r['cat_data'] ?? '';
            if ($catData !== '') {
                $cats     = explode('@@@', $catData);
                $firstCat = explode('|||', $cats[0]);
                $category = $isAr ? ($firstCat[0] ?? '') : ($firstCat[1] ?? '');
            }

            $results[] = [
                'id'       => (int) $r['id'],
                'name'     => $name,
                'slug'     => (string) $r['slug'],
                'price'    => $price,
                'image'    => $image,
                'category' => $category,
            ];
        }
    } catch (Throwable $e) {
        error_log('ajax_search.php error: ' . $e->getMessage());
        echo json_encode(['results' => []]);
        exit;
    }
} else {
    $products   = get_products_localized();
    // Use the language the user typed in, not the site language
    $searchLang = detect_query_lang($query);
    $isAr       = $searchLang === 'ar';

    foreach ($products as $p) {
        // Search across BOTH language fields for cross-language support
        $corpus = implode(' ', [
            $p['name']           ?? '',
            $p['name_en']        ?? '',
            $p['name_ar']        ?? '',
            $p['description']    ?? '',
            $p['description_en'] ?? '',
            $p['description_ar'] ?? '',
            $p['notes']          ?? '',
            $p['notes_en']       ?? '',
            $p['notes_ar']       ?? '',
        ]);

        if (mb_stripos($corpus, $query) !== false) {
            // Show name in the language the user searched with
            $displayName = $isAr
                ? ($p['name_ar'] ?? $p['name'] ?? '')
                : ($p['name_en'] ?? $p['name'] ?? '');

            $results[] = [
                'id'       => (int) $p['id'],
                'name'     => $displayName,
                'slug'     => $p['slug'] ?? '',
                'price'    => (float) ($p['price'] ?? 0),
                'image'    => $p['image'] ?? '',
                'category' => $p['category'] ?? '',
            ];
        }

        if (count($results) >= 8) {
            break;
        }
    }
}

echo json_encode(['results' => $results]);