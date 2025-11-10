<?php
// Router.php
// URL isteklerini ilgili PHP dosyalarına yönlendiren sınıf
require_once 'config.php';
require_once 'Auth.php';

class Router {
    public function run() {
        // 1. İstenen URI yolunu al
        $uri = trim($_SERVER['REQUEST_URI'], '/');

        // Query string (GET parametreleri) varsa temizle
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // 2. Ana Sayfa (index) Yönlendirmesi
        // Eğer kullanıcı index.php veya boş URL ile geldiyse (genellikle editörün olduğu yer)
        if ($uri === '' || $uri === 'index.php') {
            // NOT: index.html dosyasını buraya dahil ettiğiniz varsayılmıştır.
            // Gerçek uygulamada burası index.php olmalı ve HTML'i render etmelidir.
            require 'index.html';
            return;
        }

        // 3. Statik Yollar
        if ($uri === 'admin/dashboard') {
            // Admin/Moderatör kontrolü (Yetkisiz erişim ise ana sayfaya yönlendir)
            if (!Auth::isLoggedIn() || !in_array($_SESSION['user_role'] ?? 'user', ['admin', 'moderator'])) {
                header('Location: /');
                exit;
            }
            require 'admin/dashboard.php';
            return;
        }

        // 4. Dinamik Yollar
        $segments = explode('/', $uri);

        // a) Çizim Yolu: /drawing/123
        if ($segments[0] === 'drawing' && count($segments) === 2 && is_numeric($segments[1])) {
            // URL'deki ID'yi GET parametresi olarak ayarla
            $_GET['id'] = $segments[1];
            require 'drawing.php';
            return;
        }

        // b) Profil Yolu: /kullaniciadi
        // Bu, .htaccess'in yönlendirdiği ana kuraldır.
        if (count($segments) === 1 && $segments[0] !== '') {
            $_GET['username'] = $segments[0];
            require 'profile.php';
            return;
        }

        // 5. 404 Sayfa Bulunamadı
        http_response_code(404);
        echo "404 Sayfa Bulunamadı.";
    }
}
