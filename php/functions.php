<?php
// functions.php - DÜZELTİLMİŞ

/**
 * Kullanıcı rütbesini hesaplar
 */
function calculateUserRank($userId) {
    try {
        $db = getDbConnection();

        // Kullanıcı istatistiklerini al
        $stmt = $db->prepare("
        SELECT
        (SELECT COUNT(*) FROM comments WHERE commenter_id = ?) as comment_count,
                             (SELECT COUNT(*) FROM drawings WHERE user_id = ?) as drawing_count,
                             (SELECT COUNT(*) FROM follows WHERE following_id = ?) as follower_count,
                             (SELECT COUNT(*) FROM votes WHERE target_type IN ('drawing', 'comment') AND target_id IN
                             (SELECT id FROM drawings WHERE user_id = ?) AND vote_type = 'up') as upvotes,
                             (SELECT COUNT(*) FROM comments WHERE target_type = 'profile' AND target_id = ?) as profile_comments
                             ");
        $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Rütbe ayarlarını al - HATA KONTROLLÜ
        $settings = [];
        try {
            $settingsStmt = $db->query("SELECT * FROM rank_settings");
            $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            error_log("Rank settings error: " . $e->getMessage());
            $settings = [
                'comment_points' => 1,
                'drawing_points' => 2,
                'follower_points' => 0.5,
                'upvote_points' => 0.2,
                'profile_comment_points' => 0.3
            ];
        }

        // Puan hesapla
        $totalPoints =
        ($stats['comment_count'] * ($settings['comment_points'] ?? 1)) +
        ($stats['drawing_count'] * ($settings['drawing_points'] ?? 2)) +
        ($stats['follower_count'] * ($settings['follower_points'] ?? 0.5)) +
        ($stats['upvotes'] * ($settings['upvote_points'] ?? 0.2)) +
        ($stats['profile_comments'] * ($settings['profile_comment_points'] ?? 0.3));

        // Rütbe dağılımı: 6 yıldız (en yüksek) -> 1 yıldız (en düşük)
        if ($totalPoints >= 10000) return 6;
        if ($totalPoints >= 5000) return 5;
        if ($totalPoints >= 1000) return 4;
        if ($totalPoints >= 500) return 3;
        if ($totalPoints >= 100) return 2;
        return 1;

    } catch (Exception $e) {
        error_log("Rütbe hesaplama hatası: " . $e->getMessage());
        return 1;
    }
}

/**
 * Kullanıcının sosyal medya bağlantılarını getirir
 */
function getUserSocialLinks($userId) {
    try {
        $db = getDbConnection();

        $stmt = $db->prepare("
        SELECT smp.name, smp.emoji, usl.profile_url
        FROM user_social_links usl
        JOIN social_media_platforms smp ON usl.platform_id = smp.id
        WHERE usl.user_id = ? AND smp.is_active = TRUE
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Sosyal medya bağlantıları hatası: " . $e->getMessage());
        return [];
    }
}

/**
 * URL'nin platform regex'ine uyup uymadığını kontrol eder
 */
function validateSocialMediaUrl($platformId, $url) {
    try {
        $db = getDbConnection();

        $stmt = $db->prepare("SELECT url_regex FROM social_media_platforms WHERE id = ?");
        $stmt->execute([$platformId]);
        $platform = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$platform || empty($platform['url_regex'])) {
            return true; // Regex tanımlı değilse her URL'yi kabul et
        }

        return preg_match('/' . $platform['url_regex'] . '/', $url) === 1;

    } catch (Exception $e) {
        error_log("URL doğrulama hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Profil görünürlük kontrolü
 */
function canViewProfileContent($profileUser, $currentUserId) {
    if (!$profileUser) return false;

    $isProfileOwner = ($currentUserId === $profileUser['id']);
    $isProfilePrivate = ($profileUser['privacy_mode'] === 'private');

    if ($isProfileOwner) return true;
    if (!$isProfilePrivate) return true;

    // Gizli profil durumunda takip kontrolü
    try {
        $db = getDbConnection();
        $isFollowing = $db->query("
        SELECT 1 FROM follows
        WHERE follower_id = {$currentUserId} AND following_id = {$profileUser['id']}
        ")->fetchColumn();

        return $isFollowing;
    } catch (Exception $e) {
        error_log("Profil görünürlük kontrol hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Engelleme kontrolü
 */
function isBlockedInteraction($currentUserId, $profileUserId) {
    if (!$currentUserId || !$profileUserId) return false;

    try {
        $db = getDbConnection();

        $isBlockedByMe = $db->query("
        SELECT 1 FROM blocks
        WHERE blocker_id = {$currentUserId} AND blocked_id = {$profileUserId}
        ")->fetchColumn();

        $isBlockingMe = $db->query("
        SELECT 1 FROM blocks
        WHERE blocker_id = {$profileUserId} AND blocked_id = {$currentUserId}
        ")->fetchColumn();

        return $isBlockedByMe || $isBlockingMe;

    } catch (Exception $e) {
        error_log("Engelleme kontrol hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Profil resmini 150x150 boyutuna küçültür ve base64 formatında kaydeder
 */
function resizeAndSaveProfilePicture($uploadedFile, $userId) {
    try {
        $db = getDbConnection();

        // MIME type kontrolü
        $imageInfo = getimagesize($uploadedFile['tmp_name']);
        if (!$imageInfo) {
            throw new Exception("Geçersiz resim dosyası");
        }

        $mimeType = $imageInfo['mime'];
        $maxWidth = 150;
        $maxHeight = 150;
        $quality = 85;

        // Resmi yükle
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($uploadedFile['tmp_name']);
                break;
            case 'image/png':
                $image = imagecreatefrompng($uploadedFile['tmp_name']);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($uploadedFile['tmp_name']);
                break;
            default:
                throw new Exception("Desteklenmeyen resim formatı: " . $mimeType);
        }

        if (!$image) {
            throw new Exception("Resim yüklenemedi");
        }

        // Mevcut boyutları al
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Boyut oranını koruyarak yeniden boyutlandır
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Yeni resim oluştur
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // PNG için şeffaflığı koru
        if ($mimeType === 'image/png') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resmi yeniden boyutlandır
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Çıktıyı buffer'a yaz
        ob_start();

        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($resizedImage, null, $quality);
                break;
            case 'image/png':
                imagepng($resizedImage, null, 9);
                break;
            case 'image/gif':
                imagegif($resizedImage);
                break;
        }

        $imageData = ob_get_clean();

        // Belleği temizle
        imagedestroy($image);
        imagedestroy($resizedImage);

        // Base64'e çevir (önceki base64 veriyi kaldır)
        $base64Data = base64_encode($imageData);

        // Veritabanına kaydet
        $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$base64Data, $userId]);

        return true;

    } catch (Exception $e) {
        error_log("Profil resmi işleme hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Profil resmini güvenli şekilde formatlar
 */
function formatProfilePicture($profilePicture) {
    if (empty($profilePicture) || $profilePicture === 'default.png') {
        return '/images/default.png';
    }

    // Eğer zaten data URL formatındaysa
    if (str_starts_with($profilePicture, 'data:image')) {
        return $profilePicture;
    }

    // Base64 verisini data URL formatına çevir
    // MIME type'ı JPEG olarak varsayalım (en yaygın)
    return 'data:image/jpeg;base64,' . $profilePicture;
}

/**
 * Profil resmi boyutunu kontrol eder
 */
function getProfilePictureSize($base64Data) {
    if (empty($base64Data)) {
        return 0;
    }

    // Base64 verisinin boyutunu hesapla
    $size = (int)(strlen(rtrim($base64Data, '=')) * 0.75);
    return $size; // byte cinsinden
}

/**
 * GD kütüphanesi yoksa basit dosya kaydetme
 */
function simpleSaveProfilePicture($uploadedFile, $userId) {
    try {
        $db = getDbConnection();

        // Sadece dosyayı oku ve base64'e çevir
        $imageData = file_get_contents($uploadedFile['tmp_name']);
        $base64Data = base64_encode($imageData);

        // Veritabanına kaydet
        $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$base64Data, $userId]);

        return true;

    } catch (Exception $e) {
        error_log("Basit profil resmi kaydetme hatası: " . $e->getMessage());
        return false;
    }
}
?>
