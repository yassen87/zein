<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$pdo = medal_pdo();
$orderId = (int) ($_GET['id'] ?? $_POST['order_id'] ?? $_GET['edit'] ?? 0);

// Handle order updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    admin_verify_csrf();
    
    switch ($_POST['action']) {
        case 'update_order':
            $customerName = trim($_POST['customer_name']);
            $customerPhone = trim($_POST['customer_phone']);
            $customerEmail = trim($_POST['customer_email']);
            $shippingAddress = trim($_POST['shipping_address']);
            $status = $_POST['status'];
            $adminNotes = trim($_POST['admin_notes']);
            
            $update = $pdo->prepare("
                UPDATE orders 
                SET customer_name = ?, customer_phone = ?, customer_email = ?, 
                    shipping_address = ?, status = ?, admin_notes = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([$customerName, $customerPhone, $customerEmail, $shippingAddress, $status, $adminNotes, $orderId]);
            
            $_SESSION['success'] = 'تم تحديث بيانات الطلب بنجاح!';
            header('Location: order_management.php?id=' . $orderId);
            exit;
            
        case 'add_item':
            $productId  = (int) $_POST['product_id'];
            $variantId  = !empty($_POST['variant_id']) ? (int) $_POST['variant_id'] : null;
            $quantity   = max(1, (int) $_POST['quantity']);

            // Get product name
            $pst = $pdo->prepare('SELECT name_ar, name_en FROM products WHERE id = ?');
            $pst->execute([$productId]);
            $product = $pst->fetch();

            if ($product) {
                // Get variant price — if variantId given use it, else cheapest variant
                if ($variantId) {
                    $vst = $pdo->prepare('SELECT id, label_ar, price FROM product_variants WHERE id = ? AND product_id = ?');
                    $vst->execute([$variantId, $productId]);
                } else {
                    $vst = $pdo->prepare('SELECT id, label_ar, price FROM product_variants WHERE product_id = ? ORDER BY price ASC LIMIT 1');
                    $vst->execute([$productId]);
                }
                $variant = $vst->fetch();

                if (!$variant) {
                    $_SESSION['error'] = 'لا توجد أسعار لهذا المنتج.';
                    header('Location: order_management.php?id=' . $orderId);
                    exit;
                }

                $unitPrice = (float) $variant['price'];
                $lineTotal = $quantity * $unitPrice;
                $variantLabel = $variant['label_ar'] ?? null;
                $usedVariantId = (int) $variant['id'];

                $insert = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, variant_id, product_name_snapshot, variant_label_snapshot, qty, unit_price, line_total)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert->execute([$orderId, $productId, $usedVariantId, $product['name_ar'], $variantLabel, $quantity, $unitPrice, $lineTotal]);

                // Update order total
                update_order_totals($pdo, $orderId);

                $_SESSION['success'] = 'تم إضافة المنتج للطلب!';
            } else {
                $_SESSION['error'] = 'المنتج غير موجود.';
            }
            header('Location: order_management.php?id=' . $orderId);
            exit;

        case 'add_internal_item':
            $internalProductId = (int) $_POST['internal_product_id'];
            $quantity = (int) $_POST['quantity'];
            
            $insert = $pdo->prepare("
                INSERT INTO order_internal_products (order_id, internal_product_id, quantity)
                VALUES (?, ?, ?)
            ");
            $insert->execute([$orderId, $internalProductId, $quantity]);
            
            $_SESSION['success'] = 'تم إضافة الهدية/المنتج الداخلي للطلب!';
            header('Location: order_management.php?id=' . $orderId);
            exit;
            
        case 'remove_item':
            $itemId = (int) $_POST['item_id'];
            $delete = $pdo->prepare("DELETE FROM order_items WHERE id = ?");
            $delete->execute([$itemId]);
            
            update_order_totals($pdo, $orderId);
            
            $_SESSION['success'] = 'تم حذف المنتج من الطلب!';
            header('Location: order_management.php?id=' . $orderId);
            exit;

        case 'remove_internal_item':
            $itemId = (int) $_POST['item_id'];
            $delete = $pdo->prepare("DELETE FROM order_internal_products WHERE id = ?");
            $delete->execute([$itemId]);
            
            $_SESSION['success'] = 'تم حذف المنتج الداخلي من الطلب!';
            header('Location: order_management.php?id=' . $orderId);
            exit;
    }
}

