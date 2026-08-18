<?php
$user = require_login();
$locations = stock_locations();

// Determine target location:
// If user is locked to a location, use it. Otherwise allow selecting a specific location or all locations.
$userLocationId = current_user_location_id();
$selectedLocationId = $_GET['location_id'] ?? null;
$showAllLocations = false;

if ($userLocationId !== null) {
    $locationId = $userLocationId;
} elseif ($selectedLocationId === 'all') {
    $locationId = null;
    $showAllLocations = true;
} else {
    $locationId = (int) $selectedLocationId ?: ($locations[0]['id'] ?? 0);
}

// Find location name when a specific location is selected
$locationName = 'غير محدد';
if ($locationId !== null) {
    foreach ($locations as $l) {
        if ((int)$l['id'] === $locationId) {
            $locationName = $l['name'];
            break;
        }
    }
} else {
    $locationName = 'كل المواقع';
}

// Get rows for the chosen location or all locations
$rows = array_filter(inventory_rows($locationId), fn($r) => (float)$r['quantity'] > 0);

// Stats
$totalItems = count($rows);
$lowStockCount = count(array_filter($rows, fn($r) => (float)$r['min_stock'] > 0 && (float)$r['quantity'] <= (float)$r['min_stock']));

$typeTranslations = [
    'bottle' => 'زجاجة',
    'perfume_gram' => 'عطر بالجرام',
    'recipe' => 'تركيبة',
    'fixed' => 'منتج جاهز'
];

// Query payment balances for the branch treasury
$db = pdo();

// Cumulative Payments
$sqlCum = 'SELECT pm.method, SUM(pm.amount) as total_amount
           FROM payments pm
           JOIN invoices i ON i.id = pm.invoice_id';
$paramsCum = [];
if ($locationId !== null) {
    $sqlCum .= ' WHERE i.location_id = ?';
    $paramsCum[] = $locationId;
}
$sqlCum .= ' GROUP BY pm.method';
$stmtCum = $db->prepare($sqlCum);
$stmtCum->execute($paramsCum);
$cumPayments = $stmtCum->fetchAll();

// Today's Payments
$sqlToday = 'SELECT pm.method, SUM(pm.amount) as total_amount
             FROM payments pm
             JOIN invoices i ON i.id = pm.invoice_id
             WHERE DATE(pm.created_at) = CURRENT_DATE()';
$paramsToday = [];
if ($locationId !== null) {
    $sqlToday .= ' AND i.location_id = ?';
    $paramsToday[] = $locationId;
}
$sqlToday .= ' GROUP BY pm.method';
$stmtToday = $db->prepare($sqlToday);
$stmtToday->execute($paramsToday);
$todayPayments = $stmtToday->fetchAll();

$cumBalances = ['cash' => 0.0, 'instapay' => 0.0, 'vodafone_cash' => 0.0, 'bank_transfer' => 0.0];
foreach ($cumPayments as $cp) {
    if (isset($cumBalances[$cp['method']])) {
        $cumBalances[$cp['method']] = (float)$cp['total_amount'];
    }
}

$todayBalances = ['cash' => 0.0, 'instapay' => 0.0, 'vodafone_cash' => 0.0, 'bank_transfer' => 0.0];
foreach ($todayPayments as $tp) {
    if (isset($todayBalances[$tp['method']])) {
        $todayBalances[$tp['method']] = (float)$tp['total_amount'];
    }
}
?>

<style>
/* Custom enhancements for Branch Inventory and Treasury */
.branch-dashboard {
    display: grid;
    gap: 14px; /* Reduced from 24px */
    margin-top: 5px;
}

/* Section Titles */
.section-title {
    font-size: 13.5px; /* Reduced from 15px */
    font-weight: 800;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    margin-bottom: 4px;
}

/* Treasury Cards Design */
.treasury-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); /* Reduced minmax width */
    gap: 12px; /* Reduced from 18px */
    margin-bottom: 5px;
}

.treasury-card {
    background: var(--surface);
    border: 1.5px solid var(--line);
    border-radius: 12px; /* Reduced from 16px */
    padding: 14px 16px; /* Reduced from 20px */
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.01);
}

