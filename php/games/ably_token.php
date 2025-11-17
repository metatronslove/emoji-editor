<?php
// games/ably_token.php - TAMAMEN DÜZELTİLMİŞ VERSİYON

// TÜM hata ve çıktıları kapat
ini_set('display_errors', 0);
error_reporting(0);
ob_start(); // Çıktı tamponlamasını başlat

try {
    require_once __DIR__ . '/../config.php';

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
        ob_end_clean(); // Tamponu temizle
        exit(0);
    }

    // Oturum kontrolü
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        ob_end_clean(); // Tamponu temizle
        echo json_encode(['error' => 'Oturum yok']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $clientId = 'user_' . $userId;

    // API key kontrolü
    if (!defined('ABLY_API_KEY') || empty(ABLY_API_KEY)) {
        throw new Exception('ABLY_API_KEY tanımlı değil');
    }

    // Ably SDK'sını kullanarak token oluştur
    $ably = new AblyRest(['key' => ABLY_API_KEY]);

    // Token oluştur
    $tokenDetails = $ably->auth->requestToken([
        'clientId' => $clientId,
        'ttl' => 3600000 // 1 saat
    ]);

    // Tamponu temizle (olası tüm çıktıları kaldır)
    ob_end_clean();

    // Başarılı yanıt - Ably'nin beklediği EXACT format
    echo json_encode([
        'token' => $tokenDetails
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
