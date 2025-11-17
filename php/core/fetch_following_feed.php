<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$currentUserId = $_SESSION['user_id'] ?? null;
if (!$currentUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to view your following feed.']);
    exit;
}

// Update file paths for new location
$db = getDbConnection();

// GELİŞTİRİLMİŞ SORGU - author_id ve profil fotoğrafı dahil
$stmt = $db->prepare("SELECT d.id, d.content, d.first_row_length, d.width, d.updated_at, u.username AS author_username, u.profile_picture AS author_profile_picture, u.id AS author_id FROM drawings d INNER JOIN users u ON d.user_id = u.id WHERE d.user_id IN (SELECT following_id FROM follows WHERE follower_id = :current_user_id) AND d.user_id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = :current_user_id_block UNION SELECT blocker_id FROM blocks WHERE blocked_id = :current_user_id_blocked) AND d.is_visible = TRUE ORDER BY d.updated_at DESC LIMIT :limit");

$stmt->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
$stmt->bindParam(':current_user_id_block', $currentUserId, PDO::PARAM_INT);
$stmt->bindParam(':current_user_id_blocked', $currentUserId, PDO::PARAM_INT);
$stmt->bindParam(':limit', $LIMIT, PDO::PARAM_INT);

$stmt->execute();
$drawings = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'drawings' => $drawings,
    'message' => 'Following feed loaded successfully.'
]);

?>
