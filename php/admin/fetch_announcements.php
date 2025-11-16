<?php
// admin/fetch_announcements.php - DÜZELTİLDİ
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
    $stmt = $db->query("
    SELECT a.*, u.username as created_by_username
    FROM announcements a
    LEFT JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC
    ");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'announcements' => $announcements]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch announcements: ' . $e->getMessage()]);
}
?>
