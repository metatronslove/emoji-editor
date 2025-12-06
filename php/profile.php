<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/core/counter_manager.php';
require_once __DIR__ . '/classes/Drawing.php';
require_once __DIR__ . '/classes/Router.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');

$profileUsername = $_GET['username'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;
$site_url = BASE_SITE_URL;

if (!$profileUsername) {
    header('Location: /index.php');
    exit;
}

$isLoggedIn = false;
$userRole = 'user';
$username = '';

if (class_exists('Auth') && method_exists('Auth', 'isLoggedIn')) {
    $isLoggedIn = Auth::isLoggedIn();
    $userRole = $_SESSION['user_role'] ?? 'user';
    $username = $_SESSION['username'] ?? '';
}

try {
    $router = new Router();
    $router->run();
} catch (Exception $e) {
    error_log("Router Error: " . $e->getMessage());
    echo "Router Hatası: " . $e->getMessage();
    exit;
}

try {
    $db = getDbConnection();
    $userModel = new User();
    $profileUser = $userModel->findByUsername($profileUsername);

    if (!$profileUser) {
        http_response_code(404);
        die("Kullanıcı bulunamadı.");
    }

    $isProfilePrivate = ($profileUser['privacy_mode'] === 'private');
    $isProfileOwner = ($currentUserId == $profileUser['id']);

    if (!$isProfileOwner) {
        $stmt = $db->prepare("UPDATE users SET profile_views = profile_views + 1 WHERE id = ?");
        $stmt->execute([$profileUser['id']]);
        $profileUser['profile_views']++;
    }

    $isBlockedByMe = false;
    $isBlockingMe = false;

    if ($currentUserId && !$isProfileOwner) {
        $stmt = $db->prepare("SELECT 1 FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
        $stmt->execute([$currentUserId, $profileUser['id']]);
        $isBlockedByMe = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT 1 FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
        $stmt->execute([$profileUser['id'], $currentUserId]);
        $isBlockingMe = $stmt->fetchColumn();
    }

    if ($isBlockingMe) {
        http_response_code(403);
        die("Bu kullanıcı sizi engellediği için profilini görüntüleyemezsiniz.");
    }

    $isFollowing = false;
    $followRequestPending = false;
    $canViewContent = true;

    if ($currentUserId) {
        $stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$currentUserId, $profileUser['id']]);
        $isFollowing = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT 1 FROM follow_requests WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$currentUserId, $profileUser['id']]);
        $followRequestPending = $stmt->fetchColumn();
    }

    if ($isProfilePrivate && !$isProfileOwner && !$isFollowing) {
        $canViewContent = false;
    }

    $followButtonText = 'Takip Et';
    $followButtonAction = 'follow';
    if ($isFollowing) {
        $followButtonText = 'Takibi Bırak';
        $followButtonAction = 'unfollow';
    } elseif ($followRequestPending) {
        $followButtonText = 'İstek Gönderildi';
        $followButtonAction = 'pending';
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
    $stmt->execute([$profileUser['id']]);
    $followerCount = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $stmt->execute([$profileUser['id']]);
    $followingCount = $stmt->fetchColumn();

    $profilePicSrc = formatProfilePicture($profileUser['profile_picture'] ?? null);

} catch (PDOException $e) {
    error_log("Profile page database error: " . $e->getMessage());
    http_response_code(500);
    die("Veritabanı hatası oluştu.");
} catch (Exception $e) {
    error_log("Profile page general error: " . $e->getMessage());
    http_response_code(500);
    die("Bir hata oluştu.");
}

require_once __DIR__ . '/core/online_status_manager.php';
$counters = getCounters();
$totalViews = $counters['total_views'] ?? 0;

