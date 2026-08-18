<?php
$userLocationId = current_user_location_id();
$locations = sale_locations();
if ($userLocationId !== null) {
    $locations = array_values(array_filter($locations, fn ($l) => (int) $l['id'] === $userLocationId));
}

// Auto-create daily targets for today
$currentUser = current_user();
if ($currentUser) {
    try {
        auto_create_daily_targets((int) $currentUser['id']);
    } catch (Throwable $e) {
        // Silently fail - targets exist or other issue
    }
}

$selectedDate = $_GET['target_date'] ?? date('Y-m-d');
$rows = target_rows($userLocationId, $selectedDate);
$editId = (int) ($_GET['edit_id'] ?? 0);
$editTarget = $editId > 0 ? find_target($editId) : null;
if ($editTarget && $userLocationId !== null && (int) $editTarget['location_id'] !== $userLocationId) {
    $editTarget = null;
}
?>
<section class="page-head">
    <h2>🎯 التارجت اليومي</h2>
    <p>تارجت يومي لكل فرع مع نسبة تحقق ومبلغ متبقي. يتم إنشاء تارجت تلقائياً كل يوم.</p>
</section>

<form class="panel grid-form" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <?php if ($editTarget): ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= e($editTarget['id']) ?>">
    <?php endif; ?>

    <label>الفرع
        <?php if ($userLocationId === null && !$editTarget): ?>
            <select name="location_id">
                <?php foreach ($locations as $l): ?>
                    <option value="<?= e($l['id']) ?>"><?= e($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <select name="location_id" disabled>
                <?php foreach ($locations as $l): ?>
                    <?php $selectedLocation = $editTarget ? (int) $editTarget['location_id'] : $userLocationId; ?>
                    <option value="<?= e($l['id']) ?>" <?= $selectedLocation === (int)$l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="location_id" value="<?= e($editTarget['location_id'] ?? $userLocationId) ?>">
        <?php endif; ?>
    </label>

    <label>التاريخ
        <input name="target_date" type="date" value="<?= e($editTarget['target_date'] ?? date('Y-m-d')) ?>" required>
    </label>

    <label>قيمة التارجت (جنيه)
        <input name="target_amount" type="number" step="1" min="1" value="<?= e($editTarget['target_amount'] ?? '') ?>" required placeholder="مثال: 5000">
    </label>

    <div style="display:flex; gap:8px; align-items:end;">
        <button class="btn primary"><?= $editTarget ? 'حفظ تعديل التارجت' : 'حفظ التارجت' ?></button>
        <?php if ($editTarget): ?>
            <a class="btn" href="index.php?r=targets">إلغاء التعديل</a>
        <?php endif; ?>
    </div>
</form>

<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="margin: 0;">سجل التارجت اليومي</h3>
        <form method="get" style="display: flex; gap: 8px; align-items: center;">
            <input type="hidden" name="r" value="targets">
            <label style="display: flex; align-items: center; gap: 4px; font-size: 12px;">
                تصفية بالتاريخ:
                <input name="target_date" type="date" value="<?= e($selectedDate) ?>" onchange="this.form.submit()" style="padding: 4px 8px; border: 1px solid var(--line); border-radius: 6px;">
            </label>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>الفرع</th>
                <th>التاريخ</th>
                <th>التارجت</th>
                <th>المحقق</th>
                <th>النسبة</th>
                <th>المتبقي</th>
                <th>إجراء</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" class="muted" style="text-align: center;">لا توجد تارجتات لهذا اليوم.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $t):
                    $pct = (float)$t['target_amount'] > 0 ? ((float)$t['achieved'] / (float)$t['target_amount']) * 100 : 0;
                ?>
                    <tr>
                        <td><?= e($t['location_name']) ?></td>
                        <td><?= e($t['target_date']) ?></td>
                        <td><?= money($t['target_amount']) ?></td>
                        <td><?= money($t['achieved']) ?></td>
                        <td>
                            <?php if ((int)$t['target_amount'] > 0): ?>
                                <span style="color: <?= $pct >= 100 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') ?>; font-weight: 700;">
                                    <?= e(number_format($pct, 1)) ?>%
                                </span>
                            <?php else: ?>
                                <span class="muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= money(max(0, (float)$t['target_amount'] - (float)$t['achieved'])) ?></td>
                        <td style="white-space:nowrap;">
                            <a class="btn small" href="index.php?r=targets&edit_id=<?= e($t['id']) ?>&target_date=<?= e($selectedDate) ?>">تعديل</a>
                            <form method="post" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا التارجت اليومي؟')">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e($t['id']) ?>">
                                <button class="btn small danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
