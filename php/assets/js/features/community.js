/**
 * SEPARATOR_MAP'teki tüm ayırıcı karakterleri metinden temizler
 */
function cleanSeparators(text) {
    if (!text || typeof text !== 'string') return text;

    let cleaned = text;

    for (const key in SEPARATOR_MAP) {
        if (key !== 'none') {
            const separator = SEPARATOR_MAP[key];
            cleaned = cleaned.split(separator.char).join('');
        }
    }

    return cleaned;
}

/**
 * Çizim içeriğini firstRowLength ve width bilgisine göre formatla
 */
function formatDrawingContent(content, firstRowLength, width) {
    if (!content) return '';

    const emojis = Array.from(cleanSeparators(content));
    const totalEmojis = emojis.length;

    let result = '';
    let currentIndex = 0;
    const totalRows = ((totalEmojis - firstRowLength) / width) + 1;

    if (currentIndex < totalEmojis) {
        const firstLineCount = Math.min(firstRowLength, totalEmojis);
        const firstLineEmojis = emojis.slice(currentIndex, currentIndex + firstLineCount);
        currentIndex += firstLineCount;

        const padding = '❌'.repeat(width - firstLineCount);
        result += padding + firstLineEmojis.join('');
    }

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
 * Basit dosya kaydetme
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
 * Çizim kartı oluştur
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

    let authorDisplay = 'Anonim';
    let authorProfilePic = '';

    if (drawing.author_username) {
        authorDisplay = `<a href="/${drawing.author_username}/" style="color: var(--accent-color);">${drawing.author_username}</a>`;
        if (drawing.author_profile_picture) {
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
 * Çizimi sil
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
        const response = await fetch(SITE_BASE_URL + 'core/delete_drawing.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ drawing_id: drawingId })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('✅ Çizim başarıyla silindi.', 'success');
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
 * Çizimi editöre yükle
 */
function loadDrawingToEditor(content, firstRowLength = 6, width = 11) {
    const { firstRowLengthInput, separatorSelect } = DOM_ELEMENTS;

    if (firstRowLengthInput) {
        firstRowLengthInput.value = firstRowLength;
    }

    if (separatorSelect) {
        separatorSelect.value = width === 10 ? 'SP_BS' : 'none';
    }

    if (applyDrawingText(content)) {
        showNotification('✏️ Çizim editöre yüklendi. İlk satır: ' + firstRowLength + ', Genişlik: ' + width, 'info', 3000);
    }
}

/**
 * Panoya kopyala
 */
function copyToClipboard(content) {
    navigator.clipboard.writeText(content)
    .then(() => showNotification('📋 Çizim panoya kopyalandı.', 'success', 2000))
    .catch(err => {
        console.error('Kopyalama hatası:', err);
        showNotification('❌ Kopyalama başarısız.', 'error', 3000);
    });
}

/**
 * Sayfalama kontrollerini oluşturur
 */
function createPaginationControls(currentPage, totalPages) {
    const { PAGINATION_ELEMENT } = DOM_ELEMENTS;
    if (!PAGINATION_ELEMENT) return;

    PAGINATION_ELEMENT.innerHTML = '';

    if (totalPages <= 1) return;

    const prevButton = document.createElement('button');
    prevButton.textContent = '← Önceki';
    prevButton.disabled = currentPage === 1;
    prevButton.onclick = () => fetchDrawings(currentPage - 1);
    prevButton.className = 'btn-secondary';
    prevButton.style.marginRight = '10px';
    PAGINATION_ELEMENT.appendChild(prevButton);

    const pageInfo = document.createElement('span');
    pageInfo.textContent = `Sayfa ${currentPage} / ${totalPages}`;
    PAGINATION_ELEMENT.appendChild(pageInfo);

    const nextButton = document.createElement('button');
    nextButton.textContent = 'Sonraki →';
    nextButton.disabled = currentPage === totalPages;
    nextButton.onclick = () => fetchDrawings(currentPage + 1);
    nextButton.className = 'btn-secondary';
    nextButton.style.marginLeft = '10px';
    PAGINATION_ELEMENT.appendChild(nextButton);
}

/**
 * Çizimleri getir
 */
async function fetchDrawings(page = 1) {
    const { DRAWING_LIST_ELEMENT, PAGINATION_ELEMENT } = DOM_ELEMENTS;
    if (!DRAWING_LIST_ELEMENT) return;

    DRAWING_LIST_ELEMENT.innerHTML = '<p id="loading-message">Çizimler yükleniyor...</p>';
    if (PAGINATION_ELEMENT) PAGINATION_ELEMENT.innerHTML = '';

    try {
        const response = await fetch(SITE_BASE_URL + `core/list_drawings.php?page=${page}`);
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

/**
 * Takip edilenler akışını getir
 */
async function fetchFollowingFeed() {
    const { FOLLOWING_FEED_ELEMENT } = DOM_ELEMENTS;
    if (!FOLLOWING_FEED_ELEMENT) return;

    FOLLOWING_FEED_ELEMENT.innerHTML = '<p>Akış yükleniyor...</p>';

    try {
        const response = await fetch(SITE_BASE_URL + 'core/fetch_following_feed.php');
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
