<?php
// admin/fetch_users.php
require_once '../config.php';
header('Content-Type: application/json');
session_start();

$userRole = $_SESSION['user_role'] ?? null;
if (!$userRole || !in_array($userRole, ['admin', 'moderator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$search = sanitizeInput($_GET['q'] ?? '');
$page = (int)($_GET['p'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT u.id, u.username, u.email, u.role, u.is_banned, u.comment_mute_until, u.created_at, u.profile_views, (SELECT COUNT(*) FROM drawings WHERE user_id = u.id) as drawing_count, (SELECT COUNT(*) FROM comments WHERE commenter_id = u.id) as comment_count, (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count FROM users u WHERE u.username LIKE ? OR u.email LIKE ? ORDER BY u.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$search . '%', $search . '%', $limit, $offset]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>