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
</head>
<body>
    <!-- FÜTÜRİSTİK ARKA PLAN -->
    <div id="background-grid"></div>
