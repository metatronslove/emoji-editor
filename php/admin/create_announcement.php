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

    $content = trim($_POST['content'] ?? '');
    $type = $_POST['type'] ?? 'info';

    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Duyuru içeriği boş olamaz']);
        exit;
    }

    if (!in_array($type, ['info', 'warning', 'success', 'critical'])) {
        $type = 'info';
    }

    $stmt = $db->prepare("
    INSERT INTO announcements (content, type, created_by, is_active)
    VALUES (?, ?, ?, 1)
    ");
    $stmt->execute([$content, $type, $_SESSION['user_id']]);

    // Log kaydı
    $log_stmt = $db->prepare("
    INSERT INTO admin_logs (admin_id, action, details)
    VALUES (?, 'create_announcement', ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        json_encode(['content' => $content, 'type' => $type])
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Duyuru başarıyla oluşturuldu'
    ]);

} catch (Exception $e) {
    error_log("Create announcement error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Duyuru oluşturulurken hata oluştu'
    ]);
}
