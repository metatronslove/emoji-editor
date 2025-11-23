/**
 * Kaydetme seçeneklerini modal ile göster (callback versiyonu)
 */
function showSaveOptions(callback) {
    // Modal HTML'i oluştur
    const modalHTML = `
    <div id="saveOptionsModal" class="modal show" style="display: flex;">
    <div class="modal-content" style="max-width: 450px;">
    <h3 style="margin-bottom: 15px; color: var(--main-text);">Çizimi Kaydet</h3>
    <p style="margin-bottom: 20px; color: var(--main-text); opacity: 0.8;">
    Çizimi nasıl kaydetmek istiyorsunuz?
    </p>

    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px;">
    <button id="saveToDatabase" class="btn btn-primary full-width" style="justify-content: center; display: flex; align-items: center; gap: 8px;">
    <span>📊</span>
    Veritabanına Kaydet (Toplulukla paylaş)
    </button>

    <button id="saveToFile" class="btn btn-secondary full-width" style="justify-content: center; display: flex; align-items: center; gap: 8px;">
    <span>💾</span>
    Dosyaya Kaydet (.txt)
    </button>
    </div>

    <button id="cancelSave" class="btn" style="background: transparent; border: 1px solid var(--border-color); color: var(--main-text); width: 100%;">
    İptal
    </button>
    </div>
    </div>
    `;

    // Modal'ı body'e ekle
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modal = document.getElementById('saveOptionsModal');
    const saveToDatabaseBtn = document.getElementById('saveToDatabase');
    const saveToFileBtn = document.getElementById('saveToFile');
    const cancelBtn = document.getElementById('cancelSave');

    // Cleanup fonksiyonu
    function cleanup() {
        if (modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
        document.removeEventListener('keydown', handleKeydown);
        modal.removeEventListener('click', handleOutsideClick);
    }

    // Seçim işleyici
    function handleChoice(saveOption) {
        cleanup();
        if (callback) {
            callback(saveOption);
        }
    }

    // ESC tuşu ve dışarı tıklama desteği
    function handleKeydown(e) {
        if (e.key === 'Escape') {
            handleChoice('cancel');
        }
    }

    function handleOutsideClick(e) {
        if (e.target === modal) {
            handleChoice('cancel');
        }
    }

    // Event listener'ları ekle
    document.addEventListener('keydown', handleKeydown);
    modal.addEventListener('click', handleOutsideClick);

    saveToDatabaseBtn.addEventListener('click', () => handleChoice('database'));
    saveToFileBtn.addEventListener('click', () => handleChoice('file'));
    cancelBtn.addEventListener('click', () => handleChoice('cancel'));
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
 * Sağ tık menüsüne dosyaya kaydet seçeneği ekle
 */
function addContextMenuOption() {
    const matrixContainer = document.getElementById('matrix-container');
    if (matrixContainer) {
        matrixContainer.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            showContextMenu(e.pageX, e.pageY);
        });
    }
}

function showContextMenu(x, y) {
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

    const noitpo = document.createElement('div');
    noitpo.textContent = '🗃️ Veritabanına Kaydet';
    noitpo.style.padding = '8px 15px';
    noitpo.style.cursor = 'pointer';
    noitpo.style.fontSize = '14px';

    noitpo.addEventListener('click', () => {
        saveToDatabase(getDrawingText(false));
        document.body.removeChild(menu);
    });

    menu.appendChild(noitpo);
    document.body.appendChild(menu);

    setTimeout(() => {
        document.addEventListener('click', function closeMenu() {
            if (document.body.contains(menu)) {
                document.body.removeChild(menu);
            }
            document.removeEventListener('click', closeMenu);
        });
    }, 100);
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

        showSaveOptions(async (saveOption) => {
            if (saveOption === 'file') {
                saveToFile();
                const separatorName = SEPARATOR_MAP[window.DOM_ELEMENTS?.separatorSelect?.value]?.name || 'Bilinmeyen';
                showNotification(`✅ Çizim dosyaya kaydedildi! (${separatorName} kullanılıyor)`, 'success');
                return;
            } else if (saveOption === 'database') {
                await saveToDatabase(drawingContent);
                return;
            } else if (saveOption === 'cancel') {
                showNotification('❌ Kayıttan vazgeçildi', 'error');
                return;
            } else {
                showNotification('❌ Kayıt edilmedi', 'error');
                return;
            }
        });

    } catch (error) {
        console.error('Kaydetme hatası:', error);
        showNotification('❌ Kayıt sırasında hata oluştu.', 'error');
    }
}

/**
 * Veritabanına kaydet
 */
async function saveToDatabase(drawingContent) {
    const category = await showCategorySelector();
    if (!category) return;

    const { firstRowLengthInput, separatorSelect } = window.DOM_ELEMENTS || {};
    const firstRowLength = parseInt(firstRowLengthInput?.value) || 6;
    const width = (separatorSelect?.value === 'SP_BS') ? 10 : 11;

    try {
        const response = await fetch(SITE_BASE_URL + 'core/save_drawing.php', {
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

        // HTTP status kodunu kontrol et
        if (!response.ok) {
            if (response.status === 409) {
                // Mükerrer kayıt hatası
                showNotification(`❌ ${result.message}`, 'error');
            } else {
                // Diğer hatalar
                showNotification(`❌ ${result.message || 'Kayıt başarısız!'}`, 'error');
            }
            return;
        }

        // Başarılı kayıt
        if (result.success) {
            showNotification(`✅ Çizim #${result.id} "${result.category}" kategorisinde kaydedildi!`, 'success');
            if (typeof fetchDrawings === 'function') {
                setTimeout(() => fetchDrawings(1), 1000);
            }
        } else {
            showNotification(`❌ ${result.message}`, 'error');
        }

    } catch (error) {
        console.error('Kaydetme hatası:', error);
        showNotification('❌ Kayıt sırasında hata oluştu.', 'error');
    }
}
