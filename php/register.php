<?php
// register.php - GRAVATAR DESTEKLİ
require_once 'config.php';
require_once 'Auth.php';

// SESSION BAŞLAT - MUTLAKA EN ÜSTTE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

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

    // Veritabanı bağlantısı kurun
    $db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($db->connect_errno) {
        throw new Exception('Veritabanı bağlantısı kurulamadı.');
    }

    // Kayıt işlemini gerçekleştirin
    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);
    $stmt->execute();

    // Kayıt işlemini gerçekleştirdikten sonra veritabanı bağlantısını kapatın
    $db->close();

    echo json_encode([
        'success' => true,
        'message' => 'Kayıt başarılı! Hoş geldiniz.'
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>