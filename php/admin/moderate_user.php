<?php
// admin/moderate_user.php
require_once '../config.php';
header('Content-Type: application/json');

$db = getDbConnection();
$userRole = $_SESSION['user_role'] ?? 'user';
$currentUserId = $_SESSION['user_id'] ?? null;

// Yetki kontrolü (Admin veya Moderatör)
if (!$currentUserId || !in_array($userRole, ['admin', 'moderator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz işlem.']);
    exit;
}

$targetUserId = $_POST['user_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$targetUserId || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler.']);
    exit;
}

try {
    // Kendini banlamayı/mute etmeyi engelle
    if ($targetUserId == $currentUserId) {
        throw new Exception("Kendi hesabınızı yönetemezsiniz.");
    }

    $updateField = '';
    $updateValue = '';

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
            $durationDays = (int)($_POST['duration'] ?? 7);
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
                throw new Exception("Rol değiştirme yetkiniz yok.");
            }
            $newRole = $_POST['new_role'] ?? 'user';
            if (!in_array($newRole, ['user', 'moderator'])) {
                throw new Exception("Geçersiz rol.");
            }
            $updateField = 'role';
            $updateValue = $newRole;
            break;
        default:
            throw new Exception("Geçersiz eylem.");
    }

    $sql = "UPDATE users SET {$updateField} = :value WHERE id = :id";
    $stmt = $db->prepare($sql);

    // PDO için NULL değeri kontrolü
    if ($updateValue === null) {
        $stmt->bindParam(':value', $updateValue, PDO::PARAM_NULL);
    } else {
        $stmt->bindParam(':value', $updateValue);
    }
    $stmt->bindParam(':id', $targetUserId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => "Kullanıcı başarıyla güncellendi."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
