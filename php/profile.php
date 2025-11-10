<?php
// profile.php
require_once 'config.php';

$profileUsername = $_GET['username'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$profileUsername) {
    header('Location: index.php');
    exit;
}

try {
    $db = getDbConnection();

    // 1. Profil sahibini çek
    $stmt = $db->prepare("SELECT id, username, profile_picture, privacy_mode, profile_views, role FROM users WHERE username = ?");
    $stmt->execute([$profileUsername]);
    $profileUser = $stmt->fetch();

    if (!$profileUser) {
        http_response_code(404);
        die("Çizer Kullanıcı bulunamadı.");
    }

    $isProfilePrivate = ($profileUser['privacy_mode'] === 'private');
    $isProfileOwner = ($currentUserId === $profileUser['id']);

    // Görüntüleme Sayacını Artır (Kendisi değilse)
    if (!$isProfileOwner) {
        $db->exec("UPDATE users SET profile_views = profile_views + 1 WHERE id = {$profileUser['id']}");
    }

    /* ENGELLEME (BLOCK) KONTROLÜ */
    $isBlockedByMe = false;
    $isBlockingMe = false;

    if ($currentUserId && !$isProfileOwner) {
        $isBlockedByMe = $db->query("SELECT 1 FROM blocks WHERE blocker_id = {$currentUserId} AND blocked_id = {$profileUser['id']}")->fetchColumn();
        $isBlockingMe = $db->query("SELECT 1 FROM blocks WHERE blocker_id = {$profileUser['id']} AND blocked_id = {$currentUserId}")->fetchColumn();
    }

    // Kritik Kontrol: Herhangi bir engelleme varsa, sayfayı göstereme
    if ($isBlockedByMe || $isBlockingMe) {
        http_response_code(403);
        die("Bu kullanıcı ile etkileşime geçemezsiniz veya profilini görüntüleyemezsiniz.");
    }


    /* TAKİP ve İÇERİK GÖRÜNÜRLÜĞÜ KONTROLÜ */
    $isFollowing = false;
    $followRequestPending = false;
    $canViewContent = true;

    if ($currentUserId) {
        $isFollowing = $db->query("SELECT 1 FROM follows WHERE follower_id = {$currentUserId} AND following_id = {$profileUser['id']}")->fetchColumn();
        $followRequestPending = $db->query("SELECT 1 FROM follow_requests WHERE follower_id = {$currentUserId} AND following_id = {$profileUser['id']}")->fetchColumn();
    }

    if ($isProfilePrivate && !$isProfileOwner && !$isFollowing) {
        $canViewContent = false;
    }

    // Takip Butonu Metnini Belirle
    $followButtonText = 'Takip Et';
    $followButtonAction = 'follow';
    if ($isFollowing) {
        $followButtonText = 'Takibi Bırak';
        $followButtonAction = 'unfollow';
    } elseif ($followRequestPending) {
        $followButtonText = 'İstek Gönderildi';
        $followButtonAction = 'pending';
    }


} catch (PDOException $e) {
    http_response_code(500);
    die("Veritabanı hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($profileUser['username']); ?> Profil</title>
</head>
<body>

<div style="max-width: 800px; margin: 0 auto; padding: 20px;">

<header>
<h1><?php echo htmlspecialchars($profileUser['username']); ?></h1>
<?php if ($currentUserId && !$isProfileOwner): ?>
<button id="followButton" data-action="<?php echo $followButtonAction; ?>" data-target-id="<?php echo $profileUser['id']; ?>" class="btn-primary"
<?php echo $followRequestPending ? 'disabled' : ''; ?>
onclick="handleFollowAction(this)">
<?php echo $followButtonText; ?>
</button>
<button id="blockButton" data-target-id="<?php echo $profileUser['id']; ?>"
class="btn-danger" style="margin-left: 10px;"
onclick="handleBlockAction(this)">
<?php echo $isBlockedByMe ? 'Engellemeyi Kaldır' : 'Engelle'; ?>
</button>
<?php endif; ?>
</header>

<?php if ($isProfileOwner && $isProfilePrivate): ?>
<section id="follow-request-management" style="margin-bottom: 30px;">
<h3>🔔 Bekleyen Takip İstekleri Yönetimi</h3>
<div id="follow-requests-list">
<p>Takip istekleri yükleniyor...</p>
</div>
</section>
<?php endif; ?>

<?php if ($canViewContent): ?>
<section id="featured-drawing">
<h2>Öne Çıkan Çizim</h2>
</section>

<section id="user-drawings">
<h2>Tüm Çizimler</h2>
<div id="user-drawing-list"></div>
</section>

<section id="profile-board">
<h2>Çizer Panosu</h2>
<?php if ($currentUserId): ?>
<textarea id="boardCommentInput" placeholder="Panoya bir mesaj yaz..."></textarea>
<button onclick="postComment('profile', <?php echo $profileUser['id']; ?>)" class="btn-primary">Panoya Gönder</button>
<?php endif; ?>
<div id="board-comments-list"></div>
</section>

<?php else: ?>
<section><p>Bu profil **gizlidir**. İçeriği görmek için takip isteği göndermelisiniz.</p></section>
<?php endif; ?>

</div>

<script>
// Hedef kullanıcının ID'si ve Adı
const PROFILE_USER_ID = <?php echo $profileUser['id']; ?>;
const CURRENT_LOGGED_IN_USER_ID = <?php echo json_encode($currentUserId); ?>;
const IS_PROFILE_OWNER = <?php echo json_encode($isProfileOwner); ?>;

/* ========================================================= */
/* AJAX İŞLEVLERİ */
/* ========================================================= */

async function handleFollowAction(button) {
    // ... (follow_action.php çağrısı) ...
    const action = button.dataset.action === 'follow' ? 'follow' : 'unfollow';
    const targetId = PROFILE_USER_ID;
    if (!CURRENT_LOGGED_IN_USER_ID) { alert('Lütfen önce oturum açın.'); return; }

    try {
        const response = await fetch('follow_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `target_id=${targetId}&action=${action}`
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) window.location.reload();
    } catch (error) { console.error('Takip işlemi hatası:', error); }
}

async function handleBlockAction(button) {
    // ... (block_action.php çağrısı) ...
    const targetId = PROFILE_USER_ID;
    const isBlocking = button.textContent.includes('Engellemeyi Kaldır');
    const action = isBlocking ? 'unblock' : 'block';
    if (!CURRENT_LOGGED_IN_USER_ID) { alert('Lütfen önce oturum açın.'); return; }

    if (confirm(`Bu kullanıcıyı gerçekten ${action === 'block' ? 'engellemek' : 'engellemeyi kaldırmak'} istiyor musunuz?`)) {
        try {
            const response = await fetch('block_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `target_id=${targetId}&action=${action}`
            });
            const result = await response.json();
            alert(result.message);
            if (result.success) window.location.reload();
        } catch (error) { console.error('Engelleme işlemi hatası:', error); }
    }
}

async function postComment(targetType, targetId) {
    // ... (comment_action.php çağrısı) ...
    const inputElement = document.getElementById('boardCommentInput');
    const content = inputElement.value;
    if (content.trim() === '') { alert('Lütfen yorum içeriği girin.'); return; }

    try {
        const response = await fetch('comment_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ target_type: targetType, target_id: targetId, content: content })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) { inputElement.value = ''; fetchComments(targetType, targetId); }
    } catch (error) { console.error('Yorum gönderme hatası:', error); }
}

async function fetchComments(targetType, targetId) {
    // ... (fetch_comments.php çağrısı) ...
    const listElement = document.getElementById('board-comments-list');
    listElement.innerHTML = 'Yükleniyor...';
    try {
        const response = await fetch(`fetch_comments.php?type=${targetType}&id=${targetId}`);
        const result = await response.json();
        if (result.success && result.comments.length > 0) {
            listElement.innerHTML = result.comments.map(comment => `
            <div class="comment-item">
            <p><b><a href="/${comment.username}/">${comment.username}</a></b> <span>(${new Date(comment.created_at).toLocaleString()})</span></p>
            <p style="white-space: pre-wrap; margin: 0;">${comment.content}</p>
            </div>
            `).join('');
        } else { listElement.innerHTML = '<p>Henüz yorum/mesaj bulunmamaktadır.</p>'; }
    } catch (error) { listElement.innerHTML = '<p>Pano mesajları yüklenirken hata oluştu.</p>'; }
}

async function fetchFollowRequests() {
    // ... (fetch_follow_requests.php çağrısı) ...
    const listElement = document.getElementById('follow-requests-list');
    if (!listElement) return;
    listElement.innerHTML = 'İstekler yükleniyor...';
    try {
        const response = await fetch('fetch_follow_requests.php');
        const result = await response.json();
        if (result.success && result.requests.length > 0) {
            renderFollowRequests(result.requests);
        } else if (result.success) {
            listElement.innerHTML = '<p>Bekleyen takip isteği bulunmamaktadır.</p>';
        } else { listElement.innerHTML = `<p>❌ Hata: ${result.message}</p>`; }
    } catch (error) { listElement.innerHTML = '<p>Sunucu ile iletişim hatası.</p>'; }
}

function renderFollowRequests(requests) {
    // ... (İstekleri HTML'e çevirir) ...
    const listElement = document.getElementById('follow-requests-list');
    listElement.innerHTML = requests.map(request => `
    <div style="display: flex; justify-content: space-between;">
    <span><a href="/${request.requester_username}/">${request.requester_username}</a></span>
    <div>
    <button onclick="handleRequestAction(${request.requester_id}, 'approve')">Onayla</button>
    <button onclick="handleRequestAction(${request.requester_id}, 'reject')">Reddet</button>
    </div>
    </div>
    `).join('');
}

async function handleRequestAction(requesterId, action) {
    // ... (manage_follow_request.php çağrısı) ...
    try {
        const response = await fetch('manage_follow_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `requester_id=${requesterId}&action=${action}`
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) fetchFollowRequests();
    } catch (error) { console.error('İstek yönetim hatası:', error); }
}

async function fetchUserDrawings(userId) {
    // ... (fetch_user_drawings.php çağrısı) ...
    const listElement = document.getElementById('user-drawing-list');
    const featuredElement = document.getElementById('featured-drawing');
    if (!listElement) return;
    listElement.innerHTML = 'Çizimler yükleniyor...';

    try {
        const response = await fetch(`fetch_user_drawings.php?user_id=${userId}`);
        const result = await response.json();

        if (result.success && Object.keys(result.categorized_drawings).length > 0) {
            listElement.innerHTML = '';
            if (result.featured_drawing) renderFeaturedDrawing(result.featured_drawing, featuredElement);
            else featuredElement.innerHTML = '<p>Henüz öne çıkan çizim bulunmamaktadır.</p>';

            for (const category in result.categorized_drawings) {
                const categoryHeader = document.createElement('h4');
                categoryHeader.textContent = `Kategori: ${category}`;
                listElement.appendChild(categoryHeader);

                const drawingContainer = document.createElement('div');
                drawingContainer.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;';

                result.categorized_drawings[category].forEach(drawing => {
                    const card = createProfileDrawingCard(drawing, IS_PROFILE_OWNER);
                    drawingContainer.appendChild(card);
                });
                listElement.appendChild(drawingContainer);
            }
        } else { listElement.innerHTML = '<p>Bu çizerin henüz kayıtlı çizimi bulunmamaktadır.</p>'; }
    } catch (error) { listElement.innerHTML = '<p>❌ Çizimler yüklenirken hata oluştu.</p>'; console.error('Kullanıcı çizim yükleme hatası:', error); }
}

function renderFeaturedDrawing(drawing, container) {
    // ... (Öne çıkan çizimi render eder) ...
    container.innerHTML = 'Öne çıkan: ';
    const card = createProfileDrawingCard(drawing, IS_PROFILE_OWNER);
    container.appendChild(card);
}

function createProfileDrawingCard(drawing, isOwner) {
    // ... (Çizim kartı ve yönetici butonları oluşturulur) ...
    const card = document.createElement('div');
    card.style.border = '1px solid #ccc';
    card.style.padding = '10px';

    const drawingPreview = document.createElement('pre');
    drawingPreview.textContent = drawing.content.substring(0, 100) + '...';
    card.appendChild(drawingPreview);

    if (isOwner) {
        const adminActions = document.createElement('div');
        adminActions.innerHTML = `
        <label><input type="checkbox" data-drawing-id="${drawing.id}" data-action="comment_toggle" ${drawing.comments_allowed ? 'checked' : ''} onchange="updateDrawingSetting(this)"> Yorum Açık</label>
        <label><input type="checkbox" data-drawing-id="${drawing.id}" data-action="visible_toggle" ${drawing.is_visible ? 'checked' : ''} onchange="updateDrawingSetting(this)"> Halka Açık</label>
        `;
        card.appendChild(adminActions);
    }
    return card;
}

async function updateDrawingSetting(checkbox) {
    // ... (update_drawing_setting.php çağrısı) ...
    const drawingId = checkbox.dataset.drawingId;
    const action = checkbox.dataset.action;
    const value = checkbox.checked;

    try {
        const response = await fetch('update_drawing_setting.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: drawingId, action: action, value: value })
        });

        const result = await response.json();
        if (result.success) {
            console.log('Ayarlar güncellendi.');
        } else {
            checkbox.checked = !value;
            alert('Hata: ' + result.message);
        }
    } catch (error) {
        console.error('Ayar güncelleme hatası:', error);
        checkbox.checked = !value;
    }
}

// =========================================================
// DOMContentLoaded Başlatıcısı (Notları Temizlendi)
// =========================================================
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('profile-board')) {
        fetchComments('profile', PROFILE_USER_ID);
    }

    if (IS_PROFILE_OWNER && document.getElementById('follow-requests-list')) {
        fetchFollowRequests();
    }

    if (document.getElementById('user-drawing-list')) {
        fetchUserDrawings(PROFILE_USER_ID);
    }
});
</script>
</body>
</html>
