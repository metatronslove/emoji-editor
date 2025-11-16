<?php
// calculate_ranks.php - YENİ DOSYA
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only admins can calculate ranks.']);
    exit;
}

try {
    $db = getDbConnection();

    // Rank ayarlarını al
    $stmt = $db->query("SELECT setting_key, setting_value FROM rank_settings");
    $rank_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $comment_points = floatval($rank_settings['comment_points'] ?? 1.0);
    $drawing_points = floatval($rank_settings['drawing_points'] ?? 2.0);
    $follower_points = floatval($rank_settings['follower_points'] ?? 0.5);
    $upvote_points = floatval($rank_settings['upvote_points'] ?? 0.2);

    // Tüm kullanıcıları ve istatistiklerini al
    $users_query = "
        SELECT
            u.id,
            u.username,
            u.created_at,
            COALESCE(d.drawing_count, 0) as drawing_count,
            COALESCE(c.comment_count, 0) as comment_count,
            COALESCE(f.follower_count, 0) as follower_count,
            COALESCE(uv.upvote_count, 0) as upvote_count
        FROM users u
        LEFT JOIN (
            SELECT author_id, COUNT(*) as drawing_count
            FROM drawings
            WHERE is_visible = 1
            GROUP BY author_id
        ) d ON u.id = d.author_id
        LEFT JOIN (
            SELECT author_id, COUNT(*) as comment_count
            FROM comments
            WHERE is_visible = 1
            GROUP BY author_id
        ) c ON u.id = c.author_id
        LEFT JOIN (
            SELECT following_id, COUNT(*) as follower_count
            FROM followers
            GROUP BY following_id
        ) f ON u.id = f.following_id
        LEFT JOIN (
            SELECT d.author_id, COUNT(*) as upvote_count
            FROM upvotes uv
            JOIN drawings d ON uv.drawing_id = d.id
            GROUP BY d.author_id
        ) uv ON u.id = uv.author_id
        WHERE u.is_banned = 0
        ORDER BY u.id
    ";

    $users_stmt = $db->query($users_query);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Puanları hesapla
    $ranked_users = [];
    foreach ($users as $user) {
        $total_points =
            ($user['comment_count'] * $comment_points) +
            ($user['drawing_count'] * $drawing_points) +
            ($user['follower_count'] * $follower_points) +
            ($user['upvote_count'] * $upvote_points);

        $ranked_users[] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'total_points' => round($total_points, 2),
            'comment_count' => $user['comment_count'],
            'drawing_count' => $user['drawing_count'],
            'follower_count' => $user['follower_count'],
            'upvote_count' => $user['upvote_count'],
            'comment_points' => round($user['comment_count'] * $comment_points, 2),
            'drawing_points' => round($user['drawing_count'] * $drawing_points, 2),
            'follower_points' => round($user['follower_count'] * $follower_points, 2),
            'upvote_points' => round($user['upvote_count'] * $upvote_points, 2)
        ];
    }

    // Puana göre sırala
    usort($ranked_users, function($a, $b) {
        return $b['total_points'] <=> $a['total_points'];
    });

    // Sıralama ekle
    foreach ($ranked_users as $index => &$user) {
        $user['rank'] = $index + 1;
    }

    echo json_encode([
        'success' => true,
        'users' => $ranked_users,
        'settings_used' => [
            'comment_points' => $comment_points,
            'drawing_points' => $drawing_points,
            'follower_points' => $follower_points,
            'upvote_points' => $upvote_points
        ]
    ]);

} catch (Exception $e) {
    error_log("Calculate ranks error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error calculating ranks: ' . $e->getMessage()
    ]);
}
?>
