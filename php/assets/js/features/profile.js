// assets/js/features/profile.js - GÜNCELLEMELER
// Mevcut ProfileSystem class'ına eklenmesi gereken metodlar

class ProfileSystem {
    constructor() {
        // Mevcut constructor'a yeni property'ler ekleyin
        this.profileData = window.PROFILE_DATA || {};
        this.currentUserId = window.currentUser?.id;
        this.isInitialized = false;

        // Mevcut property'leri koruyun
        this.ably = null;
        this.profileChannel = null;
        this.isAblyConnected = false;
    }

    async init() {
        if (this.isInitialized) return;

        console.log('👤 Profil sistemi başlatılıyor...', this.profileData);

        // Mevcut init fonksiyonunu koruyun
        await this.initAbly();
        this.bindEvents();
        this.loadProfileContent();

        // Yeni fonksiyonları ekleyin
        this.setupEventListeners();
        this.setupButtonHandlers();

        this.isInitialized = true;
    }

    // YENİ EVENT LISTENER METODLARI
    setupEventListeners() {
        // Takip butonu (mevcut bindEvents ile çakışmaması için kontrol)
        const followBtn = document.getElementById('followButton');
        if (followBtn && !followBtn.hasListener) {
            followBtn.addEventListener('click', () => this.handleFollowAction());
            followBtn.hasListener = true;
        }

        // Engelle butonu
        const blockBtn = document.getElementById('blockButton');
        if (blockBtn && !blockBtn.hasListener) {
            blockBtn.addEventListener('click', () => this.handleBlockAction());
            blockBtn.hasListener = true;
        }

        // Takip isteği butonu
        const followRequestBtn = document.getElementById('followRequestBtn');
        if (followRequestBtn && !followRequestBtn.hasListener) {
            followRequestBtn.addEventListener('click', () => this.handleFollowAction());
            followRequestBtn.hasListener = true;
        }

        // Mesaj butonları - event delegation
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-simple-message]') || e.target.closest('[data-simple-message]')) {
                const button = e.target.matches('[data-simple-message]') ? e.target : e.target.closest('[data-simple-message]');
                this.handleSimpleMessage(button);
            }

            if (e.target.matches('[data-game-challenge]') || e.target.closest('[data-game-challenge]')) {
                const button = e.target.matches('[data-game-challenge]') ? e.target : e.target.closest('[data-game-challenge]');
                this.handleGameChallenge(button);
            }
        });

        console.log('🔗 Gelişmiş profil event listenerları bağlandı');
    }

    setupButtonHandlers() {
        // Dinamik olarak oluşturulan butonlar için event delegation
        document.addEventListener('click', async (e) => {
            // Takip isteği yönetimi (profil sahibi için)
            if (e.target.matches('[data-request-action]') || e.target.closest('[data-request-action]')) {
                const button = e.target.matches('[data-request-action]') ? e.target : e.target.closest('[data-request-action]');
                await this.handleRequestAction(button);
            }

            // Sosyal medya bağlantı silme
            if (e.target.matches('[data-remove-social]') || e.target.closest('[data-remove-social]')) {
                const button = e.target.matches('[data-remove-social]') ? e.target : e.target.closest('[data-remove-social]');
                await this.handleSocialLinkRemove(button);
            }

            // Basit mesaj modalı gönderme butonu
            if (e.target.matches('#send-simple-message') || e.target.closest('#send-simple-message')) {
                await this.sendSimpleMessage();
            }

            // Basit mesaj modalı dosya temizleme
            if (e.target.matches('#clear-simple-modal-file') || e.target.closest('#clear-simple-modal-file')) {
                this.clearSimpleModalFile();
            }
        });
    }

    // YENİ PROFIL İÇERİK YÜKLEME METODLARI
    async loadProfileContent() {
        try {
            // Mevcut içerik yükleme metodlarını koruyun
            if (document.getElementById('profile-board')) {
                this.fetchProfileComments();
            }

            if (window.PROFILE_DATA.isProfileOwner && document.getElementById('follow-requests-list')) {
                this.fetchFollowRequests();
            }

            if (document.getElementById('user-drawing-list')) {
                this.fetchUserDrawings();
            }

            if (document.getElementById('user-activities')) {
                this.loadUserActivities();
            }

            if (window.PROFILE_DATA.isProfileOwner) {
                this.loadSocialLinks();
                this.loadPlatformOptions();
            }

            // Yeni içerik yükleme metodları
            if (this.profileData.userId && this.profileData.canViewContent) {
                await this.loadUserDrawings();
            }

            if (this.profileData.userId) {
                await this.loadBoardComments();
            }

            if (this.profileData.userId && this.profileData.canViewContent) {
                await this.loadUserActivities();
            }

            if (this.profileData.isProfileOwner) {
                await this.loadOwnerContent();
            }

        } catch (error) {
            console.error('Profil içeriği yüklenirken hata:', error);
        }
    }

    async loadOwnerContent() {
        // Profil sahibine özel içerikleri yükle
        console.log('👑 Profil sahibi içerikleri yükleniyor...');
    }

    // GELİŞMİŞ TAKIP İŞLEMLERİ
    async handleFollowAction() {
        if (!this.currentUserId) {
            showNotification('Bu işlem için giriş yapmalısınız.', 'error');
            return;
        }

        if (this.profileData.isBlockingMe || this.profileData.isBlockedByMe) {
            showNotification('Engellenmiş kullanıcılarla etkileşimde bulunamazsınız.', 'error');
            return;
        }

        const followBtn = document.getElementById('followButton') || document.getElementById('followRequestBtn');
        const action = followBtn?.dataset.action || 'follow';
        const targetId = this.profileData.userId;

        try {
            let result;

            // Real-time bağlantı varsa real-time kullan, yoksa HTTP
            if (this.isAblyConnected) {
                result = await this.followUserRealTime(targetId, action);
            } else {
                result = await this.followUserHTTP(targetId, action);
            }

            if (result.success) {
                showNotification(result.message, 'success');
                this.updateFollowButton(result.newAction || (action === 'follow' ? 'unfollow' : 'follow'));

                // Real-time güncelleme
                if (action === 'follow') {
                    this.updateFollowerCount(parseInt(document.querySelector('[data-follower-count]')?.textContent || 0) + 1);
                } else {
                    this.updateFollowerCount(parseInt(document.querySelector('[data-follower-count]')?.textContent || 0) - 1);
                }

                // Sayfayı yenile
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Takip işlemi hatası:', error);
            showNotification('İşlem sırasında hata oluştu.', 'error');
        }
    }

    updateFollowButton(newAction) {
        const followBtn = document.getElementById('followButton') || document.getElementById('followRequestBtn');
        if (!followBtn) return;

        const texts = {
            'follow': 'Takip Et',
            'unfollow': 'Takibi Bırak',
            'pending': 'İstek Gönderildi'
        };

        followBtn.dataset.action = newAction;
        followBtn.textContent = texts[newAction] || 'Takip Et';

        if (newAction === 'pending') {
            followBtn.disabled = true;
            followBtn.style.opacity = '0.7';
        } else {
            followBtn.disabled = false;
            followBtn.style.opacity = '1';
        }
    }

    // GELİŞMİŞ ENGELLEME İŞLEMLERİ
    async handleBlockAction() {
        if (!this.currentUserId) {
            showNotification('Bu işlem için giriş yapmalısınız.', 'error');
            return;
        }

        if (this.profileData.isProfileOwner) {
            showNotification('Kendinizi engelleyemezsiniz.', 'error');
            return;
        }

        const blockBtn = document.getElementById('blockButton');
        const isCurrentlyBlocked = this.profileData.isBlockedByMe;
        const action = isCurrentlyBlocked ? 'unblock' : 'block';
        const targetId = this.profileData.userId;

        const confirmMessage = isCurrentlyBlocked ?
        'Bu kullanıcının engelini kaldırmak istediğinizden emin misiniz?' :
        'Bu kullanıcıyı engellemek istediğinizden emin misiniz? Tüm karşılıklı etkileşimleriniz kesilecek.';

        if (!await showConfirm('Engelleme İşlemi', confirmMessage)) {
            return;
        }

        try {
            const response = await fetch(`${SITE_BASE_URL}actions/block_action.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `target_id=${targetId}&action=${action}`
            });

            const result = await response.json();

            if (result.success) {
                showNotification(result.message, 'success');

                // Sayfayı yenile
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Engelleme işlemi hatası:', error);
            showNotification('İşlem sırasında hata oluştu.', 'error');
        }
    }

    // BASİT MESAJ SİSTEMİ
    async handleSimpleMessage(button) {
        if (!this.currentUserId) {
            showNotification('Mesaj göndermek için giriş yapmalısınız.', 'error');
            return;
        }

        if (this.profileData.isBlockingMe || this.profileData.isBlockedByMe) {
            showNotification('Engellenmiş kullanıcılara mesaj gönderemezsiniz.', 'error');
            return;
        }

        const targetId = button.dataset.targetId || this.profileData.userId;
        const targetUsername = button.dataset.targetUsername || this.profileData.username;

        // Basit mesaj modalını aç
        this.openSimpleMessageModal(targetId, targetUsername);
    }

    openSimpleMessageModal(targetId, targetUsername) {
        // Modal elementlerini al veya oluştur
        let modal = document.getElementById('simple-message-modal');

        if (!modal) {
            // Modal yoksa oluştur
            modal = this.createSimpleMessageModal();
            document.body.appendChild(modal);
        }

        const usernameSpan = document.getElementById('simple-modal-username');
        const messageInput = document.getElementById('simple-message-input');
        const fileInput = document.getElementById('simple-modal-file-input');

        if (!modal || !usernameSpan) {
            console.error('Basit mesaj modalı elementleri bulunamadı');
            return;
        }

        // Modal içeriğini ayarla
        usernameSpan.textContent = targetUsername;
        if (messageInput) messageInput.value = '';

        // Dosya seçim handler'ını ayarla
        if (fileInput) {
            fileInput.onchange = (e) => this.handleSimpleModalFileSelect(e);
        }

        // Modalı göster
        modal.style.display = 'block';
        modal.classList.add('show');

        // Inputa odaklan
        setTimeout(() => {
            if (messageInput) messageInput.focus();
        }, 300);

            // Modal kapatma event'lerini ayarla
            this.setupSimpleMessageModalEvents(modal, targetId);
    }

    createSimpleMessageModal() {
        const modalHTML = `
        <div id="simple-message-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
        <h3>📨 Mesaj Gönder</h3>
        <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">
        <p><strong>Kullanıcı:</strong> <span id="simple-modal-username"></span></p>

        <div style="margin: 15px 0;">
        <label for="simple-message-input">Mesajınız:</label>
        <textarea
        id="simple-message-input"
        placeholder="Mesajınızı buraya yazın..."
        rows="4"
        style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; resize: vertical;"
        ></textarea>
        </div>

        <div style="margin: 15px 0;">
        <label for="simple-modal-file-input">Dosya Ekle (opsiyonel):</label>
        <input
        type="file"
        id="simple-modal-file-input"
        style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;"
        accept="image/*,.pdf,.doc,.docx"
        >

        <div id="simple-modal-file-info" style="display: none; margin-top: 8px; padding: 8px; background: var(--fixed-bg); border-radius: 4px;">
        <span>Seçilen dosya: <strong id="simple-modal-file-name"></strong></span>
        <button type="button" id="clear-simple-modal-file" class="btn-danger btn-sm" style="margin-left: 10px;">Kaldır</button>
        </div>
        </div>
        </div>
        <div class="modal-footer">
        <button type="button" class="btn-danger" onclick="this.closest('.modal').style.display='none'">İptal</button>
        <button type="button" id="send-simple-message" class="btn-success">Gönder</button>
        </div>
        </div>
        </div>
        `;

        const template = document.createElement('template');
        template.innerHTML = modalHTML.trim();
        return template.content.firstChild;
    }

    setupSimpleMessageModalEvents(modal, targetId) {
        const closeBtn = modal.querySelector('.modal-close');
        const cancelBtn = modal.querySelector('.btn-danger');

        const closeModal = () => {
            modal.style.display = 'none';
            modal.classList.remove('show');
        };

        if (closeBtn) closeBtn.onclick = closeModal;
        if (cancelBtn) cancelBtn.onclick = closeModal;

        // ESC tuşu ile kapatma
        const escHandler = (e) => {
            if (e.key === 'Escape') closeModal();
        };

            const keyHandler = (e) => escHandler(e);
            document.addEventListener('keydown', keyHandler);

            // Background tıklama ile kapatma
            modal.onclick = (e) => {
                if (e.target === modal) closeModal();
            };

                // Modal kapandığında event listener'ları temizle
                const originalDisplay = modal.style.display;
                modal.addEventListener('transitionend', function handler(e) {
                    if (modal.style.display === 'none') {
                        document.removeEventListener('keydown', keyHandler);
                        modal.removeEventListener('transitionend', handler);
                    }
                });

                modal.dataset.targetId = targetId;
    }

    async handleSimpleModalFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Dosya boyutu kontrolü (2MB)
        if (file.size > 2 * 1024 * 1024) {
            showNotification('Dosya boyutu 2MB\'dan küçük olmalıdır.', 'error');
            event.target.value = '';
            return;
        }

        // Dosya bilgisini göster
        const fileInfo = document.getElementById('simple-modal-file-info');
        const fileName = document.getElementById('simple-modal-file-name');

        if (fileInfo && fileName) {
            fileInfo.style.display = 'block';
            fileName.textContent = `${file.name} (${this.formatFileSize(file.size)})`;
        }

        showNotification(`"${file.name}" dosyası seçildi.`, 'success');
    }

    clearSimpleModalFile() {
        const fileInput = document.getElementById('simple-modal-file-input');
        const fileInfo = document.getElementById('simple-modal-file-info');

        if (fileInput) fileInput.value = '';
        if (fileInfo) fileInfo.style.display = 'none';

        showNotification('Dosya seçimi kaldırıldı.', 'info');
    }

    async sendSimpleMessage() {
        const modal = document.getElementById('simple-message-modal');
        const targetId = modal?.dataset.targetId;
        const messageInput = document.getElementById('simple-message-input');
        const fileInput = document.getElementById('simple-modal-file-input');

        if (!targetId) {
            showNotification('Hedef kullanıcı bulunamadı.', 'error');
            return;
        }

        const content = messageInput?.value.trim() || '';
        const file = fileInput?.files[0];

        if (!content && !file) {
            showNotification('Mesaj veya dosya giriniz.', 'error');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('receiver_id', targetId);
            formData.append('content', content);

            if (file) {
                formData.append('file', file);
            }

            const response = await fetch(`${SITE_BASE_URL}core/send_message.php`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Mesaj gönderildi!', 'success');

                // Modalı kapat ve temizle
                modal.style.display = 'none';
                modal.classList.remove('show');
                if (messageInput) messageInput.value = '';
                this.clearSimpleModalFile();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Mesaj gönderme hatası:', error);
            showNotification('Mesaj gönderilirken hata oluştu.', 'error');
        }
    }

    // OYUN DAVETİ SİSTEMİ
    async handleGameChallenge(button) {
        if (!this.currentUserId) {
            showNotification('Oyun daveti göndermek için giriş yapmalısınız.', 'error');
            return;
        }

        if (this.profileData.isBlockingMe || this.profileData.isBlockedByMe) {
            showNotification('Engellenmiş kullanıcılara oyun daveti gönderemezsiniz.', 'error');
            return;
        }

        if (!this.profileData.isOnline) {
            showNotification('Bu kullanıcı şu anda çevrimdışı. Sadece çevrimiçi kullanıcılara davet gönderebilirsiniz.', 'warning');
            return;
        }

        const targetId = button.dataset.targetId || this.profileData.userId;
        const gameType = button.dataset.gameType || 'classic';

        try {
            const response = await fetch(`${SITE_BASE_URL}games/send_challenge.php`, {
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
            console.error('Oyun daveti hatası:', error);
            showNotification('Davet gönderilirken hata oluştu.', 'error');
        }
    }

    // YENİ ÇİZİM YÖNETİM METODLARI
    async toggleDrawingVisibility(drawingId, newVisibility) {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/update_drawing_visibility.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `drawing_id=${drawingId}&is_visible=${newVisibility ? 1 : 0}`
            });

            const result = await response.json();

            if (result.success) {
                showNotification(`Çizim ${newVisibility ? 'görünür' : 'gizli'} yapıldı.`, 'success');
                await this.loadUserDrawings();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Çizim görünürlük değiştirme hatası:', error);
            showNotification('İşlem sırasında hata oluştu.', 'error');
        }
    }

    async toggleDrawingComments(drawingId, allowComments) {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/update_drawing_comments.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `drawing_id=${drawingId}&comments_allowed=${allowComments ? 1 : 0}`
            });

            const result = await response.json();

            if (result.success) {
                showNotification(`Yorumlar ${allowComments ? 'açıldı' : 'kapatıldı'}.`, 'success');
                await this.loadUserDrawings();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Çizim yorum ayarı değiştirme hatası:', error);
            showNotification('İşlem sırasında hata oluştu.', 'error');
        }
    }

    async deleteDrawing(drawingId) {
        if (!await showConfirm('Çizimi Sil', 'Bu çizimi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
            return;
        }

        try {
            const response = await fetch(`${SITE_BASE_URL}core/delete_drawing.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `drawing_id=${drawingId}`
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Çizim silindi.', 'success');
                await this.loadUserDrawings();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Çizim silme hatası:', error);
            showNotification('Çizim silinirken hata oluştu.', 'error');
        }
    }

    // YENİ PANO YORUM SİSTEMİ
    async loadBoardComments() {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/fetch_comments.php?type=profile&id=${this.profileData.userId}`);
            const result = await response.json();

            if (result.success) {
                this.displayBoardComments(result.comments);
            }
        } catch (error) {
            console.error('Pano yorumları yüklenirken hata:', error);
        }
    }

    displayBoardComments(comments) {
        const container = document.getElementById('board-comments-list');
        if (!container) return;

        if (comments && comments.length > 0) {
            container.innerHTML = comments.map(comment => this.createCommentElement(comment)).join('');
        } else {
            container.innerHTML = '<p style="text-align: center; opacity: 0.7;">Henüz pano mesajı yok. İlk yorumu siz yapın!</p>';
        }
    }

    createCommentElement(comment) {
        const isOwner = comment.user_id === this.currentUserId;
        const canDelete = comment.can_delete || isOwner || this.profileData.isProfileOwner;

        return `
        <div class="comment-item" style="padding: 12px; border-bottom: 1px solid var(--border-color);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <img src="${Utils.formatProfilePicture(comment.profile_picture)}" alt="Profil"
        style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
        <div>
        <strong>
        <a href="/${comment.username}/" style="color: var(--accent-color); text-decoration: none;">
        ${comment.username}
        </a>
        </strong>
        <div style="font-size: 0.8em; opacity: 0.7;">
        ${new Date(comment.created_at).toLocaleString('tr-TR')}
        </div>
        </div>
        </div>
        <div style="white-space: pre-wrap; margin: 0; padding: 12px; background: var(--fixed-bg); border-radius: 8px; font-size: 0.95em;">
        ${comment.content}
        </div>
        ${canDelete ? `
            <div style="text-align: right; margin-top: 8px;">
            <button class="btn-danger btn-sm" onclick="profileSystem.deleteComment(${comment.id})">
            🗑️ Sil
            </button>
            </div>
            ` : ''}
            </div>
            `;
    }

    async handleBoardComment() {
        if (!this.currentUserId) {
            showNotification('Pano mesajı göndermek için giriş yapmalısınız.', 'error');
            return;
        }

        if (!this.profileData.canViewContent) {
            showNotification('Bu profilin panosuna mesaj gönderme yetkiniz yok.', 'error');
            return;
        }

        const commentInput = document.getElementById('boardCommentInput');
        const content = commentInput?.value.trim();

        if (!content) {
            showNotification('Lütfen mesajınızı yazın.', 'error');
            return;
        }

        try {
            let result;

            // Real-time bağlantı varsa real-time kullan
            if (this.isAblyConnected) {
                result = await this.postProfileCommentRealTime(content);
            } else {
                result = await this.postProfileCommentHTTP(content);
            }

            if (result.success) {
                showNotification('Mesajınız gönderildi!', 'success');
                if (commentInput) commentInput.value = '';
                this.clearBoardFile();

                // Real-time eklenmişse HTTP'den yenilemeye gerek yok
                if (!this.isAblyConnected) {
                    await this.loadBoardComments();
                }
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Pano mesajı gönderme hatası:', error);
            showNotification('Mesaj gönderilirken hata oluştu.', 'error');
        }
    }

    // YARDIMCI METODLAR
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    async deleteComment(commentId) {
        if (!await showConfirm('Yorumu Sil', 'Bu yorumu silmek istediğinizden emin misiniz?')) {
            return;
        }

        try {
            const response = await fetch(`${SITE_BASE_URL}core/delete_comment.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `comment_id=${commentId}`
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Yorum silindi.', 'success');
                await this.loadBoardComments();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Yorum silme hatası:', error);
            showNotification('Yorum silinirken hata oluştu.', 'error');
        }
    }

    async handleSocialLinkRemove(button) {
        const platformId = button.dataset.platformId;

        if (!platformId) {
            console.error('Platform ID bulunamadı');
            return;
        }

        if (!await showConfirm('Bağlantıyı Kaldır', 'Bu sosyal medya bağlantısını kaldırmak istediğinizden emin misiniz?')) {
            return;
        }

        try {
            const response = await fetch(SITE_BASE_URL + 'core/profile_social_links.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=remove&platform_id=${platformId}`
            });

            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                this.loadSocialLinks();
            }
        } catch (error) {
            console.error('Bağlantı kaldırma hatası:', error);
            showNotification('Bağlantı kaldırılırken hata oluştu.', 'error');
        }
    }

    async handleRequestAction(button) {
        const requesterId = button.dataset.requesterId;
        const action = button.dataset.action;

        if (!requesterId || !action) {
            console.error('Requester ID veya action bulunamadı');
            return;
        }

        try {
            const response = await fetch(SITE_BASE_URL + 'core/manage_follow_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `requester_id=${requesterId}&action=${action}`
            });

            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                this.fetchFollowRequests();
            }
        } catch (error) {
            console.error('İstek yönetim hatası:', error);
            showNotification('İstek yönetilirken hata oluştu.', 'error');
        }
    }
	
	    async loadFloodSets() {
        if (!this.profileData.canViewContent) return;
        
        try {
            const response = await fetch(`${SITE_BASE_URL}core/get_user_flood_sets.php?user_id=${this.profileData.userId}`);
            const result = await response.json();
            
            if (result.success) {
                this.displayFloodSets(result.sets);
            }
        } catch (error) {
            console.error('Flood set\'leri yüklenemedi:', error);
        }
    }
    
    displayFloodSets(sets) {
        const container = document.getElementById('flood-sets-container');
        if (!container || !sets || sets.length === 0) {
            if (container) {
                container.innerHTML = '<p style="text-align: center; opacity: 0.7;">Henüz flood set\'i bulunmuyor.</p>';
            }
            return;
        }
        
        container.innerHTML = '';
        
        sets.forEach(set => {
            const setCard = document.createElement('div');
            setCard.className = 'drawing-card';
            setCard.style.cursor = 'pointer';
            
            setCard.innerHTML = `
                <div style="padding: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: var(--accent-color);">${set.name}</h4>
                        <span class="badge">${set.message_count} mesaj</span>
                    </div>
                    
                    ${set.description ? `<p style="font-size: 0.9em; opacity: 0.8; margin-bottom: 10px;">${set.description}</p>` : ''}
                    
                    <div style="font-size: 0.8em; opacity: 0.7; margin-bottom: 15px;">
                        Oluşturulma: ${new Date(set.created_at).toLocaleDateString('tr-TR')}
                    </div>
                    
                    <div style="display: flex; gap: 8px;">
                        <button onclick="floodSystem.openSet(${set.id})" class="btn-sm btn-primary">
                            Aç
                        </button>
                        <button onclick="copyFloodSet(${set.id})" class="btn-sm btn-secondary">
                            Kopyala
                        </button>
                        ${this.profileData.isProfileOwner ? `
                            <button onclick="deleteFloodSet(${set.id})" class="btn-sm btn-danger">
                                Sil
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
            
            container.appendChild(setCard);
        });
    }
}

// Global profile instance'ını güncelle (mevcut kodu koru)
if (typeof profileSystem === 'undefined') {
    const profileSystem = new ProfileSystem();
}

// Yeni global fonksiyonlar ekle
window.handleProfileFollowAction = () => window.profileSystem?.handleFollowAction();
window.postProfileComment = () => window.profileSystem?.handleBoardComment();
window.fetchProfileComments = () => window.profileSystem?.loadBoardComments();

// Mevcut global fonksiyonları koru
window.handleRequestAction = async (requesterId, action) => {
    try {
        const response = await fetch(SITE_BASE_URL + 'core/manage_follow_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `requester_id=${requesterId}&action=${action}`
        });
        const result = await response.json();
        showNotification(result.message, result.success ? 'success' : 'error');
        if (result.success) profileSystem.fetchFollowRequests();
    } catch (error) {
        console.error('İstek yönetim hatası:', error);
        showNotification('İstek yönetilirken hata oluştu.', 'error');
    }
};

// Sayfa yüklendiğinde profili başlat
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        if (window.profileSystem && typeof window.profileSystem.init === 'function') {
            window.profileSystem.init();
        }
    }, 1000);
});
