<?php
// Auth.php - PROFİL FOTOĞRAFI ENTEGRASYONLU
require_once 'config.php';
require_once 'User.php';
require_once 'functions.php';

class Auth {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register($username, $email, $password) {
        // Gelişmiş validasyon
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception('Tüm alanlar zorunludur.');
        }

        if (strlen($username) < 3 || strlen($username) > 20) {
            throw new Exception('Kullanıcı adı 3-20 karakter arasında olmalıdır.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Geçersiz e-posta formatı.');
        }

        if (strlen($password) < 6) {
            throw new Exception('Şifre en az 6 karakter olmalıdır.');
        }

        // Kullanıcı adı ve e-posta kontrolü
        if ($this->userModel->findByUsername($username)) {
            throw new Exception('Bu kullanıcı adı zaten kullanılıyor.');
        }

        if ($this->userModel->findByEmail($email)) {
            throw new Exception('Bu e-posta adresi zaten kayıtlı.');
        }

        // Kullanıcı oluştur (profil fotoğrafı otomatik Gravatar'dan alınacak)
        if ($this->userModel->create($username, $email, $password)) {
            // Otomatik giriş yap
            $user = $this->userModel->findByUsername($username);
            if ($user) {
                $this->loginUser($user);
                return true;
            }
        }

        throw new Exception('Kayıt işlemi başarısız.');
    }

    public function login($username, $password) {
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            throw new Exception('Kullanıcı adı veya şifre hatalı.');
        }

        if ($user['is_banned']) {
            throw new Exception('Hesabınız yasaklanmıştır.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Kullanıcı adı veya şifre hatalı.');
        }

        $this->loginUser($user);
        return true;
    }

    public function loginWithGoogle($googleId, $email, $name, $picture) {
        try {
            $user = $this->userModel->createOrUpdateFromGoogle($googleId, $email, $name, $picture);

            if ($user) {
                if ($user['is_banned']) {
                    throw new Exception('Hesabınız yasaklanmıştır.');
                }

                $this->loginUser($user);
                return true;
            }

            throw new Exception('Kullanıcı oluşturulamadı veya güncellenemedi.');

        } catch (Exception $e) {
            error_log("Google login hatası: " . $e->getMessage());
            throw $e;
        }
    }

    public function handleGoogleAuth($googleId, $email, $name, $picture) {
        // 1. Önce Google ID ile kullanıcı ara
        $user = $this->userModel->findByGoogleId($googleId);

        if ($user) {
            // Google ID ile kullanıcı bulundu -> Giriş yap
            if ($user['is_banned']) {
                throw new Exception('Hesabınız yasaklanmıştır.');
            }
            $this->loginUser($user);
            return 'login';
        }

        // 2. Google ID yoksa, email ile ara
        $userByEmail = $this->userModel->findByEmail($email);

        if ($userByEmail) {
            // Email ile kullanıcı bulundu -> Google hesabını ilişkilendir
            $this->userModel->linkGoogleAccount($userByEmail['id'], $googleId);

            // Profil fotoğrafını Google'dan güncelle
            $this->userModel->updateProfilePictureFromGoogle($userByEmail['id'], $picture);

            $this->loginUser($userByEmail);
            return 'linked';
        }

        // 3. Hiçbiri yoksa -> Yeni kayıt oluştur
        return $this->registerWithGoogle($googleId, $email, $name, $picture);
    }

    private function registerWithGoogle($googleId, $email, $name, $picture) {
        // Otomatik username oluştur (email'in kullanıcı adı kısmı)
        $username = strtolower(explode('@', $email)[0]);
        $username = preg_replace('/[^a-z0-9_]/', '', $username);
        if (strlen($username) < 3) {
            $username = 'user' . time();
        }

        // Username benzersiz mi kontrol et
        $counter = 0;
        $originalUsername = $username;

        while ($this->userModel->findByUsername($username)) {
            $counter++;
            $username = $originalUsername . $counter;
        }

        // Google ile kullanıcı oluştur
        if ($this->userModel->createNewGoogleUser($googleId, $username, $email, $name, $picture)) {
            $user = $this->userModel->findByGoogleId($googleId);
            if ($user) {
                $this->loginUser($user);
                return 'registered';
            }
        }

        throw new Exception('Google ile kayıt işlemi başarısız.');
    }

    private function loginUser($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Son giriş zamanını güncelle (profil fotoğrafı da güncellenecek)
        $this->userModel->updateLastLogin($user['id']);
    }

    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function logout() {
        session_unset();
        session_destroy();
    }
}
?>
