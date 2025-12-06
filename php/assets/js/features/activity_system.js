// assets/js/features/activity_system.js
class ActivitySystem {
    constructor() {
        this.container = null;
        this.currentUserId = window.currentUser?.id;
        this.isInitialized = false;
    }

    init() {
        if (this.isInitialized) return;
        
        console.log('📅 Aktivite sistemi başlatılıyor...');
        
        // Container'ı bul
        this.container = document.getElementById('user-activities');
        if (!this.container) {
            console.warn('⚠️ user-activities konteyneri bulunamadı');
            return;
        }
        
        // Aktivite türü filtrelerini başlat
        this.initActivityFilters();
        
        // Aktivite yükle
        this.loadActivities();
        
        this.isInitialized = true;
        console.log('✅ Aktivite sistemi hazır');
    }

    async loadActivities(page = 1, type = 'all') {
        if (!this.container) return;
        
        try {
            this.container.innerHTML = '<div style="text-align: center; padding: 20px; opacity: 0.7;">Aktiviteler yükleniyor...</div>';
            
            const userId = window.PROFILE_DATA?.userId || window.currentUser?.id;
            if (!userId) {
                this.container.innerHTML = '<div style="text-align: center; opacity: 0.7;">Kullanıcı bulunamadı.</div>';
                return;
            }
            
            const limit = 20;
            const offset = (page - 1) * limit;
            
            const response = await fetch(`${SITE_BASE_URL}core/get_user_activities.php?user_id=${userId}&type=${type}&limit=${limit}&offset=${offset}`);
            const result = await response.json();
            
            if (result.success) {
                this.displayActivities(result.activities);
                
                // Sayfalama ekle
                if (result.total_pages > 1) {
                    this.addPagination(page, result.total_pages, type);
                }
            } else {
                this.container.innerHTML = `<div style="color: #dc3545; text-align: center;">Aktiviteler yüklenemedi: ${result.message}</div>`;
            }
        } catch (error) {
            console.error('Aktiviteler yüklenemedi:', error);
            this.container.innerHTML = '<div style="color: #dc3545; text-align: center;">Aktiviteler yüklenirken hata oluştu.</div>';
        }
    }

    displayActivities(activities) {
        if (!this.container || !activities || activities.length === 0) {
            this.container.innerHTML = `
                <div style="text-align: center; padding: 40px; opacity: 0.7;">
                    <div style="font-size: 3em;">📭</div>
                    <p>Henüz aktivite bulunmuyor.</p>
                    <p style="font-size: 0.9em; margin-top: 10px;">
                        İlk çizimini paylaş veya flood set'i oluştur!
                    </p>
                </div>
            `;
            return;
        }
        
        this.container.innerHTML = '';
        
        activities.forEach(activity => {
            const activityElement = this.createActivityElement(activity);
            this.container.appendChild(activityElement);
        });
    }

