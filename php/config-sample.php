<?php
// config.php - GÜNCELLENMİŞ

// Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_SITE_URL', 'https://flood.page.gd/');

// Composer autoload file
require_once __DIR__ . '/vendor/autoload.php';

// Google API settings
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI', '');
define('ABLY_API_KEY','');

// Database settings
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

// Gravatar settings
define('GRAVATAR_URL', 'https://www.gravatar.com/avatar/');

// Ranking system constants
define('RANK_SETTINGS', [
    'comment_points' => 1.0,
    'drawing_points' => 2.0,
    'follower_points' => 0.5,
    'upvote_points' => 0.2,
    'profile_comment_points' => 0.3
]);

/**
 * Connect to the database.
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
        exit("Veritabanı bağlantı hatası: " . $e->getMessage());
    }
}

// Online status manager'ı dahil et
require_once __DIR__ . '/core/online_status_manager.php';

// Kullanıcı aktivitesini güncelle - HER SAYFA YÜKLENİŞİNDE
if (isset($_SESSION['user_id'])) {
    OnlineStatusManager::updateOnlineStatus($_SESSION['user_id']);
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
