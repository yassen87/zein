<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

$pageTitle = '404 - Page Not Found';
$pageDescription = current_lang() === 'ar' ? 'الصفحة التي تبحث عنها غير موجودة.' : 'The page you are looking for does not exist.';
$noindex = true;
$isAr = current_lang() === 'ar';

http_response_code(404);

require __DIR__ . '/includes/header.php';
?>

<style>
.error-404 {
    padding: clamp(3rem, 8vw, 6rem) 0 clamp(3rem, 6vw, 5rem);
    text-align: center;
    background: linear-gradient(180deg, var(--bg-elevated) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
    position: relative;
    overflow: hidden;
    min-height: 60vh;
    display: flex;
    align-items: center;
}
.error-404::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 70% 60% at 50% 0%, var(--gold-glow), transparent 65%);
    pointer-events: none;
}
.error-404__container {
    position: relative;
    z-index: 1;
    max-width: 600px;
    margin: 0 auto;
    padding: 0 var(--space);
}
.error-404__code {
    font-family: var(--font-serif);
    font-size: clamp(6rem, 12vw, 10rem);
    font-weight: 700;
    line-height: 1;
    background: linear-gradient(135deg, var(--gold-bright), var(--gold) 55%, var(--gold-dim));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin: 0;
}
.error-404__title {
    font-family: var(--font-serif);
    font-size: clamp(1.4rem, 3vw, 2rem);
    font-weight: 700;
    color: var(--ink);
    margin: 0.5rem 0 0.8rem;
}
.error-404__text {
    color: var(--ink-muted);
    font-size: 1rem;
    line-height: 1.7;
    margin: 0 0 1.8rem;
}
.error-404__search {
    display: flex;
    gap: 0.5rem;
    max-width: 420px;
    margin: 0 auto 2.5rem;
}
.error-404__search input {
    flex: 1;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-full);
    color: var(--ink);
    font-family: var(--font-sans);
    font-size: 0.95rem;
    padding: 0.8rem 1.2rem;
    outline: none;
    transition: border-color 0.25s, box-shadow 0.25s;
}
.error-404__search input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px var(--gold-glow);
}
.error-404__search button {
    background: linear-gradient(145deg, var(--gold-bright), var(--gold) 50%, var(--gold-dim));
    color: #1a1508;
    border: none;
    border-radius: var(--radius-full);
    padding: 0 1.5rem;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    font-family: var(--font-sans);
    transition: all 0.25s ease;
    box-shadow: 0 4px 16px var(--gold-glow);
}
.error-404__search button:hover {
    filter: brightness(1.08);
    transform: translateY(-2px);
}
.error-404__links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.7rem;
    justify-content: center;
    margin-bottom: 1.2rem;
}
.error-404__links a {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.6rem 1.2rem;
    border-radius: var(--radius-full);
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    color: var(--ink);
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 500;
    transition: all 0.25s;
}
.error-404__links a:hover {
    border-color: var(--gold);
    color: var(--gold);
    background: var(--accent-soft);
}
.error-404__home {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(145deg, var(--gold-bright), var(--gold) 50%, var(--gold-dim));
    color: #1a1508 !important;
    padding: 0.85rem 2rem;
    border-radius: var(--radius-full);
    font-weight: 800;
    font-size: 0.92rem;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px var(--gold-glow);
}
.error-404__home:hover {
    filter: brightness(1.08);
    transform: translateY(-2px);
}
</style>

<section class="error-404">
    <div class="error-404__container">
        <h1 class="error-404__code">404</h1>
        <h2 class="error-404__title">
            <?= $isAr ? 'الصفحة غير موجودة' : 'Page Not Found' ?>
        </h2>
        <p class="error-404__text">
            <?= $isAr
                ? 'عذراً، الصفحة التي تبحث عنها غير موجودة. ربما تم نقلها أو حذفها أو أن الرابط غير صحيح.'
                : "Sorry, the page you're looking for doesn't exist. It may have been moved, deleted, or the link may be incorrect." ?>
        </p>

        <form class="error-404__search" action="<?= esc(url('products.php')) ?>" method="GET">
            <input type="hidden" name="lang" value="<?= esc(current_lang()) ?>">
            <input type="search" name="q" placeholder="<?= $isAr ? 'ابحث عن عطر...' : 'Search for a fragrance...' ?>" required>
            <button type="submit"><?= $isAr ? 'بحث' : 'Search' ?></button>
        </form>

        <div class="error-404__links">
            <a href="<?= esc(url('products.php')) ?>">
                🛍 <?= $isAr ? 'جميع المنتجات' : 'All Products' ?>
            </a>
            <?php if (has_any_offers()): ?>
            <a href="<?= esc(url('offers.php')) ?>">
                🏷️ <?= $isAr ? 'العروض' : 'Offers' ?>
            </a>
            <?php endif; ?>
            <a href="<?= esc(url('brands.php')) ?>">
                ✨ <?= $isAr ? 'الماركات' : 'Brands' ?>
            </a>
            <a href="<?= esc(url('contact.php')) ?>">
                📞 <?= $isAr ? 'تواصل معنا' : 'Contact Us' ?>
            </a>
        </div>

        <a href="<?= esc(url('index.php')) ?>" class="error-404__home">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <?= $isAr ? '<path d="M15 18l-6-6 6-6"/>' : '<path d="M9 18l6-6-6-6"/>' ?>
            </svg>
            <?= $isAr ? 'العودة للرئيسية' : 'Back to Homepage' ?>
        </a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>