<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDbConnection();

    // Bekleyen davetleri getir
    $stmt = $db->prepare("
        SELECT
            gi.*,
            u.username as challenger_username,
            u.profile_picture as challenger_picture,
            TIMESTAMPDIFF(SECOND, NOW(), gi.expires_at) as expires_in_seconds
        FROM game_invitations gi
        JOIN users u ON gi.challenger_id = u.id
        WHERE gi.challenged_id = ?
        AND gi.status = 'pending'
        AND gi.expires_at > NOW()
        ORDER BY gi.created_at DESC
    ");
    $stmt->execute([$userId]);
    $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Oyun türlerini Türkçe isimlere çevir
    $gameNames = [
        'chess' => 'Satranç',
        'reversi' => 'Reversi',
        'tavla' => 'Tavla'
    ];

    foreach ($challenges as &$challenge) {
        $challenge['game_name'] = $gameNames[$challenge['game_type']] ?? $challenge['game_type'];
        $challenge['game_emoji'] = $this->getGameEmoji($challenge['game_type']);
        $challenge['formatted_time'] = $this->formatRemainingTime($challenge['expires_in_seconds']);
    }

    echo json_encode(['success' => true, 'challenges' => $challenges]);

} catch (Exception $e) {
    error_log("Bekleyen davetler getirme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Davetler yüklenirken hata oluştu']);
}

// Oyun emojisini getir
function getGameEmoji($gameType) {
    $emojis = [
        'chess' => '♟️',
        'reversi' => '🔴',
        'tavla' => '🎲'
    ];
    return $emojis[$gameType] ?? '🎮';
}

// Kalan süreyi formatla
function formatRemainingTime($seconds) {
    if ($seconds <= 0) return 'Süresi dolmak üzere';

    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;

    if ($minutes > 0) {
        return "{$minutes}d {$remainingSeconds}s";
    } else {
        return "{$remainingSeconds}s";
    }
}
?>
