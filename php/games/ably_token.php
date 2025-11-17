<?php
// games/ably_token.php - DÜZELTİLMİŞ VERSİYON

// TÜM hata ve çıktıları kapat
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

try {
    require_once __DIR__ . '/../config.php';

    // Ably SDK'sını manuel olarak yükle - DOĞRU YOL
    require_once __DIR__ . '/../vendor/autoload.php';

    // Session başlat
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Header'ları ayarla
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    // CORS preflight isteği
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        ob_end_clean();
        exit(0);
    }

    // Oturum kontrolü
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        ob_end_clean();
        echo json_encode(['error' => 'Oturum yok']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $clientId = 'user_' . $userId;

    // API key kontrolü
    if (!defined('ABLY_API_KEY') || empty(ABLY_API_KEY)) {
        throw new Exception('ABLY_API_KEY tanımlı değil');
    }

    // Ably SDK'sını başlat
    $ably = new Ably\AblyRest(['key' => ABLY_API_KEY]);

    // Token oluştur
    $tokenDetails = $ably->auth->requestToken([
        'clientId' => $clientId,
        'ttl' => 3600000 // 1 saat
    ]);

    // Tamponu temizle
    ob_end_clean();

    // Başarılı yanıt - Ably'nin beklediği format
    echo json_encode([
        'token' => $tokenDetails->token
    ]);

} catch (Exception $e) {
    // Tamponu temizle
    ob_end_clean();

    http_response_code(500);
    echo json_encode([
        'error' => [
            'message' => 'Token oluşturulamadı: ' . $e->getMessage(),
                     'code' => 40170,
                     'statusCode' => 500
        ]
    ]);
}
?>
