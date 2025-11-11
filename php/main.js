// main.js – index.php ile %100 UYUMLU, TÜM ÖZELLİKLER ÇALIŞIR
const SITE_BASE_URL = 'https://flood.page.gd/'
const EMOJI_JSON_URL = SITE_BASE_URL + 'emoji.json';
const SAVE_DRAWING_URL = SITE_BASE_URL + 'save_drawing.php';
const LOAD_DRAWING_URL = SITE_BASE_URL + 'load_drawing.php';
const MAX_CHARACTERS = 200;
const MATRIX_HEIGHT = 20;
const DEFAULT_MATRIX_WIDTH = 11;
const SP_BS_MATRIX_WIDTH = 10;
const DEFAULT_HEART = '🖤';

let matrix = [];
let selectedEmoji = null;
let emojiCategories = {};
let currentCategory = null;

// Ayırıcı karakterlerin char ve name bilgileri
let SEPARATOR_MAP = {
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

// Global değişkenler
let currentMatrixWidth = DEFAULT_MATRIX_WIDTH;
let selectedHeart = { emoji: DEFAULT_HEART, chars: 0, name: 'black heart' };

// --- DOM ELEMENTLERİ ---
const firstRowLengthInput = document.getElementById('firstRowLength');
const matrixTable = document.getElementById('matrix');
const currentCharsSpan = document.getElementById('currentChars');
const charWarningSpan = document.getElementById('charWarning');
const guideModal = document.getElementById('guide-modal');
const showGuideButton = document.getElementById('showGuideButton');
const closeGuideButton = document.getElementById('close-guide-btn');
const updateMatrixButton = document.getElementById('updateMatrixButton');
const copyButton = document.getElementById('copyButton');
const importButton = document.getElementById('importButton');
const saveButton = document.getElementById('saveButton');
const loadButton = document.getElementById('loadButton');
const fileInput = document.getElementById('fileInput');
const clearButton = document.getElementById('clearButton');
const colorOptionsContainer = document.getElementById('color-options-container');
const categoryTabsContainer = document.getElementById('category-tabs');
const notification = document.getElementById('notification');
const confirmModal = document.getElementById('confirm-modal');
const modalTitle = document.getElementById('modal-title');
const modalMessage = document.getElementById('modal-message');
const modalConfirm = document.getElementById('modal-confirm');
const modalCancel = document.getElementById('modal-cancel');
const currentBrushEmoji = document.getElementById('current-brush-emoji');
const currentBrushName = document.getElementById('current-brush-name');
const separatorSelect = document.getElementById('separator-select');

// Liste görüntüleme için DOM elementleri
const DRAWING_LIST_ELEMENT = document.getElementById('drawing-list');
const PAGINATION_ELEMENT = document.getElementById('pagination');
const FOLLOWING_FEED_ELEMENT = document.getElementById('following-feed-list');

/**
 * Karakter maliyetini hesaplar - UTF-16 kod birimi uzunluğu
 */
function calculateChatChars(text) {
    return text.length;
}

/**
 * SEPARATOR_MAP'teki ayırıcıların karakter maliyetlerini dinamik olarak hesaplar.
 */
function calculateSeparatorCharCosts() {
    const separatorSelect = document.getElementById('separator-select');

    for (const key in SEPARATOR_MAP) {
        if (SEPARATOR_MAP.hasOwnProperty(key) && key !== 'none') {
            const separator = SEPARATOR_MAP[key];
            // length'i, char'ın gerçek karakter maliyetiyle güncelle
            separator.length = calculateChatChars(separator.char);

            // Dropdown metnini maliyetle güncelle
            const option = separatorSelect.querySelector(`option[value="${key}"]`);
            if (option) {
                option.textContent = `${separator.name} (${separator.length} Karakter)`;
            }
        }
    }
}

// --- TEMEL FONKSİYONLAR ---

function showNotification(message, type = 'info', duration = 3000) {
    if (!notification) {
        console.log('Notification:', message);
        return;
    }

    notification.textContent = message;
    notification.className = '';
    notification.classList.add(type);
    notification.classList.add('show');

    setTimeout(() => {
        notification.classList.remove('show');
    }, duration);
}

function showConfirm(title, message) {
    return new Promise((resolve) => {
        if (!confirmModal) {
            const userConfirmed = confirm(`${title}\n${message}\n\nEvet için OK, İptal için Cancel'a basın.`);
            resolve(userConfirmed);
            return;
        }

        modalTitle.textContent = title;
        modalMessage.textContent = message;
        confirmModal.classList.add('show');

        const confirmHandler = () => {
            confirmModal.classList.remove('show');
            modalConfirm.removeEventListener('click', confirmHandler);
            modalCancel.removeEventListener('click', cancelHandler);
            resolve(true);
        };

        const cancelHandler = () => {
            confirmModal.classList.remove('show');
            modalConfirm.removeEventListener('click', confirmHandler);
            modalCancel.removeEventListener('click', cancelHandler);
            resolve(false);
        };

        modalConfirm.onclick = confirmHandler;
        modalCancel.onclick = cancelHandler;
    });
}

async function loadEmojis() {
    try {
        const response = await fetch(EMOJI_JSON_URL);
        if (!response.ok) {
            throw new Error(`HTTP Hata kodu: ${response.status}`);
        }
        const rawEmojis = await response.json();

        let processedCategories = {};

        // Eğeri response array değilse, doğrudan kullan
        const emojiArray = Array.isArray(rawEmojis) ? rawEmojis : Object.values(rawEmojis);

        emojiArray.forEach(item => {
            // Kategori ismini düzenle (İlk harf büyük, diğerleri küçük)
            const categoryName = (item.category || "Diğer").charAt(0).toUpperCase() + (item.category || "Diğer").slice(1);
            const emojiName = item.description || item.names?.[0] || item.name || 'İsimsiz Emoji';

            if (!processedCategories[categoryName]) {
                processedCategories[categoryName] = {};
            }

            const charCost = calculateChatChars(item.emoji);

            processedCategories[categoryName][emojiName] = {
                emoji: item.emoji,
                chars: charCost,
                name: emojiName
            };
        });

        emojiCategories = processedCategories;

        // Başlangıçta en çok emojisi olan kategoriyi seç
        const sortedCategories = Object.keys(emojiCategories).sort((a, b) =>
        Object.keys(emojiCategories[b]).length - Object.keys(emojiCategories[a]).length
        );
        currentCategory = sortedCategories[0] || Object.keys(emojiCategories)[0];

        // Başlangıç emojisini güncel, doğru maliyetli objeyle eşleştir
        const heartData = Object.values(emojiCategories)
        .flatMap(category => Object.values(category))
        .find(data => data.emoji === DEFAULT_HEART);

        if (heartData) {
            selectedHeart = heartData;
        } else {
            // Fallback: İlk emojiyi seç
            const firstEmoji = Object.values(emojiCategories)[0] ? Object.values(Object.values(emojiCategories)[0])[0] : null;
            if (firstEmoji) {
                selectedHeart = firstEmoji;
            }
        }

        showNotification(`✅ ${emojiArray.length} adet emoji başarıyla yüklendi ve maliyetleri hesaplandı!`, 'success');

    } catch (error) {
        console.error("Emoji yükleme hatası:", error);
        showNotification('❌ Emoji yüklenemedi. Emoji verisi endpointinin mevcut ve doğru formatta olduğundan emin olun.', 'error', 8000);

        // Fallback: Basit bir emoji seti
        emojiCategories = {
            'Kalpler': {
                'Siyah Kalp': { emoji: '🖤', chars: 1, name: 'Siyah Kalp' },
                'Kırmızı Kalp': { emoji: '❤️', chars: 1, name: 'Kırmızı Kalp' },
                'Mavi Kalp': { emoji: '💙', chars: 1, name: 'Mavi Kalp' },
                'Yeşil Kalp': { emoji: '💚', chars: 1, name: 'Yeşil Kalp' }
            }
        };
        currentCategory = 'Kalpler';
        selectedHeart = emojiCategories['Kalpler']['Siyah Kalp'];
    }
}

/**
 * Karakter sayımını hesaplar ve bütçeyi aşan hücreleri otomatik olarak kırpar (clipped).
 */
function calculateAndClip(allCells) {
    let totalEmojiCharCost = 0;
    let totalEmojis = 0;
    let multiCharEmojisUsed = 0;

    const selectedSeparator = SEPARATOR_MAP[separatorSelect.value];

    // Sadece sabit olmayan (fixed) hücreleri al. Fixed hücreler çıktıya dahil edilmez.
    let editableCells = Array.from(allCells).filter(cell => !cell.classList.contains('fixed'));
    let totalEditableCount = editableCells.length;

    // V6.5 Düzeltmesi: Giriş değeri, çizilebilir piksel sayısıdır.
    const drawablePixelCount = parseInt(firstRowLengthInput.value) || 0;
    const permanentFixedCount = currentMatrixWidth - drawablePixelCount;

    let clippedCount = 0;

    // Kırpmadan önce tüm kırpma işaretlerini temizle
    editableCells.forEach(cell => cell.classList.remove('clipped'));

    let currentRow = -1;
    let emojisInCurrentRow = 0;

    // İkinci döngü: Karakter bütçesini kontrol et ve kırpma noktasını bul/uygula
    for (let i = 0; i < totalEditableCount; i++) {
        const cell = editableCells[i];
        const newRowIndex = parseInt(cell.getAttribute('data-row'));

        // Yeni satıra geçiş kontrolü
        if (newRowIndex !== currentRow) {
            currentRow = newRowIndex;
            emojisInCurrentRow = 0; // Yeni satırda emoji sayısı sıfırlanır
        }

        // Ayırıcı Maliyeti (Sadece emojilerin arasına konur)
        let separatorCost = 0;

        // Bulunduğumuz satırdaki toplam çizilebilir hücre sayısı
        let effectiveRowWidth = (currentRow === 0)
        ? (currentMatrixWidth - permanentFixedCount)
        : currentMatrixWidth;

        // Ayırıcı sadece ilk emojiden sonra (emojisInCurrentRow > 0) konur.
        if (selectedSeparator.length > 0 && emojisInCurrentRow > 0 && (emojisInCurrentRow < effectiveRowWidth)) {
            separatorCost = selectedSeparator.length;
        }

        // Hücrenin maliyeti (Drawn state'e göre)
        const emojiCost = parseInt(cell.getAttribute('data-chars') || '1');

        // Toplam maliyet (Emoji + Ayırıcı)
        const combinedCost = emojiCost + separatorCost;

        if (totalEmojiCharCost + combinedCost <= MAX_CHARACTERS) {
            // Bütçe dahilinde
            totalEmojiCharCost += combinedCost;
            totalEmojis++;
            emojisInCurrentRow++;

            if (emojiCost > 1) {
                multiCharEmojisUsed++;
            }
        } else {
            // Bütçeyi aşıyor, bu hücreyi ve kalanları kırp
            clippedCount = totalEditableCount - i;

            // Bu hücreden başlayarak tüm kalanları kırp
            for(let j = i; j < totalEditableCount; j++) {
                editableCells[j].classList.add('clipped');
            }
            break;
        }
    }

    // Nihai toplam karakter sayısı (ASLA 200'ü aşmaz)
    const totalOutputCharCount = totalEmojiCharCost;

    return {
        totalEmojiCharCost: totalOutputCharCount,
        totalEmojis: totalEmojis,
        multiCharEmojisUsed,
        clippedCount: clippedCount,
        totalOutputCharCount: totalOutputCharCount,
    };
}

// --- MATRİS FONKSİYONLARI ---

function createMatrix() {
    // Matris genişliğini seçili ayırıcıya göre ayarla
    currentMatrixWidth = (separatorSelect.value === 'SP_BS') ? SP_BS_MATRIX_WIDTH : DEFAULT_MATRIX_WIDTH;

    if (!matrixTable) {
        console.error('Matrix table element not found!');
        return;
    }

    matrixTable.innerHTML = '';

    // V6.5 Düzeltmesi: Giriş değeri, çizilebilir piksel sayısıdır.
    const drawablePixelCount = parseInt(firstRowLengthInput.value) || 5;
    // permanentFixedCount, sabit (X) hücre sayısıdır.
    let permanentFixedCount = currentMatrixWidth - drawablePixelCount;

    if (drawablePixelCount > currentMatrixWidth) {
        firstRowLengthInput.value = currentMatrixWidth;
        permanentFixedCount = 0;
    } else if (drawablePixelCount < 0) {
        firstRowLengthInput.value = 0;
        permanentFixedCount = currentMatrixWidth;
    }

    if (firstRowLengthInput) {
        firstRowLengthInput.setAttribute('max', currentMatrixWidth.toString());
    }

    const defaultHeartChars = selectedHeart.chars;

    for (let rowIndex = 0; rowIndex < MATRIX_HEIGHT; rowIndex++) {
        const row = matrixTable.insertRow();

        for (let colIndex = 0; colIndex < currentMatrixWidth; colIndex++) {
            const cell = row.insertCell();
            cell.setAttribute('data-row', rowIndex);
            cell.setAttribute('data-col', colIndex);

            // Sabitlemeyi SADECE İLK SATIRDA uygula
            const isPermanentlyFixed = (rowIndex === 0 && colIndex < permanentFixedCount);

            if (isPermanentlyFixed) {
                cell.innerHTML = '❌';
                cell.classList.add('fixed');
                cell.setAttribute('data-chars', '0');
            } else {
                // Çizilebilir alan başlangıçta varsayılan emojiyle dolar
                cell.innerHTML = selectedHeart.emoji;
                cell.setAttribute('data-chars', defaultHeartChars.toString());
                cell.addEventListener('click', () => {
                    handleCellClick(cell);
                });
                cell.classList.remove('clipped');
            }
        }
    }

    updateCharacterCount();
}

function handleCellClick(cell) {
    // Sadece sabit veya kırpılmış değilse çalıştır
    if (cell.classList.contains('fixed') || cell.classList.contains('clipped')) return;

    const newCost = selectedHeart.chars;

    cell.innerHTML = selectedHeart.emoji;
    cell.setAttribute('data-chars', newCost.toString());

    updateCharacterCount();
}

function updateCharacterCount() {
    if (!matrixTable) return;

    const allCells = matrixTable.querySelectorAll('td');
    const stats = calculateAndClip(allCells);
    const totalOutputCharCount = stats.totalOutputCharCount;

    if (currentCharsSpan) {
        currentCharsSpan.textContent = totalOutputCharCount;
        currentCharsSpan.style.color = (totalOutputCharCount < MAX_CHARACTERS) ? 'var(--accent-color)' : '#28a745';
    }

    // UYARI METNİ GÜNCELLEME
    let warningText = '';
    const selectedSeparator = SEPARATOR_MAP[separatorSelect.value];

    if (selectedSeparator.length > 0 && stats.totalEmojis > 0) {
        const totalSeparators = stats.totalEmojis > 0 ? stats.totalEmojis - 1 : 0;
        const separatorCharCost = totalSeparators * selectedSeparator.length;

        warningText += `${selectedSeparator.name} (${separatorCharCost} Karakter Maliyeti) kullanılıyor.`;
    }

    if (stats.multiCharEmojisUsed > 0) {
        if (warningText) warningText += ' | ';
        warningText += `${stats.multiCharEmojisUsed} adet çok karakterli emoji kullanılıyor.`;
    }

    if (stats.clippedCount > 0) {
        if (warningText) warningText += ' | ';
        warningText += `ÇIKTI LİMİTİ NEDENİYLE SON ${stats.clippedCount} HÜCRE OTOMATİK KIRPILDI.`;
    }

    if (charWarningSpan) {
        if (warningText) {
            charWarningSpan.textContent = ` - ⚠️ ${warningText}`;
            charWarningSpan.style.display = 'inline';
            charWarningSpan.style.color = stats.clippedCount > 0 ? '#e0a800' : 'var(--main-text)';
        } else {
            charWarningSpan.style.display = 'none';
        }
    }
}

// --- PALET VE SEKMELER ---

function updateSelectedEmojiDisplay() {
    if (!currentBrushEmoji || !currentBrushName) return;

    currentBrushEmoji.textContent = selectedHeart.emoji;
    currentBrushName.textContent = ` (${selectedHeart.name} - ${selectedHeart.chars} Karakter Maliyeti)`;

    document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected-color'));

    const activeOption = document.querySelector(`[data-color="${selectedHeart.name}"][data-category-name="${currentCategory}"]`);
    if (activeOption) {
        activeOption.classList.add('selected-color');
    }
}

function createCategoryTabs() {
    if (!categoryTabsContainer) return;

    categoryTabsContainer.innerHTML = '';

    if (!emojiCategories || Object.keys(emojiCategories).length === 0) return;

    Object.keys(emojiCategories).forEach(categoryName => {
        const tabButton = document.createElement('button');
        tabButton.className = 'category-tab';
        tabButton.textContent = `${categoryName} (${Object.keys(emojiCategories[categoryName]).length})`;
        tabButton.setAttribute('data-category', categoryName);

        if (categoryName === currentCategory) {
            tabButton.classList.add('active');
        }

        tabButton.addEventListener('click', () => {
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            tabButton.classList.add('active');
            currentCategory = categoryName;
            createPalette();
        });

        categoryTabsContainer.appendChild(tabButton);
    });
}

function createPalette() {
    if (!colorOptionsContainer) return;

    colorOptionsContainer.innerHTML = '';

    if (!currentCategory || !emojiCategories[currentCategory]) {
        return;
    }

    const emojisToShow = emojiCategories[currentCategory];

    Object.entries(emojisToShow).forEach(([name, emojiData]) => {
        const span = document.createElement('span');
        span.className = 'color-option';

        if (emojiData.chars > 1) {
            span.classList.add('multi-char-emoji');
            span.setAttribute('data-chars', emojiData.chars.toString());
        }

        span.innerHTML = emojiData.emoji;
        span.title = `${name} (${emojiData.chars} karakter maliyeti)`;
        span.setAttribute('data-color', name);
        span.setAttribute('data-chars', emojiData.chars.toString());
        span.setAttribute('data-category-name', currentCategory);

        if (emojiData.emoji === selectedHeart.emoji && emojiData.name === selectedHeart.name) {
            span.classList.add('selected-color');
        }

        span.addEventListener('click', () => {
            selectedHeart = emojiData;
            updateSelectedEmojiDisplay();
        });

        colorOptionsContainer.appendChild(span);
    });

    updateSelectedEmojiDisplay();
}

// --- İÇE/DIŞA AKTARMA FONKSİYONLARI ---

function getDrawingText(formatted = false) {
    if (!matrixTable) return '';

    let result = [];
    const rows = matrixTable.rows;
    const separatorCode = SEPARATOR_MAP[separatorSelect.value].char;
    const separator = formatted ? '' : separatorCode;

    for (let i = 0; i < rows.length; i++) {
        let emojisInRow = [];
        const cells = rows[i].cells;
        let isRowClipped = false;
        let rowHasEmoji = false;

        for (let j = 0; j < cells.length; j++) {
            const cell = cells[j];

            if (cell.classList.contains('fixed')) {
                continue;
            }

            if (cell.classList.contains('clipped')) {
                isRowClipped = true;
                break;
            }

            emojisInRow.push(cell.innerHTML);
            rowHasEmoji = true;
        }

        if (rowHasEmoji) {
            let rowText = emojisInRow.join(separator);
            result.push(rowText);
        }

        if (isRowClipped) {
            break;
        }
    }

    return formatted ? result.join('\n') : result.join('');
}

function applyDrawingText(text) {
    if (!matrixTable) return false;

    const textWithoutLineBreaks = text.replace(/[\n\r]/g, '');

    // 1. Ayırıcıyı tespit et
    let detectedSeparatorKey = 'none';
    const keysToCheck = Object.keys(SEPARATOR_MAP).reverse().filter(k => k !== 'none');

    for (const key of keysToCheck) {
        const separatorData = SEPARATOR_MAP[key];
        if (separatorData.char && textWithoutLineBreaks.includes(separatorData.char)) {
            detectedSeparatorKey = key;
            break;
        }
    }

    // 2. Dropdown'u otomatik seç
    const isSeparatorChange = separatorSelect.value !== detectedSeparatorKey;
    separatorSelect.value = detectedSeparatorKey;

    // Eğer ayırıcı seçimi matris boyutunu değiştiriyorsa, matrisi yeniden çiz.
    const newWidth = (separatorSelect.value === 'SP_BS') ? SP_BS_MATRIX_WIDTH : DEFAULT_MATRIX_WIDTH;
    const currentDisplayedWidth = matrixTable.rows.length > 0 ? matrixTable.rows[0].cells.length : DEFAULT_MATRIX_WIDTH;

    if (newWidth !== currentDisplayedWidth || isSeparatorChange) {
        createMatrix();
    }

    // 3. Ayırıcıyı temizle
    const selectedSeparator = SEPARATOR_MAP[detectedSeparatorKey];
    const cleanText = textWithoutLineBreaks.split(selectedSeparator.char).join('');

    // 4. Emojileri doldur ve bütçeyi koru
    const allEmojis = Object.values(emojiCategories)
    .flatMap(category => Object.values(category))
    .sort((a, b) => b.emoji.length - a.emoji.length);

    let charIndex = 0;
    const allCells = matrixTable.querySelectorAll('td');
    let editableCells = Array.from(allCells).filter(cell => !cell.classList.contains('fixed'));
    let totalEditableCount = editableCells.length;

    const defaultHeartChars = selectedHeart.chars;

    for (let i = 0; i < totalEditableCount; i++) {
        const cell = editableCells[i];

        if (charIndex >= cleanText.length) {
            cell.innerHTML = selectedHeart.emoji;
            cell.setAttribute('data-chars', defaultHeartChars.toString());
            cell.classList.remove('clipped');
            continue;
        }

        let tempString = cleanText.substring(charIndex);
        let emojiLength = 1;
        let detectedCharCost = 1;
        let charContent = tempString.substring(0, 1);
        let foundEmoji = null;

        for (const data of allEmojis) {
            if (tempString.startsWith(data.emoji)) {
                foundEmoji = data;
                emojiLength = data.emoji.length;
                detectedCharCost = data.chars;
                charContent = data.emoji;
                break;
            }
        }

        if (!foundEmoji) {
            detectedCharCost = calculateChatChars(charContent);
        }

        cell.innerHTML = charContent;
        cell.setAttribute('data-chars', detectedCharCost.toString());
        cell.classList.remove('clipped');
        charIndex += emojiLength;
    }

    updateCharacterCount();

    const stats = calculateAndClip(allCells);
    if (stats.clippedCount > 0) {
        showNotification(`⚠️ UYARI: İçe aktarılan metin 200 karakteri aşıyor. ${stats.clippedCount} hücre limit nedeniyle otomatik kırpıldı.`, 'warning', 7000);
    } else if (charIndex < cleanText.length) {
        showNotification(`⚠️ UYARI: İçe aktarılan metin matristeki ${totalEditableCount} hücreden daha uzundu. Fazla kısım atıldı.`, 'warning', 7000);
    }

    return true;
}

/**
 * Mevcut matris içeriğini düz metin olarak üretir
 */
function generateCurrentMatrixOutput() {
    return getDrawingText(false);
}

// --- TOPLULUK ÇİZİMLERİ FONKSİYONLARI ---

/**
 * SEPARATOR_MAP'teki tüm ayırıcı karakterleri metinden temizler
 * @param {string} text - Temizlenecek metin
 * @returns {string} Ayırıcılardan arındırılmış metin
 */
function cleanSeparators(text) {
    if (!text || typeof text !== 'string') return text;

    let cleaned = text;

    // SEPARATOR_MAP'teki tüm ayırıcı karakterleri temizle (none hariç)
    for (const key in SEPARATOR_MAP) {
        if (key !== 'none') {
            const separator = SEPARATOR_MAP[key];
            // Global replace ile tüm ayırıcı örneklerini temizle
            cleaned = cleaned.split(separator.char).join('');
        }
    }

    return cleaned;
}

/**
 * Çizim içeriğini firstRowLength ve width bilgisine göre formatla
 * İlk satır sağa yaslanır, diğer satırlar normal
 */
function formatDrawingContent(content, firstRowLength, width) {
    if (!content) return '';


    const emojis = Array.from(cleanSeparators(content));
    const totalEmojis = emojis.length;

    let result = '';
    let currentIndex = 0;
    const totalRows = ((totalEmojis - firstRowLength) / width) + 1;

    // İlk satır: firstRowLength kadar emoji SAĞA YASLI
    if (currentIndex < totalEmojis) {
        // İlk satırdaki emoji sayısı
        const firstLineCount = Math.min(firstRowLength, totalEmojis);
        const firstLineEmojis = emojis.slice(currentIndex, currentIndex + firstLineCount);
        currentIndex += firstLineCount;

        // Sağa yaslamak için boşluk ekle
        const padding = '❌'.repeat(width - firstLineCount);
        result += padding + firstLineEmojis.join('');
    }

    // Kalan satırlar: tam genişlikte
    for (let row = 1; row < totalRows; row++) {
        result += '\n';

        if (currentIndex < totalEmojis) {
            const lineCount = Math.min(width, totalEmojis - currentIndex);
            const lineEmojis = emojis.slice(currentIndex, currentIndex + lineCount);
            result += lineEmojis.join('');
            currentIndex += lineCount;
        }
    }

    return result;
}

/**
 * Basit dosya kaydetme - sadece ham içeriği kaydeder
 */
function saveDrawingToFile(content, id) {
    try {
        const filename = `pixel-art-cizim-${id}.txt`;
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showNotification(`📥 Çizim #${id} dosyaya kaydedildi.`, 'success', 2000);
    } catch (error) {
        console.error('Dosya kaydetme hatası:', error);
        showNotification('❌ Dosya kaydedilirken hata oluştu.', 'error', 3000);
    }
}

/**
 * Çizim kartı oluştur - Silme butonu eklendi
 */
function createDrawingCard(drawing) {
    const card = document.createElement('div');
    card.className = 'drawing-card';
    card.dataset.id = drawing.id;

    const drawingPreview = document.createElement('pre');
    drawingPreview.className = 'drawing-preview';

    const firstRowLength = drawing.first_row_length || 6;
    const width = drawing.width || 11;

    drawingPreview.setAttribute('data-width', width);

    const formattedContent = formatDrawingContent(
        drawing.content || drawing.drawing_content || '',
        firstRowLength,
        width
    );
    drawingPreview.textContent = formattedContent;

    const meta = document.createElement('div');
    meta.className = 'drawing-meta';

    // Çizer bilgisi - PROFİL FOTOĞRAFI DAHİL (DÜZELTİLMİŞ)
    let authorDisplay = 'Anonim';
    let authorProfilePic = '';

    if (drawing.author_username) {
        authorDisplay = `<a href="/${drawing.author_username}/" style="color: var(--accent-color);">${drawing.author_username}</a>`;
        if (drawing.author_profile_picture) {
            // PROFİL FOTOĞRAFI İŞLEME
            let profilePicSrc;
            if (drawing.author_profile_picture.startsWith('data:image')) {
                profilePicSrc = drawing.author_profile_picture;
            } else if (drawing.author_profile_picture === 'default.png') {
                profilePicSrc = '/images/default.png';
            } else {
                profilePicSrc = 'data:image/jpeg;base64,' + drawing.author_profile_picture;
            }
            authorProfilePic = `<img src="${profilePicSrc}" alt="Profil" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; margin-right: 5px;">`;
        }
    }

    const updatedAt = drawing.updated_at ? new Date(drawing.updated_at).toLocaleString('tr-TR') : 'Bilinmiyor';

    meta.innerHTML = `
    <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;">
    ${authorProfilePic}
    <span><b>Çizer:</b> ${authorDisplay}</span>
    </div>
    <p><b>ID:</b> ${drawing.id} | <b>İlk Satır:</b> ${firstRowLength} | <b>Genişlik:</b> ${width}</p>
    <p><b>Son Düzenleme:</b> ${updatedAt}</p>
    `;

    const actions = document.createElement('div');
    actions.className = 'drawing-actions';
    const content = drawing.content || drawing.drawing_content || '';

    // SİLME BUTONU - Sadece çizerin kendisi ve admin için
    let deleteButton = '';
    if (window.currentUser && (window.currentUser.id === drawing.author_id || window.currentUser.role === 'admin')) {
        deleteButton = `
        <button onclick="deleteDrawing(${drawing.id})" class="btn-sm" title="Çizimi Sil">
        ✖️
        </button>
        `;
    }

    actions.innerHTML = `
    <button onclick="loadDrawingToEditor('${content.replace(/'/g, "\\'")}', ${firstRowLength}, ${width})" class="btn-sm btn-action">Düzenle</button>
    <button onclick="copyToClipboard('${content.replace(/'/g, "\\'")}')" class="btn-sm btn-action">Kopyala</button>
    <button onclick="saveDrawingToFile('${content.replace(/'/g, "\\'")}', ${drawing.id})" class="btn-sm btn-action">Kaydet</button>
    ${deleteButton}
    `;

    card.appendChild(drawingPreview);
    card.appendChild(meta);
    card.appendChild(actions);

    return card;
}
/**
 * Çizimi silme fonksiyonu
 */
async function deleteDrawing(drawingId) {
    if (!window.currentUser) {
        showNotification('Bu işlem için giriş yapmalısınız.', 'error');
        return;
    }

    const confirmed = await showConfirm(
        'Çizimi Sil',
        'Bu çizimi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.'
    );

    if (!confirmed) return;

    try {
        const response = await fetch('delete_drawing.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ drawing_id: drawingId })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('✅ Çizim başarıyla silindi.', 'success');
            // Kartı DOM'dan kaldır
            const card = document.querySelector(`.drawing-card[data-id="${drawingId}"]`);
            if (card) {
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300);
            }
        } else {
            showNotification('❌ ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Silme hatası:', error);
        showNotification('❌ Silme işlemi sırasında hata oluştu.', 'error');
    }
}

/**
 * Çizimi editöre yükle - firstRowLength ve width bilgisiyle
 */
function loadDrawingToEditor(content, firstRowLength = 6, width = 11) {
    // firstRowLength input'unu güncelle
    if (firstRowLengthInput) {
        firstRowLengthInput.value = firstRowLength;
    }

    // Width'e göre separator seç (SP_BS ise 10, diğerleri 11)
    if (separatorSelect) {
        separatorSelect.value = width === 10 ? 'SP_BS' : 'none';
    }

    // Çizimi uygula
    if (applyDrawingText(content)) {
        showNotification('✏️ Çizim editöre yüklendi. İlk satır: ' + firstRowLength + ', Genişlik: ' + width, 'info', 3000);
    }
}

function copyToClipboard(content) {
    navigator.clipboard.writeText(content)
    .then(() => showNotification('📋 Çizim panoya kopyalandı.', 'success', 2000))
    .catch(err => {
        console.error('Kopyalama hatası:', err);
        showNotification('❌ Kopyalama başarısız.', 'error', 3000);
    });
}

/**
 * Dosyaya kaydetme (ORJİNAL İŞLEV)
 */
function saveToFile() {
    const drawingText = getDrawingText(true);
    const blob = new Blob([drawingText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'emoji_cizimi.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    showNotification('💾 Çizim dosyaya kaydedildi!', 'success');
}

/**
 * Veritabanına kaydetme (YENİ İŞLEV)
 */
async function saveToDatabase(drawingContent) {
    // Kategori seç
    const category = await showCategorySelector();
    if (!category) return; // Kullanıcı iptal etti
    const firstRowLength = parseInt(firstRowLengthInput.value) || 6;
    const width = (separatorSelect.value === 'SP_BS') ? 10 : 11;

    // Sunucuya gönder
    const response = await fetch('/save_drawing.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            drawingContent: drawingContent,
            category: category,
            firstRowLength: firstRowLength,
            width: width
        })
    });

    const result = await response.json();

    if (result.success) {
        showNotification(`✅ Çizim #${result.id} "${result.category}" kategorisinde kaydedildi!`, 'success');
        // Liste yenile
        if (typeof fetchDrawings === 'function') {
            setTimeout(() => fetchDrawings(1), 1000);
        }
    } else {
        if (response.status === 409) {
            showNotification('ℹ️ ' + result.message, 'info');
        } else {
            showNotification('❌ ' + result.message, 'error');
        }
    }
}