$pageTitle = htmlspecialchars($profileUser['username'] ?? '') . ' - Profil';
require_once __DIR__ . '/templates/header.php';
$isOnline = OnlineStatusManager::isUserOnline($profileUser);
$baseSiteUrl = BASE_SITE_URL . '../';
?>
<script>
window.PROFILE_DATA = {
    userId: <?php echo $profileUser['id']; ?>,
    currentUserId: <?php echo json_encode($currentUserId); ?>,
    isProfileOwner: <?php echo json_encode($isProfileOwner); ?>,
    profileUsername: "<?php echo htmlspecialchars($profileUser['username']); ?>",
    isBlockingMe: <?php echo json_encode($isBlockingMe); ?>,
    isBlockedByMe: <?php echo json_encode($isBlockedByMe); ?>,
    canViewContent: <?php echo json_encode($canViewContent); ?>,
    isProfilePrivate: <?php echo json_encode($isProfilePrivate); ?>,
    isOnline: <?php echo json_encode($isOnline); ?>
};
const SITE_BASE_URL = '<?php echo $baseSiteUrl; ?>';
</script>
<?php require_once __DIR__ . '/templates/navbar.php';?>
<div style="max-width: 100%; margin: 0 auto; width: 100%;">
<!-- PROFİL BAŞLIK BÖLÜMÜ -->
<header class="card" style="margin-bottom: 20px; padding: 25px;">
<div style="display: flex; align-items: center; gap: 20px;">
<!-- Profil Fotoğrafı -->
<div style="flex-shrink: 0;">
<img src="<?php echo htmlspecialchars($profilePicSrc); ?>" alt="Profil Fotoğrafı" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--accent-color); object-fit: cover;">
</div>

<!-- Kullanıcı Bilgileri -->
<div style="flex-grow: 1;">
<h1 style="margin: 0 0 8px 0; font-size: 24px; color: var(--accent-color);" class="profile-username">
<?php echo htmlspecialchars($profileUser['username'] ?? ''); ?>
<?php
$userRank = intval($profileUser['rank']);
echo str_repeat('⭐', $userRank);
?>
</h1>

<div style="display: flex; gap: 20px; margin-bottom: 12px; font-size: 14px;">
<span><strong><?php echo number_format($followerCount); ?></strong> Takipçi</span>
<span><strong><?php echo number_format($followingCount); ?></strong> Takip</span>
<span><strong><?php echo number_format($profileUser['profile_views'] ?? 0); ?></strong> Profil Görüntüleme</span>
</div>

