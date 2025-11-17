<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json; charset=utf-8');

// Input validation
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı ve şifre gereklidir.']);
    exit;
}

try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
        exit;
    }

    if (password_verify($password, $user['password'])) {
        $auth = new Auth();
        $auth->login($user['id']);

        echo json_encode([
            'success' => true,
            'message' => 'Giriş başarılı!'
        ]);
        exit;
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Hatalı şifre girdiniz.']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()]);
    exit;
}
?>
