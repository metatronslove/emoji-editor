<?php
// admin/fetch_social_platforms.php - DÜZELTİLDİ
require_once '../config.php';
require_once '../functions.php';
session_start();
header('Content-Type: application/json');

$userRole = $_SESSION['user_role'] ?? 'user';
if (!in_array($userRole, ['admin', 'moderator'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT * FROM social_media_platforms ORDER BY name");
    $platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'platforms' => $platforms]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Platformlar yüklenemedi: ' . $e->getMessage()]);
}
?>
