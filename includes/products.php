<?php
declare(strict_types=1);

require_once __DIR__ . '/product_translations.php';
require_once __DIR__ . '/db.php';

/**
 * @return array<int, array{id:int,name:string,slug:string,price:float,category:string,notes:string,description:string,image:string,season:string,bestseller:bool}>
 */
function get_products_static(): array
{
    return [
        [
            'id' => 1,
            'name' => 'Velvet Oud',
            'slug' => 'velvet-oud',
            'price' => 189.00,
            'category' => 'unisex',
            'season' => 'winter',
            'bestseller' => true,
            'notes' => 'Oud, rose, amber, sandalwood',
            'description' => 'A deep, resinous oud wrapped in velvet rose and warm amber. Designed for evenings that linger.',
            'image' => 'velvet-oud',
        ],
        [
            'id' => 2,
            'name' => 'Citrus Doré',
            'slug' => 'citrus-dore',
            'price' => 125.00,
            'category' => 'women',
            'season' => 'summer',
            'bestseller' => true,
            'notes' => 'Bergamot, neroli, white musk',
            'description' => 'Sunlit citrus and soft neroli over a clean musk base. Effortlessly bright and refined.',
            'image' => 'citrus-dore',
        ],
        [
            'id' => 3,
            'name' => 'Noir Épicé',
            'slug' => 'noir-epice',
            'price' => 165.00,
            'category' => 'men',
            'season' => 'winter',
            'bestseller' => true,
            'notes' => 'Black pepper, cedar, vetiver, tonka',
            'description' => 'Spiced opening, woody heart, and a smooth tonka dry-down. Confident without shouting.',
            'image' => 'noir-epice',
        ],
        [
            'id' => 4,
            'name' => 'Jardin de Minuit',
            'slug' => 'jardin-de-minuit',
            'price' => 142.00,
            'category' => 'women',
            'season' => 'summer',
            'bestseller' => false,
            'notes' => 'Jasmine, tuberose, patchouli',
            'description' => 'White florals at night—jasmine and tuberose grounded by earthy patchouli.',
            'image' => 'jardin-minuit',
        ],
        [
            'id' => 5,
            'name' => 'Mer & Sel',
            'slug' => 'mer-sel',
            'price' => 118.00,
            'category' => 'unisex',
            'season' => 'summer',
            'bestseller' => false,
            'notes' => 'Sea salt, driftwood, ambrette',
            'description' => 'Coastal air, sun-warmed wood, and a whisper of skin. Minimal and memorable.',
            'image' => 'mer-sel',
        ],
        [
            'id' => 6,
            'name' => 'Cuir Royal',
            'slug' => 'cuir-royal',
            'price' => 198.00,
            'category' => 'men',
            'season' => 'winter',
            'bestseller' => false,
            'notes' => 'Leather, iris, saffron, oakmoss',
            'description' => 'Supple leather with iris powder and saffron heat. A modern classic.',
            'image' => 'cuir-royal',
        ],
        [
            'id' => 7,
            'name' => 'Khinat Oud Special',
            'slug' => 'khinat-oud-special',
            'price' => 250.00,
            'category' => 'khinat',
            'season' => 'winter',
            'bestseller' => true,
            'notes' => 'Premium oud, saffron, musk, amber',
            'description' => 'Exclusive khinat blend with premium oud and luxury notes. Perfect for special occasions.',
            'image' => 'khinat-oud-special',
        ],
        [
            'id' => 8,
            'name' => 'Khinat Rose Gold',
            'slug' => 'khinat-rose-gold',
            'price' => 185.00,
            'category' => 'khinat',
            'season' => 'summer',
            'bestseller' => false,
            'notes' => 'Turkish rose, gold amber, white musk',
            'description' => 'Elegant rose and gold amber combination. Modern luxury in every drop.',
            'image' => 'khinat-rose-gold',
        ],
        [
            'id' => 9,
            'name' => 'Khinat Royal Blend',
            'slug' => 'khinat-royal-blend',
            'price' => 320.00,
            'category' => 'khinat',
            'season' => 'both',
            'bestseller' => true,
            'notes' => 'Royal oud, vanilla, sandalwood, spices',
            'description' => 'A royal blend fit for royalty. Complex and sophisticated fragrance profile.',
            'image' => 'khinat-royal-blend',
        ],
    ];
}

