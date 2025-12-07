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

if (empty($data['message_id'])) {
    echo json_encode(['success' => false, 'message' => 'Mesaj ID gerekli.']);
    exit;
}

try {
    $db = getDbConnection();
    $userId = $_SESSION['user_id'];
    $messageId = (int)$data['message_id'];
    
    // Mesajın kullanıcıya ait olduğunu kontrol et
    $stmt = $db->prepare("
        SELECT fm.id 
        FROM flood_messages fm
        JOIN flood_sets fs ON fm.set_id = fs.id
        WHERE fm.id = ? AND fs.user_id = ?
    ");
    $stmt->execute([$messageId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu mesajı silme izniniz yok.']);
        exit;
    }
    
    // Mesajı sil
    $stmt = $db->prepare("DELETE FROM flood_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    
    // Set'in mesaj sayısını güncelle
    $stmt = $db->prepare("
        UPDATE flood_sets 
        SET message_count = message_count - 1, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = (SELECT set_id FROM flood_messages WHERE id = ?)
    ");
    $stmt->execute([$messageId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mesaj silindi!'
    ]);
    
} catch (Exception $e) {
    error_log("Flood mesaj silme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Mesaj silinemedi: ' . $e->getMessage()
    ]);
}
?>