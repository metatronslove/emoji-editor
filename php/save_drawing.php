<?php
require_once 'config.php';
// Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yönteme izin verilmiyor.']);
    exit;
}

// 1. Gelen JSON verisini al
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['drawingContent']) || empty($data['drawingContent'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Çizim içeriği eksik.']);
    exit;
}

$drawingContent = $data['drawingContent'];
$rawCategory = $data['category'] ?? 'Genel';
$firstRowLength = isset($data['firstRowLength']) ? (int)$data['firstRowLength'] : 6;
$width = $data['width'] ?? 11; // Frontend'den genişlik bilgisini al

// 2. Kategori güvenliği
$category = cleanCategoryName($rawCategory);

// 3. Kullanıcı kimliğini belirle
$userId = $_SESSION['user_id'] ?? null;

try {
    $db = getDbConnection();

    // 4. MÜKERRER KAYIT KONTROLÜ - Aynı içerik var mı?
    $checkStmt = $db->prepare("
    SELECT id FROM drawings
    WHERE content = ? AND (user_id = ? OR (? IS NULL AND user_id IS NULL))
    LIMIT 1
    ");
    $checkStmt->execute([$drawingContent, $userId, $userId]);
    $existingDrawing = $checkStmt->fetch();

    if ($existingDrawing) {
        http_response_code(409); // Conflict
        echo json_encode([
            'success' => false,
            'message' => 'Bu çizim zaten kayıtlı!'
        ]);
        exit;
    }

    // 5. Yeni kayıt ekleme
    $stmt = $db->prepare("
    INSERT INTO drawings (user_id, content, first_row_length, width, category)
    VALUES (?, ?, ?, ?, ?)
    ");
    $success = $stmt->execute([$userId, $drawingContent, $firstRowLength, $width, $category]);

    if ($success) {
        $drawingId = $db->lastInsertId();
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Çizim başarıyla kaydedildi.',
            'id' => $drawingId,
            'category' => $category
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Veritabanına kayıt sırasında hata oluştu.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}

/**
 * Kategori ismini temizle
 */
function cleanCategoryName($category) {
    // HTML taglarını temizle
    $cleaned = strip_tags($category);

    // Özel karakterleri filtrele
    $cleaned = preg_replace('/[^a-zA-Z0-9ğüşıöçĞÜŞİÖÇ\s\-_]/u', '', $cleaned);

    // Boşsa varsayılan değer
    if (empty(trim($cleaned))) {
        return 'Genel';
    }

    // Maksimum uzunluk
    $cleaned = substr($cleaned, 0, 50);

    return trim($cleaned);
}
?>
