<?php
// google_callback.php - PROFİL FOTOĞRAFI DESTEKLİ
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

// Hata ayıklama
error_reporting(E_ALL);
ini_set('display_errors', 1);

$auth = new Auth();
$error_message = '';
$success_message = '';

try {
    // 1. Temel kontroller
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        throw new Exception("Google Client Library bulunamadı.");
    }

    require_once __DIR__ . '/../vendor/autoload.php';

    // Config değerlerini kontrol et
    if (!defined('GOOGLE_CLIENT_ID') || empty(GOOGLE_CLIENT_ID)) {
        throw new Exception("Google Client ID ayarlanmamış.");
    }

    if (!defined('GOOGLE_CLIENT_SECRET') || empty(GOOGLE_CLIENT_SECRET)) {
        throw new Exception("Google Client Secret ayarlanmamış.");
    }

    // 2. Google Client Objesini Oluştur
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->addScope('email');
    $client->addScope('profile');

    // 3. Geri Dönen Kodu Kontrol Et
    if (!isset($_GET['code'])) {
        $error = $_GET['error'] ?? 'Bilinmeyen hata';
        throw new Exception("Google yetkilendirme hatası: " . $error);
    }

    // 4. Access Token'ı Al
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        throw new Exception("Token hatası: " . ($token['error_description'] ?? $token['error']));
    }

    $client->setAccessToken($token);

    // 5. Kullanıcı Profil Verilerini Çek
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();

    if (!$userInfo) {
        throw new Exception("Kullanıcı bilgileri alınamadı");
    }

    $googleId = $userInfo->getId();
    $email = $userInfo->getEmail();
    $name = $userInfo->getName();
    $picture = $userInfo->getPicture();

    // Gelen verileri logla
    error_log("Google User Info - ID: " . $googleId . ", Email: " . $email . ", Name: " . $name . ", Picture: " . $picture);

    if (empty($googleId)) {
        throw new Exception("Google ID alınamadı");
    }

    if (empty($email)) {
        throw new Exception("E-posta adresi alınamadı");
    }

    // 6. Auth Sınıfı ile Giriş Yap/Kaydol - PROFİL FOTOĞRAFI İLE
    error_log("Auth sınıfı çağrılıyor...");
    if ($auth->handleGoogleAuth($googleId, $email, $name, $picture)) {
        $success_message = 'Google ile giriş başarılı! Hoş geldiniz.';
        error_log("Google giriş başarılı");
    } else {
        throw new Exception("Giriş işlemi başarısız oldu");
    }

} catch (Exception $e) {
    error_log("Google OAuth Hatası: " . $e->getMessage());
    $error_message = $e->getMessage();
}

// Yönlendirme
$source = $_SESSION['oauth_source'] ?? 'login_modal';
unset($_SESSION['oauth_source']);

if (!empty($success_message)) {
    header('Location: /?success=' . urlencode($success_message));
    exit;
} else {
    header('Location: /#' . $source . '?error=' . urlencode($error_message));
    exit;
}
?>
