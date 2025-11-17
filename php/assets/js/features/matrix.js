/**
 * Matris oluşturma
 */
function createMatrix() {
    const { matrixTable, firstRowLengthInput, separatorSelect } = DOM_ELEMENTS;
    if (!matrixTable) return;

    // Matris genişliğini ayarla
    currentMatrixWidth = (separatorSelect.value === 'SP_BS') ? SP_BS_MATRIX_WIDTH : DEFAULT_MATRIX_WIDTH;

    matrixTable.innerHTML = '';

    // V6.5 Düzeltmesi: Giriş değeri, çizilebilir piksel sayısıdır.
    const drawablePixelCount = parseInt(firstRowLengthInput.value) || 5;
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
                cell.innerHTML = selectedHeart.emoji;
                cell.setAttribute('data-chars', defaultHeartChars.toString());
                cell.addEventListener('click', () => handleCellClick(cell));
                cell.classList.remove('clipped');
            }
        }
    }

    updateCharacterCount();
}

/**
 * Hücre tıklama işleyicisi
 */
function handleCellClick(cell) {
    if (cell.classList.contains('fixed') || cell.classList.contains('clipped')) return;

    const newCost = selectedHeart.chars;
    cell.innerHTML = selectedHeart.emoji;
    cell.setAttribute('data-chars', newCost.toString());

    updateCharacterCount();
}

/**
 * Karakter sayımını hesaplar ve bütçeyi aşan hücreleri kırpar
 */
function calculateAndClip(allCells) {
    let totalEmojiCharCost = 0;
    let totalEmojis = 0;
    let multiCharEmojisUsed = 0;

    const { separatorSelect } = DOM_ELEMENTS;
    const selectedSeparator = SEPARATOR_MAP[separatorSelect.value];

    let editableCells = Array.from(allCells).filter(cell => !cell.classList.contains('fixed'));
    let totalEditableCount = editableCells.length;

    const { firstRowLengthInput } = DOM_ELEMENTS;
    const drawablePixelCount = parseInt(firstRowLengthInput.value) || 0;
    const permanentFixedCount = currentMatrixWidth - drawablePixelCount;

    let clippedCount = 0;

    editableCells.forEach(cell => cell.classList.remove('clipped'));

    let currentRow = -1;
    let emojisInCurrentRow = 0;

    for (let i = 0; i < totalEditableCount; i++) {
        const cell = editableCells[i];
        const newRowIndex = parseInt(cell.getAttribute('data-row'));

        if (newRowIndex !== currentRow) {
            currentRow = newRowIndex;
            emojisInCurrentRow = 0;
        }

        let separatorCost = 0;
        let effectiveRowWidth = (currentRow === 0)
            ? (currentMatrixWidth - permanentFixedCount)
            : currentMatrixWidth;

        if (selectedSeparator.length > 0 && emojisInCurrentRow > 0 && (emojisInCurrentRow < effectiveRowWidth)) {
            separatorCost = selectedSeparator.length;
        }

        const emojiCost = parseInt(cell.getAttribute('data-chars') || '1');
        const combinedCost = emojiCost + separatorCost;

        if (totalEmojiCharCost + combinedCost <= MAX_CHARACTERS) {
            totalEmojiCharCost += combinedCost;
            totalEmojis++;
            emojisInCurrentRow++;

            if (emojiCost > 1) {
                multiCharEmojisUsed++;
            }
        } else {
            clippedCount = totalEditableCount - i;
            for(let j = i; j < totalEditableCount; j++) {
                editableCells[j].classList.add('clipped');
            }
            break;
        }
    }

    const totalOutputCharCount = totalEmojiCharCost;

    return {
        totalEmojiCharCost: totalOutputCharCount,
        totalEmojis: totalEmojis,
        multiCharEmojisUsed,
        clippedCount: clippedCount,
        totalOutputCharCount: totalOutputCharCount,
    };
}
