<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'أكواد وقسائم الخصم (Promo Codes Studio)';
$activeMenu = 'promo_codes';

$pdo = medal_pdo();
$rows = [];
$edit = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$error = isset($_GET['err']) ? $_GET['err'] : '';

if ($pdo !== null) {
    try {
        $rows = $pdo->query('SELECT * FROM promo_codes ORDER BY id DESC')->fetchAll();
    } catch (\Exception $e) {
        $rows = [];
    }
    if ($editId > 0) {
        $st = $pdo->prepare('SELECT * FROM promo_codes WHERE id = ?');
        $st->execute([$editId]);
        $edit = $st->fetch();
    }
}

$totalCodes = count($rows);
$activeCodesCount = count(array_filter($rows, fn($r) => !empty($r['active'])));
$totalUsages = array_sum(array_map(fn($r) => (int)$r['used_count'], $rows));

require __DIR__ . '/_layout_start.php';
?>

<style>
/* Luxury Promo Codes Studio - Pure Vanilla CSS */
.promo-wrap {
    max-width: 1300px;
    margin: 0 auto 3rem auto;
    padding: 1rem;
    box-sizing: border-box;
    font-family: inherit;
}
.pc-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border: 1.5px solid rgba(212, 175, 55, 0.4);
    border-radius: 20px;
    padding: 1.75rem 2rem;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
}
.pc-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}
.pc-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.25rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
}
.pc-stat-title {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 0.35rem;
}
.pc-stat-val {
    font-size: 1.5rem;
    font-weight: 900;
    color: #0f172a;
}
.pc-layout-grid {
    display: grid;
    grid-template-columns: 1fr 1.8fr;
    gap: 1.75rem;
    align-items: start;
}
.pc-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.75rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
}
.pc-card-title {
    font-size: 1.1rem;
    font-weight: 900;
    color: #0f172a;
    margin: 0 0 1.25rem 0;
    padding-bottom: 0.75rem;
    border-bottom: 1.5px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.pc-field {
    margin-bottom: 1.2rem;
}
.pc-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 800;
    color: #334155;
    margin-bottom: 0.4rem;
}
.pc-input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.8rem 1rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    font-size: 0.9rem;
    color: #0f172a;
    transition: all 0.2s;
    font-family: inherit;
}
.pc-input:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
}
.btn-gold {
    background: linear-gradient(135deg, #d4af37 0%, #b45309 100%);
    color: #ffffff;
    padding: 0.85rem 1.5rem;
    border-radius: 12px;
    font-weight: 800;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    transition: all 0.2s;
    width: 100%;
    font-size: 0.92rem;
}
.btn-gold:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
}
.pc-table-wrap {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}
.pc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}
.pc-table th {
    background: #0f172a;
    color: #ffffff;
    padding: 0.85rem 1rem;
    text-align: right;
    font-weight: 700;
}
.pc-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.pc-table tr:hover {
    background: #f8fafc;
}
.coupon-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fffbeb;
    border: 1.5px dashed #f59e0b;
    color: #b45309;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    font-weight: 900;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 0.95rem;
    letter-spacing: 1px;
}
.discount-pill {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
    padding: 0.25rem 0.65rem;
    border-radius: 50px;
    font-weight: 800;
    font-size: 0.8rem;
}

