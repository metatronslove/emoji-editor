<?php
// messages_modal.php - TÜM SAYFALAR İÇİN ORTAK MESAJ MODALI
?>
<!-- MESAJ KUTUSU MODALI -->
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
                    <textarea id="reply-input" placeholder="Mesajınızı yazın..." style="width: 100%; height: 80px; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--fixed-bg); color: var(--main-text); font-family: inherit; resize: vertical;"></textarea>
                    <input type="file" id="reply-file-input" style="display: none;" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.mp3,.mp4">
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button onclick="sendMessageReply()" class="btn-primary">📤 Yanıtla</button>
                        <button onclick="document.getElementById('reply-file-input').click()" class="btn-secondary">📎 Dosya Ekle</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global mesaj değişkenleri
let currentConversation = null;
let currentFileData = null;
let currentFileName = null;
let currentFileType = null;

// Mesaj içeriğini formatla
function formatMessageContent(content) {
    if (!content) return '';

    // URL'leri link'e çevir
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    return content.replace(urlRegex, url =>
        `<a href="${url}" target="_blank" rel="noopener" style="color: var(--accent-color);">${url}</a>`
    );
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

// Konuşmaları yükle
async function loadConversations() {
    try {
        const response = await fetch('get_conversations.php');
        const result = await response.json();

        const container = document.getElementById('conversations-container');
        if (result.success && result.conversations && result.conversations.length > 0) {
            container.innerHTML = result.conversations.map(conv => {
                const lastMessage = conv.last_message_content || 'Henüz mesaj yok';
                const unreadCount = conv.unread_count > 0 ?
                    `<span class="unread-badge">${conv.unread_count}</span>` : '';

                const profilePicSrc = formatProfilePicture(conv.other_user_picture);

                return `
                    <div class="conversation-item" data-user-id="${conv.other_user_id}"
                         style="padding: 12px; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s; border-radius: 6px; margin-bottom: 5px;"
                         onclick="selectConversation(${conv.other_user_id}, '${conv.other_username.replace(/'/g, "\\'")}')">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="${profilePicSrc}" alt="Profil" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong style="color: var(--accent-color);">${conv.other_username}</strong>
                                    ${unreadCount}
                                </div>
                                <div style="font-size: 0.85em; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    ${lastMessage}
                                </div>
                                <div style="font-size: 0.75em; opacity: 0.6;">
                                    ${conv.last_message_time ? new Date(conv.last_message_time).toLocaleDateString('tr-TR') : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<p style="text-align: center; color: var(--main-text); opacity: 0.7;">Henüz hiç mesajınız yok.</p>';
        }
    } catch (error) {
        console.error('Konuşmalar yüklenirken hata:', error);
        const container = document.getElementById('conversations-container');
        container.innerHTML = '<p style="text-align: center; color: #dc3545;">Konuşmalar yüklenirken hata oluştu.</p>';
    }
}

// Konuşma seç
async function selectConversation(userId, username) {
    currentConversation = { id: userId, username: username };

    // Seçili konuşmayı vurgula
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.style.backgroundColor = '';
    });

    const selectedItem = document.querySelector(`.conversation-item[data-user-id="${userId}"]`);
    if (selectedItem) {
        selectedItem.style.backgroundColor = 'var(--accent-color)';
        selectedItem.style.color = 'white';
    }

    // Başlık güncelle
    document.getElementById('conversation-with').textContent = `${username} ile konuşma`;

    // Mesajları yükle
    await loadConversationMessages(userId);

    // Yanıt bölümünü göster
    document.getElementById('reply-section').style.display = 'block';

    // Okunmamış mesajları işaretle
    await markMessagesAsRead(userId);
}

// Mesajları yükle
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

// Mesaj elementi oluştur
function createMessageElement(message) {
    const isOwn = message.sender_id == window.currentUser.id;
    const alignment = isOwn ? 'right' : 'left';

    let content = '';
    if (message.message_type === 'text') {
        content = `<div class="message-text">${formatMessageContent(message.content)}</div>`;
    } else if (message.message_type === 'image') {
        content = `<img src="data:${message.mime_type};base64,${message.file_data}" alt="${message.file_name}" style="max-width: 300px; max-height: 300px; border-radius: 8px;">`;
    } else if (message.message_type === 'video') {
        content = `
            <video controls style="max-width: 300px; max-height: 300px;">
                <source src="data:${message.mime_type};base64,${message.file_data}" type="${message.mime_type}">
            </video>
        `;
    } else if (message.message_type === 'audio') {
        content = `
            <audio controls style="width: 100%;">
                <source src="data:${message.mime_type};base64,${message.file_data}" type="${message.mime_type}">
            </audio>
        `;
    } else {
        content = `<a href="data:${message.mime_type};base64,${message.file_data}" download="${message.file_name}" class="btn-secondary">📎 ${message.file_name}</a>`;
    }

    return `
        <div class="message-item" style="text-align: ${alignment}; margin-bottom: 15px;">
            <div style="display: inline-block; max-width: 80%; background: ${isOwn ? 'var(--accent-color)' : 'var(--fixed-bg)'}; color: ${isOwn ? 'white' : 'var(--main-text)'}; padding: 10px; border-radius: 12px; word-wrap: break-word;">
                ${!isOwn ? `<small><strong>${message.sender_username}</strong></small><br>` : ''}
                ${content}
                <div style="font-size: 0.8em; opacity: 0.7; margin-top: 5px;">
                    ${new Date(message.created_at).toLocaleString('tr-TR')}
                    ${message.is_read ? '✓✓' : '✓'}
                </div>
            </div>
        </div>
    `;
}

// Yanıt gönder
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

        const response = await fetch('send_message.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            textInput.value = '';
            document.getElementById('reply-file-input').value = '';
            currentFileData = null;
            currentFileName = null;
            currentFileType = null;

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
    }
}

// Mesaj tipini belirle
function getMessageType(mimeType) {
    if (mimeType.startsWith('image/')) return 'image';
    if (mimeType.startsWith('video/')) return 'video';
    if (mimeType.startsWith('audio/')) return 'audio';
    return 'file';
}

// Mesajları okundu olarak işaretle
async function markMessagesAsRead(otherUserId) {
    try {
        await fetch('mark_messages_read.php', {
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

// Dosya seçimi işlemi
document.addEventListener('DOMContentLoaded', function() {
    const replyFileInput = document.getElementById('reply-file-input');
    if (replyFileInput) {
        replyFileInput.addEventListener('change', handleReplyFileSelect);
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

    const allowedTypes = ['image/', 'video/', 'audio/', 'application/pdf', 'text/', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
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
        showNotification(`"${file.name}" dosyası eklendi.`, 'success');
    };
    reader.readAsDataURL(file);
}

// Modal kapatma event'leri
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('messages-modal');
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
</script>
