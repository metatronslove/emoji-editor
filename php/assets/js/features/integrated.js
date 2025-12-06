// assets/js/features/integrated.js
class IntegratedEditorSystem {
    constructor() {
        this.currentEditor = 'emoji';
        this.sharedSettings = {
            maxChars: 200,
            separator: 'none',
            lineBreak: 'none',
            autoSave: true,
            autoCopy: true,
            darkMode: false,
            defaultWidth: 10,
            lastUsedEditor: 'emoji'
        };
        
        this.isInitialized = false;
    }
    
    async init() {
        if (this.isInitialized) return;
        
        console.log('🚀 Entegre editör sistemi başlatılıyor...');
        
        // Ayarları yükle
        await this.loadSettings();
        
        // Event'leri bağla
        this.bindEvents();
        
        // UI'ı güncelle
        this.applySettingsToUI();
        
        if (window.floodSystem) {
            window.floodSystem.settings.maxChars = this.sharedSettings.maxChars;
            window.floodSystem.settings.separator = this.sharedSettings.separator;
        }
        
        // Sistemleri senkronize et
        this.syncWithSubSystems();
        
        this.isInitialized = true;
        console.log('✅ Entegre editör sistemi hazır');
    }
    
    async loadSettings() {
        try {
            const saved = localStorage.getItem('integratedEditorSettings');
            if (saved) {
                const parsed = JSON.parse(saved);
                this.sharedSettings = { ...this.sharedSettings, ...parsed };
            }
            
            // Mevcut editörü yükle
            this.currentEditor = this.sharedSettings.lastUsedEditor || 'emoji';
            
        } catch (error) {
            console.error('Ayarlar yüklenemedi:', error);
            this.resetToDefaults();
        }
    }
    
    saveSettings() {
        try {
            // Güncel ayarları topla
            this.collectSettingsFromUI();
            
            // Kaydet
            localStorage.setItem('integratedEditorSettings', JSON.stringify(this.sharedSettings));
            
            // Diğer sistemlere bildir
            this.syncWithSubSystems();
            
            // Başarı mesajı
            if (typeof showNotification === 'function') {
                showNotification('✅ Tüm ayarlar kaydedildi!', 'success', 2000);
            }
            
            return true;
            
        } catch (error) {
            console.error('Ayarlar kaydedilemedi:', error);
            if (typeof showNotification === 'function') {
                showNotification('❌ Ayarlar kaydedilemedi', 'error');
            }
            return false;
        }
    }
    
    collectSettingsFromUI() {
        // Input değerlerini topla
        const maxCharsInput = document.getElementById('shared-max-chars');
        const separatorSelect = document.getElementById('shared-separator');
        const lineBreakSelect = document.getElementById('shared-line-break');
        const autoSaveCheck = document.getElementById('shared-auto-save');
        const autoCopyCheck = document.getElementById('shared-auto-copy');
        const darkModeCheck = document.getElementById('shared-dark-mode');
        const defaultWidthInput = document.getElementById('shared-default-width');
        
        if (maxCharsInput) this.sharedSettings.maxChars = parseInt(maxCharsInput.value) || 200;
        if (separatorSelect) this.sharedSettings.separator = separatorSelect.value;
        if (lineBreakSelect) this.sharedSettings.lineBreak = lineBreakSelect.value;
        if (autoSaveCheck) this.sharedSettings.autoSave = autoSaveCheck.checked;
        if (autoCopyCheck) this.sharedSettings.autoCopy = autoCopyCheck.checked;
        if (darkModeCheck) this.sharedSettings.darkMode = darkModeCheck.checked;
        if (defaultWidthInput) this.sharedSettings.defaultWidth = parseInt(defaultWidthInput.value) || 10;
        
        // Son kullanılan editörü kaydet
        this.sharedSettings.lastUsedEditor = this.currentEditor;
    }
    
