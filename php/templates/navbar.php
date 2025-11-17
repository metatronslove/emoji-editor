<?php
// Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/counter_manager.php';
require_once __DIR__ . '/../classes/Drawing.php';
require_once __DIR__ . '/../classes/Router.php';

$profileUsername = $_GET['username'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;


    $isLoggedIn = false;
    $userRole = 'user';
    $username = '';

    if (class_exists('Auth') && method_exists('Auth', 'isLoggedIn')) {
        $isLoggedIn = Auth::isLoggedIn();
        $userRole = $_SESSION['user_role'] ?? 'user';
        $username = $_SESSION['username'] ?? '';
    }

if ($profileUsername) {

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

        $canViewContent = true;

    } catch (PDOException $e) {
        error_log("Profile page database error: " . $e->getMessage());
        http_response_code(500);
        die("Veritabanı hatası oluştu.");
    } catch (Exception $e) {
        error_log("Profile page general error: " . $e->getMessage());
        http_response_code(500);
        die("Bir hata oluştu.");
    }
    require_once __DIR__ . '/../core/online_status_manager.php';
    $isOnline = OnlineStatusManager::isUserOnline($profileUser);
}
$counters = getCounters();
$totalViews = $counters['total_views'] ?? 0;
$baseSiteUrl = BASE_SITE_URL . '';
?>
<!-- STATS BAR -->
<div id="stats-bar" class="card">
    <div class="info-group">
        <a href="/" class="btn btn-sm btn-primary">Ana Sayfa</a>
        <span style="display: none;">Toplam Ziyaret: <strong><?php echo number_format($totalViews ?? 0); ?></strong></span>
        <span style="color:#4CAF50"><strong><?php echo getOnlineUsersText(); ?></strong></span>
    </div>
    <div class="user-actions">
        <?php if (Auth::isLoggedIn()): ?>
            <span class="greeting">Hoş geldin,
                <strong>
                    <a href="<?php echo $baseSiteUrl; ?><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>/" style="color: inherit; text-decoration: none;">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
                    </a>
                </strong>!
            </span>
            <button onclick="openMessagesModal()" class="btn btn-sm btn-primary">📬 Mesaj Kutusu</button>
            <?php if (in_array($_SESSION['user_role'] ?? 'user', ['admin', 'moderator'])): ?>
                <a href="<?php echo $baseSiteUrl; ?>admin/dashboard.php" class="btn btn-sm btn-primary">Yönetim Paneli</a>
            <?php endif; ?>
            <a href="<?php echo $baseSiteUrl; ?>auth/logout.php" class="btn btn-sm btn-danger" id="logoutButton">Çıkış</a>
        <?php else: ?>
            <button class="btn btn-sm btn-primary" data-modal-toggle="login_modal">Giriş</button>
            <button class="btn btn-sm btn-secondary" data-modal-toggle="register_modal">Kayıt</button>
        <?php endif; ?>
    </div>
</div>
