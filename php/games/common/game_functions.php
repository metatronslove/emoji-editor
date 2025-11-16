<?php
require_once '../../config.php';

class GameCommon {
    // Oyun türlerini tanımla
    const GAME_TYPES = [
        'chess' => [
            'name' => 'Satranç',
            'emoji' => '♟️',
            'min_players' => 2,
            'max_players' => 2
        ],
        'reversi' => [
            'name' => 'Reversi',
            'emoji' => '🔴',
            'min_players' => 2,
            'max_players' => 2
        ],
        'tavla' => [
            'name' => 'Tavla',
            'emoji' => '🎲',
            'min_players' => 2,
            'max_players' => 2
        ]
    ];

    // Oyun daveti oluştur
    public static function createChallenge($challengerId, $challengedId, $gameType) {
        $db = getDbConnection();

        // Kullanıcıların çevrimiçi olup olmadığını kontrol et
        $stmt = $db->prepare("SELECT is_online FROM users WHERE id = ?");
        $stmt->execute([$challengedId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['is_online']) {
            return ['success' => false, 'message' => 'Kullanıcı şu anda çevrimdışı.'];
        }

        // Zaten bekleyen davet var mı kontrol et
        $stmt = $db->prepare("
            SELECT id FROM game_invitations
            WHERE challenger_id = ? AND challenged_id = ? AND game_type = ? AND status = 'pending'
        ");
        $stmt->execute([$challengerId, $challengedId, $gameType]);

        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Bu kullanıcıya zaten bir davet gönderdiniz.'];
        }

        // Davet oluştur
        $stmt = $db->prepare("
            INSERT INTO game_invitations (challenger_id, challenged_id, game_type, status, expires_at)
            VALUES (?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 5 MINUTE))
        ");
        $stmt->execute([$challengerId, $challengedId, $gameType]);

        $challengeId = $db->lastInsertId();

        // Aktivite kaydı oluştur
        self::logActivity($challengerId, 'challenge', $challengeId, [
            'game_type' => $gameType,
            'challenged_id' => $challengedId
        ]);

        return ['success' => true, 'challenge_id' => $challengeId];
    }

    // Oyun başlat
    public static function startGame($challengeId) {
        $db = getDbConnection();

        // Daveti al
        $stmt = $db->prepare("
            SELECT * FROM game_invitations
            WHERE id = ? AND status = 'pending' AND expires_at > NOW()
        ");
        $stmt->execute([$challengeId]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$challenge) {
            return ['success' => false, 'message' => 'Geçersiz veya süresi dolmuş davet.'];
        }

        // Oyunu başlat
        $stmt = $db->prepare("
            INSERT INTO active_games (game_type, player1_id, player2_id, game_state, current_turn)
            VALUES (?, ?, ?, ?, ?)
        ");

        $initialState = self::getInitialGameState($challenge['game_type']);
        $firstPlayer = $challenge['challenger_id']; // İlk hamleyi davet eden yapar

        $stmt->execute([
            $challenge['game_type'],
            $challenge['challenger_id'],
            $challenge['challenged_id'],
            json_encode($initialState),
            $firstPlayer
        ]);

        $gameId = $db->lastInsertId();

        // Daveti kabul edilmiş olarak işaretle
        $stmt = $db->prepare("UPDATE game_invitations SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$challengeId]);

        return ['success' => true, 'game_id' => $gameId];
    }

    // Oyun durumunu getir
    public static function getGameState($gameId) {
        $db = getDbConnection();

        $stmt = $db->prepare("
            SELECT ag.*,
                   u1.username as player1_username,
                   u2.username as player2_username
            FROM active_games ag
            JOIN users u1 ON ag.player1_id = u1.id
            JOIN users u2 ON ag.player2_id = u2.id
            WHERE ag.id = ?
        ");
        $stmt->execute([$gameId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Aktivite kaydı oluştur
    public static function logActivity($userId, $activityType, $targetId, $activityData = []) {
        $db = getDbConnection();

        $stmt = $db->prepare("
            INSERT INTO user_activities (user_id, activity_type, target_id, activity_data)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $activityType,
            $targetId,
            json_encode($activityData)
        ]);
    }

    // Başlangıç oyun durumunu getir
    private static function getInitialGameState($gameType) {
        switch ($gameType) {
            case 'chess':
                return self::getInitialChessState();
            case 'reversi':
                return self::getInitialReversiState();
            case 'tavla':
                return self::getInitialTavlaState();
            default:
                return [];
        }
    }

    // Satranç başlangıç durumu
    private static function getInitialChessState() {
        return [
            'board' => [
                ['♜', '♞', '♝', '♛', '♚', '♝', '♞', '♜'],
                ['♟', '♟', '♟', '♟', '♟', '♟', '♟', '♟'],
                ['', '', '', '', '', '', '', ''],
                ['', '', '', '', '', '', '', ''],
                ['', '', '', '', '', '', '', ''],
                ['', '', '', '', '', '', '', ''],
                ['♙', '♙', '♙', '♙', '♙', '♙', '♙', '♙'],
                ['♖', '♘', '♗', '♕', '♔', '♗', '♘', '♖']
            ],
            'current_player' => 'white',
            'move_count' => 0,
            'captured' => []
        ];
    }

    // Reversi başlangıç durumu
    private static function getInitialReversiState() {
        $board = array_fill(0, 8, array_fill(0, 8, ''));

        // Başlangıç taşları
        $board[3][3] = '⚪';
        $board[3][4] = '⚫';
        $board[4][3] = '⚫';
        $board[4][4] = '⚪';

        return [
            'board' => $board,
            'current_player' => 'black',
            'scores' => ['black' => 2, 'white' => 2]
        ];
    }

    // Tavla başlangıç durumu
    private static function getInitialTavlaState() {
        return [
            'board' => [
                24 => ['count' => 2, 'player' => 'black'],
                19 => ['count' => 5, 'player' => 'white'],
                17 => ['count' => 3, 'player' => 'white'],
                13 => ['count' => 5, 'player' => 'black'],
                12 => ['count' => 5, 'player' => 'white'],
                8 => ['count' => 3, 'player' => 'black'],
                6 => ['count' => 5, 'player' => 'black']
            ],
            'dice' => [0, 0],
            'current_player' => 'black',
            'bar' => ['black' => 0, 'white' => 0],
            'home' => ['black' => 0, 'white' => 0]
        ];
    }

    // Kullanıcının çevrimiçi durumunu güncelle
    public static function updateUserOnlineStatus($userId) {
        $db = getDbConnection();

        $stmt = $db->prepare("UPDATE users SET is_online = TRUE, last_activity = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }

    // Çevrimdışı kullanıcıları kontrol et
    public static function checkOfflineUsers() {
        $db = getDbConnection();

        $stmt = $db->prepare("UPDATE users SET is_online = FALSE WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $stmt->execute();
    }
}
?>
