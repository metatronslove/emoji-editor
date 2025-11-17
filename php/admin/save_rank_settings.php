<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek methodu']);
    exit;
}

try {
    $db = getDbConnection();

    $comment_points = floatval($_POST['comment_points'] ?? 1);
    $drawing_points = floatval($_POST['drawing_points'] ?? 2);
    $follower_points = floatval($_POST['follower_points'] ?? 0.5);
    $upvote_points = floatval($_POST['upvote_points'] ?? 0.2);

    // Ayarları kaydet (settings tablosuna)
    $stmt = $db->prepare("
    INSERT INTO settings (setting_key, setting_value)
    VALUES
    ('rank_comment_points', ?),
                         ('rank_drawing_points', ?),
                         ('rank_follower_points', ?),
                         ('rank_upvote_points', ?)
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute([$comment_points, $drawing_points, $follower_points, $upvote_points]);

    // Log kaydı
    $log_stmt = $db->prepare("
    INSERT INTO admin_logs (admin_id, action, details)
    VALUES (?, 'update_rank_settings', ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        json_encode([
            'comment_points' => $comment_points,
            'drawing_points' => $drawing_points,
            'follower_points' => $follower_points,
            'upvote_points' => $upvote_points
        ])
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Rütbe ayarları kaydedildi'
    ]);

} catch (Exception $e) {
    error_log("Save rank settings error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ayarlar kaydedilirken hata oluştu'
    ]);
}
