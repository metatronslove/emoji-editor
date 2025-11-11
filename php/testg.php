<?php
require_once 'config.php';
header('Content-Type: application/json');

// DEBUG: Basit sorgu ile test
try {
    $db = getDbConnection();

    // En basit sorgu
    $stmt = $db->prepare("
    SELECT d.id, d.content, d.updated_at, u.username AS author_username
    FROM drawings d
    LEFT JOIN users u ON d.user_id = u.id
    WHERE d.is_visible = TRUE
    ORDER BY d.updated_at DESC
    LIMIT 50
    ");

    $stmt->execute();
    $drawings = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'drawings' => $drawings,
        'message' => 'DEBUG: Basit sorgu çalıştı'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'DEBUG Hatası: ' . $e->getMessage()
    ]);
}
?>
