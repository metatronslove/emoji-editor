<?php
// google_callback.php
// Google OAuth'tan dönen isteği işler ve kullanıcıyı oturum açar.

require_once 'config.php';
require_once 'Auth.php'; // Artık Auth modelini kullanıyoruz
require_once 'vendor/autoload.php'; // Google Client Library'nin yüklü olduğu varsayılmıştır.

$auth = new Auth();
$error_message = '';

try {
    // Google Client Objesini Oluştur
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);

    // 1. Geri Dönen Kodu Kontrol Et ve Access Token'ı Al
    if (!isset($_GET['code'])) {
        throw new Exception("Google'dan yetkilendirme kodu gelmedi.");
    }

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        throw new Exception("Token alma hatası: " . $token['error_description']);
    }

    $client->setAccessToken($token);
    $_SESSION['google_access_token'] = $token;

    // 2. Kullanıcı Profil Verilerini Çek
    $google_oauth = new Google_Service_Oauth2($client);
    $google_user_info = $google_oauth->userinfo->get();

    $googleId = $google_user_info->id;
    $email = $google_user_info->email;
    $name = $google_user_info->name;
    $picture = $google_user_info->picture;

    // 3. Auth Sınıfı ile Giriş Yap/Kaydol (Tüm DB mantığı artık Auth/User içinde)
    if ($auth->loginWithGoogle($googleId, $email, $name, $picture)) {
        // Giriş/Kayıt başarılı, ana sayfaya yönlendir
        header('Location: /?success=' . urlencode('Google ile giriş başarılı!'));
        exit;
    } else {
        // Bu hata, DB bağlantısı başarısız olursa veya kullanıcı banlıysa tetiklenir.
        $error_message = 'Google hesabı ile giriş başarısız. Hesabınız yasaklanmış olabilir veya sistem hatası.';
    }

} catch (Exception $e) {
    error_log("Google OAuth Hatası: " . $e->getMessage());
    $error_message = 'Giriş sırasında bir hata oluştu: ' . $e->getMessage();
}

// Hata durumunda kullanıcıyı ana sayfadaki login modalına yönlendir
header('Location: /#login_modal?error=' . urlencode($error_message));
exit;
