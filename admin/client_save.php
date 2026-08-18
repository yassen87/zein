<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pdo = medal_pdo();
if ($pdo === null) {
    die(t('admin_err_db_not_configured'));
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if ($name === '' || $email === '') {
    die(t('admin_err_names_required'));
}

// 1. Check unique email
$chkEmail = $pdo->prepare('SELECT id FROM clients WHERE email = ? AND id != ?');
$chkEmail->execute([$email, $id]);
if ($chkEmail->fetch()) {
    die(current_lang() === 'ar' ? 'البريد الإلكتروني مسجل بالفعل لعميل آخر.' : 'Email is already in use by another client.');
}

// 2. Check unique phone
if ($phone !== '') {
    $chkPhone = $pdo->prepare('SELECT id FROM clients WHERE phone = ? AND id != ?');
    $chkPhone->execute([$phone, $id]);
    if ($chkPhone->fetch()) {
        die(current_lang() === 'ar' ? 'رقم الهاتف مسجل بالفعل لعميل آخر.' : 'Phone number is already in use by another client.');
    }
}

try {
    if ($id > 0) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $pdo->prepare('UPDATE clients SET name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?');
            $st->execute([$name, $email, $phone, $hash, $id]);
        } else {
            $st = $pdo->prepare('UPDATE clients SET name = ?, email = ?, phone = ? WHERE id = ?');
            $st->execute([$name, $email, $phone, $id]);
        }
    } else {
        if ($password === '') {
            die(t('admin_err_enter_credentials'));
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO clients (name, email, phone, password_hash, is_verified) VALUES (?, ?, ?, ?, 1)');
        $st->execute([$name, $email, $phone, $hash]);
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

header('Location: clients.php?saved=1');
exit;
