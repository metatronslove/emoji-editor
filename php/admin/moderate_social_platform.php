<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
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

    $platform_id = $_POST['platform_id'] ?? null;
    $action = $_POST['action'] ?? '';

    if (!$platform_id) {
        echo json_encode(['success' => false, 'message' => 'Platform ID gerekli']);
        exit;
    }

    switch ($action) {
        case 'activate':
            $stmt = $db->prepare("UPDATE social_platforms SET is_active = 1 WHERE id = ?");
            $message = 'Platform aktif edildi';
            break;

        case 'deactivate':
            $stmt = $db->prepare("UPDATE social_platforms SET is_active = 0 WHERE id = ?");
            $message = 'Platform pasif edildi';
            break;

        case 'delete':
            $stmt = $db->prepare("DELETE FROM social_platforms WHERE id = ?");
            $message = 'Platform silindi';
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
            exit;
    }

    $stmt->execute([$platform_id]);

    if ($stmt->rowCount() > 0) {
        // Log kaydı
        $log_stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, details)
        VALUES (?, ?, ?)
        ");
        $log_stmt->execute([
            $_SESSION['user_id'],
            $action . '_social_platform',
            json_encode(['platform_id' => $platform_id])
        ]);

        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Platform bulunamadı']);
    }

} catch (Exception $e) {
    error_log("Moderate social platform error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İşlem sırasında hata oluştu'
    ]);
}
