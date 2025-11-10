<?php
// admin/fetch_recent_content.php
require_once '../config.php';
header('Content-Type: application/json');

$db = getDbConnection();
$userRole = $_SESSION['user_role'] ?? 'user';
$currentUserId = $_SESSION['user_id'] ?? null;

// Yetki kontrolü (Admin veya Moderatör)
if (!$currentUserId || !in_array($userRole, ['admin', 'moderator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

try {
    // 1. Son Çizimleri Çek (Son 50)
    $stmtDrawings = $db->query("
        SELECT
            d.id,
            d.content,
            d.is_visible,
            d.updated_at,
            u.username AS author_name,
            'drawing' AS content_type
        FROM drawings d
        JOIN users u ON d.user_id = u.id
        ORDER BY d.updated_at DESC
        LIMIT 50
    ");
    $drawings = $stmtDrawings->fetchAll(PDO::FETCH_ASSOC);

    // 2. Son Yorumları Çek (Son 50)
    $stmtComments = $db->query("
        SELECT
            c.id,
            c.content,
            c.is_visible,
            c.created_at,
            u.username AS author_name,
            c.target_type,
            c.target_id,
            'comment' AS content_type
        FROM comments c
        JOIN users u ON c.commenter_id = u.id
        ORDER BY c.created_at DESC
        LIMIT 50
    ");
    $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'drawings' => $drawings,
        'comments' => $comments
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