/** @return list<array<string, mixed>> */
function get_products_from_db(): array
{
    $pdo = medal_pdo();
    if ($pdo === null) {
        return [];
    }
    $rows = [];
    try {
        $sql = 'SELECT p.*,
            pv_default.id AS default_variant_id,
            pv_default.price AS price,
            (SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE product_id = p.id) AS average_rating,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS reviews_count
            FROM products p
            LEFT JOIN product_variants pv_default ON pv_default.id = (
                SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
            )
            WHERE (p.active = 1 OR p.active IS NULL)
            ORDER BY p.sort_order ASC, p.id ASC';
        $rows = $pdo->query($sql)->fetchAll();
    } catch (Throwable $e) {
        // Fallback without product_reviews subquery
        try {
            $sql = 'SELECT p.*,
                pv_default.id AS default_variant_id,
                pv_default.price AS price
                FROM products p
                LEFT JOIN product_variants pv_default ON pv_default.id = (
                    SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
                )
                WHERE (p.active = 1 OR p.active IS NULL)
                ORDER BY p.sort_order ASC, p.id ASC';
            $rows = $pdo->query($sql)->fetchAll();
        } catch (Throwable $e2) {
            // Ultimate fallback directly from products table
            try {
                $rows = $pdo->query('SELECT p.* FROM products p WHERE (p.active = 1 OR p.active IS NULL) ORDER BY p.id ASC')->fetchAll();
            } catch (Throwable $e3) {
                $rows = [];
            }
        }
    }
    $out = [];
    foreach ($rows as $r) {
        $out[] = map_db_product_row($r);
    }
    // Fetch all categories for these products in one query
    if (!empty($out)) {
        $ids = array_column($out, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        try {
            $catSt = $pdo->prepare("SELECT product_id, category_slug FROM product_categories WHERE product_id IN ($placeholders) ORDER BY category_slug ASC");
            $catSt->execute($ids);
            $catMap = [];
            foreach ($catSt->fetchAll() as $cr) {
                $catMap[(int)$cr['product_id']][] = $cr['category_slug'];
            }
            foreach ($out as &$p) {
                $cats = $catMap[$p['id']] ?? [];
                $p['categories'] = !empty($cats) ? $cats : ([$p['category']] ?: []);
            }
            unset($p);
        } catch (Throwable $e) {
            error_log('Error in products.php product_categories join: ' . $e->getMessage());
            // product_categories table may not exist yet — fall back to single category
            foreach ($out as &$p) {
                $p['categories'] = [$p['category']];
            }
            unset($p);
        }
    }
    return $out;
}

/** @param array<string, mixed> $r */
function map_db_product_row(array $r): array
{
    return [
        'id' => (int) $r['id'],
        'slug' => (string) $r['slug'],
        'brand_id' => isset($r['brand_id']) ? (int) $r['brand_id'] : null,
        'is_brand_product' => (int) ($r['is_brand_product'] ?? 0),
        'category' => (string) $r['category'],
        'categories' => [],   // filled by get_products_from_db or get_product_by_id
        'season' => (string) $r['season'],
        'bestseller' => !empty($r['is_bestseller']),
        'is_offer' => !empty($r['is_offer']),
        'image' => (string) $r['primary_image_key'],
        'price' => isset($r['price']) ? (float) $r['price'] : 0.0,
        'default_variant_id' => isset($r['default_variant_id']) ? (int) $r['default_variant_id'] : 0,
        'reviews_count' => (int)($r['reviews_count'] ?? 0),
        'average_rating' => (float)($r['average_rating'] ?? 5.0),
        'name_en' => (string) $r['name_en'],
        'name_ar' => (string) $r['name_ar'],
        'notes_en' => (string) ($r['notes_en'] ?? ''),
        'notes_ar' => (string) ($r['notes_ar'] ?? ''),
        'description_en' => (string) $r['description_en'],
        'description_ar' => (string) $r['description_ar'],
        'is_db' => true,
    ];
}

/** @return array<int, array<string, mixed>> */
function get_products(): array
{
    $db = get_products_from_db();
    if ($db !== []) {
        return $db;
    }
    return get_products_static();
}

function get_product_by_id(int $id): ?array
{
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->prepare(
                'SELECT p.*,
                pv_default.id AS default_variant_id,
                pv_default.price AS price,
                (SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE product_id = p.id) AS average_rating,
                (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS reviews_count
                FROM products p
                LEFT JOIN product_variants pv_default ON pv_default.id = (
                    SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
                )
                WHERE p.id = ? AND p.active = 1'
            );
            $st->execute([$id]);
            $r = $st->fetch();
            if ($r !== false) {
                $mapped = map_db_product_row($r);
                // Fetch categories
                try {
                    $cst = $pdo->prepare('SELECT category_slug FROM product_categories WHERE product_id = ? ORDER BY category_slug ASC');
                    $cst->execute([$id]);
                    $cats = $cst->fetchAll(PDO::FETCH_COLUMN);
                    $mapped['categories'] = !empty($cats) ? $cats : [$mapped['category']];
                } catch (Throwable $e) {
                    error_log('Error in products.php product_categories in get_product_by_id: ' . $e->getMessage());
                    $mapped['categories'] = [$mapped['category']];
                }
                return $mapped;
            }
        } catch (Throwable $e) {
            error_log('Error in products.php get_product_by_id: ' . $e->getMessage());
        }
    }
    foreach (get_products_static() as $p) {
        if ($p['id'] === $id) {
            return $p;
        }
    }
    return null;
}

