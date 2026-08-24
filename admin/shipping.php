<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
require_admin('shipping');

$pageTitle = 'إدارة المحافظات وأسعار الشحن';

$pdo = medal_pdo();
$rows = [];
$edit = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($pdo !== null) {
    // Ensure columns exist
    medal_ensure_column($pdo, 'shipping_cities', 'active', 'TINYINT(1) NOT NULL DEFAULT 1');
    medal_ensure_column($pdo, 'shipping_cities', 'delivery_time', 'VARCHAR(100) NULL DEFAULT "1-3 أيام عمل"');

    try {
        $rows = $pdo->query('SELECT * FROM shipping_cities ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (\Exception $e) {
        $rows = [];
    }

    if ($editId > 0) {
        $st = $pdo->prepare('SELECT * FROM shipping_cities WHERE id = ?');
        $st->execute([$editId]);
        $edit = $st->fetch();
    }
}

// Calculate summary stats
$totalCities = count($rows);
$activeCities = count(array_filter($rows, fn($r) => (int)($r['active'] ?? 1) === 1));
$inactiveCities = $totalCities - $activeCities;

require __DIR__ . '/_layout_start.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:5px;">🚚 إدارة المحافظات وأسعار الشحن</h1>
        <p class="admin-lead" style="margin-bottom:0;">تحكم في أسعار وتفعيل الشحن لكافة محافظات جمهورية مصر العربية</p>
    </div>
    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
        <button type="button" onclick="seedAllGovernorates()" class="admin-btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color:#fff; font-weight:800; box-shadow:0 4px 12px rgba(16,185,129,0.3); border:none; padding:0.75rem 1.25rem; border-radius:10px; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
            <span>📍</span> إدراج/استعادة الـ 27 محافظة تلقائياً
        </button>
        <a href="#new-city-form" class="admin-btn" style="background:#d4af37; color:#000; font-weight:800; padding:0.75rem 1.25rem; border-radius:10px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <span>➕</span> إضافة محافظة مخصصة
        </a>
    </div>
</div>

<!-- Stats Row -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <div class="admin-card" style="margin:0; padding:1.25rem; border-right:4px solid #3b82f6; display:flex; align-items:center; gap:1rem;">
        <div style="font-size:2rem;">🏛️</div>
        <div>
            <div style="color:#94a3b8; font-size:0.85rem;">إجمالي المحافظات</div>
            <div style="font-size:1.5rem; font-weight:800; color:#f8fafc;" id="stat-total"><?= $totalCities ?></div>
        </div>
    </div>
    <div class="admin-card" style="margin:0; padding:1.25rem; border-right:4px solid #10b981; display:flex; align-items:center; gap:1rem;">
        <div style="font-size:2rem;">🟢</div>
        <div>
            <div style="color:#94a3b8; font-size:0.85rem;">المحافظات المفعلة</div>
            <div style="font-size:1.5rem; font-weight:800; color:#10b981;" id="stat-active"><?= $activeCities ?></div>
        </div>
    </div>
    <div class="admin-card" style="margin:0; padding:1.25rem; border-right:4px solid #ef4444; display:flex; align-items:center; gap:1rem;">
        <div style="font-size:2rem;">🔴</div>
        <div>
            <div style="color:#94a3b8; font-size:0.85rem;">المحافظات المعطلة</div>
            <div style="font-size:1.5rem; font-weight:800; color:#ef4444;" id="stat-inactive"><?= $inactiveCities ?></div>
        </div>
    </div>
</div>

<!-- Bulk Pricing & Quick Search Tool -->
<div class="admin-card" style="background:#18181b; border:1px solid #27272a; padding:1.25rem; margin-bottom:1.5rem; border-radius:12px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.25rem;">
        <!-- Bulk Price -->
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span style="font-weight:700; color:#e4e4e7; font-size:0.92rem;">⚡️ ضبط سعر موحد للكل:</span>
            <input type="number" id="bulk-price-input" placeholder="السعر (ج.م)" min="0" step="1" style="width:120px; padding:0.55rem 0.8rem; background:#09090b; border:1px solid #3f3f46; color:#fff; border-radius:8px; font-weight:700;">
            <button type="button" onclick="applyBulkPrice()" class="admin-btn" style="background:#d4af37; color:#000; font-weight:800; border:none; padding:0.55rem 1rem; border-radius:8px; cursor:pointer;">
                تطبيق على المحافظات المفعلة
            </button>
        </div>

        <!-- Search Filter -->
        <div style="display:flex; align-items:center; gap:8px; min-width:260px;">
            <input type="text" id="city-search" onkeyup="filterGovernorates()" placeholder="🔍 ابحث عن محافظة..." style="width:100%; padding:0.55rem 1rem; background:#09090b; border:1px solid #3f3f46; color:#fff; border-radius:8px;">
        </div>
    </div>
</div>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php else: ?>

<!-- Governorates Table -->
<div class="admin-card" style="padding:0; overflow:hidden; border-radius:12px; border:1px solid #27272a;">
    <div class="admin-table-wrap" style="margin:0;">
        <table class="admin-table" id="gov-table" style="margin:0;">
            <thead>
                <tr style="background:#18181b;">
                    <th style="width:60px; text-align:center;">الحالة</th>
                    <th>المحافظة (عربي)</th>
                    <th>المحافظة (English)</th>
                    <th style="width:180px;">سعر الشحن (ج.م)</th>
                    <th>مدة التوصيل التقديرية</th>
                    <th style="width:120px; text-align:center;">الترتيب</th>
                    <th style="text-align:end;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr id="no-govs-row">
                        <td colspan="7" style="text-align:center; padding:3rem 1rem; color:#a1a1aa;">
                            <div style="font-size:2.5rem; margin-bottom:10px;">📦</div>
                            <p style="font-size:1.1rem; font-weight:700; margin-bottom:15px;">لا توجد محافظات مسجلة حالياً</p>
                            <button type="button" onclick="seedAllGovernorates()" class="admin-btn" style="background:#10b981; color:#fff; font-weight:800; padding:0.75rem 1.5rem; border-radius:8px; border:none; cursor:pointer;">
                                📍 اضغط هنا لإدراج كافة محافظات مصر الـ 27 تلقائياً
                            </button>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): 
                        $isActive = (int)($r['active'] ?? 1) === 1;
                    ?>
                        <tr id="row-<?= (int)$r['id'] ?>" class="gov-row <?= $isActive ? '' : 'is-inactive' ?>" style="<?= $isActive ? '' : 'opacity:0.6; background:rgba(0,0,0,0.2);' ?>">
                            <!-- Active Toggle -->
                            <td style="text-align:center;">
                                <label class="custom-toggle" title="<?= $isActive ? 'مفعل للشحن' : 'معطل' ?>">
                                    <input type="checkbox" onchange="toggleGovActive(<?= (int)$r['id'] ?>, this.checked)" <?= $isActive ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </td>

                            <!-- Names -->
                            <td style="font-weight:700; font-size:1rem; color:#f4f4f5;">
                                <?= esc((string)$r['name_ar']) ?>
                            </td>
                            <td style="color:#a1a1aa;">
                                <?= esc((string)$r['name_en']) ?>
                            </td>

                            <!-- Quick Price Edit -->
                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <input type="number" id="cost-<?= (int)$r['id'] ?>" value="<?= (float)$r['shipping_cost'] ?>" min="0" step="0.5" style="width:90px; padding:0.4rem 0.6rem; background:#09090b; border:1px solid #3f3f46; color:#10b981; font-weight:800; border-radius:6px; font-size:0.95rem;">
                                    <button type="button" onclick="saveGovCost(<?= (int)$r['id'] ?>)" class="btn-save-sm" title="حفظ السعر">
                                        💾
                                    </button>
                                </div>
                            </td>

                            <!-- Delivery Time -->
                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <input type="text" id="time-<?= (int)$r['id'] ?>" value="<?= esc((string)($r['delivery_time'] ?? '1-3 أيام عمل')) ?>" placeholder="1-3 أيام" style="width:130px; padding:0.4rem 0.6rem; background:#09090b; border:1px solid #3f3f46; color:#94a3b8; font-size:0.85rem; border-radius:6px;">
                                    <button type="button" onclick="saveGovTime(<?= (int)$r['id'] ?>)" class="btn-save-sm" title="حفظ المدة">
                                        💾
                                    </button>
                                </div>
                            </td>

                            <!-- Sort -->
                            <td style="text-align:center; color:#71717a;">
                                <?= (int)$r['sort_order'] ?>
                            </td>

                            <!-- Actions -->
                            <td class="admin-actions" style="text-align:end;">
                                <a class="btn-admin btn-admin--sm" href="<?= esc(admin_url('shipping.php?edit=' . (int)$r['id'])) ?>#new-city-form">تعديل</a>
                                <form method="post" action="<?= esc(admin_url('shipping_delete.php')) ?>" style="display:inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه المحافظة؟');">
                                    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Governorate Form -->
