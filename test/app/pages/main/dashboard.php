<?php
$userLocationId = current_user_location_id();
$stats     = dashboard_stats($userLocationId);
$invoices  = recent_invoices(8, $userLocationId);
$locations = dashboard_location_sales($userLocationId);
$lowStock  = low_stock_rows($userLocationId);
?>

<!-- Dashboard Hero -->
<section class="page-head hero" style="margin-bottom: 18px;">
    <div>
        <p class="eyebrow"><?= e(__('نظرة مباشرة')) ?></p>
        <h2><?= e(__('لوحة التحكم العامة')) ?></h2>
        <p><?= e(__('مبيعات الفروع والأونلاين، المخزون، الديون، والمصاريف في شاشة واحدة.')) ?></p>
    </div>
    <a class="btn primary" href="index.php?r=pos" style="white-space: nowrap;">
        🛒 <?= e(__('فتح الكاشير')) ?>
    </a>
</section>

<!-- Stats Cards -->
<section class="cards" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
    <article class="card-sales">
        <span class="card-icon">💰</span>
        <span class="card-label"><?= e(__('مبيعات اليوم')) ?></span>
        <strong class="card-value" style="color: var(--success);"><?= money($stats['today_sales']) ?></strong>
    </article>
    <article class="card-invoices">
        <span class="card-icon">🧾</span>
        <span class="card-label"><?= e(__('فواتير اليوم')) ?></span>
        <strong class="card-value" style="color: var(--primary);"><?= e($stats['today_invoices']) ?></strong>
    </article>
    <article class="card-customers-today">
        <span class="card-icon">🆕</span>
        <span class="card-label"><?= e(__('عملاء جدد اليوم')) ?></span>
        <strong class="card-value" style="color: var(--accent);"><?= e($stats['today_customers']) ?></strong>
    </article>
    <article class="card-customers">
        <span class="card-icon">👥</span>
        <span class="card-label"><?= e(__('إجمالي العملاء')) ?></span>
        <strong class="card-value" style="color: var(--accent);"><?= e($stats['customers']) ?></strong>
    </article>
    <article class="card-debts">
        <span class="card-icon">⚠️</span>
        <span class="card-label"><?= e(__('ديون مفتوحة')) ?></span>
        <strong class="card-value" style="color: var(--danger);"><?= money($stats['open_debts']) ?></strong>
    </article>
    <article class="card-expenses">
        <span class="card-icon">📤</span>
        <span class="card-label"><?= e(__('مصاريف الشهر')) ?></span>
        <strong class="card-value" style="color: var(--warning);"><?= money($stats['month_expenses']) ?></strong>
    </article>
    <article class="card-stock">
        <span class="card-icon">📦</span>
        <span class="card-label"><?= e(__('تنبيهات مخزون')) ?></span>
        <strong class="card-value" style="color: var(--primary);"><?= e($stats['low_stock_count']) ?></strong>
    </article>
</section>

