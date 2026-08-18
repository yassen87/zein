<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
require_client();

$pdo = medal_pdo();
$isAr = current_lang() === 'ar';

$section = $_GET['section'] ?? 'orders';
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$message = '';
$messageType = '';

$clientEmail = $_SESSION['client_email'] ?? '';
$clientName = client_name();
$clientId = client_id();

// Load client data
$clientData = null;
if ($pdo) {
    try {
        $st = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
        $st->execute([$clientId]);
        $clientData = $st->fetch();
    } catch (Throwable $e) {}
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
        $message = $isAr ? 'انتهت صلاحية الجلسة.' : 'Session expired.';
        $messageType = 'error';
    } else {
        $newName = trim($_POST['name'] ?? '');
        $newPhone = trim($_POST['phone'] ?? '');
        if ($newName !== '' && $pdo) {
            try {
                $pdo->prepare('UPDATE clients SET name = ?, phone = ? WHERE id = ?')->execute([$newName, $newPhone, $clientId]);
                $_SESSION['client_name'] = $newName;
                $clientName = $newName;
                $message = $isAr ? 'تم تحديث الملف الشخصي بنجاح.' : 'Profile updated successfully.';
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = $isAr ? 'فشل تحديث الملف الشخصي.' : 'Profile update failed.';
                $messageType = 'error';
            }
        }
        $section = 'profile';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
        $message = $isAr ? 'انتهت صلاحية الجلسة.' : 'Session expired.';
        $messageType = 'error';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($pdo && $clientData && password_verify($currentPassword, $clientData['password_hash'])) {
            if (mb_strlen($newPassword) < 8) {
                $message = $isAr ? 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.' : 'New password must be at least 8 characters.';
                $messageType = 'error';
            } elseif (!preg_match('/[A-Z]/', $newPassword)) {
                $message = $isAr ? 'كلمة المرور يجب أن تحتوي على حرف كبير.' : 'Password must contain an uppercase letter.';
                $messageType = 'error';
            } elseif (!preg_match('/[0-9]/', $newPassword)) {
                $message = $isAr ? 'كلمة المرور يجب أن تحتوي على رقم.' : 'Password must contain a number.';
                $messageType = 'error';
            } elseif ($newPassword !== $confirmPassword) {
                $message = $isAr ? 'كلمتا المرور غير متطابقتين.' : 'Passwords do not match.';
                $messageType = 'error';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE clients SET password_hash = ? WHERE id = ?')->execute([$hash, $clientId]);
                $message = $isAr ? 'تم تغيير كلمة المرور بنجاح.' : 'Password changed successfully.';
                $messageType = 'success';
            }
        } else {
            $message = $isAr ? 'كلمة المرور الحالية غير صحيحة.' : 'Current password is incorrect.';
            $messageType = 'error';
        }
        $section = 'password';
    }
}

// Load orders
$orders = [];
if ($pdo) {
    try {
        $st = $pdo->prepare('SELECT * FROM orders WHERE customer_email = ? ORDER BY created_at DESC');
        $st->execute([$clientEmail]);
        $orders = $st->fetchAll();
    } catch (Throwable $e) {}
}

// Load order detail
$orderDetail = null;
$orderItems = [];
if ($orderId > 0 && $pdo) {
    try {
        $st = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND customer_email = ?');
        $st->execute([$orderId, $clientEmail]);
        $orderDetail = $st->fetch();
        if ($orderDetail) {
            $st2 = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $st2->execute([$orderId]);
            $orderItems = $st2->fetchAll();
        }
    } catch (Throwable $e) {}
}

function status_badge_class(string $status): string {
    return match ($status) {
        'pending' => 'status-badge--pending',
        'confirmed' => 'status-badge--confirmed',
        'shipped' => 'status-badge--shipped',
        'delivered' => 'status-badge--delivered',
        'cancelled' => 'status-badge--cancelled',
        default => 'status-badge--pending',
    };
}

function format_status_label(string $status): string {
    $isAr = current_lang() === 'ar';
    return match ($status) {
        'pending' => $isAr ? 'قيد الانتظار' : 'Pending',
        'confirmed' => $isAr ? 'مؤكد' : 'Confirmed',
        'shipped' => $isAr ? 'تم الشحن' : 'Shipped',
        'delivered' => $isAr ? 'تم التوصيل' : 'Delivered',
        'cancelled' => $isAr ? 'ملغي' : 'Cancelled',
        default => ucfirst($status),
    };
}

$pageTitle = t('client_dashboard');
$extraCss = [
    url('assets/css/pages/account.css?v=' . filemtime(__DIR__ . '/../assets/css/pages/account.css'))
];
require dirname(__DIR__) . '/includes/header.php';
?>



