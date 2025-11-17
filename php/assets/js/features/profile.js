// GELİŞMİŞ PROFİL SİSTEMİ - Ably Entegre
class ProfileSystem {
    constructor() {
        this.ably = null;
        this.profileChannel = null;
        this.isAblyConnected = false;
    }

    async init() {
        await this.initAbly();
        this.bindEvents();
        this.loadProfileContent();
    }

    // ABLY PROFİL SİSTEMİ
    async initAbly() {
        if (!window.PROFILE_DATA?.currentUserId) return;  // veya window.currentUser.id

        try {
            this.ably = new Ably.Realtime({
                authUrl: SITE_BASE_URL + 'games/ably_token.php',
                authMethod: 'GET',
                clientId: 'user_' + window.PROFILE_DATA.currentUserId
            });

            this.ably.connection.on('connected', () => {
                console.log('✅ Ably bağlı – Canlı sistemler aktif!');
                this.isConnected = true;  // veya this.isAblyConnected
                this.subscribeToAblyChannels();  // mevcut fonksiyon
            });

            this.ably.connection.on('failed', (err) => {
                console.error('Ably bağlantı hatası:', err);
                this.isConnected = false;
            });

        } catch (err) {
            console.error('Ably başlatma hatası:', err);
        }
    }

    subscribeToProfileChannels() {
        if (!this.ably || !window.PROFILE_DATA?.userId) return;

        // Profil aktivite kanalı
        this.profileChannel = this.ably.channels.get('profile-' + window.PROFILE_DATA.userId);

        // Yeni yorumları dinle
        this.profileChannel.subscribe('new_comment', (message) => {
            this.handleNewComment(message.data);
        });

        // Takip/takipçi değişiklikleri
        this.profileChannel.subscribe('follow_update', (message) => {
            this.handleFollowUpdate(message.data);
        });

        // Çizim paylaşımları
        this.profileChannel.subscribe('new_drawing', (message) => {
            this.handleNewDrawing(message.data);
        });

        console.log('👤 Ably profil kanallarına abone olundu');
    }

    // PROFİL AKTİVİTE İŞLEMLERİ
    handleNewComment(data) {
        console.log('💬 Yeni profil yorumu:', data);

        // Real-time yorum ekle
        this.addCommentToBoard(data);

        // Bildirim göster (profil sahibi değilse)
        if (data.author_id !== window.PROFILE_DATA.currentUserId) {
            showNotification(`💬 ${data.author_username} panonuza yorum yaptı: ${data.content.substring(0, 50)}...`, 'info');
        }
    }

    handleFollowUpdate(data) {
        console.log('👥 Takip güncellemesi:', data);

        // Takipçi sayısını real-time güncelle
        this.updateFollowerCount(data.follower_count);

        if (data.action === 'follow' && window.PROFILE_DATA.isProfileOwner) {
            showNotification(`🎉 ${data.follower_username} sizi takip etmeye başladı!`, 'success');
        }
    }

    handleNewDrawing(data) {
        console.log('🎨 Yeni çizim:', data);

        // Real-time çizim ekle
        this.addDrawingToProfile(data);

        if (!window.PROFILE_DATA.isProfileOwner) {
            showNotification(`🎨 ${data.author_username} yeni bir çizim paylaştı!`, 'info');
        }
    }

    // REAL-TIME YORUM GÖNDERME
    async postProfileCommentRealTime(content) {
        if (!this.isAblyConnected) {
            return this.postProfileCommentHTTP(content);
        }

        try {
            const commentData = {
                author_id: window.PROFILE_DATA.currentUserId,
                author_username: window.currentUser.username,
                profile_id: window.PROFILE_DATA.userId,
                content: content,
                timestamp: new Date().toISOString(),
                is_real_time: true
            };

            // Profil kanalına yayınla
            await this.profileChannel.publish('new_comment', commentData);

            console.log('💬 Real-time yorum gönderildi:', commentData);
            return { success: true, message: 'Yorum gönderildi' };

        } catch (error) {
            console.error('Real-time yorum gönderme hatası:', error);
            return this.postProfileCommentHTTP(content);
        }
    }

    // REAL-TIME TAKİP İŞLEMLERİ
    async followUserRealTime(targetId, action = 'follow') {
        if (!this.isAblyConnected) {
            return this.followUserHTTP(targetId, action);
        }

        try {
            const followData = {
                follower_id: window.PROFILE_DATA.currentUserId,
                follower_username: window.currentUser.username,
                target_id: targetId,
                action: action,
                timestamp: new Date().toISOString()
            };

            // Hedef profil kanalına yayınla
            const targetChannel = this.ably.channels.get('profile-' + targetId);
            await targetChannel.publish('follow_update', followData);

            console.log(`👥 Real-time ${action} işlemi:`, followData);
            return { success: true, message: action === 'follow' ? 'Takip edildi' : 'Takip bırakıldı' };

        } catch (error) {
            console.error('Real-time takip işlemi hatası:', error);
            return this.followUserHTTP(targetId, action);
        }
    }

    // REAL-TIME ÇİZİM PAYLAŞIMI
    async shareDrawingRealTime(drawingData) {
        if (!this.isAblyConnected || !window.PROFILE_DATA.isProfileOwner) return;

        try {
            const shareData = {
                author_id: window.PROFILE_DATA.currentUserId,
                author_username: window.currentUser.username,
                drawing_id: drawingData.id,
                drawing_title: drawingData.title,
                preview_content: drawingData.preview,
                timestamp: new Date().toISOString()
            };

            // Takipçilere yayınla
            await this.profileChannel.publish('new_drawing', shareData);

            console.log('🎨 Real-time çizim paylaşıldı:', shareData);

        } catch (error) {
            console.error('Real-time çizim paylaşım hatası:', error);
        }
    }

