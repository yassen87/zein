<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

// Check permissions
if (!admin_has_permission('clients')) {
    redirect(admin_url('index.php'));
}

$pdo = medal_pdo();
if (!$pdo) {
    exit('Database connection failed.');
}

$email = trim($_GET['email'] ?? '');
$phone = trim($_GET['phone'] ?? '');
if ($email === '' && $phone === '') {
    redirect(admin_url('clients.php'));
}

// 1. Fetch Client Details (Registered first, then Guest)
$client = null;
if ($phone !== '') {
    $st = $pdo->prepare("SELECT id AS client_id, name, email, phone, created_at, 1 AS is_registered FROM clients WHERE phone = ?");
    $st->execute([$phone]);
    $client = $st->fetch();
}
if (!$client && $email !== '') {
    $st = $pdo->prepare("SELECT id AS client_id, name, email, phone, created_at, 1 AS is_registered FROM clients WHERE email = ?");
    $st->execute([$email]);
    $client = $st->fetch();
}

if (!$client) {
    if ($phone !== '') {
        $st = $pdo->prepare("
            SELECT 
                NULL AS client_id,
                customer_name AS name, 
                customer_email AS email, 
                customer_phone AS phone, 
                MIN(created_at) AS created_at, 
                0 AS is_registered 
            FROM orders 
            WHERE customer_phone = ? 
            GROUP BY customer_phone, customer_name, customer_email
            LIMIT 1
        ");
        $st->execute([$phone]);
        $client = $st->fetch();
    }
    if (!$client && $email !== '') {
        $st = $pdo->prepare("
            SELECT 
                NULL AS client_id,
                customer_name AS name, 
                customer_email AS email, 
                customer_phone AS phone, 
                MIN(created_at) AS created_at, 
                0 AS is_registered 
            FROM orders 
            WHERE customer_email = ? 
            GROUP BY customer_email, customer_name, customer_phone
            LIMIT 1
        ");
        $st->execute([$email]);
        $client = $st->fetch();
    }
}

if (!$client) {
    exit('Client not found.');
}

// 2. Fetch Client Orders — now includes paid_amount and waived_amount
if ($client['phone'] !== '') {
    $st = $pdo->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.subtotal,
            o.discount_amount,
            o.shipping_cost,
            o.total,
            o.paid_amount,
            o.waived_amount,
            o.status,
            o.created_at,
            (
                SELECT GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' x', oi.qty) SEPARATOR ' | ')
                FROM order_items oi
                WHERE oi.order_id = o.id
            ) AS items_summary
        FROM orders o
        WHERE o.customer_phone = ?
        ORDER BY o.created_at DESC
    ");
    $st->execute([$client['phone']]);
} else {
    $st = $pdo->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.subtotal,
            o.discount_amount,
            o.shipping_cost,
            o.total,
            o.paid_amount,
            o.waived_amount,
            o.status,
            o.created_at,
            (
                SELECT GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' x', oi.qty) SEPARATOR ' | ')
                FROM order_items oi
                WHERE oi.order_id = o.id
            ) AS items_summary
        FROM orders o
        WHERE o.customer_email = ?
        ORDER BY o.created_at DESC
    ");
    $st->execute([$client['email']]);
}
$orders = $st->fetchAll();

// 3. Compute Financial Statistics
$orderCount     = count($orders);
$totalPurchases = 0.0;
$totalDiscounts = 0.0;
$totalPaid      = 0.0;
$totalRemaining = 0.0;

foreach ($orders as $ord) {
    if ($ord['status'] !== 'cancelled') {
        $t       = (float)$ord['total'];
        $p       = (float)($ord['paid_amount']   ?? 0.0);
        $w       = (float)($ord['waived_amount']  ?? 0.0);
        $rem     = max(0.0, $t - $p - $w);

        $totalPurchases += $t;
        $totalDiscounts += (float)($ord['discount_amount'] ?? 0.0);
        $totalPaid      += $p;
        $totalRemaining += $rem;
    }
}

