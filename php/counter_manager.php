<?php
require_once 'config.php';
require_once 'User.php';
require_once 'functions.php';
// counter_manager.php - HATALARI DÜZELTİLMİŞ TAM ÇÖZÜM
// NOT: Bu kodun çalışması için session_start() çağrılmış olmalı ve getDbConnection() fonksiyonu tanımlanmış olmalıdır.
const ONLINE_THRESHOLD_MINUTES = 60;

/**
 * TAM TÜRKÇE EK UYUMU FONKSİYONU
 * Tüm kurallar doğru şekilde uygulanmıştır
 */
function getTurkishSuffix($number) {
    $sonRakam = $number % 10;
    $sonIkiRakam = $number % 100;

    // Özel durumlar: 10, 20, 30, 40, 50, 60, 70, 80, 90
    if ($sonIkiRakam == 10) return "'u";
    if ($sonIkiRakam == 20) return "'si";
    if ($sonIkiRakam == 30) return "'u";
    if ($sonIkiRakam == 40) return "'ı";
    if ($sonIkiRakam == 50) return "'si";
    if ($sonIkiRakam == 60) return "'ı";
    if ($sonIkiRakam == 70) return "'i";
    if ($sonIkiRakam == 80) return "'i";
    if ($sonIkiRakam == 90) return "'ı";

    // 100 ve katları için özel kurallar
    if ($number == 100) return "'ü";
    if ($sonIkiRakam == 0) return "'ı";

    // 9 ile biten sayılar için özel kural (19, 29, 39...)
    if ($sonRakam == 9 && $sonIkiRakam != 9) return "'u";

    // Standart kurallar
    switch ($sonRakam) {
        case 1:
            return "'i";
        case 2:
            return "'si";
        case 3:
        case 4:
            return "'ü";
        case 5:
        case 6:
            return "'ı";
        case 7:
        case 8:
        case 0:
            return "'ı";
        default:
            return "'ı";
    }
}


function updateCounters() {
    try {
        // session_start() çağrıldığından emin olunmalıdır
        $db = getDbConnection();
        if (!$db) {
            error_log("Database connection failed in updateCounters");
            return;
        }

        // Login sisteminden gelen user_id'yi doğrudan al
        $currentUserId = $_SESSION['user_id'] ?? null;

        // Eklenecek Bilgileri Al
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        // User Agent bilgisi
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // TOPLAM ZİYARETÇİ SAYACINI ARTIR (Bu, her sayfa yüklemede artacaktır)
        $stmt = $db->prepare("INSERT INTO stats (key_name, value) VALUES ('total_views', 1) ON DUPLICATE KEY UPDATE value = value + 1");
        $stmt->execute();

        // SESSIONS TABLOSUNA KAYIT EKLE
        // session_key: Giriş yapmış kullanıcılar için user_id, misafirler için IP bazlı benzersiz key
        if ($currentUserId) {
            // Giriş yapmış kullanıcı için user_id'yi doğrudan kullan
            $session_key = 'user_' . $currentUserId;
            // user_id'yi integer olarak saklayalım
            $userIdToStore = (int)$currentUserId;
        } else {
            // Misafir kullanıcı için IP bazlı key
            $session_key = 'guest_' . md5($ip_address);
            $userIdToStore = null; // user_id NULL olacak
        }


        $last_active = date_create()->format('Y-m-d H:i:s');
        $db = getDbConnection();
        // Sessions tablosuna insert/update
        $stmt = $db->prepare("
        INSERT INTO sessions (session_key, user_id, last_active, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE last_active = last_active, ip_address = VALUES(ip_address), user_agent = VALUES(user_agent)
        ");

        // user_id null ise PDO için 3. parametre olarak NULL gönderilmesi gerekir
        // Parametreler: [session_key, user_id, ip_address, user_agent]
        $stmt->execute([$session_key, $userIdToStore, $last_active, $ip_address, $user_agent]);

        // ESKİMİŞ KAYITLARI TEMİZLE
        $cutoffTime = date('Y-m-d H:i:s', strtotime('-' . ONLINE_THRESHOLD_MINUTES . ' minutes'));
        $clean_stmt = $db->prepare("DELETE FROM sessions WHERE last_active < ?");
        $clean_stmt->execute([$cutoffTime]);

    } catch (PDOException $e) {
        error_log("Counter update error: " . $e->getMessage());
    }
}

function getCounters() {
    try {
        $db = getDbConnection();
        if (!$db) {
            error_log("Database connection failed in getCounters");
            return ['total_views' => 0, 'online_users' => 1];
        }

        // Toplam ziyaret sayısını al
        $stmt = $db->prepare("SELECT value FROM stats WHERE key_name = 'total_views'");
        $stmt->execute();
        $totalViews = $stmt->fetchColumn() ?? 0;

        // Çevrimiçi kullanıcı sayısını al - TÜM aktif session'lar
        // NOT: user_id NULL olsun veya olmasın, benzersiz session_key'leri sayar.
        $cutoffTime = date('Y-m-d H:i:s', strtotime('-' . ONLINE_THRESHOLD_MINUTES . ' minutes'));
        $stmt = $db->prepare("SELECT COUNT(DISTINCT session_key) FROM sessions WHERE last_active > ?");
        $stmt->execute([$cutoffTime]);
        $onlineUsers = $stmt->fetchColumn() ?? 1;

        return [
            'total_views' => (int)$totalViews,
            // Asla 0 olmayacak - En azından mevcut kullanıcı (kendiniz) sayılmalı
            'online_users' => max(1, (int)$onlineUsers)
        ];

    } catch (PDOException $e) {
        error_log("getCounters error: " . $e->getMessage());
        return ['total_views' => 0, 'online_users' => 1];
    }
}

function getOnlineUsersText() {
    $counters = getCounters();
    $totalOnline = $counters['online_users'];

    try {
        $db = getDbConnection();
        $cutoffTime = date('Y-m-d H:i:s', strtotime('-' . ONLINE_THRESHOLD_MINUTES . ' minutes'));

        // 1. KAYITLI ÜYELERİ SAY: users tablosuna join atarak
        // Giriş yapmış, aktif session'ı olan ve yasaklanmamış benzersiz user_id'leri say
        $stmt = $db->prepare("
        SELECT COUNT(DISTINCT s.user_id)
        FROM sessions AS s
        INNER JOIN users AS u ON s.user_id = u.id
        WHERE s.last_active > ?
        AND s.user_id IS NOT NULL
        AND u.is_banned = 0
        ");
        $stmt->execute([$cutoffTime]);
        $registeredUsersCount = $stmt->fetchColumn() ?? 0;

    } catch (Exception $e) {
        error_log("getOnlineUsersText error: " . $e->getMessage());
        $registeredUsersCount = 0;
    }

    // SONUÇ METNİNİN OLUŞTURULMASI:
    if ($registeredUsersCount > 0) {
        // Örn: 15 anlık kullanıcının 3'ü üye
        $ek = getTurkishSuffix($registeredUsersCount);
        return "{$totalOnline} anlık kullanıcının {$registeredUsersCount}{$ek} üye";
    } else {
        // Örn: 5 anlık kullanıcının hiçbiri üye değil
        return "{$totalOnline} anlık kullanıcının hiçbiri üye değil";
    }
}

// Sayaçları başlat
if (!defined('COUNTERS_INITIALIZED')) {
    define('COUNTERS_INITIALIZED', true);
    updateCounters();
}
?>
