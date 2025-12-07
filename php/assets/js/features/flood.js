// assets/js/features/flood.js
class FloodSystem {
    constructor() {
        this.currentSetId = null;
        this.floodMessages = [];
        this.floodSets = [];
        this.settings = {
            maxChars: 200,
            separator: 'none',
            autoSave: true,
            autoCopy: false,
            showCategories: true,
            enableQuickEmoji: true,
            darkMode: false
        };
        
        // YENİ ÖZELLİK: Kategori sistemi
        this.categories = {};
        this.currentCategory = 'genel';
        this.newCategoryInput = null;
        
        // YENİ ÖZELLİK: Favori emojiler
        this.favoriteEmojis = new Set();
        
        // YENİ ÖZELLİK: Şablonlar
        this.templates = [];
        
        this.initialized = false;
        
        // Ayarları entegre sistemden al
        if (window.integratedEditor) {
            this.settings.maxChars = window.integratedEditor.sharedSettings.maxChars;
            this.settings.separator = window.integratedEditor.sharedSettings.separator;
        }
        
        this.loadIntegratedSettings();
        this.loadUserPreferences();
    }
	
    loadIntegratedSettings() {
        try {
            const saved = localStorage.getItem('integratedEditorSettings');
            if (saved) {
                const settings = JSON.parse(saved);
                
                this.settings.maxChars = settings.maxChars || 200;
                this.settings.separator = settings.separator || 'none';
                this.settings.autoSave = settings.autoSave !== false;
                this.settings.autoCopy = settings.autoCopy !== false;
                
                console.log('✅ Flood sistemi ayarları entegre sistemden yüklendi');
            }
        } catch (error) {
            console.error('Entegre ayarlar yüklenemedi:', error);
        }
    }
	
	/**
 * Kategorileri yükle (eksik fonksiyon)
 */
async loadCategories() {
    try {
        // Sunucudan kategorileri getir
        const response = await fetch(`${SITE_BASE_URL}core/get_flood_categories.php`);
        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                this.categories = result.categories || {};
                console.log(`✅ ${Object.keys(this.categories).length} kategori yüklendi`);
            }
        } else {
            // Fallback kategoriler
            this.categories = {
                'genel': { name: 'Genel', emoji: '📝', slug: 'genel' },
                'komik': { name: 'Komik', emoji: '😂', slug: 'komik' },
                'spor': { name: 'Spor', emoji: '⚽', slug: 'spor' },
                'müzik': { name: 'Müzik', emoji: '🎵', slug: 'müzik' },
                'oyun': { name: 'Oyun', emoji: '🎮', slug: 'oyun' },
                'teknoloji': { name: 'Teknoloji', emoji: '💻', slug: 'teknoloji' }
            };
            console.log('⚠️ Fallback kategoriler kullanılıyor');
        }
    } catch (error) {
        console.error('Kategoriler yüklenemedi:', error);
        // Fallback kategoriler
        this.categories = {
            'genel': { name: 'Genel', emoji: '📝', slug: 'genel' }
        };
    }
}

/**
 * Kategori seçiciyi güncelle (eksik fonksiyon)
 */
updateCategorySelector() {
    const selector = document.getElementById('flood-category-select');
    if (!selector) return;
    
    // Mevcut seçeneği sakla
    const currentValue = selector.value;
    
    // Temizle
    selector.innerHTML = '<option value="all">Tümü</option>';
    
    // Kategorileri ekle
    Object.values(this.categories).forEach(category => {
        const option = document.createElement('option');
        option.value = category.slug;
        option.textContent = `${category.emoji} ${category.name}`;
        selector.appendChild(option);
    });
    
    // Önceki değeri geri yükle
    if (currentValue) {
        selector.value = currentValue;
    }
}
    
    async init() {
        if (this.initialized) {
            console.log('⚠️ Flood sistemi zaten başlatılmış');
            return;
        }
        
        console.log('🌊 Flood sistemi başlatılıyor...');
        
        try {
            // 1. GLOBAL DEĞİŞKENLERİ KONTROL ET
            console.log('🔍 Global değişkenler kontrol ediliyor:');
            console.log('- EMOJI_JSON_URL:', window.EMOJI_JSON_URL ? '✅ Var' : '❌ Yok');
            console.log('- calculateChatChars:', typeof window.calculateChatChars === 'function' ? '✅ Var' : '❌ Yok');
            console.log('- SITE_BASE_URL:', window.SITE_BASE_URL ? '✅ Var' : '❌ Yok');
            
			// Global fonksiyonları kaydet
			this.registerGlobalFunctions();
			
            // 2. Ayarları yükle
            await this.loadSettings();
            
            // 3. EMOJI PALETINI YÜKLE (SAFEEXECUTE İLE)
            console.log('📦 Emoji paleti yükleniyor...');
            await safeExecute('loadEmojiPalette', async () => {
                await this.loadEmojiPalette();
            });
            
            // 4. VERİLERİ YÜKLE
            await Promise.all([
                this.loadFloodSets(),
                this.loadCategories(),        // YENİ: Kategorileri yükle
                this.loadTemplates(),         // YENİ: Şablonları yükle
                this.loadFavorites()          // YENİ: Favorileri yükle
            ]);
            
            // 5. UI YAPILARINI HAZIRLA
            this.prepareUIComponents();
            
            // 6. EVENT'LERİ BAĞLA
            this.bindEvents();
            
            // 7. ÖNİZLEMEYİ GÜNCELLE
            this.updatePreview();
            
            // 8. EMOJI TAB'INI OLUŞTUR
            safeExecute('renderEmojiTabs', () => {
                this.renderEmojiTabs();
            });
            
            safeExecute('renderEmojiGrid', () => {
                this.renderEmojiGrid();
            });
            
            // YENİ: Kategori tab'ını render et
            this.renderCategoryTab();
			
			// Tab switching'i başlat
			this.setupTabSwitching();
            
            this.initialized = true;
            console.log('✅ Flood sistemi hazır');
            
        } catch (error) {
            console.error('❌ Flood sistemi başlatma hatası:', error);
            this.useFallbackMode();
        }
    }
    
    // YENİ: UI bileşenlerini hazırla
    prepareUIComponents() {
        // Kategori seçiciyi güncelle
        this.updateCategorySelector();
        
        // Şablon dropdown'unu doldur
        this.updateTemplateSelector();
        
        // Hızlı aksiyon butonlarını ekle
        this.addQuickActionButtons();
        
        // Tema desteği
        if (this.settings.darkMode) {
            this.enableDarkMode();
        }
    }
    
    // YENİ: Kullanıcı tercihlerini yükle
    loadUserPreferences() {
        try {
            const preferences = localStorage.getItem('floodUserPreferences');
            if (preferences) {
                const parsed = JSON.parse(preferences);
                
                // Favori emojiler
                if (parsed.favoriteEmojis) {
                    this.favoriteEmojis = new Set(parsed.favoriteEmojis);
                }
                
                // Son kullanılan kategoriler
                if (parsed.recentCategories) {
                    this.recentCategories = parsed.recentCategories;
                }
                
                // UI tercihleri
                if (parsed.uiPreferences) {
                    Object.assign(this.settings, parsed.uiPreferences);
                }
            }
        } catch (error) {
            console.error('Kullanıcı tercihleri yüklenemedi:', error);
        }
    }
    
    // YENİ: Şablonları yükle
async loadTemplates() {
    try {
        const response = await fetch(`${SITE_BASE_URL}core/get_flood_templates.php`);
        
        // Önce response tipini kontrol et
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.warn('⚠️ Şablonlar JSON formatında değil, atlanıyor');
            this.templates = [];
            return;
        }
        
        if (response.ok) {
            const text = await response.text();
            
            // Boş veya geçersiz JSON kontrolü
            if (!text || text.trim() === '') {
                this.templates = [];
                return;
            }
            
            try {
                const result = JSON.parse(text);
                if (result.success) {
                    this.templates = result.templates || [];
                    console.log(`✅ ${this.templates.length} şablon yüklendi`);
                }
            } catch (parseError) {
                console.error('❌ JSON parse hatası:', parseError);
                this.templates = [];
            }
        }
    } catch (error) {
        console.error('❌ Şablonlar yüklenemedi:', error);
        this.templates = [];
    }
}

    
    // YENİ: Favorileri yükle
