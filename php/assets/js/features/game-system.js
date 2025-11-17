// OYUN SİSTEMİ - Basitleştirilmiş ve Düzeltilmiş
class GameSystem {
    constructor() {
        this.ably = null;
        this.userChannel = null;
        this.isConnected = false;
    }

    async init() {
        console.log('🎮 GameSystem başlatılıyor...');
        await this.initAbly();
        this.bindEvents();

        // Aktif oyunları yükle
        setTimeout(() => {
            this.loadActiveGames();
        }, 1000);
    }

    // ABLY BAĞLANTISI
    async initAbly() {
        // Kullanıcı kontrolü
        const userId = window.PROFILE_DATA?.currentUserId || window.currentUser?.id;
        if (!userId) {
            console.warn('❌ GameSystem: Kullanıcı ID bulunamadı');
            return;
        }

        // Ably kütüphanesi kontrolü
        if (typeof Ably === 'undefined') {
            console.warn('❌ Ably kütüphanesi yüklenmemiş');
            return;
        }

        try {
            console.log('🔗 Ably bağlantısı kuruluyor...');

            this.ably = new Ably.Realtime({
                authUrl: SITE_BASE_URL + 'games/ably_token.php',
                authMethod: 'GET',
                clientId: 'user_' + userId
            });

            this.ably.connection.on('connected', () => {
                console.log('✅ Ably bağlandı!');
                this.isConnected = true;
                this.subscribeToChannels();
            });

            this.ably.connection.on('failed', (err) => {
                console.error('❌ Ably bağlantı hatası:', err);
                this.isConnected = false;
            });

        } catch (err) {
            console.error('❌ Ably başlatma hatası:', err);
        }
    }

    // KANAL ABONELİKLERİ
    subscribeToChannels() {
        const userId = window.PROFILE_DATA?.currentUserId || window.currentUser?.id;
        if (!this.ably || !userId) return;

        try {
            // Kişisel kanal
            this.userChannel = this.ably.channels.get('user-' + userId);

            this.userChannel.subscribe('game_event', (message) => {
                console.log('🎮 Game event:', message.data);
                this.handleGameMessage(message.data);
            });

            console.log('✅ Kanal abonelikleri tamamlandı');

        } catch (error) {
            console.error('❌ Kanal aboneliği hatası:', error);
        }
    }

    // MEYDAN OKUMA SİSTEMİ
    openChallengeModal(targetUserId, gameType) {
        if (!this.checkAuth()) return;

        const targetUsername = document.querySelector('.profile-username')?.textContent || 'Kullanıcı';
        const gameName = this.getGameName(gameType);

        // Modal içeriğini ayarla
        document.getElementById('game-challenge-title').textContent = `🎮 ${gameName} - ${targetUsername}`;

        document.getElementById('game-challenge-content').innerHTML = `
        <div style="text-align: center; padding: 20px;">
        <div style="font-size: 48px; margin-bottom: 20px;">
        ${this.getGameEmoji(gameType)}
        </div>
        <p style="margin-bottom: 20px;">
        <strong>${targetUsername}</strong> kullanıcısına
        <strong>${gameName}</strong> oyunu için meydan okumak üzeresiniz.
        </p>
        <div style="display: flex; gap: 10px; justify-content: center;">
        <button onclick="gameSystem.sendChallenge(${targetUserId}, '${gameType}')" class="btn-primary">
        🚀 Meydan Oku
        </button>
        <button onclick="gameSystem.closeChallengeModal()" class="btn-secondary">
        İptal
        </button>
        </div>
        </div>
        `;

        // Modal'ı aç
        const modal = document.getElementById('game-challenge-modal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    async sendChallenge(targetUserId, gameType) {
        if (!this.checkAuth()) return;

        try {
            const response = await fetch(SITE_BASE_URL + 'games/send_challenge.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    challenged_id: targetUserId,
                    game_type: gameType
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Meydan okuma gönderildi!', 'success');
                this.closeChallengeModal();
            } else {
                this.showNotification(result.message, 'error');
            }

        } catch (error) {
            console.error('Challenge gönderme hatası:', error);
            this.showNotification('Meydan okuma gönderilemedi', 'error');
        }
    }

    async acceptChallenge(challengeId) {
        try {
            const response = await fetch(SITE_BASE_URL + 'games/accept_challenge.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ challenge_id: challengeId, action: 'accept' })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Oyun kabul edildi!', 'success');
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Challenge kabul hatası:', error);
            this.showNotification('İşlem başarısız', 'error');
        }
    }

    async declineChallenge(challengeId) {
        try {
            await fetch(SITE_BASE_URL + 'games/decline_challenge.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ challenge_id: challengeId })
            });
        } catch (error) {
            console.error('Challenge reddetme hatası:', error);
        }
    }

