<?php
// admin/dashboard.php
require_once '../config.php';
$db = getDbConnection();

$userRole = $_SESSION['user_role'] ?? 'user';
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId || !in_array($userRole, ['admin', 'moderator'])) {
    http_response_code(403);
    die("Yetkisiz Erişim. Bu sayfayı görüntüleme izniniz yok.");
}

$isAdmin = ($userRole === 'admin');
$searchQuery = $_GET['q'] ?? '';
$page = (int)($_GET['p'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

// Kullanıcıları çekme sorgusu
$userList = [];
try {
    $stmt = $db->prepare("
    SELECT id, username, email, role, is_banned, comment_mute_until
    FROM users
    WHERE username LIKE :search
    ORDER BY role DESC, id DESC
    LIMIT :limit OFFSET :offset
    ");
    $searchTerm = '%' . $searchQuery . '%';
    $stmt->bindParam(':search', $searchTerm);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $userList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* Hata yönetimi */ }
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Yönetim Paneli | <?php echo ucfirst($userRole); ?></title>
</head>
<body>
<div class="container">
<h1>Yönetim Paneli</h1>
<p>Giriş Yapan: <b><?php echo $_SESSION['username']; ?></b> (Rol: <?php echo ucfirst($userRole); ?>)</p>
<hr>

<h2>İçerik Yönetimi (Çizimler & Yorumlar)</h2>
<div id="content-moderation-area">
<p>İçerikler yükleniyor...</p>
</div>
</div>

<div id="mute-modal" style="display:none; position: fixed; top: 10%; left: 50%; transform: translateX(-50%); background: white; padding: 20px;">
</div>

<script>
function sendAction(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        alert(result.message);
        if (result.success) window.location.reload();
    })
    .catch(error => { alert('Sunucu hatası: ' + error); });
}

function moderateUser(userId, action) {
    if (confirm(`Kullanıcı ID ${userId} için '${action}' eylemini onaylıyor musuz?`)) {
        sendAction('moderate_user.php', { user_id: userId, action: action });
    }
}

function showMuteModal(userId) {
    document.getElementById('mute-user-id').value = userId;
    document.getElementById('mute-modal').style.display = 'block';
}

function applyCommentMute() {
    const userId = document.getElementById('mute-user-id').value;
    const duration = document.getElementById('mute-duration').value;
    if (duration > 0) sendAction('moderate_user.php', { user_id: userId, action: 'mute', duration: duration });
    else alert('Süre 1 günden büyük olmalıdır.');
}

function setRole(userId, newRole) {
    if (confirm(`Kullanıcı ID ${userId}'nin rolünü '${newRole}' olarak değiştirmeyi onaylıyor musunuz?`)) {
        sendAction('moderate_user.php', { user_id: userId, action: 'set_role', new_role: newRole });
    }
}

function moderateContent(contentId, contentType, action) {
    if (confirm(`Bu ${contentType} ID ${contentId}'i ${action === 'hide' ? 'gizlemeyi' : 'görünür yapmayı'} onaylıyor musunuz?`)) {
        sendAction('moderate_content.php', { content_id: contentId, content_type: contentType, action: action });
    }
}

// =========================================================
// İÇERİK YÖNETİMİ BAŞLATICI FONKSİYONLAR
// =========================================================
async function fetchRecentContentForModeration() {
    const contentArea = document.getElementById('content-moderation-area');
    contentArea.innerHTML = '<p>Son içerikler yükleniyor...</p>';

    try {
        const response = await fetch('fetch_recent_content.php');
        const result = await response.json();

        if (result.success) {
            contentArea.innerHTML = '';
            contentArea.innerHTML += '<h3>Son Çizimler</h3>';
            contentArea.appendChild(createContentTable(result.drawings, 'drawing'));

            contentArea.innerHTML += '<h3>Son Yorumlar</h3>';
            contentArea.appendChild(createContentTable(result.comments, 'comment'));

        } else { contentArea.innerHTML = `<p style="color: red;">❌ İçerik yüklenemedi: ${result.message}</p>`; }
    } catch (error) { contentArea.innerHTML = '<p style="color: red;">❌ Sunucu ile iletişim hatası.</p>'; }
}

function createContentTable(data, type) {
    // ... (Daha önceki yanıtta detaylıca verilen tablo oluşturma fonksiyonu) ...
    const table = document.createElement('table');
    // Tablo içeriğini burada oluştur

    return table;
}

document.addEventListener('DOMContentLoaded', () => {
    fetchRecentContentForModeration();
});
</script>
</body>
</html>