function get_product_by_slug(string $slug): ?array
{
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->prepare(
                'SELECT p.*,
                pv_default.id AS default_variant_id,
                pv_default.price AS price,
                (SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE product_id = p.id) AS average_rating,
                (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS reviews_count
                FROM products p
                LEFT JOIN product_variants pv_default ON pv_default.id = (
                    SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
                )
                WHERE p.slug = ? AND p.active = 1'
            );
            $st->execute([$slug]);
            $r = $st->fetch();
            if ($r !== false) {
                return map_db_product_row($r);
            }
        } catch (Throwable $e) {
            error_log('Error in products.php get_product_by_slug: ' . $e->getMessage());
        }
        return null;
    }
    foreach (get_products_static() as $p) {
        if (($p['slug'] ?? '') === $slug) {
            return $p;
        }
    }
    return null;
}

/** @return list<array{id:int,label_en:string,label_ar:string,price:float,compare_at_price:?float,sort_order:int}> */
function get_product_variants(int $productId): array
{
    $pdo = medal_pdo();
    if ($pdo === null) {
        return [];
    }
    try {
        $st = $pdo->prepare('SELECT id, label_en, label_ar, price, compare_at_price, stock, sort_order FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
        $st->execute([$productId]);
        $rows = $st->fetchAll();
    } catch (Throwable $e) {
        error_log('Error in products.php get_product_variants: ' . $e->getMessage());
        return [];
    }
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'label_en' => (string) $r['label_en'],
            'label_ar' => (string) $r['label_ar'],
            'price' => (float) $r['price'],
            'compare_at_price' => isset($r['compare_at_price']) && $r['compare_at_price'] !== null ? (float) $r['compare_at_price'] : null,
            'stock' => (int) ($r['stock'] ?? 0),
            'sort_order' => (int) $r['sort_order'],
        ];
    }
    return $out;
}

/**
 * Detects whether a search query is written in Arabic or English.
 * Returns 'ar' if the query contains Arabic Unicode characters, 'en' otherwise.
 */
