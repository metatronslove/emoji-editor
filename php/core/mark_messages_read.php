<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}

$userId = $_SESSION['user_id'];
$senderId = $_POST['sender_id'] ?? null;

if (!$senderId) {
    echo json_encode(['success' => false, 'message' => 'Gönderen ID eksik.']);
    exit;
}

try {
    $db = getDbConnection();

    $stmt = $db->prepare("UPDATE private_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $stmt->execute([$senderId, $userId]);

    echo json_encode(['success' => true, 'message' => 'Mesajlar okundu olarak işaretlendi.']);

} catch (PDOException $e) {
    error_log("Mesaj okundu işaretleme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
}
?>
