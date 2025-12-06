// ============================================
// EDITOR BUTON OLUŞTURMA FONKSİYONLARI
// ============================================
/**
 * Aktif segmenti belirle
 */
function getActiveSegment() {
    const activeBtn = document.querySelector('.segment-btn.active');
    if (!activeBtn) return 'drawings';
    
    if (activeBtn.id === 'show-drawings') return 'drawings';
    if (activeBtn.id === 'show-floods') return 'floods';
    if (activeBtn.id === 'show-following') return 'following';
    
    return 'drawings';
}

/**
 * Oluşturma seçim modalını göster
 */
function showCreateChoiceModal(defaultType = null) {
    const modalHTML = `
        <div id="create-choice-modal" class="modal" style="display: flex;">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3>🎨 Yeni İçerik Oluştur</h3>
                    <span class="modal-close" onclick="closeCreateChoiceModal()">&times;</span>
                </div>
                
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0;">
                        <button id="choice-emoji" class="choice-card" data-type="emoji">
                            <div style="font-size: 2.5em; margin-bottom: 10px;">🎨</div>
                            <div style="font-weight: bold;">Emoji Çizim</div>
                            <div style="font-size: 0.9em; opacity: 0.8; margin-top: 5px;">
                                Pixel sanatı oluştur
                            </div>
                        </button>
                        
                        <button id="choice-flood" class="choice-card" data-type="flood">
                            <div style="font-size: 2.5em; margin-bottom: 10px;">🌊</div>
                            <div style="font-weight: bold;">Flood Set'i</div>
                            <div style="font-size: 0.9em; opacity: 0.8; margin-top: 5px;">
                                Mesaj koleksiyonu oluştur
                            </div>
                        </button>
                    </div>
                    
                    ${defaultType ? `
                        <div style="background: var(--fixed-bg); padding: 15px; border-radius: 8px; margin-top: 15px;">
                            <p style="margin: 0; font-size: 0.9em; opacity: 0.8;">
                                💡 <strong>İpucu:</strong> Aktif segment "${defaultType === 'flood' ? 'Flood Set\'leri' : 'Çizimler'}" olduğu için 
                                ${defaultType === 'flood' ? 'flood set' : 'emoji çizim'} oluşturma öneriliyor.
                            </p>
                        </div>
                    ` : ''}
                </div>
                
                <div class="modal-footer">
                    <button onclick="closeCreateChoiceModal()" class="btn-secondary">
                        İptal
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Modal'ı ekle
    const existingModal = document.getElementById('create-choice-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Event listener'ları bağla
    const modal = document.getElementById('create-choice-modal');
    const emojiChoice = document.getElementById('choice-emoji');
    const floodChoice = document.getElementById('choice-flood');
    const closeBtn = modal.querySelector('.modal-close');
    
    emojiChoice.addEventListener('click', () => {
        closeCreateChoiceModal();
        openEmojiEditor();
    });
    
    floodChoice.addEventListener('click', () => {
        closeCreateChoiceModal();
        openIntegratedEditor('flood');
    });
    
    closeBtn.addEventListener('click', closeCreateChoiceModal);
    
    // ESC tuşu ile kapatma
    document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') {
            closeCreateChoiceModal();
            document.removeEventListener('keydown', escHandler);
        }
    });
    
    // Background tıklama ile kapatma
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeCreateChoiceModal();
        }
    });
    
    // Varsayılan seçeneği highlight et
    if (defaultType === 'emoji') {
        emojiChoice.style.borderColor = 'var(--accent-color)';
        emojiChoice.style.boxShadow = '0 0 0 2px var(--accent-color)';
    } else if (defaultType === 'flood') {
        floodChoice.style.borderColor = 'var(--accent-color)';
        floodChoice.style.boxShadow = '0 0 0 2px var(--accent-color)';
    }
}

function closeCreateChoiceModal() {
    const modal = document.getElementById('create-choice-modal');
    if (modal) {
        modal.style.display = 'none';
        setTimeout(() => modal.remove(), 300);
    }
}

/**
 * Butonları başlat
 */
function initializeButtons() {
    console.log('🔘 Buton sistemi başlatılıyor...');    
    // 3. Editor butonlarını oluştur
    setTimeout(() => {
        // Kontrol paneli varsa buton ekle
        const controlsPanel = document.getElementById('controls-panel');
        if (controlsPanel) {
            // Hangi butonların olduğunu kontrol et
            const existingButtons = {
                emoji: document.getElementById('openEmojiEditorBtn'),
                flood: document.getElementById('openFloodEditorBtn')
            };
            
            // Sadece emoji butonu varsa, flood butonu da ekle
            if (existingButtons.emoji && !existingButtons.flood && !existingButtons.integrated) {
                ensureSingleButton('openFloodEditorBtn', createFloodEditorButton);
            }
            // Sadece flood butonu varsa, emoji butonu da ekle
            else if (!existingButtons.emoji && existingButtons.flood && !existingButtons.integrated) {
                ensureSingleButton('openEmojiEditorBtn', createEmojiEditorButton);
            }
        }
    }, 300);
}

// ============================================
// TEMEL SİSTEM FONKSİYONLARI
// ============================================

/**
 * Editör başlatma
 */
async function initializeEditor() {
    try {
        // Emojileri yükle
        if (typeof loadEmojis === 'function') {
            await loadEmojis();
        }
        
        // Flood editörü için
        if (window.floodSystem && typeof window.floodSystem.loadEmojiPalette === 'function') {
            await window.floodSystem.loadEmojiPalette();
        }
        
        console.log('✅ Tüm sistemler için emojiler yüklendi');

        // Editörü sadece matrix varsa başlat
        if (window.DOM_ELEMENTS?.matrixTable) {
            safeExecute('createMatrix', createMatrix);
            safeExecute('createCategoryTabs', createCategoryTabs);
            safeExecute('createPalette', createPalette);
            safeExecute('updateSelectedEmojiDisplay', updateSelectedEmojiDisplay);

            if (window.DOM_ELEMENTS.separatorSelect) {
                safeExecute('calculateSeparatorCharCosts', calculateSeparatorCharCosts);
            }

            console.log('🎨 Editör başlatıldı');
            safeExecute('showNotification', showNotification, '⚡ Emoji Piksel Sanat Editörü Hazır!', 'info', 2000);
        }

    } catch (error) {
        console.error('Editör başlatma hatası:', error);
    }
}

/**
 * Kullanıcı sistemlerini başlat
 */
function initializeUserSystems() {
    console.log('👤 Kullanıcı sistemleri başlatılıyor...');

    // Çevrimiçi durum
    setTimeout(() => {
        safeExecute('initOnlineStatus', initOnlineStatus);
    }, 1000);

    // Mesaj bildirimleri
    setTimeout(() => {
        safeExecute('updateMessageNotification', updateMessageNotification);
    }, 2000);

    // App instance'ını başlat
    setTimeout(() => {
        if (typeof App !== 'undefined') {
            const app = new App();
            app.init().then(() => {
                console.log('🎉 Uygulama modülleri hazır!');
            }).catch(err => {
                console.error('App başlatma hatası:', err);
            });
        }
    }, 3000);
}

/**
 * Sayfaya özel içerikleri başlat
 */
function initializePageSpecificContent() {
    setTimeout(() => {
        // Topluluk çizimleri
        if (document.getElementById('user-drawing-list') && typeof loadCommunityDrawings === 'function') {
            safeExecute('loadCommunityDrawings', loadCommunityDrawings);
        }

        // Takip edilenler akışı
        if (window.APP_DATA?.isLoggedIn && document.getElementById('following-feed-list') && typeof loadFollowingDrawings === 'function') {
            safeExecute('loadFollowingDrawings', loadFollowingDrawings);
        }

        // Çizim listesi
        if (document.getElementById('drawing-list') && typeof fetchDrawings === 'function') {
            safeExecute('fetchDrawings', fetchDrawings, 1);
        }

        // Profil yorumları
        if (document.getElementById('board-comments-list') && typeof fetchProfileComments === 'function') {
            safeExecute('fetchProfileComments', fetchProfileComments);
        }
    }, 1500);
}

/**
 * Emoji sistemini başlat
 */
async function initializeEmojiSystem() {
    console.log('🎨 Emoji sistemi başlatılıyor...');
    
    // DOM elementlerini kontrol et
    const hasEmojiEditor = document.getElementById('emoji-tab') || 
                          document.getElementById('matrixTable') ||
                          document.querySelector('[data-type="emoji"]');
    
    if (!hasEmojiEditor) {
        console.log('⚠️ Emoji editör elementi bulunamadı, emoji sistemi atlanıyor');
        return;
    }
    
    try {
        // Emojileri yükle
        if (typeof loadEmojis === 'function') {
            await loadEmojis();
        }
        
        // Flood editörü için
        if (window.floodSystem && typeof window.floodSystem.loadEmojiPalette === 'function') {
            await window.floodSystem.loadEmojiPalette();
        }
        
        console.log('✅ Tüm sistemler için emojiler yüklendi');
        
        // Editörü başlat
        await initializeEditor();
        
    } catch (error) {
        console.error('❌ Emoji sistemi başlatma hatası:', error);
    }
}

// ============================================
// SAYFA İÇERİK YÜKLEME FONKSİYONLARI
// ============================================

/**
 * Flood set'lerini getir
 */
async function fetchFloodSets(page = 1) {
    try {
        const floodFilter = document.getElementById('flood-filter');
        const floodSort = document.getElementById('flood-sort');
        const container = document.getElementById('flood-sets-grid');
        
        if (!container) {
            console.warn('Flood set grid konteyneri bulunamadı');
            return;
        }
        
        const filter = floodFilter ? floodFilter.value : 'all';
        const sort = floodSort ? floodSort.value : 'newest';
        
        const response = await fetch(`${window.SITE_BASE_URL}core/list_flood_sets.php?page=${page}&filter=${filter}&sort=${sort}`);
        const result = await response.json();
        
        if (result.success) {
            if (typeof displayFloodSets === 'function') {
                displayFloodSets(result.sets);
            }
            if (typeof createPagination === 'function') {
                createPagination('floods', page, result.totalPages);
            }
        }
    } catch (error) {
        console.error('Flood setleri yüklenemedi:', error);
    }
}

/**
 * Takip edilenleri getir
 */
async function fetchFollowingFeed() {
    const container = document.getElementById('following-feed-list');
    if (!container) return;
    
    container.innerHTML = '<p>Yükleniyor...</p>';
    
    try {
        const response = await fetch(`${window.SITE_BASE_URL}core/fetch_following_feed.php`);
        const result = await response.json();
        
        if (result.success && result.drawings.length > 0) {
            container.innerHTML = '';
            
            // Çizimleri göster
            result.drawings.forEach(drawing => {
                if (typeof createDrawingCard === 'function') {
                    const card = createDrawingCard(drawing);
                    container.appendChild(card);
                }
            });
            
            // Flood set'lerini göster
            if (result.flood_sets && result.flood_sets.length > 0) {
                const floodHeader = document.createElement('h5');
                floodHeader.textContent = '🌊 Takip Ettiklerim - Yeni Flood Set\'leri';
                floodHeader.style.margin = '20px 0 10px 0';
                container.appendChild(floodHeader);
                
                result.flood_sets.forEach(set => {
                    const setElement = document.createElement('div');
                    setElement.className = 'flood-set-mini';
                    setElement.style.cssText = 'padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; margin-bottom: 8px;';
                    
                    setElement.innerHTML = `
                        <div style="display: flex; justify-content: space-between;">
                            <strong>${escapeHtml(set.name)}</strong>
                            <span style="font-size: 0.8em;">${set.message_count} mesaj</span>
                        </div>
                        <div style="font-size: 0.8em; opacity: 0.7;">
                            ${escapeHtml(set.author_username)} • ${timeAgo(set.created_at)}
                        </div>
                    `;
                    
                    setElement.onclick = () => openFloodSet(set.id);
                    container.appendChild(setElement);
                });
            }
        } else {
            container.innerHTML = '<p>Takip ettiğiniz çizerlerin henüz yeni içeriği yok.</p>';
        }
    } catch (error) {
        console.error('Takip akışı yüklenemedi:', error);
        container.innerHTML = '<p style="color: red;">Yüklenirken hata oluştu.</p>';
    }
}

/**
 * Flood set'i aç
 */
function openFloodSet(setId) {
    window.open(`${window.SITE_BASE_URL}flood_set.php?id=${setId}`, '_blank');
}

/**
 * Sayfalama oluştur
 */
function createPagination(type, currentPage, totalPages) {
    const containerId = type === 'drawings' ? 'drawings-pagination' : 'floods-pagination';
    const container = document.getElementById(containerId);
    if (!container || totalPages <= 1) {
        if (container) container.innerHTML = '';
        return;
    }
    
    container.innerHTML = '';
    
    // Önceki butonu
    if (currentPage > 1) {
        const prevBtn = document.createElement('button');
        prevBtn.textContent = '← Önceki';
        prevBtn.className = 'btn-secondary';
        prevBtn.onclick = () => {
            if (type === 'drawings') fetchDrawings(currentPage - 1);
            else fetchFloodSets(currentPage - 1);
        };
        container.appendChild(prevBtn);
    }
    
    // Sayfa numaraları
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === currentPage ? 'btn-primary' : 'btn-secondary';
            pageBtn.style.margin = '0 2px';
            pageBtn.onclick = () => {
                if (type === 'drawings') fetchDrawings(i);
                else fetchFloodSets(i);
            };
            container.appendChild(pageBtn);
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.style.margin = '0 5px';
            container.appendChild(ellipsis);
        }
    }
    
    // Sonraki butonu
    if (currentPage < totalPages) {
        const nextBtn = document.createElement('button');
        nextBtn.textContent = 'Sonraki →';
        nextBtn.className = 'btn-secondary';
        nextBtn.onclick = () => {
            if (type === 'drawings') fetchDrawings(currentPage + 1);
            else fetchFloodSets(currentPage + 1);
        };
        container.appendChild(nextBtn);
    }
}

// ============================================
// YARDIMCI FONKSİYONLAR
// ============================================

// Buton oluşturma kontrol sistemi
let buttonsCreated = {
    emojiEditor: false,
    floodEditor: false,
    integratedEditor: false,
    communityButtons: false
};

// DOM element cache
let DOM_CACHE = {};

/**
 * Güvenli fonksiyon çalıştırma
 */
function safeExecute(fnName, fn, ...args) {
    try {
        if (typeof fn === 'function') {
            return fn(...args);
        } else {
            console.warn(`⚠️ ${fnName} fonksiyonu tanımlı değil`);
            return null;
        }
    } catch (error) {
        console.error(`❌ ${fnName} çalıştırılırken hata:`, error);
        return null;
    }
}

/**
 * Tek bir butonun oluşturulmasını sağlar
 */
function ensureSingleButton(buttonId, createFunction) {
    if (!document.getElementById(buttonId)) {
        const result = createFunction();
        console.log(`✅ ${buttonId} butonu oluşturuldu`);
        return result;
    } else {
        console.log(`⚠️ ${buttonId} butonu zaten mevcut`);
        return document.getElementById(buttonId);
    }
}

/**
 * HTML escape fonksiyonu
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Zaman formatı (az önce, 5 dk önce, vb.)
 */
function timeAgo(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'az önce';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes} dk önce`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours} sa önce`;
        const days = Math.floor(hours / 24);
        if (days < 7) return `${days} gün önce`;
        return date.toLocaleDateString('tr-TR');
    } catch (e) {
        return dateString;
    }
}

/**
 * Segment değiştirme
 */
function switchSegment(segment) {
    console.log(`🔄 Segment değiştiriliyor: ${segment}`);
    
    // Tüm segment butonlarını pasif yap
    document.querySelectorAll('.segment-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Tüm segment içeriklerini gizle
    document.querySelectorAll('.segment-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Seçilen segmenti aktif yap
    const segmentBtn = document.getElementById(`show-${segment}`);
    if (segmentBtn) {
        segmentBtn.classList.add('active');
    }
    
    // İlgili içeriği göster
    const contentId = segment === 'drawings' ? 'drawings-segment' : 
                     segment === 'floods' ? 'floods-segment' : 
                     'following-feed';
    const contentElement = document.getElementById(contentId);
    if (contentElement) {
        contentElement.style.display = 'block';
    }
    
    // İçeriği yükle
    switch(segment) {
        case 'drawings':
            if (typeof fetchDrawings === 'function') fetchDrawings(1);
            break;
        case 'floods':
            if (typeof fetchFloodSets === 'function') fetchFloodSets(1);
            break;
        case 'following':
            if (typeof fetchFollowingFeed === 'function') fetchFollowingFeed();
            break;
    }
}

/**
 * Segment switcher'ı başlat
 */
function initSegmentSwitcher() {
    const buttons = document.querySelectorAll('.segment-btn');
    if (buttons.length === 0) return;
    
    console.log(`🔍 ${buttons.length} segment butonu bulundu`);
    
    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            const segment = this.id.replace('show-', '');
            switchSegment(segment);
        });
    });
}

/**
 * Filtreleri başlat
 */
function initFilters() {
    // Çizim filtreleri
    const drawingCategoryFilter = document.getElementById('drawing-category-filter');
    const drawingSort = document.getElementById('drawing-sort');
    
    if (drawingCategoryFilter) {
        drawingCategoryFilter.addEventListener('change', () => {
            fetchDrawings(1);
        });
    }
    
    if (drawingSort) {
        drawingSort.addEventListener('change', () => {
            fetchDrawings(1);
        });
    }
    
    // Flood filtreleri
    const floodFilter = document.getElementById('flood-filter');
    const floodSort = document.getElementById('flood-sort');
    
    if (floodFilter) {
        floodFilter.addEventListener('change', () => {
            fetchFloodSets(1);
        });
    }
    
    if (floodSort) {
        floodSort.addEventListener('change', () => {
            fetchFloodSets(1);
        });
    }
}

// ============================================
// EVENT LISTENER YÖNETİMİ
// ============================================

/**
 * Buton event'lerini bağla
 */
function attachButtonEvents(dom) {
    // Load Button
    if (dom.loadButton && dom.fileInput) {
        dom.loadButton.addEventListener('click', () => dom.fileInput.click());
    }

    // File Input
    if (dom.fileInput) {
        dom.fileInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const text = e.target.result;
                    if (safeExecute('applyDrawingText', applyDrawingText, text)) {
                        safeExecute('showNotification', showNotification, '✅ Çizim dosyadan yüklendi!', 'success');
                    }
                };
                reader.readAsText(file);
                event.target.value = '';
            }
        });
    }

    // Clear Button
    if (dom.clearButton) {
        dom.clearButton.addEventListener('click', async () => {
            const confirmed = await safeExecute('showConfirm', showConfirm,
                                                "Çizimi Temizle",
                                                "Mevcut çizimi temizlemek istediğinizden emin misiniz?"
            );

            if (confirmed) {
                safeExecute('createMatrix', createMatrix);
                safeExecute('showNotification', showNotification, '🧹 Çizim temizlendi!', 'success');
            }
        });
    }

    // Guide Modal Buttons
    if (dom.showGuideButton && dom.guideModal) {
        dom.showGuideButton.addEventListener('click', () => {
            dom.guideModal.classList.add('show');
        });
    }

    if (dom.closeGuideButton && dom.guideModal) {
        dom.closeGuideButton.addEventListener('click', () => {
            dom.guideModal.classList.remove('show');
        });
    }

    // Tema değiştirme butonu
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            safeExecute('toggleDarkMode', toggleDarkMode);
        });
    }
}

/**
 * Event listener'ları başlat
 */
function initEventListeners() {
    console.log('🔗 Event listener\'lar bağlanıyor...');
    
    // DOM elementlerini cache'le
    cacheDomElements();
    
    const dom = window.DOM_ELEMENTS || DOM_CACHE;
    
    // Update Matrix Button
    if (dom.updateMatrixButton) {
        dom.updateMatrixButton.addEventListener('click', async () => {
            const confirmed = await safeExecute('showConfirm', showConfirm,
                "Matrisi Güncelle",
                "İlk satır çizilebilir piksel sayısını değiştirmek mevcut çizimi temizleyecektir. Devam etmek istiyor musunuz?"
            );

            if (confirmed && typeof createMatrix === 'function') {
                createMatrix();
                safeExecute('showNotification', showNotification, 'Matris başarıyla güncellendi!', 'success');
            }
        });
    }
    
    // SaveButton (eksikti, eklendi)
    if (dom.saveButton) {
        dom.saveButton.addEventListener('click', async () => {
            const drawingText = safeExecute('getDrawingText', getDrawingText, false);
            if (!drawingText) return;

            try {
                await handleSaveDrawing();
            } catch (err) {
                console.error('Kopyalama hatası:', err);
            }
        });
    }
    
    // Copy Button
    if (dom.copyButton) {
        dom.copyButton.addEventListener('click', async () => {
            if (typeof getDrawingText === 'function') {
                const drawingText = getDrawingText(false);
                if (drawingText) {
                    try {
                        await navigator.clipboard.writeText(drawingText);
                        safeExecute('showNotification', showNotification, '✅ Çizim panoya kopyalandı!', 'success');
                    } catch (err) {
                        console.error('Kopyalama hatası:', err);
                    }
                }
            }
        });
    }
    
    // Import Button
    if (dom.importButton) {
        dom.importButton.addEventListener('click', async () => {
            try {
                const text = await navigator.clipboard.readText();
                if (text && typeof applyDrawingText === 'function' && applyDrawingText(text)) {
                    safeExecute('showNotification', showNotification, '✅ Çizim panodan içe aktarıldı!', 'success');
                } else {
                    safeExecute('showNotification', showNotification, '❌ Panoda geçerli çizim bulunamadı', 'error');
                }
            } catch (err) {
                console.error('İçe aktarma hatası:', err);
                safeExecute('showNotification', showNotification, '❌ İçe aktarma başarısız oldu', 'error');
            }
        });
    }
    
    // Clear Button
    if (dom.clearButton) {
        dom.clearButton.addEventListener('click', async () => {
            const confirmed = await safeExecute('showConfirm', showConfirm,
                "Çizimi Temizle",
                "Mevcut çizimi temizlemek istediğinizden emin misiniz?"
            );

            if (confirmed && typeof createMatrix === 'function') {
                createMatrix();
                safeExecute('showNotification', showNotification, '🧹 Çizim temizlendi!', 'success');
            }
        });
    }
    
    // Guide Modal Buttons
    if (dom.showGuideButton && dom.guideModal) {
        dom.showGuideButton.addEventListener('click', () => {
            dom.guideModal.classList.add('show');
			dom.guideModal.style.cssText = `
				z-index: 10001;
			`;
        });
    }

    if (dom.closeGuideButton && dom.guideModal) {
        dom.closeGuideButton.addEventListener('click', () => {
            dom.guideModal.classList.remove('show');
        });
    }
    
    // Separator Select (eksikti, eklendi)
    if (dom.separatorSelect) {
        dom.separatorSelect.addEventListener('change', async () => {
            const newWidth = (dom.separatorSelect.value === 'SP_BS') ? SP_BS_MATRIX_WIDTH : DEFAULT_MATRIX_WIDTH;
            const currentDisplayedWidth = dom.matrixTable && dom.matrixTable.rows.length > 0 ? dom.matrixTable.rows[0].cells.length : DEFAULT_MATRIX_WIDTH;

            if (newWidth !== currentDisplayedWidth) {
                const confirmed = await safeExecute('showConfirm', showConfirm,
                                                    "Ayırıcı Değişikliği",
                                                    "Ayırıcı türünü değiştirmek matris boyutunu değiştirecek ve çizimi temizleyecektir. Devam etmek istiyor musunuz?"
                );

                if (confirmed) {
                    safeExecute('createMatrix', createMatrix);
                    safeExecute('showNotification', showNotification, `⚠️ Matris boyutu değiştirildi. Çizim temizlendi.`, 'warning');
                } else {
                    // İptal edildiyse eski değere dön
                    const prevValue = Array.from(dom.separatorSelect.options).find(opt =>
                    (opt.value === 'SP_BS' && currentDisplayedWidth === SP_BS_MATRIX_WIDTH) ||
                    (opt.value !== 'SP_BS' && currentDisplayedWidth === DEFAULT_MATRIX_WIDTH)
                    )?.value || 'none';
                    dom.separatorSelect.value = prevValue;
                }
            } else {
                safeExecute('updateCharacterCount', updateCharacterCount);
                const separatorName = SEPARATOR_MAP[dom.separatorSelect.value].name;
                safeExecute('showNotification', showNotification, `Ayırıcı ${separatorName} olarak ayarlandı.`, 'info');
            }
        });
    }
    
    // Logout Button (eksikti, eklendi)
    if (dom.logoutButton) {
        dom.logoutButton.addEventListener('click', (e) => {
            if (!confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                e.preventDefault();
            }
        });
    }
    
    // Mesaj butonları (eksikti, eklendi)
    const messageBtn = document.getElementById('messageButton');
    if (messageBtn) {
        messageBtn.addEventListener('click', function() {
            const targetId = this.dataset.targetId;
            const targetUsername = this.dataset.targetUsername;
            if (typeof openMessagesModal === 'function') {
                openMessagesModal();
            }
            setTimeout(() => {
                if (typeof selectConversation === 'function') {
                    selectConversation(targetId, targetUsername);
                }
            }, 500);
        });
    }
    
    // Tema değiştirme butonu
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            if (typeof toggleDarkMode === 'function') {
                toggleDarkMode();
            }
        });
    }
    
    // Buton event'lerini bağla
    attachButtonEvents(dom);
	bindMatrixWidthAndLimitEvents();
}

/**
 * Matris Genişliği ve Maksimum Karakter Limiti için event listener'lar
 */
function bindMatrixWidthAndLimitEvents() {
    console.log('🔧 Matris genişlik ve limit event listener\'ları bağlanıyor...');
    
    // 1. Matris Genişliği değiştiğinde
    const matrixWidthInput = document.getElementById('matrixWidth');
    if (matrixWidthInput) {
        // Input değeri değiştiğinde
        matrixWidthInput.addEventListener('input', function() {
            console.log('📏 Matris genişliği değişti:', this.value);
            
            // Genişlik değerini güncelle
            const newWidth = parseInt(this.value) || 10;
            window.CUSTOM_MATRIX_WIDTH = Math.max(1, Math.min(20, newWidth));
            
            // Matrisi yeniden oluştur (otomatik - buton gerekmez)
            safeExecute('createMatrix', createMatrix);
            
            // İlk satır piksel input'unun max değerini güncelle
            const firstRowLengthInput = document.getElementById('firstRowLength');
            if (firstRowLengthInput) {
                firstRowLengthInput.setAttribute('max', window.currentMatrixWidth.toString());
                
                // Eğer mevcut değer yeni max'tan büyükse, azalt
                if (parseInt(firstRowLengthInput.value) > window.currentMatrixWidth) {
                    firstRowLengthInput.value = window.currentMatrixWidth;
                }
            }
        });
        
        // Focus'tan çıkınca da güncelle
        matrixWidthInput.addEventListener('change', function() {
            // Zaten input event'inde hallediliyor
            console.log('Matris genişliği onaylandı:', this.value);
        });
    }
    
    // 2. Maksimum Karakter Limiti değiştiğinde
    const maxCharsInput = document.getElementById('maxCharsInput');
    if (maxCharsInput) {
        maxCharsInput.addEventListener('input', function() {
            console.log('🔢 Maksimum karakter limiti değişti:', this.value);
            
            const newLimit = parseInt(this.value) || 200;
            window.MAX_CHARACTERS = Math.max(50, Math.min(1000, newLimit));
            
            // Karakter limitini uygula (kırpma ve UI güncellemesi)
            safeExecute('updateCharacterCount', updateCharacterCount);
        });
        
        maxCharsInput.addEventListener('change', function() {
            console.log('Karakter limiti onaylandı:', this.value);
        });
    }
}

/**
 * DOM elementlerini cache'le
 */
function cacheDomElements() {
    DOM_CACHE = getDomElements();
}

// ============================================
// ANA BAŞLATICI FONKSİYON
// ============================================
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🚀 Emoji Sanat Uygulaması Başlatılıyor (Mevcut HTML Uyumlu)...');
    
    try {
        // 1. MEVCUT HTML ELEMENTLERİNİ KONTROL ET
        const checkExistingElements = () => {
            console.log('🔍 Mevcut HTML elementleri kontrol ediliyor:');
            
            const criticalElements = {
                // Emoji editör elementleri (emoji_editor_modal.php'de var)
                'matrix': document.getElementById('matrix'),
                'firstRowLength': document.getElementById('firstRowLength'),
                'matrixWidth': document.getElementById('matrixWidth'),
                'maxCharsInput': document.getElementById('maxCharsInput'),
                'separator-select': document.getElementById('separator-select'),
                'color-options-container': document.getElementById('color-options-container'),
                'category-tabs': document.getElementById('category-tabs'),
                'current-brush-emoji': document.getElementById('current-brush-emoji'),
                'current-brush-name': document.getElementById('current-brush-name'),
                
                // Modal elementleri (modals.php'de var)
                'emoji-tab': document.getElementById('emoji-tab'),
                'flood-tab': document.getElementById('flood-tab'),
                'flood-editor-container': document.getElementById('flood-editor-container'),
                
                // Butonlar (emoji_editor_modal.php'de var)
                'updateMatrixButton': document.getElementById('updateMatrixButton'),
                'copyButton': document.getElementById('copyButton'),
                'saveButton': document.getElementById('saveButton'),
                'clearButton': document.getElementById('clearButton'),
                'showGuideButton': document.getElementById('showGuideButton'),
                
                // Topluluk elementleri
                'user-drawing-list': document.getElementById('user-drawing-list'),
                'following-feed-list': document.getElementById('following-feed-list'),
                'community-create-btn': document.getElementById('community-create-btn'),
				'flood-editor-container': document.getElementById('flood-editor-container'),
				'flood-message-input': document.getElementById('flood-message-input'),
				'flood-emoji-container': document.getElementById('flood-emoji-container'),
				'flood-set-select': document.getElementById('flood-set-select'),
            };
            
            Object.entries(criticalElements).forEach(([id, element]) => {
                console.log(`  ${id}: ${element ? '✅ Var' : '❌ Yok'}`);
            });
            
            return criticalElements;
        };
        
        const elements = checkExistingElements();
        
        // 2. Temel sistem kontrolleri
        if (!window.SITE_BASE_URL) {
            console.warn('⚠️ SITE_BASE_URL tanımlı değil, otomatik belirleniyor...');
            window.SITE_BASE_URL = window.location.protocol + '//' + window.location.host + '/';
        }

        console.log('🌐 Site URL:', window.SITE_BASE_URL);
        console.log('👤 Kullanıcı:', window.currentUser);
        console.log('🔧 Mevcut HTML yapısına göre sistem hazırlanıyor...');

        // 3. Temel sistemleri başlat (mevcut modalları kullanarak)
        setTimeout(() => {
            safeExecute('initThemeSystem', initThemeSystem);
            
            // MODAL SİSTEMİ: Mevcut modalları kullan, yenilerini oluşturma
            safeExecute('initModalSystem', function() {
                console.log('🎯 Mevcut modal sistemi başlatılıyor...');
                
                // Mevcut modal kapatma butonlarını bağla
                document.querySelectorAll('.modal-close').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const modal = this.closest('.modal');
                        if (modal) modal.style.display = 'none';
                    });
                });
                
                // Modal toggle butonlarını bağla
                document.querySelectorAll('[data-modal-toggle]').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const modalId = this.getAttribute('data-modal-toggle');
                        const modal = document.getElementById(modalId);
                        if (modal) modal.style.display = 'flex';
                    });
                });
                
                // Modal switch butonlarını bağla (login/register switch)
                document.querySelectorAll('[data-modal-switch]').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const fromModal = this.closest('.modal');
                        const toModalId = this.getAttribute('data-modal-switch');
                        const toModal = document.getElementById(toModalId);
                        
                        if (fromModal && toModal) {
                            fromModal.style.display = 'none';
                            toModal.style.display = 'flex';
                        }
                    });
                });
                
                console.log('✅ Mevcut modal sistemi başlatıldı');
            });
        }, 100);

        // 4. DOM elementlerini başlat (mevcut fonksiyonu kullan)
        safeExecute('getDomElements', function() {
            window.DOM_ELEMENTS = getDomElements();
            console.log('🏗️ DOM elementleri hazır:', Object.keys(window.DOM_ELEMENTS || {}).length, 'adet');
        });

        // 5. EMOJİ SİSTEMİNİ BAŞLAT (ÖNCELİKLİ - MEVCUT HTML'E GÖRE)
        setTimeout(() => {
            console.log('🎨 Emoji sistemi başlatılıyor (mevcut HTML)...');
            
            // KRİTİK: calculateChatChars fonksiyonunu kontrol et
            safeExecute('calculateChatChars', function() {
                if (typeof calculateChatChars !== 'function') {
                    console.error('❌ calculateChatChars fonksiyonu bulunamadı!');
                    
                    // Fallback tanımla
                    window.calculateChatChars = function(text) {
                        return text ? text.length : 1;
                    };
                    console.log('⚠️ calculateChatChars fallback tanımlandı');
                }
            });
            
            // A. Emojileri yükle
            safeExecute('loadEmojis', async function() {
                console.log('📦 Emojiler yükleniyor...');
                try {
                    await loadEmojis();
                    console.log('✅ Emojiler yüklendi');
                } catch (error) {
                    console.error('❌ Emojiler yüklenemedi:', error);
                }
            });
            
            // B. Matrisi oluştur (eğer matrix element varsa)
            if (elements.matrix) {
                safeExecute('createMatrix', function() {
                    console.log('📊 Matris oluşturuluyor...');
                    try {
                        createMatrix();
                        console.log('✅ Matris oluşturuldu');
                    } catch (error) {
                        console.error('❌ Matris oluşturulamadı:', error);
                    }
                });
            }
            
            // C. Kategori sekmelerini oluştur
            if (elements['category-tabs']) {
                safeExecute('createCategoryTabs', function() {
                    console.log('📑 Kategori sekmeleri oluşturuluyor...');
                    try {
                        createCategoryTabs();
                        console.log('✅ Kategori sekmeleri oluşturuldu');
                    } catch (error) {
                        console.error('❌ Kategori sekmeleri oluşturulamadı:', error);
                    }
                });
            }
            
            // D. Emoji paletini oluştur
            if (elements['color-options-container']) {
                safeExecute('createPalette', function() {
                    console.log('🎨 Emoji paleti oluşturuluyor...');
                    try {
                        createPalette();
                        console.log('✅ Emoji paleti oluşturuldu');
                    } catch (error) {
                        console.error('❌ Emoji paleti oluşturulamadı:', error);
                    }
                });
            }
            
            // E. Karakter sayısını güncelle
            safeExecute('updateCharacterCount', function() {
                setTimeout(() => {
                    try {
                        updateCharacterCount();
                        console.log('🔢 Karakter sayısı güncellendi');
                    } catch (error) {
                        console.error('❌ Karakter sayısı güncellenemedi:', error);
                    }
                }, 500);
            });
        }, 200);

// 6. BUTON SİSTEMİNİ BAŞLAT (GÜNCELLENMİŞ)
setTimeout(() => {
    console.log('🔘 Buton sistemi başlatılıyor...');
    
    // AttachButtonEvents tarafından zaten işlenen butonları tanımla
    const alreadyHandledByAttachButtonEvents = [
        'loadButton', 'fileInput', 'clearButton', 'showGuideButton', 
        'closeGuideButton', 'theme-toggle', 'updateMatrixButton', 'copyButton',
        'saveButton', 'importButton'
    ];
	
	initEventListeners();
    
    // TOPLULUK BUTONU - ÖZEL İŞLEM
    const communityCreateBtn = document.getElementById('community-create-btn');
    if (communityCreateBtn && !communityCreateBtn.hasListener) {
        communityCreateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🚀 Yeni Oluştur butonu tıklandı');
            
            // 1. ENTEGRE EDITOR MODALINI AÇ (ÖNCELİK)
            const integratedModal = document.getElementById('integrated-editor-modal');
            if (integratedModal) {
                console.log('✅ Entegre editör modalı bulundu');
				integratedModal.style.cssText = `
					display: flex !important;
					position: fixed !important;
					top: 0 !important;
					left: 0 !important;
					width: 100vw !important;
					height: 100vh !important;
					justify-content: center !important;
					align-items: center !important;
					visibility: visible;
					opacity: 1;
				`;
				const content = integratedModal.querySelector('.modal-content');
				if (content) {
					content.style.cssText = `
						position: relative !important;
						border-radius: 12px !important;
						max-width: 100% !important;
						width: 100vw !important;
						max-height: 100vh !important;
						overflow: auto !important;
						visibility: visible;
						opacity: 1;
					`;
				}
                
                // Entegre editor'ü başlat
                if (window.integratedEditor && window.integratedEditor.openModal) {
                    window.integratedEditor.openModal();
                    
                    // Aktif segmente göre editor seç
                    const activeSegment = document.querySelector('.segment-btn.active');
                    if (activeSegment && activeSegment.id === 'show-floods') {
                        console.log('🌊 Flood segmenti aktif');
                        setTimeout(() => {
                            if (window.integratedEditor.switchEditor) {
                                window.integratedEditor.switchEditor('flood');
                            }
                        }, 150);
                    }
                }
                return;
            }
            
            // 2. FALLBACK: MEVCUT MODALLAR
            console.log('⚠️ Entegre editör bulunamadı, fallback...');
            const activeSegment = document.querySelector('.segment-btn.active');
            if (activeSegment && activeSegment.id === 'show-floods') {
                const floodModal = document.getElementById('flood-tab');
                if (floodModal) floodModal.style.display = 'flex';
            } else {
                const emojiModal = document.getElementById('emoji-tab');
                if (emojiModal) emojiModal.style.display = 'flex';
            }
        });
        
        communityCreateBtn.hasListener = true;
        console.log('✅ Community create butonu eklendi');
    }
       
    // FLOOD BUTONLARI
    const initializeFloodButtons = () => {
        const floodButtons = [
            { id: 'save-flood-message-btn', func: () => window.floodSystem?.saveFloodMessage?.() },
            { id: 'flood-copy-button', func: () => window.floodSystem?.copyFloodMessage?.() },
            { id: 'flood-clear-button', func: () => window.floodSystem?.clearEditor?.() },
            { id: 'flood-insert-random', func: () => window.floodSystem?.insertRandomEmoji?.() }
        ];
        
        floodButtons.forEach(btnConfig => {
            const button = document.getElementById(btnConfig.id);
            if (button && !button.hasListener) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (btnConfig.func) {
                        try {
                            btnConfig.func();
                        } catch (error) {
                            console.error(`❌ Flood butonu ${btnConfig.id} hatası:`, error);
                        }
                    }
                });
                button.hasListener = true;
                console.log(`✅ Flood butonu eklendi: ${btnConfig.id}`);
            }
        });
    };
    
    setTimeout(initializeFloodButtons, 1000);
    
    console.log('✅ Buton sistemi başlatıldı');
    
}, 400);

        // 7. ENTEGRE EDITOR SİSTEMİNİ BAŞLAT
        setTimeout(() => {
            console.log('🔄 Entegre editör sistemi kontrol ediliyor...');
            
            safeExecute('integratedEditor.init', function() {
                if (window.integratedEditor && typeof window.integratedEditor.init === 'function') {
                    console.log('🚀 Entegre editör sistemi başlatılıyor...');
                    
                    // Tab butonlarını bul (modals.php'de var)
                    const emojiTabBtn = document.querySelector('[data-tab="emoji"]');
                    const floodTabBtn = document.querySelector('[data-tab="flood"]');
                    
                    if (emojiTabBtn && floodTabBtn) {
                        console.log('✅ Tab butonları bulundu');
                        
                        // Event listener'ları ekle
                        safeExecute('emoji-tab-listener', function() {
                            emojiTabBtn.addEventListener('click', function(e) {
                                e.preventDefault();
                                safeExecute('integratedEditor.switchEditor.emoji', function() {
                                    if (window.integratedEditor) {
                                        try {
                                            window.integratedEditor.switchEditor('emoji');
                                        } catch (error) {
                                            console.error('❌ Emoji editor değiştirme hatası:', error);
                                        }
                                    }
                                });
                            });
                        });
                        
                        safeExecute('flood-tab-listener', function() {
                            floodTabBtn.addEventListener('click', function(e) {
                                e.preventDefault();
                                safeExecute('integratedEditor.switchEditor.flood', function() {
                                    if (window.integratedEditor) {
                                        try {
                                            window.integratedEditor.switchEditor('flood');
                                            
                                            // Flood editörünü başlat
                                            setTimeout(() => {
                                                safeExecute('floodSystem.renderFloodTab', function() {
                                                    if (window.floodSystem && typeof window.floodSystem.renderFloodTab === 'function') {
                                                        try {
                                                            window.floodSystem.renderFloodTab();
                                                        } catch (error) {
                                                            console.error('❌ Flood tab render hatası:', error);
                                                        }
                                                    }
                                                });
                                            }, 100);
                                        } catch (error) {
                                            console.error('❌ Flood editor değiştirme hatası:', error);
                                        }
                                    }
                                });
                            });
                        });
                    }
                    
                    // Integrated editor'ü başlat
                    setTimeout(() => {
                        safeExecute('integratedEditor.init.delayed', function() {
                            try {
                                window.integratedEditor.init();
                            } catch (error) {
                                console.error('❌ Entegre editör başlatma hatası:', error);
                            }
                        });
                    }, 200);
                } else {
                    console.warn('⚠️ Entegre editör sistemi bulunamadı');
                }
            });
        }, 600);
        
        // 8. SEGMENT SWITCHER ve FİLTRELERİ BAŞLAT
        setTimeout(() => {
            safeExecute('initSegmentSwitcher', function() {
                if (document.querySelectorAll('.segment-btn').length > 0) {
                    try {
                        initSegmentSwitcher();
                    } catch (error) {
                        console.error('❌ Segment switcher başlatma hatası:', error);
                    }
                }
            });
            
            safeExecute('initFilters', function() {
                if (document.getElementById('drawing-category-filter') || document.getElementById('flood-filter')) {
                    try {
                        initFilters();
                    } catch (error) {
                        console.error('❌ Filtre başlatma hatası:', error);
                    }
                }
            });
        }, 800);
        
        // 9. EVENT LISTENER'LARI BAŞLAT
        setTimeout(() => {
            console.log('🎯 Event listener\'lar başlatılıyor...');
            
            // Matris genişliği değişikliği
            const matrixWidthInput = document.getElementById('matrixWidth');
            if (matrixWidthInput) {
                matrixWidthInput.addEventListener('change', function() {
                    safeExecute('createMatrix.matrixWidth', function() {
                        try {
                            createMatrix();
                        } catch (error) {
                            console.error('❌ Matris genişliği değişikliği hatası:', error);
                        }
                    });
                });
            }
            
            // Karakter limiti değişikliği
            const maxCharsInput = document.getElementById('maxCharsInput');
            if (maxCharsInput) {
                maxCharsInput.addEventListener('change', function() {
                    safeExecute('updateCharacterCount.maxChars', function() {
                        try {
                            updateCharacterCount();
                        } catch (error) {
                            console.error('❌ Karakter limiti değişikliği hatası:', error);
                        }
                    });
                });
            }
            
            // Ayırıcı seçimi değişikliği
            const separatorSelect = document.getElementById('separator-select');
            if (separatorSelect) {
                separatorSelect.addEventListener('change', function() {
                    safeExecute('updateCharacterCount.separator', function() {
                        try {
                            updateCharacterCount();
                        } catch (error) {
                            console.error('❌ Ayırıcı seçimi değişikliği hatası:', error);
                        }
                    });
                });
            }
            
            // Satır sonu seçimi değişikliği
            const lineBreakSelect = document.getElementById('line-break-select');
            if (lineBreakSelect) {
                lineBreakSelect.addEventListener('change', function() {
                    safeExecute('updateCharacterCount.lineBreak', function() {
                        try {
                            updateCharacterCount();
                        } catch (error) {
                            console.error('❌ Satır sonu seçimi değişikliği hatası:', error);
                        }
                    });
                });
            }
            
            console.log('✅ Event listener\'lar başlatıldı');
        }, 1000);

        // 10. KULLANICI SİSTEMLERİNİ BAŞLAT (giriş yapılmışsa)
        setTimeout(() => {
            if (window.APP_DATA?.isLoggedIn) {
                console.log('👤 Kullanıcı sistemleri başlatılıyor...');
                
                // Takip edilenler çizimlerini yükle
                if (document.getElementById('following-feed-list')) {
                    safeExecute('loadFollowingDrawings', function() {
                        setTimeout(() => {
                            safeExecute('loadFollowingDrawings.delayed', function() {
                                try {
                                    loadFollowingDrawings();
                                } catch (error) {
                                    console.error('❌ Takip edilenler çizimleri yükleme hatası:', error);
                                }
                            });
                        }, 1500);
                    });
                }
                
                // Mesaj bildirimlerini kontrol et
                safeExecute('checkNewMessages', function() {
                    if (typeof checkNewMessages === 'function') {
                        try {
                            setInterval(function() {
                                safeExecute('checkNewMessages.interval', function() {
                                    try {
                                        checkNewMessages();
                                    } catch (error) {
                                        console.error('❌ Mesaj bildirimi kontrol hatası:', error);
                                    }
                                });
                            }, 60000); // Her 60 saniyede bir
                        } catch (error) {
                            console.error('❌ Mesaj bildirimi interval hatası:', error);
                        }
                    }
                });
            }
        }, 1200);

        // 11. SAYFAYA ÖZEL İÇERİKLERİ YÜKLE
        setTimeout(() => {
            console.log('📄 Sayfaya özel içerikler yükleniyor...');
            
            // Topluluk çizimleri
            if (document.getElementById('user-drawing-list')) {
                safeExecute('loadCommunityDrawings', function() {
                    setTimeout(() => {
                        safeExecute('loadCommunityDrawings.delayed', function() {
                            try {
                                loadCommunityDrawings();
                            } catch (error) {
                                console.error('❌ Topluluk çizimleri yükleme hatası:', error);
                            }
                        });
                    }, 2000);
                });
            }
            
            // Profil sayfası özellikleri
            if (document.querySelector('.profile-username')) {
                safeExecute('profileSystem.init', function() {
                    if (typeof profileSystem !== 'undefined' && typeof profileSystem.init === 'function') {
                        setTimeout(() => {
                            safeExecute('profileSystem.init.delayed', function() {
                                try {
                                    profileSystem.init();
                                } catch (error) {
                                    console.error('❌ Profil sistemi başlatma hatası:', error);
                                }
                            });
                        }, 2500);
                    }
                });
            }
        }, 1400);

        // 13. Flood kart sistemini başlat
        setTimeout(() => {
            safeExecute('floodCardSystem.init', function() {
                if (typeof window.floodCardSystem !== 'undefined') {
                    try {
                        window.floodCardSystem.init();
                    } catch (error) {
                        console.error('❌ Flood kart sistemi başlatma hatası:', error);
                    }
                }
            });
        }, 1600);

        // 14. GLOBAL FONKSİYON KONTROLÜ
        console.log('🔧 Global fonksiyon kontrolü:');
        const globalChecks = [
            ['calculateChatChars', calculateChatChars],
            ['loadEmojis', loadEmojis],
            ['createMatrix', createMatrix],
            ['updateCharacterCount', updateCharacterCount],
            ['copyMatrixToClipboard', copyMatrixToClipboard],
            ['handleSaveDrawing', handleSaveDrawing]
        ];
        
        globalChecks.forEach(([name, func]) => {
            console.log(`- ${name}:`, typeof func === 'function' ? '✅ Var' : '❌ Yok');
        });
        
        // 15. Emoji sistemini zorunlu yenile
        setTimeout(() => {
            safeExecute('createMatrix.final', function() {
                if (typeof createMatrix === 'function') {
                    try {
                        createMatrix();
                    } catch (error) {
                        console.error('❌ Son matris oluşturma hatası:', error);
                    }
                }
            });
            
            safeExecute('createCategoryTabs.final', function() {
                if (typeof createCategoryTabs === 'function') {
                    try {
                        createCategoryTabs();
                    } catch (error) {
                        console.error('❌ Son kategori sekmeleri hatası:', error);
                    }
                }
            });
            
            safeExecute('createPalette.final', function() {
                if (typeof createPalette === 'function') {
                    try {
                        createPalette();
                    } catch (error) {
                        console.error('❌ Son emoji paleti hatası:', error);
                    }
                }
            });
        }, 100);
        
        console.log('- integratedEditor:', window.integratedEditor ? '✅ Var' : '❌ Yok');
        console.log('- floodSystem:', window.floodSystem ? '✅ Var' : '❌ Yok');
        console.log('- SEPARATOR_MAP:', window.SEPARATOR_MAP ? '✅ Var' : '❌ Yok');
        console.log('- LINE_BREAK_MAP:', window.LINE_BREAK_MAP ? '✅ Var' : '❌ Yok');

        console.log('✅ Uygulama başlatma işlemleri tamamlandı');
    
    } catch (error) {
        console.error('❌ Uygulama başlatma hatası:', error);
        
        // Hata türüne göre spesifik mesaj
        if (error instanceof ReferenceError) {
            const missingFunc = error.message.match(/(\w+) is not defined/)?.[1];
            console.error(`⚠️ Eksik fonksiyon: ${missingFunc}`);
            
            // Sadece logla, kullanıcıyı rahatsız etme
            safeExecute('showNotification.missingFunc', function() {
                if (typeof showNotification === 'function') {
                    try {
                        showNotification(`Sistem başlatılıyor (${missingFunc} eksik)`, 'info', 2000);
                    } catch (notifyError) {
                        console.error('❌ Bildirim gönderme hatası:', notifyError);
                    }
                }
            });
        } else {
            // Diğer hatalar için bildirim
            safeExecute('showNotification.general', function() {
                if (typeof showNotification === 'function') {
                    try {
                        showNotification('Sistem başlatılıyor', 'info');
                    } catch (notifyError) {
                        console.error('❌ Genel bildirim hatası:', notifyError);
                    }
                }
            });
        }
    }
});

/**
 * Güvenli fonksiyon çalıştırıcı
 */
function safeExecute(name, func, ...args) {
    try {
        if (typeof func === 'function') {
            return func(...args);
        } else {
            console.warn(`⚠️ ${name} fonksiyonu bulunamadı veya fonksiyon değil`);
        }
    } catch (error) {
        console.error(`❌ ${name} çalıştırma hatası:`, error);
    }
    return null;
}

// ============================================
// GLOBAL FONKSİYONLAR
// ============================================
window.toggleDarkMode = toggleDarkMode;
window.handleSaveDrawing = handleSaveDrawing;
window.handleBoardFileSelect = handleBoardFileSelect;
window.clearBoardFile = clearBoardFile;
window.postProfileComment = postProfileComment;
window.fetchProfileComments = fetchProfileComments;
window.applyDrawingText = applyDrawingText;
window.deleteComment = deleteComment;
window.openMediaViewer = openMediaViewer;
window.handleProfileFollowAction = handleProfileFollowAction;
window.safeExecute = safeExecute;
window.switchSegment = switchSegment;
window.fetchDrawings = fetchDrawings;
window.fetchFloodSets = fetchFloodSets;
window.fetchFollowingFeed = fetchFollowingFeed;
window.ensureSingleButton = ensureSingleButton;
window.escapeHtml = escapeHtml;
window.timeAgo = timeAgo;
window.getDomElements = getDomElements;
window.initializeUserSystems = initializeUserSystems;
window.initializePageSpecificContent = initializePageSpecificContent;
window.attachButtonEvents = attachButtonEvents;
window.initializeEditor = initializeEditor;
window.initializeEmojiSystem = initializeEmojiSystem;
window.initEventListeners = initEventListeners;
window.cacheDomElements = cacheDomElements;
window.initSegmentSwitcher = initSegmentSwitcher;
window.initFilters = initFilters;
window.createPagination = createPagination;
window.showCreateChoiceModal = showCreateChoiceModal;
window.closeCreateChoiceModal = closeCreateChoiceModal;
window.getActiveSegment = getActiveSegment;
// ============================================
// EDITOR AÇMA FONKSİYONLARI (GLOBAL)
// ============================================
/**
 * Emoji editörü aç
 */
window.openEmojiEditor = function() {
    console.log('🎨 Emoji editor açılıyor (global)');
    const modal = document.getElementById('emoji-tab');
    if (modal) {
        modal.style.display = 'flex';
        
        // Emoji sistemini başlat
        setTimeout(() => {
            if (typeof loadEmojis === 'function') loadEmojis();
            if (window.floodSystem && typeof window.floodSystem.loadEmojiPalette === 'function') window.floodSystem.loadEmojiPalette();
            if (typeof createMatrix === 'function') createMatrix();
            if (typeof createCategoryTabs === 'function') createCategoryTabs();
            if (typeof createPalette === 'function') createPalette();
        }, 100);
        
        return true;
    }
    console.error('Emoji editor modalı bulunamadı');
    return false;
};

/**
 * Flood editörü aç
 */
window.openFloodEditor = function() {
    console.log('🌊 Flood editor açılıyor (global)');
    const modal = document.getElementById('flood-tab');
    if (modal) {
        modal.style.display = 'flex';
        
        // Flood sistemini başlat
        setTimeout(() => {
            if (window.floodSystem && typeof window.floodSystem.init === 'function') {
                window.floodSystem.init();
            }
        }, 100);
        
        return true;
    }
    console.error('Flood editor modalı bulunamadı');
    return false;
};

/**
 * Entegre editörü aç
 */
window.openIntegratedEditor = function(editorType = null) {
    console.log('🚀 Integrated editor açılıyor:', editorType);
    
    const integratedModal = document.getElementById('integrated-editor-modal');
    if (integratedModal) {
        integratedModal.style.display = 'flex';
        
        if (window.integratedEditor) {
            setTimeout(() => {
                window.integratedEditor.init();
                if (editorType) {
                    window.integratedEditor.switchEditor(editorType);
                }
            }, 100);
        }
        return true;
    }
    
    console.error('Entegre editor modalı bulunamadı');
    return false;
};

// Diğer eksik global fonksiyonlar
if (typeof window.openMessagesModal === 'undefined') {
    window.openMessagesModal = function() {
        console.log('💬 Mesajlar modalı açılıyor');
        const modal = document.getElementById('messages-modal');
        if (modal) {
            modal.style.display = 'flex';
            return true;
        }
        return false;
    };
}

if (typeof window.selectConversation === 'undefined') {
    window.selectConversation = function(targetId, targetUsername) {
        console.log(`💬 Konuşma seçiliyor: ${targetUsername} (${targetId})`);
        // Bu fonksiyon mesajlaşma sistemi tarafından tanımlanmalı
    };
}

if (typeof window.initOnlineStatus === 'undefined') {
    window.initOnlineStatus = function() {
        console.log('🌐 Çevrimiçi durum sistemi başlatılıyor');
    };
}

if (typeof window.updateMessageNotification === 'undefined') {
    window.updateMessageNotification = function() {
        console.log('🔔 Mesaj bildirimleri güncelleniyor');
    };
}

if (typeof window.loadCommunityDrawings === 'undefined') {
    window.loadCommunityDrawings = function() {
        console.log('🎨 Topluluk çizimleri yükleniyor');
        fetchDrawings(1);
    };
}

if (typeof window.loadFollowingDrawings === 'undefined') {
    window.loadFollowingDrawings = function() {
        console.log('👥 Takip edilenlerin çizimleri yükleniyor');
        fetchFollowingFeed();
    };
}

// Editör kapatma fonksiyonları (eksikti, eklendi)
if (typeof window.closeEmojiEditor === 'undefined') {
    window.closeEmojiEditor = function() {
        const modal = document.getElementById('emoji-tab');
        if (modal) modal.style.display = 'none';
    };
}

if (typeof window.closeFloodEditor === 'undefined') {
    window.closeFloodEditor = function() {
        const modal = document.getElementById('flood-tab');
        if (modal) modal.style.display = 'none';
        
        if (window.floodSystem && typeof window.floodSystem.closeEditor === 'function') {
            window.floodSystem.closeEditor();
        }
    };
}

// Hata yakalama
window.addEventListener('error', function(e) {
    console.error('🚨 Global hata:', e.error);
    safeExecute('showNotification', showNotification, 'Bir hata oluştu: ' + e.message, 'error');
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('🚨 İşlenmemiş promise hatası:', e.reason);
    safeExecute('showNotification', showNotification, 'Beklenmeyen bir hata oluştu', 'error');
});

console.log('✅ Main.js başarıyla yüklendi (eksiksiz sürüm)');