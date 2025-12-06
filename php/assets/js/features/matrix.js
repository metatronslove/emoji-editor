// Hesaplanacak length değerleri (utils.js'de calculateChatChars ile)
for (const key in window.SEPARATOR_MAP) {
    if (key !== 'none') {
         window.SEPARATOR_MAP[key].length = calculateChatChars(window.SEPARATOR_MAP[key].char);
    }
}

/**
 * Matris oluşturma
 */
function createMatrix() {
    const { matrixTable, firstRowLengthInput, separatorSelect } = DOM_ELEMENTS;
    if (!matrixTable) return;

    // Mevcut input değerlerini al
    const matrixWidthInput = document.getElementById('matrixWidth');
    const maxCharsInput = document.getElementById('maxCharsInput');
    
    // Global değişkenleri güncelle
    window.CUSTOM_MATRIX_WIDTH = parseInt(matrixWidthInput?.value) || 10;
    window.MAX_CHARACTERS = parseInt(maxCharsInput?.value) || 200;
    
    // Genişliği sınırla (1-20)
    window.CUSTOM_MATRIX_WIDTH = Math.max(1, Math.min(20, window.CUSTOM_MATRIX_WIDTH));
    
    // SP_BS için özel durum
    window.currentMatrixWidth = (separatorSelect.value === 'SP_BS') ? 
        Math.min(10, window.CUSTOM_MATRIX_WIDTH) : window.CUSTOM_MATRIX_WIDTH;

    matrixTable.innerHTML = '';

    // İlk satır çizilebilir piksel sayısı (V6.5)
    const drawablePixelCount = parseInt(firstRowLengthInput.value) || 5;
    
    // İlk satır için max değeri güncelle
    if (firstRowLengthInput) {
        firstRowLengthInput.setAttribute('max', window.currentMatrixWidth.toString());
        
        // Eğer mevcut değer yeni max'tan büyükse, azalt
        if (drawablePixelCount > window.currentMatrixWidth) {
            firstRowLengthInput.value = window.currentMatrixWidth;
        }
    }

    const permanentFixedCount = Math.max(0, window.currentMatrixWidth - drawablePixelCount);
    const defaultHeartChars = selectedHeart.chars;

    for (let rowIndex = 0; rowIndex < MATRIX_HEIGHT; rowIndex++) {
        const row = matrixTable.insertRow();

        for (let colIndex = 0; colIndex < window.currentMatrixWidth; colIndex++) {
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

    updateCharacterCount(); // EKSİK OLAN ÇAĞRI
}

/**
 * Karakter sayısını güncelle ve karakter limitini uygula
 */
function updateCharacterCount() {
    const { matrixTable, currentCharsSpan, charWarningSpan } = DOM_ELEMENTS;
    if (!matrixTable) return;

    const allCells = matrixTable.querySelectorAll('td');
    const stats = calculateAndClip(allCells);
    const totalOutputCharCount = stats.totalOutputCharCount;

    // YENİ: Karakter limitini güncelle (maxCharsInput varsa)
    const maxCharsInput = document.getElementById('maxCharsInput');
    const currentMaxChars = maxCharsInput ? parseInt(maxCharsInput.value) : window.MAX_CHARACTERS;
    
    // Karakter limitini matrise uygula
    applyCharacterLimit(stats, currentMaxChars);

    if (currentCharsSpan) {
        currentCharsSpan.textContent = totalOutputCharCount;
        currentCharsSpan.style.color = (totalOutputCharCount < currentMaxChars) ? 'var(--accent-color)' : '#28a745';
    }

    // UYARI METNİ GÜNCELLEME
    let warningText = '';
    const { separatorSelect, lineBreakSelect } = DOM_ELEMENTS;
    const selectedSeparator = SEPARATOR_MAP[separatorSelect.value];
    const selectedLineBreak = window.LINE_BREAK_MAP[lineBreakSelect.value]; 

    if (selectedSeparator.length > 0 && stats.totalEmojis > 0) {
        // Hücre ayırıcı maliyetini hesapla (sadece bilgi amaçlı)
        const totalSeparators = stats.totalEmojis > 0 ? stats.totalEmojis - 1 : 0;
        const separatorCharCost = totalSeparators * selectedSeparator.length;

        warningText += `${selectedSeparator.name} (${separatorCharCost} Karakter Maliyeti) kullanılıyor.`;
    }
    
    // YENİ DÜZELTME: Satır Sonu Maliyeti Uyarısı
    if (stats.lineBreakCharCost > 0) { 
        if (warningText) warningText += ' | ';
        warningText += `${selectedLineBreak.name} (${stats.lineBreakCharCost} Karakter Maliyeti) kullanılıyor.`;
    }

    if (stats.multiCharEmojisUsed > 0) {
        if (warningText) warningText += ' | ';
        warningText += `${stats.multiCharEmojisUsed} adet çok karakterli emoji kullanılıyor.`;
    }

    if (stats.clippedCount > 0) {
        if (warningText) warningText += ' | ';
        warningText += `ÇIKTI LİMİTİ (${currentMaxChars}) NEDENİYLE SON ${stats.clippedCount} HÜCRE OTOMATİK KIRPILDI.`;
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
 * Karakter limitini matrise uygula
 */
function applyCharacterLimit(stats, currentMaxChars) {
    const { matrixTable } = DOM_ELEMENTS;
    if (!matrixTable) return;

    const allCells = matrixTable.querySelectorAll('td');
    const editableCells = Array.from(allCells).filter(cell => !cell.classList.contains('fixed'));
    const { separatorSelect } = DOM_ELEMENTS;
    const selectedSeparator = SEPARATOR_MAP[separatorSelect.value];
    
    // Toplam karakter maliyetini hesapla
    let totalCost = 0;
    let lastEmojiIndex = -1;
    
    // Önce tüm clipped hücreleri temizle
    editableCells.forEach(cell => {
        cell.classList.remove('clipped');
        if (cell.innerHTML === '✂️') {
            cell.innerHTML = selectedHeart.emoji;
            cell.setAttribute('data-chars', selectedHeart.chars.toString());
        }
    });
    
    // Hangi hücrelerin limit içinde kalacağını hesapla
    for (let i = 0; i < editableCells.length; i++) {
        const cell = editableCells[i];
        const cellCost = parseInt(cell.getAttribute('data-chars') || '1');
        const separatorCost = (i > 0 && lastEmojiIndex >= 0) ? selectedSeparator.length : 0;
        
        if (totalCost + cellCost + separatorCost <= currentMaxChars) {
            totalCost += cellCost + separatorCost;
            lastEmojiIndex = i;
        } else {
            // Limit aşıldı, bu hücreden itibaren kırp
            for (let j = i; j < editableCells.length; j++) {
                editableCells[j].classList.add('clipped');
                editableCells[j].innerHTML = '✂️';
                editableCells[j].setAttribute('data-chars', '0');
            }
            break;
        }
    }
}

/**
 * Matrisi panoya kopyala (Emoji editörü için)
 */
function copyMatrixToClipboard() {
    try {
        const { matrixTable, separatorSelect } = DOM_ELEMENTS;
        if (!matrixTable) {
            showNotification('Matris bulunamadı', 'error');
            return;
        }

        const selectedSeparator = SEPARATOR_MAP[separatorSelect?.value || 'none'];
        const allCells = matrixTable.querySelectorAll('td:not(.fixed):not(.clipped)');
        
        let output = '';
        let currentRow = -1;
        
        for (let i = 0; i < allCells.length; i++) {
            const cell = allCells[i];
            const cellRow = parseInt(cell.getAttribute('data-row'));
            
            // Satır sonu ekle
            if (cellRow !== currentRow && currentRow !== -1) {
                output += '\n';
            }
            
            // Emoji ekle
            output += cell.innerHTML;
            
            // Ayırıcı ekle (son hücre hariç)
            if (selectedSeparator.length > 0 && i < allCells.length - 1) {
                const nextCell = allCells[i + 1];
                const nextRow = parseInt(nextCell.getAttribute('data-row'));
                
                if (nextRow === cellRow) {
                    output += selectedSeparator.char;
                }
            }
            
            currentRow = cellRow;
        }
        
        navigator.clipboard.writeText(output)
            .then(() => {
                showNotification('✅ Çizim panoya kopyalandı!', 'success');
            })
            .catch(err => {
                console.error('Kopyalama hatası:', err);
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = output;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showNotification('✅ Çizim kopyalandı! (fallback)', 'success');
            });
            
    } catch (error) {
        console.error('Kopyalama hatası:', error);
        showNotification('❌ Kopyalama başarısız', 'error');
    }
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

    // Kırpma sonrası temizleme
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

// Event listener ekleme
document.addEventListener('DOMContentLoaded', function() {
    const widthInput = document.getElementById('matrixWidth');
    const maxCharsInput = document.getElementById('maxCharsInput');
    
    if (widthInput) {
        widthInput.addEventListener('change', function() {
            CUSTOM_MATRIX_WIDTH = parseInt(this.value);
            createMatrix();
        });
    }
    
    if (maxCharsInput) {
        maxCharsInput.addEventListener('change', function() {
            MAX_CHARACTERS = parseInt(this.value);
            updateCharacterCount();
        });
    }
});