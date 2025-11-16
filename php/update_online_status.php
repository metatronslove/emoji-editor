<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDbConnection();

    // Kullanıcının çevrimiçi durumunu güncelle
    $stmt = $db->prepare("UPDATE users SET is_online = TRUE, last_activity = NOW() WHERE id = ?");
    $stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'Çevrimiçi durum güncellendi']);

} catch (Exception $e) {
    error_log("Çevrimiçi durum güncelleme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Çevrimiçi durum güncellenirken hata oluştu']);
}
?>
