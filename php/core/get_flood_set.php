<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/counter_manager.php';
require_once __DIR__ . '/../classes/Drawing.php';
require_once __DIR__ . '/../classes/Router.php';
header('Content-Type: application/json');

$setId = $_GET['id'] ?? 0;

if (!$setId) {
    echo json_encode(['success' => false, 'message' => 'Set ID gerekli.']);
    exit;
}

try {
    $db = getDbConnection();
    $currentUserId = $_SESSION['user_id'] ?? null;
    
    // Set bilgilerini getir
    $stmt = $db->prepare("
        SELECT 
            fs.*,
            u.username as author_username,
            u.profile_picture as author_profile_picture,
            fc.name as category_name,
            fc.emoji as category_emoji,
            fc.color as category_color
        FROM flood_sets fs
        LEFT JOIN users u ON fs.user_id = u.id
        LEFT JOIN flood_set_categories fc ON fs.category = fc.slug
        WHERE fs.id = ?
    ");
    $stmt->execute([$setId]);
    $set = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$set) {
        echo json_encode(['success' => false, 'message' => 'Set bulunamadı.']);
        exit;
    }
    
    // Erişim kontrolü
    $isOwner = ($currentUserId == $set['user_id']);
    $isPublic = ($set['is_public'] == 1);
    $isVisible = ($set['is_visible'] == 1);
    
    if (!$isVisible) {
        echo json_encode(['success' => false, 'message' => 'Bu set görünür değil.']);
        exit;
    }
    
    if (!$isOwner && !$isPublic) {
        // Takip kontrolü (gizli setler için)
        if ($currentUserId) {
            $stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
            $stmt->execute([$currentUserId, $set['user_id']]);
            $isFollowing = $stmt->fetchColumn();
            
            if (!$isFollowing) {
                echo json_encode(['success' => false, 'message' => 'Bu set\'e erişim izniniz yok.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Bu set\'e erişim izniniz yok.']);
            exit;
        }
    }
    
    // Mesaj sayısını getir
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM flood_messages WHERE set_id = ?");
    $stmt->execute([$setId]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    $set['message_count'] = $count['count'];
    
    // View sayısını artır (sadece sahip değilse)
    if (!$isOwner) {
        $stmt = $db->prepare("UPDATE flood_sets SET views = views + 1 WHERE id = ?");
        $stmt->execute([$setId]);
    }
    
    echo json_encode([
        'success' => true,
        'set' => $set,
        'can_edit' => $isOwner
    ]);
    
} catch (Exception $e) {
    error_log("Flood set getirme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Set getirilemedi: ' . $e->getMessage()
    ]);
}
?>