/**
 * Kategori seçici
 */
async function showCategorySelector() {
    return new Promise((resolve) => {
        const category = prompt(
            'Çizim kategorisini girin:\n(Örnek: Sanat, Pixel Art, Duygular, Soyut, Figüratif, Anime, Doğa, vs.)',
                                'Genel'
        );

        resolve(category === null ? null : (category || 'Genel'));
    });
}

/**
 * Sayfalama kontrollerini oluşturur.
 */
function createPaginationControls(currentPage, totalPages) {
    if (!PAGINATION_ELEMENT) return;

    PAGINATION_ELEMENT.innerHTML = '';

    if (totalPages <= 1) return;

    // Geri Düğmesi
    const prevButton = document.createElement('button');
    prevButton.textContent = '← Önceki';
    prevButton.disabled = currentPage === 1;
    prevButton.onclick = () => fetchDrawings(currentPage - 1);
    prevButton.className = 'btn-secondary';
    prevButton.style.marginRight = '10px';
    PAGINATION_ELEMENT.appendChild(prevButton);

    // Sayfa Bilgisi
    const pageInfo = document.createElement('span');
    pageInfo.textContent = `Sayfa ${currentPage} / ${totalPages}`;
    PAGINATION_ELEMENT.appendChild(pageInfo);

    // İleri Düğmesi
    const nextButton = document.createElement('button');
    nextButton.textContent = 'Sonraki →';
    nextButton.disabled = currentPage === totalPages;
    nextButton.onclick = () => fetchDrawings(currentPage + 1);
    nextButton.className = 'btn-secondary';
    nextButton.style.marginLeft = '10px';
    PAGINATION_ELEMENT.appendChild(nextButton);
}

