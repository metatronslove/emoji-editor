<?php
require_once(__DIR__ . '/../config.php');
header('Content-Type: application/json');

// Sadece POST ve oturum açık isteği kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim veya yöntem.']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$db = getDbConnection();

// Gelen JSON verisini al
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$drawingId = $data['id'] ?? null;
$action = $data['action'] ?? null; // 'comment_toggle' veya 'visible_toggle'
$value = $data['value'] ?? null; // true/false (PHP'de 1/0'a dönüşecek)

// Temel Veri Doğrulaması
if (!$drawingId || !$action || !isset($value) || !in_array($action, ['comment_toggle', 'visible_toggle'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz ayar bilgisi.']);
    exit;
}

try {
    // 1. Kullanıcının çizimin sahibi olup olmadığını kontrol et
    $stmt = $db->prepare("SELECT user_id FROM drawings WHERE id = ?");
    $stmt->execute([$drawingId]);
    $drawing = $stmt->fetch();

    if (!$drawing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Çizim bulunamadı.']);
        exit;
    }

    if ($drawing['user_id'] != $currentUserId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu ayarı değiştirmeye yetkiniz yok.']);
        exit;
    }

    // 2. SQL UPDATE sorgusunu oluştur
    $columnToUpdate = '';

    if ($action === 'comment_toggle') {
        $columnToUpdate = 'comments_allowed';
    } elseif ($action === 'visible_toggle') {
        $columnToUpdate = 'is_visible';
    }

    // Değeri BOOLEAN'dan veritabanına uygun INT'e çevir (TRUE -> 1, FALSE -> 0)
    $dbValue = $value ? 1 : 0;

    $updateStmt = $db->prepare("UPDATE drawings SET {$columnToUpdate} = :value WHERE id = :id AND user_id = :user_id");
    $updateStmt->bindParam(':value', $dbValue, PDO::PARAM_INT);
    $updateStmt->bindParam(':id', $drawingId, PDO::PARAM_INT);
    $updateStmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $success = $updateStmt->execute();

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Ayarlar başarıyla güncellendi.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Veritabanı güncelleme hatası.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Çizim ayarı güncelleme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
?>