$pageTitle = (current_lang() === 'ar' ? 'كشف حساب: ' : 'Statement: ') . $client['name'];
require __DIR__ . '/_layout_start.php';
?>

<style>
/* ───── Statement-specific layout ───── */
.statement-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.statement-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}
@media (max-width: 992px) {
    .statement-grid { grid-template-columns: 1fr; }
}
.statement-meta-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.statement-meta-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 0;
    border-bottom: 1px solid var(--admin-border);
    font-size: 0.925rem;
    gap: 1rem;
}
.statement-meta-list li:last-child { border-bottom: none; }
.statement-meta-list li .meta-label {
    color: var(--admin-text-muted);
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
.statement-meta-list li .meta-value {
    font-weight: 700;
    color: var(--admin-text);
    text-align: end;
}

/* ───── 4-col KPI mini-cards ───── */
.stat-mini-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    height: 100%;
}
@media (max-width: 576px) {
    .stat-mini-grid { grid-template-columns: 1fr; }
}
.stat-mini-card {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 10px;
    padding: 1.25rem 1.4rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
}
.stat-mini-card:hover {
    box-shadow: var(--admin-shadow-lg);
    transform: translateY(-2px);
}
.stat-mini-card::before {
    content: '';
    position: absolute;
    top: 0; inset-inline-start: 0;
    width: 3px; height: 100%;
    background: var(--admin-gold);
    opacity: 0.8;
}
.stat-mini-card.is-paid::before   { background: var(--admin-success); }
.stat-mini-card.is-remain::before { background: var(--admin-danger); }
.stat-mini-card.is-discount::before { background: var(--admin-warning); }

.stat-mini-card .smc-label {
    font-size: 0.75rem;
    color: var(--admin-text-muted);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.4rem;
}
.stat-mini-card .smc-value {
    font-size: 1.5rem;
    color: var(--admin-text);
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.2;
}
.stat-mini-card.is-paid   .smc-value { color: var(--admin-success); }
.stat-mini-card.is-remain .smc-value { color: var(--admin-danger); }
.stat-mini-card.is-zero   .smc-value { color: var(--admin-success); }
.stat-mini-card .smc-currency {
    font-size: 0.85rem;
    font-weight: 600;
    opacity: 0.75;
    margin-inline-start: 3px;
}

/* ───── Print Styles ───── */
@media print {
    .admin-nav,
    .admin-mobile-header,
    .admin-nav-backdrop,
    .no-print,
    .statement-actions {
        display: none !important;
    }
    .admin-shell { display: block !important; }
    .admin-main {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        margin-inline-start: 0 !important;
    }
    body, .admin-body {
        background: #fff !important;
        color: #000 !important;
        font-size: 11pt !important;
        font-family: 'Arial', 'Segoe UI', sans-serif !important;
    }
    /* Make header visible for print */
    .print-logo-header { display: flex !important; }
    /* Cards */
    .admin-card, .stat-mini-card {
        box-shadow: none !important;
        border: 1px solid #d1d5db !important;
        background: #fff !important;
        color: #000 !important;
        margin-bottom: 0.75rem !important;
        page-break-inside: avoid;
    }
    .stat-mini-card .smc-value { color: #1a1a1a !important; font-size: 1.2rem !important; }
    .stat-mini-card::before { display: none !important; }
    /* Table */
    .admin-table-wrap {
        box-shadow: none !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0 !important;
    }
    .admin-table th {
        background: #1a1a1a !important;
        color: #d4af37 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        padding: 7px 10px !important;
        font-size: 8pt !important;
    }
    .admin-table td {
        color: #1a1a1a !important;
        padding: 7px 10px !important;
        font-size: 9pt !important;
        border-bottom: 1px solid #e5e7eb !important;
    }
    .admin-badge {
        background: transparent !important;
        color: #000 !important;
        border: 1px solid #999 !important;
        padding: 2px 6px !important;
        font-size: 8pt !important;
    }
    .print-signature { display: block !important; }
    .statement-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 0.75rem !important;
    }
    /* RTL print */
    table { direction: rtl; }
}

