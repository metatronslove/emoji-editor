<?php
// core/fetch_following_feed.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

$currentUserId = $_SESSION['user_id'] ?? null;
if (!$currentUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to view your following feed.']);
    exit;
}

try {
    $db = getDbConnection();
    $LIMIT = 15; // Her tür için limit

    // 1. TAKİP ETTİKLERİMİN SON ÇİZİMLERİ
    $drawingsQuery = $db->prepare("
        SELECT 
            d.id, 
            d.content, 
            d.first_row_length, 
            d.width, 
            d.updated_at, 
            d.category,
            d.likes,
            d.views,
            u.username AS author_username, 
            u.profile_picture AS author_profile_picture, 
            u.id AS author_id 
        FROM drawings d 
        INNER JOIN users u ON d.user_id = u.id 
        WHERE d.user_id IN (
            SELECT following_id 
            FROM follows 
            WHERE follower_id = :current_user_id
        ) 
        AND d.user_id NOT IN (
            SELECT blocked_id 
            FROM blocks 
            WHERE blocker_id = :current_user_id_block 
            UNION 
            SELECT blocker_id 
            FROM blocks 
            WHERE blocked_id = :current_user_id_blocked
        ) 
        AND d.is_visible = TRUE 
        ORDER BY d.updated_at DESC 
        LIMIT :limit
    ");

    $drawingsQuery->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
    $drawingsQuery->bindParam(':current_user_id_block', $currentUserId, PDO::PARAM_INT);
    $drawingsQuery->bindParam(':current_user_id_blocked', $currentUserId, PDO::PARAM_INT);
    $drawingsQuery->bindParam(':limit', $LIMIT, PDO::PARAM_INT);
    $drawingsQuery->execute();
    $drawings = $drawingsQuery->fetchAll(PDO::FETCH_ASSOC);

    // 2. TAKİP ETTİKLERİMİN SON FLOOD SET'LERİ
    $floodSetsQuery = $db->prepare("
        SELECT 
            fs.id,
            fs.name,
            fs.description,
            fs.is_public,
            fs.message_count,
            fs.views,
            fs.likes,
            fs.copy_count,
            fs.created_at,
            fs.updated_at,
            u.username AS author_username,
            u.profile_picture AS author_profile_picture,
            u.id AS author_id
        FROM flood_sets fs
        INNER JOIN users u ON fs.user_id = u.id
        WHERE fs.user_id IN (
            SELECT following_id 
            FROM follows 
            WHERE follower_id = :current_user_id2
        ) 
        AND fs.user_id NOT IN (
            SELECT blocked_id 
            FROM blocks 
            WHERE blocker_id = :current_user_id_block2 
            UNION 
            SELECT blocker_id 
            FROM blocks 
            WHERE blocked_id = :current_user_id_blocked2
        ) 
        AND fs.is_public = TRUE
        ORDER BY fs.updated_at DESC 
        LIMIT :limit2
    ");

    $floodSetsQuery->bindParam(':current_user_id2', $currentUserId, PDO::PARAM_INT);
    $floodSetsQuery->bindParam(':current_user_id_block2', $currentUserId, PDO::PARAM_INT);
    $floodSetsQuery->bindParam(':current_user_id_blocked2', $currentUserId, PDO::PARAM_INT);
    $floodSetsQuery->bindParam(':limit2', $LIMIT, PDO::PARAM_INT);
    $floodSetsQuery->execute();
    $flood_sets = $floodSetsQuery->fetchAll(PDO::FETCH_ASSOC);

    // 3. TAKİP ETTİKLERİMİN SON AKTİVİTELERİ (birleşik)
    $activities = [];
    
    // Çizimleri aktivite olarak ekle
    foreach ($drawings as $drawing) {
        $activities[] = [
            'type' => 'drawing',
            'id' => $drawing['id'],
            'content' => $drawing['content'],
            'author_username' => $drawing['author_username'],
            'author_profile_picture' => $drawing['author_profile_picture'],
            'timestamp' => $drawing['updated_at'],
            'category' => $drawing['category'],
            'likes' => $drawing['likes'],
            'views' => $drawing['views']
        ];
    }
    
    // Flood set'lerini aktivite olarak ekle
    foreach ($flood_sets as $flood_set) {
        $activities[] = [
            'type' => 'flood_set',
            'id' => $flood_set['id'],
            'name' => $flood_set['name'],
            'description' => $flood_set['description'],
            'author_username' => $flood_set['author_username'],
            'author_profile_picture' => $flood_set['author_profile_picture'],
            'timestamp' => $flood_set['updated_at'],
            'message_count' => $flood_set['message_count'],
            'likes' => $flood_set['likes'],
            'views' => $flood_set['views']
        ];
    }
    
    // Zaman sırasına göre sırala (en yeni üstte)
    usort($activities, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });

    // İlk 15 aktiviteyi al
    $activities = array_slice($activities, 0, 15);

    echo json_encode([
        'success' => true,
        'drawings' => $drawings,
        'flood_sets' => $flood_sets,
        'activities' => $activities,
        'message' => 'Following feed loaded successfully.'
    ]);

} catch (PDOException $e) {
    error_log("Database error in fetch_following_feed.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred.'
    ]);
} catch (Exception $e) {
    error_log("General error in fetch_following_feed.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while loading the feed.'
    ]);
}
?>