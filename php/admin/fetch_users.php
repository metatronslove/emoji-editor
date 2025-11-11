<?php
// admin/fetch_users.php
require_once '../config.php';
header('Content-Type: application/json');

$db = getDbConnection();
$userRole = $_SESSION['user_role'] ?? 'user';

if (!$userRole || !in_array($userRole, ['admin', 'moderator'])) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

$search = $_GET['q'] ?? '';
$page = (int)($_GET['p'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    $sql = "SELECT u.id, u.username, u.email, u.role, u.is_banned,
                   u.comment_mute_until, u.created_at, u.profile_views,
                   (SELECT COUNT(*) FROM drawings WHERE user_id = u.id) as drawing_count,
                   (SELECT COUNT(*) FROM comments WHERE commenter_id = u.id) as comment_count,
                   (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count
            FROM users u
            WHERE u.username LIKE :search OR u.email LIKE :search
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    $searchTerm = '%' . $search . '%';
    $stmt->bindParam(':search', $searchTerm);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
