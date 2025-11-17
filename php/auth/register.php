<?php
// register.php - GRAVATAR DESTEKLİ
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        throw new Exception('Tüm alanlar gereklidir.');
    }

    if ($password !== $password_confirm) {
        throw new Exception('Şifreler uyuşmuyor.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Geçersiz email formatı.');
    }

    // Database connection
    $db = getDbConnection();

    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->fetch()) {
        throw new Exception('Kullanıcı adı veya email zaten kullanımda.');
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Generate gravatar
    $gravatar = GRAVATAR_URL . md5(strtolower(trim($email))) . '?d=identicon&s=200';

    // Insert user
    $stmt = $db->prepare("INSERT INTO users (username, email, password, profile_picture) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword, $gravatar]);

    // Auto login after registration
    $userId = $db->lastInsertId();
    $auth = new Auth();
    $auth->login($userId);

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
