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

    $backup_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'created_by' => $_SESSION['username'],
        'tables' => []
    ];

    // Tüm tabloları yedekle
    $tables = ['users', 'drawings', 'comments', 'likes', 'follows', 'announcements', 'social_platforms', 'admin_logs', 'settings'];

    foreach ($tables as $table) {
        $stmt = $db->query("SELECT * FROM $table");
        $backup_data['tables'][$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Yedek dosyasını oluştur
    $backup_dir = '../backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }

    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.json';
    $filepath = $backup_dir . $filename;

    file_put_contents($filepath, json_encode($backup_data, JSON_PRETTY_PRINT));

    // Eski yedekleri temizle (7 günden eski)
    $files = glob($backup_dir . 'backup_*.json');
    $now = time();
    $days_old = 7;

    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) >= ($days_old * 24 * 60 * 60)) {
            unlink($file);
        }
    }

    // Log kaydı
    $log_stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, details)
        VALUES (?, 'create_backup', ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        json_encode(['filename' => $filename, 'table_count' => count($tables)])
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Yedekleme başarıyla oluşturuldu',
        'filename' => $filename,
        'download_url' => '../backups/' . $filename,
        'table_count' => count($tables),
        'timestamp' => $backup_data['timestamp']
    ]);

} catch (Exception $e) {
    error_log("Create backup error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Yedekleme oluşturulurken hata oluştu: ' . $e->getMessage()
    ]);
}
