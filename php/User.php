<?php
// User.php
// Kullanıcı veritabanı işlemlerini (CRUD) yöneten Model sınıfı
require_once 'DB.php';

class User {
    private $db;

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }

    // Klasik Login için
    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * YENİ: Google ID ile kullanıcı bilgilerini çeker.
     */
    public function findByGoogleId(string $googleId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Klasik Register için
    public function create(string $username, string $email, string $password): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            return $stmt->execute([$username, $email, $hash]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * YENİ: Google verileriyle kullanıcı oluşturur veya günceller.
     */
    public function createOrUpdateFromGoogle(string $googleId, string $email, string $name, string $picture): ?array {
        // 1. Google ID ile kullanıcıyı kontrol et
        $user = $this->findByGoogleId($googleId);

        if ($user) {
            // Kullanıcı var: Sadece resim ve e-postayı güncelle
            $stmt = $this->db->prepare("UPDATE users SET email = ?, profile_picture = ? WHERE google_id = ?");
            $stmt->execute([$email, $picture, $googleId]);
            return $user;
        }

        // 2. E-posta ile kontrol et (Hesap bağlama)
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUserByEmail = $stmt->fetch(PDO::FETCH_ASSOC);

        try {
            if ($existingUserByEmail) {
                // Klasik hesabı Google ID ile güncelle (Hesapları birleştir)
                $stmt = $this->db->prepare("UPDATE users SET google_id = ?, profile_picture = ? WHERE id = ?");
                $stmt->execute([$googleId, $picture, $existingUserByEmail['id']]);

                $existingUserByEmail['google_id'] = $googleId;
                $existingUserByEmail['profile_picture'] = $picture;
                return $existingUserByEmail;

            } else {
                // 3. Tamamen yeni Google kullanıcısı kaydı
                $username = $name;
                // Benzersiz kullanıcı adı kontrolü (gerekiyorsa)
                $i = 0;
                $uniqueUsername = $username;
                while ($this->findByUsername($uniqueUsername)) {
                    $i++;
                    $uniqueUsername = $name . $i;
                }

                // password_hash NULL (Google hesabı), role 'user'
                $stmt = $this->db->prepare("INSERT INTO users (username, email, google_id, profile_picture, password_hash, role) VALUES (?, ?, ?, ?, NULL, 'user')");
                if ($stmt->execute([$uniqueUsername, $email, $googleId, $picture])) {
                    return $this->findByGoogleId($googleId);
                }
                return null;
            }
        } catch (PDOException $e) {
            error_log("Google Kayıt/Güncelleme Hatası: " . $e->getMessage());
            return null;
        }
    }
}
