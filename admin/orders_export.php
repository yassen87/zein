<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$pdo = medal_pdo();
if (!$pdo) {
    exit('Database connection failed.');
}

// Get filters
$filter    = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';

// Build SQL
$sql = "
    SELECT 
        o.id,
        o.order_number,
        o.customer_name,
        o.customer_phone,
        o.shipping_address,
        o.city,
        o.address_landmark,
        o.total,
        o.paid_amount,
        o.waived_amount,
        o.status,
        o.admin_notes,
        o.created_at,
        (
            SELECT GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' x', oi.qty, IF(oi.variant_label_snapshot IS NOT NULL AND oi.variant_label_snapshot != '', CONCAT(' (', oi.variant_label_snapshot, ')'), '')) SEPARATOR ' | ')
            FROM order_items oi
            WHERE oi.order_id = o.id
        ) AS items_summary,
        (
            SELECT GROUP_CONCAT(CONCAT(ip.name_ar, ' x', oip.quantity) SEPARATOR ' | ')
            FROM order_internal_products oip
            JOIN internal_products ip ON oip.internal_product_id = ip.id
            WHERE oip.order_id = o.id
        ) AS gifts_summary,
        (
            SELECT COALESCE(SUM(oi.qty), 0)
            FROM order_items oi
            WHERE oi.order_id = o.id
        ) AS items_count,
        (
            SELECT COALESCE(SUM(oip.quantity), 0)
            FROM order_internal_products oip
            WHERE oip.order_id = o.id
        ) AS gifts_count
    FROM orders o
    WHERE 1=1
";

$params = [];

