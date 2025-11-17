<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || !in_array($_SESSION['user_role'], ['admin', 'moderator'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

try {
    $db = getDbConnection();

    $stats = [];

    // TEMEL İSTATİSTİKLER
    $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_drawings'] = $db->query("SELECT COUNT(*) FROM drawings")->fetchColumn();
    $stats['total_comments'] = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $stats['total_likes'] = $db->query("SELECT COUNT(*) FROM likes")->fetchColumn();
    $stats['total_follows'] = $db->query("SELECT COUNT(*) FROM follows")->fetchColumn();

    // BUGÜNKÜ AKTİVİTE
    $stats['today_activity'] = $db->query("
        SELECT COUNT(*) FROM (
            SELECT id FROM drawings WHERE DATE(created_at) = CURDATE()
            UNION ALL
            SELECT id FROM comments WHERE DATE(created_at) = CURDATE()
            UNION ALL
            SELECT id FROM likes WHERE DATE(created_at) = CURDATE()
        ) AS activity
    ")->fetchColumn();

    // AKTİF KULLANICILAR (son 15 dakika)
    $stats['active_users'] = $db->query("
        SELECT COUNT(*) FROM users
        WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ")->fetchColumn();

    // YENİ KAYITLAR (bugün)
    $stats['new_users_today'] = $db->query("
        SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()
    ")->fetchColumn();

    // SON 7 GÜN AKTİVİTE
    $activityData = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM (
            SELECT created_at FROM drawings
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            UNION ALL
            SELECT created_at FROM comments
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            UNION ALL
            SELECT created_at FROM likes
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ) AS combined
        GROUP BY DATE(created_at)
        ORDER BY date
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stats['activity_labels'] = [];
    $stats['activity_data'] = [];

    foreach ($activityData as $row) {
        $stats['activity_labels'][] = date('d M', strtotime($row['date']));
        $stats['activity_data'][] = (int)$row['count'];
    }

    // SON 30 GÜN KULLANICI BÜYÜMESİ
    $userGrowth = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stats['user_growth_labels'] = [];
    $stats['user_growth_data'] = [];

    foreach ($userGrowth as $row) {
        $stats['user_growth_labels'][] = date('d M', strtotime($row['date']));
        $stats['user_growth_data'][] = (int)$row['count'];
    }

    // POPÜLER ÇİZİMLER
    $popularDrawings = $db->query("
        SELECT d.id, d.title, u.username, COUNT(l.id) as like_count
        FROM drawings d
        LEFT JOIN users u ON d.author_id = u.id
        LEFT JOIN likes l ON d.id = l.drawing_id
        GROUP BY d.id
        ORDER BY like_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stats['popular_drawings'] = $popularDrawings;

    // AKTİF KULLANICILAR
    $activeUsers = $db->query("
        SELECT username, last_activity, profile_views
        FROM users
        WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY last_activity DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stats['active_users_list'] = $activeUsers;

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İstatistikler yüklenirken hata oluştu: ' . $e->getMessage()
    ]);
}
