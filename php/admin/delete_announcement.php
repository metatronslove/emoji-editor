<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || !in_array($_SESSION['user_role'], ['admin', 'moderator'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek methodu']);
    exit;
}

try {
    $db = getDbConnection();

    $announcement_id = $_POST['announcement_id'] ?? null;

    if (!$announcement_id) {
        echo json_encode(['success' => false, 'message' => 'Duyuru ID gerekli']);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$announcement_id]);

    if ($stmt->rowCount() > 0) {
        // Log kaydı
        $log_stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, details)
        VALUES (?, 'delete_announcement', ?)
        ");
        $log_stmt->execute([
            $_SESSION['user_id'],
            json_encode(['announcement_id' => $announcement_id])
        ]);

        echo json_encode(['success' => true, 'message' => 'Duyuru silindi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Duyuru bulunamadı']);
    }

} catch (Exception $e) {
    error_log("Delete announcement error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Duyuru silinirken hata oluştu'
    ]);
}
