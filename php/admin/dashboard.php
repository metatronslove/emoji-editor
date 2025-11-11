<?php
// admin/dashboard.php - GÜNCELLENMİŞ
require_once '../config.php';
require_once '../Auth.php';
require_once '../Drawing.php';
require_once '../functions.php';
require_once '../counter_manager.php';
require_once '../Router.php';
$db = getDbConnection();

$userRole = $_SESSION['user_role'] ?? 'user';
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId || !in_array($userRole, ['admin', 'moderator'])) {
    http_response_code(403);
    die("Yetkisiz Erişim. Bu sayfayı görüntüleme izniniz yok.");
}

$isAdmin = ($userRole === 'admin');

// Dashboard verilerini çek
$stats = [];
try {
    // Temel istatistikler
    $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_drawings'] = $db->query("SELECT COUNT(*) FROM drawings")->fetchColumn();
    $stats['total_comments'] = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $stats['new_users_today'] = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();

    // Son 7 günlük aktivite
    $activityData = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM drawings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard istatistik hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yönetim Paneli | <?php echo ucfirst($userRole); ?></title>
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
<span>Toplam Kullanıcı: <strong><?php echo number_format($stats['total_users'] ?? 0); ?></strong></span>
<span>Bugünkü Kayıt: <strong style="color:#4CAF50"><?php echo number_format($stats['new_users_today'] ?? 0); ?></strong></span>
</div>
<div class="user-actions">
<span class="greeting">Hoş geldin,
<strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
</span>
<a href="../" class="btn btn-sm btn-primary">Siteye Dön</a>
<a href="../logout.php" class="btn btn-sm btn-danger">Çıkış</a>
</div>
</div>

<div class="container">
<h1>Yönetim Paneli</h1>
<p>Giriş Yapan: <b><?php echo $_SESSION['username']; ?></b> (Rol: <?php echo ucfirst($userRole); ?>)</p>
<hr>

<!-- DASHBOARD İSTATİSTİKLERİ -->
<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
<div class="card" style="text-align: center; padding: 20px;">
<h3>👥 Toplam Kullanıcı</h3>
<p style="font-size: 24px; font-weight: bold; color: var(--accent-color);"><?php echo number_format($stats['total_users'] ?? 0); ?></p>
</div>
<div class="card" style="text-align: center; padding: 20px;">
<h3>🎨 Toplam Çizim</h3>
<p style="font-size: 24px; font-weight: bold; color: var(--accent-color);"><?php echo number_format($stats['total_drawings'] ?? 0); ?></p>
</div>
<div class="card" style="text-align: center; padding: 20px;">
<h3>💬 Toplam Yorum</h3>
<p style="font-size: 24px; font-weight: bold; color: var(--accent-color);"><?php echo number_format($stats['total_comments'] ?? 0); ?></p>
</div>
<div class="card" style="text-align: center; padding: 20px;">
<h3>🆕 Bugünkü Kayıt</h3>
<p style="font-size: 24px; font-weight: bold; color: #4CAF50;"><?php echo number_format($stats['new_users_today'] ?? 0); ?></p>
</div>
</div>

<!-- YÖNETİM SEKMELERİ -->
<div class="tabs-container">
<div class="tabs">
<button class="tab-link active" data-tab="user-management">Kullanıcı Yönetimi</button>
<button class="tab-link" data-tab="content-moderation">İçerik Moderasyonu</button>
<button class="tab-link" data-tab="announcements">Duyurular</button>
<button class="tab-link" data-tab="social-media">Sosyal Medya Ayarları</button>
<?php if ($isAdmin): ?>
<button class="tab-link" data-tab="rank-system">Rütbe Sistemi</button>
<button class="tab-link" data-tab="private-messages">Özel Mesajlar</button>
<?php endif; ?>
</div>

<!-- KULLANICI YÖNETİMİ -->
<div id="user-management" class="tab-content active">
<h2>Kullanıcı Yönetimi</h2>

<!-- Kullanıcı Arama -->
<div style="margin-bottom: 20px;">
<input type="text" id="userSearch" placeholder="Kullanıcı adı veya email ara..."
style="width: 300px; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
<button onclick="searchUsers()" class="btn-primary">Ara</button>
</div>

<div id="user-list-container">
<!-- Kullanıcı listesi buraya yüklenecek -->
</div>
</div>

<!-- İÇERİK MODERASYONU -->
<div id="content-moderation" class="tab-content">
<h2>İçerik Moderasyonu</h2>
<div id="content-moderation-area">
<p>İçerikler yükleniyor...</p>
</div>
</div>

<!-- DUYURULAR -->
<div id="announcements" class="tab-content">
<h2>Site Duyuruları</h2>
<div class="card">
<h3>Yeni Duyuru Ekle</h3>
<form id="announcement-form">
<textarea id="announcement-content" placeholder="Duyuru içeriği..."
style="width: 100%; height: 120px; margin-bottom: 10px; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color);"></textarea>
<select id="announcement-type" style="margin-bottom: 10px; padding: 8px; border-radius: 4px;">
<option value="info">ℹ️ Bilgi</option>
<option value="warning">⚠️ Uyarı</option>
<option value="success">✅ Başarı</option>
<option value="critical">🚨 Kritik</option>
</select>
<button type="submit" class="btn-primary">Duyuruyu Yayınla</button>
</form>
</div>

<div id="announcements-list" style="margin-top: 20px;">
<!-- Duyurular listesi buraya yüklenecek -->
</div>
</div>

<!-- SOSYAL MEDYA AYARLARI -->
<div id="social-media" class="tab-content">
<h2>Sosyal Medya Bağlantı Ayarları</h2>
<div class="card">
<h3>Yeni Sosyal Medya Platformu Ekle</h3>
<form id="social-media-form">
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
<input type="text" id="sm-name" placeholder="Platform Adı (örn: Instagram)" required>
<input type="text" id="sm-emoji" placeholder="Emoji (örn: 📷)" required>
</div>
<input type="text" id="sm-regex" placeholder="URL Regex (örn: instagram\.com/.*)"
style="width: 100%; margin-bottom: 10px; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
<button type="submit" class="btn-primary">Platform Ekle</button>
</form>
</div>

<div id="social-media-list" style="margin-top: 20px;">
<!-- Sosyal medya platformları listesi buraya yüklenecek -->
</div>
</div>

<?php if ($isAdmin): ?>
<!-- RÜTBE SİSTEMİ -->
<div id="rank-system" class="tab-content">
<h2>Rütbe Sistemi Ayarları</h2>
<div class="card">
<h3>Rütbe Hesaplama Ayarları</h3>
<form id="rank-settings-form">
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
<div>
<label>Yorum Başına Puan:</label>
<input type="number" id="rank-comment-points" value="1" min="0" step="0.1">
</div>
<div>
<label>Çizim Başına Puan:</label>
<input type="number" id="rank-drawing-points" value="2" min="0" step="0.1">
</div>
<div>
<label>Takipçi Başına Puan:</label>
<input type="number" id="rank-follower-points" value="0.5" min="0" step="0.1">
</div>
<div>
<label>Alınan + Puan Başına:</label>
<input type="number" id="rank-upvote-points" value="0.2" min="0" step="0.1">
</div>
</div>
<button type="submit" class="btn-primary" style="margin-top: 15px;">Ayarları Kaydet</button>
</form>
</div>

<div class="card" style="margin-top: 20px;">
<h3>Rütbe Dağılımı</h3>
<button onclick="calculateRanks()" class="btn-primary">Rütbeleri Hesapla</button>
<div id="rank-distribution" style="margin-top: 15px;">
<!-- Rütbe dağılımı buraya yüklenecek -->
</div>
</div>
</div>

<!-- ÖZEL MESAJLAR -->
<div id="private-messages" class="tab-content">
<h2>Özel Mesaj Yönetimi</h2>
<div class="card">
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
<div id="pm-users-list" style="max-height: 500px; overflow-y: auto;">
<!-- Kullanıcı listesi -->
</div>
<div id="pm-conversation" style="max-height: 500px; overflow-y: auto;">
<!-- Seçili konuşma -->
</div>
</div>
</div>
</div>
<?php endif; ?>
</div>
</div>

<!-- MODALLAR -->
<div id="mute-modal" class="modal" style="display:none;">
<div class="modal-content">
<button class="modal-close">&times;</button>
<h3>Yorum Yasağı Uygula</h3>
<input type="hidden" id="mute-user-id">
<label>Süre (gün):</label>
<input type="number" id="mute-duration" min="1" max="365" value="7" style="width: 100px; margin: 10px 0;">
<div style="display: flex; gap: 10px;">
<button onclick="applyCommentMute()" class="btn-primary">Uygula</button>
<button onclick="document.getElementById('mute-modal').style.display='none'" class="btn-secondary">İptal</button>
</div>
</div>
</div>

<script>
// Current User bilgisini global olarak ayarla
window.currentUser = {
    id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
    username: <?php echo json_encode($_SESSION['username'] ?? null); ?>,
    role: <?php echo json_encode($_SESSION['role'] ?? 'user'); ?>,
    isAdmin: <?php echo json_encode($isAdmin); ?>
};
</script>
<script src="admin_actions.js"></script>
<script>
// Sekme yönetimi
document.querySelectorAll('.tab-link').forEach(tab => {
    tab.addEventListener('click', () => {
        // Aktif sekme ve içeriği güncelle
        document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');

        // Sekme değiştiğinde ilgili içeriği yükle
        loadTabContent(tab.dataset.tab);
    });
});

// Sekme içeriklerini yükle
async function loadTabContent(tabName) {
    switch(tabName) {
        case 'user-management':
            await loadUserList();
            break;
        case 'content-moderation':
            fetchRecentContentForModeration();
            break;
        case 'announcements':
            await loadAnnouncements();
            break;
        case 'social-media':
            await loadSocialMediaSettings();
            break;
        case 'rank-system':
            await loadRankSettings();
            break;
        case 'private-messages':
            await loadPrivateMessages();
            break;
    }
}

// Kullanıcı listesi yükleme
async function loadUserList(searchTerm = '') {
    const container = document.getElementById('user-list-container');
    container.innerHTML = '<p>Kullanıcılar yükleniyor...</p>';

    try {
        const response = await fetch('fetch_users.php' + (searchTerm ? `?q=${encodeURIComponent(searchTerm)}` : ''));
        const result = await response.json();

        if (result.success) {
            container.innerHTML = createUserTable(result.users);
        } else {
            container.innerHTML = `<p style="color: red;">Hata: ${result.message}</p>`;
        }
    } catch (error) {
        container.innerHTML = '<p style="color: red;">Sunucu hatası.</p>';
    }
}

// Kullanıcı arama
function searchUsers() {
    const searchTerm = document.getElementById('userSearch').value;
    loadUserList(searchTerm);
}

// Modal sistemini başlat
function initModalSystem() {
    document.querySelectorAll('.modal-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });

    // Dışarı tıklayınca kapat
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
}

// Kullanıcı tablosu oluşturma fonksiyonu
function createUserTable(users) {
    if (users.length === 0) {
        return '<p>Kullanıcı bulunamadı.</p>';
    }

    let html = `
    <table style="width: 100%; border-collapse: collapse;">
    <thead>
    <tr style="background-color: var(--fixed-bg);">
    <th style="padding: 10px; border: 1px solid var(--border-color);">ID</th>
    <th style="padding: 10px; border: 1px solid var(--border-color);">Kullanıcı Adı</th>
    <th style="padding: 10px; border: 1px solid var(--border-color);">Email</th>
    <th style="padding: 10px; border: 1px solid var(--border-color);">Rol</th>
    <th style="padding: 10px; border: 1px solid var(--border-color);">Durum</th>
    <th style="padding: 10px; border: 1px solid var(--border-color);">Çizim/Yorum</th>
    <th style="padding: 10px; border: 1px solid var(--border-color);">Kayıt Tarihi</th>
    <th style="padding: 10px; border: 1px solid var(--border-color);">Eylemler</th>
    </tr>
    </thead>
    <tbody>
    `;

    users.forEach(user => {
        const isBanned = user.is_banned == 1;
        const isMuted = user.comment_mute_until && new Date(user.comment_mute_until) > new Date();

        html += `
        <tr style="${isBanned ? 'background-color: #ffdddd;' : ''}">
        <td style="padding: 8px; border: 1px solid var(--border-color);">${user.id}</td>
        <td style="padding: 8px; border: 1px solid var(--border-color;">
        <a href="../${user.username}/" target="_blank">${user.username}</a>
        </td>
        <td style="padding: 8px; border: 1px solid var(--border-color);">${user.email}</td>
        <td style="padding: 8px; border: 1px solid var(--border-color);">${user.role}</td>
        <td style="padding: 8px; border: 1px solid var(--border-color);">
        ${isBanned ? '🚫 Banlı' : '✅ Aktif'}
        ${isMuted ? '<br>🔇 Mute' : ''}
        </td>
        <td style="padding: 8px; border: 1px solid var(--border-color);">
        ${user.drawing_count} çizim<br>
        ${user.comment_count} yorum<br>
        ${user.follower_count} takipçi
        </td>
        <td style="padding: 8px; border: 1px solid var(--border-color);">
        ${new Date(user.created_at).toLocaleDateString('tr-TR')}
        </td>
        <td style="padding: 8px; border: 1px solid var(--border-color);">
        <div style="display: flex; flex-direction: column; gap: 5px;">
        ${!isBanned ?
            `<button onclick="moderateUser(${user.id}, 'ban')" class="btn-danger btn-sm">Banla</button>` :
            `<button onclick="moderateUser(${user.id}, 'unban')" class="btn-success btn-sm">Banı Kaldır</button>`}

            ${!isMuted ?
                `<button onclick="showMuteModal(${user.id})" class="btn-warning btn-sm">Yorum Mute</button>` :
                `<button onclick="moderateUser(${user.id}, 'unmute')" class="btn-success btn-sm">Mute Kaldır</button>`}

                ${window.currentUser.isAdmin ? `
                    <select onchange="setRole(${user.id}, this.value)" style="padding: 4px; border-radius: 4px; border: 1px solid var(--border-color);">
                    <option value="user" ${user.role === 'user' ? 'selected' : ''}>Kullanıcı</option>
                    <option value="moderator" ${user.role === 'moderator' ? 'selected' : ''}>Moderatör</option>
                    </select>
                    ` : ''}
                    </div>
                    </td>
                    </tr>
                    `;
    });

        html += '</tbody></table>';
    return html;
}

// Gelişmiş sekme yönetimi
function initTabs() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();

            // Tüm sekmeleri ve içerikleri sıfırla
            tabLinks.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Aktif sekme ve içeriği ayarla
            this.classList.add('active');
            const targetTab = this.getAttribute('data-tab');
            const targetContent = document.getElementById(targetTab);

            if (targetContent) {
                targetContent.classList.add('active');

                // Sekme değiştiğinde ilgili içeriği yükle
                loadTabContent(targetTab);
            }
        });
    });
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    initTabs();
    initModalSystem();

    // Varsayılan sekme içeriğini yükle
    const activeTab = document.querySelector('.tab-link.active');
    if (activeTab) {
        loadTabContent(activeTab.getAttribute('data-tab'));
    }

    // Duyuru formu
    const announcementForm = document.getElementById('announcement-form');
    if (announcementForm) {
        announcementForm.addEventListener('submit', function(e) {
            e.preventDefault();
            createAnnouncement();
        });
    }

    // Sosyal medya formu
    const socialMediaForm = document.getElementById('social-media-form');
    if (socialMediaForm) {
        socialMediaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addSocialMediaPlatform();
        });
    }

    // Rütbe ayarları formu
    const rankForm = document.getElementById('rank-settings-form');
    if (rankForm) {
        rankForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveRankSettings();
        });
    }
});
</script>
</body>
</html>