/**
 * list_drawings.php'den verileri çeker ve listeyi günceller.
 */
async function fetchDrawings(page = 1) {
    if (!DRAWING_LIST_ELEMENT) return;

    DRAWING_LIST_ELEMENT.innerHTML = '<p id="loading-message">Çizimler yükleniyor...</p>';
    if (PAGINATION_ELEMENT) PAGINATION_ELEMENT.innerHTML = '';

    try {
        const response = await fetch(`list_drawings.php?page=${page}`);
        const result = await response.json();

        if (result.success) {
            DRAWING_LIST_ELEMENT.innerHTML = '';

            if (result.drawings.length === 0) {
                DRAWING_LIST_ELEMENT.innerHTML = '<p>Henüz kayıtlı çizim bulunmamaktadır.</p>';
                return;
            }

            result.drawings.forEach(drawing => {
                const card = createDrawingCard(drawing);
                DRAWING_LIST_ELEMENT.appendChild(card);
            });

            if (PAGINATION_ELEMENT && result.totalPages > 1) {
                createPaginationControls(result.currentPage, result.totalPages);
            }

        } else {
            DRAWING_LIST_ELEMENT.innerHTML = `<p style="color: red;">❌ Liste yüklenirken hata oluştu: ${result.message}</p>`;
        }
    } catch (error) {
        console.error('Listeleme hatası:', error);
        DRAWING_LIST_ELEMENT.innerHTML = '<p style="color: red;">❌ Sunucu ile iletişim kurulamadı. Listeleme başarısız.</p>';
    }
}

