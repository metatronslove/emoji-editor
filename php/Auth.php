<?php
// Auth.php - Geliştirilmiş versiyon
require_once 'User.php';

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

        // Kullanıcı oluştur
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
        $user = $this->userModel->createOrUpdateFromGoogle($googleId, $email, $name, $picture);

        if ($user) {
            if ($user['is_banned']) {
                throw new Exception('Hesabınız yasaklanmıştır.');
            }

            $this->loginUser($user);
            return true;
        }

        throw new Exception('Google ile giriş başarısız.');
    }

    private function loginUser($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Son giriş zamanını güncelle
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
