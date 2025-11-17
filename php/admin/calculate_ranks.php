<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
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

    // Kullanıcı istatistiklerini hesapla
    $users_stmt = $db->query("
    SELECT
    u.id, u.username,
    COUNT(DISTINCT d.id) as drawing_count,
                             COUNT(DISTINCT c.id) as comment_count,
                             COUNT(DISTINCT f1.id) as follower_count,
                             COUNT(DISTINCT l.id) as upvote_count
                             FROM users u
                             LEFT JOIN drawings d ON u.id = d.author_id
                             LEFT JOIN comments c ON u.id = c.author_id
                             LEFT JOIN follows f1 ON u.id = f1.following_id
                             LEFT JOIN likes l ON d.id = l.drawing_id
                             WHERE u.is_banned = 0
                             GROUP BY u.id
                             ORDER BY u.username
                             ");
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Puanları hesapla ve sırala
    foreach ($users as &$user) {
        $user['drawing_points'] = $user['drawing_count'] * $settings['drawing_points'];
        $user['comment_points'] = $user['comment_count'] * $settings['comment_points'];
        $user['follower_points'] = $user['follower_count'] * $settings['follower_points'];
        $user['upvote_points'] = $user['upvote_count'] * $settings['upvote_points'];
        $user['total_points'] =
        $user['drawing_points'] +
        $user['comment_points'] +
        $user['follower_points'] +
        $user['upvote_points'];
    }

    // Toplam puana göre sırala
    usort($users, function($a, $b) {
        return $b['total_points'] <=> $a['total_points'];
    });

    // Rütbeleri ata (1-5 yıldız)
    foreach ($users as $index => &$user) {
        $rank = min(5, max(1, floor(($index / count($users)) * 5) + 1));
        $user['rank'] = $rank;

        // Kullanıcının rütbesini güncelle
        $update_stmt = $db->prepare("UPDATE users SET rank = ? WHERE id = ?");
        $update_stmt->execute([$rank, $user['id']]);
    }

    // Log kaydı
    $log_stmt = $db->prepare("
    INSERT INTO admin_logs (admin_id, action, details)
    VALUES (?, 'calculate_ranks', ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        json_encode(['user_count' => count($users), 'settings' => $settings])
    ]);

    echo json_encode([
        'success' => true,
        'users' => $users,
        'settings_used' => $settings,
        'message' => count($users) . ' kullanıcının rütbesi güncellendi'
    ]);

} catch (Exception $e) {
    error_log("Calculate ranks error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Rütbe hesaplanırken hata oluştu: ' . $e->getMessage()
    ]);
}
