// assets/js/main.js

// Güvenli fonksiyon çalıştırma yardımcısı
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

// Global fonksiyonları window objesine ekle (HTML'den erişim için)
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

// Ana uygulama başlatıcı - BASİT ve GÜVENLİ
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🚀 Emoji Sanat Uygulaması Başlatılıyor...');

    try {
        // 1. Temel sistem kontrolleri
        if (!window.SITE_BASE_URL) {
            console.error('❌ Kritik hata: SITE_BASE_URL tanımlı değil!');
            return;
        }

        console.log('🌐 Site URL:', window.SITE_BASE_URL);
        console.log('👤 Kullanıcı:', window.currentUser);
        console.log('🔧 Sistem hazırlanıyor...');

        // 2. Tema sistemini başlat
        safeExecute('initThemeSystem', initThemeSystem);

        // 3. Modal sistemini başlat
        safeExecute('initModalSystem', initModalSystem);

        // 4. DOM elementlerini başlat
        window.DOM_ELEMENTS = getDomElements();
        console.log('🏗️ DOM elementleri hazır');

        // 5. Emojileri yükle ve editörü başlat
        await initializeEditor();

        // 6. Event listener'ları başlat
        safeExecute('initEventListeners', initEventListeners);

        // 7. Kullanıcı sistemlerini başlat (giriş yapılmışsa)
        if (window.APP_DATA.isLoggedIn) {
            initializeUserSystems();
        }

        // 8. Sayfaya özel içerikleri yükle
        initializePageSpecificContent();

        console.log('✅ Uygulama başarıyla başlatıldı');

    } catch (error) {
        console.error('❌ Uygulama başlatma hatası:', error);
        safeExecute('showNotification', showNotification, 'Uygulama başlatılırken hata oluştu', 'error');
    }
});

// Editör başlatma
async function initializeEditor() {
    try {
        // Emojileri yükle
        if (typeof loadEmojis === 'function') {
            await loadEmojis();
            console.log('😊 Emojiler yüklendi');
        } else {
            console.warn('⚠️ loadEmojis fonksiyonu bulunamadı');
            return;
        }

        // Editörü sadece matrix varsa başlat
        if (window.DOM_ELEMENTS.matrixTable) {
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

// Kullanıcı sistemlerini başlat
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

// Sayfaya özel içerikleri başlat
function initializePageSpecificContent() {
    setTimeout(() => {
        // Topluluk çizimleri
        if (document.getElementById('user-drawing-list') && typeof loadCommunityDrawings === 'function') {
            safeExecute('loadCommunityDrawings', loadCommunityDrawings);
        }

        // Takip edilenler akışı
        if (window.APP_DATA.isLoggedIn && document.getElementById('following-feed-list') && typeof loadFollowingDrawings === 'function') {
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

// Event listener'ları yönet
function initEventListeners() {
    const dom = window.DOM_ELEMENTS;
    if (!dom) {
        console.warn('⚠️ DOM elementleri bulunamadı');
        return;
    }

    console.log('🔗 Event listener\'lar bağlanıyor...');

    // Update Matrix Button
    if (dom.updateMatrixButton) {
        dom.updateMatrixButton.addEventListener('click', async () => {
            const confirmed = await safeExecute('showConfirm', showConfirm,
                                                "Matrisi Güncelle",
                                                "İlk satır çizilebilir piksel sayısını değiştirmek mevcut çizimi temizleyecektir. Devam etmek istiyor musunuz?"
            );

            if (confirmed) {
                safeExecute('createMatrix', createMatrix);
                safeExecute('showNotification', showNotification, 'Matris başarıyla güncellendi!', 'success');
            }
        });
    }

    // Separator Select
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

    // SaveButton
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
            const drawingText = safeExecute('getDrawingText', getDrawingText, false);
            if (!drawingText) return;

            try {
                await navigator.clipboard.writeText(drawingText);
                const separatorName = SEPARATOR_MAP[dom.separatorSelect.value].name;
                safeExecute('showNotification', showNotification, `✅ Çizim panoya kopyalandı! (${separatorName} kullanılıyor)`, 'success');
            } catch (err) {
                console.error('Kopyalama hatası:', err);
                safeExecute('showNotification', showNotification, '❌ Kopyalama başarısız oldu', 'error');
            }
        });
    }

    // Import Button
    if (dom.importButton) {
        dom.importButton.addEventListener('click', async () => {
            try {
                const text = await navigator.clipboard.readText();
                if (text && safeExecute('applyDrawingText', applyDrawingText, text)) {
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


    // Load Button
    if (dom.loadButton) {
        dom.loadButton.addEventListener('click', () => {
            if (dom.fileInput) {
                fileInput.click();
            }
        });
    }

    // File Input
    if (dom.fileInput) {
        dom.fileInput.addEventListener('change', (event) => {
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

    // Clear Button
    if (dom.clearButton) {
        dom.clearButton.addEventListener('click', async () => {
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
    if (dom.showGuideButton) {
        dom.showGuideButton.addEventListener('click', () => {
            if (dom.guideModal) {
                dom.guideModal.classList.add('show');
            }
        });
    }

    if (dom.closeGuideButton) {
        dom.closeGuideButton.addEventListener('click', () => {
            if (dom.guideModal) {
                dom.guideModal.classList.remove('show');
            }
        });
    }

    // Logout Button
    if (dom.logoutButton) {
        dom.logoutButton.addEventListener('click', (e) => {
            if (!confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                e.preventDefault();
            }
        });
    }

    // Tema değiştirme butonu
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleDarkMode);
    }

    // Mesaj butonları
    const messageBtn = document.getElementById('messageButton');
    if (messageBtn) {
        messageBtn.addEventListener('click', function() {
            const targetId = this.dataset.targetId;
            const targetUsername = this.dataset.targetUsername;
            openMessagesModal();
            setTimeout(() => {
                if (typeof selectConversation === 'function') {
                    selectConversation(targetId, targetUsername);
                }
            }, 500);
        });
    }
    attachButtonEvents(dom);
}

// Buton event'lerini bağla
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

// Hata yakalama
window.addEventListener('error', function(e) {
    console.error('🚨 Global hata:', e.error);
    safeExecute('showNotification', showNotification, 'Bir hata oluştu: ' + e.message, 'error');
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('🚨 İşlenmemiş promise hatası:', e.reason);
    safeExecute('showNotification', showNotification, 'Beklenmeyen bir hata oluştu', 'error');
});

console.log('✅ Main.js başarıyla yüklendi');
