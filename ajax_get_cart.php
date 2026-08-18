<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

header('Content-Type: application/json');

$items = [];
$total = 0.0;

foreach ($_SESSION['cart'] ?? [] as $lk => $q) {
    if (strpos((string)$lk, 'bundle-') === 0) {
        $bid = (int)str_replace('bundle-', '', (string)$lk);
        $p = get_cart_line_bundle($bid);
        if ($p) {
            $lineTotal = $p['price'] * $q;
            $total += $lineTotal;
            $imgStyle = product_image_style($p['image']);
            $imgUrl = '';
            if (preg_match('/url\(\'(.*?)\'\)/', $imgStyle, $matches)) {
                $imgUrl = $matches[1];
            }
            $items[] = [
                'line_key' => $lk,
                'id' => $p['id'],
                'is_bundle' => true,
                'name' => $p['name'],
                'price' => $p['price'],
                'price_formatted' => format_price($p['price']),
                'qty' => $q,
                'image' => $imgUrl,
                'variant' => current_lang() === 'ar' ? 'عرض خاص 🎁' : 'Special Offer 🎁',
                'line_total' => $lineTotal,
                'line_total_formatted' => format_price($lineTotal),
            ];
        }
    } else {
        $parsed = cart_parse_line_key((string) $lk);
        $p = get_cart_line_product($parsed['product_id'], $parsed['variant_id']);
        if ($p) {
            $lineTotal = $p['price'] * $q;
            $total += $lineTotal;
            
            $variantLabel = '';
            if ($parsed['variant_id']) {
                $vars = get_product_variants($p['id']);
                foreach ($vars as $v) {
                    if ($v['id'] === $parsed['variant_id']) {
                        $variantLabel = current_lang() === 'ar' ? $v['label_ar'] : $v['label_en'];
                        break;
                    }
                }
            }
            
            $imgStyle = product_image_style($p['image']);
            $imgUrl = '';
            if (preg_match('/url\(\'(.*?)\'\)/', $imgStyle, $matches)) {
                $imgUrl = $matches[1];
            }
            
            $items[] = [
                'line_key' => $lk,
                'id' => $p['id'],
                'name' => $p['name'],
                'price' => $p['price'],
                'price_formatted' => format_price($p['price']),
                'qty' => $q,
                'image' => $imgUrl,
                'variant' => $variantLabel,
                'line_total' => $lineTotal,
                'line_total_formatted' => format_price($lineTotal),
            ];
        }
    }
}

echo json_encode([
    'items' => $items,
    'cart_count' => cart_count(),
    'subtotal' => $total,
    'subtotal_formatted' => format_price($total)
]);
