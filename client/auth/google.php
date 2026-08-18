<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$clientId = get_setting('google_client_id');
$clientSecret = get_setting('google_client_secret');
$redirectUri = base_url('client/auth/google.php');

if (!$clientId || !$clientSecret) {
    die('Google login is not configured.');
}

if (!isset($_GET['code'])) {
    $params = [
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
    ];
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    exit;
}

$code = $_GET['code'];
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code',
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_SSL_VERIFYPEER => true,
]);
$tokenResponse = curl_exec($ch);
curl_close($ch);

$token = json_decode($tokenResponse, true);
if (!isset($token['access_token'])) {
    die('Failed to get access token from Google.');
}

$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token['access_token']],
    CURLOPT_SSL_VERIFYPEER => true,
]);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userInfoResponse, true);
if (!isset($userInfo['email'])) {
    die('Failed to get user info from Google.');
}

$email = $userInfo['email'];
$name = $userInfo['name'] ?? '';
$googleId = $userInfo['id'] ?? '';

$pdo = medal_pdo();
if (!$pdo) die('Database not available.');

$st = $pdo->prepare('SELECT id, name, email, phone FROM clients WHERE social_id = ? AND social_provider = ?');
$st->execute([$googleId, 'google']);
$client = $st->fetch();

if (!$client) {
    $st = $pdo->prepare('SELECT id, name, email, phone FROM clients WHERE email = ?');
    $st->execute([$email]);
    $client = $st->fetch();

    if ($client) {
        $pdo->prepare('UPDATE clients SET social_id = ?, social_provider = ? WHERE id = ?')
            ->execute([$googleId, 'google', $client['id']]);
    } else {
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO clients (name, email, password_hash, social_id, social_provider) VALUES (?, ?, ?, ?, ?)')
            ->execute([$name, $email, $password, $googleId, 'google']);
        $client = ['id' => (int)$pdo->lastInsertId(), 'name' => $name, 'email' => $email, 'phone' => ''];
    }
}

$_SESSION['client_id']    = (int)$client['id'];
$_SESSION['client_name']  = (string)$client['name'];
$_SESSION['client_email'] = (string)$client['email'];
header('Location: ' . url('client/dashboard.php'));
exit;