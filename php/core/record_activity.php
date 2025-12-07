<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/counter_manager.php';
require_once __DIR__ . '/../classes/Drawing.php';
require_once __DIR__ . '/../classes/Router.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['activity_type'])) {
    echo json_encode(['success' => false, 'message' => 'Aktivite türü gerekli.']);
    exit;
}

try {
    $db = getDbConnection();
    $userId = $_SESSION['user_id'];
    
    $activityType = $data['activity_type'];
    $targetType = $data['target_type'] ?? 'drawing';
    $targetId = $data['target_id'] ?? null;
    $activityData = $data['activity_data'] ?? [];
    
    // Aktivite verisini hazırla
    $activityData['timestamp'] = date('Y-m-d H:i:s');
    $activityData['user_id'] = $userId;
    
    // Aktiviteyi kaydet
    $stmt = $db->prepare("
        INSERT INTO user_activities (user_id, activity_type, target_type, target_id, activity_data)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $activityType,
        $targetType,
        $targetId,
        json_encode($activityData, JSON_UNESCAPED_UNICODE)
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Aktivite kaydedildi.',
        'activity_id' => $db->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("Aktivite kaydetme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Aktivite kaydedilemedi: ' . $e->getMessage()
    ]);
}
?>