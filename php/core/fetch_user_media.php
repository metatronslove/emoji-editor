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

    // Kullanıcının gönderdiği medya mesajlarını getir (hem özel mesajlar hem pano mesajları)
    $stmt = $db->prepare("
    (SELECT file_name, file_data, mime_type, message_type, created_at
    FROM private_messages
    WHERE sender_id = ? AND file_data IS NOT NULL
    ORDER BY created_at DESC
    LIMIT 25)

    UNION

    (SELECT file_name, file_data, mime_type, message_type, created_at
    FROM comments
    WHERE commenter_id = ? AND file_data IS NOT NULL
    ORDER BY created_at DESC
    LIMIT 25)

    ORDER BY created_at DESC
    LIMIT 50
    ");
    $stmt->execute([$userId, $userId]);
    $media = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'media' => $media]);

} catch (PDOException $e) {
    error_log("Medya galerisi hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
}
?>
