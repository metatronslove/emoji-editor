<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['set_id']) || empty($data['type'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik veri.']);
    exit;
}

try {
    $db = getDbConnection();
    
    $setId = (int)$data['set_id'];
    $type = $data['type']; // 'copy', 'view', 'like'
    
    // Hangi alanı artıracağımızı belirle
    $field = '';
    switch ($type) {
        case 'copy':
            $field = 'copy_count';
            break;
        case 'view':
            $field = 'views';
            break;
        case 'like':
            $field = 'likes';
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem türü.']);
            exit;
    }
    
    // Sayacı artır
    $stmt = $db->prepare("UPDATE flood_sets SET $field = $field + 1 WHERE id = ?");
    $stmt->execute([$setId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'İşlem başarılı.'
    ]);
    
} catch (Exception $e) {
    error_log("Sayaç artırma hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İşlem başarısız: ' . $e->getMessage()
    ]);
}
?>