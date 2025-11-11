<?php
require_once '../config.php';
header('Content-Type: application/json');

$userRole = $_SESSION['user_role'] ?? 'user';
if ($userRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Sadece adminler platform ekleyebilir.']);
    exit;
}

$name = $_POST['name'] ?? '';
$emoji = $_POST['emoji'] ?? '';
$regex = $_POST['regex'] ?? '';

if (empty($name) || empty($emoji)) {
    echo json_encode(['success' => false, 'message' => 'Platform adı ve emoji gereklidir.']);
    exit;
}

try {
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO social_media_platforms (name, emoji, url_regex, is_active) VALUES (?, ?, ?, 1)");
    $stmt->execute([$name, $emoji, $regex]);

    echo json_encode(['success' => true, 'message' => 'Sosyal medya platformu başarıyla eklendi.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Platform eklenemedi: ' . $e->getMessage()]);
}
?>
