<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

try {
    $db = getDbConnection();

    $stmt = $db->query("
    SELECT setting_key, setting_value
    FROM settings
    WHERE setting_key LIKE 'rank_%'
    ");
    $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $settings = [
        'comment_points' => floatval($settings_data['rank_comment_points'] ?? 1),
        'drawing_points' => floatval($settings_data['rank_drawing_points'] ?? 2),
        'follower_points' => floatval($settings_data['rank_follower_points'] ?? 0.5),
        'upvote_points' => floatval($settings_data['rank_upvote_points'] ?? 0.2)
    ];

    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);

} catch (Exception $e) {
    error_log("Fetch rank settings error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ayarlar yüklenirken hata oluştu'
    ]);
}
