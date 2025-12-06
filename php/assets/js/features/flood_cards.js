// assets/js/features/flood_cards.js
class FloodCardSystem {
    constructor() {
        this.categories = {};
        this.sets = [];
        this.currentUserSets = [];
        this.isInitialized = false;
    }

    async init() {
        if (this.isInitialized) return;
        
        console.log('🃏 Flood kart sistemi başlatılıyor...');
        
        // Kategorileri yükle
        await this.loadCategories();
        
        // Global fonksiyonları tanımla
        this.registerGlobalFunctions();
        
        this.isInitialized = true;
        console.log('✅ Flood kart sistemi hazır');
    }

    async loadCategories() {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/get_flood_categories.php`);
            const result = await response.json();
            
            if (result.success) {
                this.categories = result.categories;
                console.log(`📂 ${Object.keys(this.categories).length} kategori yüklendi`);
            } else {
                // Fallback kategoriler
                this.categories = {
                    'genel': { name: 'Genel', emoji: '📁', color: '#6c757d' },
                    'youtube': { name: 'YouTube', emoji: '📺', color: '#FF0000' },
                    'twitch': { name: 'Twitch', emoji: '🔴', color: '#9146FF' },
                    'eglence': { name: 'Eğlence', emoji: '😂', color: '#FFC107' },
                    'oyun': { name: 'Oyun', emoji: '🎮', color: '#28a745' }
                };
            }
        } catch (error) {
            console.error('Kategoriler yüklenemedi:', error);
        }
    }

    // FLOOD SET KARTI OLUŞTURMA
    createFloodSetCard(set) {
        const card = document.createElement('div');
        card.className = 'flood-set-card';
        card.dataset.setId = set.id;
        card.dataset.category = set.category || 'genel';
        
        // Kart stilleri
        card.style.cssText = `
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        `;

        // Kategori başlığı
        const category = this.categories[set.category] || this.categories['genel'];
        const categoryColor = category?.color || '#6c757d';

        // Kart içeriği
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
                    ${category?.emoji || '📁'} ${category?.name || 'Genel'}
                </span>
                
                <span style="margin-left: auto; display: flex; gap: 5px;">
                    ${set.is_public ? '<span title="Herkese Açık" style="color: #28a745;">🌍</span>' : '<span title="Gizli" style="color: #6c757d;">🔒</span>'}
                    ${set.featured ? '<span title="Öne Çıkan" style="color: #ffc107;">⭐</span>' : ''}
                </span>
            </div>
            
            <!-- Set Başlığı -->
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                <h4 style="margin: 0; font-size: 1.2em; color: var(--accent-color);">
                    ${this.escapeHtml(set.name)}
                </h4>
                <span class="badge" style="background: var(--accent-color); color: white; padding: 2px 8px; border-radius: 12px;">
                    ${set.message_count || 0} mesaj
                </span>
            </div>
            
            <!-- Açıklama -->
            ${set.description ? `
                <p style="margin: 10px 0; font-size: 0.9em; opacity: 0.8; line-height: 1.4;">
                    ${this.escapeHtml(set.description.substring(0, 120))}
                    ${set.description.length > 120 ? '...' : ''}
                </p>
            ` : ''}
            
            <!-- Mesaj Listesi -->
            <div id="messages-${set.id}" style="
                max-height: 150px;
                overflow-y: auto;
                margin: 15px 0;
                padding: 10px;
                background: var(--fixed-bg);
                border-radius: 8px;
                border: 1px solid var(--border-color);
            ">
                <div style="text-align: center; padding: 10px; opacity: 0.7;">
                    Mesajlar yükleniyor...
                </div>
            </div>
            
            <!-- İstatistikler -->
            <div style="display: flex; gap: 15px; font-size: 0.85em; margin-bottom: 15px; opacity: 0.7;">
                <span title="Görüntülenme">👁️ ${set.views || 0}</span>
                <span title="Beğeni">❤️ ${set.likes || 0}</span>
                <span title="Kopyalanma">📋 ${set.copy_count || 0}</span>
                <span title="Oluşturulma" style="margin-left: auto;">
                    ${this.formatTimeAgo(set.created_at)}
                </span>
            </div>
            
            <!-- Sahip Bilgisi -->
            <div style="display: flex; align-items: center; gap: 8px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                <img src="${set.author_profile_picture ? this.formatProfilePicture(set.author_profile_picture) : '/images/default.png'}" 
                     alt="${set.author_username}" 
                     style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                <a href="/${set.author_username}/" style="color: var(--accent-color); font-size: 0.9em;">
                    ${set.author_username}
                </a>
            </div>
            
            <!-- Aksiyon Butonları -->
            <div style="display: flex; gap: 8px; margin-top: 15px;">
                <button onclick="window.openFloodSetEditor(${set.id})" 
                        class="btn-sm btn-primary"
                        style="flex: 1;">
                    ✏️ Düzenle
                </button>
                <button onclick="window.copyFloodSetToClipboard(${set.id})" 
                        class="btn-sm btn-secondary"
                        style="flex: 1;">
                    📋 Tümünü Kopyala
                </button>
                ${window.currentUser && window.currentUser.id === set.user_id ? `
                    <button onclick="window.deleteFloodSet(${set.id})" 
                            class="btn-sm btn-danger">
                        🗑️ Sil
                    </button>
                ` : ''}
            </div>
        `;

        // Hover efektleri
        card.onmouseover = () => {
            card.style.transform = 'translateY(-3px)';
            card.style.boxShadow = '0 6px 20px rgba(0,0,0,0.1)';
            card.style.borderColor = 'var(--accent-color)';
        };

        card.onmouseout = () => {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = 'none';
            card.style.borderColor = 'var(--border-color)';
        };

        // Mesajları yükle (async)
        setTimeout(() => this.loadSetMessages(set.id), 100);

        return card;
    }

