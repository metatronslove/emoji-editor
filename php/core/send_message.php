<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}

$senderId = $_SESSION['user_id'];

// FormData ve JSON desteği
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true);
    $receiverId = $input['receiver_id'] ?? null;
    $content = $input['content'] ?? null;
    $messageType = 'text';
    $fileData = null;
    $fileName = null;
    $mimeType = null;
} else {
    // FormData işleme
    $receiverId = $_POST['receiver_id'] ?? null;
    $content = $_POST['content'] ?? null;
    $messageType = 'text';
    $fileData = null;
    $fileName = null;
    $mimeType = null;

    // Dosya yükleme kontrolü
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $fileName = $file['name'];
        $mimeType = $file['type'];

        // Dosya boyutu kontrolü (2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Dosya boyutu 2MB\'dan küçük olmalı.']);
            exit;
        }

        // Dosya türü kontrolü
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'audio/mpeg', 'application/pdf'];
        if (!in_array($mimeType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Desteklenmeyen dosya türü.']);
            exit;
        }

        // Dosyayı base64'e çevir
        $fileData = base64_encode(file_get_contents($file['tmp_name']));
        $messageType = $this->getMessageTypeFromMime($mimeType);
    }
}

// Temel validasyon
if (!$receiverId || (!$content && !$fileData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametre.']);
    exit;
}

// Kendine mesaj kontrolü
if ($senderId == $receiverId) {
    echo json_encode(['success' => false, 'message' => 'Kendinize mesaj gönderemezsiniz.']);
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

    // Mesajı kaydet
    $stmt = $db->prepare("
    INSERT INTO private_messages
    (sender_id, receiver_id, content, message_type, file_data, file_name, mime_type, file_size)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $fileSize = $fileData ? strlen(base64_decode($fileData)) : null;

    $stmt->execute([
        $senderId,
        $receiverId,
        $content,
        $messageType,
        $fileData,
        $fileName,
        $mimeType,
        $fileSize
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Mesaj gönderildi.',
        'message_id' => $db->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Mesaj gönderme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}

function getMessageTypeFromMime($mimeType) {
    if (str_starts_with($mimeType, 'image/')) return 'image';
    if (str_starts_with($mimeType, 'video/')) return 'video';
    if (str_starts_with($mimeType, 'audio/')) return 'audio';
    return 'file';
}
?>