    applySettingsToUI() {
        // UI elementlerini ayarla
        const maxCharsInput = document.getElementById('shared-max-chars');
        const separatorSelect = document.getElementById('shared-separator');
        const lineBreakSelect = document.getElementById('shared-line-break');
        const autoSaveCheck = document.getElementById('shared-auto-save');
        const autoCopyCheck = document.getElementById('shared-auto-copy');
        const darkModeCheck = document.getElementById('shared-dark-mode');
        const defaultWidthInput = document.getElementById('shared-default-width');
        
        if (maxCharsInput) maxCharsInput.value = this.sharedSettings.maxChars;
        if (separatorSelect) separatorSelect.value = this.sharedSettings.separator;
        if (lineBreakSelect) lineBreakSelect.value = this.sharedSettings.lineBreak;
        if (autoSaveCheck) autoSaveCheck.checked = this.sharedSettings.autoSave;
        if (autoCopyCheck) autoCopyCheck.checked = this.sharedSettings.autoCopy;
        if (darkModeCheck) darkModeCheck.checked = this.sharedSettings.darkMode;
        if (defaultWidthInput) defaultWidthInput.value = this.sharedSettings.defaultWidth;
        
        // Koyu tema uygula
        this.applyDarkMode();
        
        // Aktif editörü ayarla
        this.switchEditor(this.currentEditor);
    }
    
syncWithSubSystems() {
    console.log('🔄 Alt sistemler senkronize ediliyor...');
    
    // 1. Emoji sistemine senkronize et
    if (typeof MAX_CHARACTERS !== 'undefined') {
        MAX_CHARACTERS = this.sharedSettings.maxChars;
    }
    
    // 2. Flood sistemine senkronize et
    if (typeof floodSystem !== 'undefined' && floodSystem) {
        floodSystem.settings.maxChars = this.sharedSettings.maxChars;
        floodSystem.settings.separator = this.sharedSettings.separator;
        floodSystem.settings.autoSave = this.sharedSettings.autoSave;
        
        // Flood sistemini güncelle
        if (typeof floodSystem.updatePreview === 'function') {
            floodSystem.updatePreview();
        }
    }
    
    // 3. Karakter sayılarını güncelle
    this.updateCharacterCounts();
    
    // 4. Filtre ayırıcıyı güncelle (HER İKİ SİSTEM İÇİN)
    this.updateSeparator();
    
    // 5. Matris genişliğini güncelle (sadece emoji editörü için)
    if (typeof currentMatrixWidth !== 'undefined') {
        const widthInput = document.getElementById('matrixWidth');
        if (widthInput) {
            currentMatrixWidth = parseInt(widthInput.value) || this.sharedSettings.defaultWidth;
        }
    }
}
    
    updateCharacterCounts() {
        // Emoji editöründeki karakter sayısını güncelle
        if (typeof updateCharacterCount === 'function') {
            updateCharacterCount();
        }
        
        // Flood editöründeki karakter sayısını güncelle
        if (typeof floodSystem !== 'undefined' && typeof floodSystem.updatePreview === 'function') {
            floodSystem.updatePreview();
        }
        
        // Limit göstergelerini güncelle
        document.querySelectorAll('.max-chars-indicator').forEach(el => {
            el.textContent = this.sharedSettings.maxChars;
        });
    }
    
    updateSeparator() {
        const separator = this.sharedSettings.separator;
        
        // Emoji editöründeki ayırıcıyı güncelle
        const emojiSeparatorSelect = document.getElementById('separator-select');
        if (emojiSeparatorSelect) {
            emojiSeparatorSelect.value = separator;
        }
        
        // Flood editöründeki ayırıcıyı güncelle
        const floodSeparatorSelect = document.getElementById('flood-separator-select');
        if (floodSeparatorSelect) {
            floodSeparatorSelect.value = separator;
        }
    }
    
    applyDarkMode() {
        const modal = document.getElementById('integrated-editor-modal');
        if (!modal) return;
        
        if (this.sharedSettings.darkMode) {
            modal.classList.add('dark-mode');
            document.querySelectorAll('.editor-section').forEach(section => {
                section.style.backgroundColor = '#1a1a1a';
                section.style.color = '#ffffff';
            });
        } else {
            modal.classList.remove('dark-mode');
            document.querySelectorAll('.editor-section').forEach(section => {
                section.style.backgroundColor = '';
                section.style.color = '';
            });
        }
    }
    
    bindEvents() {
        // Tab değiştirme
        document.querySelectorAll('.editor-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const editorName = e.target.dataset.editor;
                this.switchEditor(editorName);
            });
        });
        
        // Ayarlar değişikliği dinle
        const settingsInputs = [
            'shared-max-chars',
            'shared-separator',
            'shared-line-break',
            'shared-auto-save',
            'shared-auto-copy',
            'shared-dark-mode',
            'shared-default-width'
        ];
        
        settingsInputs.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => {
                    // Otomatik kaydetme
                    if (this.sharedSettings.autoSave) {
                        setTimeout(() => this.saveSettings(), 500);
                    }
                    
                    // Hemen senkronize et
                    this.collectSettingsFromUI();
                    this.syncWithSubSystems();
                });
            }
        });
        
        // ESC tuşu ile kapatma
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isModalOpen()) {
                this.closeModal();
            }
        });
    }

