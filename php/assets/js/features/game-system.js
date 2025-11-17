// assets/js/features/game-system.js
class GameSystem {
    constructor() {
        this.ably = null;
        this.ablyChannel = null;
        this.isConnected = false;
        this.currentGame = null;
        this.pendingChallenges = [];
        this.activeGames = [];
    }

    async init() {
        if (!window.currentUser || !window.currentUser.id) {
            console.log('Game system: Kullanıcı girişi yok');
            return;
        }

        try {
            await this.connectToAbly();
            this.setupEventListeners();
            this.loadActiveGames();
            this.loadPendingChallenges();

            console.log('🎮 Oyun sistemi başlatıldı');
        } catch (error) {
            console.error('Oyun sistemi başlatma hatası:', error);
        }
    }

    async connectToAbly() {
        try {
            // Ably token al
            const tokenResponse = await fetch(SITE_BASE_URL + 'games/ably_token.php');
            const tokenData = await tokenResponse.json();

            if (!tokenData.token) {
                throw new Error('Token alınamadı');
            }

            // Ably client'ı başlat
            this.ably = new Ably.Realtime({
                token: tokenData.token,
                echoMessages: false
            });

            this.ably.connection.on('connected', () => {
                this.isConnected = true;
                console.log('🔗 Ably bağlantısı kuruldu');

                // Kullanıcı kanalına abone ol
                this.ablyChannel = this.ably.channels.get('user-' + window.currentUser.id);
                this.ablyChannel.subscribe('game_event', (message) => {
                    this.handleGameEvent(message.data);
                });
            });

            this.ably.connection.on('failed', () => {
                this.isConnected = false;
                console.error('❌ Ably bağlantısı başarısız');
            });

        } catch (error) {
            console.error('Ably bağlantı hatası:', error);
            this.isConnected = false;
        }
    }

