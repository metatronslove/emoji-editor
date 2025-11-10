<?php
require_once 'config.php'; // Oturum, DB bağlantı fonksiyonu ve Composer yüklemesi
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
$category = $data['category'] ?? 'Genel'; // JS'ten kategori bilgisi gelmezse 'Genel' kullan

// 2. Kullanıcı kimliğini belirle
$userId = $_SESSION['user_id'] ?? null; // Oturum açmışsa user_id, yoksa NULL (Anonim)

try {
    $db = getDbConnection(); // config.php'deki bağlantı fonksiyonu

    // Yeni kayıt ekleme sorgusu
    $stmt = $db->prepare("
        INSERT INTO drawings (user_id, content, category)
        VALUES (?, ?, ?)
    ");

    $success = $stmt->execute([$userId, $drawingContent, $category]);

    if ($success) {
        $drawingId = $db->lastInsertId();
        http_response_code(201); // Created
        echo json_encode([
            'success' => true,
            'message' => 'Çizim başarıyla kaydedildi.',
            'id' => $drawingId
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
?>