/* Hidden on screen, shown on print */
.print-logo-header { display: none; }
.print-signature    { display: none; }
</style>

<!-- ══════════════════════════════════════════
     Printable top-header (logo + date)
     ══════════════════════════════════════════ -->
<div class="print-logo-header" style="
    align-items: center;
    justify-content: space-between;
    border-bottom: 2px solid #1a1a1a;
    padding-bottom: 12px;
    margin-bottom: 20px;
">
    <div>
        <h1 style="margin:0; font-size: 1.5rem; font-weight: 800; color: #1a1a1a;">كشف حساب عميل تفصيلي</h1>
        <p style="margin: 4px 0 0; font-size: 0.85rem; color: #555;">
            متجر زين للعطور &mdash; عطور فاخرة تترك أثراً
        </p>
    </div>
    <div style="text-align: start;">
        <p style="margin:0; font-size: 0.85rem; color: #555;">تاريخ الطباعة:</p>
        <strong style="font-size: 0.95rem;"><?= date('Y-m-d H:i') ?></strong>
    </div>
</div>

<!-- ══════════════════════════════════════════
     On-screen header
     ══════════════════════════════════════════ -->
<div class="statement-header no-print">
    <div>
        <h1 style="margin-bottom: 4px;"><?= current_lang() === 'ar' ? 'كشف حساب العميل' : 'Customer Account Statement' ?></h1>
        <p class="admin-lead" style="margin-bottom: 0;">
            <?= current_lang() === 'ar' ? 'السجل المالي والطلبات التفصيلية للعميل.' : 'Financial summary and detailed orders for this customer.' ?>
        </p>
    </div>
    <div class="admin-actions statement-actions" style="gap: 0.75rem;">
        <a href="clients.php" class="admin-btn admin-btn--secondary">
            <?= current_lang() === 'ar' ? '⬅ رجوع للعملاء' : '⬅ Back to Clients' ?>
        </a>
        <button type="button" class="admin-btn admin-btn--secondary" onclick="window.print();">
            🖨 <?= current_lang() === 'ar' ? 'طباعة الكشف' : 'Print Statement' ?>
        </button>
        <a href="clients_export.php?email=<?= urlencode((string)($client['email'] ?? '')) ?>&phone=<?= urlencode((string)($client['phone'] ?? '')) ?>" class="admin-btn admin-btn--primary">
            📊 <?= current_lang() === 'ar' ? 'تصدير إكسل' : 'Export Excel' ?>
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════════
     Top grid: profile card  +  4 KPI cards
     ══════════════════════════════════════════ -->
