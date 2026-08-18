<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pdo = medal_pdo();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$client = null;

if ($id > 0 && $pdo !== null) {
    try {
        $st = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
        $st->execute([$id]);
        $client = $st->fetch();
    } catch (Throwable) {}
}

if (!$client) {
    $client = [
        'name' => $_GET['name'] ?? '',
        'email' => $_GET['email'] ?? '',
        'phone' => '',
    ];
}

$pageTitle = $id > 0 ? t('admin_edit') . ' ' . t('admin_clients') : t('admin_new_client');
require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <h1><?= esc($pageTitle) ?></h1>
    <a href="clients.php" class="admin-btn admin-badge"><?= esc(t('admin_back_orders')) ?></a>
</div>

<div class="admin-card">
    <form action="client_save.php" method="POST" class="admin-form">
        <input type="hidden" name="id" value="<?= $id ?>">
        
        <div class="admin-form-group">
            <label for="name"><?= esc(t('label_name')) ?></label>
            <input type="text" id="name" name="name" class="admin-input" value="<?= esc((string)($client['name'] ?? '')) ?>" required>
        </div>

        <div class="admin-form-group">
            <label for="email"><?= esc(t('label_email')) ?></label>
            <input type="email" id="email" name="email" class="admin-input" value="<?= esc((string)($client['email'] ?? '')) ?>" required>
        </div>

        <div class="admin-form-group">
            <label for="phone"><?= esc(t('admin_th_phone')) ?></label>
            <input type="text" id="phone" name="phone" class="admin-input" value="<?= esc((string)($client['phone'] ?? '')) ?>">
        </div>

        <div class="admin-form-group">
            <label for="password"><?= esc(t('admin_login_password')) ?> <?= $id > 0 ? '(' . t('admin_leave_blank') . ')' : '' ?></label>
            <input type="password" id="password" name="password" class="admin-input" <?= $id === 0 ? 'required' : '' ?>>
        </div>

        <div class="admin-form-actions-fixed">
            <button type="submit" class="admin-btn admin-btn--primary"><?= esc(t('admin_save')) ?></button>
            <a href="clients.php" class="admin-btn admin-btn--secondary"><?= esc(t('admin_cancel')) ?? 'Cancel' ?></a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
