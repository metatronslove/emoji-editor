<?php
// Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Composer autoload dosyasını dahil et
require_once '../vendor/autoload.php';

// Google API Ayarları (Geliştirici Konsolundan Alınanlar)
define('GOOGLE_CLIENT_ID', 'SİZİN_CLIENT_ID_BURAYA');
define('GOOGLE_CLIENT_SECRET', 'SİZİN_CLIENT_SECRET_BURAYA');
define('GOOGLE_REDIRECT_URI', 'http://localhost/google_callback.php'); // Veya sitenizin URL'si

// Veritabanı Ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'pixel_editor_db');
define('DB_USER', 'root');
define('DB_PASS', 'sifreniz');

// Avatar Ayarları
define('GRAVATAR_URL', 'https://www.gravatar.com/avatar/');

// Rütbe Sistemi Sabitleri
define('RANK_SETTINGS', [
    'comment_points' => 1.0,
    'drawing_points' => 2.0,
    'follower_points' => 0.5,
    'upvote_points' => 0.2,
    'profile_comment_points' => 0.3
]);

/**
 * Veritabanı bağlantısını kurar.
 * @return PDO
 */
function getDbConnection() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (\PDOException $e) {
        // Hata ayıklama modunda göster, canlıda sadece logla
        exit("Veritabanı bağlantı hatası: " . $e->getMessage());
    }
}

// Otomatik aktivite güncelleme fonksiyonu
function updateUserActivity() {
    if (isset($_SESSION['user_id'])) {
        try {
            $db = getDbConnection();
            $stmt = $db->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log("Activity update error: " . $e->getMessage());
        }
    }
}

// Sayfa yüklendiğinde aktiviteyi güncelle
updateUserActivity();

// Online status manager'ı dahil et
require_once '../online_status_manager.php';

// Kullanıcı aktivitesini güncelle - HER SAYFA YÜKLENİŞİNDE
if (isset($_SESSION['user_id'])) {
    OnlineStatusManager::updateOnlineStatus($_SESSION['user_id']);
}

// config.php dosyasının sonuna ekleyin
require_once '../activity_logger.php';

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
