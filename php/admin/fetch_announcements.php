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
    SELECT a.*, u.username as created_by_name
    FROM announcements a
    LEFT JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC
    LIMIT 50
    ");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'announcements' => $announcements
    ]);

} catch (Exception $e) {
    error_log("Fetch announcements error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Duyurular yüklenirken hata oluştu'
    ]);
}
