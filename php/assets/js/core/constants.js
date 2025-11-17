// assets/js/core/constants.js

// KRİTİK: Global değişken güvenliği
(function() {
    // SITE_BASE_URL güvenli tanımlama
    if (typeof window.SITE_BASE_URL === 'undefined') {
        console.warn('⚠️ SITE_BASE_URL tanımlı değil, otomatik belirleniyor...');
        window.SITE_BASE_URL = window.location.protocol + '//' + window.location.host + '/';
    }

    // APP_DATA güvenli tanımlama
    if (typeof window.APP_DATA === 'undefined') {
        window.APP_DATA = {
            isLoggedIn: false,
 userRole: 'user',
 currentUserId: null,
 totalViews: 0
        };
    }

    // currentUser güvenli tanımlama
    if (typeof window.currentUser === 'undefined') {
        window.currentUser = {
            id: null,
 username: null,
 role: 'user',
 isAdmin: false
        };
    }
})();

// SABİT DEĞERLER - Değiştirilemez
const EMOJI_JSON_URL = SITE_BASE_URL + 'assets/json/emoji.json';
const SAVE_DRAWING_URL = SITE_BASE_URL + 'core/save_drawing.php';
const LOAD_DRAWING_URL = SITE_BASE_URL + 'core/load_drawing.php';
const MAX_CHARACTERS = 200;
const MATRIX_HEIGHT = 20;
const DEFAULT_MATRIX_WIDTH = 11;
const SP_BS_MATRIX_WIDTH = 10;
const DEFAULT_HEART = '🖤';

// Global uygulama değişkenleri - Dikkatli kullanılmalı
let matrix = [];
let selectedEmoji = null;
let emojiCategories = {};
let currentCategory = null;
let allConversations = [];
let currentMessageReceiver = null;

// Ayırıcı karakter sabitleri
const SEPARATOR_MAP = Object.freeze({
    'none': { char: '', length: 0, name: 'Hiçbiri' },
    'ZWNJ': { char: '\u200C', name: 'ZWNJ' },
    'ZWSP': { char: '\u200B', name: 'ZWSP' },
    'ZWJ': { char: '\u200D', name: 'ZWJ' },
    'WJ': { char: '\u2060', name: 'WJ' },
    'SHY': { char: '\u00AD', name: 'SHY' },
    'HAIR': { char: '\u200A', name: 'Hair Space' },
    'LRM': { char: '\u200E', name: 'LRM' },
    'RLM': { char: '\u200F', name: 'RLM' },
    'ZWNBSP': { char: '\uFEFF', name: 'ZWNBSP' },
    'LRE': { char: '\u202A', name: 'LRE' },
    'RLE': { char: '\u202B', name: 'RLE' },
    'PDF': { char: '\u202C', name: 'PDF' },
    'LRI': { char: '\u2066', name: 'LRI' },
    'RLI': { char: '\u2067', name: 'RLI' },
    'PDI': { char: '\u2069', name: 'PDI' },
    'CGJ': { char: '\u034F', name: 'CGJ' },
    'SP_BS': { char: '\u0020\u0008', name: 'Space + Backspace' }
});

// Matris değişkenleri
let currentMatrixWidth = DEFAULT_MATRIX_WIDTH;
let selectedHeart = { emoji: DEFAULT_HEART, chars: 1, name: 'Siyah Kalp' };