function update_order_totals(PDO $pdo, int $orderId): void
{
    $st = $pdo->prepare('SELECT SUM(line_total) FROM order_items WHERE order_id = ?');
    $st->execute([$orderId]);
    $subtotal = (float) $st->fetchColumn();
    
    // Get shipping cost
    $sst = $pdo->prepare('SELECT shipping_cost, discount_amount FROM orders WHERE id = ?');
    $sst->execute([$orderId]);
    $order = $sst->fetch();
    $shipping = (float) ($order['shipping_cost'] ?? 0);
    $discount = (float) ($order['discount_amount'] ?? 0);
    
    $total = $subtotal + $shipping - $discount;
    
    $u = $pdo->prepare('UPDATE orders SET subtotal = ?, total = ? WHERE id = ?');
    $u->execute([$subtotal, $total, $orderId]);
}

$order = null;
$items = [];
$internalItems = [];
if ($orderId > 0) {
    $st = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$orderId]);
    $order = $st->fetch();
    
    if ($order) {
        $it = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $it->execute([$orderId]);
        $items = $it->fetchAll();
        
        $iit = $pdo->prepare('
            SELECT oip.*, ip.name_ar, ip.name_en, ip.type 
            FROM order_internal_products oip 
            JOIN internal_products ip ON oip.internal_product_id = ip.id 
            WHERE oip.order_id = ?
        ');
        $iit->execute([$orderId]);
        $internalItems = $iit->fetchAll();
    }
}

// Load all products with their variants for the quick-add form
$allProducts = $pdo->query('SELECT id, name_ar, name_en, primary_image_key FROM products ORDER BY name_ar ASC')->fetchAll();
try {
    $allVariants = $pdo->query('SELECT id, product_id, label_ar, price, stock FROM product_variants ORDER BY sort_order ASC, id ASC')->fetchAll();
} catch (Throwable) {
    // stock column may not exist yet — fallback without it
    $allVariants = $pdo->query('SELECT id, product_id, label_ar, price, 0 as stock FROM product_variants ORDER BY sort_order ASC, id ASC')->fetchAll();
}
// Group variants by product_id for JS use
$variantsByProduct = [];
foreach ($allVariants as $v) {
    $variantsByProduct[(int)$v['product_id']][] = $v;
}
$allInternal = $pdo->query('SELECT id, name_ar, name_en, type FROM internal_products ORDER BY name_ar ASC')->fetchAll();

