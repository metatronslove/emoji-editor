<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || !in_array($_SESSION['user_role'], ['admin', 'moderator'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    $db = getDbConnection();
    $searchTerm = "%$query%";
    $results = [];

    // Kullanıcıları ara
    $user_stmt = $db->prepare("
        SELECT id, username, email, 'user' as type
        FROM users
        WHERE username LIKE ? OR email LIKE ?
        LIMIT 5
    ");
    $user_stmt->execute([$searchTerm, $searchTerm]);

    while ($user = $user_stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => $user['id'],
            'title' => $user['username'],
            'subtitle' => $user['email'],
            'type' => 'user'
        ];
    }

    // Çizimleri ara
    $drawing_stmt = $db->prepare("
        SELECT d.id, d.title, u.username, 'drawing' as type
        FROM drawings d
        LEFT JOIN users u ON d.author_id = u.id
        WHERE d.title LIKE ? OR d.content LIKE ?
        LIMIT 5
    ");
    $drawing_stmt->execute([$searchTerm, $searchTerm]);

    while ($drawing = $drawing_stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => $drawing['id'],
            'title' => $drawing['title'],
            'subtitle' => 'Çizer: ' . $drawing['username'],
            'type' => 'drawing'
        ];
    }

    // Yorumları ara
    $comment_stmt = $db->prepare("
        SELECT c.id, LEFT(c.content, 50) as content, u.username, 'comment' as type
        FROM comments c
        LEFT JOIN users u ON c.author_id = u.id
        WHERE c.content LIKE ?
        LIMIT 5
    ");
    $comment_stmt->execute([$searchTerm]);

    while ($comment = $comment_stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => $comment['id'],
            'title' => $comment['content'] . '...',
            'subtitle' => 'Yorum: ' . $comment['username'],
            'type' => 'comment'
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $results
    ]);

} catch (Exception $e) {
    error_log("Quick search error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Arama sırasında hata oluştu'
    ]);
}
