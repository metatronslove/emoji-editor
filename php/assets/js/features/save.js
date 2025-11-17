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

        const saveOption = await showSaveOptions();

        if (saveOption === 'file') {
            saveToFile();
        } else if (saveOption === 'database') {
            await saveToDatabase(drawingContent);
        }

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
        const choice = confirm(
            'Çizimi nasıl kaydetmek istiyorsunuz?\n\n' +
            'OK: Veritabanına Kaydet (Toplulukla paylaş)\n' +
            'Cancel: Dosyaya Kaydet (.txt) - Sadece bilgisayarınıza kaydeder'
        );

        resolve(choice ? 'database' : 'file');
    });
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
 * Veritabanına kaydet
 */
async function saveToDatabase(drawingContent) {
    const category = await showCategorySelector();
    if (!category) return;

    const { firstRowLengthInput, separatorSelect } = DOM_ELEMENTS;
    const firstRowLength = parseInt(firstRowLengthInput.value) || 6;
    const width = (separatorSelect.value === 'SP_BS') ? 10 : 11;

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

    if (result.success) {
        showNotification(`✅ Çizim #${result.id} "${result.category}" kategorisinde kaydedildi!`, 'success');
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

    setTimeout(() => {
        document.addEventListener('click', function closeMenu() {
            if (document.body.contains(menu)) {
                document.body.removeChild(menu);
            }
            document.removeEventListener('click', closeMenu);
        });
    }, 100);
}