$pageTitle = 'تعديل الطلب #' . ($order ? $order['order_number'] : '');
require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions" style="border-bottom: 1px solid var(--admin-card-border); padding-bottom: 1.5rem; margin-bottom: 2rem;">
    <div>
        <h1 style="margin: 0; font-size: 1.8rem; font-weight: 800; color: var(--admin-heading);">
            تعديل الطلب <?= $order ? '#' . esc($order['order_number']) : '' ?>
        </h1>
        <p class="admin-muted" style="margin: 0.5rem 0 0; font-size: 0.9rem;">
            يمكنك هنا تعديل تفاصيل العميل، إضافة أو حذف المنتجات والهدايا.
        </p>
    </div>
    <a href="order_view.php?id=<?= $orderId ?>" class="admin-btn admin-btn--secondary" style="display: inline-flex; align-items: center; gap: 6px;">
        👁️ عرض تفاصيل الطلب
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="admin-notice" style="background: rgba(16, 185, 129, 0.15); color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600;">
        ✓ <?= esc($_SESSION['success']) ?>
        <?php unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="admin-error" style="background: rgba(239, 68, 68, 0.15); color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600;">
        ⚠️ <?= esc($_SESSION['error']) ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if (!$order): ?>
    <div class="admin-error">الطلب غير موجود أو تم حذفه.</div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
        
        <!-- LEFT COLUMN: Forms, Items, Gifts -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            
            <!-- Customer Info -->
            <div class="admin-card" style="padding: 1.75rem;">
                <h2 style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1.25rem; font-weight: 700; color: var(--admin-heading); display: flex; align-items: center; gap: 8px;">
                    👤 بيانات العميل والشحن
                </h2>
                <form method="POST" class="admin-form" style="margin: 0;">
                    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_order">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label for="customer_name" style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.4rem;">الاسم بالكامل</label>
                            <input type="text" id="customer_name" name="customer_name" value="<?= esc($order['customer_name']) ?>" required class="admin-input" style="padding: 0.6rem; border-radius: 8px;">
                        </div>
                        <div>
                            <label for="customer_phone" style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.4rem;">الهاتف</label>
                            <input type="text" id="customer_phone" name="customer_phone" value="<?= esc($order['customer_phone']) ?>" required class="admin-input" style="padding: 0.6rem; border-radius: 8px;">
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <label for="customer_email" style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.4rem;">البريد الإلكتروني</label>
                        <input type="email" id="customer_email" name="customer_email" value="<?= esc($order['customer_email']) ?>" class="admin-input" style="padding: 0.6rem; border-radius: 8px;">
                    </div>

                    <div style="margin-top: 1rem;">
                        <label for="shipping_address" style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.4rem;">العنوان بالتفصيل</label>
                        <textarea id="shipping_address" name="shipping_address" rows="3" class="admin-input" style="padding: 0.6rem; border-radius: 8px; resize: vertical;"><?= esc($order['shipping_address']) ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                        <div>
                            <label for="status" style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.4rem;">حالة الطلب</label>
                            <select id="status" name="status" class="admin-input" style="padding: 0.6rem; border-radius: 8px; font-weight: 700;">
                                <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= esc(admin_order_status_label($s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 1rem;">
                        <label for="admin_notes" style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.4rem;">ملاحظات داخلية (لا يراها العميل)</label>
                        <textarea id="admin_notes" name="admin_notes" rows="2" class="admin-input" style="padding: 0.6rem; border-radius: 8px; resize: vertical;" placeholder="أضف ملاحظات إدارية هنا..."><?= esc($order['admin_notes'] ?? '') ?></textarea>
                    </div>

                    <div style="margin-top: 1.5rem; text-align: inline-end;">
                        <button type="submit" class="admin-btn admin-btn--primary" style="padding: 0.65rem 1.5rem; font-weight: 700; border-radius: 8px;">💾 حفظ بيانات العميل</button>
                    </div>
                </form>
            </div>

            <!-- Items -->
            <div class="admin-card" style="padding: 1.75rem;">
                <h2 style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1.25rem; font-weight: 700; color: var(--admin-heading); display: flex; align-items: center; gap: 8px;">
                    🛒 المنتجات الحالية في الطلب
                </h2>
                <div class="admin-table-wrap">
                    <table class="admin-table" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th style="text-align: center;">الكمية</th>
                                <th>سعر الوحدة</th>
                                <th>الإجمالي</th>
                                <th style="text-align: center;">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--admin-gold);">
                                        <?= esc($item['product_name_snapshot']) ?>
                                        <?php if (!empty($item['variant_label_snapshot'])): ?>
                                            <span style="font-size: 0.75rem; font-weight: normal; color: var(--admin-text-muted); display: block;">الحجم: <?= esc($item['variant_label_snapshot']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center; font-weight: 700;"><?= (int)$item['qty'] ?></td>
                                    <td><?= number_format((float)$item['unit_price'], 2) ?> <?= esc(t('currency')) ?></td>
                                    <td style="font-weight: 700; color: var(--admin-heading);"><?= number_format((float)$item['line_total'], 2) ?> <?= esc(t('currency')) ?></td>
                                    <td style="text-align: center;">
                                        <form method="POST" onsubmit="return confirm('هل تريد بالتأكيد حذف هذا المنتج من الطلب؟')" style="margin: 0; display: inline-block;">
                                            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                            <input type="hidden" name="action" value="remove_item">
                                            <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                            <button type="submit" class="admin-btn admin-btn--sm" style="background: rgba(239, 68, 68, 0.1); color: #dc2626; border-color: rgba(239, 68, 68, 0.2); padding: 0.3rem 0.6rem; font-weight: 700;">🗑️ حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Add Item Inline Box -->
                <div style="margin-top: 1.5rem; background: rgba(212, 175, 55, 0.03); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--admin-card-border);">
                    <h3 style="margin-top: 0; font-size: 1rem; margin-bottom: 1rem; color: var(--admin-gold); font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        ➕ إضافة منتج جديد للطلب
                    </h3>
                    <form method="POST" class="admin-form" style="margin: 0;">
                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                        <input type="hidden" name="action" value="add_item">
                        <div style="display: grid; grid-template-columns: 1.5fr 1.5fr 80px 100px; gap: 12px; align-items: end;">
                            <div>
                                <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">اختر المنتج</label>
                                <select name="product_id" id="add-product-select" onchange="loadVariants(this.value)" required class="admin-input" style="padding: 0.55rem; border-radius: 8px;">
                                    <option value="">اختر منتجاً...</option>
                                    <?php foreach ($allProducts as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>"><?= esc($p['name_ar']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">الحجم / المتغير</label>
                                <select name="variant_id" id="add-variant-select" required class="admin-input" style="padding: 0.55rem; border-radius: 8px;">
                                    <option value="">اختر الحجم...</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">الكمية</label>
                                <input type="number" name="quantity" value="1" min="1" required class="admin-input" style="padding: 0.55rem; border-radius: 8px;">
                            </div>
                            <button type="submit" class="admin-btn admin-btn--primary" style="margin-top: 0; padding: 0.6rem; font-weight: 700; width: 100%; border-radius: 8px;">إضافة للطلب</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Internal Items / Gifts -->
            <div class="admin-card" style="padding: 1.75rem;">
                <h2 style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1.25rem; font-weight: 700; color: var(--admin-heading); display: flex; align-items: center; gap: 8px;">
                    🎁 الهدايا والمنتجات الداخلية في الطلب
                </h2>
                <?php if (empty($internalItems)): ?>
                    <p class="admin-muted" style="margin: 0 0 1.5rem;">لا توجد هدايا أو عينات مضافة لهذا الطلب حالياً.</p>
                <?php else: ?>
                    <div class="admin-table-wrap">
                        <table class="admin-table" style="font-size: 0.9rem;">
                            <thead>
                                <tr>
                                    <th>المنتج الداخلي</th>
                                    <th>النوع</th>
                                    <th style="text-align: center;">الكمية</th>
                                    <th style="text-align: center;">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($internalItems as $item): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?= esc($item['name_ar']) ?></td>
                                        <td>
                                            <span class="admin-badge" style="background: rgba(212, 175, 55, 0.12); color: var(--admin-gold); padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">
                                                <?= esc($item['type']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center; font-weight: 700;"><?= (int)$item['quantity'] ?></td>
                                        <td style="text-align: center;">
                                            <form method="POST" onsubmit="return confirm('هل تريد حذف هذه الهدية؟')" style="margin: 0; display: inline-block;">
                                                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                                <input type="hidden" name="action" value="remove_internal_item">
                                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                                <button type="submit" class="admin-btn admin-btn--sm" style="background: rgba(239, 68, 68, 0.1); color: #dc2626; border-color: rgba(239, 68, 68, 0.2); padding: 0.3rem 0.6rem; font-weight: 700;">🗑️ حذف</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Add Gift Box -->
                <div style="margin-top: 1.5rem; background: rgba(212, 175, 55, 0.03); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--admin-card-border);">
                    <h3 style="margin-top: 0; font-size: 1rem; margin-bottom: 1rem; color: var(--admin-gold); font-weight: 700;">
                        🎁 إضافة هدية أو عينة جديدة
                    </h3>
                    <form method="POST" class="admin-form" style="margin: 0;">
                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                        <input type="hidden" name="action" value="add_internal_item">
                        <div style="display: grid; grid-template-columns: 2fr 100px 120px; gap: 12px; align-items: end;">
                            <div>
                                <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">المنتج الداخلي</label>
                                <select name="internal_product_id" required class="admin-input" style="padding: 0.55rem; border-radius: 8px;">
                                    <option value="">اختر هدية/عينة...</option>
                                    <?php foreach ($allInternal as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= esc($p['name_ar']) ?> (<?= esc($p['type']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">الكمية</label>
                                <input type="number" name="quantity" value="1" min="1" required class="admin-input" style="padding: 0.55rem; border-radius: 8px;">
                            </div>
                            <button type="submit" class="admin-btn admin-btn--secondary" style="margin-top: 0; padding: 0.6rem; font-weight: 700; width: 100%; border-radius: 8px;">إضافة الهدية</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Order Totals & Summary -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            
            <!-- Financial Totals Summary Card -->
            <div class="admin-card" style="padding: 1.5rem;">
                <h2 style="margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid var(--admin-card-border); padding-bottom: 0.75rem; margin-bottom: 1.25rem; color: var(--admin-heading);">
                    📊 ملخص حساب الطلب
                </h2>
                <div style="display: flex; flex-direction: column; gap: 0.8rem; font-size: 0.95rem;">
                    <div style="display: flex; justify-content: space-between; color: var(--admin-text-muted);">
                        <span>المجموع الفرعي:</span>
                        <strong style="color: var(--admin-heading);"><?= number_format((float)$order['subtotal'], 2) ?> <?= esc(t('currency')) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; color: var(--admin-text-muted);">
                        <span>تكلفة الشحن:</span>
                        <strong style="color: var(--admin-heading);"><?= number_format((float)$order['shipping_cost'], 2) ?> <?= esc(t('currency')) ?></strong>
                    </div>
                    <?php if ((float)($order['discount_amount'] ?? 0) > 0): ?>
                        <div style="display: flex; justify-content: space-between; color: #dc2626;">
                            <span>الخصم المطبق:</span>
                            <strong>- <?= number_format((float)$order['discount_amount'], 2) ?> <?= esc(t('currency')) ?></strong>
                        </div>
                    <?php endif; ?>
                    <hr style="border: 0; border-top: 1px solid var(--admin-card-border); margin: 0.5rem 0;">
                    <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; color: var(--admin-gold);">
                        <span>الإجمالي الكلي:</span>
                        <span><?= number_format((float)$order['total'], 2) ?> <?= esc(t('currency')) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Metadata Info Card -->
            <div class="admin-card" style="padding: 1.5rem;">
                <h3 style="margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid var(--admin-card-border); padding-bottom: 0.75rem; margin-bottom: 1rem; color: var(--admin-heading);">
                    ⚙️ تفاصيل النظام
                </h3>
                <div style="display: flex; flex-direction: column; gap: 0.8rem; font-size: 0.9rem; line-height: 1.6;">
                    <div>
                        <span style="color: var(--admin-text-muted); display: block; font-size: 0.8rem;">رقم الطلب:</span>
                        <strong style="color: var(--admin-heading);"><?= esc($order['order_number']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--admin-text-muted); display: block; font-size: 0.8rem;">تاريخ الإنشاء:</span>
                        <strong style="color: var(--admin-heading);"><?= esc($order['created_at']) ?></strong>
                    </div>
                    <?php if ($order['promo_code']): ?>
                        <div>
                            <span style="color: var(--admin-text-muted); display: block; font-size: 0.8rem;">كود الخصم المستخدم:</span>
                            <span class="admin-badge" style="background: rgba(16, 185, 129, 0.12); color: #059669; padding: 0.2rem 0.5rem; border-radius: 6px; font-weight: 700;">
                                <?= esc($order['promo_code']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
<?php endif; ?>

<script>
// Variants data from PHP — keyed by product_id
const variantsByProduct = <?= json_encode($variantsByProduct ?? []) ?>;

function loadVariants(productId) {
    const select = document.getElementById('add-variant-select');
    select.innerHTML = '<option value="">اختر الحجم...</option>';

    if (!productId || !variantsByProduct[productId]) return;

    variantsByProduct[productId].forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.id;
        const stock = parseInt(v.stock);
        const stockLabel = stock > 0 ? ` (متاح: ${stock})` : ' ⚠️ نفد';
        opt.textContent = `${v.label_ar} — ${parseFloat(v.price).toFixed(2)} ج.م${stockLabel}`;
        if (stock === 0) opt.style.color = '#dc2626';
        select.appendChild(opt);
    });

    // Auto-select first variant
    if (select.options.length > 1) select.selectedIndex = 1;
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
