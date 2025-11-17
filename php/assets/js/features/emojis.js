/**
 * Emojileri yükle
 */
async function loadEmojis() {
    try {
        const response = await fetch(EMOJI_JSON_URL);
        if (!response.ok) {
            throw new Error(`HTTP Hata kodu: ${response.status}`);
        }
        const rawEmojis = await response.json();

        let processedCategories = {};
        const emojiArray = Array.isArray(rawEmojis) ? rawEmojis : Object.values(rawEmojis);

        emojiArray.forEach(item => {
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
            const firstEmoji = Object.values(emojiCategories)[0] ? Object.values(Object.values(emojiCategories)[0])[0] : null;
            if (firstEmoji) {
                selectedHeart = firstEmoji;
            }
        }

        showNotification(`✅ ${emojiArray.length} adet emoji başarıyla yüklendi ve maliyetleri hesaplandı!`, 'success');

    } catch (error) {
        console.error("Emoji yükleme hatası:", error);
        showNotification('❌ Emoji yüklenemedi. Emoji verisi endpointinin mevcut ve doğru formatta olduğundan emin olun.', 'error', 8000);

        // Fallback emoji seti
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
 * Seçili emoji görüntüsünü güncelle
 */
function updateSelectedEmojiDisplay() {
    const { currentBrushEmoji, currentBrushName } = DOM_ELEMENTS;
    if (!currentBrushEmoji || !currentBrushName) return;

    currentBrushEmoji.textContent = selectedHeart.emoji;
    currentBrushName.textContent = ` (${selectedHeart.name} - ${selectedHeart.chars} Karakter Maliyeti)`;

    document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected-color'));

    const activeOption = document.querySelector(`[data-color="${selectedHeart.name}"][data-category-name="${currentCategory}"]`);
    if (activeOption) {
        activeOption.classList.add('selected-color');
    }
}

/**
 * Kategori sekmelerini oluştur
 */
function createCategoryTabs() {
    const { categoryTabsContainer } = DOM_ELEMENTS;
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

/**
 * Emoji paletini oluştur
 */
function createPalette() {
    const { colorOptionsContainer } = DOM_ELEMENTS;
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
