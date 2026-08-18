<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_bootstrap.php';
require_admin('settings');

// Auto-create roles table and add role_id column
$pdo = medal_pdo();
if ($pdo) {
    try {
        // Create roles table
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
                ['مدير مبيعات', 'إدارة الطلبات والعملاء والتقارير', 0, 'orders,order_management,orders_export,clients,client_statement,clients_export,reports,sales_records,product_statistics,notifications'],
                ['مدير محتوى', 'إدارة المنتجات والعروض والماركات', 0, 'products,offers,brands,internal_products,reviews,categories,promo_codes'],
                ['خدمة عملاء', 'الرد على الرسائل ومتابعة الطلبات', 0, 'orders,order_management,messages,notifications,clients'],
                ['موظف شحن', 'إدارة الشحن والطلبات', 0, 'orders,shipping,notifications'],
            ];
            $st = $pdo->prepare("INSERT INTO roles (name, description, is_superadmin, permissions) VALUES (?, ?, ?, ?)");
            foreach ($roles as $r) {
                $st->execute($r);
            }
        }
        
        // Add role_id column to admin_users
        if (!isset($_SESSION['_migrated_admin_role_id'])) {
            try { $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS role_id INT UNSIGNED NULL"); }
            catch (Throwable $e) {}
            $_SESSION['_migrated_admin_role_id'] = true;
        }
    } catch (Throwable $e) {
        error_log('admins.php migration: ' . $e->getMessage());
    }
}

$pdo = medal_pdo();
$error = '';
$success = '';

if (isset($_POST['delete'])) {
    admin_verify_csrf();
    $id = (int)$_POST['delete'];
    if ($id === (int)$_SESSION[ADMIN_SESSION_KEY]) {
        $error = "لا يمكنك حذف حسابك الخاص!";
    } else {
        $st = $pdo->prepare("DELETE FROM admin_users WHERE id = ? AND role != 'superadmin'");
        $st->execute([$id]);
        if ($st->rowCount() > 0) {
            $success = "تم حذف الموظف بنجاح.";
        } else {
            $error = "لا يمكن حذف هذا الحساب.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    admin_verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = (int)($_POST['role_id'] ?? 0);

    if ($username === '') {
        $error = "اسم المستخدم مطلوب.";
    } elseif ($role_id === 0) {
        $error = "يرجى اختيار الدور.";
    } else {
        $st = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
        $st->execute([$role_id]);
        $role = $st->fetch();

        if (!$role) {
            $error = "الدور غير موجود.";
        } else {
            $role_type = $role['is_superadmin'] ? 'superadmin' : 'admin';
            $permissions = $role['permissions'];

            if ($id > 0) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $st = $pdo->prepare("UPDATE admin_users SET username = ?, password_hash = ?, role = ?, permissions = ?, role_id = ? WHERE id = ?");
                    $st->execute([$username, $hash, $role_type, $permissions, $role_id, $id]);
                } else {
                    $st = $pdo->prepare("UPDATE admin_users SET username = ?, role = ?, permissions = ?, role_id = ? WHERE id = ?");
                    $st->execute([$username, $role_type, $permissions, $role_id, $id]);
                }
                $success = "تم تحديث بيانات الموظف بنجاح.";
            } else {
                if ($password === '') {
                    $error = "كلمة المرور مطلوبة للموظفين الجدد.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $st = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role, permissions, role_id) VALUES (?, ?, ?, ?, ?)");
                    try {
                        $st->execute([$username, $hash, $role_type, $permissions, $role_id]);
                        $success = "تم إنشاء الموظف بنجاح.";
                    } catch (PDOException $e) {
                        $error = "اسم المستخدم موجود مسبقاً.";
                    }
                }
            }
        }
    }
}

