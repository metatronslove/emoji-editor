<?php
require_once '../config.php';
require_once '../functions.php';
header('Content-Type: application/json');

$userRole = $_SESSION['user_role'] ?? 'user';
if (!in_array($userRole, ['admin', 'moderator'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
    exit;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (!isset($_POST['content']) || !isset($_POST['type'])) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

$content = sanitizeInput($_POST['content']);
$type = sanitizeInput($_POST['type']);

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Announcement content cannot be empty.']);
    exit;
}

try {
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO announcements (content, type, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $content, $type, $_SESSION['user_id']);
    $result = $stmt->execute();

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Announcement created successfully.']);
    } else {
        throw new Exception("Error creating announcement: " . $db->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error creating announcement: ' . $e->getMessage()]);
}
?>