async loadFavorites() {
    try {
        const response = await fetch(`${SITE_BASE_URL}core/get_favorites.php?type=flood`);
        
        // Önce response tipini kontrol et
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.warn('⚠️ Favoriler JSON formatında değil, atlanıyor');
            this.favoriteEmojis = new Set();
            this.favoriteSets = [];
            return;
        }
        
        if (response.ok) {
            const text = await response.text();
            
            // Boş veya geçersiz JSON kontrolü
            if (!text || text.trim() === '') {
                this.favoriteEmojis = new Set();
                this.favoriteSets = [];
                return;
            }
            
            try {
                const result = JSON.parse(text);
                if (result.success) {
                    this.favoriteEmojis = new Set(result.emojis || []);
                    this.favoriteSets = result.sets || [];
                    console.log(`✅ ${this.favoriteEmojis.size} favori emoji yüklendi`);
                }
            } catch (parseError) {
                console.error('❌ JSON parse hatası:', parseError);
                this.favoriteEmojis = new Set();
                this.favoriteSets = [];
            }
        }
    } catch (error) {
        console.error('❌ Favoriler yüklenemedi:', error);
        this.favoriteEmojis = new Set();
        this.favoriteSets = [];
    }
}
    
    // YENİ: Kategori tab'ını render et
    renderCategoryTab() {
        const container = document.getElementById('flood-category-tab');
        if (!container) return;
        
        container.innerHTML = `
            <div style="padding: 15px;">
                <h4 style="margin-bottom: 15px; color: var(--accent-color);">📂 Kategoriler</h4>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Aktif Kategori:</label>
                    <select id="flood-category-select" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); background: var(--fixed-bg); color: var(--main-text);">
                        <option value="all">Tümü</option>
                    </select>
                    
                    <div id="flood-category-container" style="margin-top: 10px;"></div>
                </div>
                
                <div style="background: var(--fixed-bg); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); margin-top: 20px;">
                    <h5 style="margin-top: 0; margin-bottom: 10px;">⚡ Hızlı İşlemler</h5>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <button onclick="floodSystem.exportCurrentSet()" class="btn-sm btn-secondary">
                            📤 Aktif Set'i Dışa Aktar
                        </button>
                        <button onclick="floodSystem.importFloodSet()" class="btn-sm btn-secondary">
                            📥 Set İçe Aktar
                        </button>
                        <button onclick="floodSystem.mergeSets()" class="btn-sm btn-secondary">
                            🔀 Set'leri Birleştir
                        </button>
                        <button onclick="floodSystem.duplicateSet()" class="btn-sm btn-secondary">
                            📋 Set'i Çoğalt
                        </button>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <h5 style="margin-bottom: 10px;">📊 İstatistikler</h5>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 0.9em;">
                        <div style="background: var(--fixed-bg); padding: 10px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 1.5em; font-weight: bold; color: var(--accent-color);">${this.floodSets.length}</div>
                            <div>Toplam Set</div>
                        </div>
                        <div style="background: var(--fixed-bg); padding: 10px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 1.5em; font-weight: bold; color: var(--accent-color);">${this.getTotalMessages()}</div>
                            <div>Toplam Mesaj</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Kategori seçiciyi güncelle
        this.updateCategorySelector();
    }
    
    // YENİ: Toplam mesaj sayısını hesapla
    getTotalMessages() {
        return this.floodSets.reduce((total, set) => total + (set.message_count || 0), 0);
    }
    
    // YENİ: Kategoriye göre filtrele
    filterSetsByCategory(categorySlug) {
        if (!categorySlug || categorySlug === 'all') {
            return this.floodSets;
        }
        return this.floodSets.filter(set => set.category === categorySlug);
    }
    
    // YENİ: Şablon seçiciyi güncelle
    updateTemplateSelector() {
        const container = document.getElementById('flood-template-container');
        if (!container || this.templates.length === 0) return;
        
        container.innerHTML = `
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">📋 Şablonlar:</label>
                <select id="flood-template-select" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); background: var(--fixed-bg); color: var(--main-text);">
                    <option value="">Şablon seçin...</option>
                    ${this.templates.map(template => `
                        <option value="${template.id}">${template.name} (${template.usage_count || 0} kullanım)</option>
                    `).join('')}
                </select>
                <button onclick="floodSystem.applySelectedTemplate()" class="btn-sm btn-primary" style="margin-top: 5px; width: 100%;">
                    Şablonu Uygula
                </button>
            </div>
        `;
    }
    
    // YENİ: Şablon uygula
    applySelectedTemplate() {
        const select = document.getElementById('flood-template-select');
        const templateId = select?.value;
        
        if (!templateId) return;
        
        const template = this.templates.find(t => t.id == templateId);
        if (template) {
            const messageInput = document.getElementById('flood-message-input');
            if (messageInput) {
                messageInput.value = template.content;
                this.updatePreview();
                this.showNotification(`"${template.name}" şablonu uygulandı`, 'success');
                
                // Kullanım sayısını güncelle
                this.incrementTemplateUsage(templateId);
            }
        }
    }
    
    // YENİ: Şablon kullanımını artır
    async incrementTemplateUsage(templateId) {
        try {
            await fetch(`${SITE_BASE_URL}core/increment_template_usage.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ template_id: templateId })
            });
        } catch (error) {
            console.error('Şablon kullanımı güncellenemedi:', error);
        }
    }
    
    // YENİ: Hızlı aksiyon butonları ekle
    addQuickActionButtons() {
        const container = document.getElementById('flood-quick-actions');
        if (!container) return;
        
        container.innerHTML = `
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px;">
                <button onclick="floodSystem.insertTimestamp()" class="btn-sm btn-secondary" title="Zaman damgası ekle">
                    🕒 Zaman
                </button>
                <button onclick="floodSystem.insertUsername()" class="btn-sm btn-secondary" title="Kullanıcı adı ekle">
                    👤 Kullanıcı
                </button>
                <button onclick="floodSystem.insertRandomText()" class="btn-sm btn-secondary" title="Rastgele metin ekle">
                    🎲 Rastgele
                </button>
                <button onclick="floodSystem.formatAsCode()" class="btn-sm btn-secondary" title="Kod formatına çevir">
                    </> Kod
                </button>
                <button onclick="floodSystem.toggleUpperCase()" class="btn-sm btn-secondary" title="Büyük/küçük harf değiştir">
                    🔠 Büyük/Küçük
                </button>
            </div>
        `;
    }
    
    // YENİ: Zaman damgası ekle
    insertTimestamp() {
        const now = new Date();
        const timestamp = `[${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}]`;
        this.insertQuickEmoji(timestamp);
    }
    
    // YENİ: Kullanıcı adı ekle
    insertUsername() {
        const username = prompt('Kullanıcı adı girin:', 'Kullanıcı');
        if (username) {
            this.insertQuickEmoji(`${username}: `);
        }
    }
    
    // YENİ: Rastgele metin ekle
    insertRandomText() {
        const texts = [
            "Harika!",
            "Çok güzel!",
            "Teşekkürler!",
            "Mükemmel!",
            "Süper!",
            "Harika iş!",
            "Bravo!",
            "Wow!"
        ];
        const randomText = texts[Math.floor(Math.random() * texts.length)];
        this.insertQuickEmoji(randomText);
    }
    
    // YENİ: Kod formatına çevir
    formatAsCode() {
        const messageInput = document.getElementById('flood-message-input');
        if (!messageInput) return;
        
        const text = messageInput.value;
        if (text.startsWith('```') && text.endsWith('```')) {
            // Zaten kod formatında, kaldır
            messageInput.value = text.substring(3, text.length - 3);
        } else {
            // Kod formatına çevir
            messageInput.value = `\`\`\`\n${text}\n\`\`\``;
        }
        
        this.updatePreview();
    }
    
    // YENİ: Büyük/küçük harf değiştir
    toggleUpperCase() {
        const messageInput = document.getElementById('flood-message-input');
        if (!messageInput) return;
        
        const text = messageInput.value;
        if (text === text.toUpperCase()) {
            // Küçük harfe çevir
            messageInput.value = text.toLowerCase();
        } else {
            // Büyük harfe çevir
            messageInput.value = text.toUpperCase();
        }
        
        this.updatePreview();
    }
    
    // YENİ: Emojiyi favorilere ekle/çıkar
    toggleFavoriteEmoji(emojiData) {
        const emojiKey = `${emojiData.emoji}|${emojiData.name}`;
        
        if (this.favoriteEmojis.has(emojiKey)) {
            this.favoriteEmojis.delete(emojiKey);
            this.showNotification('Favorilerden çıkarıldı', 'info');
        } else {
            this.favoriteEmojis.add(emojiKey);
            this.showNotification('Favorilere eklendi', 'success');
        }
        
        this.saveFavorites();
        this.renderEmojiGrid(); // Grid'i yenile
    }
    
    // YENİ: Favorileri kaydet
    saveFavorites() {
        try {
            const favorites = {
                emojis: Array.from(this.favoriteEmojis),
                sets: this.favoriteSets,
                lastUpdated: new Date().toISOString()
            };
            
            localStorage.setItem('floodFavorites', JSON.stringify(favorites));
            
            // Sunucuya da kaydet (async)
            this.syncFavoritesToServer();
            
        } catch (error) {
            console.error('Favoriler kaydedilemedi:', error);
        }
    }
    
    // YENİ: Favorileri sunucuya senkronize et
    async syncFavoritesToServer() {
        try {
            await fetch(`${SITE_BASE_URL}core/sync_favorites.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    emojis: Array.from(this.favoriteEmojis),
                    type: 'flood'
                })
            });
        } catch (error) {
            console.error('Favoriler senkronize edilemedi:', error);
        }
    }
    
renderEmojiGrid() {
    try {
        const container = document.getElementById('flood-emoji-container');
        if (!container) {
            console.warn('❌ flood-emoji-container bulunamadı');
            return;
        }
        
        container.innerHTML = '';
        
        if (!this.emojiCategories || !this.emojiCategories[this.currentCategory]) {
            container.innerHTML = '<div style="padding: 20px; text-align: center; opacity: 0.7;">Emoji bulunamadı</div>';
            return;
        }
        
        const category = this.emojiCategories[this.currentCategory];
        const emojis = Object.values(category.emojis);
        
        if (emojis.length === 0) {
            container.innerHTML = '<div style="padding: 20px; text-align: center; opacity: 0.7;">Bu kategoride emoji yok</div>';
            return;
        }
        
        emojis.forEach(emojiData => {
            const emojiBtn = document.createElement('button');
            emojiBtn.className = 'emoji-btn';
            emojiBtn.style.cssText = `
                width: 40px;
                height: 40px;
                border: 1px solid var(--border-color);
                background: var(--fixed-bg);
                border-radius: 6px;
                cursor: pointer;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                font-size: 1.2em;
                transition: all 0.2s;
            `;
            
            emojiBtn.innerHTML = `
                <div>${emojiData.emoji}</div>
                <div style="font-size: 0.6em; color: ${emojiData.chars > 1 ? '#ffc107' : '#28a745'}">
                    ${emojiData.chars}
                </div>
            `;
            
            emojiBtn.title = `${emojiData.name} (${emojiData.chars} karakter)`;
            
            emojiBtn.addEventListener('click', () => {
                this.insertQuickEmoji(emojiData.emoji);
            });
            
            container.appendChild(emojiBtn);
        });
        
        console.log(`✅ ${emojis.length} emoji render edildi (${this.currentCategory})`);
        
    } catch (error) {
        console.error('❌ Emoji grid render hatası:', error);
    }
}
    
    // YENİ: Favori emojileri render et
    renderFavoriteEmojis(container) {
        const favoriteSection = document.createElement('div');
        favoriteSection.style.cssText = 'grid-column: 1 / -1; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color);';
        favoriteSection.innerHTML = `
            <div style="font-size: 0.9em; font-weight: bold; margin-bottom: 10px; color: var(--accent-color);">
                ⭐ Favori Emojiler (${this.favoriteEmojis.size})
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); gap: 5px;">
                ${Array.from(this.favoriteEmojis).map(emojiKey => {
                    const [emoji, name] = emojiKey.split('|');
                    return `
                        <button onclick="floodSystem.insertQuickEmoji('${emoji}')" 
                                class="emoji-btn"
                                style="background: rgba(255, 193, 7, 0.1);">
                            <div style="font-size: 1.5em;">${emoji}</div>
                        </button>
                    `;
                }).join('')}
            </div>
        `;
        
        container.appendChild(favoriteSection);
    }
    
    // YENİ: Emoji için context menü göster
    showEmojiContextMenu(e, emojiData) {
        const menu = document.createElement('div');
        menu.style.cssText = `
            position: fixed;
            top: ${e.clientY}px;
            left: ${e.clientX}px;
            background: var(--fixed-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            min-width: 150px;
        `;
        
        const emojiKey = `${emojiData.emoji}|${emojiData.name}`;
        const isFavorite = this.favoriteEmojis.has(emojiKey);
        
        menu.innerHTML = `
            <div style="padding: 10px; border-bottom: 1px solid var(--border-color); text-align: center; font-size: 1.5em;">
                ${emojiData.emoji}
            </div>
            <div style="padding: 5px 0;">
                <button onclick="floodSystem.insertQuickEmoji('${emojiData.emoji}'); this.parentNode.parentNode.remove()" 
                        style="width: 100%; text-align: left; padding: 8px 15px; background: none; border: none; cursor: pointer;">
                    📝 Mesaja Ekle
                </button>
                <button onclick="floodSystem.toggleFavoriteEmoji(${JSON.stringify(emojiData)}); this.parentNode.parentNode.remove()" 
                        style="width: 100%; text-align: left; padding: 8px 15px; background: none; border: none; cursor: pointer;">
                    ${isFavorite ? '★ Favorilerden Çıkar' : '☆ Favorilere Ekle'}
                </button>
                <button onclick="navigator.clipboard.writeText('${emojiData.emoji}'); floodSystem.showNotification('Emoji kopyalandı', 'success'); this.parentNode.parentNode.remove()" 
                        style="width: 100%; text-align: left; padding: 8px 15px; background: none; border: none; cursor: pointer;">
                    📋 Emojiyi Kopyala
                </button>
                <button onclick="floodSystem.insertEmojiMultiple('${emojiData.emoji}'); this.parentNode.parentNode.remove()" 
                        style="width: 100%; text-align: left; padding: 8px 15px; background: none; border: none; cursor: pointer;">
                    🔢 Çoklu Ekle
                </button>
            </div>
        `;
        
        document.body.appendChild(menu);
        
        // Menü dışına tıklanınca kapat
        const closeMenu = (event) => {
            if (!menu.contains(event.target)) {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            }
        };
        
        setTimeout(() => {
            document.addEventListener('click', closeMenu);
        }, 100);
    }
    
    // YENİ: Çoklu emoji ekle
    insertEmojiMultiple(emoji) {
        const count = parseInt(prompt('Kaç kere eklemek istersiniz?', '3')) || 3;
        const messageInput = document.getElementById('flood-message-input');
        
        if (messageInput) {
            const multipleEmojis = emoji.repeat(count);
            this.insertTextAtCursor(messageInput, multipleEmojis);
            this.updatePreview();
        }
    }
    
    // YENİ: Fallback modu
    useFallbackMode() {
        console.log('⚠️ Fallback modunda çalışılıyor');
        
        // Minimum fonksiyonellik
        this.settings = {
            maxChars: 200,
            separator: 'none',
            autoSave: true
        };
        
        // Fallback emojiler
        this.useFallbackEmojis();
        
        // Fallback kategoriler
        this.categories = {
            'genel': { name: 'Genel', emoji: '📝', slug: 'genel' }
        };
        
        this.initialized = true;
        this.showNotification('Flood sistemi fallback modunda çalışıyor', 'warning');
    }
    
    // YENİ: Koyu tema desteği
    enableDarkMode() {
        const container = document.getElementById('flood-editor-container');
        if (container) {
            container.classList.add('dark-mode');
        }
    }
    
    // YENİ: Gelişmiş mesaj hesaplama
    calculateMessageCost(message) {
        if (!message) return { chars: 0, emojiCost: 0, total: 0, emojiCount: 0, lineCount: 0 };
        
        let totalChars = message.length;
        let emojiCost = 0;
        let emojiCount = 0;
        let lineCount = (message.match(/\n/g) || []).length + 1;
        
        // Emoji tespiti için Unicode emoji regex
        const emojiRegex = /[\p{Emoji_Presentation}\p{Emoji}\uFE0F]/gu;
        const matches = message.match(emojiRegex) || [];
        
        matches.forEach(emoji => {
            const cost = typeof calculateChatChars === 'function' ? 
                        calculateChatChars(emoji) : emoji.length;
            emojiCost += cost;
            emojiCount++;
        });
        
        // Ayırıcı maliyeti ekle
        if (this.settings.separator !== 'none') {
            const separator = window.SEPARATOR_MAP ? window.SEPARATOR_MAP[this.settings.separator] : { length: 0 };
            if (separator.length > 0 && emojiCount > 1) {
                totalChars += (separator.length * (emojiCount - 1));
            }
        }
        
        return {
            chars: message.length,
            emojiCost: emojiCost,
            emojiCount: emojiCount,
            lineCount: lineCount,
            total: totalChars + emojiCost
        };
    }
    
    // YENİ: Gelişmiş önizleme güncelleme
    updatePreview() {
        const messageInput = document.getElementById('flood-message-input');
        const preview = document.getElementById('flood-preview');
        const charCount = document.getElementById('flood-char-count');
        const emojiCount = document.getElementById('flood-emoji-count');
        const emojiCost = document.getElementById('flood-emoji-cost');
        const totalCost = document.getElementById('flood-total-cost');
        const lineCount = document.getElementById('flood-line-count');
        const maxCharsSpan = document.getElementById('flood-max-chars');
        const warning = document.getElementById('flood-limit-warning');
        
        if (!messageInput || !preview) return;
        
        const message = messageInput.value;
        const cost = this.calculateMessageCost(message);
        const maxChars = this.settings.maxChars;
        
        // Önizlemeyi güncelle
        preview.textContent = message || 'Mesajınız burada YouTube sohbeti gibi görünecek...';
        
        // İstatistikleri güncelle
        if (charCount) {
            charCount.textContent = cost.chars;
            charCount.style.color = this.getColorForPercentage(cost.chars / maxChars);
        }
        
        if (emojiCount) {
            emojiCount.textContent = cost.emojiCount;
            emojiCount.style.color = cost.emojiCount > 10 ? '#ffc107' : '#28a745';
        }
        
        if (emojiCost) {
            emojiCost.textContent = cost.emojiCost;
            emojiCost.style.color = cost.emojiCost > 20 ? '#ffc107' : '#28a745';
        }
        
        if (totalCost) {
            totalCost.textContent = cost.total;
            totalCost.style.color = this.getColorForPercentage(cost.total / maxChars);
        }
        
        if (lineCount) {
            lineCount.textContent = cost.lineCount;
        }
        
        if (maxCharsSpan) maxCharsSpan.textContent = maxChars;
        
        // Limit uyarısı
        if (warning) {
            const percentage = (cost.total / maxChars) * 100;
            if (percentage > 100) {
                warning.style.display = 'block';
                warning.innerHTML = `⚠️ <strong>Limit aşıldı!</strong> ${cost.total - maxChars} karakter fazla`;
                warning.style.color = '#dc3545';
                warning.style.background = 'rgba(220, 53, 69, 0.1)';
                preview.style.borderColor = '#dc3545';
            } else if (percentage > 90) {
                warning.style.display = 'block';
                warning.innerHTML = `⚠️ <strong>Dikkat!</strong> %${Math.round(percentage)} doluluk`;
                warning.style.color = '#ffc107';
                warning.style.background = 'rgba(255, 193, 7, 0.1)';
                preview.style.borderColor = '#ffc107';
            } else {
                warning.style.display = 'none';
                preview.style.borderColor = 'var(--border-color)';
            }
        }
        
        // Progress bar güncelle (yeni)
        this.updateProgressBar(cost.total, maxChars);
    }
    
    // YENİ: Renk hesaplama yardımcısı
    getColorForPercentage(percentage) {
        if (percentage > 1) return '#dc3545';
        if (percentage > 0.9) return '#ffc107';
        if (percentage > 0.7) return '#007bff';
        return '#28a745';
    }
    
    // YENİ: Progress bar güncelle
    updateProgressBar(current, max) {
        const progressBar = document.getElementById('flood-progress-bar');
        if (!progressBar) return;
        
        const percentage = Math.min((current / max) * 100, 100);
        progressBar.style.width = `${percentage}%`;
        progressBar.style.background = this.getColorForPercentage(current / max);
    }
    
    // YENİ: Gelişmiş set render etme
    renderFloodSets() {
        const container = document.getElementById('flood-sets-list');
        if (!container) return;
        
        if (!this.floodSets || this.floodSets.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px; opacity: 0.7;">
                    <div style="font-size: 3em;">📁</div>
                    <div>Flood set'i bulunmuyor</div>
                    <button onclick="floodSystem.createNewSet()" class="btn-primary" style="margin-top: 15px;">
                        İlk Setini Oluştur
                    </button>
                    <div style="margin-top: 15px; font-size: 0.9em; opacity: 0.6;">
                        Veya <a href="#" onclick="floodSystem.importFloodSet()" style="color: var(--accent-color);">mevcut bir seti içe aktar</a>
                    </div>
                </div>
            `;
            return;
        }
        
        container.innerHTML = '';
        
        this.floodSets.forEach(set => {
            const setElement = document.createElement('div');
            setElement.className = 'flood-set-item';
            
            // Kategori rengini belirle
            const categoryColor = this.getCategoryColor(set.category);
            
            setElement.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex-grow: 1;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <div style="width: 4px; height: 24px; background: ${categoryColor}; border-radius: 2px;"></div>
                            <h4 style="margin: 0; color: var(--accent-color);">${set.name}</h4>
                            <span class="badge" style="background: ${set.is_public ? '#28a745' : '#6c757d'}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8em;">
                                ${set.is_public ? '🌍 Herkese Açık' : '🔒 Gizli'}
                            </span>
                            ${set.category ? `
                                <span class="badge" style="background: ${categoryColor}22; color: ${categoryColor}; padding: 2px 8px; border-radius: 12px; font-size: 0.8em;">
                                    ${this.categories[set.category]?.emoji || '📝'} ${this.categories[set.category]?.name || set.category}
                                </span>
                            ` : ''}
                        </div>
                        
                        ${set.description ? `<p style="font-size: 0.9em; opacity: 0.8; margin-bottom: 10px;">${set.description}</p>` : ''}
                        
                        <div style="display: flex; gap: 15px; font-size: 0.85em; opacity: 0.7;">
                            <span title="Mesaj sayısı">📝 ${set.message_count || 0}</span>
                            <span title="Oluşturulma tarihi">📅 ${new Date(set.created_at).toLocaleDateString('tr-TR')}</span>
                            ${set.updated_at !== set.created_at ? 
                              `<span title="Son güncelleme">✏️ ${new Date(set.updated_at).toLocaleDateString('tr-TR')}</span>` : ''}
                            <span title="Karakter ortalaması">🔤 ${this.getAverageChars(set)}</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 8px; margin-left: 10px;">
                        <button onclick="floodSystem.loadSet(${set.id})" class="btn-sm btn-primary" title="Set'i aç">
                            📂
                        </button>
                        <button onclick="floodSystem.editSet(${set.id})" class="btn-sm btn-secondary" title="Düzenle">
                            ✏️
                        </button>
                        <button onclick="floodSystem.duplicateSet(${set.id})" class="btn-sm btn-info" title="Çoğalt">
                            📋
                        </button>
                        <button onclick="floodSystem.exportSet(${set.id})" class="btn-sm btn-warning" title="Dışa aktar">
                            📤
                        </button>
                        <button onclick="floodSystem.deleteSet(${set.id})" class="btn-sm btn-danger" title="Sil">
                            🗑️
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(setElement);
        });
    }
    
    // YENİ: Kategori rengi hesapla
    getCategoryColor(categorySlug) {
        const colors = {
            'genel': '#007bff',
            'komik': '#ffc107',
            'spor': '#28a745',
            'müzik': '#dc3545',
            'oyun': '#6f42c1',
            'teknoloji': '#17a2b8'
        };
        
        return colors[categorySlug] || '#6c757d';
    }
    
    // YENİ: Ortalama karakter hesapla
    getAverageChars(set) {
        // Burada set içindeki mesajların ortalama karakter sayısını hesapla
        return set.avg_chars ? Math.round(set.avg_chars) : 'N/A';
    }
    
    // YENİ: Gelişmiş kaydetme fonksiyonu
async saveFloodMessage() {
    try {
        console.log('💾 Flood mesajı kaydediliyor...');
        
        const messageInput = document.getElementById('flood-message-input');
        const setSelect = document.getElementById('flood-set-select');
        
        if (!messageInput) {
            this.showNotification('Mesaj alanı bulunamadı', 'error');
            return;
        }
        
        const message = messageInput.value.trim();
        if (!message) {
            this.showNotification('Lütfen bir mesaj yazın', 'error');
            return;
        }
        
        // Karakter kontrolü
        const cost = this.calculateMessageCost(message);
        if (cost.total > this.settings.maxChars) {
            const confirm = window.confirm(
                `Mesajınız ${cost.total - this.settings.maxChars} karakter fazla!\n` +
                `Yine de kaydetmek istiyor musunuz?`
            );
            if (!confirm) return;
        }
        
        // Set kontrolü
        let setId = setSelect?.value;
        if (!setId || setId === '' || setId === 'new') {
            // Yeni set oluştur
            const newSetName = prompt('Yeni flood set adı girin:', `Set_${new Date().getTime()}`);
            if (!newSetName || !newSetName.trim()) {
                this.showNotification('Set adı gereklidir', 'error');
                return;
            }
            
            try {
                const response = await fetch(`${SITE_BASE_URL}core/create_flood_set.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: newSetName.trim(),
                        category: 'genel',
                        description: `Oluşturulma: ${new Date().toLocaleString()}`,
                        is_public: true
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    setId = result.set_id;
                    this.currentSetId = result.set_id;
                    
                    // Dropdown'a ekle
                    if (setSelect) {
                        const option = document.createElement('option');
                        option.value = result.set_id;
                        option.textContent = newSetName.trim();
                        setSelect.appendChild(option);
                        setSelect.value = result.set_id;
                    }
                    
                    this.showNotification('✅ Yeni set oluşturuldu', 'success');
                } else {
                    this.showNotification(`❌ ${result.message}`, 'error');
                    return;
                }
            } catch (error) {
                console.error('Set oluşturma hatası:', error);
                this.showNotification('❌ Set oluşturulamadı', 'error');
                return;
            }
        }
        
        // Mesajı kaydet
        const saveResponse = await fetch(`${SITE_BASE_URL}core/save_flood_message.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                set_id: setId,
                content: message,
                char_count: cost.chars,
                emoji_count: cost.emojiCount,
                order_index: Date.now()
            })
        });
        
        const saveResult = await saveResponse.json();
        
        if (saveResult.success) {
            this.showNotification('✅ Flood mesajı kaydedildi', 'success');
            
            // Otomatik temizleme
            if (this.settings.autoSave) {
                messageInput.value = '';
                this.updatePreview();
            }
            
            // Otomatik kopyalama
            if (this.settings.autoCopy) {
                this.copyFloodMessage();
            }
            
            // Set mesajlarını güncelle
            if (setId) {
                this.loadSetMessages(setId);
            }
            
        } else {
            this.showNotification(`❌ ${saveResult.message}`, 'error');
        }
        
    } catch (error) {
        console.error('❌ Flood mesaj kaydetme hatası:', error);
        this.showNotification('❌ Kayıt sırasında hata oluştu', 'error');
    }
}

