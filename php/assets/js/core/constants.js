// Sabitler ve yapılandırma
const EMOJI_JSON_URL = SITE_BASE_URL + 'assets/json/emoji.json';
const SAVE_DRAWING_URL = SITE_BASE_URL + 'core/save_drawing.php';
const LOAD_DRAWING_URL = SITE_BASE_URL + 'core/load_drawing.php';
const MAX_CHARACTERS = 200;
const MATRIX_HEIGHT = 20;
const DEFAULT_MATRIX_WIDTH = 11;
const SP_BS_MATRIX_WIDTH = 10;
const DEFAULT_HEART = '🖤';

// Global değişkenler
let matrix = [];
let selectedEmoji = null;
let emojiCategories = {};
let currentCategory = null;
let allConversations = [];
let currentMessageReceiver = null;

// Sabit değerler
const Constants = {
    MAX_CHARACTERS: 200,
    SEPARATORS: {
        NONE: 'none',
        ZWNJ: 'ZWNJ',
        ZWSP: 'ZWSP',
        ZWJ: 'ZWJ',
        WJ: 'WJ',
        SHY: 'SHY',
        HAIR: 'HAIR',
        LRM: 'LRM',
        RLM: 'RLM',
        ZWNBSP: 'ZWNBSP',
        LRE: 'LRE',
        RLE: 'RLE',
        PDF: 'PDF',
        LRI: 'LRI',
        RLI: 'RLI',
        PDI: 'PDI',
        CGJ: 'CGJ',
        SP_BS: 'SP_BS'
    },
    GAME_TYPES: {
        CHESS: 'chess',
        REVERSI: 'reversi',
        TAVLA: 'tavla'
    },
    MESSAGE_TYPES: {
        TEXT: 'text',
        IMAGE: 'image',
        VIDEO: 'video',
        AUDIO: 'audio',
        FILE: 'file'
    },
    GAME_NAMES: {
        'chess': 'Satranç',
        'reversi': 'Reversi',
        'tavla': 'Tavla'
    },
    GAME_EMOJIS: {
        'chess': '♟️',
        'reversi': '🔴',
        'tavla': '🎲'
    }
};

// Ayırıcı karakterler
const SEPARATOR_MAP = {
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
};

let currentMatrixWidth = DEFAULT_MATRIX_WIDTH;
let selectedHeart = { emoji: DEFAULT_HEART, chars: 0, name: 'black heart' };

// DOM element referansları
const DOM_ELEMENTS = {
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

    // Drawing elements
    DRAWING_LIST_ELEMENT: document.getElementById('user-drawing-list'),
    PAGINATION_ELEMENT: document.getElementById('pagination'),
    FOLLOWING_FEED_ELEMENT: document.getElementById('following-feed-list'),

    // Message elements
    replyInput: document.getElementById('reply-input'),
    replyFileInput: document.getElementById('reply-file-input'),
    conversationsContainer: document.getElementById('conversations-container'),
    conversationMessages: document.getElementById('conversation-messages'),
    conversationWith: document.getElementById('conversation-with'),
    replySection: document.getElementById('reply-section'),

    // Profile board elements
    boardCommentInput: document.getElementById('boardCommentInput'),
    boardFileInput: document.getElementById('boardFileInput'),
    boardFileInfo: document.getElementById('boardFileInfo'),
    boardFileName: document.getElementById('boardFileName'),
    boardCommentsList: document.getElementById('board-comments-list'),

    // Fallback mekanizması
    _createFallback: function() {
        // Notification container fallback
        if (!this.notification) {
            this.notification = document.createElement('div');
            this.notification.id = 'notification';
            this.notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000;';
            document.body.appendChild(this.notification);
        }

        // Diğer kritik elementler için fallback
        const criticalElements = ['matrixTable', 'currentCharsSpan', 'charWarningSpan'];
        criticalElements.forEach(element => {
            if (!this[element]) {
                console.warn(`${element} bulunamadı, fallback oluşturuluyor...`);
                this[element] = document.createElement('div');
            }
        });

        return this;
    }
};

// API Endpoints
const API_ENDPOINTS = {
    // User endpoints
    login: '/core/login.php',
    register: '/core/register.php',
    logout: '/core/logout.php',

    // Drawing endpoints
    saveDrawing: '/core/save_drawing.php',
    listDrawings: '/core/list_drawings.php',
    fetchDrawing: '/core/fetch_drawing.php',
    fetchUserDrawings: '/core/fetch_user_drawings.php',

    // Game endpoints
    createGame: '/core/create_game.php',
    joinGame: '/core/join_game.php',
    makeMove: '/core/make_move.php',
    activeGames: '/core/get_active_games.php',

    // Social endpoints
    followUser: '/actions/follow_action.php',
    sendMessage: '/actions/message_action.php',
    postComment: '/actions/comment_action.php',
    blockUser: '/actions/block_action.php',

    // Profile endpoints
    profilePicture: '/core/upload_profile_picture.php',
    updateUsername: '/core/update_username.php',
    socialLinks: '/core/profile_social_links.php',
    getSocialLinks: '/core/get_user_social_links.php',
    getSocialPlatforms: '/core/get_social_platforms.php',
    fetchComments: '/core/fetch_comments.php',
    fetchFollowRequests: '/core/fetch_follow_requests.php',
    manageFollowRequest: '/core/manage_follow_request.php',
    getUserActivities: '/core/get_user_activities.php'
};

// Game Constants
const GAME_CONSTANTS = {
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
};

// Drawing Constants
const DRAWING_CONSTANTS = {
    CANVAS: {
        WIDTH: 800,
        HEIGHT: 600,
        BACKGROUND_COLOR: '#FFFFFF'
    },
    BRUSH: {
        MIN_SIZE: 1,
        MAX_SIZE: 50,
        DEFAULT_SIZE: 5
    },
    COLORS: {
        BLACK: '#000000',
        WHITE: '#FFFFFF',
        RED: '#FF0000',
        GREEN: '#00FF00',
        BLUE: '#0000FF',
        YELLOW: '#FFFF00'
    }
};

// Notification Constants
const NOTIFICATION_CONSTANTS = {
    TYPES: {
        SUCCESS: 'success',
        ERROR: 'error',
        WARNING: 'warning',
        INFO: 'info'
    },
    DURATION: {
        SHORT: 3000,
        MEDIUM: 5000,
        LONG: 10000
    },
    POSITIONS: {
        TOP_RIGHT: 'top-right',
        TOP_LEFT: 'top-left',
        BOTTOM_RIGHT: 'bottom-right',
        BOTTOM_LEFT: 'bottom-left'
    }
};

// DOM yüklendikten sonra fallback'leri oluştur
document.addEventListener('DOMContentLoaded', function() {
    DOM_ELEMENTS._createFallback();

    // Global değişkenleri kontrol et
    if (typeof window.SITE_BASE_URL === 'undefined') {
        window.SITE_BASE_URL = 'https://flood.page.gd/';
    }

    if (typeof window.currentUser === 'undefined') {
        window.currentUser = {
            id: null,
            username: null,
            role: 'user'
        };
    }
});
