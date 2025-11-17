<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread_count' => 0]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as unread_count FROM private_messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['unread_count' => $result['unread_count'] ?? 0]);
} catch (PDOException $e) {
    echo json_encode(['unread_count' => 0]);
}
?>
