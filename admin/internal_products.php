<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

// Handle internal product operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    admin_verify_csrf();
    $pdo = medal_pdo();
    
    switch ($_POST['action']) {
        case 'add_internal_product':
            $nameEn = trim($_POST['name_en']);
            $nameAr = trim($_POST['name_ar']);
            $description = trim($_POST['description']);
            $cost = (float) $_POST['cost'];
            $type = $_POST['type']; // gift, sample, promotional
            
            $insert = $pdo->prepare("
                INSERT INTO internal_products (name_en, name_ar, description, cost, type, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insert->execute([$nameEn, $nameAr, $description, $cost, $type]);
            
            $_SESSION['success'] = 'تم إضافة المنتج الداخلي بنجاح!';
            header('Location: internal_products.php');
            exit;
            
        case 'update_internal_product':
            $productId = (int) $_POST['product_id'];
            $nameEn = trim($_POST['name_en']);
            $nameAr = trim($_POST['name_ar']);
            $description = trim($_POST['description']);
            $cost = (float) $_POST['cost'];
            $type = $_POST['type'];
            
            $update = $pdo->prepare("
                UPDATE internal_products 
                SET name_en = ?, name_ar = ?, description = ?, cost = ?, type = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([$nameEn, $nameAr, $description, $cost, $type, $productId]);
            
            $_SESSION['success'] = 'تم تحديث المنتج الداخلي بنجاح!';
            header('Location: internal_products.php');
            exit;
            
        case 'delete_internal_product':
            $productId = (int) $_POST['product_id'];
            
            $delete = $pdo->prepare("DELETE FROM internal_products WHERE id = ?");
            $delete->execute([$productId]);
            
            $_SESSION['success'] = 'تم حذف المنتج الداخلي بنجاح!';
            header('Location: internal_products.php');
            exit;
    }
}

// Get internal products
$pdo = medal_pdo();
$internalProducts = [];

if ($pdo) {
    $query = "
        SELECT 
            ip.*,
            (SELECT COUNT(*) FROM order_internal_products oip WHERE oip.internal_product_id = ip.id) as usage_count
        FROM internal_products ip
        ORDER BY ip.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $internalProducts = $stmt->fetchAll();
    
    // Get editing product if specified
    $editingProduct = null;
    if (isset($_GET['edit'])) {
        $editId = (int) $_GET['edit'];
        $editQuery = "SELECT * FROM internal_products WHERE id = ?";
        $editStmt = $pdo->prepare($editQuery);
        $editStmt->execute([$editId]);
        $editingProduct = $editStmt->fetch();
    }
}

$pageTitle = 'المنتجات الداخلية والهدايا';
require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <h1>المنتجات الداخلية والهدايا</h1>
    <?php if (!$editingProduct && !isset($_GET['add'])): ?>
        <a href="?add=1" class="admin-btn admin-btn--primary">إضافة منتج جديد</a>
    <?php endif; ?>
</div>

<p class="admin-lead">إدارة الهدايا والعينات والمنتجات التي لا تظهر للعامة.</p>

<?php if (isset($_SESSION['success'])): ?>
    <div class="admin-notice" style="background:rgba(16,185,129,.15); color:#059669; padding:1rem; border-radius:8px; margin-bottom:1.5rem;">
        <?= esc($_SESSION['success']) ?>
        <?php unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if ($editingProduct): ?>
    <div class="admin-card">
        <h2>تعديل منتج: <?= esc($editingProduct['name_ar']) ?></h2>
        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            <input type="hidden" name="action" value="update_internal_product">
            <input type="hidden" name="product_id" value="<?= (int)$editingProduct['id'] ?>">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                <div>
                    <label for="name_ar">الاسم (عربي)</label>
                    <input type="text" id="name_ar" name="name_ar" value="<?= esc($editingProduct['name_ar']) ?>" required>
                </div>
                <div>
                    <label for="name_en">الاسم (English)</label>
                    <input type="text" id="name_en" name="name_en" value="<?= esc($editingProduct['name_en']) ?>" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-top:1rem;">
                <div>
                    <label for="type">النوع</label>
                    <select id="type" name="type" required>
                        <option value="gift" <?= $editingProduct['type'] === 'gift' ? 'selected' : '' ?>>هدية (Gift)</option>
                        <option value="sample" <?= $editingProduct['type'] === 'sample' ? 'selected' : '' ?>>عينة (Sample)</option>
                        <option value="promotional" <?= $editingProduct['type'] === 'promotional' ? 'selected' : '' ?>>منتج ترويجي</option>
                    </select>
                </div>
                <div>
                    <label for="cost">التكلفة</label>
                    <input type="number" id="cost" name="cost" step="0.01" min="0" value="<?= (float)$editingProduct['cost'] ?>" required>
                </div>
            </div>

            <div style="margin-top:1rem;">
                <label for="description">الوصف</label>
                <textarea id="description" name="description" rows="3"><?= esc($editingProduct['description']) ?></textarea>
            </div>

            <div style="margin-top:1.5rem;">
                <button type="submit" class="admin-btn admin-btn--primary">حفظ التغييرات</button>
                <a href="internal_products.php" class="admin-btn admin-btn--secondary">إلغاء</a>
            </div>
        </form>
    </div>
<?php elseif (isset($_GET['add'])): ?>
    <div class="admin-card">
        <h2>إضافة منتج داخلي جديد</h2>
        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            <input type="hidden" name="action" value="add_internal_product">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                <div>
                    <label for="name_ar">الاسم (عربي)</label>
                    <input type="text" id="name_ar" name="name_ar" required>
                </div>
                <div>
                    <label for="name_en">الاسم (English)</label>
                    <input type="text" id="name_en" name="name_en" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-top:1rem;">
                <div>
                    <label for="type">النوع</label>
                    <select id="type" name="type" required>
                        <option value="gift">هدية (Gift)</option>
                        <option value="sample">عينة (Sample)</option>
                        <option value="promotional">منتج ترويجي</option>
                    </select>
                </div>
                <div>
                    <label for="cost">التكلفة</label>
                    <input type="number" id="cost" name="cost" step="0.01" min="0" value="0.00" required>
                </div>
            </div>

            <div style="margin-top:1rem;">
                <label for="description">الوصف</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>

            <div style="margin-top:1.5rem;">
                <button type="submit" class="admin-btn admin-btn--primary">إضافة المنتج</button>
                <a href="internal_products.php" class="admin-btn admin-btn--secondary">إلغاء</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="admin-stats" style="margin-bottom:2rem;">
        <div class="admin-stat"><strong><?= count($internalProducts) ?></strong><span>إجمالي المنتجات</span></div>
        <div class="admin-stat"><strong><?= count(array_filter($internalProducts, fn($p) => $p['type'] === 'gift')) ?></strong><span>هدايا</span></div>
        <div class="admin-stat"><strong><?= array_sum(array_column($internalProducts, 'usage_count')) ?></strong><span>مرات الاستخدام</span></div>
    </div>

    <?php if (empty($internalProducts)): ?>
        <p class="admin-muted">لا يوجد منتجات داخلية مضافة حالياً.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>النوع</th>
                        <th>التكلفة</th>
                        <th>الاستخدام</th>
                        <th>تاريخ الإضافة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($internalProducts as $p): ?>
                        <tr>
                            <td>
                                <strong><?= esc($p['name_ar']) ?></strong><br>
                                <small class="admin-muted"><?= esc($p['name_en']) ?></small>
                            </td>
                            <td>
                                <span class="admin-badge"><?= esc($p['type']) ?></span>
                            </td>
                            <td><?= number_format((float)$p['cost'], 2) ?> <?= esc(t('currency')) ?></td>
                            <td><?= (int)$p['usage_count'] ?></td>
                            <td><?= date('Y-m-d', strtotime($p['created_at'])) ?></td>
                            <td>
                                <div class="admin-actions">
                                    <a href="?edit=<?= (int)$p['id'] ?>" class="admin-btn admin-btn--sm">تعديل</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete_internal_product">
                                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                        <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