    async loadSetMessages(setId) {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/get_flood_messages.php?set_id=${setId}`);
            const result = await response.json();
            
            if (result.success && result.messages.length > 0) {
                const container = document.getElementById(`messages-${setId}`);
                if (!container) return;
                
                container.innerHTML = '';
                
                result.messages.forEach((message, index) => {
                    const messageElement = this.createMessageElement(message, index + 1);
                    container.appendChild(messageElement);
                });
            } else {
                const container = document.getElementById(`messages-${setId}`);
                if (container) {
                    container.innerHTML = '<div style="text-align: center; padding: 20px; opacity: 0.7;">Henüz mesaj eklenmemiş.</div>';
                }
            }
        } catch (error) {
            console.error('Mesajlar yüklenemedi:', error);
        }
    }

    createMessageElement(message, order) {
        const element = document.createElement('div');
        element.className = 'flood-message-item';
        element.style.cssText = `
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            margin-bottom: 5px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            transition: all 0.2s;
        `;
        
        element.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
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
                    ${order}
                </span>
                <div style="flex: 1; overflow: hidden;">
                    <div style="
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        font-size: 0.9em;
                        color: var(--main-text);
                    ">
                        ${this.escapeHtml(message.content)}
                    </div>
                    <div style="font-size: 0.75em; opacity: 0.6; margin-top: 2px;">
                        ${message.char_count || message.content.length} karakter
                    </div>
                </div>
            </div>
            <button onclick="window.copyFloodMessage(${message.id})" 
                    class="btn-sm btn-secondary"
                    style="margin-left: 10px; flex-shrink: 0;">
                📋
            </button>
        `;
        
        element.onmouseover = () => {
            element.style.backgroundColor = 'var(--fixed-bg)';
            element.style.borderColor = 'var(--accent-color)';
        };
        
        element.onmouseout = () => {
            element.style.backgroundColor = 'var(--card-bg)';
            element.style.borderColor = 'var(--border-color)';
        };
        
        return element;
    }

    // KATEGORİLERE GÖRE GRUPLA
    groupSetsByCategory(sets) {
        const grouped = {};
        
        sets.forEach(set => {
            const category = set.category || 'genel';
            if (!grouped[category]) {
                grouped[category] = {
                    category: this.categories[category] || { name: 'Genel', emoji: '📁', color: '#6c757d' },
                    sets: []
                };
            }
            grouped[category].sets.push(set);
        });
        
        return grouped;
    }

    // PROFİL SAYFASI İÇİN KATEGORİZELİ GÖSTERİM
    renderProfileFloodSets(userId, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = '<div style="text-align: center; padding: 20px; opacity: 0.7;">Yükleniyor...</div>';
        
        fetch(`${SITE_BASE_URL}core/get_user_flood_sets.php?user_id=${userId}`)
            .then(response => response.json())
            .then(result => {
                if (result.success && result.sets.length > 0) {
                    const grouped = this.groupSetsByCategory(result.sets);
                    container.innerHTML = '';
                    
                    Object.entries(grouped).forEach(([categoryKey, data]) => {
                        const categoryElement = this.createCategorySection(categoryKey, data);
                        container.appendChild(categoryElement);
                    });
                } else {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 40px; opacity: 0.7;">
                            <div style="font-size: 3em;">📭</div>
                            <p>Henüz flood set'i bulunmuyor.</p>
                            <button onclick="window.openIntegratedEditor('flood')" 
                                    class="btn-primary" style="margin-top: 15px;">
                                🌊 İlk Flood Set'ini Oluştur
                            </button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Flood set\'leri yüklenemedi:', error);
                container.innerHTML = '<div style="color: #dc3545; text-align: center;">Yüklenirken hata oluştu.</div>';
            });
    }

