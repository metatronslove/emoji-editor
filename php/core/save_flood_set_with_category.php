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

if (empty($data['name'])) {
    echo json_encode(['success' => false, 'message' => 'Set adı gereklidir.']);
    exit;
}

try {
    $db = getDbConnection();
    $userId = $_SESSION['user_id'];
    $name = trim($data['name']);
    $category = isset($data['category']) ? trim($data['category']) : 'genel';
    
    // Kategori geçerliliğini kontrol et
    $stmt = $db->prepare("SELECT slug FROM flood_set_categories WHERE slug = ?");
    $stmt->execute([$category]);
    if (!$stmt->fetch() && $category !== 'genel') {
        $category = 'genel'; // Geçersiz kategori için fallback
    }
    
    // Set adının benzersiz olup olmadığını kontrol et (aynı kullanıcı için)
    $stmt = $db->prepare("SELECT id FROM flood_sets WHERE user_id = ? AND name = ?");
    $stmt->execute([$userId, $name]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu isimde zaten bir setiniz var.']);
        exit;
    }
    
    // Yeni set oluştur
    $stmt = $db->prepare("
        INSERT INTO flood_sets (user_id, name, description, category, is_public, tags)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $name,
        $data['description'] ?? '',
        $category,
        isset($data['is_public']) ? (bool)$data['is_public'] : true,
        isset($data['tags']) ? json_encode($data['tags']) : null
    ]);
    
    $setId = $db->lastInsertId();
    
    // Kullanıcı aktivitesi kaydet
    $activityData = [
        'set_id' => $setId,
        'set_name' => $name,
        'category' => $category,
        'message_count' => 0
    ];
    
    $stmt = $db->prepare("
        INSERT INTO user_activities (user_id, activity_type, target_type, target_id, activity_data)
        VALUES (?, 'create', 'flood_set', ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $setId,
        json_encode($activityData, JSON_UNESCAPED_UNICODE)
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Flood set\'i oluşturuldu!',
        'set_id' => $setId,
        'set_name' => $name,
        'category' => $category
    ]);
    
} catch (Exception $e) {
    error_log("Flood set oluşturma hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Set oluşturulamadı: ' . $e->getMessage()
    ]);
}
?>