copyFloodMessage() {
    const messageInput = document.getElementById('flood-message-input');
    if (!messageInput || !messageInput.value.trim()) {
        this.showNotification('Kopyalanacak mesaj yok', 'error');
        return;
    }
    
    navigator.clipboard.writeText(messageInput.value.trim())
        .then(() => {
            this.showNotification('📋 Mesaj panoya kopyalandı', 'success');
        })
        .catch(err => {
            console.error('Kopyalama hatası:', err);
            // Fallback
            messageInput.select();
            document.execCommand('copy');
            this.showNotification('📋 Mesaj kopyalandı (fallback)', 'success');
        });
}
    
    // YENİ: Set'i çoğalt
    async duplicateSet(setId) {
        const set = this.floodSets.find(s => s.id == setId);
        if (!set) return;
        
        const newName = prompt('Çoğaltılan set için yeni ad:', `${set.name} (Kopya)`);
        if (!newName) return;
        
        try {
            const response = await fetch(`${SITE_BASE_URL}core/duplicate_flood_set.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    original_set_id: setId,
                    new_name: newName,
                    copy_messages: true
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('✅ Set başarıyla çoğaltıldı!', 'success');
                await this.loadFloodSets();
            } else {
                this.showNotification(`❌ ${result.message}`, 'error');
            }
        } catch (error) {
            console.error('Set çoğaltma hatası:', error);
            this.showNotification('❌ Çoğaltma sırasında hata oluştu.', 'error');
        }
    }
    
    // YENİ: Set'i dışa aktar
    exportSet(setId) {
        const set = this.floodSets.find(s => s.id == setId);
        if (!set) return;
        
        // JSON formatında dışa aktar
        const exportData = {
            set: set,
            messages: this.floodMessages.filter(msg => msg.set_id == setId),
            export_date: new Date().toISOString(),
            version: '1.0'
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `flood_set_${set.name.replace(/[^a-z0-9]/gi, '_')}.json`;
        a.click();
        
        URL.revokeObjectURL(url);
        
        this.showNotification('✅ Set dışa aktarıldı!', 'success');
    }
    
    // YENİ: Set içe aktar
    importFloodSet() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            try {
                const text = await file.text();
                const importData = JSON.parse(text);
                
                // İçe aktarma işlemi
                const confirm = window.confirm(
                    `${importData.set.name} set'ini içe aktarmak istiyor musunuz?\n` +
                    `(${importData.messages?.length || 0} mesaj)`
                );
                
                if (confirm) {
                    await this.processImport(importData);
                }
            } catch (error) {
                console.error('İçe aktarma hatası:', error);
                this.showNotification('❌ Geçersiz dosya formatı.', 'error');
            }
        };
        
        input.click();
    }
    
    // YENİ: İçe aktarma işlemi
    async processImport(importData) {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/import_flood_set.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(importData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('✅ Set başarıyla içe aktarıldı!', 'success');
                await this.loadFloodSets();
            } else {
                this.showNotification(`❌ ${result.message}`, 'error');
            }
        } catch (error) {
            console.error('İçe aktarma işlemi hatası:', error);
            this.showNotification('❌ İçe aktarma sırasında hata oluştu.', 'error');
        }
    }
    
    // YENİ: Aktivite kaydet
    async recordActivity(activityType, data) {
        try {
            // LocalStorage'a kaydet
            const activities = JSON.parse(localStorage.getItem('floodActivities') || '[]');
            activities.unshift({
                type: activityType,
                data: data,
                timestamp: new Date().toISOString()
            });
            
            // Son 100 aktiviteyi sakla
            localStorage.setItem('floodActivities', JSON.stringify(activities.slice(0, 100)));
            
            // Sunucuya gönder (async)
            if (window.SITE_BASE_URL) {
                fetch(`${SITE_BASE_URL}core/record_activity.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        activity_type: activityType,
                        activity_data: data,
                        source: 'flood_editor'
                    })
                }).catch(console.error);
            }
        } catch (error) {
            console.error('Aktivite kaydetme hatası:', error);
        }
    }
    
    // YENİ: Bildirim göster
    showNotification(message, type = 'info') {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type, 3000);
        } else {
            // Fallback bildirim
            console.log(`${type.toUpperCase()}: ${message}`);
            
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                background: ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#007bff'};
                color: white;
                border-radius: 6px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span>${type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️'}</span>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
    }
	
	async loadEmojiPalette() {
    try {
        console.log('🌊 Flood emoji paleti yükleniyor...');
        
        // 1. URL'yi belirle
        const emojiUrl = window.EMOJI_JSON_URL || (window.SITE_BASE_URL + 'assets/json/emoji.json');
        console.log('📥 Emoji URL:', emojiUrl);
        
        const response = await fetch(emojiUrl);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const rawEmojis = await response.json();
        const emojiArray = Array.isArray(rawEmojis) ? rawEmojis : Object.values(rawEmojis);
        console.log(`✅ ${emojiArray.length} emoji yüklendi`);

        // 2. Veriyi işle - EMOJİLERİ HTML'E HAZIRLA
        this.emojiCategories = {};
        
        emojiArray.forEach(item => {
            const categoryName = (item.category || "Diğer").charAt(0).toUpperCase() + 
                                (item.category || "Diğer").slice(1);
            const emojiName = item.description || item.names?.[0] || item.name || 'İsimsiz';

            if (!this.emojiCategories[categoryName]) {
                this.emojiCategories[categoryName] = {
                    name: categoryName,
                    emojis: {}
                };
            }

            // Basit karakter maliyeti
            const charCost = item.emoji ? item.emoji.length : 1;

            this.emojiCategories[categoryName].emojis[emojiName] = {
                emoji: item.emoji,
                chars: charCost,
                name: emojiName
            };
        });

        // 3. İlk kategori ve emojiyi seç
        const categories = Object.keys(this.emojiCategories);
        if (categories.length > 0) {
            this.currentCategory = categories[0];
            const categoryEmojis = this.emojiCategories[this.currentCategory].emojis;
            const firstEmojiKey = Object.keys(categoryEmojis)[0];
            this.selectedEmoji = categoryEmojis[firstEmojiKey];
        }

        // 4. HTML'E EKLE - KRİTİK KISIM!
        this.renderEmojiTabs();    // Tab'ları oluştur
        this.renderEmojiGrid();    // Emoji grid'ini oluştur

        console.log('🎨 Flood emoji paleti HTML\'e eklendi');

    } catch (error) {
        console.error('❌ Flood emoji yükleme hatası:', error);
        this.useFallbackEmojis();
    }
}

// EKSİK FONKSİYON - flood.js'ye EKLEYİN:
useFallbackEmojis() {
    console.log('⚠️ Fallback emojiler kullanılıyor');
    
    this.emojiCategories = {
        'Kalpler': {
            name: 'Kalpler',
            emojis: {
                'Siyah Kalp': { emoji: '🖤', chars: 2, name: 'Siyah Kalp' },
                'Kırmızı Kalp': { emoji: '❤️', chars: 2, name: 'Kırmızı Kalp' },
                'Mavi Kalp': { emoji: '💙', chars: 2, name: 'Mavi Kalp' }
            }
        },
        'Yüzler': {
            name: 'Yüzler',
            emojis: {
                'Gülümseyen Yüz': { emoji: '😊', chars: 2, name: 'Gülümseyen Yüz' },
                'Kahkaha': { emoji: '😂', chars: 2, name: 'Kahkaha' }
            }
        }
    };
    
    this.currentCategory = 'Kalpler';
    this.selectedEmoji = this.emojiCategories['Kalpler'].emojis['Siyah Kalp'];
    
    // HTML'E EKLE
    this.renderEmojiTabs();
    this.renderEmojiGrid();
    
    console.log('✅ Fallback emojiler HTML\'e eklendi');
}
    
    async loadFloodSets() {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/get_flood_sets.php`);
            const result = await response.json();
            
            if (result.success) {
                this.floodSets = result.sets || [];
                this.renderFloodSets();
                this.updateSetDropdown();
            }
        } catch (error) {
            console.error('Flood set\'leri yüklenemedi:', error);
        }
    }
    
    async loadSettings() {
        try {
            const saved = localStorage.getItem('floodSystemSettings');
            if (saved) {
                this.settings = { ...this.settings, ...JSON.parse(saved) };
            }
            
            // UI'ı güncelle
            const maxCharsInput = document.getElementById('flood-max-chars-input');
            if (maxCharsInput) maxCharsInput.value = this.settings.maxChars;
            
            const maxCharsSpan = document.getElementById('flood-max-chars');
            if (maxCharsSpan) maxCharsSpan.textContent = this.settings.maxChars;
            
        } catch (error) {
            console.error('Ayarlar yüklenemedi:', error);
        }
    }
       
bindEvents() {
    try {
        console.log('🔗 Flood eventleri bağlanıyor...');
        
        // 1. Kaydet butonu
        const saveBtn = document.getElementById('save-flood-message-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                console.log('💾 Kaydet butonu tıklandı');
                this.saveFloodMessage();
            });
        }
        
        // 2. Kopyala butonu
        const copyBtn = document.getElementById('copy-flood-message-btn');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                console.log('📋 Kopyala butonu tıklandı');
                this.copyFloodMessage();
            });
        }
        
        // 3. Temizle butonu
        const clearBtn = document.getElementById('clear-flood-editor-btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                console.log('🧹 Temizle butonu tıklandı');
                this.clearEditor();
            });
        }
        
        // 4. Rastgele emoji butonu
        const randomBtn = document.getElementById('insert-random-emoji-btn');
        if (randomBtn) {
            randomBtn.addEventListener('click', () => {
                console.log('🎲 Rastgele emoji butonu tıklandı');
                this.insertRandomEmoji();
            });
        }
        
        // 5. Mesaj input değişikliği
        const messageInput = document.getElementById('flood-message-input');
        if (messageInput) {
            messageInput.addEventListener('input', () => {
                this.updatePreview();
            });
        }
        
        // 6. Otomatik kopyala checkbox
        const autoCopyCheck = document.getElementById('auto-copy');
        if (autoCopyCheck) {
            autoCopyCheck.addEventListener('change', (e) => {
                this.settings.autoCopy = e.target.checked;
                this.saveSettings();
                console.log('📋 Otomatik kopyala:', e.target.checked);
            });
        }
        
        // 7. Otomatik kaydet checkbox
        const autoSaveCheck = document.getElementById('auto-save');
        if (autoSaveCheck) {
            autoSaveCheck.addEventListener('change', (e) => {
                this.settings.autoSave = e.target.checked;
                this.saveSettings();
                console.log('💾 Otomatik kaydet:', e.target.checked);
            });
        }
        
        // 8. Maksimum karakter input
        const maxCharsInput = document.getElementById('flood-max-chars-input');
        if (maxCharsInput) {
            maxCharsInput.addEventListener('change', (e) => {
                this.settings.maxChars = parseInt(e.target.value) || 200;
                this.saveSettings();
                this.updatePreview();
                console.log('🔢 Maks karakter:', this.settings.maxChars);
            });
        }
        
        // 9. Set seçimi değişikliği
        const setSelect = document.getElementById('flood-set-select');
        if (setSelect) {
            setSelect.addEventListener('change', (e) => {
                if (e.target.value === 'new') {
                    this.showNewSetForm();
                } else if (e.target.value) {
                    this.currentSetId = e.target.value;
                    this.loadSet(e.target.value);
                }
            });
        }
        
        console.log('✅ Flood eventleri bağlandı');
        
    } catch (error) {
        console.error('❌ Flood event bağlama hatası:', error);
    }
}
    
    switchTab(tabId) {
        // Tüm tab'ları gizle
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Tüm tab butonlarını pasif yap
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Hedef tab'ı göster
        const targetTab = document.getElementById(tabId);
        const targetBtn = document.querySelector(`[data-tab="${tabId}"]`);
        
        if (targetTab) targetTab.classList.add('active');
        if (targetBtn) targetBtn.classList.add('active');
        
        // Emoji grid'i yenile (emoji sekmesindeyse)
        if (tabId === 'emoji-palette-tab') {
            this.renderEmojiGrid();
        }
    }
    
    // EMOJI PALETI FONKSİYONLARI
