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
<div id="community-section" style="max-width: 1400px; margin: 0 auto 20px; width: 100%;">
<div class="card">
<h3 style="color: var(--accent-color); margin-bottom: 15px;">🎨 Topluluk Çizimleri</h3>

<!-- Takip Edilenler Akışı -->
<?php if (Auth::isLoggedIn()): ?>
<div id="following-feed" style="margin-bottom: 25px;">
<h4 style="margin-bottom: 10px;">👥 Takip Ettiklerim</h4>
<div id="following-feed-list" class="drawings-grid">
<!-- JavaScript ile doldurulacak -->
</div>
</div>
<?php endif; ?>

<!-- Tüm Çizimler Listesi -->
<div id="all-drawings">
<h4 style="margin-bottom: 10px;">🌍 Tüm Çizimler</h4>
<div id="user-drawing-list" class="drawings-grid">
<!-- JavaScript ile doldurulacak -->
</div>
<div id="pagination" style="margin-top: 15px; text-align: center;">
<!-- Sayfalama JavaScript ile oluşturulacak -->
</div>
</div>
</div>
</div>
<?php
require_once __DIR__ . '/templates/messages_modal.php';
require_once __DIR__ . '/templates/emoji_editor_modal.php';
require_once __DIR__ . '/templates/modals.php';
require_once __DIR__ . '/templates/footer.php';
