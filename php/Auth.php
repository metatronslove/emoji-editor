<?php
// Auth.php
// Kimlik doğrulama, oturum ve yetkilendirme kontrolünü yöneten sınıf
require_once 'User.php';
require_once 'config.php'; // session_start() burada çağrılır

class Auth {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Kullanıcıyı sisteme giriş yapar ve oturum değişkenlerini ayarlar.
     */
    public function login(string $username, string $password): bool {
        $user = $this->userModel->findByUsername($username);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Banlanmış mı kontrolü
            if ($user['is_banned']) {
                // Hata mesajı gösterilmeli
                return false;
            }

            // Giriş başarılı
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
        return false;
    }

    /**
     * Yeni kullanıcı kaydını gerçekleştirir.
     */
    public function register(string $username, string $email, string $password): bool {
        return $this->userModel->create($username, $email, $password);
    }

    /**
     * Kullanıcının oturumunu sonlandırır.
     */
    public function logout() {
        session_destroy();
    }

    /**
     * Kullanıcının oturum açıp açmadığını kontrol eder.
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    /**
     * Oturum açmış kullanıcının ID'sini döndürür.
     */
    public static function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
}
