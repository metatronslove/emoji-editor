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
$challengeId = $input['challenge_id'] ?? null;

if (!$challengeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

try {
    $db = getDbConnection();
    $userId = $_SESSION['user_id'];
    $ably = new AblyGameService();

    // Daveti kontrol et
    $stmt = $db->prepare("
        SELECT gi.*, u.username as challenger_username
        FROM game_invitations gi
        JOIN users u ON gi.challenger_id = u.id
        WHERE gi.id = ? AND gi.challenged_id = ? AND gi.status = 'pending' AND gi.expires_at > NOW()
    ");
    $stmt->execute([$challengeId, $userId]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$challenge) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Davet bulunamadı veya süresi dolmuş']);
        exit;
    }

    // Daveti reddet
    $stmt = $db->prepare("UPDATE game_invitations SET status = 'declined', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$challengeId]);

    // Challenger'a Ably bildirimi gönder
    $declineData = [
        'type' => 'challenge_declined',
        'challenge_id' => $challengeId,
        'declined_by_id' => $userId,
        'declined_by_username' => $_SESSION['username'] ?? 'Kullanıcı',
        'challenger_id' => $challenge['challenger_id'],
        'timestamp' => time()
    ];

    $ablySent = $ably->publishMessage(
        "user-" . $challenge['challenger_id'],
        'game_event',
        $declineData
    );

    // Aktivite kaydı oluştur
    ActivityLogger::logDeclineActivity($userId, $challenge['challenger_id'], $challenge['challenger_username'], $challenge['game_type']);

    echo json_encode([
        'success' => true,
        'message' => 'Davet reddedildi',
        'ably_sent' => $ablySent
    ]);

} catch (Exception $e) {
    error_log("Davet reddetme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Davet reddedilirken hata oluştu: ' . $e->getMessage()]);
}
?>
