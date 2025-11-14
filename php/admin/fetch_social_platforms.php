<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM social_media_platforms ORDER BY name");
    $stmt->execute();
    $platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'platforms' => $platforms]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Platformlar yüklenemedi: ' . $e->getMessage()]);
}
?>