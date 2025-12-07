<?php
// core/get_flood_messages.php
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

$setId = $_GET['set_id'] ?? 0;

if (!$setId) {
    echo json_encode(['success' => false, 'message' => 'Set ID gereklidir.']);
    exit;
}

try {
    $db = getDbConnection();
    $userId = $_SESSION['user_id'];
    
    // Set'in kullanıcıya ait olduğunu kontrol et
    $stmt = $db->prepare("SELECT id FROM flood_sets WHERE id = ? AND user_id = ?");
    $stmt->execute([$setId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu set\'e erişim izniniz yok.']);
        exit;
    }
    
    // Set'in mesajlarını getir
    $stmt = $db->prepare("
        SELECT id, content, char_count, emoji_cost, total_cost, created_at
        FROM flood_messages 
        WHERE set_id = ?
        ORDER BY order_index ASC, created_at ASC
    ");
    
    $stmt->execute([$setId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Mesajlar getirilemedi: ' . $e->getMessage()
    ]);
}
?>