<?php
// admin/moderate_content.php
require_once '../config.php';
header('Content-Type: application/json');

$db = getDbConnection();
$userRole = $_SESSION['user_role'] ?? 'user';
$currentUserId = $_SESSION['user_id'] ?? null;

// Yetki kontrolü (Admin veya Moderatör)
if (!$currentUserId || !in_array($userRole, ['admin', 'moderator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz işlem.']);
    exit;
}

$targetId = $_POST['content_id'] ?? null;
$targetType = $_POST['content_type'] ?? null; // 'drawing' veya 'comment'
$action = $_POST['action'] ?? null; // 'hide' veya 'show'

if (!$targetId || !$targetType || !in_array($targetType, ['drawing', 'comment']) || !in_array($action, ['hide', 'show'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz parametreler.']);
    exit;
}

try {
    $tableName = ($targetType === 'drawing') ? 'drawings' : 'comments';
    $isVisible = ($action === 'show') ? 1 : 0;

    $sql = "UPDATE {$tableName} SET is_visible = :is_visible WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':is_visible', $isVisible, PDO::PARAM_INT);
    $stmt->bindParam(':id', $targetId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => "{$targetType} başarıyla güncellendi."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
