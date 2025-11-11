<?php
require_once 'config.php';
header('Content-Type: application/json');

$targetUserId = $_GET['user_id'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$targetUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Çizimleri yüklenecek kullanıcı kimliği eksik.']);
    exit;
}

try {
    $db = getDbConnection();

    $WHERE_CLAUSE = "d.user_id = :user_id AND (d.is_visible = TRUE";

    if ($currentUserId == $targetUserId) {
        $WHERE_CLAUSE .= " OR d.is_visible = FALSE)";
    } else {
        $WHERE_CLAUSE .= ")";
    }

    $stmt = $db->prepare("
    SELECT
    d.id,
    d.content,
    d.category,
    d.comments_allowed,
    d.is_visible,
    d.updated_at,
    u.username AS author_username,
    u.profile_picture AS author_profile_picture,
    u.id AS author_id
    FROM drawings d
    INNER JOIN users u ON d.user_id = u.id
    WHERE {$WHERE_CLAUSE}
    ORDER BY d.category ASC, d.updated_at DESC
    ");

    $stmt->bindParam(':user_id', $targetUserId, PDO::PARAM_INT);
    $stmt->execute();
    $drawings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categorizedDrawings = [];
    foreach ($drawings as $drawing) {
        $category = $drawing['category'] ?? 'Genel';
        if (!isset($categorizedDrawings[$category])) {
            $categorizedDrawings[$category] = [];
        }
        $categorizedDrawings[$category][] = $drawing;
    }

    $featuredDrawing = $drawings[0] ?? null;

    echo json_encode([
        'success' => true,
        'categorized_drawings' => $categorizedDrawings,
        'featured_drawing' => $featuredDrawing
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Çizim listesi yükleme hatası: ' . $e->getMessage()
    ]);
}
?>
