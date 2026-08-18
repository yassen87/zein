<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/products.php';

header('Content-Type: application/xml; charset=UTF-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'zainperfumes.com';
$base   = $scheme . '://' . $host . '/';

$pages = [
    ['loc' => '', 'priority' => '1.00', 'changefreq' => 'daily'],
    ['loc' => 'products.php', 'priority' => '0.90', 'changefreq' => 'daily'],
    ...(has_any_offers() ? [['loc' => 'offers.php', 'priority' => '0.85', 'changefreq' => 'daily']] : []),
    ['loc' => 'brands.php', 'priority' => '0.80', 'changefreq' => 'weekly'],
    ['loc' => 'categories.php', 'priority' => '0.75', 'changefreq' => 'weekly'],
    ['loc' => 'about.php', 'priority' => '0.60', 'changefreq' => 'monthly'],
    ['loc' => 'contact.php', 'priority' => '0.60', 'changefreq' => 'monthly'],
    ['loc' => 'policy.php', 'priority' => '0.50', 'changefreq' => 'monthly'],
    ['loc' => 'privacy.php', 'priority' => '0.50', 'changefreq' => 'monthly'],
    ['loc' => 'terms.php', 'priority' => '0.50', 'changefreq' => 'monthly'],
    ['loc' => 'track_order.php', 'priority' => '0.50', 'changefreq' => 'monthly'],
];

$now = date('Y-m-d');

$products = [];
$categories = [];

$pdo = medal_pdo();
if ($pdo) {
    try {
        $products = $pdo->query(
            "SELECT slug, updated_at FROM products WHERE is_active = 1 ORDER BY id DESC"
        )->fetchAll();

        $categories = $pdo->query(
            "SELECT slug, updated_at FROM categories ORDER BY sort_order ASC, id ASC"
        )->fetchAll();
    } catch (Throwable $e) {
        error_log('Error in sitemap.php: ' . $e->getMessage());
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<?php foreach ($pages as $page): ?>
    <url>
        <loc><?= htmlspecialchars($base . $page['loc'], ENT_QUOTES, 'UTF-8') ?></loc>
        <lastmod><?= $now ?></lastmod>
        <changefreq><?= $page['changefreq'] ?></changefreq>
        <priority><?= $page['priority'] ?></priority>
    </url>
<?php endforeach; ?>

<?php foreach ($products as $product): ?>
    <url>
        <loc><?= htmlspecialchars($base . 'product/' . $product['slug'], ENT_QUOTES, 'UTF-8') ?></loc>
        <lastmod><?= htmlspecialchars($product['updated_at'] ? substr($product['updated_at'], 0, 10) : $now, ENT_QUOTES, 'UTF-8') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.70</priority>
    </url>
<?php endforeach; ?>

<?php foreach ($categories as $cat): ?>
    <url>
        <loc><?= htmlspecialchars($base . 'category/' . $cat['slug'], ENT_QUOTES, 'UTF-8') ?></loc>
        <lastmod><?= htmlspecialchars($cat['updated_at'] ? substr($cat['updated_at'], 0, 10) : $now, ENT_QUOTES, 'UTF-8') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.75</priority>
    </url>
<?php endforeach; ?>

</urlset>