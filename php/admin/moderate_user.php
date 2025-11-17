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

    $user_id = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? '';
    $duration = $_POST['duration'] ?? 7;
    $new_role = $_POST['new_role'] ?? '';

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı ID gerekli']);
        exit;
    }

    // Kendi kendini moderasyonu engelle
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Kendi hesabınızı yönetemezsiniz']);
        exit;
    }

    switch ($action) {
        case 'ban':
            $stmt = $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = 'Kullanıcı başarıyla yasaklandı';
            break;

        case 'unban':
            $stmt = $db->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = 'Kullanıcı yasağı kaldırıldı';
            break;

        case 'mute':
            $mute_until = date('Y-m-d H:i:s', strtotime("+$duration days"));
            $stmt = $db->prepare("UPDATE users SET comment_mute_until = ? WHERE id = ?");
            $stmt->execute([$mute_until, $user_id]);
            $message = "Kullanıcı $duration gün süreyle susturuldu";
            break;

        case 'unmute':
            $stmt = $db->prepare("UPDATE users SET comment_mute_until = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = 'Kullanıcı susturması kaldırıldı';
            break;

        case 'set_role':
            if (!in_array($new_role, ['user', 'moderator'])) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz rol']);
                exit;
            }
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            $message = "Kullanıcı rolü '$new_role' olarak güncellendi";
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
            exit;
    }

    // Log kaydı
    $log_stmt = $db->prepare("
    INSERT INTO admin_logs (admin_id, action, target_user_id, details)
    VALUES (?, ?, ?, ?)
    ");
    $log_stmt->execute([
        $_SESSION['user_id'],
        $action,
        $user_id,
        json_encode(['duration' => $duration, 'new_role' => $new_role])
    ]);

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    error_log("Moderate user error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İşlem sırasında hata oluştu: ' . $e->getMessage()
    ]);
}
