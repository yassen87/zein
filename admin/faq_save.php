<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
admin_verify_csrf();

$pdo = medal_pdo();
if ($pdo === null) {
    exit('DB NOT CONFIGURED');
}

$id = (int) ($_POST['id'] ?? 0);
$qEn = trim((string) ($_POST['question_en'] ?? ''));
$qAr = trim((string) ($_POST['question_ar'] ?? ''));
$aEn = trim((string) ($_POST['answer_en'] ?? ''));
$aAr = trim((string) ($_POST['answer_ar'] ?? ''));
$sort = (int) ($_POST['sort_order'] ?? 0);

if ($qEn === '' || $qAr === '' || $aEn === '' || $aAr === '') {
    header('Location: ' . admin_url('faqs.php?err=required_fields_missing'));
    exit;
}

try {
    if ($id > 0) {
        $st = $pdo->prepare('UPDATE faqs SET question_en = ?, question_ar = ?, answer_en = ?, answer_ar = ?, sort_order = ? WHERE id = ?');
        $st->execute([$qEn, $qAr, $aEn, $aAr, $sort, $id]);
    } else {
        $st = $pdo->prepare('INSERT INTO faqs (question_en, question_ar, answer_en, answer_ar, sort_order) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$qEn, $qAr, $aEn, $aAr, $sort]);
    }
} catch (\Exception $e) {
    header('Location: ' . admin_url('faqs.php?err=' . urlencode($e->getMessage())));
    exit;
}

header('Location: ' . admin_url('faqs.php'));
exit;
