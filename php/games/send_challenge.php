<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/common/game_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$challengedId = $input['challenged_id'] ?? null;
$gameType = $input['game_type'] ?? null;

if (!$challengedId || !$gameType) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

// Geçerli oyun türü kontrolü
$validGameTypes = array_keys(GameCommon::GAME_TYPES);
if (!in_array($gameType, $validGameTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz oyun türü']);
    exit;
}

try {
    $challengerId = $_SESSION['user_id'];

    // Kendine meydan okuma kontrolü
    if ($challengerId == $challengedId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kendinize meydan okuyamazsınız']);
        exit;
    }

    $db = getDbConnection();

    // Kullanıcı bilgilerini al
    $userStmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $userStmt->execute([$challengerId]);
    $challengerUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    $targetStmt = $db->prepare("SELECT username, is_online FROM users WHERE id = ?");
    $targetStmt->execute([$challengedId]);
    $challengedUser = $targetStmt->fetch(PDO::FETCH_ASSOC);

    if (!$challengedUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }

    // Zaten bekleyen davet var mı kontrol et
    $stmt = $db->prepare("
    SELECT id FROM game_invitations
    WHERE challenger_id = ? AND challenged_id = ? AND game_type = ? AND status = 'pending'
    ");
    $stmt->execute([$challengerId, $challengedId, $gameType]);

    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcıya zaten bir davet gönderdiniz']);
        exit;
    }

    // Davet oluştur
    $result = GameCommon::createChallenge($challengerId, $challengedId, $gameType);

    if ($result['success']) {
        // Ably ile bildirim gönder
        $ably = new AblyGameService();
        $messageData = [
            'type' => 'challenge_received',
            'challenge_id' => $result['challenge_id'],
            'challenger_id' => $challengerId,
            'challenger_username' => $challengerUser['username'],
            'challenged_id' => $challengedId,
            'game_type' => $gameType,
            'timestamp' => time()
        ];

        $ablySent = $ably->publishMessage(
            "user-" . $challengedId,
            'game_event',
            $messageData
        );

        // Aktivite kaydı oluştur
        ActivityLogger::logChallengeActivity($challengerId, $challengedId, $challengedUser['username'], $gameType);

        echo json_encode([
            'success' => true,
            'message' => 'Meydan okuma gönderildi!',
            'challenge_id' => $result['challenge_id'],
            'ably_sent' => $ablySent
        ]);
    } else {
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("Meydan okuma gönderme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Meydan okuma gönderilirken hata oluştu']);
}
?>
