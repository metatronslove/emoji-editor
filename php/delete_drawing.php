<?php
// delete_drawing.php
require_once 'config.php';
header('Content-Type: application/json');

// Sadece POST isteği kabul edilir
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);
$drawingId = $input['drawing_id'] ?? null;

if (!$drawingId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Çizim ID eksik']);
    exit;
}

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız']);
    exit;
}

try {
    $db = getDbConnection();

    // Önce çizimi ve sahibini kontrol et
    $stmt = $db->prepare("SELECT user_id FROM drawings WHERE id = ?");
    $stmt->execute([$drawingId]);
    $drawing = $stmt->fetch();

    if (!$drawing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Çizim bulunamadı']);
        exit;
    }

    $currentUserId = $_SESSION['user_id'];
    $currentUserRole = $_SESSION['role'] ?? 'user';

    // Yetki kontrolü: sadece çizimin sahibi veya admin silebilir
    if ($drawing['user_id'] != $currentUserId && $currentUserRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu çizimi silme yetkiniz yok']);
        exit;
    }

    // Çizimi sil
    $deleteStmt = $db->prepare("DELETE FROM drawings WHERE id = ?");
    $success = $deleteStmt->execute([$drawingId]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Çizim başarıyla silindi']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Çizim silinirken hata oluştu']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
