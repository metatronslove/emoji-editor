<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

$messageId = $_GET['id'] ?? 0;

if (!$messageId) {
    echo json_encode(['success' => false, 'message' => 'Mesaj ID gerekli.']);
    exit;
}

try {
    $db = getDbConnection();
    
    // Mesajı getir
    $stmt = $db->prepare("
        SELECT fm.*, fs.name as set_name, fs.user_id as set_owner_id
        FROM flood_messages fm
        JOIN flood_sets fs ON fm.set_id = fs.id
        WHERE fm.id = ?
    ");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Mesaj bulunamadı.']);
        exit;
    }
    
    // Erişim kontrolü
    $currentUserId = $_SESSION['user_id'] ?? null;
    $isOwner = ($currentUserId == $message['set_owner_id']);
    $isPublic = true; // Burada flood_set'in public olup olmadığını kontrol et
    
    if (!$isOwner && !$isPublic) {
        echo json_encode(['success' => false, 'message' => 'Bu mesaja erişim izniniz yok.']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    error_log("Flood mesaj getirme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Mesaj getirilemedi: ' . $e->getMessage()
    ]);
}
?>