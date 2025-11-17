// GELİŞMİŞ BİLDİRİM SİSTEMİ - Ably Entegre
class NotificationSystem {
    constructor() {
        this.ably = null;
        this.notificationChannel = null;
        this.isAblyConnected = false;
        this.container = document.getElementById('notification');
        this.notificationQueue = [];

        if (!this.container) {
            this.createNotificationContainer();
        }

        // Ably kontrolü - DÜZELTME
        if (typeof Ably === 'undefined') {
            console.warn('🔔 Ably kütüphanesi yüklenmemiş, bildirim sistemi devre dışı');
            return;
        }

        this.initAbly();
    }

    async initAbly() {
        // Kullanıcı kontrolü - DÜZELTME
        const userId = window.currentUser?.id || window.PROFILE_DATA?.currentUserId;
        if (!userId) {
            console.warn('🔔 Ably: Kullanıcı bilgisi bulunamadı');
            return;
        }

        try {
            // AuthUrl ile Ably başlat - DÜZELTME
            this.ably = new Ably.Realtime({
                authUrl: window.SITE_BASE_URL + 'games/ably_token.php',
                authMethod: 'GET',
                clientId: 'user_' + userId
            });

            this.ably.connection.on('connected', () => {
                console.log('🔔 Ably bildirim bağlantısı kuruldu');
                this.isAblyConnected = true;
                this.subscribeToNotificationChannel();
            });

            this.ably.connection.on('failed', (err) => {
                console.error('🔔 Ably bağlantı hatası:', err);
                this.isAblyConnected = false;
            });

        } catch (error) {
            console.error('Ably bildirim başlatma hatası:', error);
        }
    }

    subscribeToNotificationChannel() {
        if (!this.ably) return;

        const userId = window.currentUser?.id || window.PROFILE_DATA?.currentUserId;
        if (!userId) return;

        this.notificationChannel = this.ably.channels.get('user-notifications-' + userId);

        // Bildirimleri dinle
        this.notificationChannel.subscribe('new_notification', (message) => {
            this.handleRemoteNotification(message.data);
        });

        // Toplu bildirimler
        this.notificationChannel.subscribe('broadcast', (message) => {
            this.handleBroadcastNotification(message.data);
        });

        console.log('🔔 Ably bildirim kanalına abone olundu');
    }

    handleRemoteNotification(data) {
        console.log('🔔 Uzaktan bildirim alındı:', data);

        // Bildirimi kuyruğa ekle
        this.addToQueue(data);

        // Sistem bildirimi göster
        this.showSystemNotification(data);
    }

    handleBroadcastNotification(data) {
        console.log('📢 Toplu bildirim:', data);

        // Tüm kullanıcılara göster
        this.show(data.message, data.type || 'info', {
            duration: data.duration || 5000,
            action: data.action
        });
    }

    // BİLDİRİM KUYRUK SİSTEMİ
    addToQueue(notification) {
        this.notificationQueue.push(notification);
        this.processQueue();
    }

    processQueue() {
        if (this.notificationQueue.length === 0) return;

        const notification = this.notificationQueue[0];
        this.showQueuedNotification(notification);
    }

    showQueuedNotification(notification) {
        this.show(notification.message, notification.type, {
            duration: notification.duration || 3000,
            onClose: () => {
                // Sıradaki bildirimi işle
                this.notificationQueue.shift();
                setTimeout(() => this.processQueue(), 500);
            }
        });
    }

    // GELİŞMİŞ BİLDİRİM GÖSTERME
    show(message, type = 'info', options = {}) {
        const {
            duration = 3000,
            action = null,
            icon = null,
            onClose = null
        } = options;

        // Container oluştur
        if (!this.container) {
            this.createNotificationContainer();
        }

        // Bildirim HTML'i
        const notificationId = 'notification-' + Date.now();
        const notificationHTML = `
        <div id="${notificationId}" class="notification-item" style="
        background: ${this.getNotificationColor(type)};
        color: white;
        padding: 12px 16px;
        margin-bottom: 8px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: space-between;
        animation: slideInRight 0.3s ease-out;
        ">
        <div style="display: flex; align-items: center; gap: 10px;">
        ${icon || this.getNotificationIcon(type)}
        <span>${message}</span>
        </div>
        ${action ? `
            <button onclick="${action}" style="
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            ">İncele</button>
            ` : ''}
            <button onclick="notificationSystem.hide('${notificationId}')" style="
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            margin-left: 10px;
            ">×</button>
            </div>
            `;

            // Container'a ekle
            this.container.insertAdjacentHTML('beforeend', notificationHTML);

            // Otomatik kapanma
            if (duration > 0) {
                setTimeout(() => {
                    this.hide(notificationId);
                    if (onClose) onClose();
                }, duration);
            }

            // Ses bildirimi
            this.playNotificationSound(type);
    }

