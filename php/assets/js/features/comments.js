// Global değişkenler
let boardFileData = null;
let boardFileName = null;
let boardFileType = null;

/**
 * Pano dosya seçimi
 */
function handleBoardFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Dosya boyutu kontrolü (2MB)
    if (file.size > 2097152) {
        showNotification('Dosya boyutu 2MB\'dan küçük olmalı.', 'error');
        event.target.value = '';
        return;
    }

    const allowedTypes = [
        'image/', 'video/', 'audio/',
        'application/pdf', 'text/',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    const isValidType = allowedTypes.some(type => file.type.startsWith(type));

    if (!isValidType) {
        showNotification('Desteklenmeyen dosya türü.', 'error');
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        boardFileData = e.target.result.split(',')[1];
        boardFileName = file.name;
        boardFileType = file.type;

        // Dosya bilgisini göster
        document.getElementById('boardFileInfo').style.display = 'block';
        document.getElementById('boardFileName').textContent = `${file.name} (${formatFileSize(file.size)})`;

        showNotification(`"${file.name}" dosyası eklendi.`, 'success');
    };
    reader.readAsDataURL(file);
}

/**
 * Pano dosyasını temizle
 */
function clearBoardFile() {
    boardFileData = null;
    boardFileName = null;
    boardFileType = null;
    document.getElementById('boardFileInput').value = '';
    document.getElementById('boardFileInfo').style.display = 'none';
}

/**
 * Profil yorumu gönder
 */
async function postProfileComment() {
    if (!window.PROFILE_DATA?.canViewContent) {
        showNotification('Bu profilin panosuna mesaj yazma izniniz yok.', 'error');
        return;
    }

    const inputElement = document.getElementById('boardCommentInput');
    const content = inputElement.value.trim();

    if (content === '' && !boardFileData) {
        showNotification('Lütfen bir mesaj yazın veya dosya ekleyin.', 'error');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('target_type', 'profile');
        formData.append('target_id', window.PROFILE_DATA.userId);
        formData.append('content', content);

        // Dosya varsa ekle
        if (boardFileData) {
            formData.append('file_data', boardFileData);
            formData.append('file_name', boardFileName);
            formData.append('mime_type', boardFileType);
            formData.append('message_type', getMessageType(boardFileType));
        }

        const response = await fetch(SITE_BASE_URL + 'action/comment_action.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');

            // Formu temizle
            inputElement.value = '';
            clearBoardFile();

            // Yorumları yenile
            fetchProfileComments();
        } else {
            showNotification(result.message, 'error');

            // Eğer gizlilik hatası ise, sayfayı yenile
            if (result.message.includes('gizli profil') || result.message.includes('takipçiler')) {
                setTimeout(() => window.location.reload(), 2000);
            }
        }
    } catch (error) {
        console.error('Yorum gönderme hatası:', error);
        showNotification('Yorum gönderilirken hata oluştu.', 'error');
    }
}

/**
 * Profil yorumlarını getir
 */
