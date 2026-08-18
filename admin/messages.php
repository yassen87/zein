<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_messages');

$pdo = medal_pdo();
$page = max(1, (int) ($_GET['page'] ?? 1));
$pageSize = 10;
$offset = ($page - 1) * $pageSize;
$totalMessages = 0;

if ($pdo !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $mid = (int) ($_POST['message_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read_all') {
        $pdo->prepare('UPDATE contact_messages SET read_at = NOW() WHERE read_at IS NULL')->execute();
    } elseif ($mid > 0) {
        if ($action === 'mark_read') {
            $pdo->prepare('UPDATE contact_messages SET read_at = NOW() WHERE id = ? AND read_at IS NULL')->execute([$mid]);
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$mid]);
        }
    }
    header('Location: ' . admin_url('messages.php' . ($page > 1 ? '?page=' . $page : '')));
    exit;
}

$rows = [];
if ($pdo !== null) {
    try {
        $totalMessages = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
        $totalPages = max(1, (int) ceil($totalMessages / $pageSize));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $pageSize;
        }
        $rows = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ' . $pageSize . ' OFFSET ' . $offset)->fetchAll();
    } catch (Throwable) {
        $rows = [];
    }
}

require __DIR__ . '/_layout_start.php';
?>

<h1><?= esc(t('admin_messages')) ?></h1>
<p class="admin-lead"><?= esc(t('admin_messages_lead')) ?></p>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php else: ?>
    <div class="admin-card admin-card--toolbar" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1.25rem; padding:1rem 1.25rem;">
        <div>
            <strong><?= esc((string) $totalMessages) ?></strong>
            <?= current_lang() === 'ar' ? 'رسالة' : 'Messages' ?>
        </div>
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <form method="post" action="<?= esc(admin_url('messages.php' . ($page > 1 ? '?page=' . $page : ''))) ?>">
                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                <input type="hidden" name="action" value="mark_read_all">
                <button type="submit" class="admin-btn admin-btn--success"><?= current_lang() === 'ar' ? 'وضع الكل كمقروء' : 'Mark All Read' ?></button>
            </form>
        </div>
    </div>

    <?php if ($rows === []): ?>
        <p class="admin-muted"><?= esc(t('admin_no_messages')) ?></p>
    <?php else: ?>
        <?php foreach ($rows as $m): ?>
        <div class="admin-card admin-message <?= empty($m['read_at']) ? 'admin-message--unread' : '' ?>">
            <div class="admin-message__header">
                <div>
                    <strong><?= esc((string) $m['name']) ?></strong>
                    <div class="admin-message__email">
                        <a href="mailto:<?= esc((string) $m['email']) ?>"><?= esc((string) $m['email']) ?></a>
                    </div>
                </div>
                <div class="admin-message__meta">
                    <span><?= esc((string) $m['created_at']) ?></span>
                    <?php if (!empty($m['read_at'])): ?>
                        <span class="admin-badge admin-badge--success"><?= esc(t('admin_read_at')) ?></span>
                    <?php else: ?>
                        <span class="admin-badge admin-badge--pending"><?= current_lang() === 'ar' ? 'جديد' : 'New' ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="admin-message__body"><?= esc((string) $m['message']) ?></div>
            <div class="admin-message__actions">
                <?php if (empty($m['read_at'])): ?>
                    <form method="post" action="<?= esc(admin_url('messages.php')) ?>" style="margin-right: 0.5rem;">
                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                        <input type="hidden" name="action" value="mark_read">
                        <input type="hidden" name="message_id" value="<?= (int) $m['id'] ?>">
                        <button type="submit" class="admin-btn admin-btn--success"><?= esc(t('admin_mark_read')) ?></button>
                    </form>
                <?php endif; ?>
                <form method="post" action="<?= esc(admin_url('messages.php')) ?>" onsubmit="return confirm('<?= esc(current_lang() === 'ar' ? 'هل أنت متأكد من حذف هذه الرسالة؟' : 'Are you sure you want to delete this message?') ?>')">
                    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="message_id" value="<?= (int) $m['id'] ?>">
                    <button type="submit" class="admin-btn admin-btn--danger"><?= esc(t('admin_delete')) ?></button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
