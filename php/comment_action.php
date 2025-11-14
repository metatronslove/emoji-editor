<?php
require_once 'config.php'; // Oturum, DB bağlantısı ve Composer yüklemesi
header('Content-Type: application/json');

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yönteme izin verilmiyor.']);
    exit;
}

// 1. Oturum Kontrolü (Yorum yapmak için giriş zorunludur)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yorum yapmak için oturum açmalısınız.']);
    exit;
}

$commenterId = $_SESSION['user_id'];
$db = getDbConnection(); // Veritabanı bağlantısı

// Gelen JSON verisini al
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$targetType = $data['target_type'] ?? null; // 'drawing' veya 'profile'
$targetId = $data['target_id'] ?? null;
$content = $data['content'] ?? null;

// Temel Veri Doğrulaması
if (!$targetType || !$targetId || empty($content) || !in_array($targetType, ['drawing', 'profile'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz yorum bilgisi.']);
    exit;
}

// Yorum İçeriği Güvenliği: Metni temizle
// XSS saldırılarını önlemek için tüm HTML etiketlerini kaldır.
// Eğer özel bir format (Markdown/BBCode) kullanılıyorsa, sanitizasyon burada yapılmalıdır.
$sanitizedContent = strip_tags(trim($content));

if (empty($sanitizedContent)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Yorum içeriği boş olamaz.']);
    exit;
}

try {
    // 2. Yorum Yapan Kullanıcı Kısıtlamalarını Kontrol Et
    $userStmt = $db->prepare("SELECT is_banned, comment_mute_until FROM users WHERE id = ?");
    $userStmt->execute([$commenterId]);
    $commenter = $userStmt->fetch();

    if (!$commenter) {
         http_response_code(401);
         echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı. Oturumu yeniden açın.']);
         exit;
    }

    // A. Genel Ban Kontrolü
    if ($commenter['is_banned']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Genel yasaklama nedeniyle yorum yapamazsınız.']);
        exit;
    }

    // B. Yorum Yasağı Süresi Kontrolü
    if ($commenter['comment_mute_until'] && strtotime($commenter['comment_mute_until']) > time()) {
        $muteTime = date('d.m.Y H:i', strtotime($commenter['comment_mute_until']));
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => "Yorum yapma yasağınız {$muteTime} tarihine kadar devam etmektedir."]);
        exit;
    }

    // 3. Hedef Kullanıcı Kontrolü ve Engelleme Kontrolleri
    $targetOwnerId = null;

    if ($_POST['target_type'] === 'profile' && isset($_POST['content'])) {
        $content = $_POST['content'];

        // URL'leri tespit et ve Open Graph verilerini topla
        $urls = extractUrls($content);
        $metadata = [];

        foreach ($urls as $url) {
            $ogData = fetchOpenGraphData($url);
            if ($ogData) {
                $metadata[] = $ogData;
            }
        }

        // Metadata'yı JSON olarak kaydet
        $metadataJson = !empty($metadata) ? json_encode($metadata) : null;

        $stmt = $db->prepare("
        INSERT INTO comments (commenter_id, content, target_type, target_id, metadata)
        VALUES (?, ?, 'profile', ?, ?)
        ");
        $stmt->execute([$user_id, $content, $target_id, $metadataJson]);
    }

    // D. Engelleme Kontrolleri (Sadece yorum yapan ve yorumun sahibi farklıysa geçerli)
    if ($targetOwnerId !== null && $targetOwnerId != $commenterId) {

        // Yorumun sahibi beni engelledi mi? (Target Owner -> Commenter)
        $isBlockedByOwner = $db->query("SELECT 1 FROM blocks WHERE blocker_id = {$targetOwnerId} AND blocked_id = {$commenterId}")->fetchColumn();
        if ($isBlockedByOwner) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bu kullanıcı tarafından engellendiniz. Yorum yapamazsınız.']);
            exit;
        }

        // Ben yorumun sahibini engelledim mi? (Commenter -> Target Owner)
        $didBlockOwner = $db->query("SELECT 1 FROM blocks WHERE blocker_id = {$commenterId} AND blocked_id = {$targetOwnerId}")->fetchColumn();
        if ($didBlockOwner) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Yorum yapmak istediğiniz kullanıcıyı engellediniz.']);
            exit;
        }
    }

    // 4. Veritabanına Kayıt
    $stmt = $db->prepare("INSERT INTO comments (commenter_id, target_id, target_type, content, is_visible) VALUES (:commenter_id, :target_id, :target_type, :content, :is_visible)");

    $success = $stmt->execute([
        ':commenter_id' => $commenterId,
        ':target_id' => $targetId,
        ':target_type' => $targetType,
        ':content' => $sanitizedContent,
        ':is_visible' => 1
    ]);

    if ($success) {
        http_response_code(201); // Created
        echo json_encode([
            'success' => true,
            'message' => 'Yorum başarıyla gönderildi.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Yorum kayıt hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.'
    ]);
}
?>