    createActivityElement(activity) {
        const element = document.createElement('div');
        element.className = 'activity-item';
        element.style.cssText = `
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            margin-bottom: 12px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        `;
        
        // Hover efekti
        element.onmouseover = () => {
            element.style.transform = 'translateY(-2px)';
            element.style.boxShadow = '0 5px 15px rgba(0,0,0,0.08)';
            element.style.borderColor = activity.color || 'var(--accent-color)';
        };
        
        element.onmouseout = () => {
            element.style.transform = 'translateY(0)';
            element.style.boxShadow = 'none';
            element.style.borderColor = 'var(--border-color)';
        };
        
        // Tıklanabilir yap
        if (activity.link && activity.link !== '#') {
            element.style.cursor = 'pointer';
            element.onclick = () => {
                if (activity.link.startsWith('http')) {
                    window.open(activity.link, '_blank');
                } else {
                    window.location.href = activity.link;
                }
            };
        }
        
        // Aktivite ikonu
        const icon = document.createElement('div');
        icon.style.cssText = `
            font-size: 1.8em;
            flex-shrink: 0;
            padding: 10px;
            border-radius: 50%;
            background: ${activity.color}20;
            color: ${activity.color || 'var(--accent-color)'};
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
        `;
        icon.textContent = activity.icon || '🔔';
        
        // Aktivite içeriği
        const content = document.createElement('div');
        content.style.flex = '1';
        content.style.minWidth = '0';
        
        // Kullanıcı bilgisi
        const userInfo = document.createElement('div');
        userInfo.style.display = 'flex';
        userInfo.style.alignItems = 'center';
        userInfo.style.gap = '10px';
        userInfo.style.marginBottom = '8px';
        
        const profilePic = document.createElement('img');
        profilePic.src = activity.user_profile_picture ? 
            Utils.formatProfilePicture(activity.user_profile_picture) : 
            SITE_BASE_URL + 'assets/img/default.png';
        profilePic.alt = activity.user_username;
        profilePic.style.cssText = 'width: 32px; height: 32px; border-radius: 50%; object-fit: cover;';
        
        const username = document.createElement('strong');
        username.textContent = activity.user_username;
        username.style.color = 'var(--accent-color)';
        
        userInfo.appendChild(profilePic);
        userInfo.appendChild(username);
        
        // Aktivite mesajı
        const message = document.createElement('div');
        message.style.marginBottom = '5px';
        message.style.fontSize = '1em';
        
        const messageText = document.createElement('span');
        messageText.textContent = activity.message;
        message.appendChild(messageText);
        
        // Aktivite başlığı (varsa)
        let titleHtml = '';
        if (activity.title) {
            titleHtml = `
                <div style="
                    margin: 5px 0;
                    font-weight: 600;
                    color: ${activity.color || 'var(--accent-color)'};
                    font-size: 1.05em;
                ">
                    "${activity.title}"
                </div>
            `;
        }
        
        // Aktivite açıklaması (varsa)
        let descriptionHtml = '';
        if (activity.description) {
            descriptionHtml = `
                <div style="
                    margin: 5px 0;
                    font-size: 0.9em;
                    opacity: 0.8;
                    padding: 8px;
                    background: var(--fixed-bg);
                    border-radius: 6px;
                    border-left: 3px solid ${activity.color || 'var(--accent-color)'};
                ">
                    ${activity.description}
                </div>
            `;
        }
        
        // Ekstra bilgiler (varsa)
        let extraInfoHtml = '';
        if (activity.extra_info) {
            extraInfoHtml = `
                <div style="font-size: 0.85em; opacity: 0.7; margin: 5px 0;">
                    ${activity.extra_info}
                </div>
            `;
        }
        
        // İstatistikler (varsa)
        let statsHtml = '';
        if (activity.stats) {
            const stats = [];
            if (activity.stats.views) stats.push(`👁️ ${activity.stats.views}`);
            if (activity.stats.likes) stats.push(`❤️ ${activity.stats.likes}`);
            if (activity.stats.comments) stats.push(`💬 ${activity.stats.comments}`);
            
            if (stats.length > 0) {
                statsHtml = `
                    <div style="
                        display: flex;
                        gap: 15px;
                        margin: 8px 0;
                        font-size: 0.85em;
                        opacity: 0.7;
                    ">
                        ${stats.join(' • ')}
                    </div>
                `;
            }
        }
        
        // Zaman bilgisi
        const timeInfo = document.createElement('div');
        timeInfo.style.cssText = `
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            font-size: 0.85em;
            opacity: 0.7;
        `;
        
        const timeAgo = document.createElement('span');
        timeAgo.textContent = activity.time_ago || activity.formatted_date;
        
        const fullDate = document.createElement('span');
        fullDate.textContent = activity.formatted_date;
        fullDate.style.fontSize = '0.8em';
        
        timeInfo.appendChild(timeAgo);
        timeInfo.appendChild(fullDate);
        
        // Tüm içeriği birleştir
        content.innerHTML = `
            ${userInfo.outerHTML}
            ${message.outerHTML}
            ${titleHtml}
            ${descriptionHtml}
            ${extraInfoHtml}
            ${statsHtml}
            ${timeInfo.outerHTML}
        `;
        
        element.appendChild(icon);
        element.appendChild(content);
        
        return element;
    }

