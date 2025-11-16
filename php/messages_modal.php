<?php
// messages_modal.php - TAM MEDYA DESTEKLİ
?>
<!-- MESAJ KUTUSU MODALI - GÜNCELLENMİŞ -->
<div id="messages-modal" class="modal">
<div class="modal-content" style="max-width: 900px; max-height: 90vh; width: 95%;">
<button class="modal-close">❎</button>
<h2>📬 Mesaj Kutusu</h2>

<div style="display: flex; gap: 20px; height: 600px; flex-direction: row;">
<!-- Sol: Konuşma Listesi -->
<div id="conversations-list" style="flex: 1; border-right: 1px solid var(--border-color); padding-right: 15px; overflow-y: auto; min-width: 250px;">
<h3 style="margin-bottom: 15px;">Konuşmalar</h3>
<div id="conversations-container">
<p style="text-align: center; color: var(--main-text); opacity: 0.7;">Konuşmalar yükleniyor...</p>
</div>
</div>

<!-- Sağ: Mesaj Görüntüleme -->
<div id="message-view" style="flex: 2; display: flex; flex-direction: column; min-width: 300px;">
<div id="selected-conversation-header" style="padding: 10px; border-bottom: 1px solid var(--border-color); margin-bottom: 15px;">
<h3 id="conversation-with" style="margin: 0;">Konuşma seçin</h3>
</div>

<div id="conversation-messages" style="flex: 1; overflow-y: auto; margin-bottom: 15px; padding: 10px; background: var(--fixed-bg); border-radius: 8px; min-height: 300px;">
<p style="text-align: center; color: var(--main-text); opacity: 0.7;">Bir konuşma seçin</p>
</div>

<div id="reply-section" style="display: none;">
<!-- Dosya bilgisi gösterimi -->
<div id="reply-file-info" style="display: none; margin-bottom: 10px; padding: 8px; background: var(--fixed-bg); border-radius: 6px; border: 1px solid var(--accent-color);">
<span style="font-weight: bold;">📎 Dosya seçildi:</span>
<span id="reply-file-name" style="margin-left: 5px;"></span>
<button onclick="clearReplyFile()" style="margin-left: 10px; background: #dc3545; color: white; border: none; border-radius: 3px; padding: 2px 6px; font-size: 12px; cursor: pointer;">✖</button>
</div>

<textarea id="reply-input" placeholder="Mesajınızı yazın... (Resim, video veya ses de ekleyebilirsiniz)" style="width: 100%; height: 80px; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--fixed-bg); color: var(--main-text); font-family: inherit; resize: vertical;"></textarea>

<div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
<button onclick="sendMessageReply()" class="btn-primary">📤 Gönder</button>
<button onclick="document.getElementById('reply-file-input').click()" class="btn-secondary">📎 Dosya Ekle</button>
<button onclick="openMediaGallery()" class="btn-info">🖼️ Medya Galerisi</button>
<button onclick="clearReplyForm()" class="btn-warning">🗑️ Temizle</button>
</div>

<input type="file" id="reply-file-input" style="display: none;"
accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.mp3,.mp4,.wav,.avi,.mov,.zip,.rar">
</div>
</div>
</div>
</div>
</div>

<!-- MEDYA GALERİSİ MODALI -->
<div id="media-gallery-modal" class="modal">
<div class="modal-content" style="max-width: 800px; max-height: 80vh;">
<button class="modal-close">&times;</button>
<h2>🖼️ Medya Galerisi</h2>

<div style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
<button onclick="document.getElementById('gallery-file-input').click()" class="btn-primary">
📁 Yeni Medya Yükle
</button>
<button onclick="loadMediaGallery()" class="btn-secondary">
🔄 Yenile
</button>
<div style="flex-grow: 1;"></div>
<span style="font-size: 0.9em; opacity: 0.7; align-self: center;">
Son 50 medya gösteriliyor
</span>
</div>

<div id="media-gallery-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; max-height: 400px; overflow-y: auto; padding: 10px; background: var(--fixed-bg); border-radius: 8px;">
<p style="text-align: center; grid-column: 1 / -1; opacity: 0.7;">Medya yükleniyor...</p>
</div>