async function fetchFollowingFeed() {
    if (!FOLLOWING_FEED_ELEMENT) return;

    FOLLOWING_FEED_ELEMENT.innerHTML = '<p>Akış yükleniyor...</p>';

    try {
        const response = await fetch('fetch_following_feed.php');
        const result = await response.json();

        if (result.success && result.drawings.length > 0) {
            FOLLOWING_FEED_ELEMENT.innerHTML = '';
            result.drawings.forEach(drawing => {
                const card = createDrawingCard(drawing);
                FOLLOWING_FEED_ELEMENT.appendChild(card);
            });
        } else if (result.success) {
            FOLLOWING_FEED_ELEMENT.innerHTML = '<p>Takip ettiğiniz çizerlerin henüz yeni çizimi yok.</p>';
        } else {
            FOLLOWING_FEED_ELEMENT.innerHTML = `<p style="color: red;">❌ Akış yüklenemedi: ${result.message}</p>`;
        }
    } catch (error) {
        console.error('Akış hatası:', error);
        FOLLOWING_FEED_ELEMENT.innerHTML = '<p style="color: red;">❌ Sunucu hatası.</p>';
    }
}

// YERİNE BU BASİT MODAL SİSTEMİNİ EKLE:
function initModalSystem() {
    // Modal açma
    document.addEventListener('click', (e) => {
        const target = e.target;

        // Modal aç butonları
        if (target.matches('[data-modal-toggle]')) {
            e.preventDefault();
            const modalId = target.getAttribute('data-modal-toggle');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }

        // Modal kapatma
        if (target.matches('.modal-close') || target.matches('.modal')) {
            e.preventDefault();
            const modal = target.closest('.modal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }

        // Modal geçiş bağlantıları
        if (target.matches('[data-modal-switch]')) {
            e.preventDefault();
            const currentModal = target.closest('.modal');
            const targetModalId = target.getAttribute('data-modal-switch');

            if (currentModal) {
                currentModal.classList.remove('show');
            }

            setTimeout(() => {
                const targetModal = document.getElementById(targetModalId);
                if (targetModal) {
                    targetModal.classList.add('show');
                }
            }, 300);
        }
    });

    // ESC tuşu ile kapatma
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                openModal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
    });
}

