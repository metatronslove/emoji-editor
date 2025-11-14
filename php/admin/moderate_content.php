<?php
// admin/moderate_content.php
require_once '../config.php';
header('Content-Type: application/json');
session_start();

if (!isAuthorizedUser()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz işlem.']);
    exit;
}

$targetId = filter_var($_POST['content_id'], FILTER_VALIDATE_INT);
$targetType = strtolower(filter_var($_POST['content_type'], FILTER_SANITIZE_STRING));
$action = filter_var($_POST['action'], FILTER_SANITIZE_STRING);

if (!in_array($targetType, ['drawing', 'comment']) || !in_array($action, ['hide', 'show'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz parametreler.']);
    exit;
}

$db = getDbConnection();

if (!$targetId) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Tarama sonuçları boş. Geçersiz {$targetType} id'si verilmiştir."]);
    exit;
}

$tableName = ($targetType === 'drawing') ? 'drawings' : 'comments';
$stmt = $db->prepare("UPDATE {$tableName} SET is_visible = :is_visible WHERE id = :id");
$stmt->bindParam(':is_visible', $action === 'show' ? 1 : 0, PDO::PARAM_INT);
$stmt->bindParam(':id', $targetId, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => "{$targetType} başarıyla güncellendi."]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Tarama sonuçları boş. Geçersiz {$targetType} id'si verilmiştir."]);
}

function getDbConnection() {
    static $db = null;
    if (!$db) {
        try {
            $db = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        } catch (PDOException $e) {
            // Log the error and throw an exception for proper handling elsewhere
            error_log('Database connection error: ' . $e->getMessage());
            throw new Exception('Database connection failed.');
        }
    }
    return $db;
}