<div class="modal-buttons" style="margin-top: 15px;">
<button onclick="closeMediaGallery()" class="btn-secondary">Kapat</button>
</div>
</div>
</div>

<!-- MEDYA ÖNİZLEME MODALI -->
<div id="media-preview-modal" class="modal">
<div class="modal-content" style="max-width: 90vw; max-height: 90vh; background: transparent; box-shadow: none;">
<button class="modal-close" style="position: fixed; top: 20px; right: 20px; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 20px; z-index: 10001;">×</button>
<div id="media-preview-content" style="display: flex; align-items: center; justify-content: center; height: 100%;">
<!-- Medya içeriği buraya yüklenecek -->
</div>
</div>
</div>

<script>
// Global mesaj değişkenleri
let currentConversation = null;
let currentFileData = null;
let currentFileName = null;
let currentFileType = null;

// Medya galerisi için değişkenler
let mediaGallery = [];

// Mesaj içeriğini formatla - GELİŞTİRİLMİŞ
function formatMessageContent(content) {
    if (!content) return '';

    // URL'leri link'e çevir
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    let formattedContent = content.replace(urlRegex, url =>
    `<a href="${url}" target="_blank" rel="noopener" style="color: var(--accent-color);">${url}</a>`
    );

    // Satır sonlarını koru
    formattedContent = formattedContent.replace(/\n/g, '<br>');

    return formattedContent;
}

// Dosya boyutunu formatla
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Mesaj tipini belirle
function getMessageType(mimeType) {
    if (mimeType.startsWith('image/')) return 'image';
    if (mimeType.startsWith('video/')) return 'video';
    if (mimeType.startsWith('audio/')) return 'audio';
    return 'file';
}

// Mesaj kutusunu aç
function openMessagesModal() {
    if (!window.currentUser || !window.currentUser.id) {
        showNotification('Mesajları görüntülemek için giriş yapmalısınız.', 'error');
        return;
    }

    const modal = document.getElementById('messages-modal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        loadConversations();
    }
}

