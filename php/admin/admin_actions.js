// admin/admin_actions.js - GENİŞLETİLMİŞ

// Bildirim sistemi
function showAdminNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    if (!notification) return;

    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';

    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}

// Gelişmiş aksiyon gönderme
async function sendAction(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data)
        });

        const result = await response.json();
        showAdminNotification(result.message, result.success ? 'success' : 'error');

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
        showAdminNotification('Sunucu hatası: ' + error, 'error');
        return { success: false, message: 'Sunucu hatası' };
    }
}

// Duyuru oluşturma
async function createAnnouncement() {
    const content = document.getElementById('announcement-content').value.trim();
    const type = document.getElementById('announcement-type').value;

    if (!content) {
        showAdminNotification('Duyuru içeriği boş olamaz.', 'error');
        return;
    }

    const result = await sendAction('create_announcement.php', {
        content: content,
        type: type
    });

    if (result.success) {
        document.getElementById('announcement-content').value = '';
        loadAnnouncements();
    }
}

// Sosyal medya platformu ekleme
async function addSocialMediaPlatform() {
    const name = document.getElementById('sm-name').value.trim();
    const emoji = document.getElementById('sm-emoji').value.trim();
    const regex = document.getElementById('sm-regex').value.trim();

    if (!name || !emoji) {
        showAdminNotification('Platform adı ve emoji gereklidir.', 'error');
        return;
    }

    const result = await sendAction('add_social_platform.php', {
        name: name,
        emoji: emoji,
        regex: regex
    });

    if (result.success) {
        // Formu temizle
        document.getElementById('sm-name').value = '';
        document.getElementById('sm-emoji').value = '';
        document.getElementById('sm-regex').value = '';
        loadSocialMediaSettings();
    }
}

// Rütbe ayarlarını kaydetme
async function saveRankSettings() {
    const commentPoints = document.getElementById('rank-comment-points').value;
    const drawingPoints = document.getElementById('rank-drawing-points').value;
    const followerPoints = document.getElementById('rank-follower-points').value;
    const upvotePoints = document.getElementById('rank-upvote-points').value;

    const result = await sendAction('save_rank_settings.php', {
        comment_points: commentPoints,
        drawing_points: drawingPoints,
        follower_points: followerPoints,
        upvote_points: upvotePoints
    });
}

// Yardımcı fonksiyonlar
function getAnnouncementColor(type) {
    const colors = {
        'info': '#2196F3',
        'warning': '#FF9800',
        'success': '#4CAF50',
        'critical': '#F44336'
    };
    return colors[type] || '#2196F3';
}

function getAnnouncementIcon(type) {
    const icons = {
        'info': 'ℹ️',
        'warning': '⚠️',
        'success': '✅',
        'critical': '🚨'
    };
    return icons[type] || 'ℹ️';
}

// Eksik fonksiyonları ekle
async function fetchRecentContentForModeration() {
    const container = document.getElementById('content-moderation-area');
    container.innerHTML = '<p>İçerikler yükleniyor...</p>';

    try {
        const response = await fetch('fetch_recent_content.php');
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
                    <button onclick="moderateContent(${drawing.id}, 'drawing', '${drawing.is_visible ? 'hide' : 'show'}')"
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
                    <button onclick="moderateContent(${comment.id}, 'comment', '${comment.is_visible ? 'hide' : 'show'}')"
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
async function moderateContent(contentId, contentType, action) {
    try {
        const response = await fetch('moderate_content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `content_id=${contentId}&content_type=${contentType}&action=${action}`
        });

        const result = await response.json();
        showAdminNotification(result.message, result.success ? 'success' : 'error');

        if (result.success) {
            fetchRecentContentForModeration();
        }
    } catch (error) {
        console.error('Moderasyon hatası:', error);
        showAdminNotification('İşlem sırasında hata oluştu.', 'error');
    }
}

// Kullanıcı moderasyonu
async function moderateUser(userId, action) {
    const confirmed = await showConfirm(
        'Kullanıcı Yönetimi',
        `Bu kullanıcıyı ${action === 'ban' ? 'yasaklamak' : action === 'unban' ? 'yasaklamayı kaldırmak' : 'mute etmeyi kaldırmak'} istediğinizden emin misiniz?`
    );

    if (confirmed) {
        try {
            const response = await fetch('moderate_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&action=${action}`
            });

            const result = await response.json();
            showAdminNotification(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                loadUserList();
            }
        } catch (error) {
            console.error('Kullanıcı moderasyon hatası:', error);
            showAdminNotification('İşlem sırasında hata oluştu.', 'error');
        }
    }
}

// Mute modalını göster
function showMuteModal(userId) {
    document.getElementById('mute-user-id').value = userId;
    document.getElementById('mute-modal').style.display = 'block';
}