$admins = $pdo->query("
    SELECT a.*, COALESCE(r.name, CASE WHEN a.role = 'superadmin' THEN 'مدير عام' ELSE 'غير محدد' END) as role_name
    FROM admin_users a
    LEFT JOIN roles r ON a.role_id = r.id
    ORDER BY a.id ASC
")->fetchAll();

$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

$pageTitle = t('admin_nav_admins');
include __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1><?= esc($pageTitle) ?></h1>
        <p class="admin-lead">إدارة حسابات الموظفين والصلاحيات</p>
    </div>
    <button type="button" class="admin-btn admin-btn--primary" onclick="openAdminModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end:8px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"/></svg>
        إضافة موظف جديد
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
                    <th>اسم المستخدم</th>
                    <th>الدور</th>
                    <th>الصلاحيات</th>
                    <th>تاريخ الإنشاء</th>
                    <th style="text-align: end;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $a): ?>
                <tr>
                    <td>#<?= (int)$a['id'] ?></td>
                    <td><strong><?= esc($a['username']) ?></strong></td>
                    <td>
                        <span class="admin-badge <?= $a['role'] === 'superadmin' ? 'admin-badge--success' : 'admin-badge--warning' ?>">
                            <?= esc($a['role_name']) ?>
                        </span>
                    </td>
                    <td style="max-width: 200px; font-size: 0.85em; color: #666;">
                        <?= $a['role'] === 'superadmin' ? 'كل الصلاحيات' : esc($a['permissions']) ?>
                    </td>
                    <td><?= esc($a['created_at']) ?></td>
                    <td style="text-align: end;">
                        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm"
                                onclick='editAdmin(<?= json_encode($a, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>)'>
                            تعديل
                        </button>
                        <?php if ($a['role'] !== 'superadmin' && $a['id'] != $_SESSION[ADMIN_SESSION_KEY]): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الموظف؟')">
                                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                <input type="hidden" name="delete" value="<?= (int)$a['id'] ?>">
                                <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">حذف</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="adminModal" class="admin-modal-overlay" style="display:none;">
    <div class="admin-modal" style="max-width:500px; width:95%;">
        <div class="admin-modal__header">
            <h2 id="modalTitle" style="margin:0;">إضافة موظف جديد</h2>
            <button type="button" class="admin-modal__close" onclick="closeAdminModal()" aria-label="إغلاق">&times;</button>
        </div>
        <div class="admin-modal__body">
            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                <input type="hidden" name="id" id="form-id" value="0">

                <div class="admin-form-group">
                    <label class="admin-form__label">اسم المستخدم</label>
                    <input type="text" name="username" id="form-username" class="admin-input" required placeholder="أدخل اسم المستخدم">
                </div>

                <div class="admin-form-group">
                    <label class="admin-form__label">كلمة المرور <span style="color:#999; font-weight:400;">(اتركها فارغة للتعديل بدون تغيير)</span></label>
                    <input type="password" name="password" id="form-password" class="admin-input" placeholder="أدخل كلمة المرور">
                </div>

                <div class="admin-form-group">
                    <label class="admin-form__label">الدور الوظيفي</label>
                    <select name="role_id" id="form-role-id" class="admin-input" onchange="onRoleChange()">
                        <option value="">-- اختر الدور --</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?= (int)$role['id'] ?>"
                                data-is-superadmin="<?= (int)$role['is_superadmin'] ?>"
                                data-permissions="<?= esc($role['permissions']) ?>">
                            <?= esc($role['name']) ?> — <?= esc($role['description']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-modal__footer" style="margin-top:1.5rem;">
                    <button type="button" class="admin-btn admin-btn--secondary" onclick="closeAdminModal()">إلغاء</button>
                    <button type="submit" class="admin-btn admin-btn--primary">حفظ البيانات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var rolesData = <?= json_encode($roles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;

function onRoleChange() {
    var select = document.getElementById('form-role-id');
    var selectedOption = select.options[select.selectedIndex];
    if (!selectedOption || !selectedOption.value) return;
    var isSuper = selectedOption.getAttribute('data-is-superadmin');
    var perms = selectedOption.getAttribute('data-permissions');
}

function openAdminModal() {
    document.getElementById('form-id').value = '0';
    document.getElementById('form-username').value = '';
    document.getElementById('form-password').value = '';
    document.getElementById('form-role-id').value = '';
    document.getElementById('modalTitle').innerText = 'إضافة موظف جديد';
    document.getElementById('adminModal').style.display = 'flex';
}

function closeAdminModal() {
    document.getElementById('adminModal').style.display = 'none';
}

function editAdmin(admin) {
    document.getElementById('form-id').value = admin.id;
    document.getElementById('form-username').value = admin.username;
    document.getElementById('form-password').value = '';
    document.getElementById('form-role-id').value = admin.role_id || '';
    document.getElementById('modalTitle').innerText = 'تعديل بيانات الموظف';
    document.getElementById('adminModal').style.display = 'flex';
}
</script>

<?php include __DIR__ . '/_layout_end.php';