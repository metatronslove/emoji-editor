<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$targetType = $_GET['type'] ?? null;
$targetId = $_GET['id'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$targetType || !$targetId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler.']);
    exit;
}

try {
    $db = getDbConnection();

    // Profil yorumları için gizlilik kontrolleri
    if ($targetType === 'profile') {
        // Profil bilgilerini al
        $profileStmt = $db->prepare("SELECT id, privacy_mode FROM users WHERE id = ?");
        $profileStmt->execute([$targetId]);
        $profileUser = $profileStmt->fetch();

        if (!$profileUser) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Profil bulunamadı.']);
            exit;
        }

        $isProfileOwner = ($currentUserId == $profileUser['id']);
        $isProfilePrivate = ($profileUser['privacy_mode'] === 'private');
        $canViewComments = true;

        // Gizlilik kontrolleri
        if (!$isProfileOwner && $isProfilePrivate) {
            // Gizli profil - sadece takipçiler görebilir
            if ($currentUserId) {
                $followStmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
                $followStmt->execute([$currentUserId, $targetId]);
                $isFollowing = $followStmt->fetch();
                $canViewComments = $isFollowing;
            } else {
                $canViewComments = false;
            }
        }

        // Engelleme kontrolü
        if ($currentUserId && !$isProfileOwner) {
            $blockStmt = $db->prepare("SELECT 1 FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
            $blockStmt->execute([$currentUserId, $targetId, $targetId, $currentUserId]);
            $isBlocked = $blockStmt->fetch();

            if ($isBlocked) {
                $canViewComments = false;
            }
        }

        if (!$canViewComments) {
            echo json_encode([
                'success' => true,
                'comments' => [],
                'access_denied' => true,
                'message' => 'Bu gizli profilin panosunu görmek için takipçi olmalısınız'
            ]);
            exit;
        }
    }

    // Yönetici ve moderatör kontrolleri
    $isAdmin = false;
    $isModerator = false;

    if ($currentUserId) {
        $userStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $userStmt->execute([$currentUserId]);
        $user = $userStmt->fetch();
        $isAdmin = ($user['role'] === 'admin');
        $isModerator = ($user['role'] === 'moderator');
    }

    // Yorumları getir
    $query = "
    SELECT c.*, u.username, u.profile_picture, u.id as user_id
    FROM comments c
    JOIN users u ON c.commenter_id = u.id
    WHERE c.target_type = ? AND c.target_id = ?
    ";

    // Silinmiş yorumları sadece yönetici/moderatör ve yorum sahibi görebilir
    if (!$isAdmin && !$isModerator) {
        $query .= " AND (c.is_visible = 1 OR c.commenter_id = ?)";
        $params = [$targetType, $targetId, $currentUserId];
    } else {
        $params = [$targetType, $targetId];
    }

    $query .= " ORDER BY c.created_at DESC LIMIT 50";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $comments = $stmt->fetchAll();

    // Profil fotoğraflarını düzenle ve ek bilgileri işle
    foreach ($comments as &$comment) {
        // Profil fotoğrafı
        if (!empty($comment['profile_picture']) && $comment['profile_picture'] !== 'default.png') {
            $comment['profile_picture'] = 'data:image/jpeg;base64,' . $comment['profile_picture'];
        } else {
            $comment['profile_picture'] = '/images/default.png';
        }

        // Görünürlük bilgisi
        $comment['can_delete'] = ($currentUserId == $comment['commenter_id'] || $isAdmin || $isModerator);
        $comment['is_owner'] = ($currentUserId == $comment['commenter_id']);
    }

    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'current_user_id' => $currentUserId,
        'is_admin' => $isAdmin,
        'is_moderator' => $isModerator,
        'can_view' => true
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Yorumlar getirme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
