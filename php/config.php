<?php
// Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Composer autoload dosyasını dahil et
require_once 'vendor/autoload.php';

// Google API Ayarları (Geliştirici Konsolundan Alınanlar)
define('GOOGLE_CLIENT_ID', 'SİZİN_CLIENT_ID_BURAYA');
define('GOOGLE_CLIENT_SECRET', 'SİZİN_CLIENT_SECRET_BURAYA');
define('GOOGLE_REDIRECT_URI', 'http://localhost/google_callback.php'); // Veya sitenizin URL'si

// Veritabanı Ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'pixel_editor_db');
define('DB_USER', 'root');
define('DB_PASS', 'sifreniz');

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
