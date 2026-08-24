<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'بث الإعلانات والمنتجات عبر الواتساب (WhatsApp Broadcast)';
$activeMenu = 'broadcast';

$pdo = medal_pdo();
if (!$pdo) {
    die('Database connection failed.');
}

// Fetch total distinct customer phone numbers
$countSt = $pdo->query(
    "SELECT COUNT(DISTINCT phone) FROM (
        SELECT phone FROM clients WHERE phone IS NOT NULL AND phone != ''
        UNION 
        SELECT customer_phone as phone FROM orders WHERE customer_phone IS NOT NULL AND customer_phone != ''
    ) as t WHERE phone REGEXP '^[0-9+]{8,15}$'"
);
$totalCustomers = (int)($countSt->fetchColumn() ?: 0);

// Fetch all active products with variants, images, categories, and brands
$productsSql = "
    SELECT 
        p.id, 
        p.name_ar, 
        p.name_en, 
        p.slug, 
        p.primary_image_key, 
        p.category, 
        p.notes_ar, 
        p.notes_en, 
        p.description_ar, 
        p.description_en,
        b.name_ar AS brand_name_ar,
        b.name_en AS brand_name_en,
        COALESCE(
            (SELECT MIN(price) FROM product_variants WHERE product_id = p.id),
            0.0
        ) AS min_price,
        COALESCE(
            (SELECT GROUP_CONCAT(category_slug) FROM product_categories WHERE product_id = p.id),
            p.category
        ) AS all_categories
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.active = 1
    ORDER BY p.sort_order ASC, p.id DESC
";
$productsSt = $pdo->query($productsSql);
$products = $productsSt->fetchAll();

// Prepare product JSON for instant client-side interactive search & live preview
$productsJsonList = [];
foreach ($products as $p) {
    $imgKey = (string)($p['primary_image_key'] ?? '');
    $imgUrl = '';
    if (!empty($imgKey) && $imgKey !== 'default') {
        if (str_starts_with($imgKey, 'http://') || str_starts_with($imgKey, 'https://')) {
            $imgUrl = $imgKey;
        } elseif (str_starts_with($imgKey, 'img_') || str_contains($imgKey, '.')) {
            $imgUrl = storefront_url('assets/uploads/' . ltrim($imgKey, '/'));
        } else {
            $imgUrl = storefront_url('assets/img/' . $imgKey . '.jpg');
        }
    }
    $productsJsonList[] = [
        'id' => (int)$p['id'],
        'nameAr' => $p['name_ar'],
        'nameEn' => $p['name_en'] ?: '',
        'slug' => $p['slug'],
        'brand' => $p['brand_name_ar'] ?: ($p['brand_name_en'] ?: ''),
        'category' => $p['category'] ?: 'unisex',
        'allCategories' => $p['all_categories'] ?: '',
        'price' => (float)$p['min_price'],
        'description' => $p['description_ar'] ?: ($p['notes_ar'] ?: ''),
        'notes' => $p['notes_ar'] ?: '',
        'image' => $imgUrl,
        'url' => storefront_url('product.php?id=' . (int)$p['id'])
    ];
}

// Handle broadcast form submission
$broadcastResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_broadcast') {
    admin_verify_csrf();
    
    $productId = (int)($_POST['product_id'] ?? 0);
    $customMessage = trim((string)($_POST['custom_message'] ?? ''));

    require_once __DIR__ . '/../includes/whatsapp_helper.php';

    if ($productId > 0) {
        $pSt = $pdo->prepare('
            SELECT p.*, b.name_ar as brand_name_ar, b.name_en as brand_name_en,
                   (SELECT MIN(price) FROM product_variants WHERE product_id = p.id) as min_price
            FROM products p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.id = ? LIMIT 1
        ');
        $pSt->execute([$productId]);
        $prod = $pSt->fetch();

        if ($prod) {
            $recipSt = $pdo->query("SELECT DISTINCT phone FROM (SELECT phone FROM clients WHERE phone IS NOT NULL AND phone != '' UNION SELECT customer_phone as phone FROM orders WHERE customer_phone IS NOT NULL AND customer_phone != '') as t WHERE phone REGEXP '^[0-9+]{8,15}$' LIMIT 500");
            $recipientsList = $recipSt ? ($recipSt->fetchAll(PDO::FETCH_COLUMN) ?: []) : [];

            $payload = [
                'productId' => $prod['id'],
                'nameAr' => $prod['name_ar'],
                'nameEn' => $prod['name_en'],
                'brandName' => $prod['brand_name_ar'] ?: $prod['brand_name_en'],
                'description' => $prod['description_ar'] ?: $prod['notes_ar'],
                'notes' => $prod['notes_ar'],
                'price' => (float)($prod['min_price'] ?? 0),
                'slug' => $prod['slug'],
                'productUrl' => storefront_url('product.php?id=' . (int)$prod['id']),
                'customMessage' => $customMessage,
                'recipients' => $recipientsList
            ];
            $broadcastResult = broadcast_whatsapp_new_product($payload);
        }
    }
}

