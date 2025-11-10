<?php
// DB.php
// Veritabanı bağlantısını yöneten yardımcı sınıf (Utility)

// config.php dosyasındaki getDbConnection() fonksiyonuna erişmek için dahil et
require_once 'config.php';

class DB {
    private static $instance = null;
    private $pdo;

    /**
     * Singleton yapıcı metot: Yalnızca bir kez çalışır.
     */
    private function __construct() {
        // config.php'deki global fonksiyonu kullan
        $this->pdo = getDbConnection();
    }

    /**
     * Sınıfın tekil örneğini döndürür.
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DB();
        }
        return self::$instance;
    }

    /**
     * PDO bağlantı nesnesini diğer sınıflara erişim için döndürür.
     */
    public function getConnection() {
        return $this->pdo;
    }
}
