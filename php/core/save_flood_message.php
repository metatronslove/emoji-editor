<?php
// core/save_flood_message.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['set_id']) || empty($data['content'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik veri.']);
    exit;
}

try {
    $db = getDbConnection();
    $userId = $_SESSION['user_id'];
    $setId = (int)$data['set_id'];
    $content = trim($data['content']);
    
    // Set'in kullanıcıya ait olduğunu kontrol et
    $stmt = $db->prepare("SELECT id FROM flood_sets WHERE id = ? AND user_id = ?");
    $stmt->execute([$setId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz flood set.']);
        exit;
    }
    
    // Flood mesajını kaydet
    $stmt = $db->prepare("
        INSERT INTO flood_messages (set_id, content, char_count, emoji_cost, total_cost, order_index)
        VALUES (?, ?, ?, ?, ?, 
            COALESCE((SELECT MAX(order_index) + 1 FROM flood_messages WHERE set_id = ?), 0)
        )
    ");
    
    $stmt->execute([
        $setId,
        $content,
        $data['char_count'] ?? mb_strlen($content),
        $data['emoji_cost'] ?? 0,
        $data['total_cost'] ?? mb_strlen($content),
        $setId
    ]);
    
    $messageId = $db->lastInsertId();
    
    // Set'in mesaj sayısını ve güncelleme tarihini güncelle
    $stmt = $db->prepare("
        UPDATE flood_sets 
        SET message_count = message_count + 1, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$setId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Flood mesajı kaydedildi!',
        'message_id' => $messageId
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Kayıt başarısız: ' . $e->getMessage()
    ]);
}
?>