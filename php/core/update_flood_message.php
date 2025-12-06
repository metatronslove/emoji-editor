<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

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
        echo json_encode(['success' => false, 'message' => 'Bu mesaja erişim izniniz yok.']);
        exit;
    }
    
    // Güncellenecek alanları hazırla
    $updates = [];
    $params = [];
    
    if (isset($data['content'])) {
        $updates[] = "content = ?";
        $params[] = trim($data['content']);
    }
    
    if (isset($data['order_index'])) {
        $updates[] = "order_index = ?";
        $params[] = (int)$data['order_index'];
    }
    
    if (isset($data['is_public'])) {
        $updates[] = "is_public = ?";
        $params[] = (bool)$data['is_public'];
    }
    
    if (isset($data['char_count'])) {
        $updates[] = "char_count = ?";
        $params[] = (int)$data['char_count'];
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'Güncellenecek alan yok.']);
        exit;
    }
    
    // Mesajı güncelle
    $sql = "UPDATE flood_messages SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = $messageId;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Set'in güncelleme tarihini güncelle
    $stmt = $db->prepare("
        UPDATE flood_sets 
        SET updated_at = CURRENT_TIMESTAMP 
        WHERE id = (SELECT set_id FROM flood_messages WHERE id = ?)
    ");
    $stmt->execute([$messageId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mesaj güncellendi!'
    ]);
    
} catch (Exception $e) {
    error_log("Flood mesaj güncelleme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Mesaj güncellenemedi: ' . $e->getMessage()
    ]);
}
?>