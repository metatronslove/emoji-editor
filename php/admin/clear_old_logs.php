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

    // 30 günden eski logları sil
    $stmt = $db->prepare("
        DELETE FROM admin_logs
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();

    $deleted_count = $stmt->rowCount();

    // Log kaydı
    $log_stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, details)
        VALUES (?, 'clear_old_logs', ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        json_encode(['deleted_count' => $deleted_count])
    ]);

    echo json_encode([
        'success' => true,
        'message' => "$deleted_count adet eski log temizlendi"
    ]);

} catch (Exception $e) {
    error_log("Clear old logs error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Loglar temizlenirken hata oluştu'
    ]);
}
