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

$currentUserId = $_SESSION['user_id'] ?? null;
$counters = getCounters();
$totalViews = $counters['total_views'] ?? 0;

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/navbar.php';
$baseSiteUrl = BASE_SITE_URL;
?>
<script>
window.PROFILE_DATA = {
    userId: <?php echo json_encode($currentUserId); ?>,
    currentUserId: <?php echo json_encode($currentUserId); ?>,
    isProfileOwner: false,
    profileUsername: null,
    isBlockingMe: null,
    isBlockedByMe: null,
    canViewContent: null,
    isProfilePrivate: null,
    isOnline: null
};
const SITE_BASE_URL = '<?php echo $baseSiteUrl; ?>';
</script>
<!-- ÇİZİM LİSTESİ VE AKIŞ BÖLÜMÜ -->
<div id="community-section" style="max-width: 100%; margin: 0 auto 20px; width: 100%;">
    <div class="card">
        <h3 style="color: var(--accent-color); margin-bottom: 15px;">🎨 Topluluk Çizimleri & 🌊 Flood Set'leri</h3>

        <!-- SEGMENT SEÇİCİ -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
    <button id="show-drawings" class="segment-btn active" onclick="window.switchSegment('drawings')">
        🎨 Çizimler
    </button>
    <button id="show-floods" class="segment-btn" onclick="window.switchSegment('floods')">
        🌊 Flood Set'leri
    </button>
    <?php if (Auth::isLoggedIn()): ?>
    <button id="show-following" class="segment-btn" onclick="window.switchSegment('following')">
        👥 Takip Ettiklerim
    </button>
    <?php endif; ?>
    
    <!-- SADECE TIKLAMA OLAYI OLAN BASİT BİR BUTON -->
    <button id="community-create-btn" class="btn-primary" style="margin-left: auto;">
        🚀 Yeni Oluştur
    </button>
</div>

        <!-- TAKİP EDİLENLER BÖLÜMÜ -->
        <?php if (Auth::isLoggedIn()): ?>
        <div id="following-feed" class="segment-content" style="display: none;">
            <h4 style="margin-bottom: 10px;">👥 Takip Ettiklerim - Son Çizimler</h4>
            <div id="following-feed-list" class="drawings-grid">
                <!-- JavaScript ile doldurulacak -->
            </div>
        </div>
        <?php endif; ?>

        <!-- ÇİZİMLER BÖLÜMÜ -->
        <div id="drawings-segment" class="segment-content">
            <div id="drawings-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="margin: 0;">🎨 Tüm Çizimler</h4>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <select id="drawing-category-filter" style="padding: 5px 10px; border-radius: 4px;">
                        <option value="">Tüm Kategoriler</option>
                        <option value="Sanat">Sanat</option>
                        <option value="Pixel Art">Pixel Art</option>
                        <option value="Anime">Anime</option>
                        <!-- Diğer kategoriler -->
                    </select>
                    <select id="drawing-sort" style="padding: 5px 10px; border-radius: 4px;">
                        <option value="newest">En Yeni</option>
                        <option value="popular">En Popüler</option>
                        <option value="most_emojis">En Çok Emoji</option>
                    </select>
                </div>
            </div>
            
            <div id="user-drawing-list" class="drawings-grid">
                <!-- Çizimler JavaScript ile doldurulacak -->
            </div>
            
            <div id="drawings-pagination" style="margin-top: 15px; text-align: center;">
                <!-- Sayfalama -->
            </div>
        </div>

        <!-- FLOOD SET'LERİ BÖLÜMÜ -->
        <div id="floods-segment" class="segment-content" style="display: none;">
            <div id="floods-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="margin: 0;">🌊 Popüler Flood Set'leri</h4>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <select id="flood-filter" style="padding: 5px 10px; border-radius: 4px;">
                        <option value="all">Hepsi</option>
                        <option value="public">Herkese Açık</option>
                        <option value="following">Takip Ettiklerim</option>
                    </select>
                    <select id="flood-sort" style="padding: 5px 10px; border-radius: 4px;">
                        <option value="newest">En Yeni</option>
                        <option value="popular">En Popüler</option>
                        <option value="most_messages">En Çok Mesaj</option>
                    </select>
                </div>
            </div>
            
            <div id="flood-sets-grid" class="flood-sets-grid">
                <!-- Flood set'leri JavaScript ile doldurulacak -->
            </div>
            
            <div id="floods-pagination" style="margin-top: 15px; text-align: center;">
                <!-- Sayfalama -->
            </div>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . '/templates/messages_modal.php';
require_once __DIR__ . '/templates/modals.php';
require_once __DIR__ . '/templates/footer.php';
?>