// Form gönderimlerini yönet
function initAuthForms() {
    document.addEventListener('submit', async (e) => {
        if (e.target.matches('.auth-form')) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;

            // Butonu devre dışı bırak
            submitButton.disabled = true;
            submitButton.textContent = 'İşleniyor...';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });

                let result;
                try {
                    result = await response.json();
                } catch (jsonError) {
                    throw new Error('Sunucu yanıtı işlenemedi.');
                }

                if (result.success) {
                    showNotification(result.message, 'success');
                    // Modalı kapat
                    const modal = form.closest('.modal');
                    if (modal) {
                        modal.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                    // Sayfayı yenile
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(result.message || 'Bir hata oluştu', 'error');
                }
            } catch (error) {
                console.error('Form gönderim hatası:', error);
                showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
            } finally {
                // Butonu tekrar etkinleştir
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        }
    });
}

function initGoogleAuthLinks() {
    document.addEventListener('click', (e) => {
        if (e.target.matches('.btn-google') || e.target.closest('.btn-google')) {
            e.preventDefault();
            const link = e.target.matches('.btn-google') ? e.target : e.target.closest('.btn-google');
            const currentModal = link.closest('.modal')?.id;

            if (currentModal) {
                // Hangi modal üzerinden tıklandığını session'a kaydetmek için
                const googleUrl = `login.php?source=${currentModal}`;
                window.location.href = googleUrl;
            }
        }
    });
}

