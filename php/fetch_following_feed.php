<?php
require_once 'config.php';
header('Content-Type: application/json');

// Oturum Kontrolü (Giriş yapmamışsa akış gösterilmez)
$currentUserId = $_SESSION['user_id'] ?? null;
if (!$currentUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Akışı görmek için oturum açmalısınız.']);
    exit;
}

$LIMIT = 50;
$PAGE = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$OFFSET = ($PAGE - 1) * $LIMIT;

try {
    $db = getDbConnection();

    // Sadece takip edilen kullanıcıların çizimlerini çeker.
    $stmt = $db->prepare("
        SELECT
            d.id,
            d.content,
            d.updated_at,
            u.username AS author_username,
            u.id AS author_id
        FROM drawings d
        JOIN users u ON d.user_id = u.id
        -- TAKİP EDİLENLERİN KONTROLÜ
        WHERE d.user_id IN (
            SELECT following_id FROM follows WHERE follower_id = :current_user_id
        )
        -- ENGELLEME KONTROLÜ (Takip edilen, beni engelledi mi? Veya ben onu engelledim mi?)
        AND d.user_id NOT IN (
            SELECT blocked_id FROM blocks WHERE blocker_id = :current_user_id
            UNION
            SELECT blocker_id FROM blocks WHERE blocked_id = :current_user_id
        )
        -- Sadece görünür çizimleri gösterir
        AND d.is_visible = TRUE
        ORDER BY d.updated_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $LIMIT, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $OFFSET, PDO::PARAM_INT);
    $stmt->execute();
    $drawings = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'drawings' => $drawings,
        'message' => 'Takip akışı başarıyla yüklendi.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Akış yükleme hatası: ' . $e->getMessage()
    ]);
}
?>
