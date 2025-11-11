<?php
// login_handler.php - Basitleştirilmiş
require_once 'config.php';
require_once 'Auth.php';

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
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
