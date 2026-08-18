<?php
$locations = sale_locations();
$userLocationId = current_user_location_id();
if ($userLocationId !== null) {
    $locations = array_values(array_filter($locations, fn ($l) => (int) $l['id'] === $userLocationId));
}
$rows = shift_rows($userLocationId);
$editId = (int) ($_GET['edit_id'] ?? 0);
$editShift = $editId > 0 ? find_shift_closure($editId) : null;
if ($editShift && $userLocationId !== null && (int) $editShift['location_id'] !== $userLocationId) {
    $editShift = null;
}
?>
<section class="page-head">
    <h2>إغلاق الشيفت اليومي</h2>
    <p>يقارن كاش الدرج بإجمالي مدفوعات الكاش المسجلة للموظف اليوم.</p>
</section>

<?php if (!$locations): ?>
    <div class="alert danger">إغلاق الشيفت مسموح للفروع فقط.</div>
<?php else: ?>
    <form class="panel grid-form" method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <?php if ($editShift): ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= e($editShift['id']) ?>">
        <?php endif; ?>
        <label>الموقع
            <select name="location_id" <?= $userLocationId !== null || $editShift ? 'disabled' : '' ?>>
                <?php foreach ($locations as $l): ?>
                    <?php $selectedLocation = $editShift ? (int) $editShift['location_id'] : $userLocationId; ?>
                    <option value="<?= e($l['id']) ?>" <?= $selectedLocation === (int)$l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($userLocationId !== null): ?>
                <input type="hidden" name="location_id" value="<?= e($userLocationId) ?>">
            <?php elseif ($editShift): ?>
                <input type="hidden" name="location_id" value="<?= e($editShift['location_id']) ?>">
            <?php endif; ?>
        </label>
        <label>الكاش الفعلي
            <input name="actual_cash" type="number" step="1" min="0" value="<?= e($editShift['actual_cash'] ?? '') ?>" required>
        </label>
        <label>ملاحظة
            <input name="notes" value="<?= e($editShift['notes'] ?? '') ?>">
        </label>
        <div style="display:flex; gap:8px; align-items:end;">
            <button class="btn primary"><?= $editShift ? 'حفظ تعديل الشيفت' : 'إغلاق الشيفت' ?></button>
            <?php if ($editShift): ?>
                <a class="btn" href="index.php?r=shifts">إلغاء التعديل</a>
            <?php endif; ?>
        </div>
    </form>
<?php endif; ?>

<div class="panel">
    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>الموظف</th>
                <th>الموقع</th>
                <th>المتوقع</th>
                <th>الفعلي</th>
                <th>الفرق</th>
                <th>ملاحظة</th>
                <th>إجراء</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8" class="muted" style="text-align:center;">لا توجد شيفتات مسجلة.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $s): ?>
                <tr>
                    <td><?= e($s['shift_date']) ?></td>
                    <td><?= e($s['user_name']) ?></td>
                    <td><?= e($s['location_name']) ?></td>
                    <td><?= money($s['expected_cash']) ?></td>
                    <td><?= money($s['actual_cash']) ?></td>
                    <td><?= money($s['difference']) ?></td>
                    <td><?= e($s['notes'] ?: '-') ?></td>
                    <td style="white-space:nowrap;">
                        <a class="btn small" href="index.php?r=shifts&edit_id=<?= e($s['id']) ?>">تعديل</a>
                        <form method="post" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الشيفت؟')">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= e($s['id']) ?>">
                            <button class="btn small danger">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
