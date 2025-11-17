<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die("Yetkisiz Erişim");
}

try {
    $db = getDbConnection();

    // Log verilerini getir
    $stmt = $db->query("
        SELECT
            l.*,
            u.username as admin_name,
            target_u.username as target_user_name
        FROM admin_logs l
        LEFT JOIN users u ON l.admin_id = u.id
        LEFT JOIN users target_u ON l.target_user_id = target_u.id
        ORDER BY l.created_at DESC
        LIMIT 1000
    ");

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CSV header
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="admin_logs_export_' . date('Y-m-d_H-i-s') . '.csv"');

    // Output CSV
    $output = fopen('php://output', 'w');

    // BOM for UTF-8
    fputs($output, "\xEF\xBB\xBF");

    // CSV header row
    fputcsv($output, [
        'ID',
        'Tarih',
        'Admin',
        'İşlem',
        'Hedef Kullanıcı',
        'Hedef İçerik ID',
        'İçerik Tipi',
        'Detaylar'
    ]);

    // Data rows
    foreach ($logs as $log) {
        $details = $log['details'] ? json_decode($log['details'], true) : [];
        $details_text = '';

        if ($details) {
            $details_parts = [];
            foreach ($details as $key => $value) {
                if (is_array($value)) {
                    $details_parts[] = "$key: " . json_encode($value);
                } else {
                    $details_parts[] = "$key: $value";
                }
            }
            $details_text = implode('; ', $details_parts);
        }

        fputcsv($output, [
            $log['id'],
            date('d.m.Y H:i:s', strtotime($log['created_at'])),
            $log['admin_name'] ?: 'Sistem',
            $log['action'],
            $log['target_user_name'] ?: '-',
            $log['target_content_id'] ?: '-',
            $log['content_type'] ?: '-',
            $details_text
        ]);
    }

    fclose($output);

    // Log kaydı
    $log_stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, details)
        VALUES (?, 'export_logs', ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        json_encode(['log_count' => count($logs)])
    ]);

    exit;

} catch (Exception $e) {
    error_log("Export logs error: " . $e->getMessage());
    die("Loglar dışa aktarılırken hata oluştu: " . $e->getMessage());
}