// URL'den hata ve başarı mesajlarını oku ve göster
function handleUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const success = urlParams.get('success');
    const hash = window.location.hash;

    if (error) {
        showNotification(decodeURIComponent(error), 'error', 8000);

        // Hash'te belirtilen modalı aç
        if (hash && hash.includes('_modal')) {
            const modalId = hash.split('?')[0].replace('#', '');
            if (modalId) {
                setTimeout(() => {
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.classList.add('show');
                        document.body.style.overflow = 'hidden';
                    }
                }, 1000);
            }
        }
    }

    if (success) {
        showNotification(decodeURIComponent(success), 'success', 5000);
    }

    // URL'yi temizle (yeniden yükleme durumunda mesajın tekrar gösterilmemesi için)
    if (error || success) {
        const cleanUrl = window.location.pathname + (hash ? hash.split('?')[0] : '');
        window.history.replaceState({}, document.title, cleanUrl);
    }
}

/**
 * Gelişmiş kaydetme fonksiyonu - Hem dosyaya hem DB'ye kaydetme seçeneği sunar
 */
async function handleSaveDrawing() {
    try {
        const drawingContent = getDrawingText(false);

        if (!drawingContent || drawingContent.length < 5) {
            showNotification('❌ Kaydetmek için geçerli bir çizim oluşturun.', 'error');
            return;
        }

        // Kaydetme seçeneklerini göster
        const saveOption = await showSaveOptions();

        if (saveOption === 'file') {
            // DOSYAYA KAYDET (Orjinal işlev)
            saveToFile();
        } else if (saveOption === 'database') {
            // VERİTABANINA KAYDET (Yeni işlev)
            await saveToDatabase(drawingContent);
        }
        // 'cancel' ise hiçbir şey yapma

    } catch (error) {
        console.error('Kaydetme hatası:', error);
        showNotification('❌ Kayıt sırasında hata oluştu.', 'error');
    }
}

