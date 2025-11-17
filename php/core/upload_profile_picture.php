<?php
// upload_profile_picture.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Sadece POST isteği kabul edilir.']);
    exit;
}

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Resim yüklenirken hata oluştu.']);
    exit;
}

$uploadedFile = $_FILES['profile_picture'];
$userId = $_SESSION['user_id'];

// Dosya boyutu kontrolü (max 2MB)
if ($uploadedFile['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Resim boyutu 2MB\'dan küçük olmalıdır.']);
    exit;
}

// Dosya tipi kontrolü
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$fileType = mime_content_type($uploadedFile['tmp_name']);
if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Sadece JPG, PNG ve GIF formatları desteklenir.']);
    exit;
}

// Resmi boyutlandır ve kaydet
$result = resizeAndSaveProfilePicture($uploadedFile, $userId);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Profil resmi başarıyla güncellendi.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Profil resmi güncellenirken hata oluştu.']);
}
?>