renderEmojiTabs() {
    try {
        const container = document.getElementById('flood-emoji-tabs');
        if (!container) {
            console.warn('❌ flood-emoji-tabs container bulunamadı');
            return;
        }
        
        container.innerHTML = '';
        
        if (!this.emojiCategories || Object.keys(this.emojiCategories).length === 0) {
            container.innerHTML = '<div style="padding: 10px; text-align: center; opacity: 0.7;">Kategoriler yükleniyor...</div>';
            return;
        }
        
        Object.keys(this.emojiCategories).forEach(categoryKey => {
            const category = this.emojiCategories[categoryKey];
            const button = document.createElement('button');
            
            // EMOJİ EDITÖRÜNDEKİ STİLİ KULLAN
            button.className = 'category-tab';
            button.textContent = category.name;
            button.title = category.name;
            
            // Aktif kategoriyi belirle
            if (categoryKey === this.currentCategory) {
                button.classList.add('active');
            }
            
            button.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Tüm butonlardan active class'ını kaldır
                container.querySelectorAll('.category-tab').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Tıklanan butonu aktif yap
                button.classList.add('active');
                
                // Kategoriyi değiştir
                this.currentCategory = categoryKey;
                console.log(`📁 Emoji kategorisi değişti: ${categoryKey}`);
                this.renderEmojiGrid();
            });
            
            container.appendChild(button);
        });
        
        console.log(`✅ ${Object.keys(this.emojiCategories).length} emoji kategorisi render edildi`);
        
    } catch (error) {
        console.error('❌ Emoji tabları render hatası:', error);
    }
}

