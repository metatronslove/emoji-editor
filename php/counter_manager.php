<?php
// counter_manager.php - DÜZELTİLMİŞ
// Bu dosya config.php'den sonra çağrılmalıdır.
// Oturum zaten config.php'de başlatılmıştır.

// Zaman sınırı (Örn: Son 5 dakika içinde aktif olanları çevrimiçi say)
const ONLINE_THRESHOLD_MINUTES = 5;

/**
 * Total Views sayacını 1 artırır ve sessions tablosunu günceller/temizler.
 */
function updateCounters($db) {
    global $currentUserId; // Oturum açmış kullanıcının ID'si (varsa)

    // 1. TOPLAM ZİYARETÇİ SAYACINI ARTIR
    try {
        $db->exec("UPDATE stats SET value = value + 1 WHERE key_name = 'total_views'");
    } catch (PDOException $e) {
        // Eğer 'total_views' kaydı yoksa ekleyelim
        $db->exec("INSERT IGNORE INTO stats (key_name, value) VALUES ('total_views', 1)");
    }

    // 2. ÇEVRİMİÇİ KULLANICILAR İÇİN SESSIONS TABLOSUNU GÜNCELLE

    $ip_address = $_SERVER['REMOTE_ADDR'];
    $session_key = $currentUserId ? 'user_' . $currentUserId : 'ip_' . $ip_address;

    $stmt = $db->prepare("
    INSERT INTO sessions (session_key, user_id, last_active)
    VALUES (:key, :user_id, NOW())
    ON DUPLICATE KEY UPDATE last_active = NOW()
    ");

    $stmt->bindParam(':key', $session_key);
    $stmt->bindParam(':user_id', $currentUserId, $currentUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();

    // 3. Eskimiş kayıtları temizle (Çevrimdışı olanları sil)
    $cutoffTime = date('Y-m-d H:i:s', strtotime('-' . ONLINE_THRESHOLD_MINUTES . ' minutes'));
    $db->exec("DELETE FROM sessions WHERE last_active < '{$cutoffTime}'");
}

/**
 * Tüm sayaç değerlerini tek bir dizide döndürür.
 * @return array
 */
function getCounters($db = null) {
    // Eğer DB bağlantısı verilmediyse, kendimiz oluşturalım
    if ($db === null) {
        try {
            $db = getDbConnection();
        } catch (PDOException $e) {
            error_log("getCounters DB bağlantı hatası: " . $e->getMessage());
            return [
                'total_views' => 0,
                'online_users' => 0
            ];
        }
    }

    try {
        // Toplam ziyaret sayısını al
        $totalViews = $db->query("SELECT value FROM stats WHERE key_name = 'total_views'")->fetchColumn() ?? 0;

        // Çevrimiçi kullanıcı sayısını al
        $onlineUsers = $db->query("SELECT COUNT(DISTINCT session_key) FROM sessions")->fetchColumn() ?? 0;

        return [
            'total_views' => (int)$totalViews,
            'online_users' => (int)$onlineUsers
        ];
    } catch (PDOException $e) {
        error_log("getCounters sorgu hatası: " . $e->getMessage());
        return [
            'total_views' => 0,
            'online_users' => 0
        ];
    }
}

// Sayaçları başlatmak için
try {
    $db = getDbConnection();
    $currentUserId = $_SESSION['user_id'] ?? null;
    updateCounters($db);
    $counters = getCounters($db);
} catch (PDOException $e) {
    // Sayaçlar veritabanı hatası, uygulamayı durdurma
    error_log("Sayaç hatası: " . $e->getMessage());
    $counters = ['total_views' => 0, 'online_users' => 0];
}
?>
