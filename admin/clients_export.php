<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$pdo = medal_pdo();
if (!$pdo) {
    exit('Database connection failed.');
}

$email = trim($_GET['email'] ?? '');
$phone = trim($_GET['phone'] ?? '');

if ($email !== '' || $phone !== '') {
    // -------------------------------------------------------
    // Individual Client Account Statement (كشف الحساب التفصيلي للعميل)
    // -------------------------------------------------------
    
    // 1. Try to find registered client
    $client = null;
    if ($phone !== '') {
        $st = $pdo->prepare("SELECT name, email, phone, created_at, 1 AS is_registered FROM clients WHERE phone = ?");
        $st->execute([$phone]);
        $client = $st->fetch();
    }
    if (!$client && $email !== '') {
        $st = $pdo->prepare("SELECT name, email, phone, created_at, 1 AS is_registered FROM clients WHERE email = ?");
        $st->execute([$email]);
        $client = $st->fetch();
    }

    // 2. Fallback to guest client from orders
    if (!$client) {
        if ($phone !== '') {
            $st = $pdo->prepare("
                SELECT 
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
    
    if ($client) {
        // Fetch all orders for this customer using email or phone
        $whereClause = "o.customer_email = ?";
        $queryParams = [$client['email']];
        if (!empty($client['phone'])) {
            $whereClause .= " OR o.customer_phone = ?";
            $queryParams[] = $client['phone'];
        }

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
                o.promo_code,
                o.created_at,
                (
                    SELECT GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' x', oi.qty) SEPARATOR ' | ')
                    FROM order_items oi
                    WHERE oi.order_id = o.id
                ) AS items_summary
            FROM orders o
            WHERE {$whereClause}
            ORDER BY o.created_at DESC
        ");
        $st->execute($queryParams);
        $orders = $st->fetchAll();
        
        $orderCount   = count($orders);
        $totalRevenue = 0.0;
        $totalPaid    = 0.0;
        
        foreach ($orders as $ord) {
            if ($ord['status'] !== 'cancelled') {
                $totalRevenue += (float)$ord['total'];
                $totalPaid    += (float)($ord['paid_amount'] ?? 0);
            }
        }
        $totalRemaining = max(0.0, $totalRevenue - $totalPaid);

        $statusLabels = [
            'pending'    => 'قيد الانتظار',
            'processing' => 'جاري التجهيز',
            'shipped'    => 'تم الشحن',
            'delivered'  => 'تم التسليم',
            'cancelled'  => 'ملغي',
        ];
        
        $filename = "Statement_" . preg_replace('/[^a-zA-Z0-9_\x7f-\xff]/', '_', $client['name']) . "_" . date('Y-m-d') . ".xls";
        
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        ?>
        <html xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:x="urn:schemas-microsoft-com:office:excel"
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
            <style>
                table {
                    border-collapse: collapse;
                    direction: rtl;
                    font-family: Arial, 'Segoe UI', sans-serif;
                    width: 100%;
                }
                th {
                    background-color: #1a1a1a;
                    color: #d4af37;
                    font-weight: bold;
                    border: 1px solid #cbd5e1;
                    padding: 10px;
                    font-size: 11pt;
                    text-align: center;
                }
                td {
                    border: 1px solid #cbd5e1;
                    padding: 8px;
                    font-size: 10pt;
                    vertical-align: middle;
                    color: #334155;
                }
                tr.zebra {
                    background-color: #f8fafc;
                }
                .text-center {
                    text-align: center;
                }
                .text-right {
                    text-align: right;
                }
                .excel-text {
                    mso-number-format: "\@";
                }
                .excel-currency {
                    mso-number-format: "\#\,\#\#0\.00";
                    font-weight: bold;
                }
                .header-title {
                    font-size: 16pt;
                    font-weight: bold;
                    color: #1a1a1a;
                    background-color: #f0dc82;
                    text-align: center;
                    padding: 12px;
                }
                .section-header {
                    font-size: 12pt;
                    font-weight: bold;
                    background-color: #e2e8f0;
                    color: #1e293b;
                    padding: 8px;
                }
                .label-cell {
                    font-weight: bold;
                    background-color: #f1f5f9;
                    color: #475569;
                }
            </style>
        </head>
        <body>
            <table>
                <tr>
                    <td colspan="11" class="header-title">كشف حساب عميل تفصيلي — عطور زين</td>
                </tr>
                <tr>
                    <td colspan="11" class="section-header">معلومات العميل الأساسية</td>
                </tr>
                <tr>
                    <td class="label-cell">اسم العميل</td>
                    <td colspan="4"><?= esc($client['name']) ?></td>
                    <td class="label-cell">رقم الهاتف</td>
                    <td class="excel-text" colspan="5"><?= esc($client['phone'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="label-cell">البريد الإلكتروني</td>
                    <td colspan="4" class="excel-text"><?= esc($client['email'] ?: '-') ?></td>
                    <td class="label-cell">حالة الحساب</td>
                    <td colspan="5"><?= $client['is_registered'] ? 'عميل مسجل' : 'عميل زائر' ?></td>
                </tr>
                <tr>
                    <td class="label-cell">تاريخ أول نشاط</td>
                    <td colspan="10"><?= date('Y-m-d H:i', strtotime($client['created_at'])) ?></td>
                </tr>
                <tr>
                    <td colspan="11" class="section-header">الملخص المالي للعميل</td>
                </tr>
                <tr>
                    <td class="label-cell" colspan="2">إجمالي عدد الطلبات</td>
                    <td colspan="2" class="text-center" style="font-weight: bold;"><?= $orderCount ?></td>
                    <td class="label-cell" colspan="2">إجمالي قيمة المشتريات</td>
                    <td colspan="2" class="excel-currency text-right" style="color: #1e40af;"><?= $totalRevenue ?></td>
                    <td class="label-cell">إجمالي المدفوع</td>
                    <td class="excel-currency text-right" style="color: #166534;"><?= $totalPaid ?></td>
                    <td class="excel-currency text-right" style="background-color: #fee2e2; color: #b91c1c;"><?= $totalRemaining ?></td>
                </tr>
                <tr>
                    <td colspan="11"></td>
                </tr>
                <thead>
                    <tr>
                        <th>تاريخ الطلب</th>
                        <th>رقم الطلب</th>
                        <th>المنتجات المطلوبة</th>
                        <th>قيمة المنتجات (ج.م)</th>
                        <th>الخصم (ج.م)</th>
                        <th>تكلفة الشحن (ج.م)</th>
                        <th>الإجمالي النهائي (ج.م)</th>
                        <th>المبلغ المدفوع (ج.م)</th>
                        <th>المتبقي المطلوب تحصيله (ج.م)</th>
                        <th>حالة الطلب</th>
                        <th>كوبون الخصم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $isZebra = false;
                    foreach ($orders as $ord) {
                        $total     = (float)$ord['total'];
                        $paid      = (float)($ord['paid_amount'] ?? 0);
                        $waived    = (float)($ord['waived_amount'] ?? 0);
                        $remaining = max(0.0, $total - $paid - $waived);
                        
                        $rowClass = $isZebra ? 'class="zebra"' : '';
                        $isZebra = !$isZebra;
                        ?>
                        <tr <?= $rowClass ?>>
                            <td class="text-center"><?= date('Y-m-d H:i', strtotime($ord['created_at'])) ?></td>
                            <td class="excel-text text-center" style="font-weight: bold; color: #d4af37;"><?= esc($ord['order_number']) ?></td>
                            <td><?= esc($ord['items_summary'] ?: '-') ?></td>
                            <td class="excel-currency text-right"><?= (float)$ord['subtotal'] ?></td>
                            <td class="excel-currency text-right" style="color: #b91c1c;"><?= (float)($ord['discount_amount'] ?? 0) ?></td>
                            <td class="excel-currency text-right"><?= (float)$ord['shipping_cost'] ?></td>
                            <td class="excel-currency text-right" style="font-weight: bold;"><?= $total ?></td>
                            <td class="excel-currency text-right" style="color: #166534;"><?= $paid ?></td>
                            <td class="excel-currency text-right" style="color: <?= $remaining > 0 ? '#b91c1c' : '#166534' ?>;"><?= $remaining ?></td>
                            <td class="text-center"><?= $statusLabels[$ord['status']] ?? $ord['status'] ?></td>
                            <td class="excel-text text-center"><?= esc($ord['promo_code'] ?: '-') ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        exit;
    } else {
        exit('Client not found.');
    }
} else {
    // -------------------------------------------------------
    // General Clients Export (تصدير قائمة العملاء العامة)
    // -------------------------------------------------------
    
    $sql = "
        SELECT 
            c.name AS name,
            c.email AS email,
            c.phone AS phone,
            'عميل مسجل' AS client_type,
            c.created_at AS registered_at,
            COALESCE((SELECT COUNT(*) FROM orders WHERE (customer_email = c.email OR customer_phone = c.phone) AND status != 'cancelled'), 0) AS order_count,
            COALESCE((SELECT SUM(total) FROM orders WHERE (customer_email = c.email OR customer_phone = c.phone) AND status != 'cancelled'), 0) AS total_revenue,
            COALESCE((SELECT SUM(paid_amount) FROM orders WHERE (customer_email = c.email OR customer_phone = c.phone) AND status != 'cancelled'), 0) AS total_paid,
            (SELECT shipping_address FROM orders WHERE customer_email = c.email OR customer_phone = c.phone ORDER BY created_at DESC LIMIT 1) AS latest_address,
            (SELECT city FROM orders WHERE customer_email = c.email OR customer_phone = c.phone ORDER BY created_at DESC LIMIT 1) AS latest_city,
            (SELECT MAX(created_at) FROM orders WHERE customer_email = c.email OR customer_phone = c.phone) AS last_order_date
        FROM clients c

        UNION ALL

        SELECT 
            MAX(o.customer_name) AS name,
            o.customer_email AS email,
            o.customer_phone AS phone,
            'عميل زائر' AS client_type,
            MIN(o.created_at) AS registered_at,
            COUNT(CASE WHEN o.status != 'cancelled' THEN 1 END) AS order_count,
            COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total ELSE 0 END), 0) AS total_revenue,
            COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.paid_amount ELSE 0 END), 0) AS total_paid,
            (SELECT shipping_address FROM orders WHERE customer_phone = o.customer_phone ORDER BY created_at DESC LIMIT 1) AS latest_address,
            (SELECT city FROM orders WHERE customer_phone = o.customer_phone ORDER BY created_at DESC LIMIT 1) AS latest_city,
            MAX(o.created_at) AS last_order_date
        FROM orders o
        WHERE o.customer_phone IS NOT NULL AND o.customer_phone != ''
          AND o.customer_phone NOT IN (SELECT phone FROM clients WHERE phone IS NOT NULL AND phone != '')
        GROUP BY o.customer_phone, o.customer_email

        ORDER BY registered_at DESC
    ";

    $clients = $pdo->query($sql)->fetchAll();

    $filename = "Zain_Clients_Database_" . date('Y-m-d_H-i') . ".xls";

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
        <style>
            table {
                border-collapse: collapse;
                direction: rtl;
                font-family: Arial, 'Segoe UI', sans-serif;
                width: 100%;
            }
            th {
                background-color: #1a1a1a;
                color: #d4af37;
                font-weight: bold;
                border: 1px solid #cbd5e1;
                padding: 10px;
                font-size: 11pt;
                text-align: center;
            }
            td {
                border: 1px solid #cbd5e1;
                padding: 8px;
                font-size: 10pt;
                vertical-align: middle;
                color: #334155;
            }
            tr.zebra {
                background-color: #f8fafc;
            }
            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
            .excel-text {
                mso-number-format: "\@";
            }
            .excel-number {
                mso-number-format: "\#\,\#\#0";
                text-align: center;
            }
            .excel-currency {
                mso-number-format: "\#\,\#\#0\.00";
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>
                    <th>الاسم الكامل</th>
                    <th>البريد الإلكتروني</th>
                    <th>رقم الهاتف</th>
                    <th>نوع العميل</th>
                    <th>تاريخ أول طلب / التسجيل</th>
                    <th>تاريخ آخر طلب</th>
                    <th>المدينة / المحافظة</th>
                    <th>آخر عنوان توصيل معروف</th>
                    <th>عدد الطلبات الناجحة</th>
                    <th>إجمالي المشتريات (ج.م)</th>
                    <th>إجمالي المدفوع (ج.م)</th>
                    <th>إجمالي المتبقي (ج.م)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $isZebra = false;
                foreach ($clients as $c) {
                    $revenue   = (float)$c['total_revenue'];
                    $paid      = (float)$c['total_paid'];
                    $remaining = max(0.0, $revenue - $paid);
                    
                    $rowClass = $isZebra ? 'class="zebra"' : '';
                    $isZebra = !$isZebra;
                    ?>
                    <tr <?= $rowClass ?>>
                        <td style="font-weight: bold;"><?= esc($c['name']) ?></td>
                        <td class="excel-text"><?= esc($c['email'] ?: '-') ?></td>
                        <td class="excel-text text-center"><?= esc($c['phone'] ?: '-') ?></td>
                        <td class="text-center"><?= esc($c['client_type']) ?></td>
                        <td class="text-center"><?= $c['registered_at'] ? date('Y-m-d', strtotime($c['registered_at'])) : '-' ?></td>
                        <td class="text-center"><?= $c['last_order_date'] ? date('Y-m-d', strtotime($c['last_order_date'])) : '-' ?></td>
                        <td class="text-center"><?= esc($c['latest_city'] ?: '-') ?></td>
                        <td><?= esc($c['latest_address'] ?: '-') ?></td>
                        <td class="excel-number"><?= (int)$c['order_count'] ?></td>
                        <td class="excel-currency text-right" style="color: #1e40af;"><?= $revenue ?></td>
                        <td class="excel-currency text-right" style="color: #166534;"><?= $paid ?></td>
                        <td class="excel-currency text-right" style="color: <?= $remaining > 0 ? '#b91c1c' : '#166534' ?>;"><?= $remaining ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}
