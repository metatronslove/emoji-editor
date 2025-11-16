<?php
// fetch_rank_settings.php - YENİ DOSYA
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only admins can access rank settings.']);
    exit;
}

try {
    $db = getDbConnection();

    // Varsayılan değerler
    $default_settings = [
        'comment_points' => 1.0,
        'drawing_points' => 2.0,
        'follower_points' => 0.5,
        'upvote_points' => 0.2
    ];

    // Veritabanından ayarları çek
    $stmt = $db->query("SELECT setting_key, setting_value FROM rank_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Veritabanındaki ayarları kullan, yoksa varsayılanları kullan
    $settings = array_merge($default_settings, $db_settings);

    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);

} catch (Exception $e) {
    error_log("Fetch rank settings error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching rank settings',
        'settings' => $default_settings
    ]);
}
?>