<!-- Sosyal medya bağlantıları -->
<?php
$socialLinks = getUserSocialLinks($profileUser['id'] ?? 0);
if (!empty($socialLinks)):
    ?>
    <div style="margin: 10px;">
    <?php foreach($socialLinks as $link): ?>
    <a href="<?php echo htmlspecialchars($link['profile_url'] ?? ''); ?>" target="_blank" style="margin-right: 10px; text-decoration: none; font-size: 20px;" title="<?php echo htmlspecialchars($link['name'] ?? ''); ?>">
    <?php echo $link['emoji'] ?? '🔗'; ?>
    </a>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="color: var(--main-text); opacity: 0.8; font-size: 14px;">
    <span>Üyelik tarihi: <?php echo date('d.m.Y', strtotime($profileUser['created_at'] ?? 'now')); ?></span>
    <?php if (($profileUser['privacy_mode'] ?? 'public') === 'private'): ?>
    <span style="margin-left: 15px;">🔒 Gizli Profil</span>
    <?php else: ?>
    <span style="margin-left: 15px;">🌍 Herkese Açık</span>
    <?php endif; ?>
    </div>
    </div>

    <!-- Çevrimiçi Gösterge ve Oyun Butonları -->
    <div style="display: flex; align-items: center; gap: 15px; margin: 10px 0; flex-wrap: wrap;">
    <!-- Çevrimiçi Gösterge -->
    <?php
    $isOnline = OnlineStatusManager::isUserOnline($profileUser);
    ?>
    <div style="display: flex; align-items: center; gap: 5px;">
    <span style="font-size: 14px; color: var(--main-text);">
    <?php echo $isOnline ? '🟢 Çevrimiçi' : '⚫ Çevrimdışı'; ?>
    </span>
    </div>

    <!-- Aktif Oyunlar Gösterimi -->
    <?php if ($currentUserId && ($isProfileOwner || $isOnline)): ?>
    <div id="active-games-section" style="margin: 15px 0;">
    <div id="active-games-list"></div>
    </div>
    <?php endif; ?>

    <!-- Aksiyon Butonları -->
    <?php if ($currentUserId && !$isProfileOwner && !$isBlockingMe): ?>
    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
    <button data-simple-message
    data-target-id="<?php echo $profileUser['id']; ?>"
    data-target-username="<?php echo htmlspecialchars($profileUser['username']); ?>"
    class="btn btn-sm btn-primary">
    💬 Mesaj Gönder
    </button>

    <button id="followButton"
    data-action="<?php echo $followButtonAction; ?>"
    data-target-id="<?php echo $profileUser['id']; ?>"
    class="btn btn-sm btn-primary"
    <?php echo $followRequestPending ? 'disabled' : ''; ?>>
    <?php echo $followButtonText; ?>
    </button>

    <button id="blockButton"
    data-target-id="<?php echo $profileUser['id']; ?>"
    class="btn btn-sm btn-danger">
    <?php echo $isBlockedByMe ? 'Engellemeyi Kaldır' : 'Engelle'; ?>
    </button>
    </div>
    <?php endif; ?>

    <!-- Oyun Butonları - Sadece çevrimiçi ve kendisi değilse -->
    <?php if ($isOnline && $currentUserId && !$isProfileOwner && !$isBlockingMe): ?>
    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;">
    <button data-game-challenge
    data-target-id="<?php echo $profileUser['id']; ?>"
    data-game-type="chess"
    class="btn btn-sm btn-primary"
    title="Satranç Oyna">
    ♟️ Satranç
    </button>
    <button data-game-challenge
    data-target-id="<?php echo $profileUser['id']; ?>"
    data-game-type="reversi"
    class="btn btn-sm btn-primary"
    title="Reversi Oyna">
    🔴 Reversi
    </button>
    <button data-game-challenge
    data-target-id="<?php echo $profileUser['id']; ?>"
    data-game-type="tavla"
    class="btn btn-sm btn-primary"
    title="Tavla Oyna">
    🎲 Tavla
    </button>
    </div>
    <?php endif; ?>
    </header>

    <?php if ($isProfileOwner): ?>
    <!-- Profil Resmi Güncelleme Formu -->
    <div class="card" style="margin-bottom: 20px;">
    <h3>🖼️ Profil Resmi Güncelle</h3>
    <form id="profile-picture-form" enctype="multipart/form-data">
    <input type="file" id="profile-picture-input" name="profile_picture" accept="image/jpeg,image/png,image/gif" style="margin-bottom: 10px;">
    <button type="submit" class="btn-primary">Profil Resmini Güncelle</button>
    <div style="font-size: 12px; color: var(--main-text); opacity: 0.7; margin-top: 5px;">
    Maksimum: 2MB, Önerilen: 240x240 px
    </div>
    </form>
    </div>

    <!-- Kullanıcı Adı Değiştirme Formu -->
    <div class="card" style="margin-bottom: 20px;">
    <h3>👤 Kullanıcı Adını Değiştir</h3>
    <form id="username-update-form">
    <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: end;">
    <div>
    <label for="new_username" style="display: block; margin-bottom: 5px; font-size: 14px; color: var(--accent-color);">Yeni Kullanıcı Adı</label>
    <input type="text" id="new_username" name="new_username" value="<?php echo htmlspecialchars($profileUser['username']); ?>" required minlength="3" maxlength="20" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); background: var(--fixed-bg); color: var(--main-text);">
    </div>
    <button type="submit" class="btn-primary">Kullanıcı Adını Güncelle</button>
    </div>
    <div style="font-size: 12px; color: var(--main-text); opacity: 0.7; margin-top: 5px;">
    • 3-20 karakter arası<br>
    • Sadece harf, sayı, alt çizgi (_) ve tire (-)<br>
    • Boşluklar otomatik olarak tire (-) ile değiştirilir<br>
    • Türkçe karakterler İngilizce karşılıklarına dönüştürülür
    </div>
    <div id="username-preview" style="margin-top: 10px; padding: 8px; background: var(--fixed-bg); border-radius: 4px; font-size: 14px; display: none;">
    <strong>Önizleme:</strong> <span id="preview-text"></span>
    </div>
    </form>
    </div>

    <!-- Sosyal Medya Bağlantıları Yönetimi -->
    <div class="card" style="margin-bottom: 20px;">
    <h3>🔗 Sosyal Medya Bağlantıları</h3>
    <div id="current-social-links" style="margin-bottom: 15px;">
    <h4>Mevcut Bağlantılarınız</h4>
    <div id="social-links-list"></div>
    </div>
    <div id="add-social-link-form">
    <h4>Yeni Bağlantı Ekle</h4>
    <form id="social-link-form">
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin-bottom: 10px; max-width: 100%; width:100%;">
    <select id="social-platform-select" required style="padding: 8px; border-radius: 4px; border: 1px solid var(--border-color);">
    <option value="">Platform Seçin</option>
    </select>
    <input type="url" id="social-profile-url" placeholder="Profil URL'si" required style="padding: 8px; border-radius: 4px; border: 1px solid var(--border-color);">
    </div>
    <button type="submit" class="btn-primary">Bağlantı Ekle</button>
    </form>
    </div>
    </div>

    <?php if ($isProfilePrivate): ?>
    <section id="follow-request-management" class="card" style="margin-bottom: 30px;">
    <h3>🔔 Bekleyen Takip İstekleri</h3>
    <div id="follow-requests-list"></div>
    </section>
    <?php endif; ?>

    <?php endif; ?>

    <?php if ($canViewContent): ?>
    <!-- ANA İÇERİK LAYOUT'U -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start; max-width: 100%; width: 100%;">

    <!-- SOL SÜTUN: Çizimler -->
    <div>
    <!-- KULLANICI DUVARI -->
