<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}

$userId = $_SESSION['user_id'];
$otherUserId = $_GET['other_user_id'] ?? null;

if (!$otherUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametre.']);
    exit;
}

try {
    $db = getDbConnection();

    // Mesajları getir
    $stmt = $db->prepare("
    SELECT pm.*, u.username as sender_username, u.profile_picture
    FROM private_messages pm
    JOIN users u ON pm.sender_id = u.id
    WHERE (pm.sender_id = ? AND pm.receiver_id = ?) OR (pm.sender_id = ? AND pm.receiver_id = ?)
    ORDER BY pm.created_at ASC
    ");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Okunmamış mesajları okundu olarak işaretle
    $stmt = $db->prepare("UPDATE private_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
    $stmt->execute([$userId, $otherUserId]);

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
