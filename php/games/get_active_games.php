<?php
require_once '../common/game_functions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDbConnection();

    $stmt = $db->prepare("
    SELECT
    ag.*,
    u1.username as player1_username,
    u2.username as player2_username,
    CASE
    WHEN ag.player1_id = ? THEN u2.username
    ELSE u1.username
    END as opponent_username,
    CASE
    WHEN ag.player1_id = ? THEN 'player1'
    ELSE 'player2'
    END as player_position
    FROM active_games ag
    JOIN users u1 ON ag.player1_id = u1.id
    JOIN users u2 ON ag.player2_id = u2.id
    WHERE ag.player1_id = ? OR ag.player2_id = ?
    ORDER BY ag.updated_at DESC
    ");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Oyun bilgilerini zenginleştir
    foreach ($games as &$game) {
        $gameState = json_decode($game['game_state'], true);

        $game['game_name'] = GameCommon::GAME_TYPES[$game['game_type']]['name'] ?? $game['game_type'];
        $game['game_emoji'] = GameCommon::GAME_TYPES[$game['game_type']]['emoji'] ?? '🎮';
        $game['current_turn_username'] = $game['current_turn'] == $game['player1_id'] ? $game['player1_username'] : $game['player2_username'];
        $game['is_my_turn'] = $game['current_turn'] == $userId;
        $game['last_updated'] = $game['updated_at'];
        $game['game_duration'] = $this->calculateGameDuration($game['created_at']);

        // Oyun durumundan ek bilgileri çıkar
        if ($gameState) {
            $game['move_count'] = $gameState['move_count'] ?? 0;
            $game['current_player'] = $gameState['current_player'] ?? '';
        }
    }

    echo json_encode(['success' => true, 'games' => $games]);

} catch (Exception $e) {
    error_log("Aktif oyunlar getirme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Oyunlar yüklenirken hata oluştu']);
}

// Oyun süresini hesapla
function calculateGameDuration($createdAt) {
    $created = new DateTime($createdAt);
    $now = new DateTime();
    $interval = $created->diff($now);

    if ($interval->d > 0) {
        return $interval->d . ' gün';
    } elseif ($interval->h > 0) {
        return $interval->h . ' saat';
    } elseif ($interval->i > 0) {
        return $interval->i . ' dakika';
    } else {
        return $interval->s . ' saniye';
    }
}
?>
