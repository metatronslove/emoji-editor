<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

$page = $_GET['page'] ?? 1;
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$itemsPerPage = 12;

try {
    $db = getDbConnection();
    
    // BASE QUERY
    $sql = "SELECT fs.*, u.username as author_username, u.profile_picture as author_profile_picture 
            FROM flood_sets fs 
            JOIN users u ON fs.user_id = u.id 
            WHERE fs.is_public = 1";
    
    // FILTRELER
    if ($filter === 'following' && Auth::isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        $sql .= " AND fs.user_id IN (SELECT following_id FROM follows WHERE follower_id = $userId)";
    }
    
    // SIRALAMA
    $orderBy = "ORDER BY fs.created_at DESC"; // default
    switch ($sort) {
        case 'popular':
            $orderBy = "ORDER BY fs.views DESC, fs.likes DESC";
            break;
        case 'most_messages':
            $orderBy = "ORDER BY fs.message_count DESC";
            break;
        case 'newest':
        default:
            $orderBy = "ORDER BY fs.created_at DESC";
    }
    
    // TOPLAM SAYI
    $countSql = str_replace("SELECT fs.*, u.username", "SELECT COUNT(*) as total", $sql);
    $stmt = $db->query($countSql);
    $totalItems = $stmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    // SAYFALAMA
    $offset = ($page - 1) * $itemsPerPage;
    $sql .= " $orderBy LIMIT $itemsPerPage OFFSET $offset";
    
    // ÇALIŞTIR
    $stmt = $db->query($sql);
    $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'sets' => $sets,
        'currentPage' => (int)$page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veriler getirilemedi: ' . $e->getMessage()
    ]);
}
?>