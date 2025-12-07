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
    $db = getDbConnection();
    
    $stmt = $db->query("SELECT * FROM flood_set_categories ORDER BY sort_order, name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Eğer tablo yoksa veya boşsa, fallback kategoriler
    if (!$categories) {
        $categories = [
            ['id' => 1, 'slug' => 'genel', 'name' => 'Genel', 'emoji' => '📁', 'color' => '#6c757d'],
            ['id' => 2, 'slug' => 'youtube', 'name' => 'YouTube', 'emoji' => '📺', 'color' => '#FF0000'],
            ['id' => 3, 'slug' => 'twitch', 'name' => 'Twitch', 'emoji' => '🔴', 'color' => '#9146FF'],
            ['id' => 4, 'slug' => 'eglence', 'name' => 'Eğlence', 'emoji' => '😂', 'color' => '#FFC107'],
            ['id' => 5, 'slug' => 'oyun', 'name' => 'Oyun', 'emoji' => '🎮', 'color' => '#28a745'],
            ['id' => 6, 'slug' => 'sevgi', 'name' => 'Sevgi', 'emoji' => '❤️', 'color' => '#e83e8c'],
            ['id' => 7, 'slug' => 'sanat', 'name' => 'Sanat', 'emoji' => '🎨', 'color' => '#6f42c1'],
            ['id' => 8, 'slug' => 'gunluk', 'name' => 'Günlük', 'emoji' => '📝', 'color' => '#17a2b8']
        ];
    }
    
    $formatted = [];
    foreach ($categories as $cat) {
        $formatted[$cat['slug']] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'slug' => $cat['slug'],
            'emoji' => $cat['emoji'],
            'color' => $cat['color'],
            'description' => $cat['description'] ?? ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $formatted
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Kategoriler yüklenemedi: ' . $e->getMessage()
    ]);
}
?>