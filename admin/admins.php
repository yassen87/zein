<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_bootstrap.php';
require_admin('settings');

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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Seed default roles if empty
        $count = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
        if ($count == 0) {
            $roles = [
                ['مدير عام (Superadmin)', 'صلاحيات كاملة وغير محدودة لجميع الصفحات والإعدادات', 1, ''],
                ['مسؤول مبيعات وطلبات (Sales)', 'إدارة الطلبات والعملاء، إضافة طلب جديد، والتقارير المالية', 0, 'orders,order_management,orders_export,clients,client_statement,clients_export,reports,sales_records,product_statistics,notifications'],
                ['مسؤول شحن وتوصيل (Shipping)', 'متابعة بوالص الشحن، تحديث حالات الطلبات، وإدارة أسعار المحافظات', 0, 'orders,shipping,notifications'],
                ['مسؤول منتجات ومحتوى (Catalog)', 'إضافة وتعديل العطور والأسعار والعروض والماركات والتقييمات', 0, 'products,offers,brands,internal_products,reviews,categories,promo_codes'],
                ['خدمة عملاء (Support)', 'الرد على رسائل العملاء، متابعة الطلبات، وإشعارات الواتساب', 0, 'orders,order_management,messages,notifications,clients'],
            ];
            $st = $pdo->prepare("INSERT INTO roles (name, description, is_superadmin, permissions) VALUES (?, ?, ?, ?)");
            foreach ($roles as $r) {
                $st->execute($r);
            }
        }
        
        // Add role_id column to admin_users if missing
        medal_ensure_column($pdo, 'admin_users', 'role_id', 'INT UNSIGNED NULL');
        medal_ensure_column($pdo, 'admin_users', 'role', "VARCHAR(32) NOT NULL DEFAULT 'admin'");
        medal_ensure_column($pdo, 'admin_users', 'permissions', 'TEXT NULL');
    } catch (Throwable $e) {
        error_log('admins.php migration: ' . $e->getMessage());
    }
}

$error = '';
$success = '';

if (isset($_POST['delete'])) {
    admin_verify_csrf();
    $id = (int)$_POST['delete'];
    if ($id === (int)($_SESSION[ADMIN_SESSION_KEY] ?? 0)) {
        $error = "لا يمكنك حذف حسابك الخاص المسجل به الدخول حالياً!";
    } else {
        $st = $pdo->prepare("DELETE FROM admin_users WHERE id = ? AND role != 'superadmin'");
        $st->execute([$id]);
        if ($st->rowCount() > 0) {
            $success = "تم حذف حساب الموظف بنجاح.";
        } else {
            $error = "لا يمكن حذف هذا الحساب (الحساب محمي كمدير رئيسي).";
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
        $error = "يرجى كتابة اسم المستخدم.";
    } elseif ($role_id === 0) {
        $error = "يرجى اختيار الدور الوظيفي للموظف.";
    } else {
        $st = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
        $st->execute([$role_id]);
        $role = $st->fetch();

        if (!$role) {
            $error = "الدور الوظيفي المحدد غير موجود.";
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
                    $error = "كلمة المرور مطلوبة عند إنشاء حساب موظف جديد.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $st = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role, permissions, role_id) VALUES (?, ?, ?, ?, ?)");
                    try {
                        $st->execute([$username, $hash, $role_type, $permissions, $role_id]);
                        $success = "تم إنشاء حساب الموظف الجديد بنجاح!";
                    } catch (PDOException $e) {
                        $error = "اسم المستخدم '{$username}' مسجل مسبقاً لموظف آخر، يرجى اختيار اسم مستخدم مختلف.";
                    }
                }
            }
        }
    }
}