<div id="new-city-form" class="admin-card" style="margin-top:2rem; background:#18181b; border:1px solid #27272a; border-radius:12px; padding:1.5rem;">
    <h2 style="margin-top:0; font-size:1.2rem; font-weight:800; margin-bottom:1rem; color:#f4f4f5;">
        <?= $edit ? '✏️ تعديل بيانات المحافظة' : '➕ إضافة محافظة مخصصة' ?>
    </h2>
    <form class="admin-form" method="post" action="<?= esc(admin_url('shipping_save.php')) ?>">
        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
        
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
            <div>
                <label for="name_ar" style="font-weight:700; margin-bottom:6px; display:block;">اسم المحافظة (بالعربية) *</label>
                <input type="text" id="name_ar" name="name_ar" required value="<?= esc($edit ? (string)$edit['name_ar'] : '') ?>" dir="rtl" placeholder="مثال: القاهرة" style="width:100%; padding:0.65rem 0.85rem; background:#09090b; border:1px solid #3f3f46; color:#fff; border-radius:8px;">
            </div>
            
            <div>
                <label for="name_en" style="font-weight:700; margin-bottom:6px; display:block;">اسم المحافظة (بالإنجليزية) *</label>
                <input type="text" id="name_en" name="name_en" required value="<?= esc($edit ? (string)$edit['name_en'] : '') ?>" placeholder="e.g. Cairo" style="width:100%; padding:0.65rem 0.85rem; background:#09090b; border:1px solid #3f3f46; color:#fff; border-radius:8px;">
            </div>

            <div>
                <label for="shipping_cost" style="font-weight:700; margin-bottom:6px; display:block;">سعر الشحن (ج.م) *</label>
                <input type="number" id="shipping_cost" name="shipping_cost" required value="<?= $edit ? (float)$edit['shipping_cost'] : 50 ?>" step="0.5" min="0" style="width:100%; padding:0.65rem 0.85rem; background:#09090b; border:1px solid #3f3f46; color:#10b981; font-weight:800; border-radius:8px;">
            </div>

            <div>
                <label for="sort_order" style="font-weight:700; margin-bottom:6px; display:block;">ترتيب الظهور</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= $edit ? (int)$edit['sort_order'] : 10 ?>" style="width:100%; padding:0.65rem 0.85rem; background:#09090b; border:1px solid #3f3f46; color:#fff; border-radius:8px;">
            </div>
        </div>
        
        <div style="margin-top:1.5rem; display:flex; gap:10px; align-items:center;">
            <button type="submit" class="admin-btn" style="background:#d4af37; color:#000; font-weight:800; padding:0.75rem 1.5rem; border-radius:8px; border:none; cursor:pointer;">
                <?= esc(t('admin_save')) ?>
            </button>
            <?php if ($edit): ?>
                <a href="<?= esc(admin_url('shipping.php')) ?>" class="admin-btn admin-btn--secondary" style="padding:0.75rem 1.25rem; border-radius:8px; text-decoration:none;">إلغاء</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php endif; ?>

