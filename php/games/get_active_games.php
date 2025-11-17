<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDbConnection();

    // Tablo kontrolü
    $tableCheck = $db->query("SHOW TABLES LIKE 'active_games'")->fetch(PDO::FETCH_ASSOC);
    if (!$tableCheck) {
        echo json_encode(['success' => true, 'games' => []]);
        exit;
    }

    // Basit sorgu - sütun isimlerini kontrol et
    $stmt = $db->prepare("
    SELECT
    id as game_id,
    game_type,
    player1_id,
    player2_id,
    current_turn,
    created_at
    FROM active_games
    WHERE (player1_id = ? OR player2_id = ?)
    AND game_status = 'active'
    ORDER BY created_at DESC
    ");

    $stmt->execute([$userId, $userId]);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Basit format
    $formattedGames = [];
    foreach ($games as $game) {
        // Rakip bilgisi
        $opponentId = ($game['player1_id'] == $userId) ? $game['player2_id'] : $game['player1_id'];

        // Rakip kullanıcı adını al
        $userStmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $userStmt->execute([$opponentId]);
        $opponentUser = $userStmt->fetch(PDO::FETCH_ASSOC);

        $formattedGames[] = [
            'game_id' => $game['game_id'],
            'game_type' => $game['game_type'],
            'opponent_id' => $opponentId,
            'opponent_username' => $opponentUser['username'] ?? 'Bilinmeyen',
            'current_turn' => $game['current_turn'],
            'is_my_turn' => ($game['current_turn'] == $userId),
            'created_at' => $game['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'games' => $formattedGames
    ]);

} catch (Exception $e) {
    error_log("Aktif oyunlar hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Oyunlar yüklenirken hata oluştu',
        'error' => $e->getMessage()
    ]);
}
?>
