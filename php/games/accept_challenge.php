<?php
require_once 'common/game_functions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$challengeId = $input['challenge_id'] ?? null;
$action = $input['action'] ?? null; // 'accept' or 'decline'

if (!$challengeId || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

try {
    $db = getDbConnection();
    $userId = $_SESSION['user_id'];

    // Daveti kontrol et
    $stmt = $db->prepare("
        SELECT * FROM game_invitations
        WHERE id = ? AND challenged_id = ? AND status = 'pending' AND expires_at > NOW()
    ");
    $stmt->execute([$challengeId, $userId]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$challenge) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Davet bulunamadı veya süresi dolmuş']);
        exit;
    }

    if ($action === 'accept') {
        // Oyunu başlat
        $result = GameCommon::startGame($challengeId);

        if ($result['success']) {
            echo json_encode(['success' => true, 'game_id' => $result['game_id']]);
        } else {
            echo json_encode($result);
        }
    } else {
        // Daveti reddet
        $stmt = $db->prepare("UPDATE game_invitations SET status = 'declined' WHERE id = ?");
        $stmt->execute([$challengeId]);

        echo json_encode(['success' => true, 'message' => 'Davet reddedildi']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
?>
