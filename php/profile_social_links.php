<?php
// profile_social_links.php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}

$action = $_POST['action'] ?? '';
$platform_id = $_POST['platform_id'] ?? null;
$profile_url = $_POST['profile_url'] ?? '';

try {
    $db = getDbConnection();

    if ($action === 'add') {
        // Platform aktif mi kontrol et
        $stmt = $db->prepare("SELECT url_regex FROM social_media_platforms WHERE id = ? AND is_active = 1");
        $stmt->execute([$platform_id]);
        $platform = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$platform) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz platform.']);
            exit;
        }

        // URL doğrulama
        if (!empty($platform['url_regex']) && !preg_match('/' . $platform['url_regex'] . '/', $profile_url)) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz URL formatı. Bu platform için doğru URL girin.']);
            exit;
        }

        // Bağlantıyı ekle
        $stmt = $db->prepare("INSERT INTO user_social_links (user_id, platform_id, profile_url) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $platform_id, $profile_url]);

        echo json_encode(['success' => true, 'message' => 'Sosyal medya bağlantısı eklendi.']);

    } elseif ($action === 'remove') {
        $stmt = $db->prepare("DELETE FROM user_social_links WHERE user_id = ? AND platform_id = ?");
        $stmt->execute([$_SESSION['user_id'], $platform_id]);

        echo json_encode(['success' => true, 'message' => 'Sosyal medya bağlantısı kaldırıldı.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
    }

} catch (Exception $e) {
    error_log("Sosyal medya bağlantı hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'İşlem başarısız: ' . $e->getMessage()]);
}
?>
