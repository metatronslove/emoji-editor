<?php
// "core/get_user_activities.php" - GÜNCELLENMİŞ VERSİYON
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/counter_manager.php';
require_once __DIR__ . '/../classes/Drawing.php';
require_once __DIR__ . '/../classes/Router.php';
header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID gerekli']);
    exit;
}

$userId = $_GET['user_id'];
$currentUserId = $_SESSION['user_id'] ?? null;
$type = $_GET['type'] ?? 'all'; // all, drawing, flood_set, message, game, challenge, follow
$limit = $_GET['limit'] ?? 50;
$offset = $_GET['offset'] ?? 0;

try {
    $db = getDbConnection();

    // Önce kullanıcının profil gizlilik ayarını kontrol et
    $stmt = $db->prepare("SELECT privacy_mode FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profileUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }

    $isProfilePrivate = ($profileUser['privacy_mode'] === 'private');
    $isProfileOwner = ($currentUserId == $userId);

    // Gizli profil kontrolü
    if ($isProfilePrivate && !$isProfileOwner && $currentUserId) {
        // Takip kontrolü
        $stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$currentUserId, $userId]);
        $isFollowing = $stmt->fetchColumn();

        if (!$isFollowing) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bu gizli profilin aktivitelerini görmek için takipçi olmalısınız']);
            exit;
        }
    } elseif ($isProfilePrivate && !$currentUserId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu gizli profilin aktivitelerini görmek için giriş yapmalısınız']);
        exit;
    }

    // SQL filtreleme
    $whereClause = "WHERE ua.user_id = :user_id";
    $params = [':user_id' => $userId];
    
    if ($type !== 'all') {
        $whereClause .= " AND ua.activity_type = :activity_type";
        $params[':activity_type'] = $type;
    }

    // Kullanıcının aktivitelerini getir (target_type'ı da ekleyelim)
    $sql = "
        SELECT
            ua.*,
            u.username as user_username,
            u.profile_picture as user_profile_picture
        FROM user_activities ua
        JOIN users u ON ua.user_id = u.id
        $whereClause
        ORDER BY ua.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($sql);
    
    // Parametreleri bağla
    foreach ($params as $key => $value) {
        if ($key === ':user_id') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }
    
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aktivite verilerini işle ve formatla
    $processedActivities = [];

    foreach ($activities as $activity) {
        $activityData = json_decode($activity['activity_data'], true) ?? [];

        $processedActivity = [
            'id' => $activity['id'],
            'user_id' => $activity['user_id'],
            'user_username' => $activity['user_username'],
            'user_profile_picture' => $activity['user_profile_picture'],
            'activity_type' => $activity['activity_type'],
            'target_type' => $activity['target_type'] ?? $activity['activity_type'],
            'target_id' => $activity['target_id'],
            'activity_data' => $activityData,
            'created_at' => $activity['created_at'],
            'formatted_date' => date('d.m.Y H:i', strtotime($activity['created_at'])),
            'time_ago' => $this->formatTimeAgo($activity['created_at'])
        ];

        // Aktivite türüne göre mesaj ve detayları oluştur
        switch ($activity['activity_type']) {
            case 'drawing':
                // Çizim aktivitesi
                $drawingTitle = $activityData['drawing_title'] ?? 'Bir çizim';
                $category = $activityData['category'] ?? 'Sanat';
                
                $processedActivity['message'] = 'Yeni bir çizim paylaştı';
                $processedActivity['title'] = $drawingTitle;
                $processedActivity['description'] = "Kategori: $category";
                $processedActivity['icon'] = '🎨';
                $processedActivity['color'] = '#6f42c1';
                $processedActivity['link'] = '/drawing.php?id=' . $activity['target_id'];
                
                // Ekstra bilgiler
                if (isset($activityData['char_count'])) {
                    $processedActivity['extra_info'] = $activityData['char_count'] . ' karakter';
                }
                break;

            case 'flood_set':
                // Flood set oluşturma aktivitesi
                $setName = $activityData['set_name'] ?? 'Bir flood set\'i';
                $category = $activityData['category'] ?? 'Genel';
                $messageCount = $activityData['message_count'] ?? 0;
                
                $processedActivity['message'] = 'Yeni bir flood set\'i oluşturdu';
                $processedActivity['title'] = $setName;
                $processedActivity['description'] = "Kategori: $category • $messageCount mesaj";
                $processedActivity['icon'] = '🌊';
                $processedActivity['color'] = '#007bff';
                $processedActivity['link'] = '/flood_set.php?id=' . $activity['target_id'];
                
                if (isset($activityData['description'])) {
                    $processedActivity['description'] .= ' • ' . substr($activityData['description'], 0, 80) . '...';
                }
                break;

            case 'flood_message':
                // Flood mesaj ekleme aktivitesi
                $setName = $activityData['set_name'] ?? 'Bir flood set\'i';
                $charCount = $activityData['char_count'] ?? 0;
                $messagePreview = isset($activityData['content']) ? 
                    (strlen($activityData['content']) > 60 ? substr($activityData['content'], 0, 60) . '...' : $activityData['content']) : 
                    'Yeni mesaj';
                
                $processedActivity['message'] = 'Yeni bir flood mesajı ekledi';
                $processedActivity['title'] = $setName;
                $processedActivity['description'] = $messagePreview;
                $processedActivity['icon'] = '💬';
                $processedActivity['color'] = '#28a745';
                $processedActivity['link'] = '/flood_set.php?id=' . ($activityData['set_id'] ?? '');
                
                if ($charCount > 0) {
                    $processedActivity['extra_info'] = $charCount . ' karakter';
                }
                break;

            case 'game':
                // Oyun aktivitesi
                $gameType = $activityData['game_type'] ?? 'oyun';
                $result = $activityData['result'] ?? 'unknown';
                $opponent = $activityData['opponent'] ?? 'bir kullanıcı';
                $score = $activityData['score'] ?? '';

                $resultText = 'oynadı';
                if ($result === 'win') $resultText = 'kazandı';
                if ($result === 'loss') $resultText = 'kaybetti';
                if ($result === 'draw') $resultText = 'berabere kaldı';

                $processedActivity['message'] = "{$opponent} ile {$gameType} {$resultText}";
                $processedActivity['title'] = ucfirst($gameType);
                $processedActivity['description'] = $score ? "Skor: $score" : '';
                $processedActivity['icon'] = '🎮';
                $processedActivity['color'] = '#e83e8c';
                $processedActivity['link'] = '#';
                break;

            case 'message':
                // Pano mesajı aktivitesi
                $targetUsername = $activityData['target_username'] ?? 'bir kullanıcı';
                $messageContent = $activityData['message_content'] ?? '';

                // Mesaj içeriğini kısalt
                if (strlen($messageContent) > 100) {
                    $messageContent = substr($messageContent, 0, 100) . '...';
                }

                $processedActivity['message'] = "{$targetUsername} panosuna yazdı";
                $processedActivity['title'] = 'Pano Mesajı';
                $processedActivity['description'] = $messageContent;
                $processedActivity['icon'] = '💬';
                $processedActivity['color'] = '#17a2b8';
                $processedActivity['link'] = '/' . $targetUsername . '/';
                break;

            case 'challenge':
                // Oyun daveti aktivitesi
                $challengedUsername = $activityData['challenged_username'] ?? 'bir kullanıcı';
                $gameType = $activityData['game_type'] ?? 'oyun';

                $processedActivity['message'] = "{$challengedUsername} kullanıcısına {$gameType} için meydan okudu";
                $processedActivity['title'] = ucfirst($gameType) . ' Daveti';
                $processedActivity['description'] = '';
                $processedActivity['icon'] = '⚔️';
                $processedActivity['color'] = '#ffc107';
                $processedActivity['link'] = '/' . $challengedUsername . '/';
                break;

            case 'follow':
                // Takip aktivitesi
                $followedUsername = $activityData['followed_username'] ?? 'bir kullanıcı';

                $processedActivity['message'] = "{$followedUsername} kullanıcısını takip etmeye başladı";
                $processedActivity['title'] = 'Yeni Takip';
                $processedActivity['description'] = '';
                $processedActivity['icon'] = '👥';
                $processedActivity['color'] = '#20c997';
                $processedActivity['link'] = '/' . $followedUsername . '/';
                break;

            case 'comment':
                // Yorum aktivitesi
                $targetType = $activityData['target_type'] ?? 'drawing';
                $targetName = $activityData['target_name'] ?? 'bir içerik';
                $commentContent = isset($activityData['comment_content']) ? 
                    (strlen($activityData['comment_content']) > 80 ? substr($activityData['comment_content'], 0, 80) . '...' : $activityData['comment_content']) : 
                    'Yorum yaptı';
                
                $processedActivity['message'] = "{$targetName} üzerine yorum yaptı";
                $processedActivity['title'] = ucfirst($targetType) . ' Yorumu';
                $processedActivity['description'] = $commentContent;
                $processedActivity['icon'] = '💭';
                $processedActivity['color'] = '#6c757d';
                $processedActivity['link'] = $targetType === 'drawing' ? 
                    '/drawing.php?id=' . $activity['target_id'] : 
                    '/' . ($activityData['target_username'] ?? '');
                break;

            default:
                // Diğer aktiviteler
                $processedActivity['message'] = 'Yeni bir aktivite gerçekleştirdi';
                $processedActivity['title'] = 'Aktivite';
                $processedActivity['description'] = '';
                $processedActivity['icon'] = '🔔';
                $processedActivity['color'] = '#6c757d';
                $processedActivity['link'] = '#';
        }

        // Ekstra istatistikler
        if (isset($activityData['views'])) {
            $processedActivity['stats']['views'] = $activityData['views'];
        }
        if (isset($activityData['likes'])) {
            $processedActivity['stats']['likes'] = $activityData['likes'];
        }
        if (isset($activityData['comments'])) {
            $processedActivity['stats']['comments'] = $activityData['comments'];
        }

        $processedActivities[] = $processedActivity;
    }

    // TOPLAM AKTİVİTE SAYISI (sayfalama için)
    $countSql = "SELECT COUNT(*) as total FROM user_activities WHERE user_id = ?";
    if ($type !== 'all') {
        $countSql .= " AND activity_type = ?";
    }
    
    $countStmt = $db->prepare($countSql);
    if ($type !== 'all') {
        $countStmt->execute([$userId, $type]);
    } else {
        $countStmt->execute([$userId]);
    }
    
    $totalCount = $countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'activities' => $processedActivities,
        'total_count' => (int)$totalCount,
        'current_page' => $offset == 0 ? 1 : floor($offset / $limit) + 1,
        'total_pages' => ceil($totalCount / $limit),
        'can_view' => true,
        'is_profile_owner' => $isProfileOwner
    ]);

} catch (Exception $e) {
    error_log("Kullanıcı aktiviteleri getirme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Aktiviteler yüklenirken hata oluştu: ' . $e->getMessage()]);
}

// Yardımcı fonksiyon: zaman formatı
function formatTimeAgo($dateString) {
    try {
        $date = new DateTime($dateString);
        $now = new DateTime();
        $interval = $date->diff($now);
        
        if ($interval->y > 0) return $interval->y . ' yıl önce';
        if ($interval->m > 0) return $interval->m . ' ay önce';
        if ($interval->d > 0) {
            if ($interval->d == 1) return 'dün';
            return $interval->d . ' gün önce';
        }
        if ($interval->h > 0) return $interval->h . ' saat önce';
        if ($interval->i > 0) return $interval->i . ' dakika önce';
        
        return 'az önce';
    } catch (Exception $e) {
        return $dateString;
    }
}
?>