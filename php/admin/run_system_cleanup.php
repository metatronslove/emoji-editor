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

    $actions = [];

    // 1. Geçici oturumları temizle (1 günden eski)
    $stmt = $db->prepare("DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY))");
    $stmt->execute();
    $actions[] = "Geçici oturumlar temizlendi: " . $stmt->rowCount() . " kayıt";

    // 2. Eski cache dosyalarını temizle (örnek - gerçek cache sisteminize göre değiştirin)
    $actions[] = "Cache temizleme: Manuel olarak yapılmalı";

    // 3. Geçici upload dosyalarını kontrol et (1 günden eski)
    $actions[] = "Geçici dosyalar: Manuel olarak kontrol edilmeli";

    // 4. Optimize tablolar
    $tables = ['users', 'drawings', 'comments', 'likes', 'follows', 'admin_logs'];
    foreach ($tables as $table) {
        $db->query("OPTIMIZE TABLE $table");
    }
    $actions[] = "Tablolar optimize edildi: " . implode(', ', $tables);

    // Log kaydı
    $log_stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, details)
        VALUES (?, 'system_cleanup', ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        json_encode(['actions' => $actions])
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Sistem temizleme tamamlandı',
        'actions' => $actions
    ]);

} catch (Exception $e) {
    error_log("System cleanup error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Sistem temizleme sırasında hata oluştu: ' . $e->getMessage()
    ]);
}
