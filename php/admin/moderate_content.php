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

    $content_id = $_POST['content_id'] ?? null;
    $content_type = $_POST['content_type'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!$content_id || !in_array($content_type, ['drawing', 'comment'])) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler']);
        exit;
    }

    $table = $content_type === 'drawing' ? 'drawings' : 'comments';
    $visibility_value = $action === 'hide' ? 0 : 1;

    $stmt = $db->prepare("UPDATE $table SET is_visible = ? WHERE id = ?");
    $stmt->execute([$visibility_value, $content_id]);

    if ($stmt->rowCount() > 0) {
        // Log kaydı
        $log_stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, target_content_id, content_type, details)
        VALUES (?, ?, ?, ?, ?)
        ");
        $log_stmt->execute([
            $_SESSION['user_id'],
            $action,
            $content_id,
            $content_type,
            json_encode(['visibility' => $visibility_value])
        ]);

        $message = $action === 'hide' ? 'İçerik gizlendi' : 'İçerik görünür yapıldı';
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'İçerik bulunamadı']);
    }

} catch (Exception $e) {
    error_log("Moderate content error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İşlem sırasında hata oluştu'
    ]);
}
