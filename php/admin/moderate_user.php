<?php
// admin/moderate_user.php - DÜZELTİLDİ
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
session_start();
header('Content-Type: application/json');

$currentUserId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? 'user';

if (!$currentUserId || !in_array($userRole, ['admin', 'moderator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden.']);
    exit;
}

$targetUserId = intval($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$targetUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing user ID.']);
    exit;
}

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing action.']);
    exit;
}

// Kendi üzerinde işlem yapmayı engelle
if ($targetUserId == $currentUserId) {
    echo json_encode(['success' => false, 'message' => 'Cannot operate on your own account.']);
    exit;
}

try {
    $db = getDbConnection();

    switch ($action) {
        case 'ban':
            $stmt = $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $message = "User banned successfully.";
            break;

        case 'unban':
            $stmt = $db->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $message = "User unbanned successfully.";
            break;

        case 'mute':
            $duration = intval($_POST['duration'] ?? 7);
            $muteUntil = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
            $stmt = $db->prepare("UPDATE users SET comment_mute_until = ? WHERE id = ?");
            $stmt->execute([$muteUntil, $targetUserId]);
            $message = "User muted for {$duration} days.";
            break;

        case 'unmute':
            $stmt = $db->prepare("UPDATE users SET comment_mute_until = NULL WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $message = "User unmuted successfully.";
            break;

        case 'set_role':
            if ($userRole !== 'admin') {
                throw new Exception("Only admins can change roles.");
            }
            $newRole = in_array($_POST['new_role'], ['user', 'moderator']) ? $_POST['new_role'] : 'user';
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $targetUserId]);
            $message = "User role updated to {$newRole}.";
            break;

        default:
            throw new Exception("Invalid action.");
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
