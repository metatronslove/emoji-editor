<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Sadece adminler rütbe ayarlarını değiştirebilir.']);
    exit;
}

$comment_points = $_POST['comment_points'] ?? 1;
$drawing_points = $_POST['drawing_points'] ?? 2;
$follower_points = $_POST['follower_points'] ?? 0.5;
$upvote_points = $_POST['upvote_points'] ?? 0.2;

try {
    $db = getDbConnection();

    // Ayarları güncelle veya ekle
    $settings = [
        'comment_points' => $comment_points,
        'drawing_points' => $drawing_points,
        'follower_points' => $follower_points,
        'upvote_points' => $upvote_points
    ];

    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("
        INSERT INTO rank_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->execute([$key, $value, $value]);
    }

    echo json_encode(['success' => true, 'message' => 'Rütbe ayarları kaydedildi.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ayarlar kaydedilemedi: ' . $e->getMessage()]);
}
?>
