<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

// Output buffering başlat
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    ob_end_flush();
    exit;
}

$action = $_POST['action'] ?? '';
$platform_id = $_POST['platform_id'] ?? null;
$profile_url = $_POST['profile_url'] ?? '';

try {
    $db = getDbConnection();

    if (!$db) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    // Perform input validation and sanitization
    $profile_url = filter_var($profile_url, FILTER_SANITIZE_URL);

    if ($action === 'add') {
        // Platform kontrolü
        $stmt = $db->prepare("SELECT id, name, url_regex FROM social_media_platforms WHERE id = ? AND is_active = 1");
        $stmt->execute([$platform_id]);
        $platform = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$platform) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz platform.']);
            ob_end_flush();
            exit;
        }

        // URL doğrulama
        if (!empty($platform['url_regex'])) {

            // 1. URL'yi ayrıştırarak sadece alan adını (host) al.
            // parse_url fonksiyonu, URL'deki 'http(s)://' ve yol (path) kısımlarını ayırır.
            $host = parse_url($profile_url, PHP_URL_HOST);

            // Eğer parse_url geçerli bir host döndürmezse, geçersiz format.
            if (empty($host)) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz URL formatı.']);
                ob_end_flush();
                exit;
            }

            // 2. 'www.' ön ekini kaldırarak ana domain'i kontrol etmeye hazırla.
            if (strpos($host, 'www.') === 0) {
                $host = substr($host, 4);
            }

            // 3. Regex desenini oluştur.
            // ^: Başlangıç (host'un başında olmalı)
            // $: Bitiş (host'un sonunda olmalı)
            // i: Büyük/küçük harf duyarsızlığı (örn. Instagram.com da geçerli olur)
            // () parantezleri, OR ( | ) operatörünün tamamına uygulanmasını sağlar.
            $full_regex = '/^(' . $platform['url_regex'] . ')$/i';

            // 4. Host (alan adı) ile regex'i kontrol et.
            if (!preg_match($full_regex, $host)) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz URL formatı. Lütfen ' . $platform['name'] . ' için doğru domaini girin.']);
                ob_end_flush();
                exit;
            }
        }

        // Mevcut kaydı kontrol et
        $checkStmt = $db->prepare("SELECT id FROM user_social_links WHERE user_id = ? AND platform_id = ?");
        $checkStmt->execute([$_SESSION['user_id'], $platform_id]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update işlemi
            $stmt = $db->prepare("UPDATE user_social_links SET profile_url = ? WHERE user_id = ? AND platform_id = ?");
            $result = $stmt->execute([$profile_url, $_SESSION['user_id'], $platform_id]);
            $message = $platform['name'] . ' bağlantısı güncellendi.';
        } else {
            // Insert işlemi
            $stmt = $db->prepare("INSERT INTO user_social_links (user_id, platform_id, profile_url) VALUES (?, ?, ?)");
            $result = $stmt->execute([$_SESSION['user_id'], $platform_id, $profile_url]);
            $message = $platform['name'] . ' bağlantısı eklendi.';
        }

        if ($result) {
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            throw new Exception("Veritabanı işlemi başarısız");
        }

    } elseif ($action === 'remove') {
        // Platform bilgisini al
        $stmt = $db->prepare("SELECT name FROM social_media_platforms WHERE id = ?");
        $stmt->execute([$platform_id]);
        $platform = $stmt->fetch(PDO::FETCH_ASSOC);
        $platform_name = $platform['name'] ?? 'Sosyal medya';

        $stmt = $db->prepare("DELETE FROM user_social_links WHERE user_id = ? AND platform_id = ?");
        $result = $stmt->execute([$_SESSION['user_id'], $platform_id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => $platform_name . ' bağlantısı kaldırıldı.']);
        } else {
            throw new Exception("Silme işlemi başarısız");
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
    }

} catch (Exception $e) {
    error_log("Sosyal medya bağlantı hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'İşlem başarısız: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
?>
