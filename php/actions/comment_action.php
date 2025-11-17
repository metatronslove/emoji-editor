<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/activity_logger.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yönteme izin verilmiyor.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yorum yapmak için oturum açmalısınız.']);
    exit;
}

$commenterId = $_SESSION['user_id'];
$db = getDbConnection();

// Hem JSON hem de FormData desteği
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

$targetType = $input['target_type'] ?? null;
$targetId = $input['target_id'] ?? null;
$content = $input['content'] ?? null;
$messageType = $input['message_type'] ?? 'text';
$fileName = $input['file_name'] ?? null;
$fileData = $input['file_data'] ?? null;
$mimeType = $input['mime_type'] ?? null;

// Temel doğrulama
if (!$targetType || !$targetId || (!$content && !$fileData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz yorum bilgisi.']);
    exit;
}

// Profil yorumları için özel kontroller
if ($targetType === 'profile') {
    // Profil sahibini bul
    $profileStmt = $db->prepare("SELECT id, username, privacy_mode FROM users WHERE id = ?");
    $profileStmt->execute([$targetId]);
    $profileUser = $profileStmt->fetch();

    if (!$profileUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Profil bulunamadı.']);
        exit;
    }

    $isProfileOwner = ($commenterId == $profileUser['id']);
    $isProfilePrivate = ($profileUser['privacy_mode'] === 'private');

    // Gizlilik kontrolleri
    if (!$isProfileOwner) {
        if ($isProfilePrivate) {
            // Gizli profil - sadece takipçiler yorum yapabilir
            $followStmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
            $followStmt->execute([$commenterId, $targetId]);
            $isFollowing = $followStmt->fetch();

            if (!$isFollowing) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bu gizli profilde sadece takipçiler yorum yapabilir.']);
                exit;
            }
        }

        // Engelleme kontrolü
        $blockStmt = $db->prepare("SELECT 1 FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
        $blockStmt->execute([$commenterId, $targetId, $targetId, $commenterId]);
        $isBlocked = $blockStmt->fetch();

        if ($isBlocked) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Engellenmiş kullanıcılar yorum yapamaz.']);
            exit;
        }
    }
}

// Dosya boyutu kontrolü (2MB)
if ($fileData && strlen($fileData) > 2097152) {
    echo json_encode(['success' => false, 'message' => 'Dosya boyutu 2MB\'dan küçük olmalı.']);
    exit;
}

try {
    // Kullanıcı kısıtlamalarını kontrol et
    $userStmt = $db->prepare("SELECT is_banned, comment_mute_until FROM users WHERE id = ?");
    $userStmt->execute([$commenterId]);
    $commenter = $userStmt->fetch();

    if (!$commenter) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
        exit;
    }

    if ($commenter['is_banned']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Genel yasaklama nedeniyle yorum yapamazsınız.']);
        exit;
    }

    if ($commenter['comment_mute_until'] && strtotime($commenter['comment_mute_until']) > time()) {
        $muteTime = date('d.m.Y H:i', strtotime($commenter['comment_mute_until']));
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => "Yorum yapma yasağınız {$muteTime} tarihine kadar devam etmektedir."]);
        exit;
    }

    // Yorumu kaydet
    $stmt = $db->prepare("
    INSERT INTO comments
    (commenter_id, target_type, target_id, content, message_type, file_data, file_name, mime_type, is_visible)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");

    $sanitizedContent = strip_tags(trim($content));

    $stmt->execute([
        $commenterId,
        $targetType,
        $targetId,
        $sanitizedContent,
        $messageType,
        $fileData,
        $fileName,
        $mimeType
    ]);

    $commentId = $db->lastInsertId();

    // Aktivite kaydı oluştur (profil mesajı için)
    if ($targetType === 'profile') {
        ActivityLogger::logMessageActivity(
            $commenterId,
            $targetId,
            $profileUser['username'],
            $sanitizedContent
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Mesaj başarıyla gönderildi.',
        'comment_id' => $commentId
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Yorum kayıt hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası.']);
}
?>
