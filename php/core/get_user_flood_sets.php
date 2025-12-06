<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

$userId = $_GET['user_id'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID gerekli']);
    exit;
}

try {
    $db = getDbConnection();
    
    // Kullanıcı bilgilerini kontrol et
    $stmt = $db->prepare("SELECT privacy_mode FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $profileUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profileUser) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }
    
    $isProfileOwner = ($currentUserId == $userId);
    $isProfilePrivate = ($profileUser['privacy_mode'] === 'private');
    
    // Gizli profil kontrolü
    if ($isProfilePrivate && !$isProfileOwner && $currentUserId) {
        // Takip kontrolü
        $stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$currentUserId, $userId]);
        $isFollowing = $stmt->fetchColumn();
        
        if (!$isFollowing) {
            echo json_encode(['success' => false, 'message' => 'Bu gizli profilin flood set\'lerini görmek için takipçi olmalısınız']);
            exit;
        }
    } elseif ($isProfilePrivate && !$currentUserId) {
        echo json_encode(['success' => false, 'message' => 'Bu gizli profilin flood set\'lerini görmek için giriş yapmalısınız']);
        exit;
    }
    
    // Flood set'lerini getir
    $sql = "SELECT 
                fs.*,
                u.username as author_username,
                u.profile_picture as author_profile_picture,
                COALESCE(fc.name, 'Genel') as category_name,
                COALESCE(fc.emoji, '📁') as category_emoji,
                COALESCE(fc.color, '#6c757d') as category_color
            FROM flood_sets fs
            LEFT JOIN users u ON fs.user_id = u.id
            LEFT JOIN flood_set_categories fc ON fs.category = fc.slug
            WHERE fs.user_id = ? AND fs.is_visible = TRUE";
    
    // Profil sahibi değilse sadece herkese açık set'leri göster
    if (!$isProfileOwner) {
        $sql .= " AND fs.is_public = TRUE";
    }
    
    $sql .= " ORDER BY 
                CASE WHEN fs.featured = TRUE THEN 0 ELSE 1 END,
                fs.updated_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId]);
    $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mesaj sayılarını güncelle (cache için)
    foreach ($sets as &$set) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM flood_messages WHERE set_id = ?");
        $stmt->execute([$set['id']]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $set['message_count'] = $count['count'];
        
        // Kategori bilgisini düzenle
        if (empty($set['category'])) {
            $set['category'] = 'genel';
        }
    }
    
    echo json_encode([
        'success' => true,
        'sets' => $sets,
        'can_view' => true,
        'is_owner' => $isProfileOwner
    ]);
    
} catch (Exception $e) {
    error_log("Flood set getirme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Flood set\'leri yüklenirken hata oluştu'
    ]);
}
?>