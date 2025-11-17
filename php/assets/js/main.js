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

// Event listener'ları yönet
function initEventListeners() {
    const {
        firstRowLengthInput,
        updateMatrixButton,
        separatorSelect,
        copyButton,
        importButton,
        saveButton,
        loadButton,
        fileInput,
        clearButton,
        showGuideButton,
        closeGuideButton,
        logoutButton
    } = DOM_ELEMENTS;

    // First Row Length Input
    if (firstRowLengthInput) {
        firstRowLengthInput.addEventListener('input', () => {
            // Sadece matrisi güncelleme butonuna basıldığında matrix yeniden çizilir.
        });
    }

    // Update Matrix Button
    if (updateMatrixButton) {
        updateMatrixButton.addEventListener('click', async () => {
            const confirmed = await showConfirm(
                "Matrisi Güncelle",
                "İlk satır çizilebilir piksel sayısını değiştirmek mevcut çizimi temizleyecektir. Devam etmek istiyor musunuz?"
            );

            if (confirmed) {
                createMatrix();
                showNotification('Matris başarıyla güncellendi!', 'success');
            }
        });
    }

    // Separator Select
    if (separatorSelect) {
        separatorSelect.addEventListener('change', async () => {
            const newWidth = (separatorSelect.value === 'SP_BS') ? SP_BS_MATRIX_WIDTH : DEFAULT_MATRIX_WIDTH;
            const currentDisplayedWidth = matrixTable && matrixTable.rows.length > 0 ? matrixTable.rows[0].cells.length : DEFAULT_MATRIX_WIDTH;

            if (newWidth !== currentDisplayedWidth) {
                const confirmed = await showConfirm(
                    "Ayırıcı Değişikliği",
                    "Ayırıcı türünü değiştirmek matris boyutunu değiştirecek ve çizimi temizleyecektir. Devam etmek istiyor musunuz?"
                );

                if (confirmed) {
                    createMatrix();
                    showNotification(`⚠️ Matris boyutu ${currentDisplayedWidth}x${MATRIX_HEIGHT}'dan ${newWidth}x${MATRIX_HEIGHT}'a değiştirildi. Çizim temizlendi.`, 'warning');
                } else {
                    const prevValue = Array.from(separatorSelect.options).find(opt =>
                    (opt.value === 'SP_BS' && currentDisplayedWidth === SP_BS_MATRIX_WIDTH) ||
                    (opt.value !== 'SP_BS' && currentDisplayedWidth === DEFAULT_MATRIX_WIDTH)
                    )?.value || 'none';
                    separatorSelect.value = prevValue;
                    return;
                }
            } else {
                updateCharacterCount();
                const separatorName = SEPARATOR_MAP[separatorSelect.value].name;
                showNotification(`Ayırıcı ${separatorName} olarak ayarlandı.`, 'info');
            }
        });
    }

    // Copy Button
    if (copyButton) {
        copyButton.addEventListener('click', async () => {
            const drawingText = getDrawingText(false);
            const allCells = matrixTable ? matrixTable.querySelectorAll('td') : [];
            const stats = calculateAndClip(allCells);
            const totalChars = stats.totalOutputCharCount;

            try {
                const separatorName = SEPARATOR_MAP[separatorSelect.value].name;
                await navigator.clipboard.writeText(drawingText);
                showNotification(`✅ Çizim panoya kopyalandı! (${totalChars}/${MAX_CHARACTERS} Karakter - ${separatorName} kullanılıyor)`, 'success');
            } catch (err) {
                console.error('Kopyalama başarısız:', err);
                showNotification('❌ Kopyalama başarısız oldu. Lütfen tarayıcı izinlerini kontrol edin.', 'error');
            }
        });
    }

    // Import Button
    if (importButton) {
        importButton.addEventListener('click', async () => {
            try {
                const text = await navigator.clipboard.readText();
                if (text && applyDrawingText(text)) {
                    showNotification('✅ Çizim panodan başarıyla içe aktarıldı!', 'success');
                } else if (!text) {
                    showNotification('❌ Panoda içe aktarılacak metin bulunamadı.', 'error');
                }
            } catch (err) {
                console.error('İçe aktarma başarısız:', err);
                showNotification('❌ İçe aktarma başarısız oldu. Panonuzda geçerli bir çizim metni olduğundan emin olun.', 'error');
            }
        });
    }

    // Load Button
    if (loadButton) {
        loadButton.addEventListener('click', () => {
            if (fileInput) {
                fileInput.click();
            }
        });
    }

    // File Input
    if (fileInput) {
        fileInput.addEventListener('change', (event) => {
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
    if (clearButton) {
        clearButton.addEventListener('click', async () => {
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
    if (showGuideButton) {
        showGuideButton.addEventListener('click', () => {
            if (guideModal) {
                guideModal.classList.add('show');
            }
        });
    }

    if (closeGuideButton) {
        closeGuideButton.addEventListener('click', () => {
            if (guideModal) {
                guideModal.classList.remove('show');
            }
        });
    }

    // Logout Button
    if (logoutButton) {
        logoutButton.addEventListener('click', (e) => {
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
}

// Ana uygulama başlatıcı
document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 Emoji Sanat Uygulaması Başlatılıyor...');

    try {
        // Tema sistemini başlat
        initThemeSystem();

        // Modal ve UI sistemlerini başlat
        initModalSystem();
        initAuthForms();
        initGoogleAuthLinks();
        handleUrlParameters();

        // Çevrimiçi durumu başlat
        initOnlineStatus();

        // Ayırıcı karakter maliyetlerini hesapla
        if (document.getElementById('separator-select')) {
            calculateSeparatorCharCosts();
        }

        // Emojileri yükle
        await loadEmojis();

        // Editörü başlat
        if (Object.keys(emojiCategories).length > 0) {
            if (document.getElementById('matrix')) {
                updateSelectedEmojiDisplay();
                createMatrix();
                createCategoryTabs();
                createPalette();
                showNotification('⚡ Kalp Emoji Piksel Sanatı Editörü Hazır!', 'info', 3000);
            }
        }

        // Global app instance'ı
        const app = new App();

        // Mesajlaşma sistemini başlat
        if (window.currentUser && window.currentUser.id) {
            app.init().then(() => {
                console.log('🎉 Uygulama hazır!');

                // Sistem durumunu logla
                console.log('📊 Sistem durumu:', app.getSystemStatus());
            });
        }

        // Event listener'ları başlat
        initEventListeners();

        // Context menu'yu ekle
        addContextMenuOption();

        // Ek özellikleri yükle
        setTimeout(() => {
            if (typeof fetchFollowingFeed === 'function' && document.getElementById('following-feed-list')) {
                fetchFollowingFeed();
            }
            if (typeof fetchDrawings === 'function' && document.getElementById('drawing-list')) {
                fetchDrawings(1);
            }
            if (typeof fetchProfileComments === 'function' && document.getElementById('board-comments-list')) {
                fetchProfileComments();
            }
        }, 2000);

        // Google auth linklerini güncelle
        document.querySelectorAll('.btn-google').forEach(link => {
            const currentModal = link.closest('.modal')?.id;
            if (currentModal) {
                link.href = SITE_BASE_URL + `auth/login.php?source=${currentModal}`;
            }
        });

        // Hata yakalama
        window.addEventListener('error', function(e) {
            console.error('🚨 Global hata:', e.error);
            showNotification('Bir hata oluştu: ' + e.message, 'error');
        });

        // Promise hataları
        window.addEventListener('unhandledrejection', function(e) {
            console.error('🚨 İşlenmemiş promise hatası:', e.reason);
            showNotification('Beklenmeyen bir hata oluştu', 'error');
        });

    } catch (error) {
        console.error('Uygulama başlatma hatası:', error);
        showNotification('Uygulama başlatılırken hata oluştu.', 'error');
    }
});