    // UI GÜNCELLEME FONKSİYONLARI
    addCommentToBoard(commentData) {
        const commentsList = document.getElementById('board-comments-list');
        if (!commentsList) return;

        const commentHTML = `
        <div class="comment-item" style="border-bottom: 1px solid var(--border-color); padding: 15px 0;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <img src="${Utils.formatProfilePicture(commentData.author_picture)}" alt="Profil" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
        <div>
        <strong><a href="/${commentData.author_username}/" style="color: var(--accent-color); text-decoration: none;">${commentData.author_username}</a></strong>
        <div style="color: var(--main-text); opacity: 0.7; font-size: 0.85em;">
        ${new Date(commentData.timestamp).toLocaleString('tr-TR')}
        <span style="color: var(--accent-color); margin-left: 8px;">🔴 Yeni</span>
        </div>
        </div>
        </div>
        <div style="white-space: pre-wrap; margin: 0; padding: 12px; background: var(--fixed-bg); border-radius: 8px; font-size: 0.95em;">
        ${commentData.content}
        </div>
        </div>
        `;

        // En üste ekle
        if (commentsList.children.length > 0) {
            commentsList.insertAdjacentHTML('afterbegin', commentHTML);
        } else {
            commentsList.innerHTML = commentHTML;
        }

        // Yeni yorum animasyonu
        const newComment = commentsList.firstElementChild;
        newComment.style.animation = 'highlight 2s ease-in-out';
    }

    updateFollowerCount(newCount) {
        const followerElement = document.querySelector('[data-follower-count]');
        if (followerElement) {
            followerElement.textContent = newCount.toLocaleString();

            // Sayı değişimi animasyonu
            followerElement.style.transform = 'scale(1.2)';
            setTimeout(() => {
                followerElement.style.transform = 'scale(1)';
            }, 300);
        }
    }

    addDrawingToProfile(drawingData) {
        // Çizimleri yenile
        this.fetchUserDrawings();

        // Aktivite duvarına ekle
        this.addActivity({
            activity_type: 'drawing',
            target_id: drawingData.drawing_id,
            created_at: drawingData.timestamp,
            activity_data: JSON.stringify({
                title: drawingData.drawing_title,
                preview: drawingData.preview_content
            })
        });
    }

    addActivity(activityData) {
        const activitiesContainer = document.getElementById('user-activities');
        if (!activitiesContainer) return;

        const activityHTML = this.createActivityHTML(activityData);
        activitiesContainer.insertAdjacentHTML('afterbegin', activityHTML);
    }

