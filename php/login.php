<?php
// login_handler.php - DÜZELTİLMİŞ
require_once 'config.php';
require_once 'Auth.php';

// SESSION BAŞLAT
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Google Client Objesini Oluştur
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email'); // İstenen izinler (e-posta adresi)
$client->addScope('profile'); // İstenen izinler (profil bilgileri)

// Google'a yönlendirme URL'sini al
$auth_url = $client->createAuthUrl();

// Tarayıcıyı Google'a yönlendir
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit();
