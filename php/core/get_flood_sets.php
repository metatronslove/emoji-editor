<?php
// core/get_flood_sets.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız.']);
    exit;
}

try {
    $db = getDbConnection();
    $userId = $_SESSION['user_id'];
    
    // Kullanıcının flood set'lerini getir
    $stmt = $db->prepare("
        SELECT fs.*, 
               COUNT(fm.id) as message_count,
               MAX(fm.created_at) as last_message_date
        FROM flood_sets fs
        LEFT JOIN flood_messages fm ON fs.id = fm.set_id
        WHERE fs.user_id = ?
        GROUP BY fs.id
        ORDER BY fs.updated_at DESC
    ");
    
    $stmt->execute([$userId]);
    $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'sets' => $sets
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veriler getirilemedi: ' . $e->getMessage()
    ]);
}
?>