<!-- profile.php dosyasında, aktivite duvarı bölümünü güncelleyin: -->
<section class="card" style="margin-bottom: 20px;">
    <h2 style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
        📅 Aktivite Duvarı
        
        <!-- Aktivite Filtreleri -->
        <?php if ($canViewContent): ?>
        <div id="activity-filters" style="
            display: flex;
            gap: 8px;
            margin-left: auto;
            flex-wrap: wrap;
        "></div>
        <?php endif; ?>
    </h2>
    
    <div id="user-activities">
        <!-- Aktiviteler JavaScript ile yüklenecek -->
        <div style="text-align: center; padding: 40px; opacity: 0.7;">
            <div style="font-size: 3em;">⏳</div>
            <p>Aktiviteler yükleniyor...</p>
        </div>
    </div>
</section>

    <section id="featured-drawing" class="card" style="margin-bottom: 20px;">
    <h2 style="display: flex; align-items: center; gap: 10px;">⭐ Öne Çıkan Çizim</h2>
    <div id="featured-drawing-content"></div>
    </section>

    <section id="user-drawings" class="card">
    <h2 style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">🎨 Tüm Çizimler</h2>
    <div id="user-drawing-list"></div>
    </section>
    </div>
	
<!-- profile.php dosyasında, çizimler bölümünden sonra ekleyin: -->
<?php if ($canViewContent): ?>
<section id="user-flood-sets" class="card" style="margin-top: 30px;">
    <h2 style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
        🌊 Flood Set'leri
        <?php if ($isProfileOwner): ?>
            <button id="profile-flood-set-btn" class="btn-primary" style="margin-left: auto;">
                + Yeni Flood Set'i
            </button>
        <?php endif; ?>
    </h2>
    
    <!-- Kategori Filtreleri -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <button class="category-filter-btn active" data-category="all">
            Tümü
        </button>
        <?php
        // Kategorileri göster
        $categories = [
            'genel' => ['name' => 'Genel', 'emoji' => '📁'],
            'youtube' => ['name' => 'YouTube', 'emoji' => '📺'],
            'twitch' => ['name' => 'Twitch', 'emoji' => '🔴'],
            'eglence' => ['name' => 'Eğlence', 'emoji' => '😂'],
            'oyun' => ['name' => 'Oyun', 'emoji' => '🎮'],
            'sevgi' => ['name' => 'Sevgi', 'emoji' => '❤️'],
            'sanat' => ['name' => 'Sanat', 'emoji' => '🎨'],
            'gunluk' => ['name' => 'Günlük', 'emoji' => '📝']
        ];
        
        foreach ($categories as $key => $cat): ?>
            <button class="category-filter-btn" data-category="<?php echo $key; ?>">
                <?php echo $cat['emoji']; ?> <?php echo $cat['name']; ?>
            </button>
        <?php endforeach; ?>
    </div>
    
    <!-- Flood Set'leri Listesi -->
    <div id="flood-sets-container" class="flood-sets-grid">
        <!-- JavaScript ile doldurulacak -->
    </div>
    
    <!-- Sayfalama -->
    <div id="flood-pagination" style="margin-top: 20px; text-align: center;"></div>
