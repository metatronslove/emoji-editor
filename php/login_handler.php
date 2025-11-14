<?php
require_once 'config.php';
require_once 'Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

$input = filter_var_array($_POST, FILTER_VALIDATE_EMAIL | FILTER_SANITIZE_STRING);
if (empty($input['username']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı ve şifre gereklidir.']);
    exit;
}

// Fix SQL injection issue
$stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $input['username']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
    exit;
}

// Add missing functions
$user = $result->fetch_assoc();
if (password_verify($input['password'], $user['password'])) {
    $auth = new Auth();
    $auth->login($user['user_id']);
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

// Update file paths for new location
$conn->close();