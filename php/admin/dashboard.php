<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/counter_manager.php';
require_once __DIR__ . '/../classes/Drawing.php';
require_once __DIR__ . '/../classes/Router.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');

$db = getDbConnection();
$userRole = $_SESSION['user_role'] ?? 'user';
$currentUserId = $_SESSION['user_id'] ?? null;
$site_url = BASE_SITE_URL;

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

if (!$currentUserId || !in_array($userRole, ['admin', 'moderator'])) {
    http_response_code(403);
    die("Yetkisiz Erişim. Bu sayfayı görüntüleme izniniz yok.");
}

$isAdmin = ($userRole === 'admin');

// Dashboard verilerini çek
$stats = [];
try {
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

$counters = getCounters();
$totalViews = $counters['total_views'] ?? 0;

$pageTitle = 'Yönetim Paneli | ' . ucfirst($userRole);
require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/navbar.php';
$baseSiteUrl = BASE_SITE_URL . '../';
?>
<script>
// Admin global değişkenleri
window.ADMIN_DATA = {
    isAdmin: <?php echo json_encode($isAdmin); ?>,
    userRole: <?php echo json_encode($userRole); ?>,
    currentUserId: <?php echo json_encode($currentUserId); ?>,
    username: <?php echo json_encode($_SESSION['username'] ?? ''); ?>
};
// Current User bilgisini global olarak ayarla
window.currentUser = {
    id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
    username: <?php echo json_encode($_SESSION['username'] ?? null); ?>,
    role: <?php echo json_encode($_SESSION['role'] ?? 'user'); ?>,
    isAdmin: <?php echo json_encode($isAdmin); ?>
};
const SITE_BASE_URL = '<?php echo $baseSiteUrl; ?>';
</script>
<div class="container">
<h1>Yönetim Paneli</h1>
<p>Giriş Yapan: <b><?php echo $_SESSION['username']; ?></b> (Rol: <?php echo ucfirst($userRole); ?>)</p>
<hr>

<!-- GELİŞMİŞ İSTATİSTİKLER VE GRAFİKLER -->
<div class="card" style="margin-bottom: 30px;">
<h2>📈 Detaylı İstatistikler</h2>

<!-- Hızlı Arama -->
<div style="margin-bottom: 20px;">
<input type="text" id="admin-quick-search" placeholder="🔍 Kullanıcı, çizim veya yorum ara..." style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
<div id="quick-search-results" style="display: none; position: absolute; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; z-index: 1000; width: 100%;"></div>
</div>

<!-- Sistem Durumu -->
<div style="margin-bottom: 20px;">
<h3>🖥️ Sistem Durumu</h3>
<div id="system-status">
<p>Sistem durumu yükleniyor...</p>
</div>
</div>

<!-- Grafikler Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
<div class="chart-container">
<h4>📊 Son 7 Gün Aktivite</h4>
<canvas id="activity-chart" height="200"></canvas>
</div>
<div class="chart-container">
<h4>👥 Kullanıcı Büyümesi</h4>
<canvas id="user-growth-chart" height="200"></canvas>
</div>
<div class="chart-container">
<h4>🎨 İçerik Dağılımı</h4>
<canvas id="content-distribution-chart" height="200"></canvas>
</div>
</div>

<!-- Hızlı Aksiyon Butonları -->
<div style="display: flex; gap: 10px; flex-wrap: wrap;">
<button onclick="adminDashboard.refreshDashboard()" class="btn-primary">🔄 Yenile</button>
<button onclick="adminDashboard.exportDashboard()" class="btn-success">📤 Dışa Aktar</button>
<button data-action="broadcast" class="btn-warning">📢 Toplu Bildirim</button>
<button data-action="cleanup" class="btn-info">🧹 Sistem Temizleme</button>
<button data-action="backup" class="btn-secondary">💾 Yedek Al</button>
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
<button class="tab-link" data-tab="system-logs">Sistem Logları</button>
<?php endif; ?>
</div>

<!-- KULLANICI YÖNETİMİ -->
<div id="user-management" class="tab-content active">
<h2>Kullanıcı Yönetimi</h2>
<div style="margin-bottom: 20px;">
<input type="text" id="userSearch" placeholder="Kullanıcı adı veya email ara..." style="width: 300px; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
<button onclick="searchUsers()" class="btn-primary">Ara</button>
<button onclick="exportUsers()" class="btn-success" style="margin-left: 10px;">📊 Kullanıcıları Dışa Aktar</button>
</div>
<div id="user-list-container">
<!-- Kullanıcı listesi buraya yüklenecek -->
</div>
</div>

<!-- İÇERİK MODERASYONU -->
<div id="content-moderation" class="tab-content">
<h2>İçerik Moderasyonu</h2>
<div style="margin-bottom: 20px;">
<button onclick="fetchRecentContentForModeration()" class="btn-primary">🔄 İçerikleri Yenile</button>
<button onclick="scanForInappropriateContent()" class="btn-warning" style="margin-left: 10px;">🔍 Uygunsuz İçerik Tara</button>
</div>
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
<textarea id="announcement-content" placeholder="Duyuru içeriği..." style="width: 100%; height: 120px; margin-bottom: 10px; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color);"></textarea>
<select id="announcement-type" style="margin-bottom: 10px; padding: 8px; border-radius: 4px;">
<option value="info">ℹ️ Bilgi</option>
<option value="warning">⚠️ Uyarı</option>
<option value="success">✅ Başarı</option>
<option value="critical">🚨 Kritik</option>
</select>
<div style="display: flex; gap: 10px;">
<button type="submit" class="btn-primary">Duyuruyu Yayınla</button>
<button type="button" onclick="sendBroadcastNotification()" class="btn-warning">📢 Anlık Bildirim Gönder</button>
</div>
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
<input type="text" id="sm-regex" placeholder="URL Regex (örn: instagram\.com/.*)" style="width: 100%; margin-bottom: 10px; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
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
<button onclick="exportRankData()" class="btn-success" style="margin-left: 10px;">📈 Rütbe Verilerini Dışa Aktar</button>
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

<!-- SİSTEM LOGLARI -->
<div id="system-logs" class="tab-content">
<h2>Sistem Logları</h2>
<div style="margin-bottom: 20px;">
<button onclick="loadSystemLogs()" class="btn-primary">📋 Logları Yükle</button>
<button onclick="clearOldLogs()" class="btn-warning" style="margin-left: 10px;">🗑️ Eski Logları Temizle</button>
<button onclick="exportLogs()" class="btn-success" style="margin-left: 10px;">📤 Logları Dışa Aktar</button>
</div>
<div id="system-logs-container" style="max-height: 600px; overflow-y: auto; background: var(--fixed-bg); padding: 15px; border-radius: 8px;">
<!-- Sistem logları buraya yüklenecek -->
</div>
</div>
<?php endif; ?>
</div>
</div>

<!-- Mute Modal -->
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

<!-- Broadcast Modal -->
<div id="broadcast-modal" class="modal" style="display:none;">
<div class="modal-content">
<button class="modal-close">&times;</button>
<h3>📢 Toplu Bildirim Gönder</h3>
<textarea id="broadcast-message" placeholder="Bildirim mesajı..." style="width: 100%; height: 100px; margin-bottom: 10px; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color);"></textarea>
<select id="broadcast-type" style="margin-bottom: 10px; padding: 8px; border-radius: 4px;">
<option value="info">ℹ️ Bilgi</option>
<option value="warning">⚠️ Uyarı</option>
<option value="success">✅ Başarı</option>
</select>
<div style="display: flex; gap: 10px;">
<button onclick="sendBroadcast()" class="btn-primary">Gönder</button>
<button onclick="document.getElementById('broadcast-modal').style.display='none'" class="btn-secondary">İptal</button>
</div>
</div>
</div>
<?php
require_once __DIR__ . '/../templates/messages_modal.php';
require_once __DIR__ . '/../templates/modals.php';
?>
<!-- Admin Scripts -->
<script src="<?php echo $baseSiteUrl;?>admin/admin_actions.js"></script>
<script src="<?php echo $baseSiteUrl;?>admin/admin_dashboard.js"></script>

<script>
// Admin dashboard özel fonksiyonları
document.addEventListener('DOMContentLoaded', function() {
    console.log('👑 Admin dashboard yüklendi');

    // Sekme sistemini başlat
    initAdminTabs();

    // Varsayılan sekme içeriğini yükle
    loadTabContent('user-management');

    // Form event listener'larını bağla
    initAdminForms();
});

function initAdminTabs() {
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
                loadTabContent(targetTab);
            }
        });
    });
}

