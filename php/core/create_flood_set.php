<?php
// core/create_flood_set.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

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
    
    // Set adının benzersiz olup olmadığını kontrol et
    $stmt = $db->prepare("SELECT id FROM flood_sets WHERE user_id = ? AND name = ?");
    $stmt->execute([$userId, $name]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu isimde zaten bir setiniz var.']);
        exit;
    }
    
    // Yeni set oluştur
    $stmt = $db->prepare("
        INSERT INTO flood_sets (user_id, name, description, is_public)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $name,
        $data['description'] ?? '',
        isset($data['is_public']) ? (bool)$data['is_public'] : true
    ]);
    
    $setId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Flood set\'i oluşturuldu!',
        'set_id' => $setId,
        'set_name' => $name
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Set oluşturulamadı: ' . $e->getMessage()
    ]);
}
?>