function detect_query_lang(string $query): string
{
    return preg_match('/[\x{0600}-\x{06FF}]/u', $query) ? 'ar' : 'en';
}

/** @param array<string, mixed> $p */
function localize_product(array $p, ?string $lang = null): array
{
    $lang = $lang ?? current_lang();
    if (!empty($p['is_db']) || (isset($p['name_en']) && isset($p['name_ar']))) {
        $isAr = $lang === 'ar';
        $out = $p;
        $out['name']        = $isAr ? (string) $p['name_ar']        : (string) $p['name_en'];
        $out['notes']       = $isAr ? (string) ($p['notes_ar'] ?? '') : (string) ($p['notes_en'] ?? '');
        $out['description'] = $isAr ? (string) $p['description_ar'] : (string) $p['description_en'];
        // Keep bilingual fields for cross-language search (do NOT unset them)
        unset($out['is_db']);
        return $out;
    }
    if ($lang !== 'ar') {
        return $p;
    }
    $tr = product_translations_ar()[$p['id']] ?? null;
    return $tr !== null ? array_merge($p, $tr) : $p;
}

/** @return list<array<string, mixed>> */
function get_products_localized(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $cache = array_map(static fn (array $p) => localize_product($p), get_products());
    return $cache;
}

/** @return list<array<string, mixed>> */
function get_latest_products(int $days = 3): array
{
    $pdo = medal_pdo();
    if ($pdo === null) return [];
    try {
        $st = $pdo->prepare('SELECT * FROM products WHERE active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY created_at DESC');
        $st->execute([$days]);
        $rows = $st->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[] = localize_product(map_db_product_row($r));
        }
return $out;
    } catch (Throwable $e) {
        error_log('Error in products.php get_latest_products: ' . $e->getMessage());
        return [];
    }
}

/** @return array<string, mixed>|null */
function get_product_by_id_localized(int $id): ?array
{
    $p = get_product_by_id($id);
    return $p !== null ? localize_product($p) : null;
}

/** @return array{price:float, variant_id:int}|null */
function resolve_product_variant(int $productId, ?int $variantId): ?array
{
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            if ($variantId !== null && $variantId > 0) {
                $st = $pdo->prepare('SELECT id, price FROM product_variants WHERE id = ? AND product_id = ?');
                $st->execute([$variantId, $productId]);
                $r = $st->fetch();
                if ($r !== false) {
                    return ['price' => (float) $r['price'], 'variant_id' => (int) $r['id']];
                }
            }
            $st = $pdo->prepare('SELECT id, price FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
            $st->execute([$productId]);
            $r = $st->fetch();
            if ($r !== false) {
                return ['price' => (float) $r['price'], 'variant_id' => (int) $r['id']];
            }
        } catch (Throwable $e) {
            error_log('Error in products.php resolve_product_variant: ' . $e->getMessage());
            return null;
        }
        return null;
    }
    $p = get_product_by_id($productId);
    if ($p === null) {
        return null;
    }
    return ['price' => (float) $p['price'], 'variant_id' => 0];
}

/** @return array<string, mixed>|null */
function get_cart_line_product(int $productId, ?int $variantId): ?array
{
    $p = get_product_by_id($productId);
    if ($p === null) {
        return null;
    }
    $rv = resolve_product_variant($productId, $variantId);
    if ($rv === null) {
        return null;
    }
    $p['price'] = $rv['price'];
    $p['cart_variant_id'] = $rv['variant_id'];
    return localize_product($p);
}

