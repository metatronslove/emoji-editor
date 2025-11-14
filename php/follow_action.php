<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}

$followerId = $_SESSION['user_id'];
$followingId = (int) ($_POST['target_id'] ?? null);
$action = trim(strip_tags($_POST['action'] ?? null)); // 'follow' veya 'unfollow'

// YENİ KONTROL: Engelleme Kontrolü
// Takip eden ve edilen arasında herhangi bir engelleme var mı?
$isBlocked = $db->query("
SELECT 1 FROM blocks
WHERE
(blocker_id = {$followerId} AND blocked_id = {$followingId})
OR
(blocker_id = {$followingId} AND blocked_id = {$followerId})
")->fetchColumn();

if ($isBlocked) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Engelleme durumu nedeniyle takip/takip isteği yapılamaz.']);
    exit;
}

if (!$followingId || !in_array($action, ['follow', 'unfollow'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz parametre.']);
    exit;
}

try {
    $db = getDbConnection();
    // 1. Hedef kullanıcının gizlilik modunu çek
    $privacyMode = (int) $db->query("SELECT privacy_mode FROM users WHERE id = {$followingId}")->fetchColumn();

    if ($action === 'follow') {
        if ($privacyMode === 'private') {
            // Gizli profil: Takip isteği oluştur
            $db->prepare("INSERT INTO follow_requests (follower_id, following_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE requested_at = NOW()")
               ->execute([$followerId, $followingId]);
            $message = 'Takip isteği gönderildi. Onay bekleniyor.';
        } else {
            // Herkese açık profil: Direkt takip et
            $db->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE follower_id = follower_id")
               ->execute([$followerId, $followingId]);
            $message = 'Takip başarılı!';
        }
    } elseif ($action === 'unfollow') {
        // Takip veya bekleyen isteği sil
        $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?")->execute([$followerId, $followingId]);
        $db->prepare("DELETE FROM follow_requests WHERE follower_id = ? AND following_id = ?")->execute([$followerId, $followingId]);
        $message = 'Takip bırakıldı.';
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>