<style>
.custom-toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}
.custom-toggle input { opacity: 0; width: 0; height: 0; }
.custom-toggle .slider {
    position: absolute; cursor: pointer; inset: 0;
    background-color: #3f3f46;
    transition: .3s;
    border-radius: 24px;
}
.custom-toggle .slider:before {
    position: absolute; content: "";
    height: 18px; width: 18px; left: 3px; bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}
.custom-toggle input:checked + .slider { background-color: #10b981; }
.custom-toggle input:checked + .slider:before { transform: translateX(20px); }

.btn-save-sm {
    background: #27272a;
    border: 1px solid #3f3f46;
    color: #fff;
    border-radius: 6px;
    padding: 0.35rem 0.6rem;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-save-sm:hover {
    background: #10b981;
    border-color: #10b981;
    transform: scale(1.05);
}
</style>

<script>
const CSRF_TOKEN = '<?= esc(admin_csrf_token()) ?>';
const AJAX_URL = '<?= esc(admin_url("ajax_shipping_update.php")) ?>';

// Live Governorate Search Filter
function filterGovernorates() {
    const input = document.getElementById('city-search').value.toLowerCase();
    const rows = document.querySelectorAll('.gov-row');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}

// Toggle Governorate Active / Inactive
async function toggleGovActive(id, isActive) {
    const row = document.getElementById('row-' + id);
    try {
        const formData = new FormData();
        formData.append('csrf', CSRF_TOKEN);
        formData.append('action', 'toggle_active');
        formData.append('id', id);
        formData.append('active', isActive ? 1 : 0);

        const res = await fetch(AJAX_URL, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (isActive) {
                row.classList.remove('is-inactive');
                row.style.opacity = '1';
                row.style.background = '';
            } else {
                row.classList.add('is-inactive');
                row.style.opacity = '0.6';
                row.style.background = 'rgba(0,0,0,0.2)';
            }
            updateStatsCount();
        }
    } catch (e) {
        alert('حدث خطأ أثناء تحديث الحالة');
    }
}

// Quick Save Shipping Cost
async function saveGovCost(id) {
    const cost = document.getElementById('cost-' + id).value;
    const btn = event.target;
    btn.innerText = '⏳';

    try {
        const formData = new FormData();
        formData.append('csrf', CSRF_TOKEN);
        formData.append('action', 'update_cost');
        formData.append('id', id);
        formData.append('cost', cost);

        const res = await fetch(AJAX_URL, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            btn.innerText = '✅';
            setTimeout(() => { btn.innerText = '💾'; }, 1500);
        }
    } catch (e) {
        btn.innerText = '❌';
        setTimeout(() => { btn.innerText = '💾'; }, 2000);
    }
}

// Quick Save Delivery Time
async function saveGovTime(id) {
    const time = document.getElementById('time-' + id).value;
    const btn = event.target;
    btn.innerText = '⏳';

    try {
        const formData = new FormData();
        formData.append('csrf', CSRF_TOKEN);
        formData.append('action', 'update_time');
        formData.append('id', id);
        formData.append('time', time);

        const res = await fetch(AJAX_URL, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            btn.innerText = '✅';
            setTimeout(() => { btn.innerText = '💾'; }, 1500);
        }
    } catch (e) {
        btn.innerText = '❌';
        setTimeout(() => { btn.innerText = '💾'; }, 2000);
    }
}

// Seed All 27 Governorates
async function seedAllGovernorates() {
    if (!confirm('هل تريد إدراج وتحديث كافة محافظات مصر الـ 27 تلقائياً؟')) return;

    try {
        const formData = new FormData();
        formData.append('csrf', CSRF_TOKEN);
        formData.append('action', 'seed_all');
        formData.append('default_cost', 50);

        const res = await fetch(AJAX_URL, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.error || 'حدث خطأ أثناء الإدراج');
        }
    } catch (e) {
        alert('تعذر الاتصال بالسيرفر');
    }
}

// Apply Bulk Price
async function applyBulkPrice() {
    const price = document.getElementById('bulk-price-input').value;
    if (!price || parseFloat(price) < 0) {
        alert('يرجى كتابة سعر صحيح');
        return;
    }

    if (!confirm(`هل تريد تطبيق سعر ${price} ج.م على جميع المحافظات المفعلة؟`)) return;

    try {
        const formData = new FormData();
        formData.append('csrf', CSRF_TOKEN);
        formData.append('action', 'bulk_cost');
        formData.append('cost', price);
        formData.append('apply_to', 'active');

        const res = await fetch(AJAX_URL, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            location.reload();
        }
    } catch (e) {
        alert('حدث خطأ أثناء تطبيق السعر');
    }
}

function updateStatsCount() {
    const total = document.querySelectorAll('.gov-row').length;
    const inactives = document.querySelectorAll('.gov-row.is-inactive').length;
    const actives = total - inactives;

    document.getElementById('stat-total').innerText = total;
    document.getElementById('stat-active').innerText = actives;
    document.getElementById('stat-inactive').innerText = inactives;
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
