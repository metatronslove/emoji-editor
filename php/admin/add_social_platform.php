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

    $name = trim($_POST['name'] ?? '');
    $emoji = trim($_POST['emoji'] ?? '');
    $regex = trim($_POST['regex'] ?? '');

    if (empty($name) || empty($emoji)) {
        echo json_encode(['success' => false, 'message' => 'Platform adı ve emoji gerekli']);
        exit;
    }

    // Platform var mı kontrol et
    $check_stmt = $db->prepare("SELECT id FROM social_platforms WHERE name = ?");
    $check_stmt->execute([$name]);

    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu platform zaten mevcut']);
        exit;
    }

    $stmt = $db->prepare("
    INSERT INTO social_platforms (name, emoji, url_regex, is_active, created_at)
    VALUES (?, ?, ?, 1, NOW())
    ");
    $stmt->execute([$name, $emoji, $regex]);

    // Log kaydı
    $log_stmt = $db->prepare("
    INSERT INTO admin_logs (admin_id, action, details)
    VALUES (?, 'add_social_platform', ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        json_encode(['name' => $name, 'emoji' => $emoji, 'regex' => $regex])
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Sosyal medya platformu eklendi'
    ]);

} catch (Exception $e) {
    error_log("Add social platform error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Platform eklenirken hata oluştu'
    ]);
}
