<?php
// logout.php - GÜNCELLENMİŞ
require_once 'config.php';
require_once 'online_status_manager.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    // Kullanıcıyı çevrimdışı olarak işaretle
    OnlineStatusManager::setOffline($userId);
    error_log("User {$userId} logged out and set offline");
}

session_destroy();
header('Location: https://flood.page.gd/index.php');
exit;
?>