<div class="statement-grid">
    <!-- Client Profile Card -->
    <div class="admin-card" style="padding: 1.75rem; margin-bottom: 0; border-inline-start: 4px solid var(--admin-gold);">
        <h2 style="font-size: 1.1rem; margin: 0 0 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <?= current_lang() === 'ar' ? 'معلومات العميل' : 'Customer Profile' ?>
        </h2>
        <ul class="statement-meta-list">
            <li>
                <span class="meta-label"><?= current_lang() === 'ar' ? 'الاسم' : 'Name' ?></span>
                <span class="meta-value" style="font-size: 1rem;"><?= esc($client['name']) ?></span>
            </li>
            <li>
                <span class="meta-label"><?= current_lang() === 'ar' ? 'البريد الإلكتروني' : 'Email' ?></span>
                <span class="meta-value" style="direction: ltr; text-align: end;"><?= esc($client['email'] ?: '-') ?></span>
            </li>
            <li>
                <span class="meta-label"><?= current_lang() === 'ar' ? 'الهاتف' : 'Phone' ?></span>
                <span class="meta-value" style="font-family: monospace; direction: ltr; text-align: end;"><?= esc($client['phone'] ?: '-') ?></span>
            </li>
            <li>
                <span class="meta-label"><?= current_lang() === 'ar' ? 'نوع الحساب' : 'Account Type' ?></span>
                <span class="meta-value">
                    <?php if ($client['is_registered']): ?>
                        <span class="admin-badge admin-badge--success" style="font-size: 0.75rem; padding: 4px 10px; border-radius: 6px;">
                            <?= current_lang() === 'ar' ? '✔ مسجل' : '✔ Registered' ?>
                        </span>
                    <?php else: ?>
                        <span class="admin-badge" style="font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; background: rgba(107,114,128,0.1); color: var(--admin-text-muted);">
                            <?= current_lang() === 'ar' ? '🛒 زائر' : '🛒 Guest' ?>
                        </span>
                    <?php endif; ?>
                </span>
            </li>
            <li>
                <span class="meta-label"><?= current_lang() === 'ar' ? 'أول نشاط' : 'First Active' ?></span>
                <span class="meta-value" style="font-size: 0.85rem; color: var(--admin-text-muted);"><?= date('Y-m-d', strtotime($client['created_at'])) ?></span>
            </li>
        </ul>
    </div>

    <!-- 4 Financial KPI Cards -->
    <div>
        <div class="stat-mini-grid" style="grid-template-columns: repeat(2, 1fr);">

            <!-- Card 1: Total orders -->
            <div class="stat-mini-card">
                <div class="smc-label"><?= current_lang() === 'ar' ? 'عدد الطلبات الكلي' : 'Total Orders' ?></div>
                <div class="smc-value" style="font-size: 2rem;"><?= $orderCount ?></div>
            </div>

            <!-- Card 2: Total purchases -->
            <div class="stat-mini-card">
                <div class="smc-label"><?= current_lang() === 'ar' ? 'إجمالي قيمة الطلبات' : 'Total Purchases' ?></div>
                <div class="smc-value" style="color: var(--admin-gold);">
                    <?= number_format($totalPurchases, 2) ?>
                    <span class="smc-currency"><?= esc(t('currency')) ?></span>
                </div>
            </div>

            <!-- Card 3: Total Paid -->
            <div class="stat-mini-card is-paid">
                <div class="smc-label"><?= current_lang() === 'ar' ? 'إجمالي المبالغ المدفوعة' : 'Total Paid' ?></div>
                <div class="smc-value">
                    <?= number_format($totalPaid, 2) ?>
                    <span class="smc-currency"><?= esc(t('currency')) ?></span>
                </div>
            </div>

            <!-- Card 4: Remaining Due (red if > 0, green if settled) -->
            <div class="stat-mini-card <?= $totalRemaining > 0.0 ? 'is-remain' : 'is-zero' ?>">
                <div class="smc-label">
                    <?= current_lang() === 'ar' ? 'المتبقي المستحق التحصيل' : 'Remaining Due' ?>
                    <?php if ($totalRemaining <= 0.0): ?>
                        <span style="font-size:0.7rem; color: var(--admin-success); font-weight:700;"> ✔ مسوّى</span>
                    <?php endif; ?>
                </div>
                <div class="smc-value">
                    <?= number_format($totalRemaining, 2) ?>
                    <span class="smc-currency"><?= esc(t('currency')) ?></span>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════
     Orders Table
     ══════════════════════════════════════════ -->
