<?php
require_once 'config.php';
require_once 'functions.php';

// UTF-8 header'ını düzeltin
header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDbConnection();

    // UTF-8 bağlantısını garanti altına al
    $db->exec("SET NAMES 'utf8mb4'");
    $db->exec("SET CHARACTER SET utf8mb4");
    $db->exec("SET COLLATION_CONNECTION = 'utf8mb4_unicode_ci'");

    $stmt = $db->prepare("SELECT * FROM social_media_platforms WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // JSON_UNESCAPED_UNICODE flag'ini ekleyin
    echo json_encode(['success' => true, 'platforms' => $platforms], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Platform yükleme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Platformlar yüklenemedi: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
