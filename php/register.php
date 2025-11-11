<?php
// register.php - GRAVATAR DESTEKLİ
require_once 'config.php';
require_once 'Auth.php';

// SESSION BAŞLAT - MUTLAKA EN ÜSTTE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Basit validasyon
    if (empty($username) || empty($email) || empty($password)) {
        throw new Exception('Tüm alanlar gereklidir.');
    }

    if ($password !== $password_confirm) {
        throw new Exception('Şifreler uyuşmuyor.');
    }

    $auth = new Auth();

    if ($auth->register($username, $email, $password)) {
        echo json_encode([
            'success' => true,
            'message' => 'Kayıt başarılı! Hoş geldiniz.'
        ]);
        exit;
    }

    throw new Exception('Kayıt işlemi başarısız.');

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
