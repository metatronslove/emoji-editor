<?php
// games/common/game_functions.php
require_once __DIR__ . '/../../config.php';

class AblyGameService {
    private $ably;

    public function __construct() {
        try {
            $this->ably = new Ably\AblyRest(['key' => ABLY_API_KEY]);
        } catch (Exception $e) {
            error_log("Ably connection error: " . $e->getMessage());
        }
    }

    public function publishMessage($channelName, $eventName, $messageData) {
        try {
            if (!$this->ably) {
                error_log("Ably not initialized");
                return false;
            }

            $channel = $this->ably->channel($channelName);
            $result = $channel->publish($eventName, $messageData);
            return true;
        } catch (Exception $e) {
            error_log("Ably publish error: " . $e->getMessage());
            return false;
        }
    }
}

class GameCommon {
    const GAME_TYPES = [
        'chess' => ['name' => 'Satranç', 'emoji' => '♟️', 'board_size' => 8],
        'reversi' => ['name' => 'Reversi', 'emoji' => '🔴', 'board_size' => 8],
        'tavla' => ['name' => 'Tavla', 'emoji' => '🎲', 'board_size' => 24]
    ];

    public static function createChallenge($challengerId, $challengedId, $gameType) {
        try {
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
            INSERT INTO game_invitations (challenger_id, challenged_id, game_type, status, expires_at)
            VALUES (?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 1 HOUR))
            ");
            $stmt->execute([$challengerId, $challengedId, $gameType]);

            $challengeId = $db->lastInsertId();

            return [
                'success' => true,
                'challenge_id' => $challengeId,
                'message' => 'Meydan okuma gönderildi!'
            ];

        } catch (Exception $e) {
            error_log("Challenge creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Davet oluşturulurken hata oluştu'];
        }
    }

    public static function getGameState($gameId) {
        try {
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

        } catch (Exception $e) {
            error_log("Get game state error: " . $e->getMessage());
            return null;
        }
    }

    public static function initializeGameState($gameType) {
        $initialStates = [
            'chess' => [
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
            ],
            'reversi' => [
                'board' => array_fill(0, 8, array_fill(0, 8, '')),
                'current_player' => 'black',
                'scores' => ['black' => 2, 'white' => 2]
            ],
            'tavla' => [
                'board' => [],
                'dice' => [0, 0],
                'current_player' => 'black',
                'bar' => ['black' => 0, 'white' => 0]
            ]
        ];

        // Reversi için başlangıç taşları
        if ($gameType === 'reversi') {
            $initialStates['reversi']['board'][3][3] = '⚪';
            $initialStates['reversi']['board'][3][4] = '⚫';
            $initialStates['reversi']['board'][4][3] = '⚫';
            $initialStates['reversi']['board'][4][4] = '⚪';
        }

        // Tavla için başlangıç pozisyonu
        if ($gameType === 'tavla') {
            $initialStates['tavla']['board'] = [
                1 => ['count' => 2, 'player' => 'white'],
                6 => ['count' => 5, 'player' => 'black'],
                8 => ['count' => 3, 'player' => 'black'],
                12 => ['count' => 5, 'player' => 'white'],
                13 => ['count' => 5, 'player' => 'black'],
                17 => ['count' => 3, 'player' => 'white'],
                19 => ['count' => 5, 'player' => 'white'],
                24 => ['count' => 2, 'player' => 'black']
            ];
        }

        return $initialStates[$gameType] ?? null;
    }
}

class ActivityLogger {
    public static function logChallengeActivity($challengerId, $challengedId, $challengedUsername, $gameType) {
        try {
            $db = getDbConnection();

            $stmt = $db->prepare("
            INSERT INTO user_activities (user_id, activity_type, target_id, activity_data)
            VALUES (?, 'challenge', ?, ?)
            ");

            $activityData = json_encode([
                'challenged_user_id' => $challengedId,
                'challenged_username' => $challengedUsername,
                'game_type' => $gameType,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            $stmt->execute([$challengerId, $challengedId, $activityData]);
            return true;

        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
            return false;
        }
    }

    public static function logGameActivity($userId, $gameId, $gameType, $result, $opponentUsername) {
        try {
            $db = getDbConnection();

            $stmt = $db->prepare("
            INSERT INTO user_activities (user_id, activity_type, target_id, activity_data)
            VALUES (?, 'game', ?, ?)
            ");

            $activityData = json_encode([
                'game_type' => $gameType,
                'result' => $result,
                'opponent' => $opponentUsername,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            $stmt->execute([$userId, $gameId, $activityData]);
            return true;

        } catch (Exception $e) {
            error_log("Game activity log error: " . $e->getMessage());
            return false;
        }
    }
}
?>
