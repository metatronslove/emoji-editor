<!-- footer.php -->
</main>

<footer>
<div class="container">
<p>&copy; <?php echo date('Y'); ?> Flood Page - Emoji Pixel Art Platformu</p>
<p>Toplam Ziyaretçi: <?php echo $totalViews; ?> |
<?php echo $isLoggedIn ? 'Hoş geldin, ' . htmlspecialchars($_SESSION['username']) : 'Misafir'; ?></p>
</div>
<!-- 1. ÖNCE Core kütüphaneler -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/core/utils.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/core/theme.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/core/online.js"></script>
<!-- 2. SONRA UI bileşenleri -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/ui/notifications.js"></script>
<!-- 3. DAHA SONRA Feature modüller (sıralı) -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/emojis.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/matrix.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/drawing.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/comments.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/community.js"></script>

<!-- 4. EN SON Social/Game sistemleri -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/profile.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/messaging.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/game-system.js"></script>
<script>// Topluluk çizimlerini yükle
async function loadCommunityDrawings(page = 1) {
    const drawingListElement = document.getElementById('user-drawing-list');
    if (!drawingListElement) {
        console.warn('user-drawing-list elementi bulunamadı');
        return;
    }

    try {
        drawingListElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">Çizimler yükleniyor...</p>';

        const response = await fetch(SITE_BASE_URL + `core/list_drawings.php?page=${page}`);

        // HTTP durum kodunu kontrol et
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        // API yanıt yapısını kontrol et
        if (!result.success) {
            throw new Error(result.message || 'API başarısız yanıt verdi');
        }

        if (result.drawings && result.drawings.length > 0) {
            drawingListElement.innerHTML = result.drawings.map(drawing => {
                if (typeof createDrawingCard === 'function') {
                    const card = createDrawingCard(drawing);
                    return card.outerHTML;
                }
                // Fallback
                return `
                <div class="drawing-card">
                <div class="drawing-content">${drawing.content || 'Çizim içeriği'}</div>
                <div class="drawing-author">${drawing.author_username || 'Bilinmeyen'}</div>
                </div>
                `;
            }).join('');
        } else {
            drawingListElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">Henüz çizim bulunmuyor.</p>';
        }
    } catch (error) {
        console.error('Topluluk çizimleri yüklenirken hata:', error);
        drawingListElement.innerHTML = `
        <div style="text-align: center; color: #dc3545; padding: 20px;">
        <p>❌ Çizimler yüklenirken hata oluştu:</p>
        <p style="font-size: 0.9em; opacity: 0.8;">${error.message}</p>
        <button onclick="loadCommunityDrawings(${page})" class="btn-secondary" style="margin-top: 10px;">
        🔄 Yeniden Dene
        </button>
        </div>
        `;
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
// Sayfa özel başlatma - GÜVENLİ
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏠 Sayfa yüklendi - Sistem başlatılıyor');

    try {
        // DOM elementlerini başlat
        if (typeof getDomElements === 'function') {
            window.DOM_ELEMENTS = getDomElements();
        }

        // Index sayfasına özel fonksiyonlar
        if (document.getElementById('user-drawing-list') && typeof loadCommunityDrawings === 'function') {
            setTimeout(() => loadCommunityDrawings(), 500);
        }

        // Takip edilenler çizimlerini yükle (giriş yapılmışsa)
        if (window.APP_DATA.isLoggedIn && document.getElementById('following-feed-list') && typeof loadFollowingDrawings === 'function') {
            setTimeout(() => loadFollowingDrawings(), 1000);
        }

    } catch (error) {
        console.error('Sayfa başlatma hatası:', error);
    }
});
</script>
<!-- 5. VE SON OLARAK App başlatıcı -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/save.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/app.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/main.js"></script>
</footer>
</body>
</html>
