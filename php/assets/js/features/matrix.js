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
 * Mevcut matristeki karakter sayısını hesaplar ve MAX_CHARACTERS sınırına göre kırpma yapar.
 * KRİTİK DÜZELTME NOTLARI:
 * 1. Maliyetler, her seferinde karakterin '.char.length' özelliği ile dinamik olarak hesaplanır.
 * 2. Satır Sonu (LB) karakteri, emojisInCurrentRow sayacına dahil EDİLMEZ (Yapısal Koruma).
 * 3. Seçili hücre ayırıcısı (separator), LB karakteri için de maliyet hesaplamasına eklenir.
 */
function calculateAndClip(allCells) {
    const { separatorSelect, lineBreakSelect } = DOM_ELEMENTS;
    const selectedSeparator = SEPARATOR_MAP[separatorSelect.value];
    const selectedLineBreak = window.LINE_BREAK_MAP[lineBreakSelect.value];

    let totalEmojiCharCost = 0;
    let totalEmojis = 0;
    let multiCharEmojisUsed = 0;
    let clippedCount = 0;
    
    let currentRow = -1;
    let emojisInCurrentRow = 0;
    let totalLineBreakCharCost = 0; 
    let totalSeparatorCharCost = 0; 

    const editableCells = Array.from(allCells).filter(cell => !cell.classList.contains('fixed'));
    const totalEditableCount = editableCells.length;
    
    const firstRowInput = document.getElementById('firstRowLength');
    const firstRowLength = parseInt(firstRowInput.value) || 5;
    const permanentFixedCount = currentMatrixWidth - firstRowLength; 
    
    for (let i = 0; i < totalEditableCount; i++) {
        const cell = editableCells[i];
        const newRowIndex = parseInt(cell.getAttribute('data-row'));

        // *** KRİTİK BÖLÜM: SATIR SONU (LB) MALİYETİ KONTROLÜ ***
        // Bu kontrol, önceki satırın 11. emojisi başarılı bir şekilde eklendikten sonra (currentRow == newRowIndex olmadığında) tetiklenir.
        if (newRowIndex !== currentRow && currentRow !== -1) {
            
            // KRİTİK DÜZELTME 1: Maliyetler HER SEFERİNDE string uzunluğundan hesaplanıyor!
            const lineBreakLength = selectedLineBreak.char.length; 
            const lbSeparatorLength = selectedSeparator.char.length; 

            // Satır sonu maliyet bileşenleri:
            let totalLBCost = lineBreakLength;
            
            // YENİ KURAL: Eğer ayırıcı seçildiyse, LB karakterine de uygulansın (LB'nin önündeki ayırıcı maliyeti).
            if (lbSeparatorLength > 0) {
                 totalLBCost += lbSeparatorLength;
            }
            
            // KONTROL 1: Toplam Satır Sonu Maliyeti sığar mı? (200 Karakter Kontrolü)
            if (totalEmojiCharCost + totalLBCost > MAX_CHARACTERS) {
                 // Satır sonu sığmıyorsa, kırpma hemen başlar.
                clippedCount = totalEditableCount - i + 1; 
                for(let j = i; j < totalEditableCount; j++) {
                    editableCells[j].classList.add('clipped');
                }
                break; // Kırpma başladığı için döngüden çık
            }

            // Maliyetleri ekle
            totalEmojiCharCost += totalLBCost;
            totalLineBreakCharCost += lineBreakLength; 
            totalSeparatorCharCost += lbSeparatorLength; 
        }

        // *** KRİTİK DÜZELTME 2: SATIR GENİŞLİĞİ/SAYACI KORUMASI ***
        if (newRowIndex !== currentRow) {
            currentRow = newRowIndex;
            // DÜZELTME: LB karakteri bir emoji sayılmaz; emojisInCurrentRow burada sıfırlanır.
            emojisInCurrentRow = 0; 
        }

        // **HÜCRE VE HÜCRE AYIRICI MALİYETİNİ HESAPLA**
        cell.classList.remove('clipped'); 
        let separatorCost = 0;
        let effectiveRowWidth = (currentRow === 0)
            ? (currentMatrixWidth - permanentFixedCount)
            : currentMatrixWidth;

        // Hücre ayırıcı maliyeti (emojiler arasına eklenen)
        // KRİTİK DÜZELTME 1: Ayırıcının string uzunluğu HER SEFERİNDE hesaplanıyor
        const cellSeparatorLength = selectedSeparator.char.length; 

        // Not: Ayırıcı SADECE 10. emojiden sonra (emojisInCurrentRow=10 iken) eklenir. 11. emojiden sonra eklenmez (11 < 11 yanlış).
        if (cellSeparatorLength > 0 && emojisInCurrentRow > 0 && (emojisInCurrentRow < effectiveRowWidth)) {
            separatorCost = cellSeparatorLength;
        }

        const emojiCost = parseInt(cell.getAttribute('data-chars') || '1');
        const combinedCost = emojiCost + separatorCost;

        // KONTROL 2: Hücre ve Ayırıcı maliyeti sığar mı? (200 Karakter Kontrolü)
        if (totalEmojiCharCost + combinedCost <= MAX_CHARACTERS) {
            totalEmojiCharCost += combinedCost;
            totalEmojis++;
            // Satırdaki emoji sayacını artır (SADECE emojinin kendisi eklendiği için)
            emojisInCurrentRow++; 

            if (emojiCost > 1) {
                multiCharEmojisUsed++;
            }
        } else {
            // Hücre/Ayırıcı sığmıyorsa, kırpma bu hücreden başlar.
            clippedCount = totalEditableCount - i;
            for(let j = i; j < totalEditableCount; j++) {
                editableCells[j].classList.add('clipped');
            }
            break;
        }
    }

    // ... (Kırpma sonrası temizleme kodları)
    if (clippedCount === 0) {
         for(let j = 0; j < totalEditableCount; j++) {
            editableCells[j].classList.remove('clipped');
        }
    }

    const totalOutputCharCount = totalEmojiCharCost;

    return {
        totalEmojiCharCost: totalOutputCharCount,
        totalEmojis: totalEmojis,
        multiCharEmojisUsed,
        clippedCount: clippedCount,
        totalOutputCharCount: totalOutputCharCount,
        lineBreakCharCost: totalLineBreakCharCost,
        totalSeparatorCharCost: totalSeparatorCharCost
    };
}