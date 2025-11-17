<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die("Yetkisiz Erişim");
}

try {
    $db = getDbConnection();

    // Rütbe ayarlarını al
    $settings_stmt = $db->query("
        SELECT setting_key, setting_value
        FROM settings
        WHERE setting_key LIKE 'rank_%'
    ");
    $settings_data = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $settings = [
        'comment_points' => floatval($settings_data['rank_comment_points'] ?? 1),
        'drawing_points' => floatval($settings_data['rank_drawing_points'] ?? 2),
        'follower_points' => floatval($settings_data['rank_follower_points'] ?? 0.5),
        'upvote_points' => floatval($settings_data['rank_upvote_points'] ?? 0.2)
    ];

    // Kullanıcı istatistiklerini ve rütbelerini getir
    $stmt = $db->query("
        SELECT
            u.id,
            u.username,
            u.rank,
            u.created_at,
            COUNT(DISTINCT d.id) as drawing_count,
            COUNT(DISTINCT c.id) as comment_count,
            COUNT(DISTINCT f1.id) as follower_count,
            COUNT(DISTINCT l.id) as upvote_count,
            (COUNT(DISTINCT d.id) * {$settings['drawing_points']}) as drawing_points,
            (COUNT(DISTINCT c.id) * {$settings['comment_points']}) as comment_points,
            (COUNT(DISTINCT f1.id) * {$settings['follower_points']}) as follower_points,
            (COUNT(DISTINCT l.id) * {$settings['upvote_points']}) as upvote_points,
            (
                (COUNT(DISTINCT d.id) * {$settings['drawing_points']}) +
                (COUNT(DISTINCT c.id) * {$settings['comment_points']}) +
                (COUNT(DISTINCT f1.id) * {$settings['follower_points']}) +
                (COUNT(DISTINCT l.id) * {$settings['upvote_points']})
            ) as total_points
        FROM users u
        LEFT JOIN drawings d ON u.id = d.author_id
        LEFT JOIN comments c ON u.id = c.author_id
        LEFT JOIN follows f1 ON u.id = f1.following_id
        LEFT JOIN likes l ON d.id = l.drawing_id
        WHERE u.is_banned = 0
        GROUP BY u.id
        ORDER BY total_points DESC, u.username
    ");

    $rank_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CSV header
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rank_data_export_' . date('Y-m-d_H-i-s') . '.csv"');

    // Output CSV
    $output = fopen('php://output', 'w');

    // BOM for UTF-8
    fputs($output, "\xEF\xBB\xBF");

    // Önce ayarları yaz
    fputcsv($output, ['RÜTBE AYARLARI']);
    fputcsv($output, ['Çizim Başına Puan:', $settings['drawing_points']]);
    fputcsv($output, ['Yorum Başına Puan:', $settings['comment_points']]);
    fputcsv($output, ['Takipçi Başına Puan:', $settings['follower_points']]);
    fputcsv($output, ['Beğeni Başına Puan:', $settings['upvote_points']]);
    fputcsv($output, []); // Boş satır

    // CSV header row
    fputcsv($output, [
        'Sıra',
        'Kullanıcı ID',
        'Kullanıcı Adı',
        'Rütbe',
        'Toplam Puan',
        'Çizim Sayısı',
        'Çizim Puanı',
        'Yorum Sayısı',
        'Yorum Puanı',
        'Takipçi Sayısı',
        'Takipçi Puanı',
        'Beğeni Sayısı',
        'Beğeni Puanı',
        'Kayıt Tarihi'
    ]);

    // Data rows
    $rank = 1;
    foreach ($rank_data as $user) {
        fputcsv($output, [
            $rank++,
            $user['id'],
            $user['username'],
            str_repeat('⭐', $user['rank']),
            number_format($user['total_points'], 2),
            $user['drawing_count'],
            number_format($user['drawing_points'], 2),
            $user['comment_count'],
            number_format($user['comment_points'], 2),
            $user['follower_count'],
            number_format($user['follower_points'], 2),
            $user['upvote_count'],
            number_format($user['upvote_points'], 2),
            date('d.m.Y', strtotime($user['created_at']))
        ]);
    }

    fclose($output);

    // Log kaydı
    $log_stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, details)
        VALUES (?, 'export_rank_data', ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        json_encode(['user_count' => count($rank_data), 'settings' => $settings])
    ]);

    exit;

} catch (Exception $e) {
    error_log("Export rank data error: " . $e->getMessage());
    die("Rütbe verileri dışa aktarılırken hata oluştu: " . $e->getMessage());
}