.treasury-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px -8px rgba(0, 0, 0, 0.05);
    border-color: var(--line-active);
}

.treasury-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 4px;
    height: 100%;
}

.treasury-card.cash::before { background: var(--success); }
.treasury-card.instapay::before { background: var(--primary); }
.treasury-card.vodafone::before { background: var(--danger); }
.treasury-card.bank::before { background: #0ea5e9; }

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px; /* Reduced from 14px */
}

.card-title {
    font-size: 11px; /* Reduced from 12.5px */
    font-weight: 700;
    color: var(--muted);
}

.card-icon {
    width: 28px; /* Reduced from 36px */
    height: 28px; /* Reduced from 36px */
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px; /* Reduced from 18px */
}

.treasury-card.cash .card-icon { background: rgba(16, 185, 129, 0.08); color: var(--success); }
.treasury-card.instapay .card-icon { background: rgba(124, 58, 237, 0.08); color: var(--primary); }
.treasury-card.vodafone .card-icon { background: rgba(239, 68, 68, 0.08); color: var(--danger); }
.treasury-card.bank .card-icon { background: rgba(14, 165, 233, 0.08); color: #0ea5e9; }

.card-amount-wrapper {
    display: flex;
    flex-direction: column;
    gap: 6px; /* Reduced from 12px */
}

.amount-main {
    font-size: 17px; /* Reduced from 22px */
    font-weight: 800;
    color: var(--ink);
    letter-spacing: -0.3px;
}

.amount-divider {
    height: 1px;
    background: var(--line);
    width: 100%;
}

.amount-sub {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 10.5px; /* Reduced from 11.5px */
    color: var(--muted);
    font-weight: 600;
}

.amount-sub strong {
    color: var(--ink);
    font-size: 11.5px; /* Reduced from 12.5px */
    font-weight: 700;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 12px;
    margin-bottom: 5px;
}

.stat-card {
    background: var(--surface);
    border: 1.5px solid var(--line);
    border-radius: 12px;
    padding: 10px 14px; /* Reduced from 18px 20px */
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.01);
}

.stat-card:hover {
    transform: translateY(-2px);
    border-color: var(--line-active);
}

.stat-icon {
    width: 34px; /* Reduced from 46px */
    height: 34px; /* Reduced from 46px */
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px; /* Reduced from 22px */
    background: var(--surface-soft);
    border: 1px solid var(--line);
}

.stat-info {
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.stat-label {
    font-size: 11px; /* Reduced from 12px */
    font-weight: 700;
    color: var(--muted);
}

.stat-value {
    font-size: 15px; /* Reduced from 20px */
    font-weight: 800;
    color: var(--ink);
}

/* Enhanced Table Container */
.custom-table-container {
    background: var(--surface);
    border: 1.5px solid var(--line);
    border-radius: 12px;
    padding: 16px; /* Reduced from 24px */
    box-shadow: 0 2px 4px rgba(0,0,0,0.01);
}

.branch-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 6px;
    margin-top: -8px;
}

.branch-table th {
    font-weight: 700;
    color: var(--muted);
    font-size: 11.5px;
    padding: 8px 12px;
    border: none;
    text-align: right;
}

.branch-table tbody tr {
    background: var(--surface-soft);
    transition: all 0.2s ease;
}

.branch-table tbody tr:hover {
    background: var(--primary-soft) !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.01);
}

.branch-table td {
    padding: 10px 12px; /* Reduced from 15px 16px */
    border: none;
    vertical-align: middle;
}

.branch-table td:first-child {
    border-radius: 0 10px 10px 0;
}

.branch-table td:last-child {
    border-radius: 10px 0 0 10px;
}

/* Custom Badges */
.badge-type {
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
}