// Konuşmaları yükle - GÜNCELLENMİŞ
async function loadConversations() {
    try {
        const response = await fetch('https://flood.page.gd/get_conversations.php');
        const result = await response.json();

        const container = document.getElementById('conversations-container');
        if (result.success && result.conversations && result.conversations.length > 0) {
            container.innerHTML = result.conversations.map(conv => {
                const lastMessage = conv.last_message_content || 'Henüz mesaj yok';
            const unreadCount = conv.unread_count > 0 ?
            `<span class="unread-badge">${conv.unread_count}</span>` : '';

            const profilePicSrc = formatProfilePicture(conv.other_user_picture);

            // Son mesajı kısalt ve medya türünü kontrol et
            let displayMessage = lastMessage;
            let messageIcon = '💬';

            if (lastMessage.includes('[MEDYA]') || lastMessage.includes('[RESIM]')) {
                displayMessage = '📷 Resim';
            messageIcon = '📷';
            } else if (lastMessage.includes('[VIDEO]')) {
                displayMessage = '🎥 Video';
            messageIcon = '🎥';
            } else if (lastMessage.includes('[SES]')) {
                displayMessage = '🎵 Ses';
            messageIcon = '🎵';
            } else if (lastMessage.includes('[DOSYA]')) {
                displayMessage = '📎 Dosya';
            messageIcon = '📎';
            } else if (lastMessage.length > 25) {
                displayMessage = lastMessage.substring(0, 25) + '...';
            }

            return `
            <div class="conversation-item" data-user-id="${conv.other_user_id}"
            style="padding: 12px; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: all 0.3s ease; border-radius: 8px; margin-bottom: 5px; border: 1px solid transparent;"
            onclick="selectConversation(${conv.other_user_id}, '${conv.other_username.replace(/'/g, "\\'")}')"
            onmouseover="this.style.backgroundColor='var(--fixed-bg)'; this.style.borderColor='var(--accent-color)'"
            onmouseout="this.style.backgroundColor=''; this.style.borderColor='transparent'">
            <div style="display: flex; align-items: center; gap: 10px;">
            <img src="${profilePicSrc}" alt="Profil" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color);">
            <div style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
            <strong style="color: var(--accent-color); font-size: 14px;">${conv.other_username}</strong>
            ${unreadCount}
            </div>
            <div style="font-size: 0.85em; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 5px;">
            <span>${messageIcon}</span>
            <span>${displayMessage}</span>
            </div>
            <div style="font-size: 0.75em; opacity: 0.6;">
            ${conv.last_message_time ? new Date(conv.last_message_time).toLocaleDateString('tr-TR') : 'Henüz mesaj yok'}
            </div>
            </div>
            </div>
            </div>
            `;
            }).join('');
        } else {
            container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--main-text);">
            <div style="font-size: 48px; margin-bottom: 15px;">💭</div>
            <p style="margin-bottom: 15px; opacity: 0.8;">Henüz hiç mesajınız yok.</p>
            <p style="opacity: 0.6; font-size: 0.9em;">Birini bulup merhaba deyin! 👋</p>
            </div>
            `;
        }
    } catch (error) {
        console.error('Konuşmalar yüklenirken hata:', error);
        const container = document.getElementById('conversations-container');
        container.innerHTML = `
        <div style="text-align: center; padding: 20px; color: #dc3545;">
        <p>❌ Konuşmalar yüklenirken hata oluştu.</p>
        <button onclick="loadConversations()" class="btn-secondary" style="margin-top: 10px;">🔄 Yeniden Dene</button>
        </div>
        `;
    }
}

// Konuşma seç - GÜNCELLENMİŞ
async function selectConversation(userId, username) {
    console.log('🎯 Konuşma seçildi:', userId, username);

    currentConversation = { id: userId, username: username };

    // Seçili konuşmayı vurgula
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.style.backgroundColor = '';
    item.style.color = '';
    });

    const selectedItem = document.querySelector(`.conversation-item[data-user-id="${userId}"]`);
    if (selectedItem) {
        selectedItem.style.backgroundColor = 'var(--accent-color)';
        selectedItem.style.color = 'white';
    } else {
        console.log('⚠️ Konuşma öğesi bulunamadı, yeni konuşma başlatılıyor');
        // Yeni konuşma için özel stil
        document.getElementById('conversation-with').textContent = `${username} ile yeni konuşma`;
    }

    // Başlık güncelle
    document.getElementById('conversation-with').textContent = `${username} ile konuşma`;

    // Mesajları yükle
    await loadConversationMessages(userId);

    // Yanıt bölümünü göster
    document.getElementById('reply-section').style.display = 'block';

    // Okunmamış mesajları işaretle
    await markMessagesAsRead(userId);

    // Inputa odaklan
    setTimeout(() => {
        const replyInput = document.getElementById('reply-input');
        if (replyInput) {
            replyInput.focus();
        }
    }, 200);
}

// Mesajları yükle - GELİŞTİRİLMİŞ
async function loadConversationMessages(otherUserId) {
    try {
        const response = await fetch(`fetch_messages.php?other_user_id=${otherUserId}`);
        const result = await response.json();

        const container = document.getElementById('conversation-messages');
        if (result.success && result.messages && result.messages.length > 0) {
            container.innerHTML = result.messages.map(msg => createMessageElement(msg)).join('');
            container.scrollTop = container.scrollHeight;
        } else {
            container.innerHTML = '<p style="text-align: center; color: var(--main-text); opacity: 0.7;">Henüz mesaj yok. İlk mesajı siz gönderin!</p>';
        }
    } catch (error) {
        console.error('Konuşma mesajları yüklenirken hata:', error);
        const container = document.getElementById('conversation-messages');
        container.innerHTML = '<p style="text-align: center; color: #dc3545;">Mesajlar yüklenirken hata oluştu.</p>';
    }
}

// Mesaj elementi oluştur - TÜM MEDYA TÜRLERİ DESTEKLİ
function createMessageElement(message) {
    const isOwn = message.sender_id == window.currentUser.id;
    const alignment = isOwn ? 'right' : 'left';

    let content = '';
    let mediaContent = '';

    // Medya içeriğini oluştur
    if (message.message_type === 'image') {
        mediaContent = `
        <div style="margin-top: 8px;">
        <img src="data:${message.mime_type};base64,${message.file_data}"
        alt="${message.file_name}"
        style="max-width: 300px; max-height: 300px; border-radius: 8px; cursor: pointer; border: 2px solid var(--border-color);"
        onclick="openMediaPreview('data:${message.mime_type};base64,${message.file_data}', 'image')">
        <div style="font-size: 0.8em; opacity: 0.7; margin-top: 4px;">
        📷 ${message.file_name}
        </div>
        </div>
        `;
    } else if (message.message_type === 'video') {
        mediaContent = `
        <div style="margin-top: 8px;">
        <video controls style="max-width: 300px; max-height: 300px; border-radius: 8px; border: 2px solid var(--border-color);">
        <source src="data:${message.mime_type};base64,${message.file_data}" type="${message.mime_type}">
        Tarayıcınız video etiketini desteklemiyor.
        </video>
        <div style="font-size: 0.8em; opacity: 0.7; margin-top: 4px;">
        🎥 ${message.file_name}
        </div>
        </div>
        `;
    } else if (message.message_type === 'audio') {
        mediaContent = `
        <div style="margin-top: 8px; background: var(--fixed-bg); padding: 15px; border-radius: 8px;">
        <audio controls style="width: 100%;">
        <source src="data:${message.mime_type};base64,${message.file_data}" type="${message.mime_type}">
        Tarayıcınız audio etiketini desteklemiyor.
        </audio>
        <div style="font-size: 0.8em; opacity: 0.7; margin-top: 8px; text-align: center;">
        🎵 ${message.file_name}
        </div>
        </div>
        `;
    } else if (message.message_type === 'file') {
        mediaContent = `
        <div style="margin-top: 8px;">
        <a href="data:${message.mime_type};base64,${message.file_data}"
        download="${message.file_name}"
        style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 15px; background: var(--fixed-bg); border: 1px solid var(--border-color); border-radius: 6px; text-decoration: none; color: var(--main-text); transition: all 0.2s;"
        onmouseover="this.style.background='var(--accent-color)'; this.style.color='white'"
        onmouseout="this.style.background='var(--fixed-bg)'; this.style.color='var(--main-text)'">
        📎 ${message.file_name}
        <small style="opacity: 0.7;">(${formatFileSize(message.file_size)})</small>
        </a>
        </div>
        `;
    }

    // Metin içeriği
    if (message.content) {
        content = `<div class="message-text" style="margin-bottom: ${mediaContent ? '8px' : '0'};">${formatMessageContent(message.content)}</div>`;
    }

    return `
    <div class="message-item" style="text-align: ${alignment}; margin-bottom: 20px;">
    <div style="display: inline-block; max-width: 85%; background: ${isOwn ? 'var(--accent-color)' : 'var(--fixed-bg)'}; color: ${isOwn ? 'white' : 'var(--main-text)'}; padding: 12px; border-radius: 12px; word-wrap: break-word; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    ${!isOwn ? `
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
        <img src="${formatProfilePicture(message.profile_picture)}" alt="Profil" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
        <strong>${message.sender_username}</strong>
        </div>
        ` : ''}
        ${content}
        ${mediaContent}
        <div style="font-size: 0.75em; opacity: 0.7; margin-top: 8px; display: flex; justify-content: space-between; align-items: center;">
        <span>${new Date(message.created_at).toLocaleString('tr-TR')}</span>
        <span style="font-size: 1.2em;">${message.is_read ? '✓✓' : '✓'}</span>
        </div>
        </div>
        </div>
        `;
}

// Dosya seçimi işlemi - GELİŞTİRİLMİŞ
document.addEventListener('DOMContentLoaded', function() {
    const replyFileInput = document.getElementById('reply-file-input');
    if (replyFileInput) {
        replyFileInput.addEventListener('change', handleReplyFileSelect);
    }

    const galleryFileInput = document.getElementById('gallery-file-input');
    if (galleryFileInput) {
        galleryFileInput.addEventListener('change', handleGalleryFileSelect);
    }
});

function handleReplyFileSelect(event) {
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
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip', 'application/x-rar-compressed'
    ];

    const isValidType = allowedTypes.some(type => file.type.startsWith(type));

    if (!isValidType) {
        showNotification('Desteklenmeyen dosya türü.', 'error');
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        currentFileData = e.target.result.split(',')[1];
        currentFileName = file.name;
        currentFileType = file.type;

        // Dosya bilgisini göster
        document.getElementById('reply-file-info').style.display = 'block';
        document.getElementById('reply-file-name').textContent = `${file.name} (${formatFileSize(file.size)})`;

        showNotification(`"${file.name}" dosyası eklendi.`, 'success');
    };
    reader.readAsDataURL(file);
}

