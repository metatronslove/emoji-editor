<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}

$senderId = $_SESSION['user_id'];
$receiverId = $_POST['receiver_id'] ?? null;
$content = $_POST['content'] ?? null;
$messageType = $_POST['message_type'] ?? 'text';
$fileName = $_POST['file_name'] ?? null;
$fileData = $_POST['file_data'] ?? null; // Base64 encoded

if (!$receiverId || (!$content && !$fileData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametre.']);
    exit;
}

try {
    $db = getDbConnection();

    // Engelleme kontrolü
    $stmt = $db->prepare("SELECT 1 FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->execute([$receiverId, $senderId]);
    if ($stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcı sizi engellemiş.']);
        exit;
    }

    // Dosya boyutu kontrolü (2MB limit)
    if ($fileData && strlen($fileData) > 2097152) {
        echo json_encode(['success' => false, 'message' => 'Dosya boyutu 2MB\'dan küçük olmalı.']);
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO private_messages
        (sender_id, receiver_id, content, message_type, file_data, file_name, file_size, mime_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $fileSize = $fileData ? strlen(base64_decode($fileData)) : null;
    $mimeType = $fileData ? ($_POST['mime_type'] ?? 'application/octet-stream') : null;

    $stmt->execute([
        $senderId,
        $receiverId,
        $content,
        $messageType,
        $fileData,
        $fileName,
        $fileSize,
        $mimeType
    ]);

    echo json_encode(['success' => true, 'message' => 'Mesaj gönderildi.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
