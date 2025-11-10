<?php
require_once 'config.php';
header('Content-Type: application/json');

// Sabitler
$LIMIT = 50; // Tek sayfada gösterilecek maksimum çizim sayısı
$PAGE = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$OFFSET = ($PAGE - 1) * $LIMIT;

// Oturumdan mevcut kullanıcının ID'sini al (Giriş yapmamışsa NULL)
$currentUserId = $_SESSION['user_id'] ?? null;

try {
    $db = getDbConnection(); // Veritabanı bağlantısı

    // SQL Sorgusu: Gizlilik kurallarını uygulayan karmaşık WHERE koşulu
    // Bu koşul, hangi çizimlerin gösterileceğine karar verir.
    $WHERE_CLAUSE = "
    -- Önceki gizlilik kurallarınız buraya gelecek...
    -- 1. Kullanıcı ID'si olmayan (Anonim) çizimleri her zaman göster...
    -- 2. VEYA Çizim herkese açık (public) bir profile aitse...
    -- 3. VEYA Çizim, oturum açmış kullanıcının (kendisine) aitse...
    -- 4. VEYA Çizim, gizli (private) bir profile aitse VE takip ediyorsa...

    -- YENİ EKLENECEK KOŞUL:
    -- 5. Çizim, mevcut kullanıcının engellediği birine ait OLMAMALIDIR.
    AND (
        d.user_id IS NULL -- Anonim ise kontrol etme
        OR d.user_id NOT IN (
            SELECT blocked_id FROM blocks WHERE blocker_id = :current_user_id_for_block
            )
            )
            -- VEYA ENGELLEYEN DE BEN OLMAMALIYIM (Eğer ben engelleniyorsam, benim çizimim listede görünmelidir.
            -- Bu koşul, çizimin görünürlüğünü sadece engelleyen kişi için kısıtlar.
            ";

    // 1. Toplam uygun kayıt sayısını al
    // Toplam sayıyı hesaplarken de aynı gizlilik kuralları uygulanmalıdır.
    $totalStmt = $db->prepare("
        SELECT COUNT(d.id)
        FROM drawings d
        LEFT JOIN users u ON d.user_id = u.id
        WHERE {$WHERE_CLAUSE}
    ");
    $totalStmt->bindParam(':current_user_id', $currentUserId, $currentUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $totalStmt->execute();
    $totalDrawings = $totalStmt->fetchColumn();
    $totalPages = ceil($totalDrawings / $LIMIT);

    // 2. Sayfalanmış çizimleri ve çizer (user) bilgilerini al
    $stmt = $db->prepare("
        SELECT
            d.id,
            d.content,
            d.updated_at,
            u.username AS author_username,
            u.id AS author_id
        FROM drawings d
        LEFT JOIN users u ON d.user_id = u.id
        WHERE {$WHERE_CLAUSE}
        ORDER BY d.updated_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindParam(':limit', $LIMIT, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $OFFSET, PDO::PARAM_INT);
    $stmt->bindParam(':current_user_id', $currentUserId, $currentUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindParam(':current_user_id_for_block', $currentUserId, $currentUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();
    $drawings = $stmt->fetchAll();

    // 3. Sonuçları JSON olarak döndür
    echo json_encode([
        'success' => true,
        'currentPage' => $PAGE,
        'totalPages' => $totalPages,
        'totalDrawings' => $totalDrawings,
        'drawings' => $drawings
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı listeleme hatası: ' . $e->getMessage()
    ]);
}
?>
