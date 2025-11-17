<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/counter_manager.php';
require_once __DIR__ . '/../core/online_status_manager.php';
$isLoggedIn = false;
$userRole = 'user';
$username = '';
$site_url = BASE_SITE_URL;
$baseSiteUrl = BASE_SITE_URL . '../';

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
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:title" content="Emoji Piksel Sanatı ve Sosyal Sohbet Platformu">
    <meta property="og:description" content="YouTube Sohbetleri için emojilerle sanat mesajları (Flood Mesajları) oluşturan bir eğlence ve sosyal platformdur!">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $site_url; ?>">
    <meta property="og:image" content="<?php echo $site_url; ?>assets/img/four-hundred-eighty-kilograms-of-gold-worth-open-graph-image.png">
    <meta property="og:site_name" content="Emoji Piksel Sanatı">
    <meta property="og:locale" content="tr_TR">
    <title><?php echo $pageTitle ?? 'Emoji Piksel Sanatı'; ?></title>
    <link rel="stylesheet" href="<?php echo $site_url; ?>assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.ably.io/lib/ably.min-1.js"></script>
    <script src="<?php echo $baseSiteUrl; ?>assets/js/core/constants.js"></script>
</head>
<body>
<!-- Global değişkenleri EN BAŞTA tanımla -->
<script>
// KRİTİK GLOBAL DEĞİŞKENLER
window.SITE_BASE_URL = <?php echo json_encode($baseSiteUrl); ?>;
window.APP_DATA = {
    isLoggedIn: <?php echo json_encode($isLoggedIn); ?>,
    userRole: <?php echo json_encode($userRole); ?>,
    currentUserId: <?php echo json_encode($currentUserId); ?>,
    totalViews: <?php echo json_encode($totalViews); ?>
};
window.currentUser = {
    id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
    username: <?php echo json_encode($_SESSION['username'] ?? null); ?>,
    role: <?php echo json_encode($_SESSION['role'] ?? 'user'); ?>,
    isAdmin: <?php echo json_encode(($_SESSION['role'] ?? 'user') === 'admin'); ?>
};

// Ably konfigürasyonu
window.ABLY_CONFIG = {
    enabled: <?php echo json_encode($isLoggedIn); ?>,
    autoConnect: true,
    reconnectAttempts: 5
};
// Mesaj bildirimini güncelle
async function updateMessageNotification() {
    if (!window.currentUser || !window.currentUser.id) return;
    const dom = window.DOM_ELEMENTS;
    if (!dom) {
        console.warn('⚠️ DOM elementleri bulunamadı');
        return;
    }

    try {
        const response = await fetch(window.SITE_BASE_URL + 'core/get_unread_message_count.php');
        const result = await response.json();

        if (dom.messageBadge) {
            if (result.unread_count > 0) {
                dom.messageBadge.textContent = result.unread_count;
                dom.messageBadge.style.display = 'inline';
            } else {
                dom.messageBadge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Mesaj bildirimi güncelleme hatası:', error);
    }
}
</script>
    <!-- FÜTÜRİSTİK ARKA PLAN -->
    <div id="background-grid"></div>
    <main>
