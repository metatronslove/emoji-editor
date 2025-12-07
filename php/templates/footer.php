<!-- footer.php -->
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
$baseSiteUrl = BASE_SITE_URL;

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
</main>

<footer>
<div class="container">
<p>&copy; <?php echo date('Y'); ?> Flood Page - Emoji Pixel Art Platformu</p>
<p>Toplam Ziyaretçi: <?php echo $totalViews; ?> |
<?php echo $isLoggedIn ? 'Hoş geldin, ' . htmlspecialchars($_SESSION['username']) : 'Misafir'; ?></p>
</div>

<!-- 1. ÖNCE Core kütüphaneler -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/ui/modals.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/core/utils.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/core/theme.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/core/online.js"></script>

<!-- 2. SONRA UI bileşenleri -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/ui/notifications.js"></script>

<!-- 3. DAHA SONRA Feature modüller (SIRA ÇOK ÖNEMLİ!) -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/emojis.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/matrix.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/drawing.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/comments.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/community.js"></script>

<!-- 4. Sistem modülleri (GÜNCELLENMİŞ SIRA) -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/flood.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/integrated.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/flood_cards.js"></script>

<!-- 5. EN SON Social/Game sistemleri -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/save.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/profile.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/messaging.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/game-system.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/features/activity_system.js"></script>

<script>
// Topluluk çizimlerini yükle
async function loadCommunityDrawings(page = 1) {
    const drawingListElement = document.getElementById('user-drawing-list');
    if (!drawingListElement) {
        console.warn('user-drawing-list elementi bulunamadı');
        return;
    }

    try {
        drawingListElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">Çizimler yükleniyor...</p>';

        const response = await fetch(window.SITE_BASE_URL + `core/list_drawings.php?page=${page}`);

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
        const response = await fetch(window.SITE_BASE_URL + `core/fetch_following_feed.php`);
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

// Sayfa özel başlatma
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
        if (window.APP_DATA && window.APP_DATA.isLoggedIn && document.getElementById('following-feed-list') && typeof loadFollowingDrawings === 'function') {
            setTimeout(() => loadFollowingDrawings(), 1000);
        }

    } catch (error) {
        console.error('Sayfa başlatma hatası:', error);
    }
});
</script>

<!-- 6. VE SON OLARAK App başlatıcı ve ANA DOSYA -->
<script src="<?php echo $baseSiteUrl; ?>assets/js/app.js"></script>
<script src="<?php echo $baseSiteUrl; ?>assets/js/main.js"></script>

<!-- 7. SİSTEM TESTİ -->
<script>