    hide(notificationId) {
        const notification = document.getElementById(notificationId);
        if (notification) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }

    // UZAKTAN BİLDİRİM GÖNDERME
    async sendRemoteNotification(userId, message, type = 'info', options = {}) {
        if (!this.isAblyConnected) {
            console.warn('Ably bağlantısı yok, bildirim gönderilemedi');
            return;
        }

        try {
            const notificationData = {
                user_id: userId,
                message: message,
                type: type,
                timestamp: new Date().toISOString(),
                ...options
            };

            const userChannel = this.ably.channels.get('user-notifications-' + userId);
            await userChannel.publish('new_notification', notificationData);

            console.log('🔔 Uzaktan bildirim gönderildi:', notificationData);

        } catch (error) {
            console.error('Uzaktan bildirim gönderme hatası:', error);
        }
    }

    // TOPLU BİLDİRİM GÖNDERME (Admin için)
    async broadcastNotification(message, type = 'info', options = {}) {
        if (!this.isAblyConnected || window.currentUser.role !== 'admin') {
            return;
        }

        try {
            const broadcastData = {
                message: message,
                type: type,
                broadcast_by: window.currentUser.username,
                timestamp: new Date().toISOString(),
                ...options
            };

            const broadcastChannel = this.ably.channels.get('broadcast');
            await broadcastChannel.publish('notification', broadcastData);

            console.log('📢 Toplu bildirim gönderildi:', broadcastData);

        } catch (error) {
            console.error('Toplu bildirim gönderme hatası:', error);
        }
    }

    // YARDIMCI FONKSİYONLAR
    getNotificationColor(type) {
        const colors = {
            success: '#4CAF50',
            error: '#f44336',
            warning: '#FF9800',
            info: '#2196F3'
        };
        return colors[type] || colors.info;
    }

    getNotificationIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }

    playNotificationSound(type) {
        // Basit bir ses efekti
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        // Tip'e göre farklı sesler
        const frequencies = {
            success: 523.25, // C5
            error: 349.23,   // F4
            warning: 392.00, // G4
            info: 440.00     // A4
        };

        oscillator.frequency.value = frequencies[type] || frequencies.info;
        gainNode.gain.value = 0.1;

        oscillator.start();
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.5);
        oscillator.stop(audioContext.currentTime + 0.5);
    }

    showSystemNotification(notification) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const systemNotification = new Notification('Bildirim', {
                body: notification.message,
                icon: '/favicon.ico',
                tag: 'message'
            });

            systemNotification.onclick = () => {
                window.focus();
                if (notification.action) {
                    eval(notification.action);
                }
            };
        }
    }

    createNotificationContainer() {
        this.container = document.createElement('div');
        this.container.id = 'notification';
        this.container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        max-width: 400px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        `;
        document.body.appendChild(this.container);

        // CSS animasyonları
        const style = document.createElement('style');
        style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .notification-item {
            animation: slideInRight 0.3s ease-out;
        }
        `;
        document.head.appendChild(style);
    }
}

// Global notification instance'ı
const notificationSystem = new NotificationSystem();

/**
 * Bildirim göster
 */
function showNotification(message, type = 'info', duration = 3000) {
    const { notification } = window.DOM_ELEMENTS;

    if (!notification) {
        console.log('Notification:', message);
        return;
    }

    notification.textContent = message;
    notification.className = '';
    notification.classList.add(type);
    notification.classList.add('show');

    setTimeout(() => {
        notification.classList.remove('show');
    }, duration);
}

/**
 * Onay modalı göster
 */
function showConfirm(title, message) {
    return new Promise((resolve) => {
        const {
            confirmModal,
            modalTitle,
            modalMessage,
            modalConfirm,
            modalCancel
        } = DOM_ELEMENTS;

        if (!confirmModal) {
            const userConfirmed = confirm(`${title}\n${message}\n\nEvet için OK, İptal için Cancel'a basın.`);
            resolve(userConfirmed);
            return;
        }

        modalTitle.textContent = title;
        modalMessage.textContent = message;
        confirmModal.classList.add('show');

        const confirmHandler = () => {
            confirmModal.classList.remove('show');
            modalConfirm.removeEventListener('click', confirmHandler);
            modalCancel.removeEventListener('click', cancelHandler);
            resolve(true);
        };

        const cancelHandler = () => {
            confirmModal.classList.remove('show');
            modalConfirm.removeEventListener('click', confirmHandler);
            modalCancel.removeEventListener('click', cancelHandler);
            resolve(false);
        };

        modalConfirm.onclick = confirmHandler;
        modalCancel.onclick = cancelHandler;
    });
}