if ($filter !== '' && in_array($filter, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
    $sql .= ' AND o.status = ?';
    $params[] = $filter;
}

if ($startDate !== '') {
    $sql .= ' AND o.created_at >= ?';
    $params[] = $startDate . ' 00:00:00';
}

if ($endDate !== '') {
    $sql .= ' AND o.created_at <= ?';
    $params[] = $endDate . ' 23:59:59';
}

$sql .= ' ORDER BY o.created_at DESC';

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

// Excel Generation
$filename = "Zain_Shipping_Orders_" . date('Y-m-d_H-i') . ".xls";

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Pre-calculate grand totals
$grandTotalCOD    = 0.0;
$grandTotalPieces = 0;
$grandOrderCount  = count($rows);

foreach ($rows as $r) {
    $total   = (float)$r['total'];
    $paid    = (float)($r['paid_amount']  ?? 0);
    $waived  = (float)($r['waived_amount'] ?? 0);
    $grandTotalCOD    += max(0.0, $total - $paid - $waived);
    $grandTotalPieces += (int)$r['items_count'] + (int)$r['gifts_count'];
}

?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
    <style>
        body, table {
            direction: rtl;
            font-family: Arial, 'Segoe UI', sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background-color: #1a1a1a;
            color: #d4af37;
            font-weight: bold;
            border: 1px solid #94a3b8;
            padding: 10px 12px;
            font-size: 11pt;
            text-align: center;
        }
        td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            font-size: 10pt;
            vertical-align: top;
            color: #1e293b;
        }
        tr.zebra td {
            background-color: #f8fafc;
        }
        /* Force LTR for numeric/phone cells so digits never flip */
        .ltr {
            direction: ltr;
            unicode-bidi: embed;
            text-align: center;
        }
        .ltr-right {
            direction: ltr;
            unicode-bidi: embed;
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
        /* Summary totals row */
        tr.totals-row td {
            background-color: #1a1a1a !important;
            color: #d4af37 !important;
            font-weight: bold;
            font-size: 11pt;
            border: 2px solid #d4af37;
            padding: 10px 12px;
        }
        tr.totals-row .cod-total {
            color: #f87171 !important;
            font-size: 12pt;
        }
        tr.totals-row .pieces-total {
            color: #86efac !important;
        }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>رقم الأوردر</th>
                <th>تاريخ الطلب</th>
                <th>اسم المستلم</th>
                <th>رقم الهاتف</th>
                <th>المحافظة / المدينة</th>
                <th>العنوان بالتفصيل</th>
                <th>علامة مميزة</th>
                <th>محتويات الطرد (تفصيلي)</th>
                <th>عدد القطع الكلي</th>
                <th>طريقة الدفع</th>
                <th>المبلغ المطلوب تحصيله (ج.م)</th>
                <th>ملاحظات الشحن والتوصيل</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $isZebra = false;
            foreach ($rows as $r) {
                $total      = (float)$r['total'];
                $paid       = (float)($r['paid_amount']  ?? 0);
                $waived     = (float)($r['waived_amount'] ?? 0);
                $remaining  = max(0.0, $total - $paid - $waived);

                // Payment mode
                if ($remaining <= 0.0) {
                    $paymentMode  = 'مدفوع مسبقاً';
                    $codAmount    = 0.00;
                    $codStyle     = 'color:#166534; font-weight:bold;';
                    $payStyle     = 'color:#166534; font-weight:bold;';
                } else {
                    $paymentMode  = 'COD — الدفع عند الاستلام';
                    $codAmount    = $remaining;
                    $codStyle     = 'color:#b91c1c; font-weight:bold;';
                    $payStyle     = 'color:#1e40af; font-weight:bold;';
                }

                // ── Build detailed package contents ──────────────────────
                // Products: numbered list  ١. اسم العطر × 2 (مقاس صغير)
                $contentLines = [];
                $lineNum = 1;

                if (!empty($r['items_summary'])) {
                    // items_summary = "Product A x2 (Variant) | Product B x1"
                    $items = explode(' | ', $r['items_summary']);
                    foreach ($items as $item) {
                        $item = trim($item);
                        if ($item === '') continue;
                        // Separate qty from name: find last occurrence of " x<digits>"
                        if (preg_match('/^(.*?)\s+x(\d+)(.*)$/u', $item, $m)) {
                            $productName = trim($m[1]);
                            $qty         = (int)$m[2];
                            $variant     = trim($m[3]);               // e.g. " (Small)"
                            $variant     = $variant !== '' ? ' — ' . trim($variant, ' ()') : '';
                            $contentLines[] = $lineNum . '. ' . $productName . $variant . '  ×' . $qty;
                        } else {
                            $contentLines[] = $lineNum . '. ' . $item;
                        }
                        $lineNum++;
                    }
                }

                // Gifts / internal products — separated section
                if (!empty($r['gifts_summary'])) {
                    $contentLines[] = '— هدايا —';
                    $gifts = explode(' | ', $r['gifts_summary']);
                    foreach ($gifts as $gift) {
                        $gift = trim($gift);
                        if ($gift === '') continue;
                        if (preg_match('/^(.*?)\s+x(\d+)$/u', $gift, $m)) {
                            $contentLines[] = '🎁 ' . trim($m[1]) . '  ×' . (int)$m[2];
                        } else {
                            $contentLines[] = '🎁 ' . $gift;
                        }
                    }
                }

                $shipmentContents = !empty($contentLines) ? implode("\n", $contentLines) : '—';

                // Total pieces
                $totalPieces = (int)$r['items_count'] + (int)$r['gifts_count'];

                $rowClass = $isZebra ? 'zebra' : '';
                $isZebra  = !$isZebra;
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="excel-text ltr" style="font-weight:bold; color:#d4af37;"><?= esc($r['order_number']) ?></td>
                    <td class="ltr"><?= date('Y-m-d  H:i', strtotime($r['created_at'])) ?></td>
                    <td><?= esc($r['customer_name']) ?></td>
                    <td class="excel-text ltr"><?= esc($r['customer_phone'] ?: '—') ?></td>
                    <td style="text-align:center;"><?= esc($r['city'] ?: '—') ?></td>
                    <td><?= esc($r['shipping_address'] ?: '—') ?></td>
                    <td><?= esc($r['address_landmark'] ?: '—') ?></td>
                    <td style="white-space:pre-wrap; line-height:1.7; min-width:200px;"><?= esc($shipmentContents) ?></td>
                    <td class="excel-number ltr" style="font-weight:bold; font-size:12pt;"><?= $totalPieces ?></td>
                    <td style="text-align:center; <?= $payStyle ?>"><?= $paymentMode ?></td>
                    <td class="excel-currency ltr-right" style="<?= $codStyle ?>"><?= $codAmount ?></td>
                    <td><?= esc($r['admin_notes'] ?: '—') ?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
        <!-- ══ TOTALS SUMMARY ROW ══ -->
        <tfoot>
            <tr class="totals-row">
                <td colspan="8" style="text-align:start;">
                    📦 إجمالي الأوردرات: <strong><?= $grandOrderCount ?></strong>
                </td>
                <td class="pieces-total ltr" style="text-align:center;">
                    <?= $grandTotalPieces ?> قطعة
                </td>
                <td style="text-align:center;">إجمالي مبلغ التحصيل</td>
                <td class="cod-total ltr-right">
                    <?= number_format($grandTotalCOD, 2) ?> ج.م
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
<?php
exit;
