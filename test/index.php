<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/app/router.php';
require_once __DIR__ . '/app/layout.php';

$route = route();
handle_download($route);

if ($route === 'install') {
    install_database();
    flash('تم تجهيز قاعدة بيانات MySQL. بيانات الدخول: admin / admin123');
    redirect('login');
}

if (!database_exists()) {
    ?>
    <!doctype html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>تثبيت النظام</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body class="login-page">
    <main class="login-card">
        <div class="brand-mark">ERP</div>
        <h1><?= e(APP_NAME) ?></h1>
        <p>لم يتم تثبيت قاعدة البيانات بعد.</p>
        <a class="btn primary" href="index.php?r=install">تثبيت MySQL الآن</a>
    </main>
    </body>
    </html>
    <?php
    exit;
}

if ($route === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        if (login_user(post_string('username'), post_string('password'))) {
            redirect('dashboard');
        }
        flash('بيانات الدخول غير صحيحة.', 'danger');
    }
    render_login();
    exit;
}

if ($route === 'logout') {
    logout_user();
    redirect('login');
}

if ($route === 'print_barcode') {
    $user = require_login();
    $file = page_path_for_route($route);
    if ($file && is_file($file)) {
        require $file;
    } else {
        exit('الملف غير موجود');
    }
    exit;
}

if ($route === 'quick_add_customer') {
    $user = require_login();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $name = post_string('name');
        $phone = post_string('phone');
        $birthdate = post_string('birthdate');
        if (!$name) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'الاسم مطلوب']);
            exit;
        }
        $db = pdo();
        if ($phone !== '') {
            $stmt = $db->prepare('SELECT id, name, phone, is_active FROM customers WHERE phone = ? ORDER BY is_active DESC, id DESC LIMIT 1');
            $stmt->execute([$phone]);
            $existing = $stmt->fetch();
            if ($existing) {
                if ((int) $existing['is_active'] === 0) {
                    $stmt = $db->prepare('UPDATE customers SET name = ?, source = "offline", is_active = 1 WHERE id = ?');
                    $stmt->execute([$name, (int) $existing['id']]);
                    $existing['name'] = $name;
                }
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'id' => (int) $existing['id'],
                    'name' => $existing['name'],
                    'phone' => $existing['phone'],
                    'message' => 'العميل مسجل مسبقاً'
                ]);
                exit;
            }
        }
        $stmt = $db->prepare('INSERT INTO customers (name, phone, source, birthdate) VALUES (?, ?, "offline", ?)');
        $stmt->execute([$name, $phone ?: null, $birthdate ?: null]);
        $id = (int) $db->lastInsertId();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'id' => $id,
            'name' => $name,
            'phone' => $phone
        ]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح']);
    exit;
}

if ($route === 'barcode_lookup') {
    $user = require_login();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $barcode = trim((string) ($_GET['barcode'] ?? ''));
        header('Content-Type: application/json');
        if ($barcode === '') {
            echo json_encode(['status' => 'error', 'message' => 'الباركود مطلوب']);
            exit;
        }
        $product = find_product_by_barcode($barcode);
        if ($product) {
            echo json_encode([
                'status' => 'success',
                'product' => [
                    'id' => (int) $product['id'],
                    'name' => $product['name'],
                    'type' => $product['type'],
                    'sale_price' => (float) $product['sale_price'],
                    'size_ml' => $product['size_ml'] ? (int) $product['size_ml'] : null,
                    'perfume_family' => $product['perfume_family'] ?? null,
                    'quality_grade' => $product['quality_grade'] ?? null,
                    'price_per_gram' => $product['price_per_gram'] ? (float) $product['price_per_gram'] : null,
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'لا يوجد منتج بهذا الباركود']);
        }
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح']);
    exit;
}

$user = require_login();
verify_csrf();

try {
    handle_post($route, $user);
} catch (Throwable $e) {
    flash($e->getMessage(), 'danger');
    redirect($route);
}

render_layout($route, $user);
