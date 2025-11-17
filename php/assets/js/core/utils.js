/**
 * Karakter maliyetini hesaplar
 */
function calculateChatChars(text) {
    return text.length;
}

/**
 * Dosya boyutunu formatlar
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Mesaj içeriğini formatlar
 */
function formatMessageContent(content) {
    if (!content) return '';
    return content
        .replace(/\n/g, '<br>')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/**
 * Profil fotoğrafını formatlar
 */
function formatProfilePicture(profilePic) {
    if (!profilePic || profilePic === 'default.png') {
        return SITE_BASE_URL + 'assets/img/default.png';
    }
    if (profilePic.startsWith('data:image')) {
        return profilePic;
    }
    return 'data:image/jpeg;base64,' + profilePic;
}

/**
 * Mesaj tipini belirler
 */
function getMessageType(mimeType) {
    if (mimeType.startsWith('image/')) return 'image';
    if (mimeType.startsWith('video/')) return 'video';
    if (mimeType.startsWith('audio/')) return 'audio';
    return 'file';
}

/**
 * SEPARATOR_MAP'teki ayırıcıların karakter maliyetlerini hesaplar
 */
function calculateSeparatorCharCosts() {
    const separatorSelect = DOM_ELEMENTS.separatorSelect;
    if (!separatorSelect) return;

    for (const key in SEPARATOR_MAP) {
        if (SEPARATOR_MAP.hasOwnProperty(key) && key !== 'none') {
            const separator = SEPARATOR_MAP[key];
            separator.length = calculateChatChars(separator.char);

            const option = separatorSelect.querySelector(`option[value="${key}"]`);
            if (option) {
                option.textContent = `${separator.name} (${separator.length} Karakter)`;
            }
        }
    }
}

// Yardımcı fonksiyonlar
class Utils {
    // Dosya boyutunu formatla
    static formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Kullanıcı adı formatla
    static formatUsername(username) {
        const turkishToEnglish = {
            'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u',
            'Ç': 'C', 'Ğ': 'G', 'İ': 'I', 'Ö': 'O', 'Ş': 'S', 'Ü': 'U'
        };

        let formatted = username;
        formatted = formatted.replace(/[çğışöüÇĞİŞÖÜ]/g, char => turkishToEnglish[char] || char);
        formatted = formatted.replace(/\s+/g, '-');
        formatted = formatted.replace(/[^a-zA-Z0-9_-]/g, '');
        formatted = formatted.replace(/-+/g, '-');
        formatted = formatted.replace(/^-+|-+$/g, '');
        return formatted;
    }

    // Profil fotoğrafı URL'sini formatla
    static formatProfilePicture(profilePicture) {
        if (!profilePicture || profilePicture === 'default.png') {
            return SITE_BASE_URL + 'assets/img/default.png';
        }
        if (profilePicture.startsWith('data:image')) {
            return profilePicture;
        }
        return 'data:image/jpeg;base64,' + profilePicture;
    }

    // Mesaj tipini belirle
    static getMessageType(mimeType) {
        if (mimeType.startsWith('image/')) return Constants.MESSAGE_TYPES.IMAGE;
        if (mimeType.startsWith('video/')) return Constants.MESSAGE_TYPES.VIDEO;
        if (mimeType.startsWith('audio/')) return Constants.MESSAGE_TYPES.AUDIO;
        return Constants.MESSAGE_TYPES.FILE;
    }

    // Platform emojisi al
    static getPlatformEmoji(platformName) {
        const emojiMap = {
            'YouTube': '📺',
            'Linktree': '🔴',
            'Twitter': '🐦',
            'Instagram': '📷',
            'TikTok': '🎵',
            'Discord': '💬',
            'Facebook': '👥',
            'Linkedin': '💼',
            'GitHub': '💻',
            'Telegram': '🤖',
            'Spotify': '🎵',
            'Whatsapp': '💚'
        };
        const lowerName = platformName.toLowerCase();
        for (const [key, emoji] of Object.entries(emojiMap)) {
            if (lowerName.includes(key.toLowerCase())) {
                return emoji;
            }
        }
        return '🔗';
    }

    // Emoji bozuksa fallback emoji kullan
    static getFallbackEmoji(platformName) {
        return this.getPlatformEmoji(platformName);
    }
}
