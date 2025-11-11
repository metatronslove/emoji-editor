<?php
// index.php - DEBUG MODE
require_once 'config.php';
require_once 'Auth.php';
require_once 'Drawing.php';
require_once 'functions.php';
require_once 'counter_manager.php';
require_once 'Router.php';

// Hata ayıklama modu
error_reporting(E_ALL);
ini_set('display_errors', 1);

// GÜVENLİK KONTROLLERİ
if (!isset($_SESSION)) {
    session_start();
}

// AUTH KONTROLÜ
$isLoggedIn = false;
$userRole = 'user';
$username = '';

if (class_exists('Auth') && method_exists('Auth', 'isLoggedIn')) {
    $isLoggedIn = Auth::isLoggedIn();
    $userRole = $_SESSION['user_role'] ?? 'user';
    $username = $_SESSION['username'] ?? '';
}

// Router'ı başlat
try {
    $router = new Router();
    $router->run();
} catch (Exception $e) {
    error_log("Router Error: " . $e->getMessage());
    echo "Router Hatası: " . $e->getMessage();
    exit;
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
<meta property="og:image" content="four-hundred-eighty-kilograms-of-gold-worth-open-graph-image.png">
<meta property="og:site_name" content="Emoji Piksel Sanatı">
<meta property="og:locale" content="tr_TR">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kalp Emoji Piksel Sanatı Editörü</title>
<link rel="stylesheet" href="styles.css">
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
<strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
</span>
<?php if (in_array($_SESSION['user_role'] ?? 'user', ['admin', 'moderator'])): ?>
<a href="/admin/dashboard.php" class="btn btn-sm btn-primary">Yönetim Paneli</a>
<?php endif; ?>
<a href="/logout.php" class="btn btn-sm btn-danger" id="logoutButton">Çıkış</a>
<?php else: ?>
<button class="btn btn-sm btn-primary" data-modal-toggle="login_modal">Giriş</button>
<button class="btn btn-sm btn-secondary" data-modal-toggle="register_modal">Kayıt</button>
<?php endif; ?>
</div>
</div>

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
<div id="drawing-list" class="drawings-grid">
<!-- JavaScript ile doldurulacak -->
</div>
<div id="pagination" style="margin-top: 15px; text-align: center;">
<!-- Sayfalama JavaScript ile oluşturulacak -->
</div>
</div>
</div>
</div>

<!-- Giriş Modal -->
<div id="login_modal" class="modal">
<div class="modal-content">
<button class="modal-close">❎</button>
<h2>Giriş Yap</h2>

<form action="/login_handler" method="POST" class="auth-form">
<input type="text" name="username" placeholder="Kullanıcı Adı" required>
<input type="password" name="password" placeholder="Şifre" required>
<button type="submit">Giriş Yap</button>
</form>

<div class="divider">
<span>YA DA</span>
</div>

<a href="login.php" class="btn-google">
<img src="google_logo.svg" alt="Google Logo" style="width: 20px; height: 20px; margin-right: 10px;">
Google ile Giriş Yap
</a>

<div class="auth-links">
<p>Hesabın yok mu?
<a href="#" data-modal-switch="register_modal">Hemen kaydol.</a>
</p>
</div>
</div>
</div>

<!-- Kayıt Modal -->
<div id="register_modal" class="modal">
<div class="modal-content">
<button class="modal-close">❎</button>
<h2>Yeni Kayıt</h2>

<form action="/register" method="POST" class="auth-form">
<input type="text" name="username" placeholder="Kullanıcı Adı" required minlength="3" maxlength="20">
<input type="email" name="email" placeholder="E-posta" required>
<input type="password" name="password" placeholder="Şifre (Min 6 Karakter)" required minlength="6">
<input type="password" name="password_confirm" placeholder="Şifre (Tekrar)" required>
<button type="submit">Kayıt Ol</button>
</form>

<div class="divider">
<span>YA DA</span>
</div>

<a href="login.php" class="btn-google">
<img src="google_logo.svg" alt="Google Logo" style="width: 20px; height: 20px; margin-right: 10px;">
Google ile Kayıt Ol
</a>

<div class="auth-links">
<p>Zaten hesabın var mı?
<a href="#" data-modal-switch="login_modal">Giriş Yap.</a>
</p>
</div>
</div>
</div>

<div id="confirm-modal" class="modal-overlay">
<div class="modal-content">
<h3 id="modal-title">Emin misiniz?</h3>
<p id="modal-message">Bu işlem geri alınamaz.</p>
<div class="modal-buttons">
<button class="modal-btn confirm" id="modal-confirm">Evet</button>
<button class="modal-btn cancel" id="modal-cancel">İptal</button>
</div>
</div>
</div>

<div id="guide-modal" class="modal-overlay">
<div class="modal-content-guide">
<h3>📖 YouTube Sohbet Kılavuzu</h3>

<div style="background-color: var(--fixed-bg); padding: 10px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid var(--accent-color);">
<strong>🎯 ÖNEMLİ:</strong> Uygulama, çiziminizin toplam maliyetinin **200 karakteri** aşmamasını otomatik olarak garantiler.
</div>

<ol style="margin-left: 20px; font-size: 0.95em;">
<li style="margin-bottom: 8px;">**Kılavuz Adım 1 (İlk Satır Ayarı):** İlk satırda kaç adet emoji pikseli **çizebileceğinizi** belirleyin (Genellikle 5 veya 6'dır). Bu, nickname'inizin kapladığı alanı otomatik hesaplar. **(❌ ile işaretli hücreler çıktıya dahil edilmez.)**</li>
<li style="margin-bottom: 8px;">**Kılavuz Adım 2 (Filtre Atlatma):** Çiziminizin YouTube sohbetinde görünmemesi durumunda, **Filtre Atlatma Yöntemi**'ni sırayla deneyin. Bu karakterler, çiziminizin toplam karakter sayısına eklenir.</li>
<li style="margin-bottom: 8px;">**Kılavuz Adım 3 (Kopyalama):** Çiziminizi tamamladıktan sonra **Panoya Kopyala** butonuna basın. Çıktınızın 200 karakteri asla aşmadığından emin olabilirsiniz. **Kırpılan (✂️) pikseller çıktıya dahil edilmez.**</li>
</ol>
<button id="close-guide-btn">Anladım, Kapat</button>
</div>
</div>

<h2 id="main-title">KALP EMOJİ PİKSEL SANATI EDİTÖRÜ V.6.5 (Sezgisel Giriş Düzeltmesi)</h2>

<div id="main-layout">
<div id="left-panel">
<div class="card" id="palette">
<strong>Fırça Rengi Seçin:</strong>

<div id="selected-emoji-display">
<span style="font-weight: normal;">Seçili Emoji:</span>
<span id="current-brush-emoji">🖤</span>
<span id="current-brush-name"> (black heart)</span>
</div>

<div id="category-tabs">
</div>

<div id="emoji-container">
<div id="color-options-container">
</div>
</div>
</div>
</div>

<div id="right-panel">
<div class="card" id="controls-panel">
<div id="main-controls" style="margin-bottom: 15px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
<label for="firstRowLength" style="color: var(--accent-color);">İlk Satır Çizim Piksel Sayısı (0-11):</label>
<input type="number" id="firstRowLength" value="6" min="0" max="11" style="width: 70px; padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); background-color: var(--fixed-bg); color: var(--main-text);">
<button id="updateMatrixButton" class="btn-success">Matrisi Güncelle</button>
<button id="showGuideButton" class="btn-primary">Kılavuz</button>
</div>

<div style="margin-bottom: 15px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
<label for="separator-select" style="color: var(--accent-color); white-space: nowrap;">Filtre Atlatma Yöntemi:</label>
<select id="separator-select">
<option value="none" selected>Hiçbiri</option>
<option value="ZWNJ">ZWNJ (Zero Width Non-Joiner)</option>
<option value="ZWSP">ZWSP (Zero Width Space)</option>
<option value="ZWJ">ZWJ (Zero Width Joiner)</option>
<option value="WJ">WJ (Word Joiner)</option>
<option value="SHY">SHY (Soft Hyphen)</option>
<option value="HAIR">Hair Space</option>
<option value="LRM">LRM (Yön Kontrol)</option>
<option value="RLM">RLM (Yön Kontrol)</option>
<option value="ZWNBSP">ZWNBSP (Zero Width No-Break Space)</option>
<option value="LRE">LRE (Bidi L-R-Embedding)</option>
<option value="RLE">RLE (Bidi R-L-Embedding)</option>
<option value="PDF">PDF (Bidi Pop Directional)</option>
<option value="LRI">LRI (Bidi L-R-Isolate)</option>
<option value="RLI">RLI (Bidi R-L-Isolate)</option>
<option value="PDI">PDI (Bidi Pop Isolate)</option>
<option value="CGJ">CGJ (Combining Grapheme Joiner)</option>
<option value="SP_BS">DENEYSEL (Space + Backspace)</option>
</select>
</div>

<div id="auxiliary-controls" style="flex-direction: column; gap: 8px; width: 100%;">
<button id="copyButton" class="btn-primary" style="width: 100%;">Panoya Kopyala</button>
<button id="importButton" class="btn-primary" style="width: 100%;">Panodan İçe Aktar</button>

<div style="display: flex; gap: 8px; width: 100%;">
<button id="saveButton" class="btn-warning" style="flex-grow: 1;">💾 Kaydet (Dosya/Site Kaydı)</button>
<input type="file" id="fileInput" accept=".txt" style="display: none;">
<button id="loadButton" class="btn-warning" style="flex-grow: 1;">Dosya Aç</button>
</div>
<button id="clearButton" class="btn-danger" style="width: 100%;">Temizle</button>
</div>
</div>

<div id="info-panel">
<span class="char-count">Toplam Çıktı Karakteri (Emoji + Ayırıcı): <span id="currentChars">0</span>/200</span>
<span id="charWarning" class="warning" style="display: none;"> - ⚠️ Ekstra karakter maliyeti!</span>
</div>

<div id="matrix-container" style="max-width: 100%;">
<table id="matrix">
</table>
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
<script src="main.js"></script>
</body>
</html>
