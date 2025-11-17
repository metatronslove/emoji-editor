/**
 * Çevrimiçi durum yöneticisi - ORJİNAL ÇALIŞAN VERSİYON
 */
class OnlineStatusManager {
    constructor() {
        this.updateInterval = 30000; // 30 saniye
        this.isUpdating = false;
        this.retryCount = 0;
        this.maxRetries = 3;
        this.isInitialized = false;
    }

    /**
     * Çevrimiçi durum sistemini başlat
     */
    init() {
        if (this.isInitialized) {
            console.log('🔄 OnlineStatusManager zaten başlatılmış');
            return;
        }

        if (!this.isUserValid()) {
            console.warn('⚠️ OnlineStatusManager: Geçerli kullanıcı bulunamadı');
            return;
        }

        console.log('🚀 OnlineStatusManager başlatılıyor...');

        // İlk güncelleme
        this.updateOnlineStatus();

        // Periyodik güncellemeler
        this.intervalId = setInterval(() => {
            this.updateOnlineStatus();
        }, this.updateInterval);

        // Sayfa kapatılırken çevrimdışı yap
        window.addEventListener('beforeunload', () => {
            this.setOfflineStatus();
        });

        // Sayfa görünürlük değişiklikleri
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                console.log('👀 Sayfa görünür oldu, durum güncelleniyor...');
                this.updateOnlineStatus();
            }
        });

        this.isInitialized = true;
        console.log('✅ OnlineStatusManager başarıyla başlatıldı');
    }

    /**
     * Kullanıcı bilgilerini kontrol et
     */
    isUserValid() {
        return !!(window.currentUser && window.currentUser.id && window.currentUser.username);
    }

    /**
     * Çevrimiçi durumu güncelle
     */
    async updateOnlineStatus() {
        try {
            // DOĞRU URL'yi kullan - core/update_online_status.php
            const response = await fetch(SITE_BASE_URL + 'core/update_online_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                credentials: 'include'
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            console.log('✅ Online status güncellendi:', data);

        } catch (err) {
            console.error('❌ Online update error:', err);
        }
    }

    /**
     * Çevrimdışı duruma geç
     */
    async setOfflineStatus() {
        if (!this.isUserValid()) return;

        try {
            const formData = new FormData();
            formData.append('user_id', window.currentUser.id);
            formData.append('offline', '1');

            // Sync request kullan
            fetch(SITE_BASE_URL + 'core/update_online_status.php', {
                method: 'POST',
                body: formData,
                keepalive: true
            });

        } catch (error) {
            // Sayfa kapanırken hata önemsiz
        }
    }

    /**
     * Çevrimiçi kullanıcı listesini güncelle
     */
    async updateOnlineUsers() {
        try {
            const response = await fetch(SITE_BASE_URL + 'core/get_online_users.php');

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                // Navbar'daki çevrimiçi kullanıcı sayısını güncelle
                this.updateOnlineCount(result.online_count, result.online_text);

                // Profil sayfasındaysak çevrimiçi durumu güncelle
                this.updateProfileOnlineStatus();
            }
        } catch (error) {
            console.error('❌ Online users update error:', error);
        }
    }

    /**
     * Navbar'daki çevrimiçi kullanıcı sayısını güncelle
     */
    updateOnlineCount(count, text) {
        // Stats bar'daki çevrimiçi kullanıcı bilgisini güncelle
        const onlineElements = document.querySelectorAll('[data-online-count]');
        onlineElements.forEach(element => {
            element.textContent = text || `${count} çevrimiçi kullanıcı`;
        });

        // Sayfa başlığına çevrimiçi sayısını ekle (opsiyonel)
        if (count > 0 && !document.title.includes('(')) {
            document.title = document.title.replace(/\(\d+\)\s*/, '') + ` (${count})`;
        }
    }

    /**
     * Profil sayfasındaki çevrimiçi durumu güncelle
     */
    updateProfileOnlineStatus() {
        if (window.PROFILE_DATA && window.PROFILE_DATA.userId) {
            // Profil sayfasındaki çevrimiçi göstergesini güncelle
            const onlineIndicator = document.querySelector('[data-online-indicator]');
            if (onlineIndicator) {
                // Bu kısım profil sayfasına özel, gerektiğinde genişletilebilir
            }
        }
    }

    /**
     * Güncelleme hatasını yönet
     */
    handleUpdateFailure(errorMessage) {
        this.retryCount++;

        if (this.retryCount >= this.maxRetries) {
            console.error(`🛑 Maksimum deneme sayısına ulaşıldı (${this.maxRetries}). Online güncellemeler durduruluyor.`);
            this.stop();
        } else {
            console.warn(`🔄 Yeniden denenecek (${this.retryCount}/${this.maxRetries})`);
        }
    }

    /**
     * Çevrimiçi durum güncellemelerini durdur
     */
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        this.isInitialized = false;
        console.log('🛑 OnlineStatusManager durduruldu');
    }

    /**
     * Manuel olarak çevrimiçi durumu güncelle
     */
    forceUpdate() {
        console.log('🔧 Manuel online status güncellemesi tetiklendi');
        this.retryCount = 0;
        this.updateOnlineStatus();
    }
}

// Global OnlineStatusManager instance'ı oluştur
window.OnlineStatusManager = new OnlineStatusManager();

/**
 * Eski fonksiyonlar için compatibility layer
 */
function updateOnlineStatus() {
    if (window.OnlineStatusManager) {
        window.OnlineStatusManager.forceUpdate();
    }
}

function initOnlineStatus() {
    if (window.OnlineStatusManager) {
        window.OnlineStatusManager.init();
    }
}

/**
 * Sayfa yüklendiğinde çevrimiçi durum sistemini başlat
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏠 DOM yüklendi, online status sistemi başlatılıyor...');

    // Kullanıcı bilgilerinin yüklenmesini bekle
    setTimeout(() => {
        if (window.OnlineStatusManager) {
            window.OnlineStatusManager.init();
        }
    }, 1000);
});

/**
 * Kullanıcı giriş yaptığında tetiklenebilmesi için global fonksiyon
 */
window.initOnlineStatus = initOnlineStatus;
window.updateOnlineStatus = updateOnlineStatus;

// Hata ayıklama için global erişim
if (typeof window !== 'undefined') {
    window.debugOnlineStatus = function() {
        console.log('🔍 OnlineStatusManager Debug Info:');
        console.log('- User:', window.currentUser);
        console.log('- Manager:', window.OnlineStatusManager);
        console.log('- SITE_BASE_URL:', SITE_BASE_URL);

        if (window.OnlineStatusManager) {
            window.OnlineStatusManager.forceUpdate();
        }
    };
}