.badge-type.bottle { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
.badge-type.perfume_gram { background: rgba(245, 158, 11, 0.08); color: #f59e0b; }
.badge-type.recipe { background: rgba(139, 92, 246, 0.08); color: #8b5cf6; }
.badge-type.fixed { background: rgba(16, 185, 129, 0.08); color: #10b981; }

.badge-status {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.badge-status.available { background: rgba(16, 185, 129, 0.08); color: var(--success); }
.badge-status.low { background: rgba(239, 68, 68, 0.08); color: var(--danger); animation: pulse-low 2.5s infinite; }

@keyframes pulse-low {
    0% { opacity: 1; }
    50% { opacity: 0.65; }
    100% { opacity: 1; }
}

/* Toolbar & Filters */
.dashboard-toolbar {
    background: var(--surface);
    border: 1.5px solid var(--line);
    border-radius: 16px;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 2px 4px rgba(0,0,0,0.01);
}

.search-wrapper {
    position: relative;
    flex: 1;
    min-width: 280px;
}

.search-wrapper input {
    width: 100%;
    padding: 11px 40px 11px 16px !important;
    font-size: 13px !important;
    border: 1.5px solid var(--line) !important;
    border-radius: 10px !important;
    background: var(--surface-soft) !important;
    color: var(--ink) !important;
    outline: none;
    transition: all 0.2s ease;
}

.search-wrapper input:focus {
    border-color: var(--primary) !important;
    background: var(--surface) !important;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.08) !important;
}

.search-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 15px;
    pointer-events: none;
}

.filter-select {
    padding: 10px 14px !important;
    border-radius: 10px !important;
    border: 1.5px solid var(--line) !important;
    font-size: 13px !important;
    font-weight: 600;
    color: var(--ink) !important;
    background: var(--surface) !important;
    cursor: pointer;
    outline: none;
    transition: all 0.2s ease;
}

.filter-select:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.08) !important;
}
</style>

<div class="branch-dashboard">
    <section class="page-head" style="margin: 0; padding-bottom: 10px;">
        <div>
            <h2>حالة الفرع - المخزون والخزائن المالية</h2>
            <p>متابعة رصيد الأصناف والسيولة النقدية المتوفرة بالخزينة لـ: <strong><?= e($locationName) ?></strong></p>
        </div>
    </section>

    <!-- Financial Treasury Section -->
    <div>
        <h3 class="section-title">💰 السيولة النقدية والخزينة بالفرع</h3>
        <div class="treasury-grid">
            <!-- Cash Balance Card -->
            <div class="treasury-card cash">
                <div class="card-header">
                    <span class="card-title">💵 النقدية المتوفرة (كاش)</span>
                    <div class="card-icon">💵</div>
                </div>
                <div class="card-amount-wrapper">
                    <strong class="amount-main"><?= money($cumBalances['cash']) ?></strong>
                    <div class="amount-divider"></div>
                    <div class="amount-sub">
                        <span>مبيعات اليوم:</span>
                        <strong><?= money($todayBalances['cash']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- InstaPay Balance Card -->
            <div class="treasury-card instapay">
                <div class="card-header">
                    <span class="card-title">📱 رصيد إنستا باي (InstaPay)</span>
                    <div class="card-icon">📱</div>
                </div>
                <div class="card-amount-wrapper">
                    <strong class="amount-main"><?= money($cumBalances['instapay']) ?></strong>
                    <div class="amount-divider"></div>
                    <div class="amount-sub">
                        <span>مبيعات اليوم:</span>
                        <strong><?= money($todayBalances['instapay']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Vodafone Cash Card -->
            <div class="treasury-card vodafone">
                <div class="card-header">
                    <span class="card-title">🔴 رصيد فودافون كاش</span>
                    <div class="card-icon">🔴</div>
                </div>
                <div class="card-amount-wrapper">
                    <strong class="amount-main"><?= money($cumBalances['vodafone_cash']) ?></strong>
                    <div class="amount-divider"></div>
                    <div class="amount-sub">
                        <span>مبيعات اليوم:</span>
                        <strong><?= money($todayBalances['vodafone_cash']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Bank Transfer Card -->
            <div class="treasury-card bank">
                <div class="card-header">
                    <span class="card-title">🏦 رصيد التحويلات البنكية</span>
                    <div class="card-icon">🏦</div>
                </div>
                <div class="card-amount-wrapper">
                    <strong class="amount-main"><?= money($cumBalances['bank_transfer']) ?></strong>
                    <div class="amount-divider"></div>
                    <div class="amount-sub">
                        <span>مبيعات اليوم:</span>
                        <strong><?= money($todayBalances['bank_transfer']) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Overview Stats -->
    <div>
        <h3 class="section-title">📦 جرد المنتجات وحالة المخزون</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <span class="stat-label">إجمالي بنود المخزون بالفرع</span>
                    <strong class="stat-value"><?= $totalItems ?></strong>
                </div>
            </div>
            <div class="stat-card" style="border-right: 4px solid var(--danger);">
                <div class="stat-icon" style="color: var(--danger);">⚠️</div>
                <div class="stat-info">
                    <span class="stat-label">أصناف منخفضة (تحتاج توريد)</span>
                    <strong class="stat-value" style="color: var(--danger);"><?= $lowStockCount ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar: Search & Location Switcher -->
    <div class="dashboard-toolbar">
        <div class="search-wrapper">
            <input type="text" id="branch-search" placeholder="ابحث باسم المنتج أو نوعه...">
            <span class="search-icon">🔍</span>
        </div>
        
        <?php if ($userLocationId === null): ?>
            <div>
                <form method="get" style="display: flex; align-items: center; gap: 10px; margin: 0;">
                    <input type="hidden" name="r" value="branch_inventory">
                    <label style="margin: 0; font-size: 13px; font-weight: 700; color: var(--muted); white-space: nowrap;">عرض فرع:</label>
                    <select name="location_id" onchange="this.form.submit()" class="filter-select">
                        <option value="all" <?= $showAllLocations ? 'selected' : '' ?>>كل الفروع والمستودعات</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= e($l['id']) ?>" <?= !$showAllLocations && (int)$l['id'] === $locationId ? 'selected' : '' ?>><?= e($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Inventory Table Panel -->
    <div class="custom-table-container">
        <table class="branch-table" id="branch-inventory-table">
            <thead>
                <tr>
                    <th>اسم المنتج</th>
                    <th>نوع الصنف</th>
                    <?php if ($showAllLocations): ?>
                        <th>الموقع</th>
                    <?php endif; ?>
                    <th>الرصيد المتوفر</th>
                    <th>حد الأمان</th>
                    <th style="width: 130px; text-align: center;">حالة المخزون</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="muted" style="text-align: center; padding: 30px;">لا توجد أي منتجات معرفة في هذا الفرع حالياً.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): 
                        $isLow = (float)$r['min_stock'] > 0 && (float)$r['quantity'] <= (float)$r['min_stock'];
                    ?>
                        <tr class="inventory-item-row <?= $isLow ? 'warn-row' : '' ?>" data-name="<?= e(mb_strtolower($r['product_name'])) ?>" data-type="<?= e(mb_strtolower($typeTranslations[$r['type']] ?? $r['type'])) ?>">
                            <td><strong><?= e($r['product_name']) ?></strong></td>
                            <td><span class="badge-type <?= e($r['type']) ?>"><?= e($typeTranslations[$r['type']] ?? $r['type']) ?></span></td>
                            <?php if ($showAllLocations): ?>
                                <td><span style="font-weight: 600; color: var(--muted);"><?= e($r['location_name']) ?></span></td>
                            <?php endif; ?>
                            <td>
                                <strong style="font-size: 15px; color: <?= (float)$r['quantity'] > 0 ? 'var(--ink)' : 'var(--danger)' ?>;">
                                    <?= e(qty($r['quantity'])) ?>
                                </strong>
                                <span class="muted" style="font-size: 11px;"><?= e($r['unit'] === 'gram' ? 'جرام' : 'قطعة') ?></span>
                            </td>
                            <td><span style="font-weight: 600; color: var(--muted);"><?= e(qty($r['min_stock'])) ?></span></td>
                            <td style="text-align: center;">
                                <?php if ($isLow): ?>
                                    <span class="badge-status low">⚠️ منخفض</span>
                                <?php else: ?>
                                    <span class="badge-status available">✅ متوفر</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('branch-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            const rows = document.querySelectorAll('#branch-inventory-table tbody tr.inventory-item-row');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const type = row.getAttribute('data-type') || '';
                if (name.includes(query) || type.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