// FLOOD EDITOR SISTEMINI BAŞLATMAK İÇİN GLOBAL FONKSİYON
function initializeFloodSystem() {
    if (typeof floodSystem !== 'undefined' && typeof floodSystem.openEditor === 'function') {
        console.log('🌊 Flood sistemi mevcut, entegre ediliyor...');
        return true;
    }
    
    // Flood sistemi henüz yüklenmediyse, integrated.js'deki basit fonksiyonları kullan
    if (typeof window.openFloodEditor === 'undefined') {
        console.log('⚠️ Flood sistemi yüklenmedi, basit modal açılıyor...');
        
        // Basit flood modal açma fonksiyonu
        window.openFloodEditor = function() {
            const modal = document.getElementById('flood-tab');
            if (modal) {
                modal.style.display = 'flex';
                
                // Önizlemeyi güncelle
                if (typeof updateFloodPreview === 'function') {
                    updateFloodPreview();
                }
                
                // Event listener'ları bağla
                const textarea = document.getElementById('flood-message-input');
                if (textarea) {
                    textarea.addEventListener('input', updateFloodPreview);
                }
            }
        };
        
        // Basit modal kapatma
        window.closeFloodEditor = function() {
            const modal = document.getElementById('flood-tab');
            if (modal) modal.style.display = 'none';
        };
        
        // Emoji ekleme
        window.insertToFlood = function(emoji) {
            const textarea = document.getElementById('flood-message-input');
            if (!textarea) return;
            
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + emoji.length, start + emoji.length);
            
            if (typeof updateFloodPreview === 'function') {
                updateFloodPreview();
            }
        };
        
        // Önizleme güncelleme
        window.updateFloodPreview = function() {
            const textarea = document.getElementById('flood-message-input');
            const preview = document.getElementById('flood-preview');
            const charCount = document.getElementById('char-count');
            
            if (!textarea || !preview) return;
            
            const text = textarea.value;
            preview.textContent = text || 'Önizleme burada görünecek...';
            
            if (charCount) {
                charCount.textContent = text.length;
                charCount.style.color = text.length > 200 ? '#dc3545' : '#28a745';
            }
        };
        
        // Mesaj kopyalama
        window.copyFloodMessage = function() {
            const textarea = document.getElementById('flood-message-input');
            if (!textarea || !textarea.value.trim()) {
                if (typeof showNotification === 'function') {
                    showNotification('Kopyalanacak mesaj yok!', 'error');
                }
                return;
            }
            
            navigator.clipboard.writeText(textarea.value)
                .then(() => {
                    if (typeof showNotification === 'function') {
                        showNotification('Mesaj panoya kopyalandı!', 'success');
                    }
                })
                .catch(err => {
                    console.error('Kopyalama hatası:', err);
                    // Fallback
                    textarea.select();
                    document.execCommand('copy');
                    if (typeof showNotification === 'function') {
                        showNotification('Mesaj kopyalandı! (fallback)', 'success');
                    }
                });
        };
        
        // Mesaj kaydetme
        window.saveFloodMessage = function() {
            const textarea = document.getElementById('flood-message-input');
            if (!textarea || !textarea.value.trim()) {
                if (typeof showNotification === 'function') {
                    showNotification('Lütfen bir mesaj yazın!', 'error');
                }
                return;
            }
            
            if (typeof showNotification === 'function') {
                showNotification('Flood mesajı kaydedildi!', 'info');
            }
        };
    }
    
    return true;
}

// ENTEGRE EDITOR GLOBAL FONKSİYONLARI - EĞER TANIMLI DEĞİLSE TANIMLA
if (typeof window.openIntegratedEditor === 'undefined') {
    window.openIntegratedEditor = function(type = 'emoji') {
        console.log('🚀 openIntegratedEditor (fallback):', type);
        
        const modal = document.getElementById('integrated-editor-modal');
        if (modal) {
            modal.style.display = 'flex';
            return true;
        }
        
        // Fallback modal'lar
        if (type === 'flood') {
            const floodModal = document.getElementById('flood-tab');
            if (floodModal) {
                floodModal.style.display = 'flex';
                return true;
            }
        } else {
            const emojiModal = document.getElementById('emoji-tab');
            if (emojiModal) {
                emojiModal.style.display = 'flex';
                return true;
            }
        }
        
        console.error('Hiçbir editör modalı bulunamadı');
        return false;
    };
}

    // Segment switcher
