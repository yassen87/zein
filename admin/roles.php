<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

// Auto-create roles table if it doesn't exist
$pdo = medal_pdo();
if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description VARCHAR(255) DEFAULT '',
            is_superadmin TINYINT(1) NOT NULL DEFAULT 0,
            permissions TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        
        // Seed default roles if empty
        $count = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
        if ($count == 0) {
            $roles = [
                ['مدير عام', 'صلاحيات كاملة - جميع الصفحات', 1, ''],
                ['مدير مبيعات', 'إدارة الطلبات والعملاء والتقارير', 0, 'orders,order_management,orders_export,clients,students,client_statement,clients_export,reports,sales_records,product_statistics,notifications'],
                ['مدير محتوى', 'إدارة المنتجات والعروض والماركات', 0, 'products,offers,brands,internal_products,reviews,categories,promo_codes'],
                ['خدمة عملاء', 'الرد على الرسائل ومتابعة الطلبات', 0, 'orders,order_management,messages,notifications,clients'],
                ['موظف شحن', 'إدارة الشحن والطلبات', 0, 'orders,shipping,notifications'],
            ];
            $st = $pdo->prepare("INSERT INTO roles (name, description, is_superadmin, permissions) VALUES (?, ?, ?, ?)");
            foreach ($roles as $r) {
                $st->execute($r);
            }
        }
    } catch (Throwable $e) {
        error_log('roles.php migration: ' . $e->getMessage());
    }
}

$pdo = medal_pdo();
$error = '';
$success = '';