async function fetchProfileComments() {
    const listElement = document.getElementById('board-comments-list');
    if (!listElement) return;

    listElement.innerHTML = '<p style="text-align: center; color: var(--main-text); opacity: 0.7;">Mesajlar yükleniyor...</p>';

    try {
        const response = await fetch(SITE_BASE_URL + `core/fetch_comments.php?type=profile&id=${window.PROFILE_DATA.userId}`);
        const result = await response.json();

        if (result.access_denied) {
            listElement.innerHTML = `
            <div style="text-align: center; padding: 30px; color: var(--main-text);">
            <div style="font-size: 48px; margin-bottom: 15px;">🔒</div>
            <p style="margin-bottom: 15px; opacity: 0.8;">Bu gizli profilin panosunu görmek için takipçi olmalısınız.</p>
            ${window.PROFILE_DATA.currentUserId ? `
                <button onclick="handleProfileFollowAction(document.getElementById('followRequestBtn'))"
                class="btn-primary">Takip İsteği Gönder</button>
                ` : `
                <p style="opacity: 0.6;">Giriş yaparak takip isteği gönderebilirsiniz.</p>
                `}
                </div>
                `;
                return;
        }

        if (result.success && result.comments.length > 0) {
            listElement.innerHTML = result.comments.map(comment => {
                let profilePicSrc = formatProfilePicture(comment.profile_picture);

                const profilePic = `<img src="${profilePicSrc}" alt="Profil" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">`;

                // Medya içeriğini oluştur
                let mediaContent = '';
                if (comment.message_type === 'image') {
                    mediaContent = `<img src="data:${comment.mime_type};base64,${comment.file_data}" alt="${comment.file_name}" style="max-width: 300px; max-height: 300px; border-radius: 8px; margin-top: 8px; cursor: pointer;" onclick="openMediaViewer('data:${comment.mime_type};base64,${comment.file_data}')">`;
                } else if (comment.message_type === 'video') {
                    mediaContent = `
                    <div style="margin-top: 8px;">
                    <video controls style="max-width: 300px; max-height: 300px; border-radius: 8px;">
                    <source src="data:${comment.mime_type};base64,${comment.file_data}" type="${comment.mime_type}">
                    </video>
                    </div>
                    `;
                } else if (comment.message_type === 'audio') {
                    mediaContent = `
                    <div style="margin-top: 8px;">
                    <audio controls style="width: 100%;">
                    <source src="data:${comment.mime_type};base64,${comment.file_data}" type="${comment.mime_type}">
                    </audio>
                    </div>
                    `;
                } else if (comment.message_type === 'file') {
                    mediaContent = `
                    <div style="margin-top: 8px;">
                    <a href="data:${comment.mime_type};base64,${comment.file_data}" download="${comment.file_name}" class="btn-secondary">
                    📎 ${comment.file_name}
                    </a>
                    </div>
                    `;
                }

                // Silme butonu (sadece yorum sahibi, admin veya moderatör)
                let deleteButton = '';
                if (comment.can_delete) {
                    deleteButton = `
                    <button onclick="deleteComment(${comment.id})"
                    style="background: #dc3545; color: white; border: none; border-radius: 3px; padding: 2px 6px; font-size: 11px; cursor: pointer; margin-left: 8px;">
                    ✖
                    </button>
                    `;
                }

                return `
                <div class="comment-item" style="border-bottom: 1px solid var(--border-color); padding: 15px 0;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                ${profilePic}
                <div style="flex-grow: 1;">
                <strong>
                <a href="/${comment.username}/" style="color: var(--accent-color); text-decoration: none;">
                ${comment.username}
                </a>
                </strong>
                <div style="color: var(--main-text); opacity: 0.7; font-size: 0.85em;">
                ${new Date(comment.created_at).toLocaleString('tr-TR')}
                ${!comment.is_visible ? '<span style="color: #ffc107; margin-left: 5px;">(Silinmiş)</span>' : ''}
                </div>
                </div>
                ${deleteButton}
                </div>
                <div style="white-space: pre-wrap; margin: 0; padding: 12px; background: var(--fixed-bg); border-radius: 8px; font-size: 0.95em; position: relative;">
                ${comment.is_visible ? (comment.content ? formatMessageContent(comment.content) : '') : '<em style="opacity: 0.6;">Bu mesaj silinmiş</em>'}
                ${comment.is_visible ? mediaContent : ''}
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
        console.error('Yorumlar yüklenirken hata:', error);
        listElement.innerHTML = '<p style="text-align: center; color: #dc3545;">Pano mesajları yüklenirken hata oluştu.</p>';
    }
}

/**
 * Yorum sil
 */
async function deleteComment(commentId) {
    const confirmed = await showConfirm(
        'Mesajı Sil',
        'Bu mesajı silmek istediğinizden emin misiniz?'
    );

    if (!confirmed) return;

    try {
        const response = await fetch(SITE_BASE_URL + 'core/delete_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `comment_id=${commentId}`
        });

        const result = await response.json();
        showNotification(result.message, result.success ? 'success' : 'error');

        if (result.success) {
            fetchProfileComments();
        }
    } catch (error) {
        console.error('Yorum silme hatası:', error);
        showNotification('Yorum silinirken hata oluştu.', 'error');
    }
}

/**
 * Medya görüntüleyici aç
 */
function openMediaViewer(src) {
    // Basit bir medya görüntüleyici
    const overlay = document.createElement('div');
    overlay.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    cursor: pointer;
    `;

    const media = document.createElement('img');
    media.src = src;
    media.style.cssText = `
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
    border-radius: 8px;
    `;

    overlay.appendChild(media);
    overlay.onclick = () => document.body.removeChild(overlay);

    document.body.appendChild(overlay);
}

/**
 * Takip işlemi yönetimi
 */
async function handleProfileFollowAction(button) {
    if (!window.PROFILE_DATA?.currentUserId) {
        showNotification('Takip işlemi için giriş yapmalısınız.', 'error');
        return;
    }

    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'İşleniyor...';

    try {
        const formData = new FormData();
        formData.append('target_user_id', window.PROFILE_DATA.userId);
        formData.append('action', window.PROFILE_DATA.isFollowing ? 'unfollow' : 'follow');

        const response = await fetch(SITE_BASE_URL + 'action/follow_action.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Takip işlemi hatası:', error);
        showNotification('Takip işlemi sırasında hata oluştu.', 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
}

/**
 * Yorum sistemini başlat
 */
function initCommentSystem() {
    // Event listener'ları bağla
    const boardFileInput = document.getElementById('boardFileInput');
    if (boardFileInput) {
        boardFileInput.addEventListener('change', handleBoardFileSelect);
    }

    const postCommentBtn = document.getElementById('postCommentBtn');
    if (postCommentBtn) {
        postCommentBtn.addEventListener('click', postProfileComment);
    }

    const boardCommentInput = document.getElementById('boardCommentInput');
    if (boardCommentInput) {
        boardCommentInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && e.ctrlKey) {
                postProfileComment();
            }
        });
    }

    // Sayfa yüklendiğinde yorumları getir
    if (window.PROFILE_DATA?.userId) {
        fetchProfileComments();
    }
}