// insertQuickEmoji fonksiyonu zaten var, kontrol edin:
insertQuickEmoji(emoji) {
    const messageInput = document.getElementById('flood-message-input');
    if (!messageInput) {
        console.error('❌ Mesaj inputu bulunamadı');
        return;
    }
    
    this.insertTextAtCursor(messageInput, emoji);
    this.updatePreview();
}
    
    insertQuickEmoji(emoji) {
        const messageInput = document.getElementById('flood-message-input');
        if (!messageInput) return;
        
        this.insertTextAtCursor(messageInput, emoji);
        this.updatePreview();
    }
    
insertRandomEmoji() {
    if (!this.emojiCategories || Object.keys(this.emojiCategories).length === 0) return;
    
    // Rastgele kategori
    const categories = Object.keys(this.emojiCategories);
    const randomCategoryKey = categories[Math.floor(Math.random() * categories.length)];
    const randomCategory = this.emojiCategories[randomCategoryKey];
    
    // Rastgele emoji
    const emojiKeys = Object.keys(randomCategory.emojis);
    const randomEmojiKey = emojiKeys[Math.floor(Math.random() * emojiKeys.length)];
    const randomEmoji = randomCategory.emojis[randomEmojiKey];
    
    // Mesaya ekle
    this.insertQuickEmoji(randomEmoji.emoji);
}
    
    insertEmojiCombo(combo) {
        const messageInput = document.getElementById('flood-message-input');
        if (!messageInput) return;
        
        this.insertTextAtCursor(messageInput, combo);
        this.updatePreview();
    }
    
    insertTextAtCursor(textarea, text) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const currentText = textarea.value;
        
        textarea.value = currentText.substring(0, start) + text + currentText.substring(end);
        
        // Kursoru eklenen metnin sonuna taşı
        const newPosition = start + text.length;
        textarea.selectionStart = newPosition;
        textarea.selectionEnd = newPosition;
        
        // Input'a odaklan ve değişiklik event'i tetikle
        textarea.focus();
        textarea.dispatchEvent(new Event('input'));
    }
    
    // MESAJ HESAPLAMA ve ÖNİZLEME
