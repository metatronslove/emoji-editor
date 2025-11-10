<?php
// User.php
// Kullanıcı veritabanı işlemlerini (CRUD) yöneten Model sınıfı
require_once 'DB.php';

class User {
    private $db;

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }

    /**
     * Kullanıcı adıyla kullanıcı bilgilerini çeker.
     */
    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * ID ile kullanıcı bilgilerini çeker.
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Yeni kullanıcı kaydı oluşturur.
     */
    public function create(string $username, string $email, string $password): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            return $stmt->execute([$username, $email, $hash]);
        } catch (PDOException $e) {
            // E-posta veya kullanıcı adı zaten var hatası
            // Gerçek uygulamada daha detaylı hata kodu kontrolü yapılmalıdır.
            return false;
        }
    }
}