if (isset($_POST['delete'])) {
    admin_verify_csrf();
    $id = (int)$_POST['delete'];
    $st = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE role_id = ?");
    $st->execute([$id]);
    $used = $st->fetchColumn();
    if ($used > 0) {
        $error = "لا يمكن حذف الدور لأنه مستخدم من قبل موظفين.";
    } else {
        $st = $pdo->prepare("DELETE FROM roles WHERE id = ?");
        $st->execute([$id]);
        $success = "تم حذف الدور بنجاح.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    admin_verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_superadmin = isset($_POST['is_superadmin']) ? 1 : 0;
    $permissions = $is_superadmin ? '' : (isset($_POST['perms']) ? implode(',', $_POST['perms']) : '');

    if ($name === '') {
        $error = "اسم الدور مطلوب.";
    } else {
        if ($id > 0) {
            $st = $pdo->prepare("UPDATE roles SET name = ?, description = ?, is_superadmin = ?, permissions = ? WHERE id = ?");
            try {
                $st->execute([$name, $description, $is_superadmin, $permissions, $id]);
                $success = "تم تحديث الدور بنجاح.";
            } catch (PDOException $e) {
                $error = "اسم الدور موجود مسبقاً.";
            }
        } else {
            $st = $pdo->prepare("INSERT INTO roles (name, description, is_superadmin, permissions) VALUES (?, ?, ?, ?)");
            try {
                $st->execute([$name, $description, $is_superadmin, $permissions]);
                $success = "تم إنشاء الدور بنجاح.";
            } catch (PDOException $e) {
                $error = "اسم الدور موجود مسبقاً.";
            }
        }
    }
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

$pageTitle = 'الأدوار والصلاحيات';

$available = [
    '📦 الطلبات' => [
        'orders' => 'كل الطلبات',
        'order_management' => 'إضافة طلب جديد',
        'orders_export' => 'تصدير الطلبات',
    ],
    '🛍️ المنتجات' => [
        'products' => 'العطور العادية',
        'offers' => 'العروض والتخفيضات',
        'brands' => 'الماركات العالمية',
        'internal_products' => 'الهدايا والمنتجات الداخلية',
        'reviews' => 'تقييمات المنتجات',
    ],
    '📂 التصنيفات' => [
        'categories' => 'إدارة التصنيفات',
    ],
    '🏷️ أكواد الخصم' => [
        'promo_codes' => 'أكواد الخصم',
    ],
    '👥 العملاء' => [
        'clients' => 'العملاء',
        'students' => 'الطلاب',
        'customers' => 'العملاء (القديم)',
        'client_statement' => 'كشف حساب العميل',
        'clients_export' => 'تصدير العملاء',
    ],
    '💬 الرسائل' => [
        'messages' => 'الرسائل',
    ],
    '🔔 الإشعارات' => [
        'notifications' => 'الإشعارات',
    ],
    '⚙️ الإعدادات' => [
        'settings' => 'إعدادات الموقع',
        'admins' => 'الموظفين',
        'shipping' => 'الشحن والتوصيل',
        'faqs' => 'الأسئلة الشائعة',
        'about_settings' => 'صفحة من نحن',
        'policy_settings' => 'سياسة الإرجاع',
    ],
    '📊 التقارير' => [
        'reports' => 'التقارير',
        'sales_records' => 'سجلات المبيعات',
        'product_statistics' => 'إحصائيات المنتجات',
    ],
];
include __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1><?= esc($pageTitle) ?></h1>
        <p class="admin-lead">إدارة الأدوار والصلاحيات للموظفين</p>
    </div>
    <button type="button" class="admin-btn admin-btn--primary" onclick="openRoleModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end:8px;"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
        إضافة دور جديد
    </button>
</div>

<?php if ($error): ?>
    <div class="admin-error" style="margin-bottom:1rem;"><?= esc($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="admin-notice" style="margin-bottom:1rem; background:#d4edda; border-color:#c3e6cb; color:#155724;">✅ <?= esc($success) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>اسم الدور</th>
                    <th>الوصف</th>
                    <th>النوع</th>
                    <th>عدد الصلاحيات</th>
                    <th style="text-align: end;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $r): ?>
                <tr>
                    <td>#<?= (int)$r['id'] ?></td>
                    <td><strong><?= esc($r['name']) ?></strong></td>
                    <td><?= esc($r['description']) ?></td>
                    <td>
                        <span class="admin-badge <?= $r['is_superadmin'] ? 'admin-badge--success' : 'admin-badge--warning' ?>">
                            <?= $r['is_superadmin'] ? 'مدير عام' : 'موظف' ?>
                        </span>
                    </td>
                    <td>
                        <?= $r['is_superadmin'] ? 'كل الصلاحيات' : ($r['permissions'] ? count(explode(',', $r['permissions'])) : 0) ?>
                    </td>
                    <td style="text-align: end;">
                        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm"
                                onclick='editRole(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>)'>
                            تعديل
                        </button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟')">
                            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                            <input type="hidden" name="delete" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">حذف</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="roleModal" class="admin-modal-overlay" style="display:none;">
    <div class="admin-modal" style="max-width:520px; width:95%;">
        <div class="admin-modal__header">
            <h2 id="modalTitle" style="margin:0; font-size:1.2rem;">إضافة دور جديد</h2>
            <button type="button" class="admin-modal__close" onclick="closeRoleModal()" aria-label="إغلاق">&times;</button>
        </div>
        <div class="admin-modal__body">
            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                <input type="hidden" name="id" id="form-id" value="0">

                <div class="admin-form__row" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="admin-form-group">
                        <label class="admin-form__label">اسم الدور <span style="color:#e53e3e;">*</span></label>
                        <input type="text" name="name" id="form-name" class="admin-input" required placeholder="مثال: مدير مبيعات">
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form__label">نوع الدور</label>
                        <select id="form-role-type" class="admin-input" onchange="toggleSuperadmin()" style="height:42px;">
                            <option value="admin">موظف (صلاحيات محددة)</option>
                            <option value="superadmin">مدير عام (كل الصلاحيات)</option>
                        </select>
                        <input type="hidden" name="is_superadmin" id="form-is-superadmin" value="0">
                    </div>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form__label">الوصف</label>
                    <input type="text" name="description" id="form-description" class="admin-input" placeholder="وصف مختصر لمهام هذا الدور">
                </div>

                <div id="perms-section" class="admin-form-group">
                    <label class="admin-form__label" style="margin-bottom:0.75rem;">الصلاحيات</label>
                    <div style="border:1px solid var(--admin-border, #e5e5e5); border-radius:10px; max-height:320px; overflow-y:auto;">
                        <?php foreach ($available as $groupName => $perms): ?>
                        <div style="border-bottom:1px solid #f0f0f0; padding:0;">
                            <label style="display:flex; align-items:center; gap:8px; padding:0.6rem 1rem; cursor:pointer; font-weight:600; font-size:0.85rem; background:#fafafa;"
                                   onclick="togglePermGroup(this)">
                                <input type="checkbox" class="perm-group-check" style="accent-color:#d4af37; width:15px; height:15px;"
                                       onchange="toggleGroupPerms(this)" onclick="event.stopPropagation()">
                                <?= $groupName ?>
                            </label>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2px; padding:0.4rem 1rem 0.4rem 2rem; background:#fff;">
                                <?php foreach ($perms as $key => $label): ?>
                                <label style="display:flex; align-items:center; gap:6px; font-size:0.8rem; cursor:pointer; padding:3px 4px; border-radius:4px;"
                                       onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='transparent'">
                                    <input type="checkbox" name="perms[]" value="<?= $key ?>" class="perm-check"
                                           style="accent-color:#d4af37; width:14px; height:14px; flex-shrink:0;"
                                           onchange="updateGroupCheck(this)">
                                    <?= $label ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display:flex; gap:0.75rem; justify-content:flex-end; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #eee;">
                    <button type="button" class="admin-btn admin-btn--secondary" onclick="closeRoleModal()">إلغاء</button>
                    <button type="submit" class="admin-btn admin-btn--primary">حفظ الدور</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSuperadmin() {
    var select = document.getElementById('form-role-type');
    var isSuper = select.value === 'superadmin';
    document.getElementById('form-is-superadmin').value = isSuper ? '1' : '0';
    var section = document.getElementById('perms-section');
    if (isSuper) {
        section.style.display = 'none';
        document.querySelectorAll('.perm-check').forEach(function(c) { c.checked = true; });
    } else {
        section.style.display = 'block';
    }
}

function openRoleModal() {
    document.getElementById('form-id').value = '0';
    document.getElementById('form-name').value = '';
    document.getElementById('form-description').value = '';
    document.getElementById('form-role-type').value = 'admin';
    document.getElementById('form-is-superadmin').value = '0';
    document.getElementById('modalTitle').innerText = 'إضافة دور جديد';
    document.querySelectorAll('.perm-check').forEach(function(c) { c.checked = false; });
    document.querySelectorAll('.perm-group-check').forEach(function(c) { c.checked = false; c.indeterminate = false; });
    document.getElementById('perms-section').style.display = 'block';
    document.getElementById('roleModal').style.display = 'flex';
}

function closeRoleModal() {
    document.getElementById('roleModal').style.display = 'none';
}

function editRole(role) {
    document.getElementById('form-id').value = role.id;
    document.getElementById('form-name').value = role.name;
    document.getElementById('form-description').value = role.description;
    var isSuper = role.is_superadmin == '1';
    document.getElementById('form-role-type').value = isSuper ? 'superadmin' : 'admin';
    document.getElementById('form-is-superadmin').value = isSuper ? '1' : '0';
    document.getElementById('modalTitle').innerText = 'تعديل الدور';

    var perms = role.permissions ? role.permissions.split(',') : [];
    document.querySelectorAll('.perm-check').forEach(function(c) {
        c.checked = isSuper || perms.indexOf(c.value) !== -1;
    });
    updateAllGroupChecks();

    document.getElementById('perms-section').style.display = isSuper ? 'none' : 'block';
    document.getElementById('roleModal').style.display = 'flex';
}

function updateAllGroupChecks() {
    document.querySelectorAll('.perm-group-check').forEach(function(gc) {
        var container = gc.closest('label').nextElementSibling;
        if (container) {
            var allPerms = container.querySelectorAll('.perm-check');
            var allChecked = Array.from(allPerms).every(function(c) { return c.checked; });
            var noneChecked = Array.from(allPerms).every(function(c) { return !c.checked; });
            gc.checked = allChecked;
            gc.indeterminate = !allChecked && !noneChecked;
        }
    });
}

function toggleGroupPerms(groupCheck) {
    var checked = groupCheck.checked;
    var container = groupCheck.closest('label').nextElementSibling;
    if (container) {
        container.querySelectorAll('.perm-check').forEach(function(c) { c.checked = checked; });
    }
}

function updateGroupCheck(permCheck) {
    var container = permCheck.closest('div').parentElement;
    var groupCheck = container.previousElementSibling.querySelector('.perm-group-check');
    if (groupCheck) {
        var allPerms = container.querySelectorAll('.perm-check');
        var allChecked = Array.from(allPerms).every(function(c) { return c.checked; });
        var noneChecked = Array.from(allPerms).every(function(c) { return !c.checked; });
        groupCheck.checked = allChecked;
        groupCheck.indeterminate = !allChecked && !noneChecked;
    }
}

function togglePermGroup(label) {
}
</script>

<?php include __DIR__ . '/_layout_end.php';