    closeChallengeModal() {
        const modal = document.getElementById('game-challenge-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // OYUN SİSTEMİ
    startGame(gameData) {
        this.showNotification('Oyun başladı! İyi eğlenceler!', 'success');
        this.openGameModal(gameData);
    }

    openGameModal(gameData) {
        document.getElementById('game-modal-title').textContent =
        `${this.getGameName(gameData.game_type)} - ${gameData.opponent_username}`;

        this.loadGameInterface(gameData);

        const modal = document.getElementById('game-modal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    loadGameInterface(gameData) {
        const content = document.getElementById('game-modal-content');
        const gameEmoji = this.getGameEmoji(gameData.game_type);

        content.innerHTML = `
        <div style="display: flex; flex-direction: column; height: 100%; max-width: 800px; margin: 0 auto;">
        <!-- Oyun Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #ccc;">
        <div>
        <span style="font-size: 24px;">${gameEmoji}</span>
        <span style="margin-left: 10px; font-weight: bold;">${this.getGameName(gameData.game_type)}</span>
        </div>
        <div id="game-turn-indicator" style="font-size: 14px; opacity: 0.8;">
        Rakip bekleniyor...
        </div>
        </div>

        <!-- Oyun Tahtası -->
        <div id="game-board-container" style="flex: 1; padding: 20px; text-align: center;">
        <div style="font-size: 48px; margin: 40px 0;">
        ${gameEmoji} Oyun Tahtası
        </div>
        <div style="margin-top: 20px; font-size: 14px;">
        Oyun yükleniyor...
        </div>
        </div>

        <!-- Kontroller -->
        <div style="padding: 15px; border-top: 1px solid #ccc; text-align: center;">
        <button onclick="gameSystem.closeGameModal()" class="btn-secondary">
        ← Geri Dön
        </button>
        </div>
        </div>
        `;

        // Oyun tahtasını yükle
        this.loadGameBoard(gameData.game_type, gameData.game_id);
    }

    loadGameBoard(gameType, gameId) {
        const container = document.getElementById('game-board-container');

        // Basit oyun tahtası - gerçek implementasyon için hazır
        container.innerHTML += `
        <div style="background: #f0f0f0; padding: 20px; border-radius: 10px; margin: 20px auto; max-width: 400px;">
        <p><strong>${this.getGameName(gameType)} Tahtası</strong></p>
        <p>Oyun ID: ${gameId}</p>
        <p>⚡ Gerçek oyun tahtası buraya yüklenecek</p>
        </div>
        `;
    }

    closeGameModal() {
        const modal = document.getElementById('game-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // AKTİF OYUNLARI YÜKLE
    async loadActiveGames() {
        const userId = window.PROFILE_DATA?.currentUserId || window.currentUser?.id;
        if (!userId) return;

        try {
            const response = await fetch(SITE_BASE_URL + 'games/get_active_games.php');
            const responseText = await response.text();

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse hatası:', parseError);
                return;
            }

            const container = document.getElementById('active-games-list');
            if (!container) return;

            if (result.success && result.games && result.games.length > 0) {
                container.innerHTML = result.games.map(game => `
                <div style="border: 1px solid #ddd; border-radius: 8px; padding: 10px; margin-bottom: 8px; background: #f9f9f9;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                <strong>${this.getGameEmoji(game.game_type)} ${this.getGameName(game.game_type)}</strong>
                <div style="font-size: 0.9em; opacity: 0.8;">vs ${game.opponent_username}</div>
                </div>
                <button onclick="gameSystem.startGame(${JSON.stringify(game).replace(/"/g, '&quot;')})"
                class="btn-primary btn-sm">
                🔄 Devam Et
                </button>
                </div>
                </div>
                `).join('');
            } else {
                container.innerHTML = '<p style="opacity: 0.7; text-align: center;">Aktif oyun bulunmuyor.</p>';
            }

        } catch (error) {
            console.error('Aktif oyunlar yüklenirken hata:', error);
            const container = document.getElementById('active-games-list');
            if (container) {
                container.innerHTML = '<p style="opacity: 0.7; text-align: center; color: red;">Oyunlar yüklenirken hata oluştu.</p>';
            }
        }
    }

    // MESAJ İŞLEME
    handleGameMessage(data) {
        console.log('🎮 Game mesajı:', data);

        switch (data.type) {
            case 'challenge_received':
                this.showChallengeNotification(data);
                break;
            case 'challenge_accepted':
                this.showNotification(`${data.opponent_username} meydan okumanızı kabul etti!`, 'success');
                break;
            case 'challenge_declined':
                this.showNotification(`${data.declined_by_username} meydan okumanızı reddetti.`, 'warning');
                break;
            case 'game_move':
                this.handleGameMove(data);
                break;
            case 'game_ended':
                this.handleGameEnd(data);
                break;
        }
    }

    showChallengeNotification(data) {
        const gameName = this.getGameName(data.game_type);
        const challengerName = data.challenger_username;

        if (confirm(`${challengerName} sizi ${gameName} oyununa davet ediyor!\n\nKabul etmek istiyor musunuz?`)) {
            this.acceptChallenge(data.challenge_id);
        } else {
            this.declineChallenge(data.challenge_id);
        }
    }

    handleGameMove(data) {
        console.log('🎯 Rakip hamlesi:', data);
        this.showNotification('Rakip hamle yaptı!', 'info');

        // Tahta güncelleme event'i
        const event = new CustomEvent('opponentMove', { detail: data });
        document.dispatchEvent(event);
    }

    handleGameEnd(data) {
        const userId = window.PROFILE_DATA?.currentUserId || window.currentUser?.id;
        let message = 'Oyun sona erdi.';
        let type = 'info';

        if (data.winner_id === userId) {
            message = '🎉 Tebrikler! Oyunu kazandınız!';
            type = 'success';
        } else if (data.winner_id) {
            message = `😞 Maalesef rakibiniz oyunu kazandı.`;
            type = 'warning';
        }

        this.showNotification(message, type);
        this.closeGameModal();
        this.loadActiveGames();
    }

    // YARDIMCI FONKSİYONLAR
    checkAuth() {
        const isAuthenticated = !!(window.PROFILE_DATA?.currentUserId || window.currentUser?.id);
        if (!isAuthenticated) {
            this.showNotification('Önce giriş yapmalısınız!', 'error');
        }
        return isAuthenticated;
    }

    showNotification(message, type = 'info') {
        // Basit notification - mevcut sisteminizle değiştirebilirsiniz
        console.log(`Notification [${type}]:`, message);

        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            alert(`${type.toUpperCase()}: ${message}`);
        }
    }

    getGameEmoji(gameType) {
        const emojis = {
            chess: '♟️',
            reversi: '🔴',
            tavla: '🎲'
        };
        return emojis[gameType] || '🎮';
    }

    getGameName(gameType) {
        const names = {
            chess: 'Satranç',
            reversi: 'Reversi',
            tavla: 'Tavla'
        };
        return names[gameType] || 'Oyun';
    }

    // EVENT BINDING
    bindEvents() {
        // Challenge butonları
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-game-challenge]')) {
                const targetId = e.target.getAttribute('data-target-id');
                const gameType = e.target.getAttribute('data-game-type');
                this.openChallengeModal(targetId, gameType);
            }
        });

        // ESC tuşu ile modal kapatma
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeChallengeModal();
                this.closeGameModal();
            }
        });

        // Modal dışına tıklama ile kapatma
        document.addEventListener('click', (e) => {
            if (e.target.id === 'game-challenge-modal') {
                this.closeChallengeModal();
            }
            if (e.target.id === 'game-modal') {
                this.closeGameModal();
            }
        });

        // Aktif oyunları periyodik yenile
        setInterval(() => {
            this.loadActiveGames();
        }, 30000);
    }
}

// GLOBAL INSTANCE
const gameSystem = new GameSystem();

// COMPATIBILITY FONKSİYONLARI
function openGameChallengeModal(targetUserId, gameType) {
    gameSystem.openChallengeModal(targetUserId, gameType);
}

function closeGameChallengeModal() {
    gameSystem.closeChallengeModal();
}

function closeGameModal() {
    gameSystem.closeGameModal();
}

function loadActiveGames() {
    gameSystem.loadActiveGames();
}

// OTOMATİK BAŞLATMA
document.addEventListener('DOMContentLoaded', () => {
    gameSystem.init();
});
