/**
 * Karakter sayısını güncelle
 */
function updateCharacterCount() {
    const { matrixTable, currentCharsSpan, charWarningSpan, separatorSelect } = DOM_ELEMENTS;
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

/**
 * Mevcut matris içeriğini düz metin olarak üretir
 */
function getDrawingText(formatted = false) {
    const { matrixTable, separatorSelect } = DOM_ELEMENTS;
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

/**
 * Metni matrise uygula
 */
function applyDrawingText(text) {
    const { matrixTable, separatorSelect } = DOM_ELEMENTS;
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
 * Dosyaya kaydet
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