    createCategorySection(categoryKey, data) {
        const section = document.createElement('div');
        section.className = 'flood-category-section';
        section.style.marginBottom = '30px';
        
        const category = data.category;
        
        section.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                <div style="
                    background: ${category.color}20;
                    color: ${category.color};
                    padding: 6px 15px;
                    border-radius: 20px;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 1.1em;
                ">
                    ${category.emoji} ${category.name}
                    <span style="font-size: 0.8em; opacity: 0.8;">
                        (${data.sets.length})
                    </span>
                </div>
            </div>
            
            <div class="flood-sets-grid" style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 15px;
            ">
                <!-- Set kartları buraya eklenecek -->
            </div>
        `;
        
        const grid = section.querySelector('.flood-sets-grid');
        data.sets.forEach(set => {
            const card = this.createFloodSetCard(set);
            grid.appendChild(card);
        });
        
        return section;
    }

    // GLOBAL FONKSİYONLARI KAYDET
    registerGlobalFunctions() {
        window.openFloodSetEditor = (setId) => this.openSetEditor(setId);
        window.copyFloodSetToClipboard = (setId) => this.copySetToClipboard(setId);
        window.copyFloodMessage = (messageId) => this.copyMessage(messageId);
        window.deleteFloodSet = (setId) => this.deleteSet(setId);
        window.loadProfileFloodSets = (userId) => this.renderProfileFloodSets(userId, 'flood-sets-container');
    }

    async openSetEditor(setId) {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/get_flood_set.php?id=${setId}`);
            const result = await response.json();
            
            if (result.success) {
                // Editör modalını aç
                if (window.integratedEditor) {
                    window.integratedEditor.openModal();
                    setTimeout(() => {
                        window.integratedEditor.switchEditor('flood');
                        // Set bilgilerini editöre yükle
                        this.loadSetToEditor(result.set);
                    }, 100);
                }
            }
        } catch (error) {
            console.error('Set editörü açılamadı:', error);
            showNotification('Set açılırken hata oluştu', 'error');
        }
    }

    loadSetToEditor(set) {
        const floodSystem = window.floodSystem;
        if (!floodSystem) return;
        
        // Set seçimi
        const setSelect = document.getElementById('flood-set-select');
        if (setSelect) {
            setSelect.value = set.id;
        }
        
        // Mesajları yükle
        if (floodSystem.loadSet) {
            floodSystem.loadSet(set.id);
        }
    }

    async copySetToClipboard(setId) {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/get_flood_messages.php?set_id=${setId}`);
            const result = await response.json();
            
            if (result.success) {
                let output = '';
                result.messages.forEach((message, index) => {
                    output += `${index + 1}. ${message.content}\n`;
                });
                
                await navigator.clipboard.writeText(output);
                showNotification('📋 Tüm set kopyalandı!', 'success');
                
                // Kopyalama sayısını artır
                this.incrementCopyCount(setId);
            }
        } catch (error) {
            console.error('Set kopyalanamadı:', error);
            showNotification('Kopyalama başarısız', 'error');
        }
    }

    async copyMessage(messageId) {
        try {
            const response = await fetch(`${SITE_BASE_URL}core/get_flood_message.php?id=${messageId}`);
            const result = await response.json();
            
            if (result.success) {
                await navigator.clipboard.writeText(result.message.content);
                showNotification('📋 Mesaj kopyalandı!', 'success');
            }
        } catch (error) {
            console.error('Mesaj kopyalanamadı:', error);
            showNotification('Mesaj kopyalanamadı', 'error');
        }
    }

    async deleteSet(setId) {
        if (!confirm('Bu flood set\'ini silmek istediğinizden emin misiniz? Tüm mesajlar da silinecek.')) {
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
                showNotification('✅ Flood set\'i silindi', 'success');
                
                // Kartı kaldır
                const card = document.querySelector(`.flood-set-card[data-set-id="${setId}"]`);
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(-10px)';
                    setTimeout(() => card.remove(), 300);
                }
                
                // Profil sayfasındaysa yenile
                if (window.PROFILE_DATA?.isProfileOwner) {
                    setTimeout(() => this.renderProfileFloodSets(window.PROFILE_DATA.userId, 'flood-sets-container'), 500);
                }
            } else {
                showNotification(`❌ ${result.message}`, 'error');
            }
        } catch (error) {
            console.error('Set silinemedi:', error);
            showNotification('Set silinirken hata oluştu', 'error');
        }
    }

    async incrementCopyCount(setId) {
        try {
            await fetch(`${SITE_BASE_URL}core/increment_copy_count.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ set_id: setId, type: 'copy' })
            });
        } catch (error) {
            console.error('Kopyalama sayısı artırılamadı:', error);
        }
    }

    // YARDIMCI FONKSİYONLAR
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatTimeAgo(dateString) {
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
    }

    formatProfilePicture(profilePic) {
        if (!profilePic || profilePic === 'default.png') {
            return SITE_BASE_URL + 'assets/img/default.png';
        }
        if (profilePic.startsWith('data:image')) {
            return profilePic;
        }
        return 'data:image/jpeg;base64,' + profilePic;
    }
}

// Global instance
if (typeof window.floodCardSystem === 'undefined') {
    window.floodCardSystem = new FloodCardSystem();
}