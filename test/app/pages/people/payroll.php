<?php
$user = require_login();
$db = pdo();
$month = $_GET['month'] ?? date('Y-m');

$stmt = $db->query('SELECT u.*, r.name AS role_name, l.name AS location_name FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN locations l ON l.id = u.location_id WHERE u.is_active = 1 ORDER BY u.name');
$employees = $stmt->fetchAll();

$payrollData = [];
foreach ($employees as $emp) {
    // 1. Attendance Days & Delays
    $stmt = $db->prepare("SELECT COUNT(*) FROM attendance_records WHERE user_id = ? AND action = 'check_in' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$emp['id'], $month]);
    $daysPresent = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM attendance_records WHERE user_id = ? AND action = 'check_in' AND DATE_FORMAT(created_at, '%Y-%m') = ? AND TIME(created_at) > '09:00:00'");
    $stmt->execute([$emp['id'], $month]);
    $delays = (int)$stmt->fetchColumn();

    // 2. Personal Sales
    $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE user_id = ? AND status = 'completed' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$emp['id'], $month]);
    $personalSales = (float)$stmt->fetchColumn();

    // 3. Branch Sales & Target
    $branchSales = 0.0;
    $branchTarget = 0.0;
    if ($emp['location_id']) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE location_id = ? AND status = 'completed' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
        $stmt->execute([$emp['location_id'], $month]);
        $branchSales = (float)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(target_amount), 0) FROM branch_targets WHERE location_id = ? AND DATE_FORMAT(target_date, '%Y-%m') = ?");
        $stmt->execute([$emp['location_id'], $month]);
        $branchTarget = (float)($stmt->fetchColumn() ?: 0);
    }

    $targetPercent = $branchTarget > 0 ? ($branchSales / $branchTarget) * 100 : 0;

    // Calculate commission: Personal Sales * commission_percent
    $commission = ($personalSales * (float)$emp['commission_percent']) / 100;
    
    // Add bonus (e.g. 500 EGP) if target is set and fully achieved
    $bonus = 0.0;
    if ($targetPercent >= 100.0 && $branchTarget > 0) {
        $bonus = 500.0;
    }

    $totalPayout = (float)$emp['basic_salary'] + $commission + $bonus;

    $payrollData[] = [
        'user' => $emp,
        'days_present' => $daysPresent,
        'delays' => $delays,
        'personal_sales' => $personalSales,
        'branch_sales' => $branchSales,
        'branch_target' => $branchTarget,
        'target_percent' => $targetPercent,
        'commission' => $commission,
        'bonus' => $bonus,
        'total_payout' => $totalPayout
    ];
}
?>
<section class="page-head">
    <div>
        <h2>مسيرات الرواتب والمستحقات (HR)</h2>
        <p>الاحتساب التلقائي للمستحقات الشهرية بناءً على حضور الموظفين ومبيعاتهم الشخصية ونسبة تارجت الفروع.</p>
    </div>
</section>

<div class="panel toolbar" style="display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">
    <form method="get" style="display: flex; align-items: center; gap: 10px; margin: 0;">
        <input type="hidden" name="r" value="payroll">
        <label style="margin: 0; display: flex; align-items: center; gap: 8px;">اختر الشهر:
            <input type="month" name="month" value="<?= e($month) ?>" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: 8px; border: 1px solid var(--line); font-family: inherit;">
        </label>
    </form>
</div>

<div class="panel">
    <h3>سجل الأجور والعمولات لشهر (<?= e($month) ?>)</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--line); text-align: right;">
                <th>الموظف / الدور</th>
                <th>الموقع / الفرع</th>
                <th style="text-align: center;">الحضور / التأخير</th>
                <th style="text-align: center;">الراتب الأساسي</th>
                <th style="text-align: center;">العمولة %</th>
                <th>مبيعات الموظف</th>
                <th>أداء الفرع (التارجت)</th>
                <th>إجمالي المستحقات</th>
                <?php if (has_permission('users_permissions')): ?>
                    <th style="text-align: center; width: 100px;">إجراء</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payrollData as $row): 
                $u = $row['user'];
            ?>
                <tr style="border-bottom: 1px solid var(--line);">
                    <td>
                        <strong><?= e($u['name']) ?></strong><br>
                        <span class="muted" style="font-size: 11px;"><?= e($u['role_name']) ?></span>
                    </td>
                    <td><?= e($u['location_name'] ?: 'إدارة عامة') ?></td>
                    <td style="text-align: center;">
                        <span class="badge success" title="أيام الحضور"><?= $row['days_present'] ?> يوم</span>
                        <?php if ($row['delays'] > 0): ?>
                            <span class="badge danger" title="عدد مرات التأخير عن 9 صباحاً" style="margin-right: 4px;"><?= $row['delays'] ?> تأخير</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if (has_permission('users_permissions')): ?>
                            <form method="post" id="form-rates-<?= $u['id'] ?>" style="margin: 0; display: inline-flex; align-items: center; gap: 4px;">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_rates">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="number" name="basic_salary" value="<?= (float)$u['basic_salary'] ?>" step="1" min="0" style="width: 80px; padding: 4px; border: 1px solid var(--line); border-radius: 6px; text-align: center; outline: none; font-family: inherit;">
                        <?php else: ?>
                            <strong><?= money($u['basic_salary']) ?></strong>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if (has_permission('users_permissions')): ?>
                                <input type="number" name="commission_percent" value="<?= (float)$u['commission_percent'] ?>" step="1" min="0" style="width: 60px; padding: 4px; border: 1px solid var(--line); border-radius: 6px; text-align: center; outline: none; font-family: inherit;"> %
                            </form>
                        <?php else: ?>
                            <strong><?= (float)$u['commission_percent'] ?> %</strong>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= money($row['personal_sales']) ?></strong><br>
                        <span class="muted" style="font-size: 11px;">العمولة: <?= money($row['commission']) ?></span>
                    </td>
                    <td>
                        <?php if ($u['location_id']): ?>
                            <strong><?= money($row['branch_sales']) ?></strong> / <?= money($row['branch_target']) ?><br>
                            <span class="badge <?= $row['target_percent'] >= 100 ? 'success' : 'warning' ?>" style="font-size: 10px;">
                                <?= number_format($row['target_percent'], 1) ?>% محقق
                            </span>
                            <?php if ($row['bonus'] > 0): ?>
                                <span class="badge success" style="font-size: 10px; margin-right: 4px;" title="مكافأة تحقيق التارجت">+<?= money($row['bonus']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="color: var(--primary-dark); font-weight: 700; font-size: 14px;">
                        <?= money($row['total_payout']) ?>
                    </td>
                    <?php if (has_permission('users_permissions')): ?>
                        <td style="text-align: center;">
                            <button type="submit" form="form-rates-<?= $u['id'] ?>" class="btn small success" style="padding: 4px 8px; font-size: 11px;">تحديث 💾</button>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
