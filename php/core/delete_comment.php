<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}

$userId = $_SESSION['user_id'];
$commentId = $_POST['comment_id'] ?? null;

if (!$commentId) {
    echo json_encode(['success' => false, 'message' => 'Yorum ID eksik.']);
    exit;
}

try {
    $db = getDbConnection();

    // Yorum bilgilerini al
    $stmt = $db->prepare("SELECT commenter_id, target_type, target_id FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();

    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı.']);
        exit;
    }

    // Yetki kontrolü
    $userStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    $isAdmin = ($user['role'] === 'admin');
    $isModerator = ($user['role'] === 'moderator');
    $isCommentOwner = ($comment['commenter_id'] == $userId);

    if (!$isAdmin && !$isModerator && !$isCommentOwner) {
        echo json_encode(['success' => false, 'message' => 'Bu yorumu silme yetkiniz yok.']);
        exit;
    }

    // Yorumu sil (soft delete)
    $updateStmt = $db->prepare("UPDATE comments SET is_visible = 0 WHERE id = ?");
    $updateStmt->execute([$commentId]);

    echo json_encode(['success' => true, 'message' => 'Yorum başarıyla silindi.']);

} catch (PDOException $e) {
    error_log("Yorum silme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
}
?>
