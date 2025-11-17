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

    $result = [
        'success' => true,
        'drawings' => [],
        'comments' => []
    ];

    // SON ÇİZİMLER
    $drawings_stmt = $db->query("
    SELECT
    d.id, d.title, d.content, d.is_visible, d.updated_at,
    u.username as author_name, u.id as author_id
    FROM drawings d
    LEFT JOIN users u ON d.author_id = u.id
    ORDER BY d.updated_at DESC
    LIMIT 20
    ");
    $result['drawings'] = $drawings_stmt->fetchAll(PDO::FETCH_ASSOC);

    // SON YORUMLAR
    $comments_stmt = $db->query("
    SELECT
    c.id, c.content, c.is_visible, c.created_at,
    u.username as author_name, u.id as author_id,
    COALESCE(d.title, 'Silinmiş Çizim') as drawing_title
    FROM comments c
    LEFT JOIN users u ON c.author_id = u.id
    LEFT JOIN drawings d ON c.drawing_id = d.id
    ORDER BY c.created_at DESC
    LIMIT 20
    ");
    $result['comments'] = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Fetch recent content error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İçerikler yüklenirken hata oluştu'
    ]);
}