calculateMessageCost(message) {
    if (!message) return { chars: 0, emojiCost: 0, total: 0, emojiCount: 0 };
    
    let totalChars = message.length;
    let emojiCost = 0;
    let emojiCount = 0;
    
    // Emoji tespiti için Unicode emoji regex
    const emojiRegex = /[\p{Emoji_Presentation}\p{Emoji}\uFE0F]/gu;
    const matches = message.match(emojiRegex) || [];
    
    matches.forEach(emoji => {
        // AYNI HESAPLAMA: calculateChatChars fonksiyonunu kullan
        const cost = calculateChatChars(emoji);
        emojiCost += cost;
        emojiCount++;
    });
    
    // Ayırıcı maliyeti ekle (flood için de geçerli)
    if (this.settings.separator !== 'none') {
        const separator = window.SEPARATOR_MAP ? window.SEPARATOR_MAP[this.settings.separator] : { length: 0 };
        if (separator.length > 0 && emojiCount > 1) {
            // Emojiler arasına ayırıcı ekle
            totalChars += (separator.length * (emojiCount - 1));
        }
    }
    
    return {
        chars: message.length,
        emojiCost: emojiCost,
        emojiCount: emojiCount,
        total: totalChars
    };
}
       
    showNewSetForm() {
        const form = document.getElementById('new-set-form');
        const select = document.getElementById('flood-set-select');
        const newSetName = document.getElementById('new-set-name');
        
        if (form && select && newSetName) {
            form.style.display = 'block';
            select.style.display = 'none';
            newSetName.style.display = 'block';
            newSetName.focus();
        }
    }
    
    hideNewSetForm() {
        const form = document.getElementById('new-set-form');
        const select = document.getElementById('flood-set-select');
        const newSetName = document.getElementById('new-set-name');
        
        if (form && select && newSetName) {
            form.style.display = 'none';
            select.style.display = 'block';
            newSetName.style.display = 'none';
            newSetName.value = '';
        }
    }
    
    async createNewSet() {
        this.switchTab('flood-sets-tab');
        
        const form = document.getElementById('new-set-form');
        if (form) {
            form.style.display = 'block';
            document.getElementById('create-set-name').focus();
        }
    }
    
    async confirmCreateSet() {
        const nameInput = document.getElementById('create-set-name');
        const descInput = document.getElementById('create-set-desc');
        const publicInput = document.getElementById('create-set-public');
        
        if (!nameInput || !nameInput.value.trim()) {
            if (typeof showNotification === 'function') {
                showNotification('Set adı gereklidir', 'error');
            }
            return;
        }
        
        try {
            const response = await fetch(`${SITE_BASE_URL}core/create_flood_set.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: nameInput.value.trim(),
                    description: descInput?.value.trim() || '',
                    is_public: publicInput?.checked || true
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (typeof showNotification === 'function') {
                    showNotification('✅ Flood set\'i oluşturuldu!', 'success');
                }
                
                // Formu temizle
                if (nameInput) nameInput.value = '';
                if (descInput) descInput.value = '';
                
                // Formu gizle
                const form = document.getElementById('new-set-form');
                if (form) form.style.display = 'none';
                
                // Set listesini yenile
                await this.loadFloodSets();
                
                // Otomatik seç
                this.currentSetId = result.set_id;
                const select = document.getElementById('flood-set-select');
                if (select) select.value = result.set_id;
                
            } else {
                if (typeof showNotification === 'function') {
                    showNotification(`❌ ${result.message}`, 'error');
                }
            }
        } catch (error) {
            console.error('Set oluşturma hatası:', error);
            if (typeof showNotification === 'function') {
                showNotification('❌ Set oluşturulurken hata oluştu.', 'error');
            }
        }
    }
    
    cancelCreateSet() {
        const form = document.getElementById('new-set-form');
        if (form) form.style.display = 'none';
        
        const select = document.getElementById('flood-set-select');
        if (select) select.value = '';
    }
    
async loadSet(setId) {
    try {
        console.log(`📂 Set yükleniyor: ${setId}`);
        
        const response = await fetch(`${SITE_BASE_URL}core/get_flood_messages.php?set_id=${setId}`);
        const result = await response.json();
        
        if (result.success) {
            this.currentSetId = setId;
            this.floodMessages = result.messages || [];
            
            // Set bilgilerini göster
            this.showSetInfo(setId, result.set);
            
            // Mesajları listeleyen yeni bölüm oluştur
            this.showSetMessagesList();
            
            // Dropdown'da seçili yap
            const setSelect = document.getElementById('flood-set-select');
            if (setSelect) setSelect.value = setId;
            
            showNotification(`✅ "${result.set?.name || 'Set'}" yüklendi (${this.floodMessages.length} mesaj)`, 'success');
        } else {
            showNotification('❌ Set yüklenemedi', 'error');
        }
    } catch (error) {
        console.error('Set yüklenemedi:', error);
        showNotification('❌ Set yüklenirken hata oluştu.', 'error');
    }
}

/**
 * Set bilgilerini göster
 */
showSetInfo(setId, setData) {
    const container = document.getElementById('set-info');
    if (!container) return;
    
    container.innerHTML = `
        <div style="background: var(--fixed-bg); padding: 12px; border-radius: 8px; border-left: 4px solid var(--accent-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <strong style="color: var(--accent-color);">${setData?.name || 'Set'}</strong>
                <span style="font-size: 0.85em; opacity: 0.7;">
                    ${setData?.is_public ? '🌍 Herkese Açık' : '🔒 Gizli'}
                </span>
            </div>
            ${setData?.description ? `<p style="font-size: 0.9em; margin: 8px 0; opacity: 0.8;">${setData.description}</p>` : ''}
            <div style="display: flex; gap: 15px; font-size: 0.85em; opacity: 0.7;">
                <span>📝 ${setData?.message_count || 0} mesaj</span>
                <span>👁️ ${setData?.views || 0} görüntülenme</span>
                <span>❤️ ${setData?.likes || 0} beğeni</span>
            </div>
            <div style="margin-top: 8px; font-size: 0.8em; opacity: 0.6;">
                Oluşturulma: ${setData?.created_at ? new Date(setData.created_at).toLocaleDateString('tr-TR') : 'Bilinmiyor'}
            </div>
        </div>
    `;
}

/**
 * Set mesajlarını liste olarak göster
 */
showSetMessagesList() {
    // Mevcut liste container'ını bul veya oluştur
    let messagesContainer = document.getElementById('flood-messages-list');
    
    if (!messagesContainer) {
        // Set seçiminin altına mesaj listesi container'ı ekle
        const setInfo = document.getElementById('set-info');
        if (setInfo && setInfo.parentNode) {
            messagesContainer = document.createElement('div');
            messagesContainer.id = 'flood-messages-list';
            messagesContainer.style.cssText = `
                margin-top: 15px;
                max-height: 300px;
                overflow-y: auto;
                background: var(--fixed-bg);
                border-radius: 8px;
                border: 1px solid var(--border-color);
            `;
            
            setInfo.parentNode.insertBefore(messagesContainer, setInfo.nextSibling);
        } else {
            console.error('Set info container bulunamadı');
            return;
        }
    }
    
    if (this.floodMessages.length === 0) {
        messagesContainer.innerHTML = `
            <div style="text-align: center; padding: 30px; opacity: 0.7;">
                <div style="font-size: 2em;">📭</div>
                <p>Henüz mesaj eklenmemiş</p>
                <button onclick="window.floodSystem.addSampleMessages()" class="btn-sm btn-secondary" style="margin-top: 10px;">
                    Örnek Mesajlar Ekle
                </button>
            </div>
        `;
        return;
    }
    
    messagesContainer.innerHTML = `
        <div style="padding: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <strong style="color: var(--accent-color);">Mesaj Listesi (${this.floodMessages.length})</strong>
                <button onclick="window.floodSystem.exportSetMessages(${this.currentSetId})" class="btn-sm btn-secondary">
                    📤 Tümünü Dışa Aktar
                </button>
            </div>
            ${this.floodMessages.map((message, index) => `
                <div class="flood-message-item" data-message-id="${message.id}" style="
                    padding: 10px;
                    margin-bottom: 8px;
                    background: var(--card-bg);
                    border: 1px solid var(--border-color);
                    border-radius: 6px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    cursor: pointer;
                    transition: all 0.2s;
                ">
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span style="
                                background: var(--accent-color);
                                color: white;
                                width: 24px;
                                height: 24px;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 0.8em;
                            ">
                                ${index + 1}
                            </span>
                            <div style="flex: 1; overflow: hidden;">
                                <div style="
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                    font-size: 0.9em;
                                ">
                                    ${escapeHtml(message.content)}
                                </div>
                                <div style="font-size: 0.75em; opacity: 0.6; margin-top: 2px;">
                                    ${message.char_count || message.content.length} karakter
                                    • ${new Date(message.created_at).toLocaleDateString('tr-TR')}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 5px; margin-left: 10px;">
                        <button onclick="event.stopPropagation(); window.floodSystem.editMessage(${message.id})" 
                                class="btn-sm btn-secondary" title="Düzenle">
                            ✏️
                        </button>
                        <button onclick="event.stopPropagation(); window.floodSystem.copyMessage(${message.id})" 
                                class="btn-sm btn-secondary" title="Kopyala">
                            📋
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    
    // Mesaj item'larına tıklama event'i ekle
    messagesContainer.querySelectorAll('.flood-message-item').forEach(item => {
        item.addEventListener('click', (e) => {
            if (!e.target.closest('button')) {
                const messageId = item.dataset.messageId;
                const message = this.floodMessages.find(m => m.id == messageId);
                if (message) {
                    // Mesajı editöre yükle
                    const messageInput = document.getElementById('flood-message-input');
                    if (messageInput) {
                        messageInput.value = message.content;
                        this.updatePreview();
                        messageInput.focus();
                        
                        // İpucu göster
                        showNotification(`✏️ Mesaj #${item.querySelector('span').textContent} editöre yüklendi`, 'info');
                    }
                }
            }
        });
    });
}

/**
 * Mesajı kopyala
 */
async copyMessage(messageId) {
    try {
        const message = this.floodMessages.find(m => m.id == messageId);
        if (!message) return;
        
        await navigator.clipboard.writeText(message.content);
        showNotification('📋 Mesaj kopyalandı!', 'success');
    } catch (error) {
        console.error('Mesaj kopyalanamadı:', error);
        showNotification('Mesaj kopyalanamadı', 'error');
    }
}

/**
 * Mesajı düzenle
 */
editMessage(messageId) {
    const message = this.floodMessages.find(m => m.id == messageId);
    if (!message) return;
    
    // Mesajı editöre yükle
    const messageInput = document.getElementById('flood-message-input');
    if (messageInput) {
        messageInput.value = message.content;
        this.updatePreview();
        messageInput.focus();
        
        // Kaydet butonunu güncelle butonuna çevir
        const saveBtn = document.getElementById('save-flood-message-btn');
        const updateBtn = document.getElementById('update-flood-message-btn');
        const cancelBtn = document.getElementById('cancel-edit-btn');
        
        if (saveBtn) saveBtn.style.display = 'none';
        if (updateBtn) updateBtn.style.display = 'block';
        if (cancelBtn) cancelBtn.style.display = 'block';
        
        // Update butonuna event ekle
        updateBtn.onclick = () => this.updateMessage(messageId);
        cancelBtn.onclick = () => this.cancelEdit();
        
        showNotification(`✏️ Mesaj #${this.floodMessages.findIndex(m => m.id == messageId) + 1} düzenlemeye hazır`, 'info');
    }
}

/**
 * Mesajı güncelle
 */
async updateMessage(messageId) {
    const messageInput = document.getElementById('flood-message-input');
    if (!messageInput) return;
    
    const newContent = messageInput.value.trim();
    if (!newContent) {
        showNotification('Mesaj boş olamaz', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${SITE_BASE_URL}core/update_flood_message.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message_id: messageId,
                content: newContent,
                char_count: newContent.length
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('✅ Mesaj güncellendi!', 'success');
            
            // Listeyi yenile
            this.loadSet(this.currentSetId);
            
            // Editörü sıfırla
            messageInput.value = '';
            this.cancelEdit();
            this.updatePreview();
        } else {
            showNotification(`❌ ${result.message}`, 'error');
        }
    } catch (error) {
        console.error('Mesaj güncelleme hatası:', error);
        showNotification('❌ Güncelleme sırasında hata oluştu.', 'error');
    }
}

/**
 * Düzenlemeyi iptal et
 */