function category_label(string $category): string
{
    static $catsMap = null;
    if ($catsMap === null) {
        $catsMap = [];
        // Hardcode fallback names for built-in or virtual sections
        $isAr = current_lang() === 'ar';
        $catsMap['offers'] = [
            'ar' => 'العروض الحصرية',
            'en' => 'Exclusive Offers'
        ];
        $catsMap['brands'] = [
            'ar' => 'ماركات عالمية',
            'en' => 'Global Brands'
        ];
        
        $pdo = medal_pdo();
        if ($pdo) {
            try {
                $rows = $pdo->query("SELECT slug, name_ar, name_en FROM categories")->fetchAll();
                foreach ($rows as $r) {
                    $catsMap[strtolower($r['slug'])] = [
                        'ar' => $r['name_ar'] ?? '',
                        'en' => $r['name_en'] ?? ''
                    ];
                }
            } catch (Throwable $e) {}
        }
    }

    $key = strtolower($category);
    $lang = current_lang();
    if (isset($catsMap[$key])) {
        $label = $catsMap[$key][$lang] ?? '';
        if ($label !== '') {
            return $label;
        }
    }

    // Fallback: clean up the slug if not found in DB
    $clean = str_ireplace(['cat_', 'category_', '-', '_'], ['', '', ' ', ' '], $category);
    return ucwords(trim($clean));
}

function count_products_in_category(string $category): int
{
    if ($category === 'all') {
        $pdo = medal_pdo();
        if ($pdo !== null) {
            try {
                return (int) $pdo->query('SELECT COUNT(*) FROM products WHERE active = 1')->fetchColumn();
            } catch (Throwable $e) {
                error_log('Error in products.php count_products_in_category(all): ' . $e->getMessage());
            }
        }
        return count(get_products());
    }
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->prepare('SELECT COUNT(*) FROM products p JOIN product_categories pc ON p.id = pc.product_id WHERE pc.category_slug = ? AND p.active = 1');
            $st->execute([$category]);
            $count = (int) $st->fetchColumn();
            if ($count > 0) {
                return $count;
            }
        } catch (Throwable $e) {
            error_log('Error in products.php count_products_in_category: ' . $e->getMessage());
        }
    }
    return count(array_filter(get_products(), static fn (array $p) => $p['category'] === $category));
}

function product_matches_season(array $p, string $season): bool
{
    $s = $p['season'] ?? 'both';
    return $s === 'both' || $s === $season;
}

/** @return list<array<string, mixed>> */
function get_products_localized_filtered(callable $predicate): array
{
    return array_values(array_filter(get_products_localized(), $predicate));
}

/** @return list<array<string, mixed>> */
function get_bestsellers_localized(int $limit = 4): array
{
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->prepare(
                'SELECT p.*,
                pv_default.id AS default_variant_id,
                pv_default.price AS price,
                (SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE product_id = p.id) AS average_rating,
                (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS reviews_count
                FROM products p
                LEFT JOIN product_variants pv_default ON pv_default.id = (
                    SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
                )
                WHERE p.active = 1 AND p.is_bestseller = 1
                ORDER BY p.sort_order ASC, p.id ASC
                LIMIT ?'
            );
            $st->bindValue(1, $limit, PDO::PARAM_INT);
            $st->execute();
            $rows = $st->fetchAll();
            if (!empty($rows)) {
                return array_map(static fn($r) => localize_product(map_db_product_row($r)), $rows);
            }
        } catch (Throwable $e) {
            error_log('Error in products.php get_bestsellers_localized: ' . $e->getMessage());
        }
    }
    $list = get_products_localized_filtered(static fn (array $p) => !empty($p['bestseller']));
    if ($list === []) {
        $list = array_slice(get_products_localized_filtered(static fn (array $p) => true), 0, $limit);
    }
    return array_slice($list, 0, $limit);
}

/** @return list<array<string, mixed>> */
function get_brand_products_localized(int $limit = 8): array
{
    $pdo = medal_pdo();
    if ($pdo === null) return [];
    try {
        $st = $pdo->prepare('SELECT p.*,
            pv_default.id AS default_variant_id,
            pv_default.price AS price,
            (SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE product_id = p.id) AS average_rating,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS reviews_count
            FROM products p
            LEFT JOIN product_variants pv_default ON pv_default.id = (
                SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
            )
            WHERE p.active = 1 AND EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_slug = \'brands\')
            ORDER BY p.sort_order ASC, p.id ASC LIMIT ?');
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = localize_product(map_db_product_row($r));
        }
        return $out;
    } catch (Throwable $e) {
        error_log('Error in products.php get_brand_products_localized: ' . $e->getMessage());
        return [];
    }
}

