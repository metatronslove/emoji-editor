<?php
require_once __DIR__ . '/../../config.php';

class GameCommon {
    const GAME_TYPES = [
        'chess' => ['name' => 'Satranç', 'emoji' => '♟️'],
        'reversi' => ['name' => 'Reversi', 'emoji' => '🔴'],
        'tavla' => ['name' => 'Tavla', 'emoji' => '🎲']
    ];

    // Basit oyun başlatma
    public static function startGame($challengeId) {
        $db = getDbConnection();

        // Daveti al
        $stmt = $db->prepare("
        SELECT * FROM game_invitations
        WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$challengeId]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$challenge) {
            return ['success' => false, 'message' => 'Geçersiz davet'];
        }

        // Aktif oyun oluştur
        $stmt = $db->prepare("
        INSERT INTO active_games (game_type, player1_id, player2_id, current_turn, game_status)
        VALUES (?, ?, ?, ?, 'active')
        ");

        $firstPlayer = $challenge['challenger_id'];
        $stmt->execute([
            $challenge['game_type'],
            $challenge['challenger_id'],
            $challenge['challenged_id'],
            $firstPlayer
        ]);

        $gameId = $db->lastInsertId();

        // Daveti güncelle
        $stmt = $db->prepare("UPDATE game_invitations SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$challengeId]);

        return ['success' => true, 'game_id' => $gameId];
    }

    // Basit davet oluşturma
    public static function createChallenge($challengerId, $challengedId, $gameType) {
        $db = getDbConnection();

        // Çakışan davet kontrolü
        $stmt = $db->prepare("
        SELECT id FROM game_invitations
        WHERE challenger_id = ? AND challenged_id = ? AND game_type = ? AND status = 'pending'
        ");
        $stmt->execute([$challengerId, $challengedId, $gameType]);

        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Zaten bekleyen bir davetiniz var'];
        }

        // Davet oluştur
        $stmt = $db->prepare("
        INSERT INTO game_invitations (challenger_id, challenged_id, game_type, status)
        VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$challengerId, $challengedId, $gameType]);

        $challengeId = $db->lastInsertId();

        return ['success' => true, 'challenge_id' => $challengeId];
    }

    // Oyun durumunu getir
    public static function getGameState($gameId) {
        $db = getDbConnection();

        $stmt = $db->prepare("
        SELECT ag.*,
        u1.username as player1_username,
        u2.username as player2_username
        FROM active_games ag
        LEFT JOIN users u1 ON ag.player1_id = u1.id
        LEFT JOIN users u2 ON ag.player2_id = u2.id
        WHERE ag.id = ?
        ");
        $stmt->execute([$gameId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

class ActivityLogger {
    // Basit aktivite loglama
    public static function logChallengeActivity($challengerId, $challengedId, $challengedUsername, $gameType) {
        // Geçici olarak boş bırak - sonra implement et
        return true;
    }

    public static function logDeclineActivity($userId, $challengerId, $challengerUsername, $gameType) {
        // Geçici olarak boş bırak - sonra implement et
        return true;
    }
}

// Database bağlantı fonksiyonu
function getDbConnection() {
    // config.php'deki bağlantıyı kullan
    global $db;
    if (!$db) {
        throw new Exception("Database bağlantısı yok");
    }
    return $db;
}
?>
