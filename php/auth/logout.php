<?php
// logout.php - GÜNCELLENMİŞ
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Online status manager kontrolü
    if (file_exists(__DIR__ . '/../core/online_status_manager.php')) {
        require_once __DIR__ . '/../core/online_status_manager.php';
        OnlineStatusManager::setOffline($userId);
        error_log("User {$userId} logged out and set offline");
    }
}

// Session'ı tamamen temizle
$_SESSION = [];
session_destroy();

// Ana sayfaya yönlendir
header('Location: /');
exit;
?>
