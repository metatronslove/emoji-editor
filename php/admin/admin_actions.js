// GENİŞLETİLMİŞ ADMIN ACTIONS - Template Sistemi Uyumlu
class AdminActions {
    constructor() {
        this.baseUrl = window.location.pathname.includes('/admin/') ? '../' : './';
    }

    // Bildirim sistemi
    showAdminNotification(message, type = 'info') {
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            // Fallback bildirim
            const notification = document.getElementById('notification');
            if (notification) {
                notification.textContent = message;
                notification.className = `notification ${type}`;
                notification.style.display = 'block';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 3000);
            } else {
                alert(`${type.toUpperCase()}: ${message}`);
            }
        }
    }

    // Gelişmiş aksiyon gönderme
    async sendAction(url, data) {
        try {
            const fullUrl = url.startsWith('http') ? url : this.baseUrl + url;

            const response = await fetch(fullUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(data)
            });

            const result = await response.json();
            this.showAdminNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                // İşlem başarılıysa ilgili içeriği yenile
                setTimeout(() => {
                    const activeTab = document.querySelector('.tab-link.active');
                    if (activeTab) {
                        loadTabContent(activeTab.dataset.tab);
                    }
                }, 1000);
            }

            return result;
        } catch (error) {
            console.error('AJAX Hatası:', error);
            this.showAdminNotification('Sunucu hatası: ' + error, 'error');
            return { success: false, message: 'Sunucu hatası' };
        }
    }

    // Duyuru oluşturma
    async createAnnouncement() {
        const content = document.getElementById('announcement-content')?.value.trim();
        const type = document.getElementById('announcement-type')?.value;

        if (!content) {
            this.showAdminNotification('Duyuru içeriği boş olamaz.', 'error');
            return;
        }

        const result = await this.sendAction(SITE_BASE_URL + 'admin/create_announcement.php', {
            content: content,
            type: type
        });

        if (result.success) {
            document.getElementById('announcement-content').value = '';
            this.loadAnnouncements();
        }
    }

    // Sosyal medya platformu ekleme
    async addSocialMediaPlatform() {
        const name = document.getElementById('sm-name')?.value.trim();
        const emoji = document.getElementById('sm-emoji')?.value.trim();
        const regex = document.getElementById('sm-regex')?.value.trim();

        if (!name || !emoji) {
            this.showAdminNotification('Platform adı ve emoji gereklidir.', 'error');
            return;
        }

        const result = await this.sendAction(SITE_BASE_URL + 'admin/add_social_platform.php', {
            name: name,
            emoji: emoji,
            regex: regex
        });

        if (result.success) {
            // Formu temizle
            document.getElementById('sm-name').value = '';
            document.getElementById('sm-emoji').value = '';
            document.getElementById('sm-regex').value = '';
            this.loadSocialMediaSettings();
        }
    }

    // Rütbe ayarlarını kaydetme
    async saveRankSettings() {
        const commentPoints = document.getElementById('rank-comment-points')?.value;
        const drawingPoints = document.getElementById('rank-drawing-points')?.value;
        const followerPoints = document.getElementById('rank-follower-points')?.value;
        const upvotePoints = document.getElementById('rank-upvote-points')?.value;

        const result = await this.sendAction(SITE_BASE_URL + 'admin/save_rank_settings.php', {
            comment_points: commentPoints,
            drawing_points: drawingPoints,
            follower_points: followerPoints,
            upvote_points: upvotePoints
        });
    }

    // Kullanıcı listesi yükleme
    async loadUserList(searchTerm = '') {
        const container = document.getElementById('user-list-container');
        if (!container) return;

        container.innerHTML = '<p>Kullanıcılar yükleniyor...</p>';

        try {
            const url = searchTerm ?
            SITE_BASE_URL + `admin/fetch_users.php?q=${encodeURIComponent(searchTerm)}` :
            SITE_BASE_URL + 'admin/fetch_users.php';

            const response = await fetch(url);
            const result = await response.json();

            if (result.success) {
                container.innerHTML = this.createUserTable(result.users);
            } else {
                container.innerHTML = `<p style="color: red;">Hata: ${result.message}</p>`;
            }
        } catch (error) {
            container.innerHTML = '<p style="color: red;">Sunucu hatası.</p>';
        }
    }

    // Kullanıcı tablosu oluşturma
    createUserTable(users) {
        if (users.length === 0) {
            return '<p>Kullanıcı bulunamadı.</p>';
        }

        let html = `
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <thead>
        <tr style="background-color: var(--fixed-bg);">
        <th style="padding: 10px; border: 1px solid var(--border-color);">ID</th>
        <th style="padding: 10px; border: 1px solid var(--border-color);">Kullanıcı Adı</th>
        <th style="padding: 10px; border: 1px solid var(--border-color);">Email</th>
        <th style="padding: 10px; border: 1px solid var(--border-color);">Rol</th>
        <th style="padding: 10px; border: 1px solid var(--border-color);">Durum</th>
        <th style="padding: 10px; border: 1px solid var(--border-color);">Çizim/Yorum</th>
        <th style="padding: 10px; border: 1px solid var(--border-color);">Kayıt Tarihi</th>
        <th style="padding: 10px; border: 1px solid var(--border-color);">Eylemler</th>
        </tr>
        </thead>
        <tbody>
        `;

        users.forEach(user => {
            const isBanned = user.is_banned == 1;
            const isMuted = user.comment_mute_until && new Date(user.comment_mute_until) > new Date();

            html += `
            <tr style="${isBanned ? 'background-color: #ffdddd;' : ''}">
            <td style="padding: 8px; border: 1px solid var(--border-color);">${user.id}</td>
            <td style="padding: 8px; border: 1px solid var(--border-color);">
            <a href="../${user.username}/" target="_blank">${user.username}</a>
            </td>
            <td style="padding: 8px; border: 1px solid var(--border-color);">${user.email}</td>
            <td style="padding: 8px; border: 1px solid var(--border-color);">${user.role}</td>
            <td style="padding: 8px; border: 1px solid var(--border-color);">
            ${isBanned ? '🚫 Banlı' : '✅ Aktif'}
            ${isMuted ? '<br>🔇 Mute' : ''}
            </td>
            <td style="padding: 8px; border: 1px solid var(--border-color);">
            ${user.drawing_count} çizim<br>
            ${user.comment_count} yorum<br>
            ${user.follower_count} takipçi
            </td>
            <td style="padding: 8px; border: 1px solid var(--border-color);">
            ${new Date(user.created_at).toLocaleDateString('tr-TR')}
            </td>
            <td style="padding: 8px; border: 1px solid var(--border-color);">
            <div style="display: flex; flex-direction: column; gap: 5px;">
            ${!isBanned ?
                `<button onclick="adminActions.moderateUser(${user.id}, 'ban')" class="btn-danger btn-sm">Banla</button>` :
                `<button onclick="adminActions.moderateUser(${user.id}, 'unban')" class="btn-success btn-sm">Banı Kaldır</button>`}

                ${!isMuted ?
                    `<button onclick="adminActions.showMuteModal(${user.id})" class="btn-warning btn-sm">Yorum Mute</button>` :
                    `<button onclick="adminActions.moderateUser(${user.id}, 'unmute')" class="btn-success btn-sm">Mute Kaldır</button>`}

                    ${window.currentUser.isAdmin ? `
                        <select onchange="adminActions.setRole(${user.id}, this.value)" style="padding: 4px; border-radius: 4px; border: 1px solid var(--border-color);">
                        <option value="user" ${user.role === 'user' ? 'selected' : ''}>Kullanıcı</option>
                        <option value="moderator" ${user.role === 'moderator' ? 'selected' : ''}>Moderatör</option>
                        </select>
                        ` : ''}
                        </div>
                        </td>
                        </tr>
                        `;
        });

        html += '</tbody></table>';
        return html;
    }

    // Kullanıcı moderasyonu
    async moderateUser(userId, action) {
        const actions = {
            'ban': 'yasaklamak',
            'unban': 'yasaklamayı kaldırmak',
            'mute': 'mute etmek',
            'unmute': 'mute etmeyi kaldırmak'
        };

        const confirmed = await this.showConfirm(
            'Kullanıcı Yönetimi',
            `Bu kullanıcıyı ${actions[action]} istediğinizden emin misiniz?`
        );

        if (confirmed) {
            try {
                const response = await fetch(SITE_BASE_URL + 'admin/moderate_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${userId}&action=${action}`
                });

                const result = await response.json();
                this.showAdminNotification(result.message, result.success ? 'success' : 'error');

                if (result.success) {
                    this.loadUserList();
                }
            } catch (error) {
                console.error('Kullanıcı moderasyon hatası:', error);
                this.showAdminNotification('İşlem sırasında hata oluştu.', 'error');
            }
        }
    }

    // Mute modalını göster
    showMuteModal(userId) {
        document.getElementById('mute-user-id').value = userId;
        document.getElementById('mute-modal').style.display = 'block';
    }

    // Mute uygula
    async applyCommentMute() {
        const userId = document.getElementById('mute-user-id').value;
        const duration = document.getElementById('mute-duration').value;

        try {
            const response = await fetch(SITE_BASE_URL + 'admin/moderate_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&action=mute&duration=${duration}`
            });

            const result = await response.json();
            this.showAdminNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                document.getElementById('mute-modal').style.display = 'none';
                this.loadUserList();
            }
        } catch (error) {
            console.error('Mute uygulama hatası:', error);
            this.showAdminNotification('Mute uygulanırken hata oluştu.', 'error');
        }
    }

    // Rol değiştirme
    async setRole(userId, newRole) {
        try {
            const response = await fetch(SITE_BASE_URL + 'admin/moderate_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&action=set_role&new_role=${newRole}`
            });

            const result = await response.json();
            this.showAdminNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                this.loadUserList();
            }
        } catch (error) {
            console.error('Rol değiştirme hatası:', error);
            this.showAdminNotification('Rol değiştirilirken hata oluştu.', 'error');
        }
    }

    // İçerik moderasyonu
    async fetchRecentContentForModeration() {
        const container = document.getElementById('content-moderation-area');
        if (!container) return;

        container.innerHTML = '<p>İçerikler yükleniyor...</p>';

        try {
            const response = await fetch(SITE_BASE_URL + 'admin/fetch_recent_content.php');
            const result = await response.json();

            if (result.success) {
                let contentHTML = '<div class="moderation-grid">';

                // Çizimleri listele
                if (result.drawings && result.drawings.length > 0) {
                    contentHTML += '<h3>🎨 Son Çizimler</h3>';
                    result.drawings.forEach(drawing => {
                        contentHTML += `
                        <div class="moderation-item" style="border: 1px solid var(--border-color); padding: 10px; margin-bottom: 10px; border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                        <strong>${drawing.author_name}</strong>
                        <small style="color: var(--main-text); opacity: 0.7;"> - ${new Date(drawing.updated_at).toLocaleString('tr-TR')}</small>
                        <div style="margin-top: 5px; font-size: 0.9em;">${drawing.content.substring(0, 100)}...</div>
                        </div>
                        <div style="display: flex; gap: 5px;">
                        <button onclick="adminActions.moderateContent(${drawing.id}, 'drawing', '${drawing.is_visible ? 'hide' : 'show'}')"
                        class="btn-${drawing.is_visible ? 'warning' : 'success'} btn-sm">
                        ${drawing.is_visible ? '❌ Gizle' : '✅ Göster'}
                        </button>
                        </div>
                        </div>
                        </div>
                        `;
                    });
                }

                // Yorumları listele
                if (result.comments && result.comments.length > 0) {
                    contentHTML += '<h3>💬 Son Yorumlar</h3>';
                    result.comments.forEach(comment => {
                        contentHTML += `
                        <div class="moderation-item" style="border: 1px solid var(--border-color); padding: 10px; margin-bottom: 10px; border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                        <strong>${comment.author_name}</strong>
                        <small style="color: var(--main-text); opacity: 0.7;"> - ${new Date(comment.created_at).toLocaleString('tr-TR')}</small>
                        <div style="margin-top: 5px; font-size: 0.9em;">${comment.content.substring(0, 100)}...</div>
                        </div>
                        <div style="display: flex; gap: 5px;">
                        <button onclick="adminActions.moderateContent(${comment.id}, 'comment', '${comment.is_visible ? 'hide' : 'show'}')"
                        class="btn-${comment.is_visible ? 'warning' : 'success'} btn-sm">
                        ${comment.is_visible ? '❌ Gizle' : '✅ Göster'}
                        </button>
                        </div>
                        </div>
                        </div>
                        `;
                    });
                }

                contentHTML += '</div>';
                container.innerHTML = contentHTML;
            } else {
                container.innerHTML = `<p style="color: red;">Hata: ${result.message}</p>`;
            }
        } catch (error) {
            container.innerHTML = '<p style="color: red;">İçerikler yüklenirken hata oluştu.</p>';
        }
    }

    // İçerik moderasyonu
    async moderateContent(contentId, contentType, action) {
        try {
            const response = await fetch(SITE_BASE_URL + 'admin/moderate_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `content_id=${contentId}&content_type=${contentType}&action=${action}`
            });

            const result = await response.json();
            this.showAdminNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                this.fetchRecentContentForModeration();
            }
        } catch (error) {
            console.error('Moderasyon hatası:', error);
            this.showAdminNotification('İşlem sırasında hata oluştu.', 'error');
        }
    }

    // Duyuruları yükle
    async loadAnnouncements() {
        try {
            const response = await fetch(SITE_BASE_URL + 'admin/fetch_announcements.php');
            const result = await response.json();

            const container = document.getElementById('announcements-list');
            if (container && result.success) {
                container.innerHTML = result.announcements.map(ann => `
                <div class="announcement-item" style="border-left: 4px solid ${this.getAnnouncementColor(ann.type)}; padding: 10px; margin-bottom: 10px; background: var(--fixed-bg);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                <strong>${this.getAnnouncementIcon(ann.type)} ${ann.content}</strong>
                <small>${new Date(ann.created_at).toLocaleString('tr-TR')}</small>
                </div>
                <button onclick="adminActions.deleteAnnouncement(${ann.id})" class="btn-danger btn-sm" style="margin-top: 5px;">Sil</button>
                </div>
                `).join('');
            } else if (container) {
                container.innerHTML = `<p style="color: red;">Hata: ${result.message}</p>`;
            }
        } catch (error) {
            console.error('Duyurular yüklenirken hata:', error);
        }
    }

    // Sosyal medya ayarlarını yükle
    async loadSocialMediaSettings() {
        try {
            const response = await fetch(SITE_BASE_URL + 'admin/fetch_social_platforms.php');
            const result = await response.json();

            const container = document.getElementById('social-media-list');
            if (container && result.success) {
                container.innerHTML = result.platforms.map(platform => `
                <div class="platform-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid var(--border-color);">
                <div>
                <span style="font-size: 20px;">${platform.emoji}</span>
                <strong>${platform.name}</strong>
                <small style="color: var(--main-text); opacity: 0.7;"> - ${platform.url_regex || 'Regex tanımlı değil'}</small>
                </div>
                <div>
                <button onclick="adminActions.togglePlatform(${platform.id}, ${platform.is_active})" class="${platform.is_active ? 'btn-warning' : 'btn-success'} btn-sm">
                ${platform.is_active ? 'Pasif Yap' : 'Aktif Yap'}
                </button>
                <button onclick="adminActions.deletePlatform(${platform.id})" class="btn-danger btn-sm">Sil</button>
                </div>
                </div>
                `).join('');
            } else if (container) {
                container.innerHTML = `<p style="color: red;">Hata: ${result.message}</p>`;
            }
        } catch (error) {
            console.error('Sosyal medya ayarları yüklenirken hata:', error);
        }
    }

    // Rütbe ayarlarını yükle
    async loadRankSettings() {
        try {
            const response = await fetch(SITE_BASE_URL + 'admin/fetch_rank_settings.php');
            const result = await response.json();

            if (result.success) {
                const commentPoints = document.getElementById('rank-comment-points');
                const drawingPoints = document.getElementById('rank-drawing-points');
                const followerPoints = document.getElementById('rank-follower-points');
                const upvotePoints = document.getElementById('rank-upvote-points');

                if (commentPoints) commentPoints.value = result.settings.comment_points || 1;
                if (drawingPoints) drawingPoints.value = result.settings.drawing_points || 2;
                if (followerPoints) followerPoints.value = result.settings.follower_points || 0.5;
                if (upvotePoints) upvotePoints.value = result.settings.upvote_points || 0.2;
            }
        } catch (error) {
            console.error('Rütbe ayarları yüklenirken hata:', error);
        }
    }

    // Rütbe hesaplama
    async calculateRanks() {
        try {
            const response = await fetch(SITE_BASE_URL + 'admin/calculate_ranks.php');
            const result = await response.json();

            const container = document.getElementById('rank-distribution');
            if (!container) return;

            if (result.success) {
                let html = `
                <div style="margin-bottom: 20px;">
                <h4>Kullanılan Puan Ayarları:</h4>
                <p>Yorum: ${result.settings_used.comment_points} puan | Çizim: ${result.settings_used.drawing_points} puan</p>
                <p>Takipçi: ${result.settings_used.follower_points} puan | Beğeni: ${result.settings_used.upvote_points} puan</p>
                </div>
                <div style="max-height: 600px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                <tr style="background-color: var(--accent-color); color: white;">
                <th style="padding: 10px; border: 1px solid var(--border-color);">Sıra</th>
                <th style="padding: 10px; border: 1px solid var(--border-color);">Kullanıcı</th>
                <th style="padding: 10px; border: 1px solid var(--border-color);">Toplam Puan</th>
                <th style="padding: 10px; border: 1px solid var(--border-color);">Çizimler</th>
                <th style="padding: 10px; border: 1px solid var(--border-color);">Yorumlar</th>
                <th style="padding: 10px; border: 1px solid var(--border-color);">Takipçiler</th>
                <th style="padding: 10px; border: 1px solid var(--border-color);">Beğeniler</th>
                </tr>
                </thead>
                <tbody>
                `;

                result.users.forEach(user => {
                    html += `
                    <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 8px; border: 1px solid var(--border-color); text-align: center; font-weight: bold;">${user.rank}</td>
                    <td style="padding: 8px; border: 1px solid var(--border-color);">
                    <a href="../${user.username}/" target="_blank">${user.username}</a>
                    </td>
                    <td style="padding: 8px; border: 1px solid var(--border-color); text-align: center; font-weight: bold; color: var(--accent-color);">
                    ${user.total_points}
                    </td>
                    <td style="padding: 8px; border: 1px solid var(--border-color); text-align: center;">
                    ${user.drawing_count} (${user.drawing_points}p)
                    </td>
                    <td style="padding: 8px; border: 1px solid var(--border-color); text-align: center;">
                    ${user.comment_count} (${user.comment_points}p)
                    </td>
                    <td style="padding: 8px; border: 1px solid var(--border-color); text-align: center;">
                    ${user.follower_count} (${user.follower_points}p)
                    </td>
                    <td style="padding: 8px; border: 1px solid var(--border-color); text-align: center;">
                    ${user.upvote_count} (${user.upvote_points}p)
                    </td>
                    </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;

                this.showAdminNotification(`✅ Rütbeler başarıyla hesaplandı! Toplam ${result.users.length} kullanıcı sıralandı.`, 'success');
            } else {
                container.innerHTML = `<p style="color: red;">Hata: ${result.message}</p>`;
                this.showAdminNotification('❌ Rütbe hesaplama başarısız.', 'error');
            }
        } catch (error) {
            console.error('Rütbe hesaplama hatası:', error);
            const container = document.getElementById('rank-distribution');
            if (container) {
                container.innerHTML = '<p style="color: red;">Rütbe hesaplanırken hata oluştu.</p>';
            }
            this.showAdminNotification('❌ Rütbe hesaplanırken hata oluştu.', 'error');
        }
    }

    // Sosyal medya platform yönetimi
    async togglePlatform(platformId, currentState) {
        const action = currentState ? 'deactivate' : 'activate';

        try {
            const response = await fetch(SITE_BASE_URL + 'admin/moderate_social_platform.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `platform_id=${platformId}&action=${action}`
            });

            const result = await response.json();
            this.showAdminNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                this.loadSocialMediaSettings();
            }
        } catch (error) {
            console.error('Platform durumu değiştirme hatası:', error);
            this.showAdminNotification('Platform durumu değiştirilirken hata oluştu.', 'error');
        }
    }

    async deletePlatform(platformId) {
        const confirmed = await this.showConfirm(
            'Platform Sil',
            'Bu sosyal medya platformunu silmek istediğinizden emin misiniz?'
        );

        if (confirmed) {
            try {
                const response = await fetch(SITE_BASE_URL + 'admin/moderate_social_platform.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `platform_id=${platformId}&action=delete`
                });

                const result = await response.json();
                this.showAdminNotification(result.message, result.success ? 'success' : 'error');

                if (result.success) {
                    this.loadSocialMediaSettings();
                }
            } catch (error) {
                console.error('Platform silme hatası:', error);
                this.showAdminNotification('Platform silinirken hata oluştu.', 'error');
            }
        }
    }

    async deleteAnnouncement(announcementId) {
        const confirmed = await this.showConfirm(
            'Duyuru Sil',
            'Bu duyuruyu silmek istediğinizden emin misiniz?'
        );

        if (confirmed) {
            try {
                const response = await fetch(SITE_BASE_URL + 'admin/delete_announcement.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `announcement_id=${announcementId}`
                });

                const result = await response.json();
                this.showAdminNotification(result.message, result.success ? 'success' : 'error');

                if (result.success) {
                    this.loadAnnouncements();
                }
            } catch (error) {
                console.error('Duyuru silme hatası:', error);
                this.showAdminNotification('Duyuru silinirken hata oluştu.', 'error');
            }
        }
    }

    // Yardımcı fonksiyonlar
    getAnnouncementColor(type) {
        const colors = {
            'info': '#2196F3',
            'warning': '#FF9800',
            'success': '#4CAF50',
            'critical': '#F44336'
        };
        return colors[type] || '#2196F3';
    }

    getAnnouncementIcon(type) {
        const icons = {
            'info': 'ℹ️',
            'warning': '⚠️',
            'success': '✅',
            'critical': '🚨'
        };
        return icons[type] || 'ℹ️';
    }

    async showConfirm(title, message) {
        if (typeof showConfirm === 'function') {
            return await showConfirm(title, message);
        } else {
            // Fallback confirm
            return confirm(`${title}\n\n${message}`);
        }
    }

    // Sekme yükleme
    loadTabContent(tabName) {
        switch(tabName) {
            case 'user-management':
                this.loadUserList();
                break;
            case 'content-moderation':
                this.fetchRecentContentForModeration();
                break;
            case 'announcements':
                this.loadAnnouncements();
                break;
            case 'social-media':
                this.loadSocialMediaSettings();
                break;
            case 'rank-system':
                this.loadRankSettings();
                break;
            case 'private-messages':
                this.loadPrivateMessages();
                break;
            case 'system-logs':
                // Sistem logları yükleme
                if (typeof loadSystemLogs === 'function') {
                    loadSystemLogs();
                }
                break;
        }
    }

    // Özel mesajları yükle (placeholder)
    async loadPrivateMessages() {
        const usersContainer = document.getElementById('pm-users-list');
        const conversationContainer = document.getElementById('pm-conversation');

        if (usersContainer) {
            usersContainer.innerHTML = '<p>Kullanıcılar yükleniyor...</p>';
        }
        if (conversationContainer) {
            conversationContainer.innerHTML = '<p>Bir konuşma seçin</p>';
        }
    }
}

// Global admin actions instance'ı
const adminActions = new AdminActions();

// Eski fonksiyonlar için compatibility
function searchUsers() {
    const searchTerm = document.getElementById('userSearch').value;
    adminActions.loadUserList(searchTerm);
}

function calculateRanks() {
    adminActions.calculateRanks();
}

function createAnnouncement() {
    adminActions.createAnnouncement();
}

function addSocialMediaPlatform() {
    adminActions.addSocialMediaPlatform();
}

function saveRankSettings() {
    adminActions.saveRankSettings();
}

function fetchRecentContentForModeration() {
    adminActions.fetchRecentContentForModeration();
}

function moderateContent(contentId, contentType, action) {
    adminActions.moderateContent(contentId, contentType, action);
}

function moderateUser(userId, action) {
    adminActions.moderateUser(userId, action);
}

function showMuteModal(userId) {
    adminActions.showMuteModal(userId);
}

function applyCommentMute() {
    adminActions.applyCommentMute();
}

function setRole(userId, newRole) {
    adminActions.setRole(userId, newRole);
}

function loadAnnouncements() {
    adminActions.loadAnnouncements();
}

function loadSocialMediaSettings() {
    adminActions.loadSocialMediaSettings();
}

function loadRankSettings() {
    adminActions.loadRankSettings();
}

function togglePlatform(platformId, currentState) {
    adminActions.togglePlatform(platformId, currentState);
}

function deletePlatform(platformId) {
    adminActions.deletePlatform(platformId);
}

function deleteAnnouncement(announcementId) {
    adminActions.deleteAnnouncement(announcementId);
}

function loadUserList(searchTerm = '') {
    adminActions.loadUserList(searchTerm);
}

// Global tab yükleme fonksiyonu
function loadTabContent(tabName) {
    adminActions.loadTabContent(tabName);
}
