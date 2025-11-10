<?php
require_once 'config.php';

// Google Client Objesini Oluştur
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

// 1. Google'dan gelen kodu al
if (isset($_GET['code'])) {
    try {
        // Access Token'ı al
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        // Access Token'ı oturumda sakla
        $_SESSION['google_access_token'] = $token;

        // 2. Kullanıcı profil verilerini al
        $oauth2 = new Google_Service_Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        $google_id = $userInfo->id;
        $email = $userInfo->email;
        $name = $userInfo->name;
        $picture = $userInfo->picture;

        // 3. Veritabanı İşlemleri (Kullanıcıyı kaydet/güncelle)
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE google_id = ?");
        $stmt->execute([$google_id]);
        $userExists = $stmt->fetch();

        if ($userExists) {
            // Kullanıcı zaten var: Güncelleme yap
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE google_id = ?");
            $stmt->execute([$name, $email, $picture, $google_id]);
            $user_id = $userExists['id'];

        } else {
            // Yeni kullanıcı: Kayıt yap
            $stmt = $db->prepare("INSERT INTO users (google_id, username, email, profile_picture) VALUES (?, ?, ?, ?)");
            $stmt->execute([$google_id, $name, $email, $picture]);
            $user_id = $db->lastInsertId();
        }

        // 4. Uygulama Oturumunu Başlat
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $name;
        $_SESSION['is_logged_in'] = true;

        // Başarılı giriş sonrası ana sayfaya yönlendir
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        // Hata durumunda hata mesajı göster
        echo "Giriş işlemi sırasında bir hata oluştu: " . $e->getMessage();
        exit();
    }
} else {
    // Google'dan 'code' gelmezse (örneğin kullanıcı izni iptal ederse)
    header('Location: index.php'); // Ana sayfaya geri dön
    exit();
}
