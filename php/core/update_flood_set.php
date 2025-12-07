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

if (empty($data['set_id'])) {
    echo json_encode(['success' => false, 'message' => 'Set ID gereklidir.']);
    exit;
}

try {
    $db = getDbConnection();
    $userId = $_SESSION['user_id'];
    $setId = (int)$data['set_id'];
    
    // Set'in kullanıcıya ait olduğunu kontrol et
    $stmt = $db->prepare("SELECT id FROM flood_sets WHERE id = ? AND user_id = ?");
    $stmt->execute([$setId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu set\'e erişim izniniz yok.']);
        exit;
    }
    
    // Güncellenecek alanları hazırla
    $updates = [];
    $params = [];
    
    if (isset($data['name'])) {
        $updates[] = "name = ?";
        $params[] = trim($data['name']);
    }
    
    if (isset($data['description'])) {
        $updates[] = "description = ?";
        $params[] = trim($data['description']);
    }
    
    if (isset($data['category'])) {
        $updates[] = "category = ?";
        $params[] = trim($data['category']);
    }
    
    if (isset($data['is_public'])) {
        $updates[] = "is_public = ?";
        $params[] = (bool)$data['is_public'];
    }
    
    if (isset($data['is_visible'])) {
        $updates[] = "is_visible = ?";
        $params[] = (bool)$data['is_visible'];
    }
    
    if (isset($data['comments_allowed'])) {
        $updates[] = "comments_allowed = ?";
        $params[] = (bool)$data['comments_allowed'];
    }
    
    if (isset($data['tags'])) {
        $updates[] = "tags = ?";
        $params[] = json_encode($data['tags']);
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'Güncellenecek alan yok.']);
        exit;
    }
    
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
    
    // Set'i güncelle
    $sql = "UPDATE flood_sets SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = $setId;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Aktivite kaydet
    $activityData = [
        'set_id' => $setId,
        'set_name' => $data['name'] ?? '',
        'updated_fields' => array_keys($data)
    ];
    
    $stmt = $db->prepare("
        INSERT INTO user_activities (user_id, activity_type, target_type, target_id, activity_data)
        VALUES (?, 'update', 'flood_set', ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $setId,
        json_encode($activityData, JSON_UNESCAPED_UNICODE)
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Flood set\'i güncellendi!'
    ]);
    
} catch (Exception $e) {
    error_log("Flood set güncelleme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Set güncellenemedi: ' . $e->getMessage()
    ]);
}
?>