cancelEdit() {
    const messageInput = document.getElementById('flood-message-input');
    const saveBtn = document.getElementById('save-flood-message-btn');
    const updateBtn = document.getElementById('update-flood-message-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    
    if (messageInput) messageInput.value = '';
    if (saveBtn) saveBtn.style.display = 'block';
    if (updateBtn) updateBtn.style.display = 'none';
    if (cancelBtn) cancelBtn.style.display = 'none';
    
    this.updatePreview();
}

/**
 * Set mesajlarını dışa aktar
 */
exportSetMessages(setId) {
    if (!this.floodMessages.length) return;
    
    let exportText = `Flood Seti Mesajları\n`;
    exportText += `========================\n\n`;
    
    this.floodMessages.forEach((message, index) => {
        exportText += `${index + 1}. ${message.content}\n`;
    });
    
    exportText += `\n========================\n`;
    exportText += `Toplam: ${this.floodMessages.length} mesaj\n`;
    exportText += `Oluşturulma: ${new Date().toLocaleString('tr-TR')}\n`;
    
    const blob = new Blob([exportText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `flood_set_${setId}_${new Date().getTime()}.txt`;
    a.click();
    URL.revokeObjectURL(url);
    
    showNotification('📤 Mesajlar dışa aktarıldı!', 'success');
}
    
    showSetMessages() {
        alert(`Set'te ${this.floodMessages.length} mesaj bulunuyor. Bu özellik geliştirme aşamasında.`);
    }
      
    copyFloodMessage() {
        const messageInput = document.getElementById('flood-message-input');
        if (!messageInput || !messageInput.value.trim()) {
            if (typeof showNotification === 'function') {
                showNotification('Kopyalanacak mesaj yok.', 'error');
            }
            return;
        }
        
        navigator.clipboard.writeText(messageInput.value)
            .then(() => {
                if (typeof showNotification === 'function') {
                    showNotification('📋 Mesaj panoya kopyalandı!', 'success');
                }
            })
            .catch(err => {
                console.error('Kopyalama hatası:', err);
                
                // Fallback yöntemi
                messageInput.select();
                document.execCommand('copy');
                if (typeof showNotification === 'function') {
                    showNotification('📋 Mesaj kopyalandı! (fallback)', 'success');
                }
            });
    }
    
    clearEditor() {
        const messageInput = document.getElementById('flood-message-input');
        if (messageInput) {
            messageInput.value = '';
            this.updatePreview();
        }
        if (typeof showNotification === 'function') {
            showNotification('Editor temizlendi', 'info');
        }
    }
    
    // DİĞER YARDIMCI FONKSİYONLAR
    saveSettings() {
        localStorage.setItem('floodSystemSettings', JSON.stringify(this.settings));
    }
    
    openEditor() {
        try {
            const modal = document.getElementById('flood-editor-modal');
            if (!modal) {
                console.warn('Flood editör modalı bulunamadı');
                return false;
            }
            
            modal.style.display = 'flex';
            this.init();
            return true;
            
        } catch (error) {
            console.error('Flood editör açma hatası:', error);
            return false;
        }
    }
    
    closeEditor() {
        const modal = document.getElementById('flood-editor-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    async deleteSet(setId) {
        if (!confirm('Set\'i Sil\n\nBu flood set\'ini silmek istediğinizden emin misiniz? İçindeki tüm mesajlar da silinecek.')) {
            return;
        }
        
        try {
            const response = await fetch(`${SITE_BASE_URL}core/delete_flood_set.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ set_id: setId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (typeof showNotification === 'function') {
                    showNotification('✅ Flood set\'i silindi.', 'success');
                }
                await this.loadFloodSets();
                
                // Eğer silinen set seçiliyse, seçimi temizle
                if (this.currentSetId === setId) {
                    this.currentSetId = null;
                    const select = document.getElementById('flood-set-select');
                    if (select) select.value = '';
                }
            } else {
                if (typeof showNotification === 'function') {
                    showNotification(`❌ ${result.message}`, 'error');
                }
            }
        } catch (error) {
            console.error('Set silme hatası:', error);
            if (typeof showNotification === 'function') {
                showNotification('❌ Set silinirken hata oluştu.', 'error');
            }
        }
    }
    
    editSet(setId) {
        const set = this.floodSets.find(s => s.id == setId);
        if (!set) return;
        
        const newName = prompt('Yeni set adı:', set.name);
        if (!newName || newName.trim() === set.name) return;
        
        console.log(`Set ${setId} güncellenecek: ${newName}`);
        if (typeof showNotification === 'function') {
            showNotification('Set güncelleme özelliği geliştirme aşamasında.', 'info');
        }
    }
	
/**
 * Flood editörünü sekme içinde render et
 */
renderFloodTab() {
    console.log('🌊 Flood sekmesi render ediliyor...');
    
    const floodContainer = document.getElementById('flood-editor-container');
	
    if (!floodContainer) {
        console.warn('⚠️ flood-editor-container bulunamadı, oluşturuluyor...');	
        const floodTab = document.getElementById('flood-tab');
    // MEVCUT YAPIDAN FAYDALAN, YENİSİNİ OLUŞTURMA
    floodTab.innerHTML = `
        <div class="flood-editor-initialized" style="width: 100%; height: 100%; display: flex; flex-direction: column;">
            <!-- Üst Kontroller -->
            <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                        🌊 Flood Set'i:
                    </label>
                    <select id="flood-set-select" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); background: var(--fixed-bg); color: var(--main-text);">
                        <option value="">Yeni set oluştur...</option>
                    </select>
                </div>
                <div id="new-set-form" style="display: none; flex: 1;">
                    <input type="text" id="new-set-name" placeholder="Set adı" 
                           style="width: 100%; padding: 8px; margin-bottom: 5px; border: 1px solid var(--border-color); background: var(--fixed-bg); color: var(--main-text);">
                    <div style="display: flex; gap: 5px;">
                        <button onclick="floodSystem.confirmCreateSet()" class="btn-primary btn-sm">
                            Oluştur
                        </button>
                        <button onclick="floodSystem.cancelCreateSet()" class="btn-danger btn-sm">
                            İptal
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Ana Editör Alanı -->
            <div style="flex: 1; display: flex; flex-direction: column; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <label style="font-weight: bold;">📝 Flood Mesajı:</label>
                    <div style="display: flex; gap: 5px;">
                        <button id="flood-insert-random" onclick="floodSystem.insertRandomEmoji()" class="btn-secondary btn-sm">
                            🎲 Rastgele Emoji
                        </button>
                        <button onclick="floodSystem.clearEditor()" class="btn-danger btn-sm">
                            🧹 Temizle
                        </button>
                    </div>
                </div>
                
                <textarea id="flood-message-input" 
                          placeholder="Flood mesajınızı yazın... Emojiler ekleyebilirsiniz 😊"
                          style="flex: 1; width: 100%; padding: 15px; font-size: 16px; 
                                 border: 2px solid var(--border-color); border-radius: 8px;
                                 resize: none; font-family: inherit; background: var(--fixed-bg); color: var(--main-text);"></textarea>
            </div>
            
            <!-- Hızlı Emoji Butonları - EMOJİ.JSON'DAN YÜKLENECEK -->
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">😊 Emoji Paleti:</label>
                <div id="flood-emoji-tabs" style="margin-bottom: 10px;"></div>
                <div id="flood-emoji-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); gap: 5px; max-height: 150px; overflow-y: auto; padding: 10px; background: var(--fixed-bg); border-radius: 8px;"></div>
            </div>
            
            <!-- İstatistikler ve Önizleme -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <!-- Sol: İstatistikler -->
                <div style="background: var(--fixed-bg); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="margin-bottom: 10px; font-weight: bold;">📊 İstatistikler</div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <div>
                            <span style="opacity: 0.8;">Karakter:</span><br>
                            <span id="flood-char-count" style="font-size: 1.2em; font-weight: bold;">0</span>/
                            <span id="flood-max-chars">${this.settings.maxChars || 200}</span>
                        </div>
                        <div>
                            <span style="opacity: 0.8;">Emoji:</span><br>
                            <span id="flood-emoji-count" style="font-size: 1.2em; font-weight: bold;">0</span>
                        </div>
                        <div>
                            <span style="opacity: 0.8;">Emoji Maliyeti:</span><br>
                            <span id="flood-emoji-cost" style="font-size: 1.2em; font-weight: bold;">0</span>
                        </div>
                        <div>
                            <span style="opacity: 0.8;">Toplam:</span><br>
                            <span id="flood-total-cost" style="font-size: 1.2em; font-weight: bold; color: var(--accent-color);">0</span>
                        </div>
                    </div>
                    <div id="flood-limit-warning" 
                         style="display: none; margin-top: 10px; padding: 8px; 
                                background: #fff3cd; border-radius: 4px; color: #856404;">
                        ⚠️ Karakter limiti aşılıyor!
                    </div>
                </div>
                
                <!-- Sağ: Önizleme -->
                <div style="background: var(--fixed-bg); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="margin-bottom: 10px; font-weight: bold;">👁️ Önizleme</div>
                    <div id="flood-preview" 
                         style="height: 120px; overflow-y: auto; font-family: Arial, sans-serif; 
                                line-height: 1.4; padding: 10px; background: white; border-radius: 4px; color: black;">
                        Mesajınız burada YouTube sohbeti gibi görünecek...
                    </div>
                </div>
            </div>
            
            <!-- Aksiyon Butonları -->
<div style="display: flex; gap: 10px;">
    <button id="flood-save-button" onclick="floodSystem.saveFloodMessage()" 
            class="btn-primary" style="flex: 1; padding: 12px; font-size: 16px;">
        💾 Kaydet
    </button>
    <button id="flood-copy-button" onclick="floodSystem.copyFloodMessage()" 
            class="btn-success" style="flex: 1; padding: 12px; font-size: 16px;">
        📋 Kopyala
    </button>
    <button id="flood-clear-button" onclick="floodSystem.clearEditor()" 
            class="btn-danger" style="flex: 1; padding: 12px; font-size: 16px;">
        🧹 Temizle
    </button>
</div>
        </div>
    `;    
    floodContainer = document.getElementById('flood-editor-container');
    } else {
		console.log('⚠️ Flood editör zaten yüklenmiş');
		return true;		
	}
	
	if (!floodContainer) {
        console.error('❌ Flood container oluşturulamadı!');
        return false;
    }
	
    // Flood sistemini başlat
    this.init();
    
    return true;
}

/**
 * Sekme değiştiğinde flood editörü başlat
 */
setupTabSwitching() {
    const floodTab = document.getElementById('flood-tab');
    const emojiTab = document.getElementById('emoji-tab');
    const floodTabBtn = document.querySelector('[data-tab="flood"]');
    const emojiTabBtn = document.querySelector('[data-tab="emoji"]');
    
    if (!floodTabBtn || !emojiTabBtn) return;
    
    // Tab butonlarına event listener ekle
    floodTabBtn.addEventListener('click', () => {
        console.log('🌊 Flood sekmesi seçildi');
        
        // Flood editörünü başlat
        if (!this.initialized) {
            this.renderFloodTab();
        }
        
        // Aktif tab'ı güncelle
        if (floodTab) floodTab.style.display = 'block';
        if (emojiTab) emojiTab.style.display = 'none';
        
        // Buton stillerini güncelle
        floodTabBtn.classList.add('active');
        floodTabBtn.style.background = 'var(--accent-color)';
        floodTabBtn.style.color = 'white';
        
        emojiTabBtn.classList.remove('active');
        emojiTabBtn.style.background = 'var(--fixed-bg)';
        emojiTabBtn.style.color = 'var(--main-text)';
    });
    
    emojiTabBtn.addEventListener('click', () => {
        console.log('🎨 Emoji sekmesi seçildi');
        
        // Aktif tab'ı güncelle
        if (floodTab) floodTab.style.display = 'none';
        if (emojiTab) emojiTab.style.display = 'block';
        
        // Buton stillerini güncelle
        emojiTabBtn.classList.add('active');
        emojiTabBtn.style.background = 'var(--accent-color)';
        emojiTabBtn.style.color = 'white';
        
        floodTabBtn.classList.remove('active');
        floodTabBtn.style.background = 'var(--fixed-bg)';
        floodTabBtn.style.color = 'var(--main-text)';
    });    
    console.log('✅ Tab switching sistemi başlatıldı');
	}
}

// index.php'nin JavaScript kısmına ekleyin
async function loadFloodCategories() {
    try {
        const response = await fetch(`${SITE_BASE_URL}core/get_flood_categories.php`);
        const result = await response.json();
        
        if (result.success && result.categories) {
            const filterSelect = document.getElementById('flood-filter');
            if (filterSelect) {
                // Mevcut seçenekleri koru
                const existingOptions = Array.from(filterSelect.options);
                
                // Kategori seçeneklerini ekle
                Object.values(result.categories).forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.slug || category.id;
                    option.textContent = `${category.emoji || '📁'} ${category.name}`;
                    filterSelect.appendChild(option);
                });
                
                console.log(`✅ ${Object.keys(result.categories).length} flood kategorisi yüklendi`);
            }
        }
    } catch (error) {
        console.error('Flood kategorileri yüklenemedi:', error);
    }
}

/**
 * Profil flood set'lerini yükle
 */
async function loadProfileFloodSets() {
    try {
        const container = document.getElementById('flood-sets-container');
        if (!container || !window.PROFILE_DATA?.userId) return;
        
        container.innerHTML = '<div style="text-align: center; padding: 20px; opacity: 0.7;">Yükleniyor...</div>';
        
        const response = await fetch(`${SITE_BASE_URL}core/get_user_flood_sets.php?user_id=${window.PROFILE_DATA.userId}`);
        const result = await response.json();
        
        if (result.success) {
            displayProfileFloodSets(result.sets);
        } else {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px; opacity: 0.7;">
                    <div style="font-size: 3em;">📭</div>
                    <p>Henüz flood set'i bulunmuyor.</p>
                    ${window.PROFILE_DATA.isProfileOwner ? `
                        <button onclick="window.openIntegratedEditor('flood')" class="btn-primary" style="margin-top: 15px;">
                            🌊 İlk Flood Set'ini Oluştur
                        </button>
                    ` : ''}
                </div>
            `;
        }
    } catch (error) {
        console.error('❌ Profil flood setleri yüklenemedi:', error);
        const container = document.getElementById('flood-sets-container');
        if (container) {
            container.innerHTML = '<div style="color: #dc3545; text-align: center;">Yüklenirken hata oluştu.</div>';
        }
    }
}

/**
 * Profil flood set'lerini göster
 */
function displayProfileFloodSets(sets) {
    const container = document.getElementById('flood-sets-container');
    if (!container) return;
    
    if (!sets || sets.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; opacity: 0.7;">
                <div style="font-size: 3em;">📭</div>
                <p>Henüz flood set'i bulunmuyor.</p>
                ${window.PROFILE_DATA.isProfileOwner ? `
                    <button onclick="window.openIntegratedEditor('flood')" class="btn-primary" style="margin-top: 15px;">
                        🌊 İlk Flood Set'ini Oluştur
                    </button>
                ` : ''}
            </div>
        `;
        return;
    }
    
    container.innerHTML = '';
    
    // Kategorilere göre grupla
    const categories = {};
    sets.forEach(set => {
        const category = set.category || 'genel';
        const categoryName = set.category_name || category;
        const categoryEmoji = set.category_emoji || '📁';
        
        if (!categories[category]) {
            categories[category] = {
                name: categoryName,
                emoji: categoryEmoji,
                sets: []
            };
        }
        categories[category].sets.push(set);
    });
    
    // Her kategori için bölüm oluştur
    Object.entries(categories).forEach(([categoryKey, categoryData]) => {
        const categorySection = document.createElement('div');
        categorySection.className = 'category-section';
        categorySection.style.marginBottom = '30px';
        
        // Kategori başlığı
        categorySection.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                <div style="
                    background: var(--accent-color)20;
                    color: var(--accent-color);
                    padding: 6px 15px;
                    border-radius: 20px;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 1.1em;
                ">
                    ${categoryData.emoji} ${categoryData.name}
                    <span style="font-size: 0.8em; opacity: 0.8;">
                        (${categoryData.sets.length})
                    </span>
                </div>
            </div>
            
            <div class="flood-sets-grid" style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 15px;
            "></div>
        `;
        
        // Grid içine set'leri ekle
        const grid = categorySection.querySelector('.flood-sets-grid');
        categoryData.sets.forEach(set => {
            const card = createFloodSetCard(set);
            grid.appendChild(card);
        });
        
        container.appendChild(categorySection);
    });
}

/**
 * Flood set kartı oluştur (index.php'dekiyle aynı)
 */
function createFloodSetCard(set) {
    const card = document.createElement('div');
    card.className = 'flood-set-card';
    card.dataset.setId = set.id;
    card.dataset.category = set.category || 'genel';
    
    // Kategori rengi
    const categoryColors = {
        'genel': '#007bff',
        'komik': '#ffc107',
        'spor': '#28a745',
        'müzik': '#dc3545',
        'oyun': '#6f42c1',
        'teknoloji': '#17a2b8',
        'youtube': '#FF0000',
        'twitch': '#9146FF'
    };
    
    const categoryColor = categoryColors[set.category] || '#6c757d';
    
    card.innerHTML = `
        <!-- Kategori Başlığı -->
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <span style="
                background: ${categoryColor}20;
                color: ${categoryColor};
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 0.8em;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 5px;
            ">
                ${set.category_emoji || '📁'} ${set.category_name || set.category || 'Genel'}
            </span>
            
            <span style="margin-left: auto; display: flex; gap: 5px;">
                ${set.is_public ? '<span title="Herkese Açık" style="color: #28a745;">🌍</span>' : '<span title="Gizli" style="color: #6c757d;">🔒</span>'}
            </span>
        </div>
        
        <!-- Set Başlığı -->
        <div style="margin-bottom: 10px;">
            <h4 style="margin: 0; font-size: 1.2em; color: var(--accent-color); cursor: pointer;">
                ${escapeHtml(set.name)}
            </h4>
            ${set.description ? `
                <p style="margin: 8px 0; font-size: 0.9em; opacity: 0.8; line-height: 1.4;">
                    ${escapeHtml(set.description.substring(0, 120))}
                    ${set.description.length > 120 ? '...' : ''}
                </p>
            ` : ''}
        </div>
        
        <!-- İstatistikler -->
        <div style="display: flex; gap: 15px; font-size: 0.85em; margin-bottom: 15px; opacity: 0.7;">
            <span title="Mesaj sayısı">📝 ${set.message_count || 0}</span>
            <span title="Görüntülenme">👁️ ${set.views || 0}</span>
            <span title="Beğeni">❤️ ${set.likes || 0}</span>
            <span title="Oluşturulma" style="margin-left: auto;">
                ${formatTimeAgo(set.created_at)}
            </span>
        </div>
        
        <!-- Sahip Bilgisi (Profil sayfasında gerek yok) -->
        
        <!-- Aksiyon Butonları -->
        <div style="display: flex; gap: 8px; margin-top: 15px;">
            <button onclick="openFloodSetEditor(${set.id})" 
                    class="btn-sm btn-primary" style="flex: 1;">
                ✏️ Aç
            </button>
            <button onclick="copyFloodSetToClipboard(${set.id})" 
                    class="btn-sm btn-secondary" style="flex: 1;">
                📋 Kopyala
            </button>
        </div>
    `;
    
    // Set başlığına tıklanınca da açılabilir
    card.querySelector('h4').addEventListener('click', () => {
        openFloodSetEditor(set.id);
    });
    
    // Hover efektleri
    card.style.cssText = `
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    `;
    
    card.onmouseover = () => {
        card.style.transform = 'translateY(-3px)';
        card.style.boxShadow = '0 6px 20px rgba(0,0,0,0.1)';
        card.style.borderColor = categoryColor;
    };
    
    card.onmouseout = () => {
        card.style.transform = 'translateY(0)';
        card.style.boxShadow = 'none';
        card.style.borderColor = 'var(--border-color)';
    };
    
    return card;
}

/**
 * Kategoriye göre filtrele
 */
function filterFloodSetsByCategory(category) {
    const cards = document.querySelectorAll('.flood-set-card');
    
    cards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Flood set editörünü aç
 */
function openFloodSetEditor(setId) {
    if (window.integratedEditor) {
        window.integratedEditor.openModal();
        setTimeout(() => {
            window.integratedEditor.switchEditor('flood');
            
            // Set'i yükle
            setTimeout(() => {
                if (window.floodSystem && window.floodSystem.loadSet) {
                    window.floodSystem.loadSet(setId);
                }
            }, 200);
        }, 100);
    }
}

/**
 * Flood set'i panoya kopyala
 */
async function copyFloodSetToClipboard(setId) {
    try {
        const response = await fetch(`${SITE_BASE_URL}core/get_flood_messages.php?set_id=${setId}`);
        const result = await response.json();
        
        if (result.success && result.messages.length > 0) {
            let output = '';
            result.messages.forEach((message, index) => {
                output += `${index + 1}. ${message.content}\n`;
            });
            
            await navigator.clipboard.writeText(output);
            showNotification('📋 Tüm set kopyalandı!', 'success');
        }
    } catch (error) {
        console.error('Set kopyalanamadı:', error);
        showNotification('Kopyalama başarısız', 'error');
    }
}

/**
 * HTML escape
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Flood segmentini aktif et
function switchToFloodSegment() {
    const segmentBtn = document.getElementById('show-floods');
    if (segmentBtn) segmentBtn.click();
    
    // Flood set'lerini yükle
    setTimeout(() => {
        fetchFloodSets(1);
        
        // Filtre event'lerini bağla
        const filterSelect = document.getElementById('flood-filter');
        const sortSelect = document.getElementById('flood-sort');
        
        if (filterSelect) {
            filterSelect.addEventListener('change', () => {
                fetchFloodSets(1, filterSelect.value, sortSelect?.value || 'newest');
            });
        }
        
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                fetchFloodSets(1, filterSelect?.value || 'all', sortSelect.value);
            });
        }
    }, 100);
}

// Sayfa yüklendiğinde flood sistemi hazırla
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        // Flood segment butonuna event ekle
        const floodSegmentBtn = document.getElementById('show-floods');
        if (floodSegmentBtn) {
            floodSegmentBtn.addEventListener('click', () => {
                setTimeout(() => {
                    fetchFloodSets(1);
                }, 300);
            });
        }
        
        // Eğer flood segmenti aktifse hemen yükle
        if (document.querySelector('#show-floods.active')) {
            switchToFloodSegment();
        }
    }, 2000);
});

/**
 * Global fonksiyonları tanımla (main.js ile uyumluluk için)
 */
FloodSystem.prototype.registerGlobalFunctions = function() {
    window.openFloodEditor = () => this.openEditor();
    window.closeFloodEditor = () => this.closeEditor();
    window.saveFloodMessage = () => this.saveFloodMessage();
    window.copyFloodMessage = () => this.copyFloodMessage();
    window.clearFloodEditor = () => this.clearEditor();
    window.insertRandomEmoji = () => this.insertRandomEmoji();
};

// Global fonksiyonları tanımla
window.loadProfileFloodSets = loadProfileFloodSets;
window.displayProfileFloodSets = displayProfileFloodSets;
window.createFloodSetCard = createFloodSetCard;
window.filterFloodSetsByCategory = filterFloodSetsByCategory;
window.openFloodSetEditor = openFloodSetEditor;
window.copyFloodSetToClipboard = copyFloodSetToClipboard;
window.escapeHtml = escapeHtml;
window.formatTimeAgo = formatTimeAgo;

// Global instance - değişmeden kalacak
document.addEventListener('DOMContentLoaded', function() {
	setTimeout(() => {
        // Flood segment butonuna event ekle
        const floodSegmentBtn = document.getElementById('show-floods');
        if (floodSegmentBtn) {
            floodSegmentBtn.addEventListener('click', () => {
                setTimeout(() => {
                    fetchFloodSets(1);
                }, 300);
            });
        }
        
        // Eğer flood segmenti aktifse hemen yükle
        if (document.querySelector('#show-floods.active')) {
            switchToFloodSegment();
        }
    }, 2000);
	try {
        if (typeof window.floodSystem === 'undefined') {
            if (typeof FloodSystem !== 'undefined') {
                window.floodSystem = new FloodSystem();
                console.log('✅ floodSystem instance oluşturuldu');
            } else {
                console.error('❌ FloodSystem classı bulunamadı');
                // Fallback basit floodSystem
                window.floodSystem = {
                    init: function() { console.log('⚠️ Basit floodSystem init'); },
                    settings: { maxChars: 200, separator: 'none' }
                };
            }
        }
    } catch (error) {
        console.error('FloodSystem oluşturma hatası:', error);
    }
	
	// Sayfa yüklendiğinde çalıştır
	if (document.getElementById('flood-filter')) {
		setTimeout(loadFloodCategories, 1000);
		// Flood set'lerini yükle
		setTimeout(() => {
			    // Flood set butonu
    const floodSetBtn = document.getElementById('profile-flood-set-btn');
    if (floodSetBtn) {
        floodSetBtn.addEventListener('click', function() {
            if (window.integratedEditor) {
                window.integratedEditor.openModal();
                setTimeout(() => {
                    window.integratedEditor.switchEditor('flood');
                }, 100);
            }
        });
    }
    
    // Kategori filtreleri
    document.querySelectorAll('.category-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Aktif butonu güncelle
            document.querySelectorAll('.category-filter-btn').forEach(b => {
                b.classList.remove('active');
                b.style.background = 'var(--fixed-bg)';
                b.style.color = 'var(--main-text)';
            });
            
            this.classList.add('active');
            this.style.background = 'var(--accent-color)';
            this.style.color = 'white';
            
            // Flood set'lerini filtrele
            const category = this.dataset.category;
            filterFloodSetsByCategory(category);
        });
    });
			
			if (window.PROFILE_DATA && window.PROFILE_DATA.userId) {
				console.log('📥 Profil flood set\'leri yükleniyor...');
				loadProfileFloodSets();
			}
		}, 2000);
	}
});