<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || !in_array($_SESSION['user_role'], ['admin', 'moderator'])) {
    header('HTTP/1.1 403 Forbidden');
    die("Yetkisiz Erişim");
}

try {
    $db = getDbConnection();

    // Kullanıcı verilerini getir
    $stmt = $db->query("
        SELECT
            u.id,
            u.username,
            u.email,
            u.role,
            u.is_banned,
            u.comment_mute_until,
            u.rank,
            u.profile_views,
            u.created_at,
            u.last_activity,
            COUNT(DISTINCT d.id) as drawing_count,
            COUNT(DISTINCT c.id) as comment_count,
            COUNT(DISTINCT f1.id) as follower_count,
            COUNT(DISTINCT f2.id) as following_count,
            COUNT(DISTINCT l.id) as like_count
        FROM users u
        LEFT JOIN drawings d ON u.id = d.author_id
        LEFT JOIN comments c ON u.id = c.author_id
        LEFT JOIN follows f1 ON u.id = f1.following_id
        LEFT JOIN follows f2 ON u.id = f2.follower_id
        LEFT JOIN likes l ON d.id = l.drawing_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CSV header
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');

    // Output CSV
    $output = fopen('php://output', 'w');

    // BOM for UTF-8
    fputs($output, "\xEF\xBB\xBF");

    // CSV header row
    fputcsv($output, [
        'ID',
        'Kullanıcı Adı',
        'E-posta',
        'Rol',
        'Yasaklı',
        'Susturma Bitiş',
        'Rütbe',
        'Profil Görüntüleme',
        'Çizim Sayısı',
        'Yorum Sayısı',
        'Takipçi Sayısı',
        'Takip Sayısı',
        'Beğeni Sayısı',
        'Kayıt Tarihi',
        'Son Aktivite',
        'Durum'
    ]);

    // Data rows
    foreach ($users as $user) {
        $is_banned = $user['is_banned'] ? 'Evet' : 'Hayır';
        $is_muted = $user['comment_mute_until'] && strtotime($user['comment_mute_until']) > time() ? 'Evet' : 'Hayır';
        $status = $user['is_banned'] ? 'Yasaklı' : ($is_muted ? 'Susturulmuş' : 'Aktif');

        fputcsv($output, [
            $user['id'],
            $user['username'],
            $user['email'],
            $user['role'],
            $is_banned,
            $user['comment_mute_until'] ? date('d.m.Y H:i', strtotime($user['comment_mute_until'])) : '-',
            $user['rank'],
            $user['profile_views'],
            $user['drawing_count'],
            $user['comment_count'],
            $user['follower_count'],
            $user['following_count'],
            $user['like_count'],
            date('d.m.Y H:i', strtotime($user['created_at'])),
            $user['last_activity'] ? date('d.m.Y H:i', strtotime($user['last_activity'])) : '-',
            $status
        ]);
    }

    fclose($output);

    // Log kaydı
    $log_stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, details)
        VALUES (?, 'export_users', ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        json_encode(['user_count' => count($users)])
    ]);

    exit;

} catch (Exception $e) {
    error_log("Export users error: " . $e->getMessage());
    die("Kullanıcılar dışa aktarılırken hata oluştu: " . $e->getMessage());
}
