<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['name'])) {
    echo json_encode(['success' => false, 'message' => 'Kategori adı gerekli.']);
    exit;
}

try {
    $db = getDbConnection();
    
    $name = trim($data['name']);
    $slug = $this->createSlug($name);
    $emoji = $data['emoji'] ?? '📁';
    $color = $data['color'] ?? '#6c757d';
    
    // Slug'un benzersiz olup olmadığını kontrol et
    $stmt = $db->prepare("SELECT id FROM flood_set_categories WHERE slug = ?");
    $stmt->execute([$slug]);
    
    if ($stmt->fetch()) {
        // Benzersiz slug oluştur
        $counter = 1;
        $originalSlug = $slug;
        
        while ($stmt->fetch()) {
            $slug = $originalSlug . '-' . $counter;
            $stmt->execute([$slug]);
            $counter++;
        }
    }
    
    // Kategoriyi oluştur
    $stmt = $db->prepare("
        INSERT INTO flood_set_categories (name, slug, emoji, color, description, sort_order)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $name,
        $slug,
        $emoji,
        $color,
        $data['description'] ?? '',
        $data['sort_order'] ?? 999
    ]);
    
    $categoryId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Kategori oluşturuldu!',
        'category_id' => $categoryId,
        'category_slug' => $slug
    ]);
    
} catch (Exception $e) {
    error_log("Kategori oluşturma hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Kategori oluşturulamadı: ' . $e->getMessage()
    ]);
}

// Slug oluşturma fonksiyonu
function createSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    
    // Türkçe karakterleri dönüştür
    $turkish = array('ş', 'Ş', 'ı', 'I', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç');
    $english = array('s', 's', 'i', 'i', 'i', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c');
    $text = str_replace($turkish, $english, $text);
    
    // Özel karakterleri temizle
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}
?>