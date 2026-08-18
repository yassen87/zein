<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';
$pageTitle = t('bottom_nav_categories');
require __DIR__ . '/includes/header.php';

$pdo = medal_pdo();
$categories = [];
if ($pdo) {
    try {
        $categories = $pdo->query("SELECT * FROM categories WHERE slug NOT IN ('gifts', 'gift', 'hadiya') ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (Throwable $e) {}
}
?>

<div class="container" style="padding-top: 120px; padding-bottom: 100px; font-family: 'Tajawal', sans-serif;">
    <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 2rem; text-align: center; color: #111;"><?= esc(t('bottom_nav_categories')) ?></h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
        <?php foreach ($categories as $cat): 
            $catName = current_lang() === 'ar' ? $cat['name_ar'] : $cat['name_en'];
            $emoji = get_category_emoji($cat['slug']);
        ?>
            <a href="<?= esc(url('products.php?cat=' . $cat['slug'])) ?>" style="text-decoration: none; color: inherit; display: block;">
                <div style="background: #fff; padding: 2.5rem 2rem; border-radius: 20px; text-align: center; border: 1px solid #f0f0f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s ease;" class="category-card-hover">
                    <div style="font-size: 3rem; margin-bottom: 1rem;"><?= $emoji ?></div>
                    <h3 style="font-size: 1.2rem; font-weight: 600; color: #222; margin: 0;"><?= esc($catName) ?></h3>
                </div>
            </a>
        <?php endforeach; ?>

        <?php if (has_any_offers()): ?>
        <!-- Offers Card -->
        <a href="<?= esc(url('offers.php')) ?>" style="text-decoration: none; color: inherit; display: block;">
            <div style="background: #fff; padding: 2.5rem 2rem; border-radius: 20px; text-align: center; border: 1px solid #f0f0f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s ease;" class="category-card-hover">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🏷️</div>
                <h3 style="font-size: 1.2rem; font-weight: 600; color: #222; margin: 0;"><?= current_lang() === 'ar' ? 'العروض الحصرية' : 'Exclusive Offers' ?></h3>
            </div>
        </a>
        <?php endif; ?>
        
        <!-- Track Order Card -->
        <a href="<?= esc(url('track_order.php')) ?>" style="text-decoration: none; color: inherit; display: block;">
            <div style="background: #fff; padding: 2.5rem 2rem; border-radius: 20px; text-align: center; border: 1px solid #f0f0f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s ease;" class="category-card-hover">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                <h3 style="font-size: 1.2rem; font-weight: 600; color: #222; margin: 0;"><?= current_lang() === 'ar' ? 'تتبع طلبك' : 'Track Your Order' ?></h3>
            </div>
        </a>
    </div>
</div>

<style>
.category-card-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.category-card-hover:hover {
    transform: translateY(-8px);
    border-color: #c5a059 !important;
    box-shadow: 0 10px 25px rgba(197, 160, 89, 0.1) !important;
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
