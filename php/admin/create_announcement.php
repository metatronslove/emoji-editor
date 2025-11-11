<?php
require_once '../config.php';
header('Content-Type: application/json');

$userRole = $_SESSION['user_role'] ?? 'user';
if (!in_array($userRole, ['admin', 'moderator'])) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz işlem.']);
    exit;
}

$content = $_POST['content'] ?? '';
$type = $_POST['type'] ?? 'info';

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Duyuru içeriği boş olamaz.']);
    exit;
}

try {
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO announcements (content, type, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$content, $type, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Duyuru başarıyla oluşturuldu.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Duyuru oluşturulamadı: ' . $e->getMessage()]);
}
?>
