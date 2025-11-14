<?php
require_once 'config.php';
header('Content-Type: application/json');

$targetType = $_GET['type'] ?? null;
$targetId = $_GET['id'] ?? null;

if (!$targetType || !$targetId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler.']);
    exit;
}

try {
    $db = getDbConnection();

    $stmt = $db->prepare("SELECT c.content, c.created_at, u.username, u.profile_picture FROM comments c JOIN users u ON c.commenter_id = u.id WHERE c.target_type = ? AND c.target_id = ? ORDER BY c.created_at DESC");
    $stmt->execute([$targetType, $targetId]);
    $comments = $stmt->fetchAll();

    // PROFİL FOTOĞRAFLARINI DÜZENLE
    foreach ($comments as &$comment) {
        if (!empty($comment['profile_picture']) && $comment['profile_picture'] !== 'default.png') {
            $comment['profile_picture'] = 'data:image/jpeg;base64,' . $comment['profile_picture'];
        } else {
            $comment['profile_picture'] = '/images/default.png';
        }
    }

    echo json_encode(['success' => true, 'comments' => $comments]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>