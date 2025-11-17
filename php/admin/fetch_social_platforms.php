<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || !in_array($_SESSION['user_role'], ['admin', 'moderator'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

try {
    $db = getDbConnection();

    $stmt = $db->query("
    SELECT * FROM social_platforms
    ORDER BY name
    ");
    $platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'platforms' => $platforms
    ]);

} catch (Exception $e) {
    error_log("Fetch social platforms error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Platformlar yüklenirken hata oluştu'
    ]);
}
