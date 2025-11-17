<script>
// Global değişkenleri ve Ably konfigürasyonunu önceden ayarla
window.ABLY_CONFIG = {
    enabled: true,
    autoConnect: true,
    reconnectAttempts: 5
};

// Global değişkenler
window.APP_DATA = {
    isLoggedIn: <?php echo json_encode($isLoggedIn); ?>,
    userRole: <?php echo json_encode($userRole); ?>,
    currentUserId: <?php echo json_encode($currentUserId); ?>,
    totalViews: <?php echo json_encode($totalViews); ?>
};

// Current User bilgisini global olarak ayarla
window.currentUser = {
    id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
    username: <?php echo json_encode($_SESSION['username'] ?? null); ?>,
    role: <?php echo json_encode($_SESSION['role'] ?? 'user'); ?>
};
</script>

<script src="<?php echo $baseSiteUrl; ?>assets/js/core/constants.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/core/utils.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/core/theme.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/core/online.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/ui/notifications.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/ui/modals.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/profile.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/game-system.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/messaging.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/emojis.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/matrix.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/drawing.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/comments.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/community.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/save.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/app.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/main.js"></script>

<script>
// Index sayfasına özel fonksiyonlar
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏠 Index sayfası yüklendi');

    // Global elementleri kontrol et
    if (typeof DOM_ELEMENTS === 'undefined') {
        console.warn('DOM_ELEMENTS tanımlı değil, manuel olarak ayarlanıyor...');
        window.DOM_ELEMENTS = {
            notificationContainer: document.getElementById('notification-container') || document.createElement('div')
        };
    }

    // Topluluk çizimlerini yükle
    if (typeof loadCommunityDrawings === 'function') {
        loadCommunityDrawings();
    }

    // Takip edilenler çizimlerini yükle (giriş yapılmışsa)
    if (window.APP_DATA.isLoggedIn && typeof loadFollowingDrawings === 'function') {
        loadFollowingDrawings();
    }
});

// Topluluk çizimlerini yükle
async function loadCommunityDrawings(page = 1) {
    const drawingListElement = document.getElementById('user-drawing-list');
    if (!drawingListElement) {
        console.warn('user-drawing-list elementi bulunamadı');
        return;
    }

    drawingListElement.innerHTML = '<p id="loading-message">Çizimler yükleniyor...</p>';

    try {
        const response = await fetch(SITE_BASE_URL + `core/list_drawings.php?page=${page}`);
        const result = await response.json();

        if (result.success && result.drawings.length > 0) {
            drawingListElement.innerHTML = result.drawings.map(drawing => {
                if (typeof createDrawingCard === 'function') {
                    const card = createDrawingCard(drawing);
                    return card.outerHTML;
                }
                return `<div>Çizim: ${drawing.title}</div>`;
            }).join('');
        } else {
            drawingListElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">Henüz çizim bulunmuyor.</p>';
        }
    } catch (error) {
        console.error('Topluluk çizimleri yüklenirken hata:', error);
        drawingListElement.innerHTML = '<p style="text-align: center; color: #dc3545;">Çizimler yüklenirken hata oluştu.</p>';
    }
}

// Takip edilenler çizimlerini yükle
async function loadFollowingDrawings() {
    const feedListElement = document.getElementById('following-feed-list');
    if (!feedListElement) {
        console.warn('following-feed-list elementi bulunamadı');
        return;
    }

    try {
        const response = await fetch(SITE_BASE_URL + `core/fetch_following_feed.php`);
        const result = await response.json();

        if (result.success && result.drawings.length > 0) {
            feedListElement.innerHTML = result.drawings.map(drawing => {
                if (typeof createDrawingCard === 'function') {
                    const card = createDrawingCard(drawing);
                    return card.outerHTML;
                }
                return `<div>Çizim: ${drawing.title}</div>`;
            }).join('');
        } else {
            feedListElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">Takip ettikleriniz henüz çizim paylaşmamış.</p>';
        }
    } catch (error) {
        console.error('Takip edilenler çizimleri yüklenirken hata:', error);
        feedListElement.innerHTML = '<p style="text-align: center; color: #dc3545;">Takip edilenler yüklenirken hata oluştu.</p>';
    }
}
</script>
</body>
</html>