    // HTTP FALLBACK FONKSİYONLARI
    async postProfileCommentHTTP(content) {
        try {
            const response = await fetch(SITE_BASE_URL + 'actions/comment_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    target_type: 'profile',
                    target_id: window.PROFILE_DATA.userId,
                    content: content
                })
            });
            return await response.json();
        } catch (error) {
            console.error('HTTP yorum gönderme hatası:', error);
            return { success: false, message: 'Yorum gönderilemedi' };
        }
    }

    async followUserHTTP(targetId, action) {
        try {
            const response = await fetch(SITE_BASE_URL + 'actions/follow_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `target_id=${targetId}&action=${action}`
            });
            return await response.json();
        } catch (error) {
            console.error('HTTP takip işlemi hatası:', error);
            return { success: false, message: 'İşlem başarısız' };
        }
    }

    // GÜNCELLENMİŞ EVENT HANDLER'LAR
    async handleFollowAction(button) {
        if (!window.PROFILE_DATA?.currentUserId) {
            showNotification('Lütfen önce oturum açın.', 'error');
            return;
        }

        const action = button.dataset.action === 'follow' ? 'follow' : 'unfollow';

        try {
            let result;
            if (this.isAblyConnected) {
                result = await this.followUserRealTime(window.PROFILE_DATA.userId, action);
            } else {
                result = await this.followUserHTTP(window.PROFILE_DATA.userId, action);
            }

            showNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                // Real-time güncelleme
                if (action === 'follow') {
                    this.updateFollowerCount(parseInt(document.querySelector('[data-follower-count]')?.textContent || 0) + 1);
                } else {
                    this.updateFollowerCount(parseInt(document.querySelector('[data-follower-count]')?.textContent || 0) - 1);
                }

                setTimeout(() => window.location.reload(), 1500);
            }
        } catch (error) {
            console.error('Takip işlemi hatası:', error);
            showNotification('İşlem sırasında hata oluştu.', 'error');
        }
    }

    async postProfileComment() {
        const inputElement = document.getElementById('boardCommentInput');
        const content = inputElement.value.trim();

        if (content === '') {
            showNotification('Lütfen panoya yazmak için bir mesaj girin.', 'error');
            return;
        }

        try {
            let result;
            if (this.isAblyConnected) {
                result = await this.postProfileCommentRealTime(content);
            } else {
                result = await this.postProfileCommentHTTP(content);
            }

            showNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                inputElement.value = '';
                this.clearBoardFile();

                // Real-time eklenmişse HTTP'den yenilemeye gerek yok
                if (!this.isAblyConnected) {
                    this.fetchProfileComments();
                }
            }
        } catch (error) {
            console.error('Yorum gönderme hatası:', error);
            showNotification('Yorum gönderilirken hata oluştu.', 'error');
        }
    }

    bindEvents() {
        // Takip butonu
        const followBtn = document.getElementById('followButton');
        if (followBtn) {
            followBtn.addEventListener('click', () => this.handleFollowAction(followBtn));
        }

        // Engelleme butonu
        const blockBtn = document.getElementById('blockButton');
        if (blockBtn) {
            blockBtn.addEventListener('click', () => this.handleBlockAction(blockBtn));
        }

        // Takip isteği butonu
        const followRequestBtn = document.getElementById('followRequestBtn');
        if (followRequestBtn) {
            followRequestBtn.addEventListener('click', () => this.handleFollowAction(followRequestBtn));
        }

        // Profil resmi yükleme
        const profilePicForm = document.getElementById('profile-picture-form');
        if (profilePicForm) {
            profilePicForm.addEventListener('submit', (e) => this.handleProfilePictureUpload(e));
        }

        // Kullanıcı adı güncelleme
        const usernameForm = document.getElementById('username-update-form');
        if (usernameForm) {
            usernameForm.addEventListener('submit', (e) => this.handleUsernameUpdate(e));
            this.initUsernamePreview();
        }

        // Sosyal medya formu
        const socialLinkForm = document.getElementById('social-link-form');
        if (socialLinkForm) {
            socialLinkForm.addEventListener('submit', (e) => this.handleSocialLinkAdd(e));
        }

        // Yorum gönderme butonu
        const postCommentBtn = document.getElementById('postCommentBtn');
        if (postCommentBtn) {
            postCommentBtn.addEventListener('click', () => this.postProfileComment());
        }

        // Dosya yükleme butonu
        const boardFileInput = document.getElementById('boardFileInput');
        if (boardFileInput) {
            boardFileInput.addEventListener('change', (e) => this.handleBoardFileSelect(e));
        }
    }

    initUsernamePreview() {
        const usernameInput = document.getElementById('new_username');
        if (usernameInput) {
            usernameInput.addEventListener('input', (e) => {
                const originalValue = e.target.value;
                const formattedValue = Utils.formatUsername(originalValue);

                if (formattedValue !== originalValue && originalValue.length > 0) {
                    document.getElementById('username-preview').style.display = 'block';
                    document.getElementById('preview-text').textContent = formattedValue;
                } else {
                    document.getElementById('username-preview').style.display = 'none';
                }
            });
        }
    }

    async handleFollowAction(button) {
        if (!window.PROFILE_DATA?.currentUserId) {
            showNotification('Lütfen önce oturum açın.', 'error');
            return;
        }

        const action = button.dataset.action === 'follow' ? 'follow' : 'unfollow';

        try {
            const response = await fetch(SITE_BASE_URL + 'actions/follow_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `target_id=${window.PROFILE_DATA.userId}&action=${action}`
            });
            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');
            if (result.success) setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            console.error('Takip işlemi hatası:', error);
            showNotification('İşlem sırasında hata oluştu.', 'error');
        }
    }

    async handleBlockAction(button) {
        if (!window.PROFILE_DATA?.currentUserId) {
            showNotification('Lütfen önce oturum açın.', 'error');
            return;
        }

        const isBlocking = button.textContent.includes('Engellemeyi Kaldır');
        const action = isBlocking ? 'unblock' : 'block';

        const confirmed = await showConfirm(
            'Engelleme İşlemi',
            `Bu kullanıcıyı gerçekten ${action === 'block' ? 'engellemek' : 'engellemeyi kaldırmak'} istiyor musunuz?`
        );

        if (confirmed) {
            try {
                const response = await fetch(SITE_BASE_URL + 'actions/block_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `target_id=${window.PROFILE_DATA.userId}&action=${action}`
                });
                const result = await response.json();
                showNotification(result.message, result.success ? 'success' : 'error');
                if (result.success) setTimeout(() => window.location.reload(), 4000);
            } catch (error) {
                console.error('Engelleme işlemi hatası:', error);
                showNotification('İşlem sırasında hata oluştu.', 'error');
            }
        }
    }

    async handleProfilePictureUpload(e) {
        e.preventDefault();
        const fileInput = document.getElementById('profile-picture-input');
        const file = fileInput.files[0];

        if (!file) {
            showNotification('Lütfen bir resim seçin.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('profile_picture', file);

        try {
            const response = await fetch(SITE_BASE_URL + 'core/upload_profile_picture.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch (error) {
            console.error('Profil resmi yükleme hatası:', error);
            showNotification('Yükleme sırasında hata oluştu.', 'error');
        }
    }

    async handleUsernameUpdate(e) {
        e.preventDefault();
        let newUsername = document.getElementById('new_username').value.trim();
        newUsername = Utils.formatUsername(newUsername);

        if (newUsername.length < 3) {
            showNotification('Kullanıcı adı en az 3 karakter olmalıdır.', 'error');
            return;
        }

        if (newUsername.length > 20) {
            showNotification('Kullanıcı adı en fazla 20 karakter olabilir.', 'error');
            return;
        }

        const usernameRegex = /^[a-zA-Z0-9_-]+$/;
        if (!usernameRegex.test(newUsername)) {
            showNotification('Kullanıcı adı sadece harf, sayı, alt çizgi (_) ve tire (-) içerebilir.', 'error');
            return;
        }

        const confirmed = await showConfirm(
            'Kullanıcı Adını Değiştir',
            `Kullanıcı adınızı "${newUsername}" olarak değiştirmek istediğinizden emin misiniz?<br><br>
            • Profil URL'niz değişecek: <strong>/${newUsername}/</strong><br>
            • Eski bağlantılar çalışmayacak<br>
            • Bu işlem geri alınamaz`
        );

        if (confirmed) {
            try {
                const response = await fetch(SITE_BASE_URL + 'core/update_username.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `new_username=${encodeURIComponent(newUsername)}`
                });

                const result = await response.json();
                showNotification(result.message, result.success ? 'success' : 'error');

                if (result.success) {
                    setTimeout(() => {
                        window.location.href = `/${newUsername}/`;
                    }, 2000);
                }
            } catch (error) {
                console.error('Kullanıcı adı güncelleme hatası:', error);
                showNotification('Güncelleme sırasında hata oluştu.', 'error');
            }
        }
    }

    async handleSocialLinkAdd(e) {
        e.preventDefault();
        const platformId = document.getElementById('social-platform-select').value;
        const profileUrl = document.getElementById('social-profile-url').value.trim();

        if (!platformId || !profileUrl) {
            showNotification('Lütfen platform ve URL girin.', 'error');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('platform_id', platformId);
            formData.append('profile_url', profileUrl);

            const response = await fetch(SITE_BASE_URL + 'core/profile_social_links.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                document.getElementById('social-link-form').reset();
                await this.loadSocialLinks();
            }
        } catch (error) {
            console.error('Bağlantı ekleme hatası:', error);
            showNotification('Bağlantı eklenirken hata oluştu.', 'error');
        }
    }

    async removeSocialLink(platformId) {
        const confirmed = await showConfirm(
            'Bağlantıyı Kaldır',
            'Bu sosyal medya bağlantısını kaldırmak istediğinizden emin misiniz?'
        );

        if (confirmed) {
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
    }

    handleBoardFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (file.size > 2097152) {
            showNotification('Dosya boyutu 2MB\'dan küçük olmalı.', 'error');
            return;
        }

        document.getElementById('boardFileInfo').style.display = 'block';
        document.getElementById('boardFileName').textContent = `${file.name} (${Utils.formatFileSize(file.size)})`;
    }

    clearBoardFile() {
        document.getElementById('boardFileInput').value = '';
        document.getElementById('boardFileInfo').style.display = 'none';
    }

    async postProfileComment() {
        const inputElement = document.getElementById('boardCommentInput');
        const content = inputElement.value.trim();

        if (content === '') {
            showNotification('Lütfen panoya yazmak için bir mesaj girin.', 'error');
            return;
        }

        try {
            const response = await fetch(SITE_BASE_URL + 'actions/comment_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    target_type: 'profile',
                    target_id: window.PROFILE_DATA.userId,
                    content: content
                })
            });
            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                inputElement.value = '';
                this.clearBoardFile();
                this.fetchProfileComments();
            }
        } catch (error) {
            console.error('Yorum gönderme hatası:', error);
            showNotification('Yorum gönderilirken hata oluştu.', 'error');
        }
    }

    async loadSocialLinks() {
        try {
            const response = await fetch(SITE_BASE_URL + 'core/get_user_social_links.php');
            const result = await response.json();

            const container = document.getElementById('social-links-list');
            if (result.success && result.links && result.links.length > 0) {
                container.innerHTML = result.links.map(link => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; margin-bottom: 8px; background: var(--fixed-bg);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 24px;">${link.emoji || '🔗'}</span>
                            <div>
                                <strong style="color: var(--accent-color);">${link.name || 'Bilinmeyen Platform'}</strong>
                                <div style="font-size: 0.9em; opacity: 0.8;">
                                    <a href="${link.profile_url}" target="_blank" style="color: var(--main-text);">
                                        ${link.profile_url}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <button onclick="profileSystem.removeSocialLink(${link.platform_id})" class="btn-danger btn-sm">
                            Kaldır
                        </button>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p style="opacity: 0.7; text-align: center; padding: 20px;">Henüz sosyal medya bağlantınız yok.</p>';
            }
        } catch (error) {
            console.error('Sosyal medya bağlantıları yüklenirken hata:', error);
            const container = document.getElementById('social-links-list');
            container.innerHTML = '<p style="color: #dc3545; text-align: center;">Bağlantılar yüklenirken hata oluştu.</p>';
        }
    }

    async loadPlatformOptions() {
        try {
            const response = await fetch(SITE_BASE_URL + 'core/get_social_platforms.php');
            const responseText = await response.text();

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                const fixedText = responseText
                    .replace(/âº/g, '☺')
                    .replace(/ð/g, '😀')
                    .replace(//g, '')
                    .replace(/â/g, '')
                    .replace(//g, '');
                result = JSON.parse(fixedText);
            }

            const platformSelect = document.getElementById('social-platform-select');
            if (platformSelect && result.success && result.platforms) {
                while (platformSelect.options.length > 1) {
                    platformSelect.remove(1);
                }

                result.platforms.forEach(platform => {
                    let emoji = platform['emoji'] || '🔗';
                    if (emoji.includes('?') || emoji.length > 2) {
                        emoji = Utils.getFallbackEmoji(platform['name']);
                    }

                    const option = new Option(`${emoji} ${platform["name"]}`, platform['id']);
                    platformSelect.add(option);
                });
            } else {
                this.loadFallbackPlatformOptions();
            }
        } catch (error) {
            console.error('Platform yükleme hatası:', error);
            this.loadFallbackPlatformOptions();
        }
    }

    loadFallbackPlatformOptions() {
        const platforms = [
            { id: 1, name: 'YouTube', emoji: '\u{1F4FA}' },
            { id: 2, name: 'Linktree', emoji: '\u{1F534}' },
            { id: 3, name: 'Twitter', emoji: '\u{1F426}' },
            { id: 4, name: 'Instagram', emoji: '\u{1F4F7}' },
            { id: 5, name: 'TikTok', emoji: '\u{1F3B5}' },
            { id: 6, name: 'Discord', emoji: '\u{1F4AC}' },
            { id: 7, name: 'Facebook', emoji: '\u{1F465}' },
            { id: 8, name: 'LinkedIn', emoji: '\u{1F4BC}' },
            { id: 9, name: 'GitHub', emoji: '\u{1F4BB}' },
            { id: 10, name: 'Telegram', emoji: '\u{1F916}' }
        ];

        const platformSelect = document.getElementById('social-platform-select');
        if (platformSelect) {
            platforms.forEach(platform => {
                const option = new Option(`${platform.emoji} ${platform.name}`, platform.id);
                platformSelect.add(option);
            });
        }
    }

    async fetchProfileComments() {
        const listElement = document.getElementById('board-comments-list');
        if (!listElement) return;

        listElement.innerHTML = '<p style="text-align: center; color: var(--main-text); opacity: 0.7;">Mesajlar yükleniyor...</p>';

        try {
            const response = await fetch(`SITE_BASE_URL + 'core/fetch_comments.php?type=profile&id=${window.PROFILE_DATA.userId}`);
            const result = await response.json();

            if (result.success && result.comments.length > 0) {
                listElement.innerHTML = result.comments.map(comment => {
                    let profilePicSrc = Utils.formatProfilePicture(comment.profile_picture);
                    const profilePic = `<img src="${profilePicSrc}" alt="Profil" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">`;

                    return `
                        <div class="comment-item" style="border-bottom: 1px solid var(--border-color); padding: 15px 0;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                ${profilePic}
                                <div>
                                    <strong><a href="/${comment.username}/" style="color: var(--accent-color); text-decoration: none;">${comment.username}</a></strong>
                                    <div style="color: var(--main-text); opacity: 0.7; font-size: 0.85em;">
                                        ${new Date(comment.created_at).toLocaleString('tr-TR')}
                                    </div>
                                </div>
                            </div>
                            <div style="white-space: pre-wrap; margin: 0; padding: 12px; background: var(--fixed-bg); border-radius: 8px; font-size: 0.95em;">
                                ${comment.content}
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                listElement.innerHTML = `
                    <div style="text-align: center; padding: 30px; color: var(--main-text);">
                        <div style="font-size: 48px; margin-bottom: 15px;">💬</div>
                        <p style="margin-bottom: 15px; opacity: 0.8;">Panoda henüz mesaj yok...</p>
                        <p style="opacity: 0.6; font-size: 0.9em;">İlk mesajı yazmak ister misin? ✨</p>
                    </div>
                `;
            }
        } catch (error) {
            listElement.innerHTML = '<p style="text-align: center; color: #dc3545;">Pano mesajları yüklenirken hata oluştu.</p>';
        }
    }

    async fetchUserDrawings() {
        const listElement = document.getElementById('user-drawing-list');
        const featuredElement = document.getElementById('featured-drawing-content');

        if (!listElement) return;

        listElement.innerHTML = '<p style="text-align: center; color: var(--main-text); opacity: 0.7;">Çizimler yükleniyor...</p>';

        try {
            const response = await fetch(SITE_BASE_URL + `core/fetch_user_drawings.php?user_id=${window.PROFILE_DATA.userId}`);
            const result = await response.json();

            if (result.success && Object.keys(result.categorized_drawings).length > 0) {
                listElement.innerHTML = '';

                let featuredDrawing = result.featured_drawing;
                if (!featuredDrawing) {
                    const allDrawings = Object.values(result.categorized_drawings).flat();
                    if (allDrawings.length > 0) {
                        allDrawings.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
                        featuredDrawing = allDrawings[0];
                    }
                }

                if (featuredDrawing && typeof window.createDrawingCard === 'function') {
                    featuredElement.innerHTML = '';
                    const card = window.createDrawingCard(featuredDrawing);
                    featuredElement.appendChild(card);
                }

                for (const category in result.categorized_drawings) {
                    const categoryHeader = document.createElement('h3');
                    categoryHeader.textContent = `📁 ${category}`;
                    categoryHeader.style.marginTop = '25px';
                    categoryHeader.style.marginBottom = '15px';
                    categoryHeader.style.color = 'var(--accent-color)';
                    categoryHeader.style.paddingBottom = '8px';
                    categoryHeader.style.borderBottom = '2px solid var(--border-color)';
                    listElement.appendChild(categoryHeader);

                    const drawingContainer = document.createElement('div');
                    drawingContainer.className = 'drawings-grid';
                    drawingContainer.style.marginBottom = '30px';

                    result.categorized_drawings[category].forEach(drawing => {
                        if (typeof window.createDrawingCard === 'function') {
                            const card = window.createDrawingCard(drawing);
                            drawingContainer.appendChild(card);
                        }
                    });
                    listElement.appendChild(drawingContainer);
                }
            } else {
                listElement.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--main-text);">
                        <div style="font-size: 48px; margin-bottom: 15px;">🎨</div>
                        <p style="margin-bottom: 15px; opacity: 0.8;">Bu çizerin henüz kayıtlı çizimi bulunmamaktadır.</p>
                        <p style="opacity: 0.6; font-size: 0.9em;">İlk çizimi sen yapmak ister misin? ✨</p>
                    </div>
                `;
            }
        } catch (error) {
            listElement.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc3545;">
                    <p>❌ Çizimler yüklenirken hata oluştu.</p>
                    <p style="font-size: 0.9em; opacity: 0.8;">Lütfen sayfayı yenileyin veya daha sonra tekrar deneyin.</p>
                </div>
            `;
        }
    }

    async fetchFollowRequests() {
        const listElement = document.getElementById('follow-requests-list');
        if (!listElement) return;

        listElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">İstekler yükleniyor...</p>';

        try {
            const response = await fetch(SITE_BASE_URL + 'core/fetch_follow_requests.php');
            const result = await response.json();

            if (result.success && result.requests.length > 0) {
                listElement.innerHTML = result.requests.map(request => {
                    let profilePicSrc = Utils.formatProfilePicture(request.requester_picture);
                    const profilePic = `<img src="${profilePicSrc}" alt="Profil" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">`;

                    return `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--border-color);">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                ${profilePic}
                                <div>
                                    <a href="/${request.requester_username}/" style="color: var(--accent-color); font-weight: 500; text-decoration: none;">
                                        ${request.requester_username}
                                    </a>
                                    <div style="color: var(--main-text); opacity: 0.7; font-size: 0.85em;">
                                        ${new Date(request.requested_at).toLocaleString('tr-TR')}
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="handleRequestAction(${request.requester_id}, 'approve')" class="btn-success" style="padding: 6px 12px; font-size: 0.85em;">✅ Onayla</button>
                                <button onclick="handleRequestAction(${request.requester_id}, 'reject')" class="btn-danger" style="padding: 6px 12px; font-size: 0.85em;">❌ Reddet</button>
                            </div>
                        </div>
                    `;
                }).join('');
            } else if (result.success) {
                listElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">Bekleyen takip isteği bulunmamaktadır.</p>';
            } else {
                listElement.innerHTML = `<p style="text-align: center; color: #dc3545;">❌ Hata: ${result.message}</p>`;
            }
        } catch (error) {
            listElement.innerHTML = '<p style="text-align: center; color: #dc3545;">Sunucu ile iletişim hatası.</p>';
        }
    }

    async loadUserActivities() {
        try {
            const response = await fetch(SITE_BASE_URL + `core/get_user_activities.php?user_id=${window.PROFILE_DATA.userId}`);
            const result = await response.json();

            const container = document.getElementById('user-activities');
            if (result.success && result.activities.length > 0) {
                container.innerHTML = result.activities.map(activity => {
                    return this.createActivityHTML(activity);
                }).join('');
            } else {
                container.innerHTML = '<p style="text-align: center; opacity: 0.7;">Henüz aktivite bulunmuyor.</p>';
            }
        } catch (error) {
            console.error('Aktiviteler yüklenirken hata:', error);
            document.getElementById('user-activities').innerHTML = '<p style="text-align: center; color: #dc3545;">Aktiviteler yüklenirken hata oluştu.</p>';
        }
    }

    createActivityHTML(activity) {
        const baseUrl = SITE_BASE_URL;
        let html = '';

        switch (activity.activity_type) {
            case 'drawing':
                html = `
                    <div class="activity-item" style="border-left: 4px solid #4CAF50; padding: 10px 15px; margin-bottom: 10px; background: var(--fixed-bg); border-radius: 0 8px 8px 0;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 20px;">🎨</span>
                            <div>
                                <strong>Yeni bir çizim paylaştı</strong>
                                <div style="font-size: 0.9em; opacity: 0.8; margin-top: 4px;">
                                    <a href="${baseUrl}core/drawing.php?id=${activity.target_id}" style="color: var(--accent-color);">
                                        Çizim #${activity.target_id}'i görüntüle
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div style="font-size: 0.8em; opacity: 0.6; margin-top: 5px;">
                            ${new Date(activity.created_at).toLocaleString('tr-TR')}
                        </div>
                    </div>
                `;
                break;

            case 'game':
                const gameData = JSON.parse(activity.activity_data || '{}');
                html = `
                    <div class="activity-item" style="border-left: 4px solid #2196F3; padding: 10px 15px; margin-bottom: 10px; background: var(--fixed-bg); border-radius: 0 8px 8px 0;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 20px;">🎮</span>
                            <div>
                                <strong>${gameData.opponent} ile ${gameData.game_type} oynadı</strong>
                                <div style="font-size: 0.9em; opacity: 0.8; margin-top: 4px;">
                                    Sonuç: <strong style="color: ${gameData.result === 'win' ? '#4CAF50' : gameData.result === 'loss' ? '#f44336' : '#ff9800'}">
                                    ${gameData.result === 'win' ? 'Kazandı' : gameData.result === 'loss' ? 'Kaybetti' : 'Berabere'}
                                    </strong>
                                </div>
                            </div>
                        </div>
                        <div style="font-size: 0.8em; opacity: 0.6; margin-top: 5px;">
                            ${new Date(activity.created_at).toLocaleString('tr-TR')}
                        </div>
                    </div>
                `;
                break;

            case 'message':
                const messageData = JSON.parse(activity.activity_data || '{}');
                if (window.PROFILE_DATA.canViewContent) {
                    html = `
                        <div class="activity-item" style="border-left: 4px solid #FF9800; padding: 10px 15px; margin-bottom: 10px; background: var(--fixed-bg); border-radius: 0 8px 8px 0;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 20px;">💬</span>
                                <div>
                                    <strong><a href="${baseUrl}/${messageData.target_username}/" style="color: var(--accent-color);">${messageData.target_username}</a> panosuna yazdı</strong>
                                    <div style="font-size: 0.9em; opacity: 0.8; margin-top: 4px; background: var(--card-bg); padding: 8px; border-radius: 4px;">
                                        ${messageData.message_content}
                                    </div>
                                </div>
                            </div>
                            <div style="font-size: 0.8em; opacity: 0.6; margin-top: 5px;">
                                ${new Date(activity.created_at).toLocaleString('tr-TR')}
                            </div>
                        </div>
                    `;
                }
                break;

            default:
                html = `
                    <div class="activity-item" style="padding: 10px 15px; margin-bottom: 10px; background: var(--fixed-bg); border-radius: 8px;">
                        <div style="font-size: 0.8em; opacity: 0.6;">
                            ${new Date(activity.created_at).toLocaleString('tr-TR')}
                        </div>
                        <div>Bilinmeyen aktivite</div>
                    </div>
                `;
        }

        return html;
    }

    loadProfileContent() {
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
    }

    /**
     * Kullanıcı çizimlerini getirir
     */
    async fetchUserDrawings() {
        const listElement = document.getElementById('user-drawing-list');
        const featuredElement = document.getElementById('featured-drawing-content');

        if (!listElement) return;

        listElement.innerHTML = '<p style="text-align: center; color: var(--main-text); opacity: 0.7;">Çizimler yükleniyor...</p>';

        try {
            const response = await fetch(`${SITE_BASE_URL}core/fetch_user_drawings.php?user_id=${window.PROFILE_DATA.userId}`);
            const result = await response.json();

            if (result.success && Object.keys(result.categorized_drawings).length > 0) {
                listElement.innerHTML = '';

                let featuredDrawing = result.featured_drawing;
                if (!featuredDrawing) {
                    const allDrawings = Object.values(result.categorized_drawings).flat();
                    if (allDrawings.length > 0) {
                        allDrawings.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
                        featuredDrawing = allDrawings[0];
                    }
                }

                if (featuredDrawing && typeof window.createDrawingCard === 'function') {
                    featuredElement.innerHTML = '';
                    const card = window.createDrawingCard(featuredDrawing);
                    featuredElement.appendChild(card);
                }

                for (const category in result.categorized_drawings) {
                    const categoryHeader = document.createElement('h3');
                    categoryHeader.textContent = `📁 ${category}`;
                    categoryHeader.style.marginTop = '25px';
                    categoryHeader.style.marginBottom = '15px';
                    categoryHeader.style.color = 'var(--accent-color)';
                    categoryHeader.style.paddingBottom = '8px';
                    categoryHeader.style.borderBottom = '2px solid var(--border-color)';
                    listElement.appendChild(categoryHeader);

                    const drawingContainer = document.createElement('div');
                    drawingContainer.className = 'drawings-grid';
                    drawingContainer.style.marginBottom = '30px';

                    result.categorized_drawings[category].forEach(drawing => {
                        if (typeof window.createDrawingCard === 'function') {
                            const card = window.createDrawingCard(drawing);
                            drawingContainer.appendChild(card);
                        }
                    });
                    listElement.appendChild(drawingContainer);
                }
            } else {
                listElement.innerHTML = `
                <div style="text-align: center; padding: 40px; color: var(--main-text);">
                <div style="font-size: 48px; margin-bottom: 15px;">🎨</div>
                <p style="margin-bottom: 15px; opacity: 0.8;">Bu çizerin henüz kayıtlı çizimi bulunmamaktadır.</p>
                <p style="opacity: 0.6; font-size: 0.9em;">İlk çizimi sen yapmak ister misin? ✨</p>
                </div>
                `;
            }
        } catch (error) {
            listElement.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #dc3545;">
            <p>❌ Çizimler yüklenirken hata oluştu.</p>
            <p style="font-size: 0.9em; opacity: 0.8;">Lütfen sayfayı yenileyin veya daha sonra tekrar deneyin.</p>
            </div>
            `;
        }
    }

    /**
     * Takip isteklerini getirir
     */
    async fetchFollowRequests() {
        const listElement = document.getElementById('follow-requests-list');
        if (!listElement) return;

        listElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">İstekler yükleniyor...</p>';

        try {
            const response = await fetch(SITE_BASE_URL + 'core/fetch_follow_requests.php');
            const result = await response.json();

            if (result.success && result.requests.length > 0) {
                listElement.innerHTML = result.requests.map(request => {
                    let profilePicSrc = Utils.formatProfilePicture(request.requester_picture);
                    const profilePic = `<img src="${profilePicSrc}" alt="Profil" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">`;

                    return `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                    ${profilePic}
                    <div>
                    <a href="/${request.requester_username}/" style="color: var(--accent-color); font-weight: 500; text-decoration: none;">
                    ${request.requester_username}
                    </a>
                    <div style="color: var(--main-text); opacity: 0.7; font-size: 0.85em;">
                    ${new Date(request.requested_at).toLocaleString('tr-TR')}
                    </div>
                    </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                    <button onclick="handleRequestAction(${request.requester_id}, 'approve')" class="btn-success" style="padding: 6px 12px; font-size: 0.85em;">✅ Onayla</button>
                    <button onclick="handleRequestAction(${request.requester_id}, 'reject')" class="btn-danger" style="padding: 6px 12px; font-size: 0.85em;">❌ Reddet</button>
                    </div>
                    </div>
                    `;
                }).join('');
            } else if (result.success) {
                listElement.innerHTML = '<p style="text-align: center; opacity: 0.7;">Bekleyen takip isteği bulunmamaktadır.</p>';
            } else {
                listElement.innerHTML = `<p style="text-align: center; color: #dc3545;">❌ Hata: ${result.message}</p>`;
            }
        } catch (error) {
            listElement.innerHTML = '<p style="text-align: center; color: #dc3545;">Sunucu ile iletişim hatası.</p>';
        }
    }

    /**
     * Kullanıcı aktivitelerini yükler
     */
    async loadUserActivities() {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/get_user_activities.php?user_id=${window.PROFILE_DATA.userId}`);
            const result = await response.json();

            const container = document.getElementById('user-activities');
            if (result.success && result.activities.length > 0) {
                container.innerHTML = result.activities.map(activity => {
                    return this.createActivityHTML(activity);
                }).join('');
            } else {
                container.innerHTML = '<p style="text-align: center; opacity: 0.7;">Henüz aktivite bulunmuyor.</p>';
            }
        } catch (error) {
            console.error('Aktiviteler yüklenirken hata:', error);
            document.getElementById('user-activities').innerHTML = '<p style="text-align: center; color: #dc3545;">Aktiviteler yüklenirken hata oluştu.</p>';
        }
    }

    /**
     * Aktivite verisini HTML'e dönüştürür
     */
    createActivityHTML(activity) {
        const baseUrl = SITE_BASE_URL;
        let html = '';

        switch (activity.activity_type) {
            case 'drawing':
                html = `
                <div class="activity-item" style="border-left: 4px solid #4CAF50; padding: 10px 15px; margin-bottom: 10px; background: var(--fixed-bg); border-radius: 0 8px 8px 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;">🎨</span>
                <div>
                <strong>Yeni bir çizim paylaştı</strong>
                <div style="font-size: 0.9em; opacity: 0.8; margin-top: 4px;">
                <a href="${baseUrl}core/drawing.php?id=${activity.target_id}" style="color: var(--accent-color);">
                Çizim #${activity.target_id}'i görüntüle
                </a>
                </div>
                </div>
                </div>
                <div style="font-size: 0.8em; opacity: 0.6; margin-top: 5px;">
                ${new Date(activity.created_at).toLocaleString('tr-TR')}
                </div>
                </div>
                `;
                break;

            case 'game':
                const gameData = JSON.parse(activity.activity_data || '{}');
                html = `
                <div class="activity-item" style="border-left: 4px solid #2196F3; padding: 10px 15px; margin-bottom: 10px; background: var(--fixed-bg); border-radius: 0 8px 8px 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;">🎮</span>
                <div>
                <strong>${gameData.opponent} ile ${gameData.game_type} oynadı</strong>
                <div style="font-size: 0.9em; opacity: 0.8; margin-top: 4px;">
                Sonuç: <strong style="color: ${gameData.result === 'win' ? '#4CAF50' : gameData.result === 'loss' ? '#f44336' : '#ff9800'}">
                ${gameData.result === 'win' ? 'Kazandı' : gameData.result === 'loss' ? 'Kaybetti' : 'Berabere'}
                </strong>
                </div>
                </div>
                </div>
                <div style="font-size: 0.8em; opacity: 0.6; margin-top: 5px;">
                ${new Date(activity.created_at).toLocaleString('tr-TR')}
                </div>
                </div>
                `;
                break;

            case 'message':
                const messageData = JSON.parse(activity.activity_data || '{}');
                if (window.PROFILE_DATA.canViewContent) {
                    html = `
                    <div class="activity-item" style="border-left: 4px solid #FF9800; padding: 10px 15px; margin-bottom: 10px; background: var(--fixed-bg); border-radius: 0 8px 8px 0;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">💬</span>
                    <div>
                    <strong><a href="${baseUrl}/${messageData.target_username}/" style="color: var(--accent-color);">${messageData.target_username}</a> panosuna yazdı</strong>
                    <div style="font-size: 0.9em; opacity: 0.8; margin-top: 4px; background: var(--card-bg); padding: 8px; border-radius: 4px;">
                    ${messageData.message_content}
                    </div>
                    </div>
                    </div>
                    <div style="font-size: 0.8em; opacity: 0.6; margin-top: 5px;">
                    ${new Date(activity.created_at).toLocaleString('tr-TR')}
                    </div>
                    </div>
                    `;
                }
                break;

            default:
                html = `
                <div class="activity-item" style="padding: 10px 15px; margin-bottom: 10px; background: var(--fixed-bg); border-radius: 8px;">
                <div style="font-size: 0.8em; opacity: 0.6;">
                ${new Date(activity.created_at).toLocaleString('tr-TR')}
                </div>
                <div>Bilinmeyen aktivite</div>
                </div>
                `;
        }

        return html;
    }
}

// Global profile instance'ı
const profileSystem = new ProfileSystem();

// Eski fonksiyonlar için compatibility
async function handleRequestAction(requesterId, action) {
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
}

function clearBoardFile() {
    profileSystem.clearBoardFile();
}
