<?php
session_start();
require_once 'config.php';
require_once 'User.php';

header('Content-Type: application/json');

// Sadece giriş yapmış kullanıcılar erişebilir
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$newUsername = $_POST['new_username'] ?? '';

if (empty($newUsername)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Yeni kullanıcı adı boş olamaz.']);
    exit;
}

// Kullanıcı adı formatını kontrol et (daha katı kurallar)
if (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $newUsername)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı 3-20 karakter arasında olmalı ve sadece harf, sayı, alt çizgi (_) ve tire (-) içerebilir.']);
    exit;
}

// Özel durumları kontrol et (sadece tire veya alt çizgiden oluşuyorsa)
if (preg_match('/^[-_]+$/', $newUsername)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı sadece tire veya alt çizgiden oluşamaz.']);
    exit;
}

// Sayıyla başlayamaz (isteğe bağlı - kaldırabilirsiniz)
if (preg_match('/^\d/', $newUsername)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı sayı ile başlayamaz.']);
    exit;
}

try {
    $db = getDbConnection();
    $userModel = new User();

    // Mevcut kullanıcıyı al
    $currentUser = $userModel->findById($currentUserId);
    if (!$currentUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
        exit;
    }

    // Eğer kullanıcı adı aynıysa değişiklik yapma
    if ($currentUser['username'] === $newUsername) {
        echo json_encode(['success' => true, 'message' => 'Kullanıcı adınız zaten bu değer.']);
        exit;
    }

    // Yeni kullanıcı adının başka biri tarafından kullanılıp kullanılmadığını kontrol et
    $existingUser = $userModel->findByUsername($newUsername);
    if ($existingUser && $existingUser['id'] != $currentUserId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcı adı zaten kullanılıyor. Lütfen başka bir kullanıcı adı seçin.']);
        exit;
    }

    // Kullanıcı adını güncelle
    $stmt = $db->prepare("UPDATE users SET username = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newUsername, $currentUserId]);

    // Oturumdaki kullanıcı adını güncelle
    $_SESSION['username'] = $newUsername;

    // Kullanıcının tüm aktif oturumlarını güncellemek için (isteğe bağlı)
    $stmt = $db->prepare("UPDATE sessions SET username = ? WHERE user_id = ?");
    $stmt->execute([$newUsername, $currentUserId]);

    echo json_encode(['success' => true, 'message' => 'Kullanıcı adı başarıyla güncellendi. Yeni profil adresinize yönlendiriliyorsunuz...']);

} catch (PDOException $e) {
    error_log("Kullanıcı adı güncelleme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.']);
} catch (Exception $e) {
    error_log("Kullanıcı adı güncelleme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
}
?>
