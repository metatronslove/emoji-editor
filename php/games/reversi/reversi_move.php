<?php
require_once '../common/game_functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$gameId = $input['game_id'] ?? null;
$row = $input['row'] ?? null;
$col = $input['col'] ?? null;

if (!$gameId || $row === null || $col === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

try {
    $reversiGame = new ReversiGame($gameId);
    $result = $reversiGame->makeMove($row, $col, $userId);

    if ($result['success']) {
        // Oyun sonu kontrolü
        $gameEnd = $reversiGame->checkGameEnd();
        if ($gameEnd) {
            $result['game_end'] = $gameEnd;

            // Aktivite kaydı oluştur
            if ($gameEnd['winner'] !== 'draw') {
                $gameData = GameCommon::getGameState($gameId);
                $winnerId = $gameEnd['winner'] === 'black' ? $gameData['player1_id'] : $gameData['player2_id'];
                GameCommon::logActivity($winnerId, 'game', $gameId, [
                    'game_type' => 'reversi',
                    'result' => 'win',
                    'opponent' => $winnerId == $gameData['player1_id'] ? $gameData['player2_username'] : $gameData['player1_username']
                ]);
            }
        }
    }

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
?>
