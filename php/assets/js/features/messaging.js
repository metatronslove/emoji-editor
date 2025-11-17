class MessagingSystem {
    constructor() {
        this.ably = null;
        this.userChannel = null;
        this.conversationChannels = new Map();
        this.simpleModalReceiverId = null;
        this.simpleModalReceiverUsername = null;
        this.simpleModalFileData = null;
        this.simpleModalFileName = null;
        this.simpleModalFileType = null;
        this.isAblyConnected = false;
        this.typingTimeouts = new Map();
    }

    async init() {
        await this.initAbly();
        this.bindEvents();
        this.initNotificationPermission();
    }

    // ABLY MESAJLAŞMA SİSTEMİ
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

    subscribeToMessagingChannels() {
        if (!this.ably || !window.currentUser?.id) return;

        // Kullanıcıya özel mesaj kanalı
        const userId = window.currentUser.id;
        this.userChannel = this.ably.channels.get('user-messages-' + userId);

        // Yeni mesajları dinle
        this.userChannel.subscribe('new_message', (message) => {
            this.handleNewMessage(message.data);
        });

        // Mesaj okundu bildirimleri
        this.userChannel.subscribe('message_read', (message) => {
            this.handleMessageRead(message.data);
        });

        // Çevrimiçi durum değişiklikleri
        this.userChannel.subscribe('user_online', (message) => {
            this.handleUserOnlineStatus(message.data);
        });

        // Yazıyor göstergesi
        this.userChannel.subscribe('typing_indicator', (message) => {
            this.handleTypingIndicator(message.data);
        });

        console.log('💬 Ably mesajlaşma kanallarına abone olundu');
    }

    // Konuşma kanalına abone ol
    subscribeToConversation(conversationId) {
        if (!this.ably || !this.isAblyConnected) return;

        const channelName = `conversation-${conversationId}`;
        if (!this.conversationChannels.has(conversationId)) {
            const channel = this.ably.channels.get(channelName);

            channel.subscribe('new_message', (message) => {
                this.handleConversationMessage(conversationId, message.data);
            });

            channel.subscribe('typing_indicator', (message) => {
                this.handleConversationTyping(conversationId, message.data);
            });

            this.conversationChannels.set(conversationId, channel);
            console.log(`💬 Konuşma ${conversationId} kanalına abone olundu`);
        }
    }

    // Konuşma kanalından çık
    unsubscribeFromConversation(conversationId) {
        if (this.conversationChannels.has(conversationId)) {
            const channel = this.conversationChannels.get(conversationId);
            channel.unsubscribe();
            this.conversationChannels.delete(conversationId);
            console.log(`💬 Konuşma ${conversationId} kanalından çıkıldı`);
        }
    }

    // MESAJ İŞLEME FONKSİYONLARI
    handleNewMessage(data) {
        console.log('💬 Yeni mesaj alındı:', data);

        // Kendi mesajımızı işleme
        if (data.sender_id == window.currentUser.id) return;

        // Bildirim göster
        this.showMessageNotification(data);

        // Mesaj kutusunu güncelle
        if (typeof updateMessageNotification === 'function') {
            updateMessageNotification();
        }

        // Aktif konuşma varsa güncelle
        if (this.isActiveConversation(data.sender_id)) {
            this.addMessageToActiveConversation(data);
        }
    }

    handleMessageRead(data) {
        console.log('👀 Mesaj okundu:', data);

        // Mesaj okundu işaretle
        if (typeof markMessagesAsRead === 'function') {
            markMessagesAsRead(data.sender_id);
        }
    }

    handleConversationMessage(conversationId, data) {
        console.log(`💬 Konuşma ${conversationId} mesajı:`, data);

        // Aktif konuşma mesajını göster
        if (this.isActiveConversation(data.sender_id)) {
            this.addMessageToActiveConversation(data);
        }
    }

    handleTypingIndicator(data) {
        console.log(`⌨️ Yazıyor:`, data);

        // Yazıyor göstergesi
        this.showTypingIndicator(data.user_id, data.is_typing);
    }

    handleConversationTyping(conversationId, data) {
        console.log(`⌨️ Konuşma ${conversationId} yazıyor:`, data);

        // Konuşma içi yazıyor göstergesi
        if (this.isActiveConversation(data.user_id)) {
            this.showTypingIndicator(data.user_id, data.is_typing);
        }
    }

    handleUserOnlineStatus(data) {
        console.log('🟢 Çevrimiçi durumu:', data);

        // Çevrimiçi durumu güncelle
        this.updateUserOnlineStatus(data.user_id, data.is_online, data.last_seen);
    }

    // BİLDİRİM SİSTEMİ
    showMessageNotification(message) {
        // Sayfa içi bildirim
        showNotification(`💬 ${message.sender_username}: ${message.content.substring(0, 50)}...`, 'info');

        // Browser bildirimi
        if (Notification.permission === 'granted') {
            const notification = new Notification(`${message.sender_username} yeni mesaj gönderdi`, {
                body: message.content.substring(0, 100) + (message.content.length > 100 ? '...' : ''),
                                                  icon: '/favicon.ico',
                                                  tag: 'message-' + message.sender_id
            });

            notification.onclick = () => {
                window.focus();
                this.openSimpleMessageModal(message.sender_id, message.sender_username);
                notification.close();
            };
        }

        // Ses bildirimi
        this.playMessageSound();
    }

    playMessageSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (error) {
            console.log('Ses çalınamadı:', error);
        }
    }

    // REAL-TIME MESAJ GÖNDERME
    async sendMessageRealTime(receiverId, content, messageType = 'text', fileData = null, fileName = null, fileType = null) {
        if (!this.isAblyConnected) {
            console.warn('Ably bağlantısı yok, HTTP fallback kullanılıyor');
            return this.sendMessageHTTP(receiverId, content, messageType, fileData, fileName, fileType);
        }

        try {
            const messageData = {
                sender_id: window.currentUser.id,
                sender_username: window.currentUser.username,
                receiver_id: receiverId,
                content: content,
                message_type: messageType,
                file_data: fileData,
                file_name: fileName,
                mime_type: fileType,
                timestamp: new Date().toISOString(),
                is_real_time: true
            };

            // Alıcının mesaj kanalına gönder
            const receiverChannel = this.ably.channels.get('user-messages-' + receiverId);
            await receiverChannel.publish('new_message', messageData);

            // Konuşma kanalına da gönder
            const conversationId = this.getConversationId(window.currentUser.id, receiverId);
            const conversationChannel = this.conversationChannels.get(conversationId);
            if (conversationChannel) {
                await conversationChannel.publish('new_message', messageData);
            }

            console.log('💬 Real-time mesaj gönderildi:', messageData);

            // UI'ı güncelle
            if (this.isActiveConversation(receiverId)) {
                this.addMessageToActiveConversation(messageData);
            }

            return { success: true, message: 'Mesaj gönderildi' };

        } catch (error) {
            console.error('Real-time mesaj gönderme hatası:', error);
            return this.sendMessageHTTP(receiverId, content, messageType, fileData, fileName, fileType);
        }
    }

    // HTTP Fallback mesaj gönderme
    async sendMessageHTTP(receiverId, content, messageType = 'text', fileData = null, fileName = null, fileType = null) {
        try {
            const formData = new FormData();
            formData.append('receiver_id', receiverId);
            formData.append('content', content);
            formData.append('message_type', messageType);

            if (fileData && fileName && fileType) {
                formData.append('file_data', fileData);
                formData.append('file_name', fileName);
                formData.append('mime_type', fileType);
            }

            const response = await fetch(SITE_BASE_URL + 'core/send_message.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success && this.isActiveConversation(receiverId)) {
                // UI'ı manuel güncelle
                this.loadConversationMessages(receiverId);
            }

            return result;

        } catch (error) {
            console.error('HTTP mesaj gönderme hatası:', error);
            return { success: false, message: 'Mesaj gönderilemedi' };
        }
    }

    // YAZIYOR GÖSTERGESİ
    startTyping(receiverId) {
        if (!this.isAblyConnected || !receiverId) return;

        const typingData = {
            user_id: window.currentUser.id,
            username: window.currentUser.username,
            is_typing: true,
            timestamp: new Date().toISOString()
        };

        // Alıcıya bildir
        const receiverChannel = this.ably.channels.get('user-messages-' + receiverId);
        receiverChannel.publish('typing_indicator', typingData);

        // Konuşma kanalına bildir
        const conversationId = this.getConversationId(window.currentUser.id, receiverId);
        const conversationChannel = this.conversationChannels.get(conversationId);
        if (conversationChannel) {
            conversationChannel.publish('typing_indicator', typingData);
        }

        // Timeout'u temizle ve yeniden kur
        if (this.typingTimeouts.has(receiverId)) {
            clearTimeout(this.typingTimeouts.get(receiverId));
        }

        const timeoutId = setTimeout(() => {
            this.stopTyping(receiverId);
        }, 3000);

        this.typingTimeouts.set(receiverId, timeoutId);
    }

    stopTyping(receiverId) {
        if (!this.isAblyConnected || !receiverId) return;

        const typingData = {
            user_id: window.currentUser.id,
            username: window.currentUser.username,
            is_typing: false,
            timestamp: new Date().toISOString()
        };

        // Alıcıya bildir
        const receiverChannel = this.ably.channels.get('user-messages-' + receiverId);
        receiverChannel.publish('typing_indicator', typingData);

        // Konuşma kanalına bildir
        const conversationId = this.getConversationId(window.currentUser.id, receiverId);
        const conversationChannel = this.conversationChannels.get(conversationId);
        if (conversationChannel) {
            conversationChannel.publish('typing_indicator', typingData);
        }

        // Timeout'u temizle
        if (this.typingTimeouts.has(receiverId)) {
            clearTimeout(this.typingTimeouts.get(receiverId));
            this.typingTimeouts.delete(receiverId);
        }
    }

    // MESAJ OKUNDU BİLDİRİMİ
    markMessageAsRead(messageId, conversationId, senderId) {
        if (!this.isAblyConnected) return;

        const readData = {
            message_id: messageId,
            conversation_id: conversationId,
            reader_id: window.currentUser.id,
            read_at: new Date().toISOString()
        };

        const senderChannel = this.ably.channels.get('user-messages-' + senderId);
        senderChannel.publish('message_read', readData);
    }

    // ÇEVRİMİÇİ DURUM GÜNCELLEME
    updateOnlineStatus(isOnline = true) {
        if (!this.isAblyConnected) return;

        const statusData = {
            user_id: window.currentUser.id,
            username: window.currentUser.username,
            is_online: isOnline,
            last_seen: new Date().toISOString()
        };

        // Durumu yayınla
        this.userChannel.publish('user_online', statusData);
    }

    // KONUŞMA ID'Sİ OLUŞTURMA
    getConversationId(user1Id, user2Id) {
        return [user1Id, user2Id].sort((a, b) => a - b).join('-');
    }

    // YARDIMCI FONKSİYONLAR
    isActiveConversation(userId) {
        return this.simpleModalReceiverId == userId;
    }

    addMessageToActiveConversation(message) {
        const messagesContainer = document.getElementById('simple-message-content');
        if (!messagesContainer) return;

        const messageElement = this.createMessageElement(message);
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    createMessageElement(message) {
        const isOwn = message.sender_id == window.currentUser.id;
        const alignment = isOwn ? 'right' : 'left';

        let content = '';
        if (message.message_type === 'text') {
            content = `<div class="message-text">${formatMessageContent(message.content)}</div>`;
        } else if (message.message_type === 'image') {
            content = `<img src="data:${message.mime_type};base64,${message.file_data}" alt="${message.file_name}" style="max-width: 300px; max-height: 300px; border-radius: 8px; cursor: pointer;" onclick="openMediaViewer(this.src)">`;
        } else if (message.message_type === 'video') {
            content = `
            <video controls style="max-width: 300px; max-height: 300px; border-radius: 8px;">
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
            content = `<a href="data:${message.mime_type};base64,${message.file_data}" download="${message.file_name}" class="btn-secondary" style="display: inline-block; margin: 5px 0;">📎 ${message.file_name}</a>`;
        }

        return `
        <div class="message-item" style="text-align: ${alignment}; margin-bottom: 15px;">
        <div style="display: inline-block; max-width: 80%; background: ${isOwn ? 'var(--accent-color)' : 'var(--fixed-bg)'}; color: ${isOwn ? 'white' : 'var(--main-text)'}; padding: 10px 15px; border-radius: 18px; word-wrap: break-word; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        ${!isOwn ? `<div style="font-size: 0.8em; font-weight: bold; margin-bottom: 5px; opacity: 0.8;">${message.sender_username}</div>` : ''}
        ${content}
        <div style="font-size: 0.7em; opacity: 0.6; margin-top: 5px; text-align: ${isOwn ? 'right' : 'left'};">
        ${new Date(message.timestamp).toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}
        </div>
        </div>
        </div>
        `;
    }

    showTypingIndicator(userId, isTyping) {
        const indicator = document.getElementById('typing-indicator');
        if (!indicator) return;

        if (isTyping && this.isActiveConversation(userId)) {
            indicator.style.display = 'block';
            indicator.innerHTML = `<em>${this.simpleModalReceiverUsername} yazıyor...</em>`;
        } else {
            indicator.style.display = 'none';
        }
    }

    updateUserOnlineStatus(userId, isOnline, lastSeen) {
        // Çevrimiçi durumu güncelleme mantığı buraya gelecek
        console.log(`User ${userId} is ${isOnline ? 'online' : 'offline'}`);
    }

    async loadConversationMessages(otherUserId) {
        try {
            const response = await fetch(SITE_BASE_URL + `core/fetch_messages.php?other_user_id=${otherUserId}`);
            const result = await response.json();

            const container = document.getElementById('simple-message-content');
            if (result.success && result.messages.length > 0) {
                container.innerHTML = result.messages.map(msg => this.createMessageElement(msg)).join('');
                container.scrollTop = container.scrollHeight;
            } else {
                container.innerHTML = '<p style="text-align: center; color: var(--main-text); opacity: 0.7; padding: 20px;">Henüz mesaj yok. İlk mesajı siz gönderin!</p>';
            }
        } catch (error) {
            console.error('Mesajlar yüklenirken hata:', error);
        }
    }

    // EVENT BINDING
    bindEvents() {
        // Basit mesaj modalı açma
        document.addEventListener('click', (e) => {
            const messageButton = e.target.closest('[data-simple-message]');
            if (messageButton) {
                const targetId = messageButton.getAttribute('data-target-id');
                const targetUsername = messageButton.getAttribute('data-target-username') || 'Kullanıcı';
                this.openSimpleMessageModal(targetId, targetUsername);
            }
        });

        // Yazıyor göstergesi için input event'leri
        document.addEventListener('input', (e) => {
            if (e.target.id === 'simple-message-input' && this.simpleModalReceiverId) {
                this.startTyping(this.simpleModalReceiverId);
            }
        });

        // Sayfa görünürlüğü değişikliği
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.updateOnlineStatus(false);
            } else {
                this.updateOnlineStatus(true);
            }
        });

        // Sayfa kapatma/kaybolma
        window.addEventListener('beforeunload', () => {
            this.updateOnlineStatus(false);
        });
    }

    initNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    // BASİT MESAJ MODALI FONKSİYONLARI
    openSimpleMessageModal(targetId, targetUsername) {
        this.simpleModalReceiverId = targetId;
        this.simpleModalReceiverUsername = targetUsername;

        // Modal içeriğini güncelle
        document.getElementById('simple-modal-username').textContent = targetUsername;
        document.getElementById('simple-message-input').value = '';
        this.clearSimpleModalFile();

        // Mesaj geçmişini yükle
        this.loadConversationMessages(targetId);

        // Modalı göster
        const modal = document.getElementById('simple-message-modal');
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        // Konuşma kanalına abone ol
        const conversationId = this.getConversationId(window.currentUser.id, targetId);
        this.subscribeToConversation(conversationId);

        // Input'a odaklan
        setTimeout(() => {
            document.getElementById('simple-message-input').focus();
        }, 100);
    }

    closeSimpleMessageModal() {
        // Yazmayı durdur
        if (this.simpleModalReceiverId) {
            this.stopTyping(this.simpleModalReceiverId);

            // Konuşma kanalından çık
            const conversationId = this.getConversationId(window.currentUser.id, this.simpleModalReceiverId);
            this.unsubscribeFromConversation(conversationId);
        }

        // Modalı kapat
        const modal = document.getElementById('simple-message-modal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Değişkenleri temizle
        this.simpleModalReceiverId = null;
        this.simpleModalReceiverUsername = null;
        this.clearSimpleModalFile();
    }

    // DOSYA İŞLEMLERİ
    handleSimpleModalFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;
        this.processFileForSimpleModal(file);
    }

    processFileForSimpleModal(file) {
        if (file.size > 2097152) {
            showNotification('Dosya boyutu 2MB\'dan küçük olmalı.', 'error');
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
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            this.simpleModalFileData = e.target.result.split(',')[1];
            this.simpleModalFileName = file.name;
            this.simpleModalFileType = file.type;

            document.getElementById('simple-modal-file-info').style.display = 'block';
            document.getElementById('simple-modal-file-name').textContent = `${file.name} (${formatFileSize(file.size)})`;

            showNotification(`"${file.name}" dosyası eklendi.`, 'success');
        };
        reader.readAsDataURL(file);
    }

    clearSimpleModalFile() {
        this.simpleModalFileData = null;
        this.simpleModalFileName = null;
        this.simpleModalFileType = null;

        const fileInput = document.getElementById('simple-modal-file-input');
        if (fileInput) fileInput.value = '';

        document.getElementById('simple-modal-file-info').style.display = 'none';
    }

    async sendSimpleMessage() {
        if (!this.simpleModalReceiverId) {
            showNotification('Alıcı bulunamadı.', 'error');
            return;
        }

        const input = document.getElementById('simple-message-input');
        const content = input.value.trim();

        if (!content && !this.simpleModalFileData) {
            showNotification('Lütfen mesaj yazın veya dosya ekleyin.', 'error');
            return;
        }

        const sendButton = document.querySelector('#simple-message-modal .btn-primary');
        const originalText = sendButton.textContent;
        sendButton.disabled = true;
        sendButton.textContent = '⏳ Gönderiliyor...';

        try {
            // Yazmayı durdur
            this.stopTyping(this.simpleModalReceiverId);

            let result;
            if (this.isAblyConnected) {
                result = await this.sendMessageRealTime(
                    this.simpleModalReceiverId,
                    content,
                    this.simpleModalFileData ? getMessageType(this.simpleModalFileType) : 'text',
                                                        this.simpleModalFileData,
                                                        this.simpleModalFileName,
                                                        this.simpleModalFileType
                );
            } else {
                result = await this.sendMessageHTTP(
                    this.simpleModalReceiverId,
                    content,
                    this.simpleModalFileData ? getMessageType(this.simpleModalFileType) : 'text',
                                                    this.simpleModalFileData,
                                                    this.simpleModalFileName,
                                                    this.simpleModalFileType
                );
            }

            if (result.success) {
                input.value = '';
                this.clearSimpleModalFile();
                showNotification('✅ Mesajınız gönderildi!', 'success');
            } else {
                showNotification('❌ ' + (result.message || 'Mesaj gönderilemedi'), 'error');
            }
        } catch (error) {
            console.error('Mesaj gönderme hatası:', error);
            showNotification('❌ Mesaj gönderilirken hata oluştu.', 'error');
        } finally {
            sendButton.disabled = false;
            sendButton.textContent = originalText;
        }
    }
}

// Global messaging instance'ı
const messagingSystem = new MessagingSystem();

// Eski fonksiyonlar için compatibility wrapper'lar
function openSimpleMessageModalFromProfile(targetId, targetUsername) {
    messagingSystem.openSimpleMessageModal(targetId, targetUsername);
}

function closeSimpleMessageModal() {
    messagingSystem.closeSimpleMessageModal();
}

function sendSimpleMessage() {
    messagingSystem.sendSimpleMessage();
}

function clearSimpleModalFile() {
    messagingSystem.clearSimpleModalFile();
}

// Mesaj bildirimini güncelle
async function updateMessageNotification() {
    if (!window.currentUser?.id) return;

    try {
        const response = await fetch(SITE_BASE_URL + 'core/get_unread_message_count.php');
        const result = await response.json();

        const messageBadge = document.getElementById('message-notification-badge');
        if (messageBadge) {
            if (result.unread_count > 0) {
                messageBadge.textContent = result.unread_count;
                messageBadge.style.display = 'inline';
            } else {
                messageBadge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Mesaj bildirimi güncelleme hatası:', error);
    }
}