switchEditor(type) {
    try {
        this.currentEditor = type;
        
        console.log(`🔀 Editor değiştiriliyor: ${type}`);
        
        // Tüm tab'ları ve butonları sıfırla
        document.querySelectorAll('.editor-tab-content, .tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        
        document.querySelectorAll('.tab-btn, .editor-tab').forEach(btn => {
            btn.classList.remove('active');
            btn.style.background = 'var(--fixed-bg)';
            btn.style.color = 'var(--main-text)';
        });
        
        // Seçilen editorü aktif yap
        if (type === 'flood') {
            // Flood tab'ını göster
            const floodTab = document.getElementById('flood-tab');
            const floodTabBtn = document.querySelector('[data-tab="flood"]');
            
            if (floodTab) floodTab.style.display = 'block';
            if (floodTabBtn) {
                floodTabBtn.classList.add('active');
                floodTabBtn.style.background = 'var(--accent-color)';
                floodTabBtn.style.color = 'white';
            }
            
            // Flood sistemini başlat
            if (window.floodSystem) {
                setTimeout(() => {
                    // Emoji paletini yükle
                    window.floodSystem.loadEmojiPalette().then(() => {
                        window.floodSystem.renderEmojiTabs();
                        window.floodSystem.renderEmojiGrid();
                    });
                }, 100);
            }
        } else {
            // Emoji tab'ını göster
            const emojiTab = document.getElementById('emoji-tab');
            const emojiTabBtn = document.querySelector('[data-tab="emoji"]');
            
            if (emojiTab) emojiTab.style.display = 'block';
            if (emojiTabBtn) {
                emojiTabBtn.classList.add('active');
                emojiTabBtn.style.background = 'var(--accent-color)';
                emojiTabBtn.style.color = 'white';
            }
            
            // Emoji sistemini başlat
            if (typeof createMatrix === 'function') {
                setTimeout(() => {
                    createMatrix();
                    if (typeof createCategoryTabs === 'function') createCategoryTabs();
                    if (typeof createPalette === 'function') createPalette();
                }, 100);
            }
        }
        
        // Ayarları güncelle
        this.sharedSettings.lastUsedEditor = type;
        this.saveSettings();
        
    } catch (error) {
        console.error('Editor değiştirme hatası:', error);
    }
}

hideAllTabs() {
    // Tüm tab içeriklerini gizle
    const tabs = ['flood-tab', 'emoji-tab'];
    tabs.forEach(tabId => {
        const tab = document.getElementById(tabId);
        if (tab) tab.style.display = 'none';
    });
    
    // Tüm tab butonlarını pasif yap
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'var(--fixed-bg)';
        btn.style.color = 'var(--main-text)';
    });
}
    
    openModal() {
        try {
            const modal = document.getElementById('integrated-editor-modal');
            if (!modal) {
                console.warn('Entegre editör modalı bulunamadı');
                return false;
            }
            
            modal.style.display = 'flex';
            this.init();
            
            // Son kullanılan editöre geç
            if (this.currentEditor) {
                this.switchEditor(this.currentEditor);
            }
            
            return true;
            
        } catch (error) {
            console.error('Modal açma hatası:', error);
            return false;
        }
    }
    
    closeModal() {
        const modal = document.getElementById('integrated-editor-modal');
        if (!modal) return;
        
        // Ayarları kaydet
        this.saveSettings();
        
        // Modalı gizle
        modal.style.display = 'none';
    }
    
    isModalOpen() {
        const modal = document.getElementById('integrated-editor-modal');
        return modal && modal.style.display === 'flex';
    }
    
    resetToDefaults() {
        this.sharedSettings = {
            maxChars: 200,
            separator: 'none',
            lineBreak: 'none',
            autoSave: true,
            autoCopy: true,
            darkMode: false,
            defaultWidth: 10,
            lastUsedEditor: 'emoji'
        };
        
        this.applySettingsToUI();
        this.saveSettings();
        
        if (typeof showNotification === 'function') {
            showNotification('🔄 Tüm ayarlar varsayılana döndürüldü!', 'info');
        }
    }
    
    exportSettings() {
        const settingsStr = JSON.stringify(this.sharedSettings, null, 2);
        const blob = new Blob([settingsStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `emoji-flood-settings-${new Date().toISOString().slice(0, 10)}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        if (typeof showNotification === 'function') {
            showNotification('📥 Ayarlar JSON dosyası olarak indirildi!', 'success');
        }
    }

hideAllTabs() {
    // Tüm tab içeriklerini gizle
    document.querySelectorAll('.editor-tab-content, .tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Tüm tab butonlarını pasif yap
    document.querySelectorAll('.tab-btn, .editor-tab').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'var(--fixed-bg)';
        btn.style.color = 'var(--main-text)';
    });
}

showFloodEditor() {
    console.log('🌊 Flood editör gösteriliyor...');
    
    // Tab'ları yönet
    this.hideAllTabs();
    
    const floodTab = document.getElementById('flood-tab');
    const floodTabBtn = document.querySelector('[data-tab="flood"]');
    
    if (floodTab) {
        floodTab.style.display = 'block';
        console.log('✅ Flood tab gösterildi');
    }
    
    if (floodTabBtn) {
        floodTabBtn.classList.add('active');
        floodTabBtn.style.background = 'var(--accent-color)';
        floodTabBtn.style.color = 'white';
    }
    
    // Flood sistemi başlat
    if (window.floodSystem) {
        if (!window.floodSystem.initialized) {
            window.floodSystem.init();
        }
        
        // Emoji paletini yükle
        setTimeout(() => {
            if (window.floodSystem.loadEmojiPalette) {
                window.floodSystem.loadEmojiPalette().then(() => {
                    if (window.floodSystem.renderEmojiTabs) {
                        window.floodSystem.renderEmojiTabs();
                    }
                    if (window.floodSystem.renderEmojiGrid) {
                        window.floodSystem.renderEmojiGrid();
                    }
                });
            }
        }, 100);
    }
    
    // Ayarları güncelle
    this.currentEditor = 'flood';
    this.sharedSettings.lastUsedEditor = 'flood';
}

/**
 * Flood editör içeriğini güvenli bir şekilde oluştur
 */
ensureFloodEditorContent() {
    const floodContainer = document.getElementById('flood-editor-container');
    if (!floodContainer) {
        console.error('❌ flood-editor-container bulunamadı!');
        
        // Acil durumda oluştur
        const floodTab = document.getElementById('flood-tab');
        if (floodTab) {
            floodTab.innerHTML = `
                <div id="flood-editor-container" style="width: 100%; height: 100%; padding: 20px;">
                    <div style="text-align: center; padding: 40px; opacity: 0.7;">
                        <div style="font-size: 3em;">🌊</div>
                        <p>Flood editör yükleniyor...</p>
                    </div>
                </div>
            `;
            console.log('⚠️ flood-editor-container acil oluşturuldu');
        }
        return;
    }
    
    // Eğer içerik boşsa veya yükleniyor mesajı varsa, içeriği doldur
    if (!floodContainer.querySelector('.flood-editor-initialized') || 
        floodContainer.innerHTML.includes('yükleniyor')) {
        this.renderFloodTabContent();
    }
}

showEmojiEditor() {
    console.log('🎨 Emoji editör gösteriliyor...');
    
    const emojiTab = document.getElementById('emoji-tab');
    const emojiTabBtn = document.querySelector('[data-tab="emoji"]') || 
                       document.querySelector('button[onclick*="emoji"]');
    
    if (emojiTab) {
        emojiTab.style.display = 'block';
        console.log('✅ Emoji tab gösterildi');
    }
    
    if (emojiTabBtn) {
        emojiTabBtn.classList.add('active');
        emojiTabBtn.style.background = 'var(--accent-color)';
        emojiTabBtn.style.color = 'white';
        console.log('✅ Emoji tab butonu aktif yapıldı');
    }
    
    // Emoji editörünü başlat
    setTimeout(() => {
        if (typeof createMatrix === 'function') createMatrix();
        if (typeof createCategoryTabs === 'function') createCategoryTabs();
        if (typeof createPalette === 'function') createPalette();
    }, 100);
}

showFallbackEditor(type) {
    console.log(`⚠️ Fallback editor gösteriliyor: ${type}`);
    
    // Basit bir fallback modal göster
    if (type === 'flood') {
        // Flood editör modalını aç
        const floodModal = document.getElementById('flood-editor-modal');
        if (floodModal) {
            floodModal.style.display = 'flex';
            if (window.floodSystem) {
                window.floodSystem.init();
            }
        }
    } else {
        // Emoji editör modalını aç
        const emojiModal = document.getElementById('emoji-editor-modal');
        if (emojiModal) {
            emojiModal.style.display = 'flex';
            if (typeof createMatrix === 'function') {
                createMatrix();
            }
        }
    }
}
    
    async importSettings() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            try {
                const text = await file.text();
                const imported = JSON.parse(text);
                
                // Geçerlilik kontrolü
                if (!imported.maxChars || !imported.separator) {
                    throw new Error('Geçersiz ayar dosyası');
                }
                
                // Ayarları yükle
                this.sharedSettings = { ...this.sharedSettings, ...imported };
                this.applySettingsToUI();
                this.saveSettings();
                
                if (typeof showNotification === 'function') {
                    showNotification('✅ Ayarlar başarıyla içe aktarıldı!', 'success');
                }
                
            } catch (error) {
                console.error('Ayarlar içe aktarılamadı:', error);
                if (typeof showNotification === 'function') {
                    showNotification('❌ Geçersiz ayar dosyası!', 'error');
                }
            }
        };
        
        input.click();
    }
}

// GLOBAL INTEGRATED EDITOR INSTANCE - SADECE BU KALSIN
if (typeof window.integratedEditor === 'undefined') {
    window.integratedEditor = new IntegratedEditorSystem();
}