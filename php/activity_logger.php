<?php
require_once 'config.php';

class ActivityLogger {

    /**
     * Çizim aktivitesi kaydet
     */
    public static function logDrawingActivity($userId, $drawingId, $drawingTitle = null) {
        try {
            $db = getDbConnection();

            $stmt = $db->prepare("
                INSERT INTO user_activities (user_id, activity_type, target_id, activity_data)
                VALUES (?, 'drawing', ?, ?)
            ");
            $stmt->execute([
                $userId,
                $drawingId,
                json_encode([
                    'drawing_id' => $drawingId,
                    'title' => $drawingTitle,
                    'timestamp' => date('Y-m-d H:i:s')
                ])
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Çizim aktivitesi kaydetme hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Oyun aktivitesi kaydet
     */
    public static function logGameActivity($userId, $gameId, $gameType, $result, $opponentUsername, $score = null) {
        try {
            $db = getDbConnection();

            $stmt = $db->prepare("
                INSERT INTO user_activities (user_id, activity_type, target_id, activity_data)
                VALUES (?, 'game', ?, ?)
            ");
            $stmt->execute([
                $userId,
                $gameId,
                json_encode([
                    'game_type' => $gameType,
                    'result' => $result,
                    'opponent' => $opponentUsername,
                    'score' => $score,
                    'timestamp' => date('Y-m-d H:i:s')
                ])
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Oyun aktivitesi kaydetme hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mesaj aktivitesi kaydet
     */
    public static function logMessageActivity($userId, $targetUserId, $targetUsername, $messageContent) {
        try {
            $db = getDbConnection();

            // Mesaj içeriğini kısalt (güvenlik ve performans için)
            $shortContent = strlen($messageContent) > 200 ?
                substr($messageContent, 0, 200) . '...' : $messageContent;

            $stmt = $db->prepare("
                INSERT INTO user_activities (user_id, activity_type, target_id, activity_data)
                VALUES (?, 'message', ?, ?)
            ");
            $stmt->execute([
                $userId,
                $targetUserId,
                json_encode([
                    'target_user_id' => $targetUserId,
                    'target_username' => $targetUsername,
                    'message_content' => $shortContent,
                    'timestamp' => date('Y-m-d H:i:s')
                ])
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Mesaj aktivitesi kaydetme hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Meydan okuma aktivitesi kaydet
     */
    public static function logChallengeActivity($challengerId, $challengedId, $challengedUsername, $gameType) {
        try {
            $db = getDbConnection();

            $stmt = $db->prepare("
                INSERT INTO user_activities (user_id, activity_type, target_id, activity_data)
                VALUES (?, 'challenge', ?, ?)
            ");
            $stmt->execute([
                $challengerId,
                $challengedId,
                json_encode([
                    'challenged_user_id' => $challengedId,
                    'challenged_username' => $challengedUsername,
                    'game_type' => $gameType,
                    'timestamp' => date('Y-m-d H:i:s')
                ])
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Meydan okuma aktivitesi kaydetme hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Takip aktivitesi kaydet
     */
    public static function logFollowActivity($followerId, $followingId, $followingUsername) {
        try {
            $db = getDbConnection();

            $stmt = $db->prepare("
                INSERT INTO user_activities (user_id, activity_type, target_id, activity_data)
                VALUES (?, 'follow', ?, ?)
            ");
            $stmt->execute([
                $followerId,
                $followingId,
                json_encode([
                    'followed_user_id' => $followingId,
                    'followed_username' => $followingUsername,
                    'timestamp' => date('Y-m-d H:i:s')
                ])
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Takip aktivitesi kaydetme hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Profil güncelleme aktivitesi kaydet
     */
    public static function logProfileUpdateActivity($userId, $updateType, $details = []) {
        try {
            $db = getDbConnection();

            $stmt = $db->prepare("
                INSERT INTO user_activities (user_id, activity_type, target_id, activity_data)
                VALUES (?, 'profile_update', ?, ?)
            ");
            $stmt->execute([
                $userId,
                $userId, // Kendi profilini hedef alır
                json_encode([
                    'update_type' => $updateType,
                    'details' => $details,
                    'timestamp' => date('Y-m-d H:i:s')
                ])
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Profil güncelleme aktivitesi kaydetme hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kullanıcının aktivitelerini temizle (eski kayıtları sil)
     */
    public static function cleanupOldActivities($userId, $keepLast = 100) {
        try {
            $db = getDbConnection();

            $stmt = $db->prepare("
                DELETE FROM user_activities
                WHERE user_id = ?
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id
                        FROM user_activities
                        WHERE user_id = ?
                        ORDER BY created_at DESC
                        LIMIT ?
                    ) AS recent_activities
                )
            ");
            $stmt->execute([$userId, $userId, $keepLast]);

            return true;
        } catch (Exception $e) {
            error_log("Eski aktiviteleri temizleme hatası: " . $e->getMessage());
            return false;
        }
    }
}
?>