    initActivityFilters() {
        const filterContainer = document.getElementById('activity-filters');
        if (!filterContainer) return;
        
        const filters = [
            { id: 'all', text: 'Tümü', icon: '📊' },
            { id: 'drawing', text: 'Çizimler', icon: '🎨' },
            { id: 'flood_set', text: 'Flood Set\'leri', icon: '🌊' },
            { id: 'flood_message', text: 'Flood Mesajları', icon: '💬' },
            { id: 'game', text: 'Oyunlar', icon: '🎮' },
            { id: 'message', text: 'Mesajlar', icon: '📝' },
            { id: 'follow', text: 'Takip', icon: '👥' },
            { id: 'challenge', text: 'Davetler', icon: '⚔️' }
        ];
        
        filterContainer.innerHTML = '';
        
        filters.forEach(filter => {
            const button = document.createElement('button');
            button.className = 'activity-filter-btn';
            button.dataset.type = filter.id;
            button.style.cssText = `
                padding: 8px 15px;
                border: 1px solid var(--border-color);
                background: var(--fixed-bg);
                color: var(--main-text);
                border-radius: 20px;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 0.9em;
                display: flex;
                align-items: center;
                gap: 5px;
            `;
            
            button.innerHTML = `${filter.icon} ${filter.text}`;
            
            if (filter.id === 'all') {
                button.style.background = 'var(--accent-color)';
                button.style.color = 'white';
                button.style.borderColor = 'var(--accent-color)';
            }
            
            button.addEventListener('click', () => {
                // Tüm butonları sıfırla
                document.querySelectorAll('.activity-filter-btn').forEach(btn => {
                    btn.style.background = 'var(--fixed-bg)';
                    btn.style.color = 'var(--main-text)';
                    btn.style.borderColor = 'var(--border-color)';
                });
                
                // Aktif butonu vurgula
                button.style.background = 'var(--accent-color)';
                button.style.color = 'white';
                button.style.borderColor = 'var(--accent-color)';
                
                // Aktiviteleri filtrele
                this.loadActivities(1, filter.id);
            });
            
            filterContainer.appendChild(button);
        });
    }

    addPagination(currentPage, totalPages, type) {
        if (totalPages <= 1) return;
        
        const pagination = document.createElement('div');
        pagination.className = 'activity-pagination';
        pagination.style.cssText = `
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        `;
        
        // Önceki butonu
        if (currentPage > 1) {
            const prevBtn = document.createElement('button');
            prevBtn.textContent = '← Önceki';
            prevBtn.className = 'btn-secondary btn-sm';
            prevBtn.onclick = () => this.loadActivities(currentPage - 1, type);
            pagination.appendChild(prevBtn);
        }
        
        // Sayfa numaraları
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === currentPage ? 'btn-primary btn-sm' : 'btn-secondary btn-sm';
            pageBtn.style.margin = '0 2px';
            pageBtn.onclick = () => this.loadActivities(i, type);
            pagination.appendChild(pageBtn);
        }
        
        // Sonraki butonu
        if (currentPage < totalPages) {
            const nextBtn = document.createElement('button');
            nextBtn.textContent = 'Sonraki →';
            nextBtn.className = 'btn-secondary btn-sm';
            nextBtn.onclick = () => this.loadActivities(currentPage + 1, type);
            pagination.appendChild(nextBtn);
        }
        
        this.container.appendChild(pagination);
    }

    // Yeni aktivite ekle (real-time için)
    addNewActivity(activityData) {
        if (!this.container) return;
        
        const activityElement = this.createActivityElement(activityData);
        
        // En üste ekle
        if (this.container.firstChild) {
            this.container.insertBefore(activityElement, this.container.firstChild);
        } else {
            this.container.appendChild(activityElement);
        }
        
        // Animasyon
        activityElement.style.opacity = '0';
        activityElement.style.transform = 'translateY(-10px)';
        
        setTimeout(() => {
            activityElement.style.transition = 'all 0.3s';
            activityElement.style.opacity = '1';
            activityElement.style.transform = 'translateY(0)';
        }, 10);
        
        // Bildirim göster (isteğe bağlı)
        if (typeof showNotification === 'function' && activityData.user_id !== this.currentUserId) {
            showNotification(`📅 ${activityData.user_username}: ${activityData.message}`, 'info', 3000);
        }
    }
}

// Global instance
if (typeof window.activitySystem === 'undefined') {
    window.activitySystem = new ActivitySystem();
}

// Profil sayfası yüklendiğinde aktivite sistemini başlat
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        if (window.activitySystem && window.PROFILE_DATA?.userId) {
            window.activitySystem.init();
        }
    }, 1000);
});