<?php
// profile.php - HATA DÜZELTMELİ
require_once 'config.php';
require_once 'Auth.php';
require_once 'Drawing.php';
require_once 'functions.php';
require_once 'counter_manager.php';
require_once 'Router.php';

$profileUsername = $_GET['username'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$profileUsername) {
    header('Location: ../index.php');
    exit;
}

try {
    $db = getDbConnection();
    $userModel = new User();

    // 1. Profil sahibini çek - profile_picture dahil
    $profileUser = $userModel->findByUsername($profileUsername);

    if (!$profileUser) {
        http_response_code(404);
        die("Çizer Kullanıcı bulunamadı.");
    }

    $isProfilePrivate = ($profileUser['privacy_mode'] === 'private');
    $isProfileOwner = ($currentUserId === $profileUser['id']);

    // Görüntüleme Sayacını Artır (Kendisi değilse)
    if (!$isProfileOwner) {
        $db->exec("UPDATE users SET profile_views = profile_views + 1 WHERE id = {$profileUser['id']}");
        $profileUser['profile_views']++; // Anlık görüntü için artır
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

    // Takipçi ve takip edilen sayılarını al
    $followerCount = $db->query("SELECT COUNT(*) FROM follows WHERE following_id = {$profileUser['id']}")->fetchColumn();
    $followingCount = $db->query("SELECT COUNT(*) FROM follows WHERE follower_id = {$profileUser['id']}")->fetchColumn();

    // Profil fotoğrafını kontrol et ve formatla - HATA DÜZELTİLDİ
    $profilePicSrc = formatProfilePicture($profileUser['profile_picture'] ?? null);

} catch (PDOException $e) {
    http_response_code(500);
    die("Veritabanı hatası: " . $e->getMessage());
}

$totalViews = $counters['total_views'] ?? 0;
$onlineUsers = $counters['online_users'] ?? 0;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta property="og:title" content="Emoji Piksel Sanatı ve Sosyal Sohbet Platformu">
<meta property="og:description" content="YouTube Sohbetleri için emojilerle sanat mesajları (Flood Mesajları) oluşturan bir eğlence ve sosyal platformdur!">
<meta property="og:type" content="website">
<meta property="og:url" content="https://flood.page.gd/">
<meta property="og:image" content="../four-hundred-eighty-kilograms-of-gold-worth-open-graph-image.png">
<meta property="og:site_name" content="Emoji Piksel Sanatı">
<meta property="og:locale" content="tr_TR">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($profileUser['username'] ?? ''); ?> - Profil</title>
<link rel="stylesheet" href="../styles.css">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<!-- FÜTÜRİSTİK ARKA PLAN -->
<div id="background-grid"></div>

<div id="notification"></div>

<!-- STATS BAR -->
<div id="stats-bar" class="card">
<div class="info-group">
<a href="/" class="btn btn-sm btn-primary">Ana Sayfa</a>
<span>Toplam Ziyaret: <strong><?php echo number_format($totalViews); ?></strong></span>
<span>Aktif Kullanıcı: <strong style="color:#4CAF50"><?php echo number_format($onlineUsers); ?></strong></span>
</div>
<div class="user-actions">
<?php if (Auth::isLoggedIn()): ?>
<span class="greeting">Hoş geldin,
<strong><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></strong>!
</span>
<?php if (in_array($_SESSION['user_role'] ?? 'user', ['admin', 'moderator'])): ?>
<a href="../admin/dashboard.php" class="btn btn-sm btn-primary">Yönetim Paneli</a>
<?php endif; ?>
<a href="../logout.php" class="btn btn-sm btn-danger" id="logoutButton">Çıkış</a>
<?php else: ?>
<button class="btn btn-sm btn-primary" data-modal-toggle="login_modal">Giriş</button>
<button class="btn btn-sm btn-secondary" data-modal-toggle="register_modal">Kayıt</button>
<?php endif; ?>
</div>
</div>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">

<!-- PROFİL BAŞLIK BÖLÜMÜ -->
<header class="card" style="margin-bottom: 20px; padding: 25px;">
<div style="display: flex; align-items: center; gap: 20px;">
<!-- Profil Fotoğrafı -->
<div style="flex-shrink: 0;">
<img src="<?php echo htmlspecialchars($profilePicSrc); ?>"
alt="Profil Fotoğrafı"
style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--accent-color); object-fit: cover;">
</div>