@media (max-width: 900px) {
    .pc-layout-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="promo-wrap">

    <!-- Top Hero Banner -->
    <div class="pc-hero">
        <div>
            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.5rem;">
                <span style="background:rgba(212,175,55,0.2); border:1px solid #d4af37; color:#f59e0b; padding:0.25rem 0.75rem; border-radius:50px; font-size:0.75rem; font-weight:800;">
                    🎟️ PROMOTIONS & DISCOUNTS
                </span>
            </div>
            <h1 style="font-size:1.65rem; font-weight:900; margin:0 0 0.35rem 0;">
                🎁 إدارة قسائم وأكواد الخصم الترويجية
            </h1>
            <p style="color:#94a3b8; font-size:0.85rem; margin:0; line-height:1.4;">
                أنشئ أكواد خصم بنسب مئوية مخصصة، وحدد عدد مرات الاستخدام المسموح بها وتفعيلها فوراً لعملائك.
            </p>
        </div>

        <div>
            <button type="button" onclick="generateRandomPromo()" class="btn-gold" style="padding:0.75rem 1.25rem; font-size:0.85rem; width:auto;">
                <span>✨</span> توليد كود خصم عشوائي
            </button>
        </div>
    </div>

    <!-- 3 Stats Cards -->
    <div class="pc-stats-grid">
        <div class="pc-stat-card">
            <div class="pc-stat-title">إجمالي الأكواد المسجلة</div>
            <div class="pc-stat-val" style="color:#0f172a;"><?= number_format($totalCodes) ?></div>
        </div>
        <div class="pc-stat-card">
            <div class="pc-stat-title">الأكواد النشطة حالياً</div>
            <div class="pc-stat-val" style="color:#059669;">✓ <?= number_format($activeCodesCount) ?></div>
        </div>
        <div class="pc-stat-card">
            <div class="pc-stat-title">إجمالي مرات الاستخدام للعملاء</div>
            <div class="pc-stat-val" style="color:#d97706;">🔥 <?= number_format($totalUsages) ?> <span style="font-size:0.8rem; font-weight:normal;">مرة</span></div>
        </div>
    </div>

    <?php if ($error === 'code_in_use'): ?>
        <div style="background:#fef2f2; border:1.5px solid #fecaca; color:#991b1b; padding:1.25rem; border-radius:16px; margin-bottom:1.5rem; font-weight:700;">
            ⚠️ كود الخصم هذا مستخدم بالفعل، يرجى اختيار كود آخر.
        </div>
    <?php endif; ?>

    <!-- 2 Column Layout -->
    <div class="pc-layout-grid">
        
        <!-- Left: Form -->
        <div class="pc-card">
            <h2 class="pc-card-title">
                <span><?= $edit ? '✏️' : '➕' ?></span>
                <?= $edit ? 'تعديل كود الخصم' : 'إنشاء كود خصم جديد' ?>
            </h2>

            <form class="admin-form" method="post" action="<?= esc(admin_url('promo_code_save.php')) ?>">
                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
                
                <div class="pc-field">
                    <label class="pc-label" for="code">
                        <span>كود الخصم (Promo Code) <span style="color:#ef4444;">*</span></span>
                        <a href="javascript:void(0)" onclick="generateRandomPromo()" style="color:#d4af37; font-size:0.75rem; text-decoration:none; font-weight:bold;">توليد تلقائي 🎲</a>
                    </label>
                    <input type="text" id="code" name="code" class="pc-input" required value="<?= esc($edit ? (string) $edit['code'] : '') ?>" style="text-transform: uppercase; font-weight:bold; letter-spacing:1px;" placeholder="مثلاً: ZEIN20, SUMMER">
                </div>

                <div class="pc-field">
                    <label class="pc-label" for="discount_percentage">نسبة الخصم المئوية (%) <span style="color:#ef4444;">*</span></label>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <input type="number" id="discount_percentage" name="discount_percentage" class="pc-input" required value="<?= $edit ? (int) $edit['discount_percentage'] : 15 ?>" min="1" max="100">
                        <span style="font-weight:900; color:#0f172a; font-size:1.1rem;">%</span>
                    </div>
                </div>

                <div class="pc-field">
                    <label class="pc-label" for="usage_limit">
                        <span>الحد الأقصى لمرات الاستخدام</span>
                        <span style="font-size:0.75rem; color:#64748b;">(0 = غير محدود)</span>
                    </label>
                    <input type="number" id="usage_limit" name="usage_limit" class="pc-input" required value="<?= $edit ? (int) $edit['usage_limit'] : 0 ?>" min="0" placeholder="0 لجعله غير محدود">
                </div>

                <div class="pc-field">
                    <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; font-weight:800; font-size:0.9rem; color:#0f172a;">
                        <input type="checkbox" name="active" value="1" <?= (!$edit || !empty($edit['active'])) ? 'checked' : '' ?> style="accent-color:#10b981; width:20px; height:20px;">
                        <span>✓ الكود نشط وجاهز للاستخدام في المتجر</span>
                    </label>
                </div>

                <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
                    <button type="submit" class="btn-gold">
                        <span>💾</span> <?= $edit ? 'حفظ التعديلات' : 'نشر كود الخصم الآن' ?>
                    </button>
                    <?php if ($edit): ?>
                        <a href="<?= esc(admin_url('promo_codes.php')) ?>" style="background:#f1f5f9; color:#475569; padding:0.85rem 1.25rem; border-radius:12px; font-weight:800; text-decoration:none; display:inline-flex; align-items:center;">
                            إلغاء
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Right: Promo Codes Table -->
        <div class="pc-table-wrap">
            <table class="pc-table">
                <thead>
                    <tr>
                        <th>كود الخصم</th>
                        <th style="text-align:center;">نسبة الخصم</th>
                        <th style="text-align:center;">الاستخدام / الحد</th>
                        <th style="text-align:center;">الحالة</th>
                        <th style="text-align:center;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:2.5rem; color:#64748b;">لا توجد أكواد خصم مسجلة حالياً.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <span class="coupon-tag">
                                        <span>🎟️</span> <?= esc((string) $r['code']) ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <span class="discount-pill">
                                        خصم <?= (int) $r['discount_percentage'] ?>%
                                    </span>
                                </td>
                                <td style="text-align:center; font-weight:800; color:#0f172a;">
                                    <?= (int) $r['used_count'] ?> / <?= (int)$r['usage_limit'] === 0 ? '<span style="color:#059669; font-size:1.1rem;">∞</span>' : (int)$r['usage_limit'] ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php if (!empty($r['active'])): ?>
                                        <span style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; padding:0.2rem 0.5rem; border-radius:6px; font-size:0.75rem; font-weight:800;">
                                            ● نشط
                                        </span>
                                    <?php else: ?>
                                        <span style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; padding:0.2rem 0.5rem; border-radius:6px; font-size:0.75rem; font-weight:800;">
                                            متوقف
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.5rem; justify-content:center;">
                                        <a href="<?= esc(admin_url('promo_codes.php?edit=' . (int) $r['id'])) ?>" style="background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; padding:0.4rem 0.75rem; border-radius:8px; font-weight:700; font-size:0.8rem; text-decoration:none;" title="تعديل">
                                            ✏️ تعديل
                                        </a>
                                        <form method="post" action="<?= esc(admin_url('promo_code_delete.php')) ?>" style="display:inline;" onsubmit="return confirm('حذف كود الخصم <?= esc(addslashes((string)$r['code'])) ?>؟');">
                                            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                            <button type="submit" style="background:#fee2e2; border:1px solid #fecaca; color:#dc2626; padding:0.4rem 0.75rem; border-radius:8px; font-weight:700; font-size:0.8rem; cursor:pointer;" title="حذف">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<script>
function generateRandomPromo() {
    var prefixes = ['ZEIN', 'GOLD', 'VIP', 'PERFUME', 'LUXURY', 'OFFER'];
    var prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
    var discounts = [10, 15, 20, 25, 30, 50];
    var discount = discounts[Math.floor(Math.random() * discounts.length)];
    var randomNum = Math.floor(100 + Math.random() * 900);
    
    var codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.value = prefix + discount;
    }
    var discountInput = document.getElementById('discount_percentage');
    if (discountInput) {
        discountInput.value = discount;
    }
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