$admins = $pdo->query("
    SELECT a.*, COALESCE(r.name, CASE WHEN a.role = 'superadmin' THEN 'مدير عام (Superadmin)' ELSE 'غير محدد' END) as role_name,
           r.description as role_desc, r.is_superadmin as role_is_super
    FROM admin_users a
    LEFT JOIN roles r ON a.role_id = r.id
    ORDER BY a.id ASC
")->fetchAll();

$roles = $pdo->query("SELECT * FROM roles ORDER BY is_superadmin DESC, id ASC")->fetchAll();

$pageTitle = 'إدارة حسابات الموظفين والصلاحيات';
include __DIR__ . '/_layout_start.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:5px;">👥 فريق العمل وحسابات الموظفين</h1>
        <p class="admin-lead" style="margin-bottom:0;">إنشاء حسابات دخول مخصصة وتحديد صلاحيات كل موظف (مبيعات، شحن، محتوى، دعم)</p>
    </div>
    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
        <a href="<?= esc(admin_url('roles.php')) ?>" class="admin-btn" style="background:#27272a; border:1px solid #3f3f46; color:#e4e4e7; font-weight:700; padding:0.75rem 1.25rem; border-radius:10px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <span>🛡️</span> تخصيص الأدوار والصلاحيات
        </a>
        <button type="button" class="admin-btn" onclick="openAdminModal()" style="background:linear-gradient(135deg, #d4af37 0%, #b45309 100%); color:#fff; font-weight:800; padding:0.75rem 1.5rem; border-radius:10px; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(212,175,55,0.3);">
            <span>➕</span> إضافة حساب موظف جديد
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="admin-error" style="margin-bottom:1.5rem;"><?= esc($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="admin-notice" style="margin-bottom:1.5rem; background:#064e3b; border-color:#059669; color:#a7f3d0;">
        ✅ <?= esc($success) ?>
    </div>
<?php endif; ?>

<!-- Quick Guide Banner -->
<div class="admin-card" style="background:rgba(212,175,55,0.06); border:1px solid rgba(212,175,55,0.25); border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
    <div style="display:flex; gap:12px; align-items:flex-start;">
        <span style="font-size:1.6rem;">💡</span>
        <div style="font-size:0.9rem; line-height:1.6; color:#e4e4e7;">
            <strong style="color:#d4af37; display:block; margin-bottom:4px;">كيف تنشئ حساباً لأحد أفراد فريقك؟</strong>
            اضغط على زر <strong>"إضافة حساب موظف جديد"</strong> ➔ اكتب اسم المستخدم وكلمة المرور ➔ اختر الدور الوظيفي المناسب (مثل: مسؤول مبيعات، مسؤول شحن، خدمة عملاء). سيتمكن الموظف من تسجيل الدخول والوصول فقط للصفحات المخصصة لدوره دون الاطلاع على باقي إعدادات المتجر الحساسة.
        </div>
    </div>
</div>

<!-- Staff Table -->
<div class="admin-card" style="padding:0; overflow:hidden; border-radius:12px; border:1px solid #27272a;">
    <div class="admin-table-wrap" style="margin:0;">
        <table class="admin-table" style="margin:0;">
            <thead>
                <tr style="background:#18181b;">
                    <th style="width:70px;">ID</th>
                    <th>اسم المستخدم</th>
                    <th>الدور الوظيفي</th>
                    <th>نطاق الصلاحيات</th>
                    <th>تاريخ الإنشاء</th>
                    <th style="text-align: end;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $a): 
                    $isSuper = ($a['role'] === 'superadmin' || !empty($a['role_is_super']));
                ?>
                <tr>
                    <td style="color:#71717a; font-weight:700;">#<?= (int)$a['id'] ?></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:32px; height:32px; border-radius:50%; background:<?= $isSuper ? 'rgba(212,175,55,0.2)' : 'rgba(59,130,246,0.2)' ?>; color:<?= $isSuper ? '#d4af37' : '#60a5fa' ?>; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.85rem;">
                                <?= mb_strtoupper(mb_substr((string)$a['username'], 0, 1)) ?>
                            </div>
                            <strong style="color:#f4f4f5; font-size:1rem;"><?= esc($a['username']) ?></strong>
                            <?php if ($a['id'] == ($_SESSION[ADMIN_SESSION_KEY] ?? 0)): ?>
                                <span style="background:rgba(16,185,129,0.15); color:#10b981; font-size:0.7rem; font-weight:800; padding:2px 6px; border-radius:4px;">حسابك الحالي</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <span class="admin-badge <?= $isSuper ? 'admin-badge--warning' : 'admin-badge--info' ?>" style="font-weight:700;">
                            <?= $isSuper ? '👑 ' : '💼 ' ?><?= esc($a['role_name']) ?>
                        </span>
                    </td>
                    <td style="max-width: 250px; font-size: 0.84rem; color: #a1a1aa;">
                        <?php if ($isSuper): ?>
                            <span style="color:#10b981; font-weight:700;">✨ وصول كامل لجميع أقسام ولوحة التحكم</span>
                        <?php else: ?>
                            <?= esc($a['role_desc'] ?? $a['permissions'] ?? 'صلاحيات مخصصة') ?>
                        <?php endif; ?>
                    </td>
                    <td style="color:#71717a; font-size:0.85rem;"><?= esc((string)($a['created_at'] ?? '—')) ?></td>
                    <td style="text-align: end;">
                        <button type="button" class="admin-btn btn-admin--sm" style="background:#27272a; border:1px solid #3f3f46; color:#fff; cursor:pointer;"
                                onclick='editAdmin(<?= json_encode($a, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>)'>
                            ✏️ تعديل
                        </button>
                        <?php if (!$isSuper && $a['id'] != ($_SESSION[ADMIN_SESSION_KEY] ?? 0)): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف حساب هذا الموظف نهائياً؟')">
                                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                <input type="hidden" name="delete" value="<?= (int)$a['id'] ?>">
                                <button type="submit" class="admin-btn admin-btn--danger btn-admin--sm">🗑️ حذف</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Employee Modal -->
<div id="adminModal" class="admin-modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div class="admin-modal" style="background:#18181b; border:1px solid #27272a; border-radius:16px; max-width:520px; width:95%; padding:1.75rem; box-shadow:0 25px 60px rgba(0,0,0,0.6);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid #27272a; padding-bottom:1rem;">
            <h2 id="modalTitle" style="margin:0; font-size:1.3rem; font-weight:800; color:#f4f4f5;">إضافة موظف جديد</h2>
            <button type="button" onclick="closeAdminModal()" style="background:none; border:none; color:#a1a1aa; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        
        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            <input type="hidden" name="id" id="form-id" value="0">

            <div style="margin-bottom:1.25rem;">
                <label style="font-weight:700; color:#e4e4e7; margin-bottom:6px; display:block;">اسم المستخدم (Username) *</label>
                <input type="text" name="username" id="form-username" required placeholder="مثال: ahmed_sales أو sara_shipping" style="width:100%; padding:0.75rem 1rem; background:#09090b; border:1px solid #3f3f46; color:#fff; border-radius:8px; font-size:0.95rem;">
            </div>

            <div style="margin-bottom:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <label style="font-weight:700; color:#e4e4e7;">كلمة المرور (Password) *</label>
                    <button type="button" onclick="generateRandomPass()" style="background:none; border:none; color:#d4af37; font-size:0.8rem; font-weight:700; cursor:pointer;">
                        ⚡️ توليد كلمة سر تلقائية
                    </button>
                </div>
                <div style="position:relative;">
                    <input type="text" name="password" id="form-password" placeholder="أدخل كلمة المرور" style="width:100%; padding:0.75rem 1rem; background:#09090b; border:1px solid #3f3f46; color:#fff; border-radius:8px; font-size:0.95rem;">
                </div>
                <small id="password-hint" style="color:#71717a; font-size:0.8rem; margin-top:4px; display:block;">(اترك الحقل فارغاً عند التعديل إذا كنت لا ترغب في تغيير كلمة المرور الحالية)</small>
            </div>

            <div style="margin-bottom:1.5rem;">
                <label style="font-weight:700; color:#e4e4e7; margin-bottom:6px; display:block;">الدور الوظيفي والصلاحيات *</label>
                <select name="role_id" id="form-role-id" required onchange="onRoleChange()" style="width:100%; padding:0.75rem 1rem; background:#09090b; border:1px solid #3f3f46; color:#fff; border-radius:8px; font-size:0.95rem;">
                    <option value="">-- اختر الدور الوظيفي للموظف --</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= (int)$role['id'] ?>"
                            data-is-superadmin="<?= (int)$role['is_superadmin'] ?>"
                            data-desc="<?= esc($role['description']) ?>"
                            data-permissions="<?= esc($role['permissions']) ?>">
                        <?= esc($role['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Live Role Preview Box -->
                <div id="role-preview-box" style="margin-top:10px; background:rgba(0,0,0,0.3); border:1px solid #27272a; border-radius:8px; padding:0.8rem; font-size:0.84rem; color:#a1a1aa; display:none;">
                    <div id="role-preview-text"></div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #27272a; padding-top:1.25rem;">
                <button type="button" class="admin-btn admin-btn--secondary" onclick="closeAdminModal()" style="padding:0.75rem 1.25rem; border-radius:8px;">إلغاء</button>
                <button type="submit" class="admin-btn" style="background:#d4af37; color:#000; font-weight:800; padding:0.75rem 1.5rem; border-radius:8px; border:none; cursor:pointer;">
                    حفظ الحساب
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function onRoleChange() {
    var select = document.getElementById('form-role-id');
    var preview = document.getElementById('role-preview-box');
    var text = document.getElementById('role-preview-text');
    var selectedOption = select.options[select.selectedIndex];

    if (!selectedOption || !selectedOption.value) {
        preview.style.display = 'none';
        return;
    }

    var isSuper = selectedOption.getAttribute('data-is-superadmin') === '1';
    var desc = selectedOption.getAttribute('data-desc');

    preview.style.display = 'block';
    if (isSuper) {
        text.innerHTML = '👑 <strong style="color:#d4af37;">مدير عام:</strong> يمتلك صلاحية الوصول الكامل لجميع صفحات المتجر والإعدادات والحسابات.';
    } else {
        text.innerHTML = '🛡️ <strong style="color:#60a5fa;">صلاحيات هذا الدور:</strong> ' + desc;
    }
}

function generateRandomPass() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$';
    let pass = '';
    for (let i = 0; i < 10; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('form-password').value = pass;
}

function openAdminModal() {
    document.getElementById('form-id').value = '0';
    document.getElementById('form-username').value = '';
    document.getElementById('form-password').value = '';
    document.getElementById('form-role-id').value = '';
    document.getElementById('role-preview-box').style.display = 'none';
    document.getElementById('modalTitle').innerText = '➕ إضافة حساب موظف جديد';
    document.getElementById('password-hint').innerText = '(يجب تعيين كلمة مرور قوية للحساب)';
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
    document.getElementById('modalTitle').innerText = '✏️ تعديل بيانات الموظف: ' + admin.username;
    document.getElementById('password-hint').innerText = '(اترك الحقل فارغاً إذا كنت لا ترغب في تغيير كلمة المرور)';
    onRoleChange();
    document.getElementById('adminModal').style.display = 'flex';
}
</script>

<?php include __DIR__ . '/_layout_end.php'; ?>