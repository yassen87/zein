<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = t('page_checkout');
$errors = [];
$done = false;
$orderNumber = '';
$shippingCities = [];
$pdoMain = medal_pdo();
if ($pdoMain !== null) {
    try {
        $shippingCities = $pdoMain->query('SELECT * FROM shipping_cities WHERE active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (\Throwable $e) {
    error_log('Error in checkout.php shipping_cities: ' . $e->getMessage());
}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'submit_order';
    
    if ($action === 'remove') {
        $lineKey = (string) ($_POST['line_key'] ?? '');
        if ($lineKey !== '' && isset($_SESSION['cart'][$lineKey])) {
            remove_cart_line($lineKey);
        }
        header('Location: ' . url('checkout.php'));
        exit;
    }
    
    if ($action === 'update_qty') {
        $lineKey = (string) ($_POST['line_key'] ?? '');
        $newQty = (int) ($_POST['qty'] ?? 1);
        if ($lineKey !== '' && isset($_SESSION['cart'][$lineKey])) {
            if ($newQty > 0) {
                $_SESSION['cart'][$lineKey] = $newQty;
            } else {
                remove_cart_line($lineKey);
            }
        }
        header('Location: ' . url('checkout.php'));
        exit;
    }

    if ($action === 'submit_order') {
        if (!validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
            $errors[] = current_lang() === 'ar' ? 'انتهت صلاحية الجلسة. يرجى تحديث الصفحة والمحاولة مرة أخرى.' : 'Session expired. Please refresh the page and try again.';
            header('Content-Type: text/html; charset=utf-8');
$pageTitle = t('page_checkout');
$pageDescription = get_page_description('checkout');
$canonicalUrl = get_current_url_without_lang();
$noindex = true;
$extraCss = [
    url('assets/css/pages/checkout.css?v=' . filemtime(__DIR__ . '/assets/css/pages/checkout.css'))
];

require __DIR__ . '/includes/header.php';
            echo '<div class="neo-container" style="padding:4rem;text-align:center;"><div class="neo-box" style="color:#b91c1c;"><p>' . esc($errors[0]) . '</p><a href="' . esc(url('checkout.php')) . '" class="neo-submit" style="display:inline-block;width:auto;padding:0.8rem 2rem;text-decoration:none;margin-top:1rem;">' . esc(t('nav_home')) . '</a></div></div>';
            require __DIR__ . '/includes/footer.php';
            exit;
        }
        $name = trim((string) ($_POST['customer_name'] ?? ''));
        $email = trim((string) ($_POST['customer_email'] ?? ''));
        $phone = trim((string) ($_POST['customer_phone'] ?? ''));
        $address = trim((string) ($_POST['shipping_address'] ?? ''));
        $cityId = (int) ($_POST['city_id'] ?? 0);
        $cityName = '';
        $shippingCost = 0.0;
        foreach ($shippingCities as $sc) {
            if ((int)$sc['id'] === $cityId) {
                $cityName = current_lang() === 'ar' ? $sc['name_ar'] : $sc['name_en'];
                $shippingCost = (float) $sc['shipping_cost'];
                break;
            }
        }
        $city = $cityName;
        $landmark = trim((string) ($_POST['address_landmark'] ?? ''));
        $notes = trim((string) ($_POST['order_notes'] ?? ''));
        $phone2 = trim((string) ($_POST['customer_phone_2'] ?? ''));
        if ($phone2 !== '') {
            $notes .= ($notes !== '' ? "\n" : "") . (current_lang() === 'ar' ? 'رقم هاتف إضافي: ' : 'Additional phone: ') . $phone2;
        }

        if ($name === '' || $phone === '' || $address === '' || $city === '' || $cityId === 0) {
            $errors[] = t('checkout_err_required') ?? 'Please fill all required fields, including phone.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = t('checkout_err_email');
        }

        $totalForSubmit = 0.0;
        $orderLines = [];

        $stockCheckIds = [];
        $stockCheckMeta = [];
        foreach ($_SESSION['cart'] ?? [] as $lineKey => $qty) {
            $qty = (int) $qty;
            if ($qty < 1) {
                continue;
            }
            if (strpos((string)$lineKey, 'bundle-') === 0) {
                $bid = (int)str_replace('bundle-', '', (string)$lineKey);
                $p = get_cart_line_bundle($bid);
                if ($p !== null && $p['variant_id'] !== null) {
                    $stockCheckIds[] = $p['variant_id'];
                    $stockCheckMeta[] = ['line_key' => $lineKey, 'qty' => $qty, 'bundle_qty' => $p['quantity'], 'is_bundle' => true];
                }
            } else {
                $parsed = cart_parse_line_key((string) $lineKey);
                $rv = resolve_product_variant($parsed['product_id'], $parsed['variant_id']);
                $vid = $rv['variant_id'] ?? null;
                if ($vid === 0) $vid = null;
                if ($vid !== null) {
                    $stockCheckIds[] = $vid;
                    $stockCheckMeta[] = ['line_key' => $lineKey, 'qty' => $qty, 'is_bundle' => false];
                }
            }
        }

        $stockMap = [];
        if (!empty($stockCheckIds) && $pdoMain !== null) {
            $placeholders = implode(',', array_fill(0, count($stockCheckIds), '?'));
            $stockSt = $pdoMain->prepare("SELECT id, stock, label_ar, label_en FROM product_variants WHERE id IN ($placeholders)");
            $stockSt->execute($stockCheckIds);
            foreach ($stockSt->fetchAll() as $v) {
                $stockMap[(int)$v['id']] = $v;
            }
        }

        foreach ($_SESSION['cart'] ?? [] as $lineKey => $qty) {
            $qty = (int) $qty;
            if ($qty < 1) {
                continue;
            }
            if (strpos((string)$lineKey, 'bundle-') === 0) {
                $bid = (int)str_replace('bundle-', '', (string)$lineKey);
                $p = get_cart_line_bundle($bid);
                if ($p !== null) {
                    $vid = $p['variant_id'];
                    $qtyRequired = $qty * $p['quantity'];
                    if ($vid !== null && isset($stockMap[$vid])) {
                        $vRow = $stockMap[$vid];
                        if ((int)$vRow['stock'] >= 0 && (int)$vRow['stock'] < $qtyRequired) {
                            $vLabel = current_lang() === 'ar' ? $vRow['label_ar'] : $vRow['label_en'];
                            $errors[] = "الكمية المطلوبة من العرض ({$p['name']} - {$vLabel}) غير متوفرة بالمخزون.";
                            continue;
                        }
                    }
                    $sub = $p['price'] * $qty;
                    $totalForSubmit += $sub;
                    $orderLines[] = [
                        'product_id' => $p['product_id'],
                        'variant_id' => $vid,
                        'name' => $p['name'],
                        'qty' => $qty,
                        'unit_price' => $p['price'],
                        'line_total' => $sub,
                        'is_bundle' => true,
                        'bundle_qty' => $p['quantity'],
                    ];
                }
            } else {
                $parsed = cart_parse_line_key((string) $lineKey);
                $p = get_cart_line_product($parsed['product_id'], $parsed['variant_id']);
                if ($p !== null) {
                    $rv = resolve_product_variant($parsed['product_id'], $parsed['variant_id']);
                    $vid = $rv['variant_id'] ?? null;
                    if ($vid === 0) {
                        $vid = null;
                    }

                    if ($vid !== null && isset($stockMap[$vid])) {
                        $vRow = $stockMap[$vid];
                        if ((int)$vRow['stock'] >= 0 && (int)$vRow['stock'] < $qty) {
                            $vLabel = current_lang() === 'ar' ? $vRow['label_ar'] : $vRow['label_en'];
                            $errors[] = "الكمية المطلوبة من ({$p['name']} - {$vLabel}) غير متوفرة. المتاح حالياً: {$vRow['stock']}";
                            continue;
                        }
                    }

                    $sub = $p['price'] * $qty;
                    $totalForSubmit += $sub;

                    $orderLines[] = [
                        'product_id' => $parsed['product_id'],
                        'variant_id' => $vid,
                        'name' => $p['name'],
                        'qty' => $qty,
                        'unit_price' => $p['price'],
                        'line_total' => $sub,
                    ];
                }
            }
        }

        if ($orderLines === []) {
             $errors[] = t('cart_empty');
        }

        $appliedPromoCode = trim((string) ($_POST['applied_promo_code'] ?? ''));
        $discountAmount = 0.0;
        $promoCodeRow = null;
        if ($appliedPromoCode !== '' && $pdoMain !== null && $errors === []) {
            $promoSt = $pdoMain->prepare('SELECT id, code, discount_percentage, usage_limit, used_count FROM promo_codes WHERE code = ? AND active = 1');
            $promoSt->execute([$appliedPromoCode]);
            $promoCodeRow = $promoSt->fetch();
            if ($promoCodeRow) {
                if ($promoCodeRow['usage_limit'] > 0 && $promoCodeRow['used_count'] >= $promoCodeRow['usage_limit']) {
                    $errors[] = t('checkout_err_promo_limit');
                } else {
                    $discountPct = (int) $promoCodeRow['discount_percentage'];
                    $discountAmount = $totalForSubmit * ($discountPct / 100);
                }
            } else {
                $errors[] = t('checkout_err_promo_invalid');
            }
        }

        if ($errors === []) {
            $pdo = medal_pdo();
            if ($pdo !== null) {
                try {
                    try {
                        $pdo->exec("ALTER TABLE orders MODIFY customer_email VARCHAR(255) NULL DEFAULT ''");
                        $pdo->exec("ALTER TABLE orders MODIFY customer_phone VARCHAR(64) NULL DEFAULT ''");
                        $pdo->exec("ALTER TABLE orders MODIFY shipping_address TEXT NULL");
                        $pdo->exec("ALTER TABLE orders MODIFY city VARCHAR(128) NULL DEFAULT ''");
                        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS confirmation_code VARCHAR(16) NULL");
                        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS is_confirmed TINYINT(1) NOT NULL DEFAULT 0");
                        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS bot_step VARCHAR(32) NOT NULL DEFAULT 'initial'");
                        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS confirmed_at DATETIME NULL");
                        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS wa_conf_sent TINYINT(1) NOT NULL DEFAULT 0");
                        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(32) NOT NULL DEFAULT 'cod'");
                        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(32) NOT NULL DEFAULT 'unpaid'");
                        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
                        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS waived_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
                        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL");
                        $pdo->exec("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS variant_label_snapshot VARCHAR(255) NULL");
                    } catch (Throwable $me) {}

                    $pdo->beginTransaction();
                    $orderNumber = 'MED-' . strtoupper(bin2hex(random_bytes(4)));
                    $confirmationCode = (string) random_int(1000, 9999);
                    $emailToSave = $email !== '' ? $email : ($phone . '@guest.zeinperfumes.com');
                    
                    $ins = $pdo->prepare(
                        'INSERT INTO orders (order_number, confirmation_code, status, customer_name, customer_email, customer_phone, shipping_address, address_landmark, city, admin_notes, promo_code, subtotal, discount_amount, shipping_cost, total, is_confirmed, bot_step)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 0, \'initial\')'
                    );
                    $ins->execute([
                        $orderNumber,
                        $confirmationCode,
                        'pending',
                        $name,
                        $emailToSave,
                        $phone !== '' ? $phone : null,
                        $address,
                        $landmark !== '' ? $landmark : null,
                        $city,
                        $notes !== '' ? $notes : null,
                        $promoCodeRow ? $promoCodeRow['code'] : null,
                        round($totalForSubmit, 2),
                        $discountAmount > 0 ? round($discountAmount, 2) : null,
                        $shippingCost,
                        round($totalForSubmit - $discountAmount + $shippingCost, 2),
                    ]);
                    $oid = (int) $pdo->lastInsertId();
                    $variantIds = [];
                    foreach ($orderLines as $ln) {
                        if (!empty($ln['variant_id'])) {
                            $variantIds[] = (int) $ln['variant_id'];
                        }
                    }
                    $variantLabelMap = [];
                    if (!empty($variantIds)) {
                        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
                        $vSt = $pdo->prepare("SELECT id, label_en FROM product_variants WHERE id IN ($placeholders)");
                        $vSt->execute($variantIds);
                        foreach ($vSt->fetchAll() as $v) {
                            $variantLabelMap[(int)$v['id']] = $v['label_en'];
                        }
                    }
                    $itemSt = $pdo->prepare(
                        'INSERT INTO order_items (order_id, product_id, variant_id, product_name_snapshot, variant_label_snapshot, qty, unit_price, line_total) VALUES (?,?,?,?,?,?,?,?)'
                    );
                    foreach ($orderLines as $ln) {
                        $vlabel = !empty($ln['variant_id']) ? ($variantLabelMap[$ln['variant_id']] ?? null) : null;
                        $itemSt->execute([
                            $oid,
                            $ln['product_id'],
                            $ln['variant_id'],
                            $ln['name'],
                            $vlabel,
                            $ln['qty'],
                            $ln['unit_price'],
                            $ln['line_total'],
                        ]);
                    }

                    if ($promoCodeRow) {
                        $updPromo = $pdo->prepare('UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?');
                        $updPromo->execute([$promoCodeRow['id']]);
                    }
                    $pdo->commit();

                    // Notify Admins
                    try {
                        add_admin_notification(
                            'new_order',
                            'طلب جديد: ' . $orderNumber,
                            'New Order: ' . $orderNumber,
                            'لديك طلب جديد من ' . $name . ' بقيمة ' . format_price($totalForSubmit - $discountAmount + $shippingCost),
                            'New order from ' . $name . ' total ' . format_price($totalForSubmit - $discountAmount + $shippingCost),
                            'order_view.php?id=' . $oid
                        );
                    } catch (Throwable $ne) {}

                    // Deduct stock — runs AFTER commit so failure never blocks the order
                    try {
                        $stockSt = $pdo->prepare(
                            'UPDATE product_variants SET stock = GREATEST(0, stock - ?) WHERE id = ? AND stock >= 0'
                        );
                        foreach ($orderLines as $ln) {
                            if (!empty($ln['variant_id'])) {
                                $qtyToDeduct = !empty($ln['is_bundle']) ? $ln['qty'] * $ln['bundle_qty'] : $ln['qty'];
                                $stockSt->execute([$qtyToDeduct, (int) $ln['variant_id']]);
                            }
                        }
                    } catch (Throwable $e) {
                        error_log('Error in checkout.php stock deduction: ' . $e->getMessage());
                    }
                    
                    // Automatic Client Sync
                    if ($email !== '') {
                        try {
                            $checkClient = $pdo->prepare('SELECT id FROM clients WHERE email = ?');
                            $checkClient->execute([$email]);
                            $existingClient = $checkClient->fetch();

                            if (!$existingClient) {
                                $insClient = $pdo->prepare('INSERT INTO clients (name, email, phone, created_at) VALUES (?, ?, ?, NOW())');
                                $insClient->execute([$name, $email, $phone]);
                            } else {
                                $updClient = $pdo->prepare('UPDATE clients SET name = ?, phone = COALESCE(phone, ?) WHERE id = ?');
                                $updClient->execute([$name, $phone, $existingClient['id']]);
                            }
                        } catch (Throwable $e) {
                            error_log('Error in checkout.php client sync: ' . $e->getMessage());
                        }
                    }
                    
                    // Send Email Notification to Customer
                    if ($email !== '') {
                        try {
                            require_once __DIR__ . '/includes/mail_helper.php';
                            $orderTotal = (float)($totalForSubmit - $discountAmount + $shippingCost);
                            send_order_confirmation_email($email, $orderNumber, $orderTotal, $name);
                        } catch (Throwable $e) {
                            error_log('Error in checkout.php send_order_confirmation_email: ' . $e->getMessage());
                        }
                    }

                    // Send Email Notification to Admin
                    try {
                        require_once __DIR__ . '/includes/mail_helper.php';
                        $orderTotal = (float)($totalForSubmit - $discountAmount + $shippingCost);
                        send_admin_new_order_notification($orderNumber, $orderTotal, $name, $orderLines, $phone, $address, $city, $notes);
                    } catch (Throwable $e) {
                        error_log('Error in checkout.php send_admin_new_order_notification: ' . $e->getMessage());
                    }

                    // Trigger WhatsApp Bot Notification (1 - Confirm, 2 - Cancel, 3 - Edit)
                    try {
                        $orderTotal = (float)($totalForSubmit - $discountAmount + $shippingCost);
                        require_once __DIR__ . '/includes/whatsapp_helper.php';
                        $waResult = send_whatsapp_order_bot_notification($oid, $orderNumber, $name, $phone, $orderTotal, $orderLines, (float)$shippingCost);
                        $_SESSION['last_wa_url'] = $waResult['fallback_wa_url'] ?? contact_whatsapp_url(1);
                    } catch (Throwable $we) {
                        $_SESSION['last_wa_url'] = contact_whatsapp_url(1);
                    }

                    $_SESSION['cart'] = [];
                    header('Location: ' . url('order_success.php?id=' . $oid));
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('Error in checkout.php order submission: ' . $e->getMessage());
                    $errors[] = (current_lang() === 'ar' ? 'حدث خطأ: ' : 'Error: ') . $e->getMessage();
                }
            } else {
                $errors[] = 'Orders require the database. Import schema and run migrate.php.';
            }
        }
    }
}

$lines = [];
$total = 0.0;

$cartVariantIds = [];
$cartVariantMeta = [];
foreach ($_SESSION['cart'] ?? [] as $lineKey => $qty) {
    $qty = (int) $qty;
    if ($qty < 1) continue;
    if (strpos((string)$lineKey, 'bundle-') === 0) continue;
    $parsed = cart_parse_line_key((string) $lineKey);
    $rv = resolve_product_variant($parsed['product_id'], $parsed['variant_id']);
    $vid = $rv['variant_id'] ?? null;
    if ($vid === 0) $vid = null;
    if ($vid !== null) {
        $cartVariantIds[] = $vid;
        $cartVariantMeta[$lineKey] = $vid;
    }
}
$cartVariantLabelMap = [];
if (!empty($cartVariantIds) && $pdoMain !== null) {
    $placeholders = implode(',', array_fill(0, count($cartVariantIds), '?'));
    $vSt = $pdoMain->prepare("SELECT id, label_ar, label_en FROM product_variants WHERE id IN ($placeholders)");
    $vSt->execute($cartVariantIds);
    foreach ($vSt->fetchAll() as $v) {
        $cartVariantLabelMap[(int)$v['id']] = $v;
    }
}

foreach ($_SESSION['cart'] ?? [] as $lineKey => $qty) {
    $qty = (int) $qty;
    if ($qty < 1) {
        continue;
    }
    if (strpos((string)$lineKey, 'bundle-') === 0) {
        $bid = (int)str_replace('bundle-', '', (string)$lineKey);
        $p = get_cart_line_bundle($bid);
        if ($p !== null) {
            $sub = $p['price'] * $qty;
            $total += $sub;
            
            $lines[] = [
                'product_id' => $p['product_id'],
                'variant_id' => $p['variant_id'],
                'variant_label' => current_lang() === 'ar' ? 'عرض خاص 🎁' : 'Special Offer 🎁',
                'name' => $p['name'],
                'image' => $p['image'],
                'qty' => $qty,
                'unit_price' => $p['price'],
                'line_total' => $sub,
                'line_key' => (string) $lineKey,
                'is_bundle' => true,
            ];
        }
    } else {
        $parsed = cart_parse_line_key((string) $lineKey);
        $p = get_cart_line_product($parsed['product_id'], $parsed['variant_id']);
        if ($p !== null) {
            $sub = $p['price'] * $qty;
            $total += $sub;

            $rv = resolve_product_variant($parsed['product_id'], $parsed['variant_id']);
            $vid = $rv['variant_id'] ?? null;
            if ($vid === 0) {
                $vid = null;
            }

            $variantLabel = null;
            if ($vid !== null && isset($cartVariantLabelMap[$vid])) {
                $vRow = $cartVariantLabelMap[$vid];
                $variantLabel = current_lang() === 'ar' ? $vRow['label_ar'] : $vRow['label_en'];
            }

            $lines[] = [
                'product_id' => $parsed['product_id'],
                'variant_id' => $vid,
                'variant_label' => $variantLabel,
                'name' => $p['name'],
                'image' => $p['image'],
                'qty' => $qty,
                'unit_price' => $p['price'],
                'line_total' => $sub,
                'line_key' => (string) $lineKey,
            ];
        }
    }
}

$extraCss = [
    url('assets/css/pages/checkout.css?v=' . filemtime(__DIR__ . '/assets/css/pages/checkout.css'))
];

require __DIR__ . '/includes/header.php';
?>



<section class="checkout-layout">
    <div class="neo-container">
        <?php if ($done): ?>
            <div class="neo-main" style="align-items: center; text-align: center; max-width: 600px; margin: 0 auto;">
                <div class="neo-box" style="width: 100%; padding: 4rem 2rem;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?= esc(t('checkout_success_title')) ?></h2>
                    <p style="color: var(--neo-muted); margin-bottom: 2rem;"><?= esc(t('checkout_success_lead', ['number' => $orderNumber])) ?></p>
                    
                    <?php if (isset($_SESSION['last_wa_url'])): ?>
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                            <p style="color: #166534; font-weight: 600; margin-bottom: 1rem;">
                                <?= current_lang() === 'ar' ? 'يرجى الضغط على الزر أدناه لتأكيد طلبك عبر واتساب' : 'Please click the button below to confirm your order via WhatsApp' ?>
                            </p>
                            <a href="<?= esc($_SESSION['last_wa_url']) ?>" target="_blank" style="background: #25d366; color: white; padding: 1rem 2rem; border-radius: 50px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                <?= current_lang() === 'ar' ? 'تأكيد الطلب عبر واتساب' : 'Confirm on WhatsApp' ?>
                            </a>
                        </div>
                        <?php unset($_SESSION['last_wa_url']); ?>
                    <?php endif; ?>
                    
                    <a class="neo-submit" href="<?= esc(url('index.php')) ?>" style="display:inline-block; width:auto; padding: 0.8rem 2rem; text-decoration:none;"><?= esc(t('nav_home')) ?></a>
                </div>
            </div>
        <?php elseif ($lines === []): ?>
            <div class="neo-main" style="align-items: center; text-align: center; max-width: 600px; margin: 0 auto;">
                <div class="neo-box" style="width: 100%; padding: 4rem 2rem;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    <p style="font-size: 1.1rem; color: var(--neo-muted); margin-bottom: 2rem;"><?= esc(t('cart_empty')) ?></p>
                    <a href="<?= esc(url('products.php')) ?>" class="neo-submit" style="display:inline-block; width:auto; padding: 0.8rem 2rem; text-decoration:none;"><?= esc(t('cart_browse')) ?></a>
                </div>
            </div>
        <?php else: ?>
            
            <div class="neo-main">

                <?php if ($errors !== []): ?>
                    <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 1rem; border-radius: 8px;">
                        <ul style="margin: 0; padding-left: 1.5rem;">
                            <?php foreach ($errors as $e): ?>
                                <li><?= esc($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Form Container -->
                <div class="neo-dashed-box">
                    <h3 class="neo-form-title"><?= esc(current_lang() == 'ar' ? 'يرجى تعبئة بياناتك لإتمام الطلب' : 'Please fill your information to complete the order') ?></h3>
                    
                    <form method="post" action="<?= esc(url('checkout.php')) ?>" id="main-checkout-form" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="lang" value="<?= esc(current_lang()) ?>">
                        <input type="hidden" name="action" value="submit_order">
                        <input type="hidden" name="applied_promo_code" id="applied_promo_code" value="">
                        <input type="hidden" name="address_landmark" id="address_landmark_hidden" value="">
                        <input type="hidden" name="payment_method" id="payment_method_hidden" value="cod">

                        <!-- Name -->
                        <div class="neo-input-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <input type="text" name="customer_name" placeholder="<?= esc(t('checkout_name')) ?>" required value="<?= esc(trim((string) ($_POST['customer_name'] ?? ''))) ?>">
                        </div>

                        <!-- Phone -->
                        <div class="neo-input-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            <input type="tel" name="customer_phone" placeholder="<?= esc(t('checkout_phone')) ?>" required value="<?= esc(trim((string) ($_POST['customer_phone'] ?? ''))) ?>" dir="ltr">
                        </div>

                        <!-- Phone 2 -->
                        <div class="neo-input-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            <input type="tel" name="customer_phone_2" placeholder="<?= esc(t('checkout_phone_2')) ?>" value="<?= esc(trim((string) ($_POST['customer_phone_2'] ?? ''))) ?>" dir="ltr">
                        </div>

                        <!-- Email (Optional, hidden in design but kept functionality) -->
                        <div class="neo-input-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            <input type="email" name="customer_email" placeholder="<?= esc(t('checkout_email')) ?> (<?= esc(current_lang() == 'ar' ? 'إختياري' : 'Optional') ?>)" value="<?= esc(trim((string) ($_POST['customer_email'] ?? ''))) ?>">
                        </div>

                        <!-- City Selection -->
                        <div class="neo-select-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16"></path><path d="M1 21h22"></path><path d="M8 7h2"></path><path d="M8 11h2"></path><path d="M8 15h2"></path><path d="M14 7h2"></path><path d="M14 11h2"></path><path d="M14 15h2"></path></svg>
                            <select name="city_id" id="checkout_city_id" required>
                                <option value="" disabled><?= esc(t('checkout_city')) ?></option>
                                <?php foreach ($shippingCities as $sc): ?>
                                    <?php $isCairo = (stripos((string)$sc['name_en'], 'Cairo') !== false || mb_strpos((string)$sc['name_ar'], 'قاهر') !== false); ?>
                                    <option value="<?= (int) $sc['id'] ?>" data-cost="<?= (float) $sc['shipping_cost'] ?>" <?= $isCairo ? 'selected' : '' ?>><?= esc(current_lang() === 'ar' ? $sc['name_ar'] : $sc['name_en']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <svg class="neo-select-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>

                        <!-- Address Section -->
                        <div class="neo-input-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--gold-dark);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <input type="text" name="shipping_address" placeholder="<?= esc(t('checkout_address')) ?>" required value="<?= esc(trim((string) ($_POST['shipping_address'] ?? ''))) ?>">
                        </div>





                    </form>
                </div>
            </div>
            
            <div class="neo-sidebar">
                <div class="neo-box" style="padding: 1.5rem;">
                    
                    <div style="max-height: 50vh; overflow-y: auto; padding-right: 0.5rem; margin-bottom: 1rem;">
                        <?php foreach($lines as $ln): ?>
                        <div class="neo-product">
                            <?php if (!empty($ln['is_bundle'])): ?>
                                <span class="neo-product-img product-visual <?= esc(product_image_class($ln['image'])) ?>"<?= product_image_style($ln['image']) ?>></span>
                            <?php else: ?>
                                <a href="<?= esc(url('product.php?id=' . $ln['product_id'])) ?>" class="neo-product-img product-visual <?= esc(product_image_class($ln['image'])) ?>"<?= product_image_style($ln['image']) ?>></a>
                            <?php endif; ?>
                            
                            <div class="neo-product-info">
                                <div class="neo-product-head">
                                    <h4 class="neo-product-title">
                                        <?php if (!empty($ln['is_bundle'])): ?>
                                            <?= esc($ln['name']) ?>
                                        <?php else: ?>
                                            <a href="<?= esc(url('product.php?id=' . $ln['product_id'])) ?>" style="text-decoration:none; color:inherit;"><?= esc($ln['name']) ?></a>
                                        <?php endif; ?>
                                    </h4>
                                    <div class="neo-product-price" dir="ltr" data-line-price-key="<?= esc($ln['line_key']) ?>"><?= esc(format_price($ln['line_total'])) ?></div>
                                </div>
                                <?php if ($ln['variant_label']): ?>
                                    <div class="neo-product-meta"><?= esc($ln['variant_label']) ?></div>
                                <?php endif; ?>
                                
                                <!-- Redundant line total removed -->
                                
                                <div class="neo-qty-controls" data-line-key="<?= esc($ln['line_key']) ?>">
                                    <div class="neo-qty-box">
                                        <button type="button" class="neo-qty-btn btn-qty-plus">+</button>
                                        <input type="number" name="qty" value="<?= esc((string)$ln['qty']) ?>" min="1" class="neo-qty-input" readonly>
                                        <button type="button" class="neo-qty-btn btn-qty-minus">-</button>
                                    </div>
                                    
                                    <button type="button" class="neo-trash-btn btn-remove-item">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div id="promo-toggle-wrapper" style="margin-bottom: 1rem;">
                        <button type="button" id="toggle-promo-btn" style="background:none; border:none; color:var(--gold-dark); font-weight:600; font-size:1.1rem; cursor:pointer; padding:0; font-family:inherit; text-decoration:none;">
                            <?= esc(current_lang() == 'ar' ? 'هل لديك كود خصم؟' : 'Have a discount code?') ?>
                        </button>
                    </div>

                    <div id="promo-input-section" style="display: none; margin-bottom: 1.5rem; gap: 0.5rem; transition: all 0.3s ease;">
                        <input type="text" id="promo-code-input" placeholder="<?= esc(t('checkout_promo_code_label')) ?>" style="flex:1; padding:0.8rem 1rem; border:1px solid var(--neo-border); border-radius:8px; font-family:inherit; text-transform:uppercase;">
                        <button type="button" id="btn-apply-promo" style="background:var(--neo-heading); color:#fff; border:none; border-radius:8px; padding:0 1.5rem; cursor:pointer; font-weight:600; font-size: 0.95rem;"><?= esc(t('checkout_apply')) ?></button>
                    </div>
                    <div id="promo-message" style="margin-bottom: 1.5rem; font-size:0.9rem;"></div>
                    
                    <div class="neo-summary-table">
                        <div class="neo-summary-row">
                            <span><?= esc(t('checkout_product_total')) ?></span>
                            <span class="neo-summary-val" dir="ltr" id="ui-subtotal" data-val="<?= esc((string)$total) ?>"><?= esc(format_price($total)) ?></span>
                        </div>
                        <div class="neo-summary-row" id="row-discount" style="display:none; color: #10b981;">
                            <span><?= esc(t('checkout_discount')) ?></span>
                            <span class="neo-summary-val" dir="ltr" id="ui-discount"><strong>—</strong></span>
                        </div>
                        <div class="neo-summary-row">
                            <span><?= esc(t('checkout_shipping_cost')) ?></span>
                            <span class="neo-summary-val" dir="ltr" id="ui-shipping"><strong>—</strong></span>
                        </div>
                        <div class="neo-summary-row neo-total">
                            <span><?= esc(t('checkout_total')) ?></span>
                            <strong dir="ltr" id="ui-total"><?= esc(format_price($total)) ?></strong>
                        </div>
                    </div>
                    
                </div>
                
                <div class="neo-trust-badges" style="display:flex; justify-content:center; gap:1rem; margin:1rem 0; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:0.4rem; background:#f0fdf4; border:1px solid #bbf7d0; padding:0.5rem 0.8rem; border-radius:8px; font-size:0.8rem; font-weight:600; color:#166534;">
                        <span>🔒</span> <?= esc(current_lang() === 'ar' ? 'دفع آمن' : 'Secure Checkout') ?>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.4rem; background:#eff6ff; border:1px solid #bfdbfe; padding:0.5rem 0.8rem; border-radius:8px; font-size:0.8rem; font-weight:600; color:#1e40af;">
                        <span>🚚</span> <?= esc(current_lang() === 'ar' ? 'توصيل سريع' : 'Fast Delivery') ?>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.4rem; background:#fff7ed; border:1px solid #fed7aa; padding:0.5rem 0.8rem; border-radius:8px; font-size:0.8rem; font-weight:600; color:#9a3412;">
                        <span>↩️</span> <?= esc(current_lang() === 'ar' ? 'استرجاع سهل' : 'Easy Returns') ?>
                    </div>
                </div>
                
                <button type="submit" form="main-checkout-form" class="neo-submit"><?= esc(t('checkout_complete_order')) ?></button>
            </div>
            
        <?php endif; ?>
    </div>
</section>

<script src="<?= url('assets/js/checkout.js?v=' . filemtime(__DIR__ . '/assets/js/checkout.js')) ?>" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
