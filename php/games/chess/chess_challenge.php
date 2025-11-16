<?php
require_once '../common/game_functions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $challengedId = $input['challenged_id'] ?? null;
    $gameType = 'chess';

    if (!$challengedId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Eksik parametre']);
        exit;
    }

    $result = GameCommon::createChallenge($userId, $challengedId, $gameType);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
