<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$LIMIT = 50;
$PAGE = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$OFFSET = ($PAGE - 1) * $LIMIT;

$currentUserId = $_SESSION['user_id'] ?? null;

try {
    $db = getDbConnection();

    // GELİŞTİRİLMİŞ SORGU - author_id ve profil fotoğrafı dahil
    $sql = "
    SELECT
    d.id,
    d.content,
    d.first_row_length,
    d.width,
    d.updated_at,
    u.username AS author_username,
    u.profile_picture AS author_profile_picture,
    u.id AS author_id
    FROM drawings d
    INNER JOIN users u ON d.user_id = u.id
    WHERE d.is_visible = TRUE
    AND (
        u.privacy_mode = 'public'
        OR d.user_id = :current_user_id
        OR (
            u.privacy_mode = 'private'
            AND EXISTS (
                SELECT 1 FROM follows f
                WHERE f.follower_id = :current_user_id_follow
                AND f.following_id = u.id
                )
                )
                )
                ORDER BY d.updated_at DESC
                LIMIT :limit OFFSET :offset
                ";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':limit', $LIMIT, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $OFFSET, PDO::PARAM_INT);

    if ($currentUserId) {
        $stmt->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
        $stmt->bindParam(':current_user_id_follow', $currentUserId, PDO::PARAM_INT);
    } else {
        $nullParam = null;
        $stmt->bindParam(':current_user_id', $nullParam, PDO::PARAM_NULL);
        $stmt->bindParam(':current_user_id_follow', $nullParam, PDO::PARAM_NULL);
    }

    $stmt->execute();
    $drawings = $stmt->fetchAll();

    // Toplam kayıt sayısı
    $countSql = "
    SELECT COUNT(*)
    FROM drawings d
    INNER JOIN users u ON d.user_id = u.id
    WHERE d.is_visible = TRUE
    AND (
        u.privacy_mode = 'public'
        OR d.user_id = :current_user_id_count
        OR (
            u.privacy_mode = 'private'
            AND EXISTS (
                SELECT 1 FROM follows f
                WHERE f.follower_id = :current_user_id_count_follow
                AND f.following_id = u.id
                )
                )
                )
                ";

    $countStmt = $db->prepare($countSql);

    if ($currentUserId) {
        $countStmt->bindParam(':current_user_id_count', $currentUserId, PDO::PARAM_INT);
        $countStmt->bindParam(':current_user_id_count_follow', $currentUserId, PDO::PARAM_INT);
    } else {
        $nullParam = null;
        $countStmt->bindParam(':current_user_id_count', $nullParam, PDO::PARAM_NULL);
        $countStmt->bindParam(':current_user_id_count_follow', $nullParam, PDO::PARAM_NULL);
    }

    $countStmt->execute();
    $totalDrawings = $countStmt->fetchColumn();
    $totalPages = ceil($totalDrawings / $LIMIT);

    echo json_encode([
        'success' => true,
        'currentPage' => $PAGE,
        'totalPages' => $totalPages,
        'totalDrawings' => $totalDrawings,
        'drawings' => $drawings
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>