<!-- Two-column: Location Sales + Low Stock -->
<section class="split" style="margin-bottom: 14px;">
    <!-- Location Sales -->
    <div class="panel" style="padding: 0; overflow: hidden;">
        <div style="padding: 12px 16px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 13.5px;">📊 <?= e(__('مبيعات اليوم حسب القناة')) ?></h3>
            <a href="index.php?r=reports" class="btn small" style="font-size: 11px;"><?= e(__('التقارير')) ?> →</a>
        </div>
        <table style="min-width: 0;">
            <thead>
                <tr>
                    <th><?= e(__('الموقع')) ?></th>
                    <th><?= e(__('النوع')) ?></th>
                    <th style="text-align: end;"><?= e(__('المبيعات')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($locations): ?>
                    <?php foreach ($locations as $l): ?>
                        <tr>
                            <td><strong><?= e($l['name']) ?></strong></td>
                            <td>
                                <span class="badge" style="<?= $l['type'] === 'online' ? 'background:rgba(6,182,212,.15);color:#0891b2;' : '' ?>">
                                    <?= e(__($l['type'])) ?>
                                </span>
                            </td>
                            <td style="text-align: end; font-weight: 700; color: var(--success);"><?= money($l['today_sales']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 20px; color: var(--muted);">
                            <?= e(__('لا توجد مبيعات اليوم بعد.')) ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Low Stock Alerts -->
    <div class="panel" style="padding: 0; overflow: hidden;">
        <div style="padding: 12px 16px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 13.5px;">
                🚨 <?= e(__('أصناف وصلت للحد الأدنى')) ?>
                <?php if ($lowStock): ?>
                    <span style="background: #dc2626; color: #fff; border-radius: 99px; padding: 1px 8px; font-size: 10.5px; font-weight: 700; margin-inline-start: 6px;">
                        <?= count($lowStock) ?>
                    </span>
                <?php endif; ?>
            </h3>
            <a href="index.php?r=inventory" class="btn small" style="font-size: 11px;"><?= e(__('المخزون')) ?> →</a>
        </div>
        <table style="min-width: 0;">
            <thead>
                <tr>
                    <th><?= e(__('الموقع')) ?></th>
                    <th><?= e(__('الصنف')) ?></th>
                    <th style="text-align: center;"><?= e(__('الرصيد')) ?></th>
                    <th style="text-align: center;"><?= e(__('الحد')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lowStock): ?>
                    <?php foreach ($lowStock as $r): ?>
                        <tr>
                            <td style="font-size: 12px;"><?= e($r['location_name']) ?></td>
                            <td><strong style="font-size: 13px;"><?= e($r['product_name']) ?></strong></td>
                            <td style="text-align: center;">
                                <span style="color: var(--danger); font-weight: 800;"><?= e(qty($r['quantity'])) ?></span>
                            </td>
                            <td style="text-align: center; color: var(--muted); font-weight: 700;"><?= e(qty($r['min_stock'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px; color: var(--success);">
                            ✅ <?= e(__('المخزون بمستويات جيدة')) ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Recent Invoices -->
<div class="panel" style="padding: 0; overflow: hidden;">
    <div style="padding: 12px 16px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 13.5px;">🧾 <?= e(__('آخر الفواتير')) ?></h3>
        <a href="index.php?r=invoices" class="btn small primary" style="font-size: 11px;"><?= e(__('كل الفواتير')) ?> →</a>
    </div>
    <table>
        <thead>
            <tr>
                <th><?= e(__('رقم الفاتورة')) ?></th>
                <th><?= e(__('الحالة')) ?></th>
                <th><?= e(__('الموقع')) ?></th>
                <th><?= e(__('الموظف')) ?></th>
                <th><?= e(__('العميل')) ?></th>
                <th><?= e(__('الإجمالي')) ?></th>
                <th><?= e(__('المدفوع')) ?></th>
                <th><?= e(__('المتبقي')) ?></th>
                <th><?= e(__('التاريخ')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($invoices): ?>
                <?php foreach ($invoices as $i): ?>
                    <tr>
                        <td><a href="index.php?r=invoice_view&id=<?= e($i['id']) ?>" style="font-weight: 700; color: var(--primary-dark);"><?= e($i['invoice_number']) ?></a></td>
                        <td><span class="badge"><?= e(__($i['status'])) ?></span></td>
                        <td><?= e($i['location_name']) ?></td>
                        <td><?= e($i['user_name']) ?></td>
                        <td><?= e($i['customer_name'] ?: __('زبون عابر')) ?></td>
                        <td style="font-weight: 700;"><?= money($i['total']) ?></td>
                        <td style="color: var(--success); font-weight: 700;"><?= money($i['paid_total']) ?></td>
                        <td style="color: <?= (float)$i['due_total'] > 0 ? 'var(--danger)' : 'var(--muted)' ?>; font-weight: 700;"><?= money($i['due_total']) ?></td>
                        <td style="color: var(--muted); font-size: 12px;"><?= e($i['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 30px; color: var(--muted);">
                        <?= e(__('لا توجد فواتير حتى الآن.')) ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.card-sales { border-inline-start: 0 !important; }
.cards article:after { 
    background: linear-gradient(135deg, rgba(139, 90, 43, .15), rgba(212, 163, 115, .12)); 
}
.dashboard-trend {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    font-weight: 700;
    margin-top: 4px;
    padding: 3px 8px;
    border-radius: 99px;
    position: relative;
    z-index: 1;
}
.trend-up   { background: rgba(85, 139, 47, .12); color: var(--success); }
.trend-down { background: rgba(166, 35, 38, .12); color: var(--danger); }
@keyframes pulseWarm {
    0%  { box-shadow: 0 0 0 0 rgba(139, 90, 43, .25); }
    70% { box-shadow: 0 0 0 8px rgba(139, 90, 43, 0); }
    100%{ box-shadow: 0 0 0 0 rgba(139, 90, 43, 0); }
}
.card-sales { animation: pulseWarm 3s ease infinite; }
</style>
