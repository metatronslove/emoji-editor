<?php
// admin/moderate_user.php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
session_start(); // Added session_start() to start the session before using $_SESSION

$db = getDbConnection();

$currentUserId = intval($_SESSION['user_id'] ?? null);
$userRole = strtolower(trim($_SESSION['user_role'] ?? 'user'));

if (!$currentUserId || !in_array($userRole, ['admin', 'moderator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden.']);
    exit;
}

$targetUserId = intval($_POST['user_id'] ?? null);
if (!$targetUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameter user_id.']);
    exit;
}

$action = strtolower(trim($_POST['action'] ?? null));
if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameter action.']);
    exit;
}

if ($targetUserId == $currentUserId && in_array($action, ['ban', 'mute', 'set_role'])) {
    echo json_encode(['success' => false, 'message' => 'Cannot operate on your own account.']);
    exit;
}

$csrfToken = trim(filter_input(INPUT_POST, 'csrf_token') ?? null);
if (!$csrfToken || !verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF protection failed.']);
    exit;
}

function verifyCsrfToken($token)
{
    $sessionToken = $_SESSION['csrf_token'] ?? null;
    return hash_equals($token, $sessionToken);
}

try {
    // Kendini banlamayı/mute etmeyi engelle
    if ($targetUserId == $currentUserId) {
        throw new Exception("Cannot operate on your own account.");
    }

    $updateField = '';
    $updateValue = null;

    switch ($action) {
        case 'ban':
            $updateField = 'is_banned';
            $updateValue = 1;
            break;
        case 'unban':
            $updateField = 'is_banned';
            $updateValue = 0;
            break;
        case 'mute':
            // Yorum yasağı koyma (Gelen süreye göre hesapla)
            $durationDays = intval($_POST['duration'] ?? 7);
            $muteUntil = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
            $updateField = 'comment_mute_until';
            $updateValue = $muteUntil;
            break;
        case 'unmute':
            $updateField = 'comment_mute_until';
            $updateValue = null;
            break;
        case 'set_role':
            // Sadece Admin rol değiştirebilir
            if ($userRole !== 'admin') {
                throw new Exception("Role change permission denied.");
            }
            $newRole = strtolower(trim($_POST['new_role'] ?? 'user'));
            if (!in_array($newRole, ['user', 'moderator'])) {
                throw new Exception("Invalid role.");
            }
            $updateField = 'role';
            $updateValue = $newRole;
            break;
        default:
            throw new Exception("Invalid action.");
    }

    // Update user in database
    $stmt = $db->prepare("UPDATE users SET {$updateField} = :value WHERE id = :id");
    $stmt->bindParam(':value', $updateValue, PDO::PARAM_INT);
    $stmt->bindParam(':id', $targetUserId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => "User updated successfully."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>