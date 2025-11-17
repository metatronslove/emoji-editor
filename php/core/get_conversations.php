<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDbConnection();

    // Basitleştirilmiş sorgu
    $stmt = $db->prepare("
    SELECT DISTINCT
    CASE
    WHEN sender_id = ? THEN receiver_id
    ELSE sender_id
    END as other_user_id,
    u.username as other_username,
    u.profile_picture as other_user_picture
    FROM private_messages
    JOIN users u ON u.id = CASE
    WHEN sender_id = ? THEN receiver_id
    ELSE sender_id
    END
    WHERE sender_id = ? OR receiver_id = ?
    ORDER BY (SELECT MAX(created_at) FROM private_messages
    WHERE (sender_id = other_user_id AND receiver_id = ?)
    OR (sender_id = ? AND receiver_id = other_user_id)) DESC
    ");

    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Her konuşma için son mesaj ve okunmamış sayısını ekle
    foreach ($conversations as &$conv) {
        $otherUserId = $conv['other_user_id'];

        // Son mesajı getir
        $msgStmt = $db->prepare("
        SELECT content, created_at
        FROM private_messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at DESC LIMIT 1
        ");
        $msgStmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
        $lastMsg = $msgStmt->fetch(PDO::FETCH_ASSOC);

        $conv['last_message_content'] = $lastMsg['content'] ?? '';
        $conv['last_message_time'] = $lastMsg['created_at'] ?? '';

        // Okunmamış mesaj sayısı
        $unreadStmt = $db->prepare("
        SELECT COUNT(*) as unread_count
        FROM private_messages
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
        ");
        $unreadStmt->execute([$otherUserId, $userId]);
        $unread = $unreadStmt->fetch(PDO::FETCH_ASSOC);

        $conv['unread_count'] = $unread['unread_count'] ?? 0;
    }

    echo json_encode(['success' => true, 'conversations' => $conversations]);

} catch (PDOException $e) {
    error_log("Konuşmalar getirme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
}
?>
