<?php
// get_user_social_links.php
require_once 'config.php';
require_once 'counter_manager.php';
require_once 'Auth.php';
require_once 'Drawing.php';
require_once 'functions.php';
require_once 'Router.php';
header('Content-Type: application/json');
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT usl.*, smp.name, smp.emoji FROM user_social_links AS usl JOIN social_media_platforms AS smp ON usl.platform_id = smp.id WHERE usl.user_id = ? AND smp.is_active = 1");
    $stmt->execute([$userId]);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'links' => $links]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bağlantılar yüklenemedi: ' . $e->getMessage()]);
}
?>
