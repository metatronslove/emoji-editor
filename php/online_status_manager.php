<?php
// online_status_manager.php
require_once 'config.php';

class OnlineStatusManager {

    const ONLINE_TIMEOUT = 300; // 5 dakika (saniye)

    public static function updateOnlineStatus($userId) {
        try {
            $db = getDbConnection();

            // Hem last_activity hem de is_online alanlarını güncelle
            $stmt = $db->prepare("
                UPDATE users
                SET last_activity = NOW(),
                    is_online = 1
                WHERE id = ?
            ");
            $result = $stmt->execute([$userId]);

            error_log("Online status updated for user {$userId}. Result: " . ($result ? 'success' : 'failed'));
            return $result;

        } catch (Exception $e) {
            error_log("Online status update error for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    public static function setOffline($userId) {
        try {
            $db = getDbConnection();
            $stmt = $db->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
            $result = $stmt->execute([$userId]);

            error_log("User {$userId} set to offline. Result: " . ($result ? 'success' : 'failed'));
            return $result;

        } catch (Exception $e) {
            error_log("Offline status update error for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    public static function cleanupInactiveUsers() {
        try {
            $db = getDbConnection();
            $stmt = $db->prepare("
                UPDATE users
                SET is_online = 0
                WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                AND is_online = 1
            ");
            $result = $stmt->execute();

            $affected = $stmt->rowCount();
            error_log("Cleaned up {$affected} inactive users");
            return $affected;

        } catch (Exception $e) {
            error_log("Inactive users cleanup error: " . $e->getMessage());
            return false;
        }
    }

    public static function isUserOnline($userData) {
        // userData array içinde last_activity ve is_online olmalı
        if (!$userData) return false;

        // Önce is_online alanını kontrol et
        if (isset($userData['is_online']) && $userData['is_online'] == 1) {
            return true;
        }

        // Eğer is_online 0 ise, last_activity'ye bak
        if (!empty($userData['last_activity']) && $userData['last_activity'] !== '0000-00-00 00:00:00') {
            $lastActivityTime = strtotime($userData['last_activity']);
            $currentTime = time();
            $diff = $currentTime - $lastActivityTime;

            return ($diff < self::ONLINE_TIMEOUT);
        }

        return false;
    }

    public static function getOnlineUsersCount() {
        try {
            $db = getDbConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE is_online = 1");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Online users count error: " . $e->getMessage());
            return 0;
        }
    }
}

// Otomatik temizleme - %10 ihtimalle çalıştır (performans için)
if (rand(1, 10) === 1) {
    OnlineStatusManager::cleanupInactiveUsers();
}
?>