</section>

<script>
// Profil sayfası yüklendiğinde flood set'lerini yükle
document.addEventListener('DOMContentLoaded', function() {
    // Flood set butonu
    const floodSetBtn = document.getElementById('profile-flood-set-btn');
    if (floodSetBtn) {
        floodSetBtn.addEventListener('click', function() {
            if (window.integratedEditor) {
                window.integratedEditor.openModal();
                setTimeout(() => {
                    window.integratedEditor.switchEditor('flood');
                }, 100);
            }
        });
    }
    
    // Kategori filtreleri
    document.querySelectorAll('.category-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Aktif butonu güncelle
            document.querySelectorAll('.category-filter-btn').forEach(b => {
                b.classList.remove('active');
                b.style.background = 'var(--fixed-bg)';
                b.style.color = 'var(--main-text)';
            });
            
            this.classList.add('active');
            this.style.background = 'var(--accent-color)';
            this.style.color = 'white';
            
            // Flood set'lerini filtrele
            const category = this.dataset.category;
            filterFloodSetsByCategory(category);
        });
    });
    
    // Flood set'lerini yükle
    if (window.floodCardSystem && window.PROFILE_DATA.userId) {
        setTimeout(() => {
            window.floodCardSystem.renderProfileFloodSets(
                window.PROFILE_DATA.userId, 
                'flood-sets-container'
            );
        }, 1000);
    }
});

