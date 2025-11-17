<?php
// User.php - PROFİL FOTOĞRAFI DESTEKLİ
require_once 'DB.php';

class User {
    private $db;

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }

    public function findByUsername(string $username): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("findByUsername hatası: " . $e->getMessage());
            return null;
        }
    }

    public function findByEmail(string $email): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("findByEmail hatası: " . $e->getMessage());
            return null;
        }
    }

    public function findByGoogleId(string $googleId): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE google_id = ?");
            $stmt->execute([$googleId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("findByGoogleId hatası: " . $e->getMessage());
            return null;
        }
    }

    public function findById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("findById hatası: " . $e->getMessage());
            return null;
        }
    }
    /**
     * Normal kayıt - GRAVATAR DESTEKLİ
     */
    public function create(string $username, string $email, string $password): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Gravatar'dan boyutlandırılmış resim al
        $profile_picture = $this->getGravatar($email);

        try {
            $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, profile_picture, role, created_at)
            VALUES (?, ?, ?, ?, 'user', NOW())
            ");
            return $stmt->execute([$username, $email, $hash, $profile_picture]);
        } catch (PDOException $e) {
            error_log("Klasik kayıt hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Google kullanıcısı oluştur veya güncelle - PROFİL FOTOĞRAFI DESTEKLİ
     */
    public function createOrUpdateFromGoogle(string $googleId, string $email, string $name, string $picture): ?array {
        error_log("Google kullanıcı işlemi başladı: " . $googleId);

        try {
            // 1. Önce Google ID ile ara
            $user = $this->findByGoogleId($googleId);
            if ($user) {
                error_log("Google ID ile kullanıcı bulundu: " . $user['id']);
                // Profil fotoğrafını güncelle
                $this->updateProfilePictureFromGoogle($user['id'], $picture);
                $this->updateLastLogin($user['id']);
                return $user;
            }

            // 2. E-posta ile ara (hesap birleştirme)
            $userByEmail = $this->findByEmail($email);
            if ($userByEmail) {
                error_log("E-posta ile kullanıcı bulundu, Google ID ekleniyor: " . $userByEmail['id']);

                // Mevcut kullanıcıya Google ID ekle ve profil fotoğrafını güncelle
                $stmt = $this->db->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $success = $stmt->execute([$googleId, $userByEmail['id']]);

                if ($success) {
                    $this->updateProfilePictureFromGoogle($userByEmail['id'], $picture);
                    $this->updateLastLogin($userByEmail['id']);
                    $userByEmail['google_id'] = $googleId;
                    return $userByEmail;
                }
                return null;
            }

            // 3. Yeni kullanıcı oluştur
            error_log("Yeni Google kullanıcısı oluşturuluyor");
            return $this->createNewGoogleUser($googleId, $email, $name, $picture);

        } catch (PDOException $e) {
            error_log("createOrUpdateFromGoogle CRITICAL ERROR: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Yeni Google kullanıcısı oluştur - PROFİL FOTOĞRAFI DESTEKLİ
     */
    public function createNewGoogleUser(string $googleId, string $email, string $name, string $picture): ?array {
        try {
            // Benzersiz kullanıcı adı oluştur
            $username = $this->generateSimpleUsername($name, $email);

            // Google profil fotoğrafını işle
            $profile_picture = $this->processGooglePicture($picture);

            $stmt = $this->db->prepare("
            INSERT INTO users
            (username, email, password_hash, google_id, profile_picture, role, created_at)
            VALUES (?, ?, '', ?, ?, 'user', NOW())
            ");

            $success = $stmt->execute([$username, $email, $googleId, $profile_picture]);

            if ($success) {
                $newUserId = $this->db->lastInsertId();
                error_log("Yeni Google kullanıcısı oluşturuldu: " . $newUserId);
                $this->updateLastLogin($newUserId);
                return $this->findByGoogleId($googleId);
            }

            return null;

        } catch (PDOException $e) {
            error_log("createNewGoogleUser CRITICAL ERROR: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Google hesabını ilişkilendir
     */
    public function linkGoogleAccount(int $user_id, string $googleId): bool {
        try {
            $stmt = $this->db->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            return $stmt->execute([$googleId, $user_id]);
        } catch (PDOException $e) {
            error_log("linkGoogleAccount hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Basit kullanıcı adı oluştur
     */
    public function generateSimpleUsername(string $name, string $email): string {
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $name);
        $baseUsername = strtolower($baseUsername);

        if (empty($baseUsername) || strlen($baseUsername) < 3) {
            $baseUsername = strstr($email, '@', true);
            $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
        }

        if (empty($baseUsername)) {
            $baseUsername = 'user';
        }

        $baseUsername = substr($baseUsername, 0, 45);
        $username = $baseUsername;
        $counter = 0;

        while ($this->findByUsername($username) && $counter < 100) {
            $counter++;
            $username = $baseUsername . $counter;
            if (strlen($username) > 50) {
                $username = substr($baseUsername, 0, 40) . $counter;
            }
        }

        return $username;
    }

    /**
     * Son giriş zamanını güncelle ve profil fotoğrafını kontrol et
     */
    public function updateLastLogin(int $userId): bool {
        try {
            // Profil fotoğrafını güncelle (eğer default ise)
            $user = $this->findById($userId);
            if ($user && ($user['profile_picture'] === 'default.png' || empty($user['profile_picture']))) {
                $new_profile_picture = $this->getGravatar($user['email']);
                $this->updateProfilePicture($userId, $new_profile_picture);
            }

            $stmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("updateLastLogin hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gravatar'dan profil fotoğrafını al ve boyutlandır
     */
    public function getGravatar(string $email, int $size = 240): string {
        $hash = md5(strtolower(trim($email)));
        $gravatar_url = GRAVATAR_URL . $hash . '?s=' . $size . '&d=identicon';

        $image_data = @file_get_contents($gravatar_url);
        if ($image_data !== false) {
            // Gravatar resmini de boyutlandır
            $optimized_image = $this->resizeAndOptimizeImage($image_data);
            if ($optimized_image) {
                return $optimized_image;
            }
        }

        return 'default';
    }

    /**
     * Resmi 240x240 boyutuna küçült ve optimize et
     */
    public function resizeAndOptimizeImage(string $image_data): ?string {
        try {
            // Resmi bellekte oluştur
            $image = imagecreatefromstring($image_data);
            if (!$image) {
                return null;
            }

            // Mevcut boyutları al
            $original_width = imagesx($image);
            $original_height = imagesy($image);

            // Hedef boyut
            $target_size = 240;

            // En-boy oranını koruyarak yeniden boyutlandır
            $ratio = min($target_size / $original_width, $target_size / $original_height);
            $new_width = (int)($original_width * $ratio);
            $new_height = (int)($original_height * $ratio);

            // Yeni resim oluştur
            $resized_image = imagecreatetruecolor($new_width, $new_height);

            // Şeffaflığı koru (PNG için)
            imagealphablending($resized_image, false);
            imagesavealpha($resized_image, true);
            $transparent = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
            imagefilledrectangle($resized_image, 0, 0, $new_width, $new_height, $transparent);

            // Resmi yeniden boyutlandır
            imagecopyresampled(
                $resized_image, $image,
                0, 0, 0, 0,
                $new_width, $new_height, $original_width, $original_height
            );

            // Çıktıyı buffer'a yaz - JPEG formatında (daha küçük boyut)
            ob_start();
            imagejpeg($resized_image, null, 85); // %85 kalite
            $optimized_data = ob_get_clean();

            // Belleği temizle
            imagedestroy($image);
            imagedestroy($resized_image);

            // Base64 formatına çevir (data URL olmadan sadece base64)
            return base64_encode($optimized_data);

        } catch (Exception $e) {
            error_log("Resim boyutlandırma hatası: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Google profil fotoğrafını işle ve boyutlandır
     */
    public function processGooglePicture(string $google_picture_url): string {
        $image_data = @file_get_contents($google_picture_url);
        if ($image_data !== false) {
            // Resmi boyutlandır ve optimize et
            $optimized_image = $this->resizeAndOptimizeImage($image_data);
            if ($optimized_image) {
                return $optimized_image;
            }
        }

        // Fallback olarak Gravatar kullan
        return $this->getGravatar('');
    }

    /**
     * Google profil fotoğrafını güncelle
     */
    public function updateProfilePictureFromGoogle(int $user_id, string $picture_url): bool {
        $profile_picture = $this->processGooglePicture($picture_url);
        return $this->updateProfilePicture($user_id, $profile_picture);
    }

    /**
     * Profil fotoğrafını güncelle
     */
    public function updateProfilePicture(int $user_id, string $profile_picture): bool {
        try {
            $stmt = $this->db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            return $stmt->execute([$profile_picture, $user_id]);
        } catch (PDOException $e) {
            error_log("Profil fotoğrafı güncelleme hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resim verisinden MIME typeını al
     */
    public function getMimeTypeFromString(string $image_data): string {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $image_data);
        finfo_close($finfo);
        return $mime_type ?: 'image/jpeg';
    }
}
?>
