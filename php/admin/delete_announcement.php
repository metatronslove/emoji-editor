<?php
// admin/delete_announcement.php
require_once '../config.php';
require_once '../functions.php';
session_start();
header('Content-Type: application/json');

$userRole = $_SESSION['user_role'] ?? 'user';
if (!in_array($userRole, ['admin', 'moderator'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$announcementId = intval($_POST['announcement_id'] ?? 0);
if (!$announcementId) {
    echo json_encode(['success' => false, 'message' => 'Invalid announcement ID.']);
    exit;
}

try {
    $db = getDbConnection();
    $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$announcementId]);

    echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to delete announcement: ' . $e->getMessage()]);
}
?>
