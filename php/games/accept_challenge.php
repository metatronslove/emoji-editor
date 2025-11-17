<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$challengeId = $input['challenge_id'] ?? null;
$action = $input['action'] ?? 'accept';

if (!$challengeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

try {
    $db = getDbConnection();
    $userId = $_SESSION['user_id'];

    // Daveti kontrol et
    $stmt = $db->prepare("
    SELECT gi.*, u.username as challenger_username
    FROM game_invitations gi
    JOIN users u ON gi.challenger_id = u.id
    WHERE gi.id = ? AND gi.challenged_id = ? AND gi.status = 'pending'
    ");
    $stmt->execute([$challengeId, $userId]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$challenge) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Davet bulunamadı']);
        exit;
    }

    if ($action === 'accept') {
        // Aktif oyun oluştur
        $stmt = $db->prepare("
        INSERT INTO active_games (game_type, player1_id, player2_id, current_turn, game_status)
        VALUES (?, ?, ?, ?, 'active')
        ");

        // İlk hamleyi challenger yapar
        $firstPlayer = $challenge['challenger_id'];
        $stmt->execute([
            $challenge['game_type'],
            $challenge['challenger_id'],
            $challenge['challenged_id'],
            $firstPlayer
        ]);

        $gameId = $db->lastInsertId();

        // Daveti kabul edilmiş olarak işaretle
        $stmt = $db->prepare("UPDATE game_invitations SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$challengeId]);

        echo json_encode([
            'success' => true,
            'game_id' => $gameId,
            'message' => 'Oyun başlatıldı!'
        ]);
    } else {
        // Daveti reddet
        $stmt = $db->prepare("UPDATE game_invitations SET status = 'declined' WHERE id = ?");
        $stmt->execute([$challengeId]);

        echo json_encode(['success' => true, 'message' => 'Davet reddedildi']);
    }

} catch (Exception $e) {
    error_log("Challenge kabul/reddetme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'İşlem sırasında hata oluştu: ' . $e->getMessage()
    ]);
}
?>