/**
 * Kaydetme seçeneklerini göster
 */
async function showSaveOptions() {
    return new Promise((resolve) => {
        // Basit bir seçim kutusu
        const choice = confirm(
            'Çizimi nasıl kaydetmek istiyorsunuz?\n\n' +
            'OK: Veritabanına Kaydet (Toplulukla paylaş)\n' +
            'Cancel: Dosyaya Kaydet (.txt) - Sadece bilgisayarınıza kaydeder'
        );

        resolve(choice ? 'database' : 'file');
    });
}

/**
 * Sağ tık menüsüne dosyaya kaydet seçeneği ekle (isteğe bağlı)
 */
function addContextMenuOption() {
    // Çizim alanına sağ tık menüsü ekle
    const matrixContainer = document.getElementById('matrix-container');
    if (matrixContainer) {
        matrixContainer.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            showContextMenu(e.pageX, e.pageY);
        });
    }
}

function showContextMenu(x, y) {
    // Basit bir context menu oluştur
    const menu = document.createElement('div');
    menu.style.position = 'absolute';
    menu.style.left = x + 'px';
    menu.style.top = y + 'px';
    menu.style.background = 'var(--fixed-bg)';
    menu.style.border = '1px solid var(--border-color)';
    menu.style.borderRadius = '4px';
    menu.style.padding = '5px 0';
    menu.style.zIndex = '1000';
    menu.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';

    const option = document.createElement('div');
    option.textContent = '📁 Dosyaya Kaydet (.txt)';
    option.style.padding = '8px 15px';
    option.style.cursor = 'pointer';
    option.style.fontSize = '14px';

    option.addEventListener('click', () => {
        saveToFile();
        document.body.removeChild(menu);
    });

    menu.appendChild(option);
    document.body.appendChild(menu);

    // Menüyü kapat
    setTimeout(() => {
        document.addEventListener('click', function closeMenu() {
            if (document.body.contains(menu)) {
                document.body.removeChild(menu);
            }
            document.removeEventListener('click', closeMenu);
        });
    }, 100);
}

