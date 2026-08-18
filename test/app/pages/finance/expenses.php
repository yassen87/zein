<?php
$userLocationId = current_user_location_id();

$categories = expense_categories();
$locations = all_locations();
if ($userLocationId !== null) {
    $locations = array_values(array_filter($locations, fn ($l) => (int) $l['id'] === $userLocationId));
}
$rows = expense_rows($userLocationId);
?>
<section class="page-head">
    <h2>المصاريف</h2>
    <p>تسجيل مصروف حسب الفئة والموقع مع تقرير شهري/سنوي قابل للتوسع.</p>
</section>

<form class="panel grid-form" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    
    <label>الفئة
        <select name="category_id">
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    
    <label>الموقع
        <?php if ($userLocationId === null): ?>
        <select name="location_id">
            <option value="">عام</option>
            <?php foreach ($locations as $l): ?>
                <option value="<?= e($l['id']) ?>"><?= e($l['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php else: ?>
            <select name="location_id" disabled>
                <?php foreach ($locations as $l): ?>
                    <option value="<?= e($l['id']) ?>" <?= $userLocationId === (int)$l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="location_id" value="<?= $userLocationId ?>">
        <?php endif; ?>
    </label>
    
    <label>المبلغ
        <input name="amount" type="number" step="1" min="1" required>
    </label>
    
    <label>التاريخ
        <input name="expense_date" type="date" value="<?= e(date('Y-m-d')) ?>" required>
    </label>
    
    <label>ملاحظة
        <input name="notes">
    </label>
    
    <button class="btn primary">تسجيل مصروف</button>
</form>

<div class="panel">
    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>الفئة</th>
                <th>الموقع</th>
                <th>المبلغ</th>
                <th>المسؤول</th>
                <th>ملاحظة</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="6" class="muted" style="text-align: center;">لا توجد مصاريف مسجلة.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $eRow): ?>
                    <tr>
                        <td><?= e($eRow['expense_date']) ?></td>
                        <td><?= e($eRow['category_name']) ?></td>
                        <td><?= e($eRow['location_name'] ?: 'عام') ?></td>
                        <td><?= money($eRow['amount']) ?></td>
                        <td><?= e($eRow['user_name']) ?></td>
                        <td><?= e($eRow['notes']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
