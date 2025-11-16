<?php
// admin/create_announcement.php - DÜZELTİLDİ
require_once '../config.php';
require_once '../functions.php';
session_start();
header('Content-Type: application/json');

$userRole = $_SESSION['user_role'] ?? 'user';
if (!in_array($userRole, ['admin', 'moderator'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
    exit;
}

$content = trim($_POST['content'] ?? '');
$type = $_POST['type'] ?? 'info';

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Announcement content cannot be empty.']);
    exit;
}

// İzin verilen tipler
$allowedTypes = ['info', 'warning', 'success', 'critical'];
if (!in_array($type, $allowedTypes)) {
    $type = 'info';
}

try {
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO announcements (content, type, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$content, $type, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Announcement created successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error creating announcement: ' . $e->getMessage()]);
}
?>
