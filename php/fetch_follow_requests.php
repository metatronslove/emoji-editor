<?php
require_once 'config.php';
header('Content-Type: application/json');

$currentUserId = $_SESSION['user_id'] ?? null;
if (!$currentUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açılmadı.']);
    exit;
}

try {
    $db = getDbConnection();

    $stmt = $db->prepare("
    SELECT
    fr.follower_id AS requester_id,
    u.username AS requester_username,
    u.profile_picture AS requester_picture,
    fr.requested_at
    FROM follow_requests fr
    JOIN users u ON fr.follower_id = u.id
    WHERE fr.following_id = :owner_id AND fr.status = 'pending'
    ORDER BY fr.requested_at ASC
    ");
    $stmt->bindParam(':owner_id', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll();

    // PROFİL FOTOĞRAFLARINI DÜZENLE
    foreach ($requests as &$request) {
        if ($request['requester_picture'] && $request['requester_picture'] !== 'default.png') {
            $request['requester_picture'] = 'data:image/jpeg;base64,' . $request['requester_picture'];
        } else {
            $request['requester_picture'] = '/images/default.png';
        }
    }

    echo json_encode(['success' => true, 'requests' => $requests]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'İstekler yüklenirken veritabanı hatası oluştu.']);
}
?>
