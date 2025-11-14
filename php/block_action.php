<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}

$blockerId = $_SESSION['user_id']; // Engellemeyi yapan
$blockedId = $_POST['target_id'] ?? null; // Engellenen kişi
$action = $_POST['action'] ?? null; // 'block' veya 'unblock'

if (!$blockedId || !in_array($action, ['block', 'unblock']) || $blockerId == $blockedId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz parametre.']);
    exit;
}

try {
    $db = getDbConnection();
    $db->beginTransaction();

    if ($action === 'block') {
        // Engelleme: blocks tablosuna ekle
        $stmt = $db->prepare("INSERT INTO blocks (blocker_id, blocked_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE blocker_id = blocker_id");
        $stmt->execute([$blockerId, $blockedId]);
        $message = 'Kullanıcı başarıyla engellendi. Tüm etkileşimleriniz kesildi.';

        // Engelleme durumunda varsa karşılıklı takip ve takip isteği ilişkilerini de kes
        $db->prepare("DELETE FROM follows WHERE (follower_id = ? AND following_id = ?) OR (follower_id = ? AND following_id = ?)")
        ->execute([$blockerId, $blockedId, $blockedId, $blockerId]);

        $db->prepare("DELETE FROM follow_requests WHERE (follower_id = ? AND following_id = ?) OR (follower_id = ? AND following_id = ?)")
        ->execute([$blockerId, $blockedId, $blockedId, $blockerId]);


    } elseif ($action === 'unblock') {
        // Engellemeyi Kaldırma
        $db->prepare("DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?")->execute([$blockerId, $blockedId]);
        $message = 'Kullanıcının engeli başarıyla kaldırıldı.';
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
