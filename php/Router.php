<?php
// Router.php - BASİT VE ETKİLİ VERSİYON
require_once 'config.php';

class Router {
    public function run() {
        $path = trim($_SERVER['REQUEST_URI'], '/');
        $path = parse_url($path, PHP_URL_PATH);

        $routes = [
            'login_handler' => 'login_handler.php',
            'register' => 'register.php',
            'logout.php' => 'logout.php',
            'login.php' => 'login.php',
            'google_callback.php' => 'google_callback.php'
        ];

        foreach ($routes as $route => $file) {
            if ($path === $route || strpos($path, $route) === 0) {
                if (file_exists($file)) {
                    require $file;
                    exit;
                }
            }
        }

        // Profil sayfaları
        if (preg_match('/^[a-zA-Z0-9_]+$/', $path)) {
            $_GET['username'] = $path;
            require 'profile.php';
            exit;
        }

        // Ana sayfa - hiçbir şey yapma, index.php devam etsin
        if ($path === '' || $path === 'index.php') {
            return;
        }

        // 404
        http_response_code(404);
        die("404 - Sayfa Bulunamadı");
    }
}
?>