<div class="admin-card" style="padding: 1.75rem; border-inline-start: 4px solid var(--admin-info);">
    <h2 style="font-size: 1.15rem; margin: 0 0 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 18 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        <?= current_lang() === 'ar' ? 'سجل المعاملات والطلبات التفصيلي' : 'Transaction History & Orders' ?>
    </h2>

    <?php if ($orders === []): ?>
        <p class="admin-muted"><?= current_lang() === 'ar' ? 'لا يوجد أي معاملات مسجلة لهذا العميل.' : 'No recorded transactions for this customer.' ?></p>
    <?php else: ?>
        <div class="admin-table-wrap" style="border-radius: 10px; border: 1px solid var(--admin-border);">
            <table class="admin-table" style="min-width: 900px;">
                <thead>
                    <tr>
                        <th style="font-size: 0.78rem; padding: 12px 14px;"><?= current_lang() === 'ar' ? 'التاريخ' : 'Date' ?></th>
                        <th style="font-size: 0.78rem; padding: 12px 14px;"><?= current_lang() === 'ar' ? 'رقم الطلب' : 'Order #' ?></th>
                        <th style="font-size: 0.78rem; padding: 12px 14px;"><?= current_lang() === 'ar' ? 'المنتجات' : 'Products' ?></th>
                        <th style="font-size: 0.78rem; padding: 12px 14px; text-align: center;"><?= current_lang() === 'ar' ? 'الحالة' : 'Status' ?></th>
                        <th style="font-size: 0.78rem; padding: 12px 14px; text-align: end;"><?= current_lang() === 'ar' ? 'قيمة المنتجات' : 'Subtotal' ?></th>
                        <th style="font-size: 0.78rem; padding: 12px 14px; text-align: end;"><?= current_lang() === 'ar' ? 'الخصم' : 'Discount' ?></th>
                        <th style="font-size: 0.78rem; padding: 12px 14px; text-align: end;"><?= current_lang() === 'ar' ? 'الشحن' : 'Shipping' ?></th>
                        <th style="font-size: 0.78rem; padding: 12px 14px; text-align: end;"><?= current_lang() === 'ar' ? 'الإجمالي' : 'Total' ?></th>
                        <th style="font-size: 0.78rem; padding: 12px 14px; text-align: end; color: var(--admin-success);"><?= current_lang() === 'ar' ? 'المدفوع' : 'Paid' ?></th>
                        <th style="font-size: 0.78rem; padding: 12px 14px; text-align: end; color: var(--admin-danger);"><?= current_lang() === 'ar' ? 'المتبقي (COD)' : 'Remaining' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $ord): ?>
                        <?php
                        $statusClass = 'admin-badge--' . $ord['status'];
                        $statusLabel = admin_order_status_label($ord['status']);
                        $ordTotal    = (float)$ord['total'];
                        $ordPaid     = (float)($ord['paid_amount']  ?? 0.0);
                        $ordWaived   = (float)($ord['waived_amount'] ?? 0.0);
                        $ordRemain   = max(0.0, $ordTotal - $ordPaid - $ordWaived);
                        ?>
                        <tr>
                            <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--admin-text-muted); white-space: nowrap;">
                                <?= date('Y-m-d', strtotime($ord['created_at'])) ?>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 700; white-space: nowrap;">
                                <a href="order_view.php?id=<?= (int)$ord['id'] ?>" class="no-print" style="color: var(--admin-gold); text-decoration: none; font-weight: 700;">
                                    <?= esc($ord['order_number']) ?>
                                </a>
                                <span class="print-only" style="display:none; font-weight:700;"><?= esc($ord['order_number']) ?></span>
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.82rem; max-width: 220px; white-space: normal; line-height: 1.5;">
                                <?= esc($ord['items_summary'] ?: '-') ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span class="admin-badge <?= $statusClass ?>" style="font-size: 0.72rem; padding: 4px 10px; border-radius: 6px;">
                                    <?= esc($statusLabel) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px; text-align: end; font-weight: 600;">
                                <?= number_format((float)$ord['subtotal'], 2) ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: end; color: var(--admin-danger);">
                                <?= $ord['discount_amount'] ? '-' . number_format((float)$ord['discount_amount'], 2) : '—' ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: end; color: var(--admin-text-muted);">
                                <?= number_format((float)$ord['shipping_cost'], 2) ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: end; font-weight: 800; color: var(--admin-gold);">
                                <?= number_format($ordTotal, 2) ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: end; font-weight: 700; color: var(--admin-success);">
                                <?= $ordPaid > 0 ? number_format($ordPaid, 2) : '—' ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: end; font-weight: 800; color: <?= $ordRemain > 0 ? 'var(--admin-danger)' : 'var(--admin-success)' ?>;">
                                <?= $ordRemain > 0 ? number_format($ordRemain, 2) : '✔ مسوّى' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <!-- Summary footer row -->
                <tfoot>
                    <tr style="background: rgba(212,175,55,0.06);">
                        <td colspan="7" style="padding: 12px 14px; font-weight: 800; font-size: 0.9rem; text-align: start;">
                            <?= current_lang() === 'ar' ? 'الإجماليات (بدون الملغية)' : 'Totals (excl. cancelled)' ?>
                        </td>
                        <td style="padding: 12px 14px; text-align: end; font-weight: 900; color: var(--admin-gold); font-size: 1rem; border-top: 2px solid var(--admin-border);">
                            <?= number_format($totalPurchases, 2) ?>
                        </td>
                        <td style="padding: 12px 14px; text-align: end; font-weight: 900; color: var(--admin-success); font-size: 1rem; border-top: 2px solid var(--admin-border);">
                            <?= number_format($totalPaid, 2) ?>
                        </td>
                        <td style="padding: 12px 14px; text-align: end; font-weight: 900; color: <?= $totalRemaining > 0 ? 'var(--admin-danger)' : 'var(--admin-success)' ?>; font-size: 1rem; border-top: 2px solid var(--admin-border);">
                            <?= number_format($totalRemaining, 2) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════
     Print-only: Signature / Authorization block
     ══════════════════════════════════════════ -->
