<?php
// save_rank_settings.php - DÜZELTİLMİŞ
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only admins can change rank settings.']);
    exit;
}

try {
    $db = getDbConnection();

    // Gelen verileri al ve doğrula
    $comment_points = floatval($_POST['comment_points'] ?? 1);
    $drawing_points = floatval($_POST['drawing_points'] ?? 2);
    $follower_points = floatval($_POST['follower_points'] ?? 0.5);
    $upvote_points = floatval($_POST['upvote_points'] ?? 0.2);

    // Rank settings tablosunu kontrol et, yoksa oluştur
    $db->exec("
    CREATE TABLE IF NOT EXISTS rank_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
              setting_value DECIMAL(10,2) NOT NULL,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
    ");

    $db->beginTransaction();

    // Ayarları kaydet/güncelle
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

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Rank settings saved successfully!'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Rank settings error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error saving rank settings: ' . $e->getMessage()
    ]);
}
?>