function filterFloodSetsByCategory(category) {
    const cards = document.querySelectorAll('.flood-set-card');
    
    cards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
<style>
.category-filter-btn {
    padding: 8px 15px;
    border: 1px solid var(--border-color);
    background: var(--fixed-bg);
    color: var(--main-text);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9em;
}

.category-filter-btn:hover {
    border-color: var(--accent-color);
    transform: translateY(-1px);
}

.category-filter-btn.active {
    background: var(--accent-color);
    color: white;
    border-color: var(--accent-color);
}

.flood-sets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

@media (max-width: 768px) {
    .flood-sets-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<?php endif; ?>

    <!-- SAĞ SÜTUN: Profil Panosu -->
    <section id="profile-board" class="card" style="position: sticky; top: 20px;">
    <h2 style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">💬 Çizer Panosu</h2>
    <span style="font-size: 0.8em; opacity: 0.7;">(<?php echo $isProfilePrivate ? '🔒 Sadece takipçiler' : '🌍 Herkese açık'; ?>)</span>

    <?php if ($currentUserId && $canViewContent): ?>
    <div style="margin-bottom: 20px;">
    <textarea id="boardCommentInput" placeholder="Panoya bir mesaj yaz... İlk yorumu sen yap! (Resim, video veya ses de ekleyebilirsin)" style="width: 100%; margin-bottom: 10px; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--fixed-bg); color: var(--main-text); resize: vertical; min-height: 80px; font-family: inherit;"></textarea>

    <div style="margin-bottom: 10px;">
    <input type="file" id="boardFileInput" style="display: none;" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.mp3,.mp4,.wav">
    <button onclick="document.getElementById('boardFileInput').click()" class="btn-secondary" style="width: 100%; margin-bottom: 5px;">📎 Dosya Ekle (Resim, Video, Ses)</button>
    <div id="boardFileInfo" style="font-size: 12px; color: var(--main-text); opacity: 0.7; display: none; padding: 8px; background: var(--fixed-bg); border-radius: 4px; border: 1px solid var(--accent-color);">
    <span>Seçilen dosya:</span>
    <span id="boardFileName" style="font-weight: bold; margin-left: 5px;"></span>
    <button onclick="window.clearBoardFile()" style="margin-left: 10px; background: #dc3545; color: white; border: none; border-radius: 3px; padding: 2px 6px; font-size: 12px; cursor: pointer;">✖</button>
    </div>
    </div>

    <button id="postCommentBtn" class="btn-primary" style="width: 100%;">📝 Panoya Gönder</button>

    <?php if ($isProfilePrivate && !$isProfileOwner): ?>
    <div style="font-size: 12px; color: var(--accent-color); margin-top: 8px; text-align: center;">🔒 Bu gizli profilde sadece takipçiler pano mesajı yazabilir</div>
    <?php endif; ?>
    </div>
    <?php elseif ($currentUserId && !$canViewContent): ?>
    <div style="background: var(--fixed-bg); padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 15px;">
    <p style="margin: 0; color: var(--main-text);">🔒 Bu gizli profilin panosunu görmek için takip isteği göndermelisiniz.</p>
    <button id="followRequestBtn" data-action="follow" class="btn-primary" style="margin-top: 10px;">Takip İsteği Gönder</button>
    </div>
    <?php else: ?>
    <div style="background: var(--fixed-bg); padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 15px;">
    <p style="margin: 0; color: var(--main-text);">Pano mesajı yazmak için <a href="#" data-modal-toggle="login_modal" style="color: var(--accent-color);">giriş yapmalısın</a></p>
    </div>
    <?php endif; ?>

    <div id="board-comments-list" style="max-height: 400px; overflow-y: auto;"></div>
    </section>
    </div>
    <?php endif; ?>
    </div>
    <?php
    require_once __DIR__ . '/templates/messages_modal.php';
    require_once __DIR__ . '/templates/modals.php';
    ?>
    <script>
    // Profile sistemini başlat
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof profileSystem !== 'undefined') {
            profileSystem.init();
        }

        // Eski fonksiyonlar için compatibility
        window.handleRequestAction = async function(requesterId, action) {
            try {
                const response = await fetch(SITE_BASE_URL + 'core/manage_follow_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `requester_id=${requesterId}&action=${action}`
                });
                const result = await response.json();
                showNotification(result.message, result.success ? 'success' : 'error');
                if (result.success && typeof profileSystem !== 'undefined') {
                    profileSystem.fetchFollowRequests();
                }
            } catch (error) {
                console.error('İstek yönetim hatası:', error);
                showNotification('İstek yönetilirken hata oluştu.', 'error');
            }
        };

        window.clearBoardFile = function() {
            if (typeof profileSystem !== 'undefined') {
                profileSystem.clearBoardFile();
            }
        };
        if (document.getElementById('active-games-list')) {
            window.gameSystem.loadActiveGames();
            setInterval(window.gameSystem.loadActiveGames, 30000);
        }
    });
    </script>
    <?php require_once __DIR__ . '/templates/footer.php'; ?>
