<?php
// admin/moderate_social_platform.php
require_once '../config.php';
require_once '../functions.php';
session_start();
header('Content-Type: application/json');

$userRole = $_SESSION['user_role'] ?? 'user';
if ($userRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$platformId = intval($_POST['platform_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$platformId || !in_array($action, ['activate', 'deactivate', 'delete'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

try {
    $db = getDbConnection();

    switch ($action) {
        case 'activate':
            $stmt = $db->prepare("UPDATE social_media_platforms SET is_active = 1 WHERE id = ?");
            $stmt->execute([$platformId]);
            $message = "Platform activated.";
            break;

        case 'deactivate':
            $stmt = $db->prepare("UPDATE social_media_platforms SET is_active = 0 WHERE id = ?");
            $stmt->execute([$platformId]);
            $message = "Platform deactivated.";
            break;

        case 'delete':
            $stmt = $db->prepare("DELETE FROM social_media_platforms WHERE id = ?");
            $stmt->execute([$platformId]);
            $message = "Platform deleted.";
            break;
    }

    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to moderate platform: ' . $e->getMessage()]);
}
?>