require __DIR__ . '/_layout_start.php';
?>

<style>
/* Pure Vanilla CSS - Luxury Zein Admin Design System */
.bc-container {
    max-width: 1350px;
    margin: 0 auto;
    padding: 1.5rem 1rem;
    font-family: inherit;
    box-sizing: border-box;
}

/* Hero Header Card */
.bc-hero {
    background: linear-gradient(135deg, #0b0f19 0%, #1e293b 100%);
    border: 1.5px solid rgba(212, 175, 55, 0.4);
    border-radius: 20px;
    padding: 2rem;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}
.bc-hero-info {
    flex: 1;
}
.bc-hero-badge-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.bc-gold-badge {
    background: rgba(212, 175, 55, 0.15);
    border: 1px solid #d4af37;
    color: #f59e0b;
    padding: 0.35rem 0.85rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.bc-live-badge {
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid #10b981;
    color: #34d399;
    padding: 0.35rem 0.85rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
.bc-pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 8px #10b981;
}
.bc-hero-title {
    font-size: 1.65rem;
    font-weight: 900;
    color: #ffffff;
    margin: 0 0 0.5rem 0;
}
.bc-hero-desc {
    font-size: 0.85rem;
    color: #94a3b8;
    margin: 0;
    line-height: 1.5;
    max-width: 650px;
}
.bc-hero-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.bc-stat-box {
    background: rgba(11, 15, 25, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.1rem 1.5rem;
    text-align: center;
    min-width: 140px;
}
.bc-stat-label {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 600;
}
.bc-stat-num {
    font-size: 1.6rem;
    font-weight: 900;
    color: #f59e0b;
    margin-top: 0.25rem;
}
.bc-bot-btn {
    background: #059669;
    color: #ffffff;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 0.85rem 1.25rem;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    transition: background 0.2s;
}
.bc-bot-btn:hover {
    background: #047857;
    color: #ffffff;
}

/* Alert Boxes */
.bc-alert-success {
    background: #ecfdf5;
    border: 1.5px solid #a7f3d0;
    color: #065f46;
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.bc-alert-error {
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    color: #991b1b;
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

/* Main Grid Layout */
.bc-grid {
    display: grid;
    grid-template-columns: 1.3fr 0.9fr;
    gap: 1.75rem;
    align-items: start;
}

/* Card Sections */
.bc-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}
.bc-card-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 1rem 0;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Search Box */
.bc-search-wrap {
    position: relative;
    margin-bottom: 1rem;
}
.bc-search-input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.85rem 1rem 0.85rem 2.75rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.85rem;
    color: #0f172a;
    outline: none;
    transition: all 0.2s;
}
.bc-search-input:focus {
    background: #ffffff;
    border-color: #d4af37;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
}
.bc-search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1rem;
}

/* Category Filter Tabs */
.bc-cat-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.bc-cat-btn {
    background: #f1f5f9;
    color: #475569;
    border: none;
    padding: 0.4rem 0.9rem;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.bc-cat-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
}
.bc-cat-btn.active {
    background: #0f172a;
    color: #ffffff;
}

/* Product Scroll Grid */
.bc-product-list {
    max-height: 400px;
    overflow-y: auto;
    padding-left: 0.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}
.bc-product-list::-webkit-scrollbar {
    width: 6px;
}
.bc-product-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

/* Product Card */
.bc-product-card {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.bc-product-card:hover {
    background: #ffffff;
    border-color: #d4af37;
    transform: translateX(-3px);
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.12);
}
.bc-product-card.selected {
    background: #f0fdf4;
    border-color: #10b981;
    box-shadow: 0 0 0 2px #10b981;
}
.bc-product-img {
    width: 54px;
    height: 54px;
    border-radius: 10px;
    object-fit: cover;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}
.bc-product-meta {
    flex: 1;
    min-width: 0;
}
.bc-product-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
}
.bc-product-title {
    font-size: 0.85rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bc-product-price {
    font-size: 0.85rem;
    font-weight: 900;
    color: #059669;
    flex-shrink: 0;
}
.bc-product-sub {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0.15rem 0 0 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bc-product-notes {
    font-size: 0.72rem;
    color: #b45309;
    margin: 0.2rem 0 0 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bc-check-circle {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 1.5px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
    font-size: 0.75rem;
    font-weight: bold;
    flex-shrink: 0;
    transition: all 0.2s;
}
.bc-product-card.selected .bc-check-circle {
    background: #10b981;
    border-color: #10b981;
    color: #ffffff;
}

/* Inputs & Form Controls */
.bc-form-group {
    margin-bottom: 1.25rem;
}
.bc-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.4rem;
}
.bc-input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.85rem;
    color: #0f172a;
    outline: none;
    transition: border-color 0.2s;
}
.bc-input:focus {
    border-color: #d4af37;
    background: #ffffff;
}
.bc-textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 1rem;
    background: #0b0f19;
    color: #34d399;
    border: 1.5px solid #1e293b;
    border-radius: 14px;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 0.85rem;
    line-height: 1.6;
    outline: none;
    resize: vertical;
    min-height: 180px;
}
.bc-textarea:focus {
    border-color: #d4af37;
}

/* Tips Box */
.bc-tips-box {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 12px;
    padding: 0.85rem 1rem;
    font-size: 0.75rem;
    color: #92400e;
    margin-bottom: 1.25rem;
    line-height: 1.5;
}
.bc-tips-box p {
    margin: 0.25rem 0;
}

/* Submit Button */
.bc-btn-broadcast {
    width: 100%;
    padding: 1.1rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    border: none;
    border-radius: 14px;
    font-size: 0.95rem;
    font-weight: 800;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
    transition: all 0.2s;
}
.bc-btn-broadcast:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(16, 185, 129, 0.35);
}
.bc-btn-broadcast:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Phone Mockup Screen */
.bc-phone-wrapper {
    position: sticky;
    top: 1.5rem;
}
.bc-phone-frame {
    background: #0b141a;
    border: 10px solid #1f2c34;
    border-radius: 36px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}