/** @return list<array<string, mixed>> */
function get_seasonal_localized(string $season, int $limit = 4): array
{
    $list = get_products_localized_filtered(static fn (array $p) => product_matches_season($p, $season));
    return array_slice($list, 0, $limit);
}

/**
 * Get all active offer bundles with their single product (localized) + custom quantity and price.
 * @return list<array<string,mixed>>
 */
function get_offer_bundles(): array
{
    $pdo = medal_pdo();
    if ($pdo === null) return [];
    try {
        $bundles = $pdo->query(
            'SELECT b.*, p.name_ar AS prod_name_ar, p.name_en AS prod_name_en, p.primary_image_key AS prod_image,
                    pv.label_ar AS var_label_ar, pv.label_en AS var_label_en, pv.price AS original_price
             FROM offer_bundles b
             JOIN products p ON p.id = b.product_id AND p.active = 1
             LEFT JOIN product_variants pv ON pv.id = b.variant_id
             WHERE b.active = 1 AND b.product_id IS NOT NULL
             ORDER BY b.sort_order ASC, b.id ASC'
        )->fetchAll();
        
        $out = [];
        $lang = current_lang();
        foreach ($bundles as $b) {
            $name = $lang === 'ar' ? (string)$b['name_ar'] : (string)$b['name_en'];
            $desc = $lang === 'ar' ? (string)($b['description_ar'] ?? '') : (string)($b['description_en'] ?? '');
            
            // Choose image: custom bundle image OR falls back to product image
            $image = $b['image_key'] !== '' ? $b['image_key'] : (string)$b['prod_image'];
            
            // Calculate prices
            $originalPrice = (float)$b['original_price'];
            $packagePrice = (float)$b['price'];
            
            // Original total value of these pieces
            $comparePrice = $originalPrice * (int)$b['quantity'];
            
            $out[] = [
                'id' => (int)$b['id'],
                'product_id' => (int)$b['product_id'],
                'variant_id' => $b['variant_id'] ? (int)$b['variant_id'] : null,
                'quantity' => (int)$b['quantity'],
                'name' => $name,
                'name_ar' => (string)$b['name_ar'],
                'name_en' => (string)$b['name_en'],
                'description' => $desc,
                'image' => $image,
                'price' => $packagePrice,
                'compare_at_price' => $comparePrice,
                'discount_type' => (string)$b['discount_type'],
                'discount_value' => (float)$b['discount_value'],
                'variant_label' => $lang === 'ar' ? (string)$b['var_label_ar'] : (string)$b['var_label_en'],
            ];
        }
return $out;
    } catch (Throwable $e) {
        error_log('Error in products.php get_offer_bundles: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get details for a bundle in the shopping cart.
 * @return array<string, mixed>|null
 */
function get_cart_line_bundle(int $bundleId): ?array
{
    $pdo = medal_pdo();
    if ($pdo === null) return null;
    try {
        $st = $pdo->prepare(
            'SELECT b.*, p.name_ar AS prod_name_ar, p.name_en AS prod_name_en, p.primary_image_key AS prod_image,
                    pv.label_ar AS var_label_ar, pv.label_en AS var_label_en, pv.price AS original_price
             FROM offer_bundles b
             JOIN products p ON p.id = b.product_id AND p.active = 1
             LEFT JOIN product_variants pv ON pv.id = b.variant_id
             WHERE b.id = ?'
        );
        $st->execute([$bundleId]);
        $b = $st->fetch();
        if (!$b) return null;
        
        $lang = current_lang();
        $name = $lang === 'ar' ? (string)$b['name_ar'] : (string)$b['name_en'];
        $image = $b['image_key'] !== '' ? $b['image_key'] : (string)$b['prod_image'];
        
        $price = (float)$b['price'];
        $comparePrice = (float)$b['original_price'] * (int)$b['quantity'];
        
        return [
            'id' => (int)$b['id'],
            'is_bundle' => true,
            'name' => $name,
            'price' => $price,
            'compare_at_price' => $comparePrice,
            'image' => $image,
            'product_id' => (int)$b['product_id'],
            'variant_id' => $b['variant_id'] ? (int)$b['variant_id'] : null,
            'quantity' => (int)$b['quantity'],
        ];
    } catch (Throwable $e) {
        error_log('Error in products.php get_cart_line_bundle: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get products for a category slug using the multi-category pivot table.
 * Falls back to in-memory filter if the table doesn't exist yet.
 * @return list<array<string,mixed>>
 */
function get_products_by_category(string $slug): array
{
    $pdo = medal_pdo();
    if ($pdo === null) {
        return array_values(array_filter(
            get_products_localized(),
            fn($p) => in_array($slug, (array)($p['categories'] ?? [$p['category']]), true)
        ));
    }
    try {
        $st = $pdo->prepare(
            'SELECT p.*,
             pv_default.id AS default_variant_id,
             pv_default.price AS price,
             (SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE product_id = p.id) AS average_rating,
             (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS reviews_count
             FROM product_categories pc
             JOIN products p ON p.id = pc.product_id AND p.active = 1
             LEFT JOIN product_variants pv_default ON pv_default.id = (
                 SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
             )
             WHERE pc.category_slug = ?
             ORDER BY p.sort_order ASC, p.id ASC'
        );
        $st->execute([$slug]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = localize_product(map_db_product_row($r));
        }
        return $out;
    } catch (Throwable $e) {
        error_log('Error in products.php get_products_by_category: ' . $e->getMessage());
        return array_values(array_filter(
            get_products_localized(),
            fn($p) => in_array($slug, (array)($p['categories'] ?? [$p['category']]), true)
        ));
    }
}

/**
 * Get all active standalone offer products (is_offer = 1) — localized.
 * Used by the public offers.php page.
 * @return list<array<string, mixed>>
 */
function get_offer_products_localized(int $limit = 0): array
{
    $pdo = medal_pdo();
    if ($pdo === null) return [];
    try {
        $sql = 'SELECT p.*,
            pv_default.id AS default_variant_id,
            pv_default.price AS price,
            pv_default.compare_at_price AS compare_at_price,
            (SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE product_id = p.id) AS average_rating,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS reviews_count
            FROM products p
            LEFT JOIN product_variants pv_default ON pv_default.id = (
                SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
            )
            WHERE p.active = 1 AND EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_slug = \'offers\')
            ORDER BY p.sort_order ASC, p.id ASC';
        if ($limit > 0) $sql .= ' LIMIT ' . (int)$limit;
        $rows = $pdo->query($sql)->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $p = map_db_product_row($r);
            $p['compare_at_price'] = (isset($r['compare_at_price']) && $r['compare_at_price'] !== null)
                ? (float)$r['compare_at_price'] : null;
            $out[] = localize_product($p);
        }
        return $out;
    } catch (Throwable $e) {
        error_log('Error in products.php get_offer_products_localized: ' . $e->getMessage());
        return [];
    }
}

/**
 * Returns true if there is at least one active product in the 'offers' category.
 * Cached per request — safe to call from header, page, etc.
 */
function has_any_offers(): bool
{
    static $result = null;
    if ($result !== null) return $result;

    $pdo = medal_pdo();
    if ($pdo === null) {
        $result = false;
        return $result;
    }
    try {
        $count = (int) $pdo->query(
            "SELECT COUNT(*) FROM products p
             WHERE p.active = 1
               AND EXISTS (
                   SELECT 1 FROM product_categories pc
                   WHERE pc.product_id = p.id AND pc.category_slug = 'offers'
               )
             LIMIT 1"
        )->fetchColumn();
        $result = $count > 0;
    } catch (Throwable $e) {
        error_log('Error in products.php has_any_offers: ' . $e->getMessage());
        $result = false;
    }
    return $result;
}