// Mute uygula
async function applyCommentMute() {
    const userId = document.getElementById('mute-user-id').value;
    const duration = document.getElementById('mute-duration').value;

    try {
        const response = await fetch('moderate_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}&action=mute&duration=${duration}`
        });

        const result = await response.json();
        showAdminNotification(result.message, result.success ? 'success' : 'error');

        if (result.success) {
            document.getElementById('mute-modal').style.display = 'none';
            loadUserList();
        }
    } catch (error) {
        console.error('Mute uygulama hatası:', error);
        showAdminNotification('Mute uygulanırken hata oluştu.', 'error');
    }
}

// Rol değiştirme
async function setRole(userId, newRole) {
    try {
        const response = await fetch('moderate_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}&action=set_role&new_role=${newRole}`
        });

        const result = await response.json();
        showAdminNotification(result.message, result.success ? 'success' : 'error');

        if (result.success) {
            loadUserList();
        }
    } catch (error) {
        console.error('Rol değiştirme hatası:', error);
        showAdminNotification('Rol değiştirilirken hata oluştu.', 'error');
    }
}

// Duyuruları yükle
async function loadAnnouncements() {
    try {
        const response = await fetch('fetch_announcements.php');
        const result = await response.json();

        const container = document.getElementById('announcements-list');
        if (result.success) {
            container.innerHTML = result.announcements.map(ann => `
            <div class="announcement-item" style="border-left: 4px solid ${getAnnouncementColor(ann.type)}; padding: 10px; margin-bottom: 10px; background: var(--fixed-bg);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
            <strong>${getAnnouncementIcon(ann.type)} ${ann.content}</strong>
            <small>${new Date(ann.created_at).toLocaleString('tr-TR')}</small>
            </div>
            <button onclick="deleteAnnouncement(${ann.id})" class="btn-danger btn-sm" style="margin-top: 5px;">Sil</button>
            </div>
            `).join('');
        } else {
            container.innerHTML = `<p style="color: red;">Hata: ${result.message}</p>`;
        }
    } catch (error) {
        console.error('Duyurular yüklenirken hata:', error);
    }
}

// Sosyal medya ayarlarını yükle
async function loadSocialMediaSettings() {
    try {
        const response = await fetch('fetch_social_platforms.php');
        const result = await response.json();

        const container = document.getElementById('social-media-list');
        if (result.success) {
            container.innerHTML = result.platforms.map(platform => `
            <div class="platform-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid var(--border-color);">
            <div>
            <span style="font-size: 20px;">${platform.emoji}</span>
            <strong>${platform.name}</strong>
            <small style="color: var(--main-text); opacity: 0.7;"> - ${platform.url_regex || 'Regex tanımlı değil'}</small>
            </div>
            <div>
            <button onclick="togglePlatform(${platform.id}, ${platform.is_active})" class="${platform.is_active ? 'btn-warning' : 'btn-success'} btn-sm">
            ${platform.is_active ? 'Pasif Yap' : 'Aktif Yap'}
            </button>
            <button onclick="deletePlatform(${platform.id})" class="btn-danger btn-sm">Sil</button>
            </div>
            </div>
            `).join('');
        } else {
            container.innerHTML = `<p style="color: red;">Hata: ${result.message}</p>`;
        }
    } catch (error) {
        console.error('Sosyal medya ayarları yüklenirken hata:', error);
    }
}

// Rütbe ayarlarını yükle
async function loadRankSettings() {
    try {
        const response = await fetch('fetch_rank_settings.php');
        const result = await response.json();

        if (result.success) {
            document.getElementById('rank-comment-points').value = result.settings.comment_points || 1;
            document.getElementById('rank-drawing-points').value = result.settings.drawing_points || 2;
            document.getElementById('rank-follower-points').value = result.settings.follower_points || 0.5;
            document.getElementById('rank-upvote-points').value = result.settings.upvote_points || 0.2;
        }
    } catch (error) {
        console.error('Rütbe ayarları yüklenirken hata:', error);
    }
}

// Özel mesajları yükle
async function loadPrivateMessages() {
    const usersContainer = document.getElementById('pm-users-list');
    const conversationContainer = document.getElementById('pm-conversation');

    usersContainer.innerHTML = '<p>Kullanıcılar yükleniyor...</p>';
    conversationContainer.innerHTML = '<p>Bir konuşma seçin</p>';
}

// Onay modalı fonksiyonu
function showConfirm(title, message) {
    return new Promise((resolve) => {
        // Basit onay kutusu - daha gelişmiş bir modal eklenebilir
        resolve(confirm(`${title}\n\n${message}`));
    });
}