function initAdminForms() {
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
}

function sendBroadcastNotification() {
    document.getElementById('broadcast-modal').style.display = 'block';
}

function sendBroadcast() {
    const message = document.getElementById('broadcast-message').value.trim();
    const type = document.getElementById('broadcast-type').value;

    if (!message) {
        showNotification('Lütfen bildirim mesajı girin.', 'error');
        return;
    }

    // Ably broadcast gönder
    if (typeof notificationSystem !== 'undefined' && notificationSystem.isAblyConnected) {
        notificationSystem.broadcastNotification(message, type);
        showNotification('Toplu bildirim gönderildi!', 'success');
        document.getElementById('broadcast-modal').style.display = 'none';
        document.getElementById('broadcast-message').value = '';
    } else {
        showNotification('Broadcast sistemi şu anda kullanılamıyor.', 'error');
    }
}

function exportUsers() {
    // Kullanıcıları CSV olarak dışa aktar
    window.open('<?php echo $baseSiteUrl;?>admin/export_users.php', '_blank');
}

function exportRankData() {
    // Rütbe verilerini dışa aktar
    window.open('<?php echo $baseSiteUrl;?>admin/export_rank_data.php', '_blank');
}

function scanForInappropriateContent() {
    showNotification('Uygunsuz içerik taraması başlatıldı...', 'info');
    // Bu fonksiyon admin_actions.js'de implemente edilecek
}

function loadSystemLogs() {
    const container = document.getElementById('system-logs-container');
    container.innerHTML = '<p>Loglar yükleniyor...</p>';

    fetch('<?php echo $baseSiteUrl;?>admin/get_system_logs.php')
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            container.innerHTML = result.logs.map(log => `
            <div style="border-bottom: 1px solid var(--border-color); padding: 8px 0;">
            <div style="display: flex; justify-content: between;">
            <strong>${log.level}</strong>
            <small>${new Date(log.timestamp).toLocaleString('tr-TR')}</small>
            </div>
            <div>${log.message}</div>
            <small style="color: var(--main-text); opacity: 0.7;">${log.context}</small>
            </div>
            `).join('');
        } else {
            container.innerHTML = `<p style="color: red;">Hata: ${result.message}</p>`;
        }
    })
    .catch(error => {
        container.innerHTML = '<p style="color: red;">Loglar yüklenirken hata oluştu.</p>';
    });
}

function clearOldLogs() {
    if (confirm('30 günden eski logları temizlemek istediğinizden emin misiniz?')) {
        fetch('<?php echo $baseSiteUrl;?>admin/clear_old_logs.php', { method: 'POST' })
        .then(response => response.json())
        .then(result => {
            showNotification(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                loadSystemLogs();
            }
        });
    }
}

function exportLogs() {
    window.open('<?php echo $baseSiteUrl;?>admin/export_logs.php', '_blank');
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php';
