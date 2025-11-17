<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID gerekli']);
    exit;
}

$userId = $_GET['user_id'];
$currentUserId = $_SESSION['user_id'] ?? null;

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

    // Kullanıcının aktivitelerini getir
    $stmt = $db->prepare("
        SELECT
            ua.*,
            u.username as user_username,
            u.profile_picture as user_profile_picture
        FROM user_activities ua
        JOIN users u ON ua.user_id = u.id
        WHERE ua.user_id = ?
        ORDER BY ua.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$userId]);
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
            'target_id' => $activity['target_id'],
            'activity_data' => $activityData,
            'created_at' => $activity['created_at'],
            'formatted_date' => date('d.m.Y H:i', strtotime($activity['created_at']))
        ];

        // Aktivite türüne göre mesaj oluştur
        switch ($activity['activity_type']) {
            case 'drawing':
                $processedActivity['message'] = 'Yeni bir çizim paylaştı';
                $processedActivity['icon'] = '🎨';
                $processedActivity['link'] = '/drawing.php?id=' . $activity['target_id'];
                break;

            case 'game':
                $gameType = $activityData['game_type'] ?? 'oyun';
                $result = $activityData['result'] ?? 'unknown';
                $opponent = $activityData['opponent'] ?? 'bir kullanıcı';

                $resultText = 'oynadı';
                if ($result === 'win') $resultText = 'kazandı';
                if ($result === 'loss') $resultText = 'kaybetti';
                if ($result === 'draw') $resultText = 'berabere kaldı';

                $processedActivity['message'] = "{$opponent} ile {$gameType} {$resultText}";
                $processedActivity['icon'] = '🎮';
                $processedActivity['link'] = '#';
                break;

            case 'message':
                $targetUsername = $activityData['target_username'] ?? 'bir kullanıcı';
                $messageContent = $activityData['message_content'] ?? '';

                // Mesaj içeriğini kısalt
                if (strlen($messageContent) > 100) {
                    $messageContent = substr($messageContent, 0, 100) . '...';
                }

                $processedActivity['message'] = "{$targetUsername} panosuna yazdı: {$messageContent}";
                $processedActivity['icon'] = '💬';
                $processedActivity['link'] = '/' . $targetUsername . '/';
                break;

            case 'challenge':
                $challengedUsername = $activityData['challenged_username'] ?? 'bir kullanıcı';
                $gameType = $activityData['game_type'] ?? 'oyun';

                $processedActivity['message'] = "{$challengedUsername} kullanıcısına {$gameType} için meydan okudu";
                $processedActivity['icon'] = '⚔️';
                $processedActivity['link'] = '/' . $challengedUsername . '/';
                break;

            case 'follow':
                $followedUsername = $activityData['followed_username'] ?? 'bir kullanıcı';

                $processedActivity['message'] = "{$followedUsername} kullanıcısını takip etmeye başladı";
                $processedActivity['icon'] = '👥';
                $processedActivity['link'] = '/' . $followedUsername . '/';
                break;

            default:
                $processedActivity['message'] = 'Yeni bir aktivite gerçekleştirdi';
                $processedActivity['icon'] = '🔔';
                $processedActivity['link'] = '#';
        }

        $processedActivities[] = $processedActivity;
    }

    echo json_encode([
        'success' => true,
        'activities' => $processedActivities,
        'can_view' => true
    ]);

} catch (Exception $e) {
    error_log("Kullanıcı aktiviteleri getirme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Aktiviteler yüklenirken hata oluştu: ' . $e->getMessage()]);
}
?>
