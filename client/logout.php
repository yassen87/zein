<?php
declare(strict_types=1);

session_start();
unset($_SESSION['client_id']);
unset($_SESSION['client_name']);
unset($_SESSION['client_email']);
session_destroy();

if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

header('Location: login.php');
exit;