window.switchSegment = function(segment) {
    console.log(`🔄 Segment değiştiriliyor: ${segment}`);
    
    // Tüm segment butonlarını pasif yap
    document.querySelectorAll('.segment-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Tüm segment içeriklerini gizle
    document.querySelectorAll('.segment-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Seçilen segmenti aktif yap
    const segmentBtn = document.getElementById(`show-${segment}`);
    if (segmentBtn) {
        segmentBtn.classList.add('active');
    }
    
    // İlgili içeriği göster
    const contentId = segment === 'drawings' ? 'drawings-segment' : 
                     segment === 'floods' ? 'floods-segment' : 
                     'following-feed';
    const contentElement = document.getElementById(contentId);
    if (contentElement) {
        contentElement.style.display = 'block';
    }
    
    // İçeriği yükle - BU KISMI DÜZELTİN:
    switch(segment) {
        case 'drawings':
            if (typeof loadCommunityDrawings === 'function') loadCommunityDrawings(1);
            break;
        case 'floods':
            // floodCardSystem kontrolü
            if (window.floodCardSystem && typeof window.floodCardSystem.init === 'function') {
                window.floodCardSystem.init().then(() => {
                    // Eğer renderFloodSets fonksiyonu varsa
                    if (window.floodCardSystem.renderFloodSets) {
                        window.floodCardSystem.renderFloodSets('flood-sets-grid');
                    } else if (window.floodCardSystem.renderFloodSetsGrid) {
                        // Alternatif fonksiyon adı
                        window.floodCardSystem.renderFloodSetsGrid('flood-sets-grid');
                    } else {
                        console.warn('⚠️ floodCardSystem.renderFloodSets fonksiyonu bulunamadı');
                        // Basit fallback
                        fetchFloodSets(1);
                    }
                });
            } else {
                // Direkt fetchFloodSets çağır
                if (typeof fetchFloodSets === 'function') fetchFloodSets(1);
            }
            break;
        case 'following':
            if (typeof fetchFollowingFeed === 'function') fetchFollowingFeed();
            break;
    }
};

// fetchFloodSets fonksiyonunu tanımlayın (eğer yoksa):
if (typeof window.fetchFloodSets === 'undefined') {
    window.fetchFloodSets = async function(page = 1) {
        try {
            const floodFilter = document.getElementById('flood-filter');
            const floodSort = document.getElementById('flood-sort');
            const container = document.getElementById('flood-sets-grid');
            
            if (!container) return;
            
            const filter = floodFilter ? floodFilter.value : 'all';
            const sort = floodSort ? floodSort.value : 'newest';
            
            const response = await fetch(`${window.SITE_BASE_URL}core/list_flood_sets.php?page=${page}&filter=${filter}&sort=${sort}`);
            const result = await response.json();
            
            if (result.success) {
                if (window.floodCardSystem && window.floodCardSystem.createFloodSetCard) {
                    container.innerHTML = '';
                    result.sets.forEach(set => {
                        const card = window.floodCardSystem.createFloodSetCard(set);
                        container.appendChild(card);
                    });
                } else {
                    // Basit fallback
                    container.innerHTML = result.sets.map(set => `
                        <div class="flood-set-card">
                            <h4>${set.name}</h4>
                            <p>${set.message_count || 0} mesaj</p>
                        </div>
                    `).join('');
                }
                
                // Sayfalama
                if (typeof createPagination === 'function') {
                    createPagination('floods', page, result.totalPages);
                }
            }
        } catch (error) {
            console.error('Flood setleri yüklenemedi:', error);
        }
    };
}

// DOM yüklendiğinde sistemleri başlat
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        initializeFloodSystem();
        
        // ESC tuşu ile tüm modal'ları kapat
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal[style*="display: flex"]').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });
    }, 500);
	console.log('Global editör fonksiyonları tanımlandı: openIntegratedEditor, openEmojiEditor, openFloodEditor');
    setTimeout(() => {
        console.log('✅ Tüm script\'ler yüklendi, sistem test ediliyor:');
        console.log('- openIntegratedEditor:', typeof window.openIntegratedEditor);
        console.log('- openEmojiEditor:', typeof window.openEmojiEditor);
        console.log('- openFloodEditor:', typeof window.openFloodEditor);
        console.log('- integratedEditor:', window.integratedEditor ? '✓ Tanımlı' : '✗ Tanımlı değil');
        console.log('- floodSystem:', window.floodSystem ? '✓ Tanımlı' : '✗ Tanımlı değil');
        
        // Butonları başlat
        if (typeof initializeButtons === 'function') {
            initializeButtons();
        }
    }, 1000);
});
</script>

</footer>
</body>
</html>