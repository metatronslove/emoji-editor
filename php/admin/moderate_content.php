<?php
// admin/moderate_content.php - DÜZELTİLDİ
require_once '../config.php';
require_once '../functions.php';
session_start();
header('Content-Type: application/json');

$userRole = $_SESSION['user_role'] ?? 'user';
if (!in_array($userRole, ['admin', 'moderator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz işlem.']);
    exit;
}

$contentId = intval($_POST['content_id'] ?? 0);
$contentType = $_POST['content_type'] ?? '';
$action = $_POST['action'] ?? '';

if (!$contentId || !in_array($contentType, ['drawing', 'comment']) || !in_array($action, ['hide', 'show'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz parametreler.']);
    exit;
}

try {
    $db = getDbConnection();
    $table = $contentType === 'drawing' ? 'drawings' : 'comments';
    $isVisible = $action === 'show' ? 1 : 0;

    $stmt = $db->prepare("UPDATE {$table} SET is_visible = ? WHERE id = ?");
    $stmt->execute([$isVisible, $contentId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => "{$contentType} başarıyla güncellendi."]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "İçerik bulunamadı."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
?>
