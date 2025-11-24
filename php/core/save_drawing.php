<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yönteme izin verilmiyor.']);
    exit;
}

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
$width = $data['width'] ?? 11;

$category = cleanCategoryName($rawCategory);
$userId = $_SESSION['user_id'] ?? null;

try {
    $db = getDbConnection();

    if ($userId == null) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Üye olmadan veri tabanına yazamazsın, üye ol'
        ]);
        exit;
    }

    // TÜM ÇİZİMLERİ AL VE PHP'DE KONTROL ET
    $checkStmt = $db->prepare("SELECT id, content FROM drawings");
    $checkStmt->execute();
    $allDrawings = $checkStmt->fetchAll();

    // Her bir çizimi kontrol et
    foreach ($allDrawings as $existingDrawing) {
        // drawingContent mevcut içerikte var mı?
        if (strpos($existingDrawing['content'], $drawingContent) !== false) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Bu çizim içeriği zaten kayıtlı! (ID: '.$existingDrawing['id'].')'
            ]);
            exit;
        }
    }

    // Yeni kayıt ekleme
    $stmt = $db->prepare("
    INSERT INTO drawings (user_id, content, first_row_length, width, category)
    VALUES (?, ?, ?, ?, ?)
    ");
    $success = $stmt->execute([
        $userId,
        $drawingContent,
        $firstRowLength,
        $width,
        $category
    ]);

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

function cleanCategoryName($category) {
    $cleaned = strip_tags($category);
    $cleaned = preg_replace('/[^a-zA-Z0-9ğüşıöçĞÜŞİÖÇ\s\-_]/u', '', $cleaned);
    if (empty(trim($cleaned))) {
        return 'Genel';
    }
    $cleaned = substr($cleaned, 0, 50);
    return trim($cleaned);
}
?>