<div class="account-page">
    <div class="account-layout">
        <aside class="account-sidebar">
            <div class="account-sidebar__user">
                <div class="account-sidebar__avatar">
                    <?= esc(mb_substr($clientName, 0, 1)) ?>
                </div>
                <div class="account-sidebar__name"><?= esc($clientName) ?></div>
                <div class="account-sidebar__email"><?= esc($clientEmail) ?></div>
            </div>
            <nav class="account-sidebar__nav">
                <a href="?section=orders" class="<?= $section === 'orders' && $orderId === 0 ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                    <?= esc($isAr ? 'طلباتي' : 'My Orders') ?>
                </a>
                <a href="?section=profile" class="<?= $section === 'profile' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?= esc($isAr ? 'الملف الشخصي' : 'Profile') ?>
                </a>
                <a href="?section=password" class="<?= $section === 'password' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <?= esc($isAr ? 'تغيير كلمة المرور' : 'Change Password') ?>
                </a>
                <a href="logout.php" class="logout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <?= esc($isAr ? 'تسجيل الخروج' : 'Logout') ?>
                </a>
            </nav>
        </aside>

        <main class="account-content">
            <?php if ($message): ?>
                <div style="padding:1rem;border-radius:8px;margin-bottom:1.5rem;font-weight:600;<?= $messageType === 'success' ? 'background:#d4edda;color:#155724;border:1px solid #c3e6cb;' : 'background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;' ?>">
                    <?= esc($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($section === 'orders' && $orderId === 0): ?>
                <h2 class="account-content__title"><?= esc($isAr ? 'طلباتي' : 'My Orders') ?></h2>

                <?php if ($orders === []): ?>
                    <div style="text-align:center;padding:3rem 1rem;color:var(--ink-muted);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:1rem;opacity:0.4;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        <p style="margin-bottom:1.5rem;"><?= esc($isAr ? 'لم تقم بإجراء أي طلبات بعد.' : 'You haven\'t placed any orders yet.') ?></p>
                        <a href="<?= esc(url('products.php')) ?>" style="display:inline-block;padding:0.75rem 1.5rem;background:var(--gold);color:#000;border-radius:8px;text-decoration:none;font-weight:700;">
                            <?= esc($isAr ? 'تصفح المنتجات' : 'Browse Products') ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th><?= esc($isAr ? 'رقم الطلب' : 'Order #') ?></th>
                                    <th><?= esc($isAr ? 'التاريخ' : 'Date') ?></th>
                                    <th><?= esc($isAr ? 'المدينة' : 'City') ?></th>
                                    <th><?= esc($isAr ? 'الإجمالي' : 'Total') ?></th>
                                    <th><?= esc($isAr ? 'الحالة' : 'Status') ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td data-label="<?= esc($isAr ? 'رقم الطلب' : 'Order #') ?>">#<?= esc($o['order_number']) ?></td>
                                    <td data-label="<?= esc($isAr ? 'التاريخ' : 'Date') ?>"><?= date('Y-m-d', strtotime($o['created_at'])) ?></td>
                                    <td data-label="<?= esc($isAr ? 'المدينة' : 'City') ?>"><?= esc($o['city']) ?></td>
                                    <td data-label="<?= esc($isAr ? 'الإجمالي' : 'Total') ?>"><?= number_format((float)$o['total'], 2) ?> <?= esc(t('currency')) ?></td>
                                    <td data-label="<?= esc($isAr ? 'الحالة' : 'Status') ?>">
                                        <span class="status-badge <?= status_badge_class($o['status']) ?>">
                                            <?= esc(format_status_label($o['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?section=orders&order_id=<?= (int)$o['id'] ?>" style="color:var(--gold);font-weight:600;text-decoration:none;font-size:0.85rem;">
                                            <?= esc($isAr ? 'تفاصيل' : 'Details') ?> →
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php elseif ($section === 'orders' && $orderId > 0 && $orderDetail): ?>
                <h2 class="account-content__title"><?= esc($isAr ? 'تفاصيل الطلب' : 'Order Details') ?></h2>

                <div class="order-detail__header">
                    <div>
                        <strong style="font-size:1.1rem;">#<?= esc($orderDetail['order_number']) ?></strong>
                        <span style="margin-<?= $isAr ? 'right' : 'left' ?>:1rem;color:var(--ink-muted);font-size:0.9rem;">
                            <?= date('Y-m-d H:i', strtotime($orderDetail['created_at'])) ?>
                        </span>
                    </div>
                    <span class="status-badge <?= status_badge_class($orderDetail['status']) ?>">
                        <?= esc(format_status_label($orderDetail['status'])) ?>
                    </span>
                </div>

                <div class="order-detail__items">
                    <?php foreach ($orderItems as $item): ?>
                    <div class="order-detail__item">
                        <div class="order-detail__item-info">
                            <div class="order-detail__item-name"><?= esc($item['product_name_snapshot']) ?></div>
                            <?php if ($item['variant_label_snapshot']): ?>
                            <div class="order-detail__item-variant"><?= esc($item['variant_label_snapshot']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="text-align:right;font-size:0.9rem;color:var(--ink-muted);">
                            <?= esc($isAr ? 'الكمية' : 'Qty') ?>: <?= (int)$item['qty'] ?>
                        </div>
                        <div class="order-detail__item-price">
                            <?= number_format((float)$item['line_total'], 2) ?> <?= esc(t('currency')) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-detail__summary">
                    <div class="order-detail__summary-row">
                        <span><?= esc($isAr ? 'المجموع الفرعي' : 'Subtotal') ?></span>
                        <span><?= number_format((float)$orderDetail['subtotal'], 2) ?> <?= esc(t('currency')) ?></span>
                    </div>
                    <?php if ((float)$orderDetail['discount_amount'] > 0): ?>
                    <div class="order-detail__summary-row" style="color:#10b981;">
                        <span><?= esc($isAr ? 'الخصم' : 'Discount') ?></span>
                        <span>-<?= number_format((float)$orderDetail['discount_amount'], 2) ?> <?= esc(t('currency')) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="order-detail__summary-row">
                        <span><?= esc($isAr ? 'الشحن' : 'Shipping') ?></span>
                        <span><?= number_format((float)$orderDetail['shipping_cost'], 2) ?> <?= esc(t('currency')) ?></span>
                    </div>
                    <div class="order-detail__summary-row total">
                        <span><?= esc($isAr ? 'الإجمالي' : 'Total') ?></span>
                        <span><?= number_format((float)$orderDetail['total'], 2) ?> <?= esc(t('currency')) ?></span>
                    </div>
                </div>

                <div style="margin-top:2rem;background:var(--bg-card-hover);padding:1.25rem;border-radius:8px;font-size:0.9rem;">
                    <div style="margin-bottom:0.5rem;"><strong><?= esc($isAr ? 'العنوان:' : 'Address:') ?></strong> <?= esc($orderDetail['shipping_address']) ?></div>
                    <div><strong><?= esc($isAr ? 'المدينة:' : 'City:') ?></strong> <?= esc($orderDetail['city']) ?></div>
                    <?php if ($orderDetail['address_landmark']): ?>
                    <div style="margin-top:0.5rem;"><strong><?= esc($isAr ? 'علامة مميزة:' : 'Landmark:') ?></strong> <?= esc($orderDetail['address_landmark']) ?></div>
                    <?php endif; ?>
                    <?php if ($orderDetail['admin_notes']): ?>
                    <div style="margin-top:0.5rem;"><strong><?= esc($isAr ? 'ملاحظات:' : 'Notes:') ?></strong> <?= esc($orderDetail['admin_notes']) ?></div>
                    <?php endif; ?>
                </div>

                <div style="margin-top:1.5rem;">
                    <a href="?section=orders" style="color:var(--gold);text-decoration:none;font-weight:600;">
                        ← <?= esc($isAr ? 'العودة للطلبات' : 'Back to Orders') ?>
                    </a>
                </div>

            <?php elseif ($section === 'profile'): ?>
                <h2 class="account-content__title"><?= esc($isAr ? 'الملف الشخصي' : 'Profile') ?></h2>

                <form method="POST" class="account-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="account-form__group">
                        <label class="account-form__label" for="profile-name"><?= esc($isAr ? 'الاسم' : 'Name') ?></label>
                        <input type="text" id="profile-name" name="name" class="account-form__input"
                               value="<?= esc($clientName) ?>" required>
                    </div>

                    <div class="account-form__group">
                        <label class="account-form__label" for="profile-email"><?= esc(t('label_email')) ?></label>
                        <input type="email" id="profile-email" class="account-form__input"
                               value="<?= esc($clientEmail) ?>" disabled style="background:var(--bg-card-hover);">
                    </div>

                    <div class="account-form__group">
                        <label class="account-form__label" for="profile-phone"><?= esc($isAr ? 'رقم الهاتف' : 'Phone') ?></label>
                        <input type="text" id="profile-phone" name="phone" class="account-form__input"
                               value="<?= esc($clientData['phone'] ?? '') ?>" placeholder="05xxxxxxxx">
                    </div>

                    <button type="submit" class="account-form__submit">
                        <?= esc($isAr ? 'حفظ التغييرات' : 'Save Changes') ?>
                    </button>
                </form>

            <?php elseif ($section === 'password'): ?>
                <h2 class="account-content__title"><?= esc($isAr ? 'تغيير كلمة المرور' : 'Change Password') ?></h2>

                <form method="POST" class="account-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="account-form__group">
                        <label class="account-form__label" for="current-password">
                            <?= esc($isAr ? 'كلمة المرور الحالية' : 'Current Password') ?>
                        </label>
                        <input type="password" id="current-password" name="current_password" class="account-form__input" required>
                    </div>

                    <div class="account-form__group">
                        <label class="account-form__label" for="new-password">
                            <?= esc($isAr ? 'كلمة المرور الجديدة' : 'New Password') ?>
                        </label>
                        <input type="password" id="new-password" name="new_password" class="account-form__input"
                               required minlength="8" autocomplete="new-password">
                    </div>

                    <div class="account-form__group">
                        <label class="account-form__label" for="confirm-password">
                            <?= esc($isAr ? 'تأكيد كلمة المرور' : 'Confirm Password') ?>
                        </label>
                        <input type="password" id="confirm-password" name="confirm_password" class="account-form__input" required>
                    </div>

                    <button type="submit" class="account-form__submit">
                        <?= esc($isAr ? 'تغيير كلمة المرور' : 'Change Password') ?>
                    </button>
                </form>

            <?php endif; ?>
        </main>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>