<div class="print-signature" style="
    margin-top: 40px;
    border-top: 1px solid #d1d5db;
    padding-top: 24px;
    direction: rtl;
">
    <p style="font-size: 0.85rem; color: #555; margin: 0 0 24px;">
        تم إعداد هذا الكشف من قِبَل متجر زين للعطور وهو سجل رسمي للمعاملات المالية للعميل المذكور أعلاه.
    </p>
    <div style="display: flex; justify-content: space-between; gap: 40px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 180px; text-align: center;">
            <div style="border-top: 1px solid #555; padding-top: 8px; margin-top: 50px; font-size: 0.8rem; color: #333; font-weight: 600;">
                توقيع المسؤول المالي
            </div>
            <div style="font-size: 0.75rem; color: #888; margin-top: 4px;">
                الاسم: ________________________
            </div>
        </div>
        <div style="flex: 1; min-width: 180px; text-align: center;">
            <div style="border-top: 1px solid #555; padding-top: 8px; margin-top: 50px; font-size: 0.8rem; color: #333; font-weight: 600;">
                ختم المتجر / الاعتماد
            </div>
            <div style="font-size: 0.75rem; color: #888; margin-top: 4px;">
                متجر زين للعطور — www.zain.store
            </div>
        </div>
        <div style="flex: 1; min-width: 180px; text-align: center;">
            <div style="border-top: 1px solid #555; padding-top: 8px; margin-top: 50px; font-size: 0.8rem; color: #333; font-weight: 600;">
                استلم العميل / توقيعه
            </div>
            <div style="font-size: 0.75rem; color: #888; margin-top: 4px;">
                الاسم: ________________________
            </div>
        </div>
    </div>
    <p style="font-size: 0.7rem; color: #aaa; text-align: center; margin-top: 20px;">
        طُبع بتاريخ: <?= date('Y-m-d H:i') ?> &mdash; هذا المستند لا يُعتمد إلا بختم المتجر وتوقيع المسؤول
    </p>
</div>

<script>
window.onbeforeprint = function () {
    document.querySelectorAll('.admin-badge').forEach(function (b) {
        b.style.border  = '1px solid #666';
        b.style.color   = '#000';
        b.style.background = 'transparent';
    });
    document.querySelectorAll('.print-only').forEach(function (el) {
        el.style.display = 'inline';
    });
};
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
