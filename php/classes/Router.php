<?php
// Router.php - NULL DEĞER KONTROLLÜ VERSİYON
require_once __DIR__ . '/../config.php';

class Router {
    public function run() {
        // NULL kontrolü ekle - REQUEST_URI boş olabilir
        $requestUri = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';

        // parse_url NULL dönebilir, bu durumda boş string kullan
        $parsedPath = isset($path) ? parse_url($path, PHP_URL_PATH) : '';
        $path = !empty($parsedPath) ? $parsedPath : '';

        // DEBUG: Hangi path'in işlendiğini logla
        error_log("Router Processing Path: " . $path);

        $routes = [
            'login_handler' => 'auth/login_handler.php',
            'register' => 'auth/register.php',
            'logout.php' => 'auth/logout.php',
            'login.php' => 'auth/login.php',
            'google_callback.php' => 'auth/google_callback.php',
            'admin/dashboard' => 'admin/dashboard.php',
            'admin/fetch_users' => 'admin/fetch_users.php',
            'admin/announcements' => 'admin/announcements.php',
            'admin/social_media' => 'admin/social_media.php',
            'admin/rank_system' => 'admin/rank_system.php',
            'admin/private_messages' => 'admin/private_messages.php'
        ];

        foreach ($routes as $route => $file) {
            if ($path === $route) {
                if (file_exists($file)) {
                    error_log("Routing to: " . $file);
                    require $file;
                    exit;
                } else {
                    error_log("File not found: " . $file);
                    $this->show404($path);
                }
            }
        }

        // Profil sayfaları için regex kontrolü - NULL kontrolü ekle
        if (!empty($path) && preg_match('/^[a-zA-Z0-9_]+$/', $path)) {
            $_GET['username'] = $path;
            if (file_exists('profile.php')) {
                require 'profile.php';
                exit;
            } else {
                $this->show404($path);
            }
        }

        // Ana sayfa - hiçbir şey yapma, index.php devam etsin
        if ($path === '' || $path === 'index.php') {
            error_log("Routing to index.php");
            return;
        }

        // 404 - NULL kontrolü ile
        $this->show404($path);
    }

    private function show404($path) {
        http_response_code(404);

        // NULL kontrolü - path boşsa "empty" göster
        $safePath = isset($path) ? htmlspecialchars($path) : 'empty';

        // Basit 404 sayfası
        echo "<!DOCTYPE html>
        <html>
        <head>
        <title>404 - Sayfa Bulunamadı</title>
        <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #d9534f; }
        .container { max-width: 500px; margin: 0 auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
        </head>
        <body>
        <div class='container'>
        <h1>404 - Sayfa Bulunamadı</h1>
        <p>Aradığınız sayfa mevcut değil: <strong>{$safePath}</strong></p>
        <p>Ana sayfaya dönmek için aşağıdaki butonu kullanabilirsiniz.</p>
        <a href='/' class='btn'>Ana Sayfaya Dön</a>
        </div>
        </body>
        </html>";
        exit;
    }
}
?>