<!-- Kullanıcı Bilgileri -->
<div style="flex-grow: 1;">
<h1 style="margin: 0 0 8px 0; font-size: 24px; color: var(--accent-color);">
<?php echo htmlspecialchars($profileUser['username'] ?? ''); ?>
<?php
// Rütbe yıldızlarını göster - HATA DÜZELTİLDİ
$userRank = calculateUserRank($profileUser['id'] ?? 0);
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
    <div style="margin-top: 10px;">
    <?php foreach($socialLinks as $link): ?>
    <a href="<?php echo htmlspecialchars($link['profile_url'] ?? ''); ?>"
    target="_blank"
    style="margin-right: 10px; text-decoration: none; font-size: 20px;"
    title="<?php echo htmlspecialchars($link['name'] ?? ''); ?>">
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

    <!-- Aksiyon Butonları -->
    <?php if ($currentUserId && !$isProfileOwner): ?>
    <div style="flex-shrink: 0;">
    <button id="followButton" data-action="<?php echo $followButtonAction; ?>"
    data-target-id="<?php echo $profileUser['id']; ?>"
    class="btn-primary"
    <?php echo $followRequestPending ? 'disabled' : ''; ?>>
    <?php echo $followButtonText; ?>
    </button>
    <button id="blockButton" data-target-id="<?php echo $profileUser['id']; ?>"
    class="btn-danger" style="margin-left: 10px;">
    <?php echo $isBlockedByMe ? 'Engellemeyi Kaldır' : 'Engelle'; ?>
    </button>
    </div>
    <?php endif; ?>
    </div>
    </header>

    <?php if ($isProfileOwner): ?>
    <!-- Profil Resmi Güncelleme Formu -->
    <div class="card" style="margin-bottom: 20px;">
    <h3>🖼️ Profil Resmi Güncelle</h3>
    <form id="profile-picture-form" enctype="multipart/form-data">
    <input type="file" id="profile-picture-input" name="profile_picture"
    accept="image/jpeg,image/png,image/gif" style="margin-bottom: 10px;">
    <button type="submit" class="btn-primary">Profil Resmini Güncelle</button>
    <div style="font-size: 12px; color: var(--main-text); opacity: 0.7; margin-top: 5px;">
    Maksimum: 2MB, Önerilen: 240x240 px
    </div>
    </form>
    </div>

    <script>
    // Profil resmi yükleme
    document.getElementById('profile-picture-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const fileInput = document.getElementById('profile-picture-input');
        const file = fileInput.files[0];

        if (!file) {
            showNotification('Lütfen bir resim seçin.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('profile_picture', file);

        try {
            const response = await fetch('../upload_profile_picture.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } catch (error) {
            console.error('Profil resmi yükleme hatası:', error);
            showNotification('Yükleme sırasında hata oluştu.', 'error');
        }
    });
    </script>

    <!-- Sosyal Medya Bağlantıları Yönetimi -->
    <div class="card" style="margin-bottom: 20px;">
    <h3>🔗 Sosyal Medya Bağlantıları</h3>

    <!-- Mevcut bağlantılar -->
    <div id="current-social-links" style="margin-bottom: 15px;">
    <h4>Mevcut Bağlantılarınız</h4>
    <div id="social-links-list">
    <!-- JavaScript ile doldurulacak -->
    </div>
    </div>

    <!-- Yeni bağlantı ekleme formu -->
    <div id="add-social-link-form">
    <h4>Yeni Bağlantı Ekle</h4>
    <form id="social-link-form">
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin-bottom: 10px;">
    <select id="social-platform-select" required style="padding: 8px; border-radius: 4px; border: 1px solid var(--border-color);">
    <option value="">Platform Seçin</option>
    <!-- Platformlar JavaScript ile yüklenecek -->
    </select>
    <input type="url" id="social-profile-url" placeholder="Profil URL'si" required style="padding: 8px; border-radius: 4px; border: 1px solid var(--border-color);">
    </div>
    <button type="submit" class="btn-primary">Bağlantı Ekle</button>
    </form>
    </div>
    </div>

    <script>
    // Sosyal medya bağlantılarını yükle
    async function loadSocialLinks() {
        try {
            const response = await fetch('../get_user_social_links.php');
            const result = await response.json();

            const container = document.getElementById('social-links-list');
            if (result.success && result.links.length > 0) {
                container.innerHTML = result.links.map(link => `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; margin-bottom: 5px;">
                <div>
                <span style="font-size: 20px;">${link.emoji}</span>
                <strong>${link.name}</strong>
                <a href="${link.profile_url}" target="_blank" style="margin-left: 10px; color: var(--accent-color);">${link.profile_url}</a>
                </div>
                <button onclick="removeSocialLink(${link.platform_id})" class="btn-danger btn-sm">Kaldır</button>
                </div>
                `).join('');
            } else {
                container.innerHTML = '<p style="opacity: 0.7;">Henüz sosyal medya bağlantınız yok.</p>';
            }
        } catch (error) {
            console.error('Sosyal medya bağlantıları yüklenirken hata:', error);
        }
    }

    // Platform seçeneklerini yükle
    async function loadPlatformOptions() {
        try {
            const response = await fetch('../get_social_platforms.php');
            const result = await response.json();

            const select = document.getElementById('social-platform-select');
            if (result.success && result.platforms.length > 0) {
                select.innerHTML = '<option value="">Platform Seçin</option>' +
                result.platforms.map(platform =>
                `<option value="${platform.id}" data-regex="${platform.url_regex || ''}">${platform.emoji} ${platform.name}</option>`
                ).join('');
            }
        } catch (error) {
            console.error('Platform seçenekleri yüklenirken hata:', error);
        }
    }

    // Sosyal medya bağlantısı ekle
    document.getElementById('social-link-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const platformId = document.getElementById('social-platform-select').value;
        const profileUrl = document.getElementById('social-profile-url').value;

        if (!platformId || !profileUrl) {
            showNotification('Lütfen platform ve URL girin.', 'error');
            return;
        }

        try {
            const response = await fetch('../profile_social_links.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&platform_id=${platformId}&profile_url=${encodeURIComponent(profileUrl)}`
            });

            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                document.getElementById('social-link-form').reset();
                loadSocialLinks();
            }
        } catch (error) {
            console.error('Bağlantı ekleme hatası:', error);
            showNotification('Bağlantı eklenirken hata oluştu.', 'error');
        }
    });

    // Sosyal medya bağlantısını kaldır
    async function removeSocialLink(platformId) {
        const confirmed = await showConfirm(
            'Bağlantıyı Kaldır',
            'Bu sosyal medya bağlantısını kaldırmak istediğinizden emin misiniz?'
        );

        if (confirmed) {
            try {
                const response = await fetch('../profile_social_links.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=remove&platform_id=${platformId}`
                });

                const result = await response.json();
                showNotification(result.message, result.success ? 'success' : 'error');

                if (result.success) {
                    loadSocialLinks();
                }
            } catch (error) {
                console.error('Bağlantı kaldırma hatası:', error);
                showNotification('Bağlantı kaldırılırken hata oluştu.', 'error');
            }
        }
    }

    // Sayfa yüklendiğinde sosyal medya bileşenlerini yükle
    document.addEventListener('DOMContentLoaded', function() {
        if (window.PROFILE_DATA.isProfileOwner) {
            loadSocialLinks();
            loadPlatformOptions();
        }
    });
    </script>
    <?php endif; ?>

    <?php if ($isProfileOwner && $isProfilePrivate): ?>
    <section id="follow-request-management" class="card" style="margin-bottom: 30px;">
    <h3>🔔 Bekleyen Takip İstekleri</h3>
    <div id="follow-requests-list">
    <p>Takip istekleri yükleniyor...</p>
    </div>
    </section>
    <?php endif; ?>

    <?php if ($canViewContent): ?>
    <!-- ANA İÇERİK LAYOUT'U -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start;">

    <!-- SOL SÜTUN: Çizimler -->
    <div>
    <section id="featured-drawing" class="card" style="margin-bottom: 20px;">
    <h2 style="display: flex; align-items: center; gap: 10px;">
    ⭐ Öne Çıkan Çizim
    </h2>
    <div id="featured-drawing-content">
    <p style="text-align: center; color: var(--main-text); opacity: 0.7;">
    Öne çıkan çizim yükleniyor...
    </p>
    </div>
    </section>

    <section id="user-drawings" class="card">
    <h2 style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
    🎨 Tüm Çizimler
    </h2>
    <div id="user-drawing-list">
    <p style="text-align: center; color: var(--main-text); opacity: 0.7;">
    Çizimler yükleniyor...
    </p>
    </div>
    </section>
    </div>

    <!-- SAĞ SÜTUN: Pano ve İstatistikler -->
    <div>
    <section id="profile-board" class="card" style="position: sticky; top: 20px;">
    <h2 style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
    💬 Çizer Panosu
    </h2>

    <?php if ($currentUserId): ?>
    <div style="margin-bottom: 20px;">
    <textarea id="boardCommentInput"
    placeholder="Panoya bir mesaj yaz... İlk yorumu sen yap!"
    style="width: 100%; margin-bottom: 10px; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--fixed-bg); color: var(--main-text); resize: vertical; min-height: 80px; font-family: inherit;"></textarea>
    <button id="postCommentBtn" class="btn-primary" style="width: 100%;">
    📝 Panoya Gönder
    </button>
    </div>
    <?php else: ?>
    <div style="background: var(--fixed-bg); padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 15px;">
    <p style="margin: 0; color: var(--main-text);">
    Pano mesajı yazmak için <a href="#" data-modal-toggle="login_modal" style="color: var(--accent-color);">giriş yapmalısın</a>
    </p>
    </div>
    <?php endif; ?>

    <div id="board-comments-list" style="max-height: 400px; overflow-y: auto;">
    <p style="text-align: center; color: var(--main-text); opacity: 0.7;">
    Panoda henüz mesaj yok... İlk mesajı sen yaz! ✨
    </p>
    </div>
    </section>
    </div>
    </div>

    <?php else: ?>
    <section class="card" style="text-align: center; padding: 40px;">
    <div style="font-size: 48px; margin-bottom: 20px;">🔒</div>
    <h2 style="color: var(--accent-color); margin-bottom: 15px;">Gizli Profil</h2>
    <p style="margin-bottom: 20px; color: var(--main-text);">
    Bu profil gizlidir. İçeriği görmek için takip isteği göndermelisiniz.
    </p>
    <?php if ($currentUserId && !$isProfileOwner): ?>
    <button id="followRequestBtn" data-action="follow" class="btn-primary">
    Takip İsteği Gönder
    </button>
    <?php endif; ?>
    </section>
    <?php endif; ?>

    </div>

    <!-- MODALLAR -->
    <div id="login_modal" class="modal">
    <div class="modal-content">
    <button class="modal-close">&times;</button>
    <h2>Giriş Yap</h2>
    <form action="../login_handler" method="POST" class="auth-form">
    <input type="text" name="username" placeholder="Kullanıcı Adı" required>
    <input type="password" name="password" placeholder="Şifre" required>
    <button type="submit">Giriş Yap</button>
    </form>
    <div class="divider"><span>YA DA</span></div>
    <a href="../login.php" class="btn-google">
    <img src="../google_logo.svg" alt="Google Logo" style="width: 20px; height: 20px; margin-right: 10px;">
    Google ile Giriş Yap
    </a>
    <div class="auth-links">
    <p>Hesabın yok mu? <a href="#" data-modal-switch="register_modal">Hemen kaydol.</a></p>
    </div>
    </div>
    </div>

    <div id="register_modal" class="modal">
    <div class="modal-content">
    <button class="modal-close">&times;</button>
    <h2>Yeni Kayıt</h2>
    <form action="../register" method="POST" class="auth-form">
    <input type="text" name="username" placeholder="Kullanıcı Adı" required minlength="3" maxlength="20">
    <input type="email" name="email" placeholder="E-posta" required>
    <input type="password" name="password" placeholder="Şifre (Min 6 Karakter)" required minlength="6">
    <input type="password" name="password_confirm" placeholder="Şifre (Tekrar)" required>
    <button type="submit">Kayıt Ol</button>
    </form>
    <div class="divider"><span>YA DA</span></div>
    <a href="../login.php" class="btn-google">
    <img src="../google_logo.svg" alt="Google Logo" style="width: 20px; height: 20px; margin-right: 10px;">
    Google ile Kayıt Ol
    </a>
    <div class="auth-links">
    <p>Zaten hesabın var mı? <a href="#" data-modal-switch="login_modal">Giriş Yap.</a></p>
    </div>
    </div>
    </div>
    <script>
    // Current User bilgisini global olarak ayarla
    window.currentUser = {
        id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
        username: <?php echo json_encode($_SESSION['username'] ?? null); ?>,
        role: <?php echo json_encode($_SESSION['role'] ?? 'user'); ?>
    };
    </script>
    <script src="../main.js"></script>
    <script>
    // Global değişkenler
    window.PROFILE_DATA = {
        userId: <?php echo $profileUser['id']; ?>,
        currentUserId: <?php echo json_encode($currentUserId); ?>,
        isProfileOwner: <?php echo json_encode($isProfileOwner); ?>,
        profileUsername: "<?php echo htmlspecialchars($profileUser['username']); ?>"
    };

    // PROFİL FOTOĞRAFI İŞLEME - TÜM YERLERDE TUTARLILIK
    function formatProfilePicture(profilePicture) {
        if (!profilePicture || profilePicture === 'default.png') {
            return '/images/default.png';
        }

        if (profilePicture.startsWith('data:image')) {
            return profilePicture;
        }

        // Base64 verisini data URL formatına çevir
        return 'data:image/jpeg;base64,' + profilePicture;
    }

    // Profil sayfasına özgü işlevler
    document.addEventListener('DOMContentLoaded', function() {
        // Modal sistemini başlat
        if (typeof initModalSystem === 'function') {
            initModalSystem();
        }

        // Buton event listener'larını ekle
        initProfileEventListeners();

        // İçerikleri yükle
        loadProfileContent();
    });

    function initProfileEventListeners() {
        // Takip butonu
        const followBtn = document.getElementById('followButton');
        if (followBtn) {
            followBtn.addEventListener('click', function() {
                handleProfileFollowAction(this);
            });
        }

        // Engelleme butonu
        const blockBtn = document.getElementById('blockButton');
        if (blockBtn) {
            blockBtn.addEventListener('click', function() {
                handleProfileBlockAction(this);
            });
        }

        // Takip isteği butonu (gizli profil)
        const followRequestBtn = document.getElementById('followRequestBtn');
        if (followRequestBtn) {
            followRequestBtn.addEventListener('click', function() {
                handleProfileFollowAction(this);
            });
        }

        // Yorum gönderme butonu
        const postCommentBtn = document.getElementById('postCommentBtn');
        if (postCommentBtn) {
            postCommentBtn.addEventListener('click', function() {
                postProfileComment();
            });
        }
    }

    async function handleProfileFollowAction(button) {
        if (!window.PROFILE_DATA.currentUserId) {
            showNotification('Lütfen önce oturum açın.', 'error');
            return;
        }

        const action = button.dataset.action === 'follow' ? 'follow' : 'unfollow';

        try {
            const response = await fetch('../follow_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `target_id=${window.PROFILE_DATA.userId}&action=${action}`
            });
            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');
            if (result.success) setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            console.error('Takip işlemi hatası:', error);
            showNotification('İşlem sırasında hata oluştu.', 'error');
        }
    }

    async function handleProfileBlockAction(button) {
        if (!window.PROFILE_DATA.currentUserId) {
            showNotification('Lütfen önce oturum açın.', 'error');
            return;
        }

        const isBlocking = button.textContent.includes('Engellemeyi Kaldır');
        const action = isBlocking ? 'unblock' : 'block';

        const confirmed = await showConfirm(
            'Engelleme İşlemi',
            `Bu kullanıcıyı gerçekten ${action === 'block' ? 'engellemek' : 'engellemeyi kaldırmak'} istiyor musunuz?`
        );

        if (confirmed) {
            try {
                const response = await fetch('../block_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `target_id=${window.PROFILE_DATA.userId}&action=${action}`
                });
                const result = await response.json();
                showNotification(result.message, result.success ? 'success' : 'error');
                if (result.success) setTimeout(() => window.location.reload(), 1500);
            } catch (error) {
                console.error('Engelleme işlemi hatası:', error);
                showNotification('İşlem sırasında hata oluştu.', 'error');
            }
        }
    }

    async function postProfileComment() {
        const inputElement = document.getElementById('boardCommentInput');
        const content = inputElement.value.trim();

        if (content === '') {
            showNotification('Lütfen panoya yazmak için bir mesaj girin.', 'error');
            return;
        }

        try {
            const response = await fetch('../comment_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    target_type: 'profile',
                    target_id: window.PROFILE_DATA.userId,
                    content: content
                })
            });
            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                inputElement.value = '';
                fetchProfileComments();
            }
        } catch (error) {
            console.error('Yorum gönderme hatası:', error);
            showNotification('Yorum gönderilirken hata oluştu.', 'error');
        }
    }

    async function fetchProfileComments() {
        const listElement = document.getElementById('board-comments-list');
        if (!listElement) return;

        listElement.innerHTML = '<p style="text-align: center; color: var(--main-text); opacity: 0.7;">Mesajlar yükleniyor...</p>';

        try {
            const response = await fetch(`../fetch_comments.php?type=profile&id=${window.PROFILE_DATA.userId}`);
            const result = await response.json();

            if (result.success && result.comments.length > 0) {
                listElement.innerHTML = result.comments.map(comment => {
                    let profilePicSrc = formatProfilePicture(comment.profile_picture);

                    const profilePic = `<img src="${profilePicSrc}" alt="Profil" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">`;

                    return `
                    <div class="comment-item" style="border-bottom: 1px solid var(--border-color); padding: 15px 0;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    ${profilePic}
                    <div>
                    <strong><a href="/${comment.username}/" style="color: var(--accent-color); text-decoration: none;">${comment.username}</a></strong>
                    <div style="color: var(--main-text); opacity: 0.7; font-size: 0.85em;">
                    ${new Date(comment.created_at).toLocaleString('tr-TR')}
                    </div>
                    </div>
                    </div>
                    <div style="white-space: pre-wrap; margin: 0; padding: 12px; background: var(--fixed-bg); border-radius: 8px; font-size: 0.95em;">
                    ${comment.content}
                    </div>
                    </div>
                    `;
                }).join('');
            } else {
                listElement.innerHTML = `
                <div style="text-align: center; padding: 30px; color: var(--main-text);">
                <div style="font-size: 48px; margin-bottom: 15px;">💬</div>
                <p style="margin-bottom: 15px; opacity: 0.8;">Panoda henüz mesaj yok...</p>
                <p style="opacity: 0.6; font-size: 0.9em;">İlk mesajı yazmak ister misin? ✨</p>
                </div>
                `;
            }
        } catch (error) {
            listElement.innerHTML = '<p style="text-align: center; color: #dc3545;">Pano mesajları yüklenirken hata oluştu.</p>';
        }
    }

    async function fetchFollowRequests() {
        const listElement = document.getElementById('follow-requests-list');
        if (!listElement) return;

        listElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">İstekler yükleniyor...</p>';

        try {
            const response = await fetch('../fetch_follow_requests.php');
            const result = await response.json();

            if (result.success && result.requests.length > 0) {
                listElement.innerHTML = result.requests.map(request => {
                    let profilePicSrc = formatProfilePicture(request.requester_picture);

                    const profilePic = `<img src="${profilePicSrc}" alt="Profil" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">`;

                    return `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                    ${profilePic}
                    <div>
                    <a href="/${request.requester_username}/" style="color: var(--accent-color); font-weight: 500; text-decoration: none;">
                    ${request.requester_username}
                    </a>
                    <div style="color: var(--main-text); opacity: 0.7; font-size: 0.85em;">
                    ${new Date(request.requested_at).toLocaleString('tr-TR')}
                    </div>
                    </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                    <button onclick="handleRequestAction(${request.requester_id}, 'approve')"
                    class="btn-success" style="padding: 6px 12px; font-size: 0.85em;">
                    ✅ Onayla
                    </button>
                    <button onclick="handleRequestAction(${request.requester_id}, 'reject')"
                    class="btn-danger" style="padding: 6px 12px; font-size: 0.85em;">
                    ❌ Reddet
                    </button>
                    </div>
                    </div>
                    `;
                }).join('');
            } else if (result.success) {
                listElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">Bekleyen takip isteği bulunmamaktadır.</p>';
            } else {
                listElement.innerHTML = `<p style="text-align: center; color: #dc3545;">❌ Hata: ${result.message}</p>`;
            }
        } catch (error) {
            listElement.innerHTML = '<p style="text-align: center; color: #dc3545;">Sunucu ile iletişim hatası.</p>';
        }
    }

    async function handleRequestAction(requesterId, action) {
        try {
            const response = await fetch('../manage_follow_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `requester_id=${requesterId}&action=${action}`
            });
            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');
            if (result.success) fetchFollowRequests();
        } catch (error) {
            console.error('İstek yönetim hatası:', error);
            showNotification('İstek yönetilirken hata oluştu.', 'error');
        }
    }

    async function loadProfileContent() {
        // Yorumları yükle
        if (document.getElementById('profile-board')) {
            fetchProfileComments();
        }

        // Takip isteklerini yükle (sadece profil sahibi için)
        if (window.PROFILE_DATA.isProfileOwner && document.getElementById('follow-requests-list')) {
            fetchFollowRequests();
        }

        // Çizimleri yükle
        if (document.getElementById('user-drawing-list')) {
            fetchUserDrawings();
        }
    }

    async function fetchUserDrawings() {
        const listElement = document.getElementById('user-drawing-list');
        const featuredElement = document.getElementById('featured-drawing-content');

        if (!listElement) return;

        listElement.innerHTML = '<p style="text-align: center; color: var(--main-text); opacity: 0.7;">Çizimler yükleniyor...</p>';

        try {
            const response = await fetch(`../fetch_user_drawings.php?user_id=${window.PROFILE_DATA.userId}`);
            const result = await response.json();

            if (result.success && Object.keys(result.categorized_drawings).length > 0) {
                listElement.innerHTML = '';

                // Öne çıkan çizimi göster
                let featuredDrawing = result.featured_drawing;
                if (!featuredDrawing) {
                    // En son çizimi öne çıkar
                    const allDrawings = Object.values(result.categorized_drawings).flat();
                    if (allDrawings.length > 0) {
                        allDrawings.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
                        featuredDrawing = allDrawings[0];
                    }
                }

                if (featuredDrawing && typeof window.createDrawingCard === 'function') {
                    featuredElement.innerHTML = '';
                    const card = window.createDrawingCard(featuredDrawing);
                    featuredElement.appendChild(card);
                }

                // Kategorilere göre çizimleri listele
                for (const category in result.categorized_drawings) {
                    const categoryHeader = document.createElement('h3');
                    categoryHeader.textContent = `📁 ${category}`;
                    categoryHeader.style.marginTop = '25px';
                    categoryHeader.style.marginBottom = '15px';
                    categoryHeader.style.color = 'var(--accent-color)';
                    categoryHeader.style.paddingBottom = '8px';
                    categoryHeader.style.borderBottom = '2px solid var(--border-color)';
                    listElement.appendChild(categoryHeader);

                    const drawingContainer = document.createElement('div');
                    drawingContainer.className = 'drawings-grid';
                    drawingContainer.style.marginBottom = '30px';

                    result.categorized_drawings[category].forEach(drawing => {
                        if (typeof window.createDrawingCard === 'function') {
                            const card = window.createDrawingCard(drawing);
                            drawingContainer.appendChild(card);
                        }
                    });
                    listElement.appendChild(drawingContainer);
                }
            } else {
                listElement.innerHTML = `
                <div style="text-align: center; padding: 40px; color: var(--main-text);">
                <div style="font-size: 48px; margin-bottom: 15px;">🎨</div>
                <p style="margin-bottom: 15px; opacity: 0.8;">Bu çizerin henüz kayıtlı çizimi bulunmamaktadır.</p>
                <p style="opacity: 0.6; font-size: 0.9em;">İlk çizimi sen yapmak ister misin? ✨</p>
                </div>
                `;
            }
        } catch (error) {
            listElement.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #dc3545;">
            <p>❌ Çizimler yüklenirken hata oluştu.</p>
            <p style="font-size: 0.9em; opacity: 0.8;">Lütfen sayfayı yenileyin veya daha sonra tekrar deneyin.</p>
            </div>
            `;
        }
    }
    </script>
    </body>
    </html>
