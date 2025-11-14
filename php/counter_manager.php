<?php
require_once 'config.php';
require_once 'User.php';
require_once 'functions.php';
// counter_manager.php - HATALARI DÜZELTİLMİŞ TAM ÇÖZÜM
// NOT: Bu kodun çalışması için session_start() çağrılmış olmalı ve getDbConnection() fonksiyonu tanımlanmış olmalıdır.
const ONLINE_THRESHOLD_MINUTES = 60;

/**
 * Türk Dil Kurumu (TDK) kurallarına göre bir sayıya (rakamla yazılmış) gelen ismin -i, -e, -de, -den eklerini
 * (örneğin: 1'i, 5'e, 10'da, 3'ten) doğru şekilde belirler.
 * Bu senaryo için iyelik eki (iyelik + hal eki) olan '-nin / -nın' eklerinin kısaltması olan iyelik eki için uyarlanmıştır.
 * Örneğin: 5'in, 13'ün, 20'nin. (Sayının okunuşunun son sesli harfine göre)
 *
 * @param int $number Sayı
 * @return string Kesme işareti ve Türkçe ek.
 */
function getTurkishSuffix($number) {
    if (!is_numeric($number)) return '';

    // Sayının okunuşu (örneğin 13 -> "on üç")
    $numberInWords = (string)$number; // Basit bir çözüm için sayıyı metne çeviriyoruz (Daha karmaşık uyum için harf harf çeviri gerekir)

    // Bu senaryoda sayının son rakamını okunuşuna göre varsayacağız.
    // 0: sıfır (r) -> ın / un
    // 1: bir (r) -> in / ün
    // 2: iki (i) -> nin / nın
    // 3: üç (ç) -> ün
    // 4: dört (t) -> ün
    // 5: beş (ş) -> in
    // 6: altı (ı) -> nın
    // 7: yedi (i) -> nin
    // 8: sekiz (z) -> in
    // 9: dokuz (z) -> un

    $lastDigit = $number % 10;

    // Basitleştirilmiş, ancak yaygın kullanıma yakın bir mantık
    // En sağlıklı yöntem sayının okunuşunun son seslisini bulmaktır.
    // Ancak bu senaryo için sayının son rakamına göre sesli harf uyumu yapalım:

    switch ($lastDigit) {
        case 1: // bir -> in
        case 2: // iki -> nin (ÜNSÜZ: 'si' yerine 'i' kullanıldı, çünkü TDK Kuralı: iki'nin)
        case 7: // yedi -> nin
        case 8: // sekiz -> in
            // Sesliler i/e ise 'in'/'nin' eki. Sayı ünsüzle bitiyorsa 'in', sesliyle bitiyorsa 'nin'
            if (in_array($lastDigit, [2, 7])) { // 2 ve 7 sesliyle biter (iki, yedi)
                return "'nin"; // TDK kuralına göre
            }
            return "'in";

        case 3: // üç -> ün
        case 4: // dört -> ün
            return "'ün";

        case 5: // beş -> in
        case 6: // altı -> nın
            // 5 ünsüzle (beş), 6 sesliyle (altı) biter.
            if ($lastDigit == 6) {
                return "'nın";
            }
            return "'in"; // Beş'in

        case 9: // dokuz -> un
            return "'un";

        case 0:
            // 10, 20, 30, ... gibi durumlarda son okunuş 'n' veya 'z' ile biter.
            if ($number == 0) return "'ın"; // Eğer 0 üye olsaydı

            // 10 (on), 30 (otuz), 40 (kırk)
            $lastTwoDigits = $number % 100;
        if ($lastTwoDigits == 10 || $lastTwoDigits == 90) return "'un"; // on'un, doksan'ın
        if ($lastTwoDigits == 20 || $lastTwoDigits == 50 || $lastTwoDigits == 70) return "'nin"; // yirmi'nin, yetmiş'in
        if ($lastTwoDigits == 30 || $lastTwoDigits == 80) return "'un"; // otuz'un, seksen'in
        if ($lastTwoDigits == 40 || $lastTwoDigits == 60) return "'ın"; // kırk'ın, altmış'ın

        // Genel olarak 0 ile biten büyük sayılar için varsayılan
        return "'ın";

        default:
            return "'ın";
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
?>