.bc-phone-header {
    background: #202c33;
    padding: 0.85rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #111b21;
}
.bc-phone-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #d4af37;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.bc-phone-title {
    font-size: 0.85rem;
    font-weight: 800;
    color: #e9edef;
    margin: 0;
}
.bc-phone-status {
    font-size: 0.7rem;
    color: #00a884;
    margin: 0;
}
.bc-phone-body {
    background-color: #0b141a;
    background-image: radial-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px);
    background-size: 18px 18px;
    padding: 1.25rem;
    min-height: 480px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    box-sizing: border-box;
}
.bc-msg-bubble {
    background: #005c4b;
    border-radius: 18px 4px 18px 18px;
    padding: 0.85rem 1rem;
    color: #e9edef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    max-width: 95%;
    margin-right: auto;
    font-size: 0.82rem;
    line-height: 1.55;
    box-sizing: border-box;
}
.bc-msg-img {
    width: 100%;
    max-height: 180px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 0.65rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}
.bc-msg-text {
    white-space: pre-line;
    word-break: break-word;
}
.bc-msg-time {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.65rem;
    color: rgba(233, 237, 239, 0.6);
    margin-top: 0.4rem;
}
.bc-phone-footer {
    background: #202c33;
    padding: 0.65rem 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.bc-fake-input {
    background: #2a3942;
    border-radius: 20px;
    padding: 0.5rem 0.85rem;
    color: #8696a0;
    font-size: 0.75rem;
    flex: 1;
}

@media (max-width: 992px) {
    .bc-grid {
        grid-template-columns: 1fr;
    }
    .bc-hero {
        flex-direction: column;
        align-items: flex-start;
    }
    .bc-hero-stats {
        width: 100%;
    }
    .bc-stat-box {
        flex: 1;
    }
}
</style>

<div class="bc-container">
    
    <!-- Top Hero Banner -->
    <div class="bc-hero">
        <div class="bc-hero-info">
            <div class="bc-hero-badge-row">
                <span class="bc-gold-badge">
                    <span>👑</span> ZEI BROADCAST STUDIO
                </span>
                <span class="bc-live-badge">
                    <span class="bc-pulse-dot"></span> البوت متصل وجاهز
                </span>
            </div>
            <h1 class="bc-hero-title">
                📢 استوديو بث إعلانات وعطور الواتساب
            </h1>
            <p class="bc-hero-desc">
                إرسال رسائل ترويجية احترافية بتفاصيل العطر الحقيقية (الاسم، الوصف، المكونات، السعر والرابط المباشر) إلى هواتف جميع عملائك بنقرة زر واحدة.
            </p>
        </div>

        <div class="bc-hero-stats">
            <div class="bc-stat-box">
                <div class="bc-stat-label">العملاء المؤهلين للاستلام</div>
                <div class="bc-stat-num"><?= number_format($totalCustomers) ?> <span style="font-size:0.75rem; font-weight:normal; color:#94a3b8;">عميل</span></div>
            </div>
            <a href="whatsapp_bot.php" class="bc-bot-btn">
                <span>💬</span> لوحة البوت
            </a>
        </div>
    </div>

    <?php if ($broadcastResult): ?>
        <?php if (!empty($broadcastResult['success'])): ?>
            <div class="bc-alert-success">
                <span style="font-size: 2rem;">🚀</span>
                <div>
                    <h4 style="margin:0 0 0.25rem 0; font-weight:800; font-size:0.95rem;">تم بدء حملة البث بنجاح في الخلفية!</h4>
                    <p style="margin:0; font-size:0.8rem; line-height:1.4;">يقوم البوت الآن بإرسال الرسائل لـ (<?= $totalCustomers ?>) عميل بفاصل زمني آمن 2.5 ثانية لحماية الرقم من الحظر.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="bc-alert-error">
                <h4 style="margin:0 0 0.25rem 0; font-weight:800; font-size:0.95rem;">تعذر بدء البث</h4>
                <p style="margin:0; font-size:0.8rem;"><?= htmlspecialchars($broadcastResult['curl_error'] ?: 'تأكد من تشغيل البوت ومسح الـ QR.') ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST" action="broadcast.php" id="broadcastForm">
        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
        <input type="hidden" name="action" value="send_broadcast">
        <input type="hidden" name="product_id" id="selected_product_id" value="">

        <div class="bc-grid">
            
            <!-- LEFT COLUMN: Product Selector & Message Customizer -->
            <div>
                
                <!-- 1. Product Search & Visual Selector Card -->
                <div class="bc-card">
                    <div class="bc-card-title">
                        <span>🔍 1. اختر العطر أو المنتج المراد الإعلان عنه</span>
                        <span id="productCounter" style="font-size:0.75rem; color:#94a3b8; font-weight:bold;">
                            <?= count($products) ?> منتج متاح
                        </span>
                    </div>

                    <!-- Instant Search Input -->
                    <div class="bc-search-wrap">
                        <input type="text" id="productSearchInput" placeholder="اكتب اسم العطر بالعربي أو الإنجليزي أو الماركة للبحث الفوري..." class="bc-search-input">
                        <span class="bc-search-icon">🔎</span>
                    </div>

                    <!-- Category Filter Tabs -->
                    <div class="bc-cat-tabs">
                        <button type="button" class="bc-cat-btn active" data-cat="all">الكل</button>
                        <button type="button" class="bc-cat-btn" data-cat="unisex">للجنسين</button>
                        <button type="button" class="bc-cat-btn" data-cat="men">رجالي</button>
                        <button type="button" class="bc-cat-btn" data-cat="women">نسائي</button>
                        <button type="button" class="bc-cat-btn" data-cat="offers">العروض</button>
                    </div>

                    <!-- Visual Product Cards Scrollable Grid -->
                    <div class="bc-product-list" id="productListContainer">
                        <?php foreach ($productsJsonList as $idx => $item): ?>
                            <div class="bc-product-card" 
                                 data-id="<?= $item['id'] ?>"
                                 data-name-ar="<?= esc($item['nameAr']) ?>"
                                 data-name-en="<?= esc($item['nameEn']) ?>"
                                 data-brand="<?= esc($item['brand']) ?>"
                                 data-price="<?= $item['price'] ?>"
                                 data-desc="<?= esc($item['description']) ?>"
                                 data-notes="<?= esc($item['notes']) ?>"
                                 data-image="<?= esc($item['image']) ?>"
                                 data-url="<?= esc($item['url']) ?>"
                                 data-categories="<?= esc($item['allCategories'] . ' ' . $item['category']) ?>"
                                 onclick="selectProduct(this)">
                                
                                <img src="<?= !empty($item['image']) ? esc($item['image']) : '../assets/img/logo.png' ?>" 
                                     class="bc-product-img" alt="<?= esc($item['nameAr']) ?>"
                                     onerror="this.src='../assets/img/logo.png'">

                                <div class="bc-product-meta">
                                    <div class="bc-product-header">
                                        <h4 class="bc-product-title"><?= esc($item['nameAr']) ?></h4>
                                        <span class="bc-product-price">
                                            <?= $item['price'] > 0 ? number_format($item['price'], 2) . ' ج.م' : 'سعر مخصص' ?>
                                        </span>
                                    </div>
                                    <p class="bc-product-sub"><?= esc($item['nameEn'] ?: $item['brand']) ?></p>
                                    <?php if (!empty($item['notes'])): ?>
                                        <p class="bc-product-notes">🌸 <?= esc($item['notes']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="bc-check-circle">
                                    ✓
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. Message Customizer Card -->
                <div class="bc-card">
                    <div class="bc-card-title">
                        <span>✍️ 2. تخصيص وتحرير نص الرسالة الترويجية</span>
                        <button type="button" onclick="regenerateMessage()" style="background:none; border:none; color:#d97706; font-size:0.75rem; font-weight:bold; cursor:pointer;">
                            ↺ إعادة إنشاء النص التلقائي
                        </button>
                    </div>

                    <div class="bc-form-group">
                        <label class="bc-label">نص العرض الترويجي أو كود الخصم (اختياري):</label>
                        <input type="text" id="promoInput" placeholder="مثال: خصم 15% حصري لأول 50 عميل باستخدام كود ZEI15" class="bc-input">
                    </div>

                    <div class="bc-form-group">
                        <label class="bc-label">نص الرسالة النهائي الذي سيصل للعملاء (يمكنك التعديل عليه):</label>
                        <textarea name="custom_message" id="finalMessageTextarea" class="bc-textarea" placeholder="اختر منتجاً من الأعلى وسيتم إنشاء الرسالة الفاخرة تلقائياً هنا..."></textarea>
                    </div>

                    <!-- Safety Reminder -->
                    <div class="bc-tips-box">
                        <p style="font-weight:800; margin-bottom:0.35rem;">🛡️ ضمان أمان حساب الواتساب:</p>
                        <p>• النظام يرسل الرسائل مع فاصل زمني (2.5 ثانية) لمنع أي حظر للحساب.</p>
                        <p>• الرسالة ستصل لكافة العملاء المسجلين في المتجر (<?= $totalCustomers ?> عميل).</p>
                    </div>

                    <!-- Submit Button -->
                    <button type="button" id="submitBroadcastBtn" onclick="confirmAndSend()" class="bc-btn-broadcast">
                        <span>🚀</span>
                        <span>بدء إرسال البث لجميع العملاء (<?= $totalCustomers ?>) عبر الواتساب</span>
                    </button>
                </div>

            </div>

            <!-- RIGHT COLUMN: Ultra-Realistic Live WhatsApp Phone Preview -->
            <div>
                <div class="bc-phone-wrapper">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; padding:0 0.25rem;">
                        <span style="font-size:0.8rem; font-weight:800; color:#334155; display:flex; align-items:center; gap:0.35rem;">
                            <span>📱</span> معاينة حية لشاشة واتساب العميل
                        </span>
                        <span style="font-size:0.7rem; color:#94a3b8; font-weight:bold;">Live Preview</span>
                    </div>

                    <!-- Phone Frame -->
                    <div class="bc-phone-frame">
                        <!-- WA Top Header -->
                        <div class="bc-phone-header">
                            <div style="display:flex; align-items:center; gap:0.65rem;">
                                <div class="bc-phone-avatar">
                                    👑
                                </div>
                                <div>
                                    <div class="bc-phone-title">
                                        متجر زين للعطور <span style="color:#00a884; font-size:0.75rem;">✓</span>
                                    </div>
                                    <div class="bc-phone-status">متصل الآن (Online)</div>
                                </div>
                            </div>
                            <div style="color:#8696a0; font-size:0.85rem; display:flex; gap:0.75rem;">
                                <span>📞</span>
                                <span>⋮</span>
                            </div>
                        </div>

                        <!-- Chat Body -->
                        <div class="bc-phone-body">
                            <!-- Date Badge -->
                            <div style="text-align:center; margin-bottom:1rem;">
                                <span style="background:#182229; color:#8696a0; font-size:0.7rem; padding:0.25rem 0.75rem; border-radius:8px;">اليوم</span>
                            </div>

                            <!-- Live Message Bubble -->
                            <div class="bc-msg-bubble">
                                <!-- Image Preview inside Bubble -->
                                <div id="previewImageWrapper" style="display:none; margin-bottom:0.5rem;">
                                    <img id="previewImage" src="" class="bc-msg-img">
                                </div>

                                <!-- Formatted Text -->
                                <div id="previewText" class="bc-msg-text">
                                    👈 يرجى اختيار عطر من القائمة على اليمين للمعاينة الحية...
                                </div>

                                <!-- Time & Double Checkmark -->
                                <div class="bc-msg-time">
                                    <span><?= date('H:i') ?></span>
                                    <span style="color:#53bdeb;">✓✓</span>
                                </div>
                            </div>
                        </div>

                        <!-- WA Bottom Bar Mockup -->
                        <div class="bc-phone-footer">
                            <div class="bc-fake-input">اكتب رسالة...</div>
                            <div style="width:32px; height:32px; border-radius:50%; background:#00a884; display:flex; align-items:center; justify-content:center; color:#fff; font-size:0.85rem;">🎙️</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
var currentSelectedProduct = null;

function selectProduct(cardElement) {
    // Remove previous selection highlight
    document.querySelectorAll('.bc-product-card').forEach(c => {
        c.classList.remove('selected');
    });

    // Highlight clicked card
    cardElement.classList.add('selected');

    // Save selected data
    currentSelectedProduct = {
        id: cardElement.getAttribute('data-id'),
        nameAr: cardElement.getAttribute('data-name-ar'),
        nameEn: cardElement.getAttribute('data-name-en'),
        brand: cardElement.getAttribute('data-brand'),
        price: cardElement.getAttribute('data-price'),
        desc: cardElement.getAttribute('data-desc'),
        notes: cardElement.getAttribute('data-notes'),
        image: cardElement.getAttribute('data-image'),
        url: cardElement.getAttribute('data-url')
    };

    document.getElementById('selected_product_id').value = currentSelectedProduct.id;

    // Generate & update message
    regenerateMessage();
}

function regenerateMessage() {
    if (!currentSelectedProduct) return;

    var promo = document.getElementById('promoInput').value.trim();
    var p = currentSelectedProduct;

    var lines = [];
    lines.push('✨ *عطر جديد وحصري وصل متجر زين للعطور!* 🌸');
    lines.push('');
    lines.push('👑 *' + p.nameAr + '*' + (p.nameEn ? ' | ' + p.nameEn : ''));
    if (p.brand) {
        lines.push('🏷️ *الماركة:* ' + p.brand);
    }
    if (p.desc) {
        lines.push('📝 *عن العطر:* ' + p.desc);
    }
    if (p.notes) {
        lines.push('🌸 *النفحات العطرية:* ' + p.notes);
    }
    if (parseFloat(p.price) > 0) {
        lines.push('💰 *السعر:* *' + parseFloat(p.price).toFixed(2) + ' ج.م*');
    }
    if (promo) {
        lines.push('');
        lines.push('🎁 *عرض خاص:* ' + promo);
    }
    lines.push('');
    lines.push('🔗 *للطلب والتفاصيل فوراً عبر موقعنا:*');
    lines.push(p.url);
    lines.push('');
    lines.push('🚚 *متاح الشحن والتوصيل السريع لجميع المحافظات!* ✨');

    var fullText = lines.join('\n');
    document.getElementById('finalMessageTextarea').value = fullText;
    updateLivePreview(fullText);
}

function updateLivePreview(text) {
    document.getElementById('previewText').innerText = text;

    var imgWrapper = document.getElementById('previewImageWrapper');
    var imgElem = document.getElementById('previewImage');
    if (currentSelectedProduct && currentSelectedProduct.image) {
        imgElem.src = currentSelectedProduct.image;
        imgWrapper.style.display = 'block';
    } else {
        imgWrapper.style.display = 'none';
    }
}

// Live typing update from Textarea to Phone Screen
document.getElementById('finalMessageTextarea').addEventListener('input', function() {
    updateLivePreview(this.value);
});

document.getElementById('promoInput').addEventListener('input', function() {
    regenerateMessage();
});

// Live Instant Search Filter
document.getElementById('productSearchInput').addEventListener('input', function() {
    var query = this.value.toLowerCase().trim();
    var cards = document.querySelectorAll('.bc-product-card');
    var visibleCount = 0;

    cards.forEach(card => {
        var nameAr = (card.getAttribute('data-name-ar') || '').toLowerCase();
        var nameEn = (card.getAttribute('data-name-en') || '').toLowerCase();
        var brand = (card.getAttribute('data-brand') || '').toLowerCase();
        var notes = (card.getAttribute('data-notes') || '').toLowerCase();

        if (nameAr.includes(query) || nameEn.includes(query) || brand.includes(query) || notes.includes(query)) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    document.getElementById('productCounter').innerText = visibleCount + ' منتج مطابق';
});

// Category Filter Tabs
document.querySelectorAll('.bc-cat-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.bc-cat-btn').forEach(b => {
            b.classList.remove('active');
        });
        this.classList.add('active');

        var cat = this.getAttribute('data-cat');
        var cards = document.querySelectorAll('.bc-product-card');
        var visibleCount = 0;

        cards.forEach(card => {
            var categories = (card.getAttribute('data-categories') || '').toLowerCase();
            if (cat === 'all' || categories.includes(cat)) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        document.getElementById('productCounter').innerText = visibleCount + ' منتج';
    });
});

function confirmAndSend() {
    var prodId = document.getElementById('selected_product_id').value;
    if (!prodId) {
        alert('⚠️ يرجى اختيار العطر أو المنتج أولاً من القائمة!');
        return;
    }

    var total = <?= $totalCustomers ?>;
    var confirmMsg = 'هل أنت متأكد من بدء إرسال هذا الإعلان بالواتساب إلى (' + total + ') عميل مسجل بالمتجر؟';
    if (confirm(confirmMsg)) {
        var btn = document.getElementById('submitBroadcastBtn');
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span> جاري بدء الحملة في الخلفية...';
        document.getElementById('broadcastForm').submit();
    }
}

// Auto-select first product on page load
document.addEventListener('DOMContentLoaded', function() {
    var firstCard = document.querySelector('.bc-product-card');
    if (firstCard) {
        selectProduct(firstCard);
    }
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
