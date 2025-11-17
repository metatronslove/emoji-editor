// ANA UYGULAMA BAŞLATICI
class App {
    constructor() {
        this.modules = [];
        this.isInitialized = false;
    }

    async init() {
        if (this.isInitialized) return;

        console.log('🚀 Uygulama başlatılıyor...');

        try {
            // Temel sistemleri başlat
            await this.initCoreSystems();

            // Feature modüllerini başlat
            if (SITE_BASE_URL)
            await this.initFeatureModules();

            // Event listener'ları kur
            this.bindGlobalEvents();

            this.isInitialized = true;
            console.log('✅ Uygulama başlatma tamamlandı');

        } catch (error) {
            console.error('❌ Uygulama başlatma hatası:', error);
        }
    }

    async initCoreSystems() {
        // Modal sistemi
        this.modules.push(modalSystem);

        // Bildirim sistemi
        this.modules.push(notificationSystem);

        console.log('🔧 Temel sistemler başlatıldı');
    }

    async initFeatureModules() {
        // Game sistemi (Ably entegre)
        if (typeof gameSystem !== 'undefined') {
            await gameSystem.init();
            this.modules.push(gameSystem);
        }

        // Mesajlaşma sistemi (Ably entegre)
        if (typeof messagingSystem !== 'undefined') {
            await messagingSystem.init();
            this.modules.push(messagingSystem);
        }

        // Profil sistemi (Ably entegre)
        if (typeof profileSystem !== 'undefined') {
            await profileSystem.init();
            this.modules.push(profileSystem);
        }

        console.log('🎯 Feature modülleri başlatıldı');
    }

    bindGlobalEvents() {
        // Sayfa görünürlüğü değişikliği
        document.addEventListener('visibilitychange', () => {
            this.handleVisibilityChange();
        });

        // Çevrimiçi/çevrimdışı durumu
        window.addEventListener('online', () => {
            this.handleOnlineStatus(true);
        });

        window.addEventListener('offline', () => {
            this.handleOnlineStatus(false);
        });

        // Sayfa kapatma
        window.addEventListener('beforeunload', () => {
            this.handlePageUnload();
        });

        console.log('🔗 Global event listener\'lar kuruldu');
    }

    handleVisibilityChange() {
        const isVisible = !document.hidden;

        if (isVisible) {
            // Sayfa görünür oldu - çevrimiçi durumu güncelle
            if (messagingSystem) {
                messagingSystem.updateOnlineStatus(true);
            }

            // Bildirim sayısını sıfırla
            document.title = document.title.replace(/^\(\d+\) /, '');
        } else {
            // Sayfa gizlendi
            console.log('👁️ Sayfa gizlendi');
        }
    }

    handleOnlineStatus(isOnline) {
        const status = isOnline ? '🟢 Çevrimiçi' : '🔴 Çevrimdışı';
        showNotification(status, isOnline ? 'success' : 'warning');

        // Tüm modüllere durumu bildir
        this.modules.forEach(module => {
            if (module.handleOnlineStatus) {
                module.handleOnlineStatus(isOnline);
            }
        });
    }

    handlePageUnload() {
        // Çevrimdışı durumu güncelle
        if (messagingSystem) {
            messagingSystem.updateOnlineStatus(false);
        }

        // Tüm Ably bağlantılarını kapat
        this.modules.forEach(module => {
            if (module.ably) {
                module.ably.close();
            }
        });

        console.log('👋 Uygulama kapatılıyor...');
    }

    // MODÜL YÖNETİMİ
    registerModule(module) {
        this.modules.push(module);
    }

    getModule(moduleName) {
        return this.modules.find(module =>
            module.constructor.name.toLowerCase() === moduleName.toLowerCase()
        );
    }

    // SİSTEM DURUMU
    getSystemStatus() {
        return {
            initialized: this.isInitialized,
            modules: this.modules.map(module => ({
                name: module.constructor.name,
                ablyConnected: module.isAblyConnected || false,
                status: 'active'
            })),
            online: navigator.onLine,
            visibility: !document.hidden
        };
    }
}