function clearReplyFile() {
    currentFileData = null;
    currentFileName = null;
    currentFileType = null;
    document.getElementById('reply-file-input').value = '';
    document.getElementById('reply-file-info').style.display = 'none';
    showNotification('Dosya kaldırıldı.', 'info');
}

function clearReplyForm() {
    document.getElementById('reply-input').value = '';
    clearReplyFile();
    showNotification('Form temizlendi.', 'info');
}

// Yanıt gönder - GELİŞTİRİLMİŞ
async function sendMessageReply() {
    if (!currentConversation) {
        showNotification('Lütfen bir konuşma seçin.', 'error');
        return;
    }

    const textInput = document.getElementById('reply-input');
    const content = textInput.value.trim();

    if (!content && !currentFileData) {
        showNotification('Mesaj veya dosya girin.', 'error');
        return;
    }

    // Gönder butonunu devre dışı bırak
    const sendButton = document.querySelector('#reply-section button[onclick="sendMessageReply()"]');
    const originalText = sendButton.textContent;
    sendButton.disabled = true;
    sendButton.textContent = '⏳ Gönderiliyor...';

    try {
        const formData = new FormData();
        formData.append('receiver_id', currentConversation.id);
        formData.append('content', content);

        if (currentFileData) {
            formData.append('file_data', currentFileData);
            formData.append('file_name', currentFileName);
            formData.append('mime_type', currentFileType);
            formData.append('message_type', getMessageType(currentFileType));
        } else {
            formData.append('message_type', 'text');
        }

        const response = await fetch('https://flood.page.gd/send_message.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Formu temizle
            textInput.value = '';
            clearReplyFile();

            // Mesajları yeniden yükle
            await loadConversationMessages(currentConversation.id);
            // Konuşma listesini güncelle
            await loadConversations();
            showNotification('Mesaj gönderildi.', 'success');
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Yanıt gönderme hatası:', error);
        showNotification('Mesaj gönderilemedi.', 'error');
    } finally {
        // Butonu tekrar etkinleştir
        sendButton.disabled = false;
        sendButton.textContent = originalText;
    }
}

// Medya galerisi işlevleri
function openMediaGallery() {
    const modal = document.getElementById('media-gallery-modal');
    if (modal) {
        modal.classList.add('show');
        loadMediaGallery();
    }
}

function closeMediaGallery() {
    const modal = document.getElementById('media-gallery-modal');
    if (modal) {
        modal.classList.remove('show');
    }
}

async function loadMediaGallery() {
    try {
        const container = document.getElementById('media-gallery-container');
        container.innerHTML = '<p style="text-align: center; grid-column: 1 / -1; opacity: 0.7;">Medya yükleniyor...</p>';

        const response = await fetch('https://flood.page.gd/fetch_user_media.php');
        const result = await response.json();

        if (result.success && result.media.length > 0) {
            container.innerHTML = result.media.map(media => `
            <div class="media-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 8px; text-align: center; cursor: pointer; background: var(--card-bg); transition: all 0.2s;"
            onclick="selectFromGallery('${media.file_data}', '${media.file_name}', '${media.mime_type}')"
            onmouseover="this.style.borderColor='var(--accent-color)'; this.style.transform='translateY(-2px)'"
            onmouseout="this.style.borderColor='var(--border-color)'; this.style.transform='translateY(0)'">
            ${media.message_type === 'image' ?
                `<img src="data:${media.mime_type};base64,${media.file_data}"
                style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px;">` :
                media.message_type === 'video' ?
                `<div style="width: 100%; height: 100px; background: linear-gradient(135deg, var(--accent-color), var(--accent-hover)); display: flex; align-items: center; justify-content: center; border-radius: 4px; color: white;">
                <span style="font-size: 24px;">🎥</span>
                </div>` :
                `<div style="width: 100%; height: 100px; background: linear-gradient(135deg, #6c757d, #495057); display: flex; align-items: center; justify-content: center; border-radius: 4px; color: white;">
                <span style="font-size: 24px;">📄</span>
                </div>`
            }
            <div style="font-size: 11px; margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500;">
            ${media.file_name}
            </div>
            <div style="font-size: 9px; opacity: 0.7; margin-top: 2px;">
            ${new Date(media.created_at).toLocaleDateString('tr-TR')}
            </div>
            </div>
            `).join('');
        } else {
            container.innerHTML = '<p style="text-align: center; grid-column: 1 / -1; opacity: 0.7; padding: 40px;">Henüz medya yok. Yeni medya yükleyin!</p>';
        }
    } catch (error) {
        console.error('Medya galerisi yüklenirken hata:', error);
        const container = document.getElementById('media-gallery-container');
        container.innerHTML = '<p style="text-align: center; grid-column: 1 / -1; color: #dc3545;">Medya yüklenirken hata oluştu.</p>';
    }
}

function handleGalleryFileSelect(event) {
    const files = event.target.files;
    if (!files.length) return;

    // İlk dosyayı işle
    const file = files[0];

    // Dosya boyutu kontrolü (2MB)
    if (file.size > 2097152) {
        showNotification('Dosya boyutu 2MB\'dan küçük olmalı.', 'error');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const fileData = e.target.result.split(',')[1];
        selectFromGallery(fileData, file.name, file.type);
    };

    reader.readAsDataURL(file);
}

function selectFromGallery(fileData, fileName, mimeType) {
    currentFileData = fileData;
    currentFileName = fileName;
    currentFileType = mimeType;

    // Dosya bilgisini göster
    document.getElementById('reply-file-info').style.display = 'block';
    document.getElementById('reply-file-name').textContent = `${fileName}`;

    closeMediaGallery();
    showNotification(`"${fileName}" galeriden seçildi.`, 'success');

    // Inputa odaklan
    document.getElementById('reply-input').focus();
}

// Medya önizleyici
function openMediaPreview(src, type) {
    const modal = document.getElementById('media-preview-modal');
    const content = document.getElementById('media-preview-content');

    if (type === 'image') {
        content.innerHTML = `<img src="${src}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
    } else if (type === 'video') {
        content.innerHTML = `
        <video controls autoplay style="max-width: 100%; max-height: 100%;">
        <source src="${src}" type="${currentFileType}">
        </video>
        `;
    }

    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeMediaPreview() {
    const modal = document.getElementById('media-preview-modal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Mesajları okundu olarak işaretle
async function markMessagesAsRead(otherUserId) {
    try {
        await fetch('https://flood.page.gd/mark_messages_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `sender_id=${otherUserId}`
        });

        // Bildirim sayacını güncelle
        updateMessageNotification();
    } catch (error) {
        console.error('Mesaj okundu işaretleme hatası:', error);
    }
}

// Modal kapatma event'leri
document.addEventListener('DOMContentLoaded', function() {
    const modals = ['messages-modal', 'media-gallery-modal', 'media-preview-modal'];

    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });

            const closeBtn = modal.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                });
            }
        }
    });

    // ESC tuşu ile kapatma
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && modal.classList.contains('show')) {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        }
    });
});

// Sayfa yüklendiğinde mesaj sistemini başlat
if (window.currentUser && window.currentUser.id) {
    // Mesaj bildirimlerini başlat
    setInterval(updateMessageNotification, 30000);
}
</script>