// DOM element referansları - FONKSİYON olarak
function getDomElements() {
    const elements = {
        // Matrix elements
        firstRowLengthInput: document.getElementById('firstRowLength'),
        matrixTable: document.getElementById('matrix'),
        currentCharsSpan: document.getElementById('currentChars'),
        charWarningSpan: document.getElementById('charWarning'),
        matrixContainer: document.getElementById('matrix-container'),

        // Modal elements
        guideModal: document.getElementById('guide-modal'),
        showGuideButton: document.getElementById('showGuideButton'),
        closeGuideButton: document.getElementById('close-guide-btn'),
        confirmModal: document.getElementById('confirm-modal'),
        modalTitle: document.getElementById('modal-title'),
        modalMessage: document.getElementById('modal-message'),
        modalConfirm: document.getElementById('modal-confirm'),
        modalCancel: document.getElementById('modal-cancel'),

        // Button elements
        updateMatrixButton: document.getElementById('updateMatrixButton'),
        copyButton: document.getElementById('copyButton'),
        importButton: document.getElementById('importButton'),
        saveButton: document.getElementById('saveButton'),
        loadButton: document.getElementById('loadButton'),
        fileInput: document.getElementById('fileInput'),
        clearButton: document.getElementById('clearButton'),

        // Emoji elements
        colorOptionsContainer: document.getElementById('color-options-container'),
        categoryTabsContainer: document.getElementById('category-tabs'),
        currentBrushEmoji: document.getElementById('current-brush-emoji'),
        currentBrushName: document.getElementById('current-brush-name'),
        separatorSelect: document.getElementById('separator-select'),

        // Notification element
        notification: document.getElementById('notification'),
        messageBadge: document.getElementById('message-notification-badge')
    };

    // Fallback mekanizması - kritik elementler
    if (!elements.notification) {
        elements.notification = document.createElement('div');
        elements.notification.id = 'notification';
        elements.notification.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;padding:10px;border-radius:5px;display:none;';
        document.body.appendChild(elements.notification);
    }

    return elements;
}

// API Endpoints
const API_ENDPOINTS = Object.freeze({
    // User endpoints
    login: 'auth/login.php',
    register: 'auth/register.php',
    logout: 'auth/logout.php',

    // Drawing endpoints
    saveDrawing: 'core/save_drawing.php',
    listDrawings: 'core/list_drawings.php',
    fetchDrawing: 'core/fetch_drawing.php',
    fetchUserDrawings: 'core/fetch_user_drawings.php',

    // Game endpoints
    createGame: 'core/create_game.php',
    joinGame: 'core/join_game.php',
    makeMove: 'core/make_move.php',
    activeGames: 'games/get_active_games.php',

    // Social endpoints
    followUser: 'actions/follow_action.php',
    sendMessage: 'actions/message_action.php',
    postComment: 'actions/comment_action.php',
    blockUser: 'actions/block_action.php',

    // Profile endpoints
    profilePicture: 'core/upload_profile_picture.php',
    updateUsername: 'core/update_username.php',
    socialLinks: 'core/profile_social_links.php',
    getSocialLinks: 'core/get_user_social_links.php',
    getSocialPlatforms: 'core/get_social_platforms.php',
    fetchComments: 'core/fetch_comments.php',
    fetchFollowRequests: 'core/fetch_follow_requests.php',
    manageFollowRequest: 'core/manage_follow_request.php',
    getUserActivities: 'core/get_user_activities.php'
});

// Game Constants
const GAME_CONSTANTS = Object.freeze({
    CHESS: {
        BOARD_SIZE: 8,
        PIECES: {
            PAWN: '♟️',
            ROOK: '♜',
            KNIGHT: '♞',
            BISHOP: '♝',
            QUEEN: '♛',
            KING: '♚'
        }
    },
    REVERSI: {
        BOARD_SIZE: 8,
        PIECES: {
            BLACK: '⚫',
            WHITE: '⚪',
            EMPTY: '⬜'
        }
    },
    TAVLA: {
        BOARD_SIZE: 24,
        PIECES: {
            BLACK: '⚫',
            WHITE: '⚪'
        }
    }
});

// Sistem sabitleri
const SYSTEM_CONSTANTS = Object.freeze({
    VERSION: '1.0.0',
    MAX_FILE_SIZE: 2097152, // 2MB
    ALLOWED_FILE_TYPES: [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm', 'audio/mpeg', 'audio/wav',
        'application/pdf', 'text/plain'
    ],
    REQUEST_TIMEOUT: 10000,
    DEBOUNCE_DELAY: 300
});

// Hata mesajları
const ERROR_MESSAGES = Object.freeze({
    NETWORK_ERROR: 'Ağ hatası. Lütfen bağlantınızı kontrol edin.',
    SERVER_ERROR: 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.',
    UNAUTHORIZED: 'Bu işlem için giriş yapmalısınız.',
    PERMISSION_DENIED: 'Bu işlemi yapmaya yetkiniz yok.',
    INVALID_FILE: 'Geçersiz dosya türü veya boyutu.'
});

console.log('✅ Constants.js başarıyla yüklendi');
