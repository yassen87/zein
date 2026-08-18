<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(t('admin_err_bad_request'));
}

admin_verify_csrf();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$code = strtoupper(trim((string) ($_POST['code'] ?? '')));
$discountPercentage = (int) ($_POST['discount_percentage'] ?? 0);
$usageLimit = (int) ($_POST['usage_limit'] ?? 0);
$active = !empty($_POST['active']) ? 1 : 0;

if ($code === '' || $discountPercentage < 1 || $discountPercentage > 100) {
    exit(t('admin_err_invalid_input'));
}

$pdo = medal_pdo();
if ($pdo !== null) {
    try {
        if ($id > 0) {
            $st = $pdo->prepare('UPDATE promo_codes SET code=?, discount_percentage=?, usage_limit=?, active=? WHERE id=?');
            $st->execute([$code, $discountPercentage, $usageLimit, $active, $id]);
        } else {
            $st = $pdo->prepare('INSERT INTO promo_codes (code, discount_percentage, usage_limit, active) VALUES (?,?,?,?)');
            $st->execute([$code, $discountPercentage, $usageLimit, $active]);
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            header('Location: ' . admin_url('promo_codes.php?err=code_in_use' . ($id > 0 ? '&edit=' . $id : '')));
            exit;
        } else {
            throw $e;
        }
    }
}

header('Location: ' . admin_url('promo_codes.php'));
exit;
