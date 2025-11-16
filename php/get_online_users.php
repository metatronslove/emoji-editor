<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $db = getDbConnection();

    // Son 5 dakika içinde aktif olan kullanıcıları getir
    $stmt = $db->prepare("
        SELECT
            id,
            username,
            profile_picture,
            last_activity,
            TIMESTAMPDIFF(SECOND, last_activity, NOW()) as seconds_ago
        FROM users
        WHERE is_online = TRUE
        AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY last_activity DESC
        LIMIT 50
    ");
    $stmt->execute();
    $onlineUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Çevrimiçi kullanıcı sayısı
    $onlineCount = count($onlineUsers);

    // Metin formatında çevrimiçi kullanıcı bilgisi
    $onlineText = "{$onlineCount} çevrimiçi kullanıcı";
    if ($onlineCount === 0) {
        $onlineText = "Henüz çevrimiçi kullanıcı yok";
    } elseif ($onlineCount === 1) {
        $onlineText = "1 çevrimiçi kullanıcı";
    }

    echo json_encode([
        'success' => true,
        'online_users' => $onlineUsers,
        'online_count' => $onlineCount,
        'online_text' => $onlineText
    ]);

} catch (Exception $e) {
    error_log("Çevrimiçi kullanıcılar getirme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Çevrimiçi kullanıcılar yüklenirken hata oluştu']);
}
?>