    setupEventListeners() {
        // Oyun butonlarına event listener ekle
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-game-challenge]')) {
                this.handleGameChallenge(e.target);
            }

            if (e.target.matches('[data-accept-challenge]')) {
                this.handleAcceptChallenge(e.target);
            }

            if (e.target.matches('[data-decline-challenge]')) {
                this.handleDeclineChallenge(e.target);
            }
        });

        // Sayfa görünürlüğü değiştiğinde aktif oyunları yenile
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.loadActiveGames();
                this.loadPendingChallenges();
            }
        });

        // Her 30 saniyede bir aktif oyunları kontrol et
        setInterval(() => {
            this.loadActiveGames();
            this.loadPendingChallenges();
        }, 30000);
    }

    async handleGameChallenge(button) {
        const targetId = button.dataset.targetId;
        const gameType = button.dataset.gameType;

        if (!targetId || !gameType) {
            showNotification('Geçersiz oyun daveti parametreleri', 'error');
            return;
        }

        try {
            const response = await fetch(SITE_BASE_URL + 'games/send_challenge.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    challenged_id: targetId,
                    game_type: gameType
                })
            });

            const result = await response.json();

            if (result.success) {
                showNotification(result.message, 'success');
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Oyun daveti gönderme hatası:', error);
            showNotification('Davet gönderilirken hata oluştu', 'error');
        }
    }

    async handleAcceptChallenge(button) {
        const challengeId = button.dataset.challengeId;

        try {
            const response = await fetch(SITE_BASE_URL + 'games/accept_challenge.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    challenge_id: challengeId,
                    action: 'accept'
                })
            });

            const result = await response.json();

            if (result.success) {
                showNotification(result.message, 'success');
                this.loadPendingChallenges();
                this.loadActiveGames();

                // Oyunu başlat
                if (result.game_id) {
                    this.openGameModal(result.game_id);
                }
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Davet kabul etme hatası:', error);
            showNotification('Davet kabul edilirken hata oluştu', 'error');
        }
    }

    async handleDeclineChallenge(button) {
        const challengeId = button.dataset.challengeId;

        try {
            const response = await fetch(SITE_BASE_URL + 'games/decline_challenge.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    challenge_id: challengeId
                })
            });

            const result = await response.json();

            if (result.success) {
                showNotification(result.message, 'success');
                this.loadPendingChallenges();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Davet reddetme hatası:', error);
            showNotification('Davet reddedilirken hata oluştu', 'error');
        }
    }

    async loadActiveGames() {
        try {
            const response = await fetch(SITE_BASE_URL + 'games/get_active_games.php');
            const result = await response.json();

            if (result.success) {
                this.activeGames = result.games || [];
                this.updateActiveGamesDisplay();
            }
        } catch (error) {
            console.error('Aktif oyunlar yüklenirken hata:', error);
        }
    }

    async loadPendingChallenges() {
        try {
            const response = await fetch(SITE_BASE_URL + 'core/get_pending_challenges.php');
            const result = await response.json();

            if (result.success) {
                this.pendingChallenges = result.challenges || [];
                this.updatePendingChallengesDisplay();
            }
        } catch (error) {
            console.error('Bekleyen davetler yüklenirken hata:', error);
        }
    }

    updateActiveGamesDisplay() {
        const container = document.getElementById('active-games-list');
        if (!container) return;

        if (this.activeGames.length === 0) {
            container.innerHTML = '<p style="opacity: 0.7; text-align: center;">Aktif oyununuz yok</p>';
            return;
        }

        container.innerHTML = this.activeGames.map(game => `
        <div class="active-game-item" style="
        padding: 10px;
        margin-bottom: 8px;
        background: var(--fixed-bg);
        border-radius: 6px;
        border: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        ">
        <div>
        <strong>${this.getGameEmoji(game.game_type)} ${this.getGameName(game.game_type)}</strong>
        <br>
        <small>vs ${game.opponent_username}</small>
        </div>
        <div>
        ${game.is_my_turn ?
            '<span style="color: #4CAF50;">▶️ Sıra sizde</span>' :
            '<span style="opacity: 0.7;">⏸️ Rakibin sırası</span>'
        }
        <br>
        <button onclick="gameSystem.openGameModal(${game.game_id})"
        class="btn-primary btn-sm"
        style="margin-top: 5px; padding: 4px 8px; font-size: 12px;">
        Oyna
        </button>
        </div>
        </div>
        `).join('');
    }

    updatePendingChallengesDisplay() {
        // Profil sayfasındaki takip istekleri bölümünü kullan
        const container = document.getElementById('follow-requests-list');
        if (!container || this.pendingChallenges.length === 0) return;

        const challengeHTML = this.pendingChallenges.map(challenge => `
        <div class="challenge-item" style="
        padding: 12px;
        margin-bottom: 10px;
        background: var(--fixed-bg);
        border-radius: 8px;
        border: 2px solid var(--accent-color);
        ">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <img src="${challenge.challenger_picture || '/images/default.png'}"
        alt="Profil"
        style="width: 40px; height: 40px; border-radius: 50%;">
        <div>
        <strong>${challenge.challenger_username}</strong>
        <br>
        <span>${challenge.game_name} oyunu için meydan okudu</span>
        </div>
        </div>
        <div style="display: flex; gap: 8px; justify-content: space-between;">
        <button data-accept-challenge data-challenge-id="${challenge.id}"
        class="btn-success btn-sm"
        style="flex: 1;">
        ✅ Kabul Et
        </button>
        <button data-decline-challenge data-challenge-id="${challenge.id}"
        class="btn-danger btn-sm"
        style="flex: 1;">
        ❌ Reddet
        </button>
        </div>
        <div style="text-align: center; margin-top: 8px; font-size: 0.8em; opacity: 0.7;">
        ${challenge.formatted_time}
        </div>
        </div>
        `).join('');

        // Mevcut içeriğin yanına ekle
        container.innerHTML = challengeHTML + container.innerHTML;
    }

    handleGameEvent(event) {
        console.log('Oyun eventi alındı:', event);

        switch (event.type) {
            case 'challenge_received':
                this.showChallengeNotification(event);
                this.loadPendingChallenges();
                break;

            case 'challenge_accepted':
                showNotification(`${event.challenger_username} davetinizi kabul etti!`, 'success');
                this.loadActiveGames();
                break;

            case 'challenge_declined':
                showNotification(`${event.challenger_username} davetinizi reddetti.`, 'warning');
                break;

            case 'game_move':
                if (this.currentGame && this.currentGame.id === event.game_id) {
                    this.updateGameBoard(event.game_state);
                }
                break;

            case 'game_end':
                showNotification(`Oyun bitti: ${event.result}`, 'info');
                if (this.currentGame && this.currentGame.id === event.game_id) {
                    this.closeGameModal();
                }
                this.loadActiveGames();
                break;
        }
    }

    showChallengeNotification(event) {
        const notification = document.createElement('div');
        notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--card-bg);
        border: 2px solid var(--accent-color);
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        max-width: 300px;
        `;

        notification.innerHTML = `
        <strong>🎮 Oyun Daveti!</strong>
        <p>${event.challenger_username} size ${this.getGameName(event.game_type)} oyunu için meydan okudu.</p>
        <div style="display: flex; gap: 8px; margin-top: 10px;">
        <button onclick="gameSystem.handleAcceptFromNotification(${event.challenge_id})"
        class="btn-success btn-sm">
        ✅ Kabul
        </button>
        <button onclick="gameSystem.handleDeclineFromNotification(${event.challenge_id})"
        class="btn-danger btn-sm">
        ❌ Reddet
        </button>
        </div>
        `;

        document.body.appendChild(notification);

        // 30 saniye sonra otomatik kaldır
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 30000);
    }

    async handleAcceptFromNotification(challengeId) {
        await this.handleAcceptChallenge({ dataset: { challengeId: challengeId } });
        this.removeChallengeNotification();
    }

    async handleDeclineFromNotification(challengeId) {
        await this.handleDeclineChallenge({ dataset: { challengeId: challengeId } });
        this.removeChallengeNotification();
    }

    removeChallengeNotification() {
        const notification = document.querySelector('[style*="position: fixed"][style*="top: 20px"][style*="right: 20px"]');
        if (notification) {
            notification.remove();
        }
    }

    getGameEmoji(gameType) {
        const emojis = {
            'chess': '♟️',
            'reversi': '🔴',
            'tavla': '🎲'
        };
        return emojis[gameType] || '🎮';
    }

    getGameName(gameType) {
        const names = {
            'chess': 'Satranç',
            'reversi': 'Reversi',
            'tavla': 'Tavla'
        };
        return names[gameType] || 'Oyun';
    }

    openGameModal(gameId) {
        // Oyun modalını açma kodu buraya gelecek
        console.log('Oyun modalı açılıyor:', gameId);
        showNotification('Oyun modülü hazırlanıyor...', 'info');
    }

    closeGameModal() {
        // Oyun modalını kapatma kodu buraya gelecek
        this.currentGame = null;
    }

    updateGameBoard(gameState) {
        // Oyun tahtasını güncelleme kodu buraya gelecek
        console.log('Oyun tahtası güncelleniyor:', gameState);
    }
}

// Global game system instance
window.gameSystem = new GameSystem();

// Sayfa yüklendiğinde oyun sistemini başlat
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        if (window.gameSystem) {
            window.gameSystem.init();
        }
    }, 2000);
});