// --- OLAY DİNLEYİCİLERİ ---
// Event listener'ları güncelle
document.addEventListener('DOMContentLoaded', () => {
    // Save butonunu veritabanına kaydet işlevi ile değiştir
    const saveButton = document.getElementById('saveButton');
    if (saveButton) {
        // Buton metnini biraz daha açıklayıcı yap
        saveButton.textContent = '💾 Kaydet (Dosya/DB)';

        // Önceki event listener'ı kaldır ve yeni ekle
        saveButton.replaceWith(saveButton.cloneNode(true));
        const newSaveButton = document.getElementById('saveButton');
        newSaveButton.addEventListener('click', handleSaveDrawing);
    }

    // Alternatif: Sağ tık menüsüne dosyaya kaydet ekleyelim
    addContextMenuOption();

    // First Row Length Input
    if (firstRowLengthInput) {
        firstRowLengthInput.addEventListener('input', () => {
            // Sadece matrisi güncelleme butonuna basıldığında matrix yeniden çizilir.
        });
    }

    // Update Matrix Button
    if (updateMatrixButton) {
        updateMatrixButton.addEventListener('click', async () => {
            const confirmed = await showConfirm(
                "Matrisi Güncelle",
                "İlk satır çizilebilir piksel sayısını değiştirmek mevcut çizimi temizleyecektir. Devam etmek istiyor musunuz?"
            );

            if (confirmed) {
                createMatrix();
                showNotification('Matris başarıyla güncellendi!', 'success');
            }
        });
    }

    // Separator Select
    if (separatorSelect) {
        separatorSelect.addEventListener('change', async () => {
            const newWidth = (separatorSelect.value === 'SP_BS') ? SP_BS_MATRIX_WIDTH : DEFAULT_MATRIX_WIDTH;
            const currentDisplayedWidth = matrixTable && matrixTable.rows.length > 0 ? matrixTable.rows[0].cells.length : DEFAULT_MATRIX_WIDTH;

            if (newWidth !== currentDisplayedWidth) {
                const confirmed = await showConfirm(
                    "Ayırıcı Değişikliği",
                    "Ayırıcı türünü değiştirmek matris boyutunu değiştirecek ve çizimi temizleyecektir. Devam etmek istiyor musunuz?"
                );

                if (confirmed) {
                    createMatrix();
                    showNotification(`⚠️ Matris boyutu ${currentDisplayedWidth}x${MATRIX_HEIGHT}'dan ${newWidth}x${MATRIX_HEIGHT}'a değiştirildi. Çizim temizlendi.`, 'warning');
                } else {
                    const prevValue = Array.from(separatorSelect.options).find(opt =>
                    (opt.value === 'SP_BS' && currentDisplayedWidth === SP_BS_MATRIX_WIDTH) ||
                    (opt.value !== 'SP_BS' && currentDisplayedWidth === DEFAULT_MATRIX_WIDTH)
                    )?.value || 'none';
                    separatorSelect.value = prevValue;
                    return;
                }
            } else {
                updateCharacterCount();
                const separatorName = SEPARATOR_MAP[separatorSelect.value].name;
                showNotification(`Ayırıcı ${separatorName} olarak ayarlandı.`, 'info');
            }
        });
    }

    // Copy Button - ORJİNAL
    const copyButton = document.getElementById('copyButton');
    if (copyButton) {
        copyButton.addEventListener('click', async () => {
            const drawingText = getDrawingText(false);
            const allCells = matrixTable ? matrixTable.querySelectorAll('td') : [];
            const stats = calculateAndClip(allCells);
            const totalChars = stats.totalOutputCharCount;

            try {
                const separatorName = SEPARATOR_MAP[separatorSelect.value].name;
                await navigator.clipboard.writeText(drawingText);
                showNotification(`✅ Çizim panoya kopyalandı! (${totalChars}/${MAX_CHARACTERS} Karakter - ${separatorName} kullanılıyor)`, 'success');
            } catch (err) {
                console.error('Kopyalama başarısız:', err);
                showNotification('❌ Kopyalama başarısız oldu. Lütfen tarayıcı izinlerini kontrol edin.', 'error');
            }
        });
    }

    // Import Button - ORJİNAL
    const importButton = document.getElementById('importButton');
    if (importButton) {
        importButton.addEventListener('click', async () => {
            try {
                const text = await navigator.clipboard.readText();
                if (text && applyDrawingText(text)) {
                    showNotification('✅ Çizim panodan başarıyla içe aktarıldı!', 'success');
                } else if (!text) {
                    showNotification('❌ Panoda içe aktarılacak metin bulunamadı.', 'error');
                }
            } catch (err) {
                console.error('İçe aktarma başarısız:', err);
                showNotification('❌ İçe aktarma başarısız oldu. Panonuzda geçerli bir çizim metni olduğundan emin olun.', 'error');
            }
        });
    }

    // Load Button - ORJİNAL
    const loadButton = document.getElementById('loadButton');
    if (loadButton) {
        loadButton.addEventListener('click', () => {
            if (fileInput) {
                fileInput.click();
            }
        });
    }

    // File Input - ORJİNAL
    if (fileInput) {
        fileInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const text = e.target.result;
                    if (applyDrawingText(text)) {
                        showNotification('✅ Çizim dosyadan başarıyla yüklendi!', 'success');
                    }
                };
                reader.readAsText(file);
                event.target.value = '';
            }
        });
    }

    // Clear Button - ORJİNAL
    const clearButton = document.getElementById('clearButton');
    if (clearButton) {
        clearButton.addEventListener('click', async () => {
            const confirmed = await showConfirm(
                "Çizimi Temizle",
                "Mevcut çizimi temizlemek istediğinizden emin misiniz?"
            );

            if (confirmed) {
                createMatrix();
                showNotification('🧹 Çizim temizlendi!', 'success');
            }
        });
    }

    // Guide Modal Buttons
    if (showGuideButton) {
        showGuideButton.addEventListener('click', () => {
            if (guideModal) {
                guideModal.classList.add('show');
            }
        });
    }

    if (closeGuideButton) {
        closeGuideButton.addEventListener('click', () => {
            if (guideModal) {
                guideModal.classList.remove('show');
            }
        });
    }

    // Logout Button
    const logoutButton = document.getElementById('logoutButton');
    if (logoutButton) {
        logoutButton.addEventListener('click', (e) => {
            if (!confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                e.preventDefault();
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 Emoji Sanat Uygulaması Başlatılıyor...');

    try {
        // Mevcut kodlar aynı kalacak...
        initModalSystem();
        initAuthForms();
        initGoogleAuthLinks();
        handleUrlParameters();

        if (document.getElementById('separator-select')) {
            calculateSeparatorCharCosts();
        }

        await loadEmojis();

        if (Object.keys(emojiCategories).length > 0) {
            if (document.getElementById('matrix')) {
                updateSelectedEmojiDisplay();
                createMatrix();
                createCategoryTabs();
                createPalette();
                showNotification('⚡ Kalp Emoji Piksel Sanatı Editörü Hazır!', 'info', 3000);
            }
        }

        setTimeout(() => {
            if (typeof fetchFollowingFeed === 'function' && document.getElementById('following-feed-list')) {
                fetchFollowingFeed();
            }
            if (typeof fetchDrawings === 'function' && document.getElementById('drawing-list')) {
                fetchDrawings(1);
            }
        }, 2000);

        document.querySelectorAll('.btn-google').forEach(link => {
            const currentModal = link.closest('.modal')?.id;
            if (currentModal) {
                link.href = `login.php?source=${currentModal}`;
            }
        });

    } catch (error) {
        console.error('Uygulama başlatma hatası:', error);
        showNotification('Uygulama başlatılırken hata oluştu.', 'error');
    }
});
