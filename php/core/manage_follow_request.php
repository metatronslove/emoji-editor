<?php
require_once __DIR__ . '/../config.php';
// Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Sadece POST isteği
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

$ownerId = $_SESSION['user_id']; // Oturum açan (profil sahibi)
$requesterId = $_POST['requester_id'] ?? null;
$action = $_POST['action'] ?? null; // 'approve' veya 'reject'

if (!$requesterId || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametre.']);
    exit;
}

try {
    $db = getDbConnection();
    $db->beginTransaction();

    // İstek var mı ve gerçekten benim profilime mi geldi?
    $requestExists = $db->query("SELECT 1 FROM follow_requests WHERE follower_id = {$requesterId} AND following_id = {$ownerId}")->fetchColumn();

    if (!$requestExists) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'İstek bulunamadı.']);
        exit;
    }

    if ($action === 'approve') {
        // 1. follows tablosuna ekle
        $db->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)")->execute([$requesterId, $ownerId]);
        $message = 'Takip isteği onaylandı.';
    } elseif ($action === 'reject') {
        // 2. follow_requests tablosunda 'rejected' olarak işaretle
        // Veya direkt sil, ancak reddetme logu tutmak daha iyidir.
        // Şimdilik sadece silerek ilerleyelim:
        $message = 'Takip isteği reddedildi.';
    }

    // 3. follow_requests tablosundan isteği sil
    $db->prepare("DELETE FROM follow_requests WHERE follower_id = ? AND following_id = ?")->execute([$requesterId, $ownerId]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'İşlem hatası: ' . $e->getMessage()]);
}
?>
