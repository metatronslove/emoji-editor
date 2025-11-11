<?php
// login_handler.php - TAMAMEN DÜZELTİLMİŞ
require_once 'config.php';
require_once 'Auth.php';

// SESSION BAŞLAT - MUTLAKA EN ÜSTTE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        throw new Exception('Kullanıcı adı ve şifre gereklidir.');
    }

    $auth = new Auth();

    if ($auth->login($username, $password)) {
        echo json_encode([
            'success' => true,
            'message' => 'Giriş başarılı!'
        ]);
        exit;
    } else {
        throw new Exception('Giriş başarısız.');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
