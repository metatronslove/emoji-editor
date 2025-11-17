<?php
// update_online_status.php - GÜNCELLENMİŞ
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Doğrudan online_status_manager fonksiyonlarını kullan
    require_once __DIR__ . '/../core/online_status_manager.php';

    // Kullanıcının çevrimiçi durumunu güncelle
    $result = OnlineStatusManager::updateOnlineStatus($userId);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Çevrimiçi durum güncellendi',
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception('Update failed');
    }

} catch (Exception $e) {
    error_log("Çevrimiçi durum güncelleme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Çevrimiçi durum güncellenirken hata oluştu',
        'error' => $e->getMessage()
    ]);
}
?>
