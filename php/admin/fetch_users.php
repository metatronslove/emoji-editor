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

    $search = $_GET['q'] ?? '';
    $page = $_GET['page'] ?? 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $whereClause = '';
    $params = [];

    if (!empty($search)) {
        $whereClause = "WHERE u.username LIKE ? OR u.email LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm];
    }

    // Kullanıcıları getir
    $sql = "
    SELECT
    u.id, u.username, u.email, u.role, u.is_banned,
    u.comment_mute_until, u.created_at,
    COUNT(DISTINCT d.id) as drawing_count,
    COUNT(DISTINCT c.id) as comment_count,
    COUNT(DISTINCT f1.id) as follower_count,
    u.last_activity,
    u.profile_views
    FROM users u
    LEFT JOIN drawings d ON u.id = d.author_id
    LEFT JOIN comments c ON u.id = c.author_id
    LEFT JOIN follows f1 ON u.id = f1.following_id
    $whereClause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Toplam kullanıcı sayısı
    $countSql = "SELECT COUNT(*) FROM users u $whereClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => $totalUsers,
        'page' => $page,
        'totalPages' => ceil($totalUsers / $limit)
    ]);

} catch (Exception $e) {
    error_log("Fetch users error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Kullanıcılar yüklenirken hata oluştu'
    ]);
}
