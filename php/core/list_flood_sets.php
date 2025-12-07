<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/counter_manager.php';
require_once __DIR__ . '/../classes/Drawing.php';
require_once __DIR__ . '/../classes/Router.php';
header('Content-Type: application/json');

try {
    $db = Database::getConnection();
    
    // Sayfalama parametreleri
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 12;
    $offset = ($page - 1) * $limit;
    
    // Filtre parametreleri
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    
    // Sorgu oluştur
    $query = "SELECT fs.*, 
                     u.username as author_username,
                     u.profile_picture as author_profile_picture,
                     COUNT(fm.id) as message_count,
                     fc.name as category_name,
                     fc.emoji as category_emoji
              FROM flood_sets fs
              LEFT JOIN users u ON fs.user_id = u.id
              LEFT JOIN flood_messages fm ON fs.id = fm.set_id
              LEFT JOIN flood_categories fc ON fs.category = fc.slug
              WHERE fs.is_public = 1 AND fs.is_active = 1";
    
    // Kategori filtresi
    if ($category !== 'all') {
        $query .= " AND fs.category = :category";
    }
    
    $query .= " GROUP BY fs.id";
    
    // Sıralama
    switch ($sort) {
        case 'popular':
            $query .= " ORDER BY fs.views DESC, fs.likes DESC";
            break;
        case 'most_messages':
            $query .= " ORDER BY message_count DESC";
            break;
        case 'newest':
        default:
            $query .= " ORDER BY fs.created_at DESC";
            break;
    }
    
    // Toplam sayfa için count
    $countQuery = "SELECT COUNT(DISTINCT fs.id) as total 
                   FROM flood_sets fs 
                   WHERE fs.is_public = 1 AND fs.is_active = 1";
    
    if ($category !== 'all') {
        $countQuery .= " AND fs.category = :category";
    }
    
    $stmt = $db->prepare($countQuery);
    if ($category !== 'all') {
        $stmt->bindValue(':category', $category);
    }
    $stmt->execute();
    $total = $stmt->fetchColumn();
    $totalPages = ceil($total / $limit);
    
    // Verileri getir
    $query .= " LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);
    
    if ($category !== 'all') {
        $stmt->bindValue(':category', $category);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'sets' => $sets,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'total_sets' => $total
    ]);
    
} catch (Exception $e) {
    error_log("Flood set listeleme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Flood set\'leri yüklenirken hata oluştu.'
    ]);
}

?>