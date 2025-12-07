<?php
// core/delete_flood_set.php
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
    
    // Transaction başlat
    $db->beginTransaction();
    
    try {
        // Önce mesajları sil
        $stmt = $db->prepare("DELETE FROM flood_messages WHERE set_id = ?");
        $stmt->execute([$setId]);
        
        // Sonra set'i sil
        $stmt = $db->prepare("DELETE FROM flood_sets WHERE id = ?");
        $stmt->execute([$setId]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Flood set\'i ve tüm mesajları silindi.'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Silme işlemi başarısız: ' . $e->getMessage()
    ]);
}
?>