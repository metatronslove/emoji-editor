<?php
// login.php - GELİŞMİŞ HATA YÖNETİMLİ
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

try {
    // Google Client Library kontrolü
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        throw new Exception("Google Client Library yüklü değil. Composer ile yükleyin: composer require google/apiclient");
    }

    require_once __DIR__ . '/../vendor/autoload.php';

    // Config değerlerini kontrol et
    if (!defined('GOOGLE_CLIENT_ID') || empty(GOOGLE_CLIENT_ID)) {
        throw new Exception("GOOGLE_CLIENT_ID tanımlı değil veya boş");
    }

    if (!defined('GOOGLE_CLIENT_SECRET') || empty(GOOGLE_CLIENT_SECRET)) {
        throw new Exception("GOOGLE_CLIENT_SECRET tanımlı değil veya boş");
    }

    if (!defined('GOOGLE_REDIRECT_URI') || empty(GOOGLE_REDIRECT_URI)) {
        throw new Exception("GOOGLE_REDIRECT_URI tanımlı değil veya boş");
    }

    // Kaynak modal bilgisini session'a kaydet
    if (isset($_GET['source']) && in_array($_GET['source'], ['login_modal', 'register_modal'])) {
        $_SESSION['oauth_source'] = $_GET['source'];
    } else {
        $_SESSION['oauth_source'] = 'login_modal';
    }

    // Google Client Objesini Oluştur
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);

    // İzin kapsamları
    $client->addScope('email');
    $client->addScope('profile');

    // OAuth 2.0 ayarları
    $client->setAccessType('offline');
    $client->setPrompt('consent');

    // Doğrulama URL'si oluştur
    $authUrl = $client->createAuthUrl();

    // Tarayıcıyı Google'a yönlendir
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit;

} catch (Exception $e) {
    error_log("Google OAuth Başlatma Hatası: " . $e->getMessage());

    // Hata durumunda ana sayfaya yönlendir
    $source = $_SESSION['oauth_source'] ?? 'login_modal';
    header('Location: /#' . $source . '?error=' . urlencode('Google girişi başlatılamadı: ' . $e->getMessage()));
    exit;
}
?>
