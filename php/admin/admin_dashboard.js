// ADMIN DASHBOARD ÖZEL FONKSİYONLAR
class AdminDashboard {
    constructor() {
        this.stats = {};
        this.charts = {};
        this.realtimeData = {};
    }

    init() {
        this.loadDashboardStats();
        this.initRealtimeUpdates();
        this.bindDashboardEvents();
        console.log('📊 Admin dashboard başlatıldı');
    }

    // Dashboard istatistiklerini yükle
    async loadDashboardStats() {
        try {
            const response = await fetch(SITE_BASE_URL + 'admin/get_dashboard_stats.php');
            const result = await response.json();

            if (result.success) {
                this.stats = result.stats;
                this.updateStatsDisplay();
                this.initCharts();
            }
        } catch (error) {
            console.error('Dashboard istatistikleri yüklenirken hata:', error);
        }
    }

    // İstatistikleri ekranda güncelle
    updateStatsDisplay() {
        // Toplam kullanıcı
        const totalUsersEl = document.querySelector('[data-stat="total-users"]');
        if (totalUsersEl) {
            totalUsersEl.textContent = this.stats.total_users?.toLocaleString() || '0';
        }

        // Toplam çizim
        const totalDrawingsEl = document.querySelector('[data-stat="total-drawings"]');
        if (totalDrawingsEl) {
            totalDrawingsEl.textContent = this.stats.total_drawings?.toLocaleString() || '0';
        }

        // Bugünkü aktivite
        const todayActivityEl = document.querySelector('[data-stat="today-activity"]');
        if (todayActivityEl) {
            todayActivityEl.textContent = this.stats.today_activity?.toLocaleString() || '0';
        }

        // Aktif kullanıcılar
        const activeUsersEl = document.querySelector('[data-stat="active-users"]');
        if (activeUsersEl) {
            activeUsersEl.textContent = this.stats.active_users?.toLocaleString() || '0';
        }
    }

    // Grafikleri başlat
    initCharts() {
        // Eğer Chart.js mevcutsa grafikleri oluştur
        if (typeof Chart !== 'undefined') {
            this.createActivityChart();
            this.createUserGrowthChart();
            this.createContentDistributionChart();
        }
    }

    // Aktivite grafiği
    createActivityChart() {
        const ctx = document.getElementById('activity-chart');
        if (!ctx) return;

        this.charts.activity = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.stats.activity_labels || [],
                datasets: [{
                    label: 'Günlük Aktivite',
                    data: this.stats.activity_data || [],
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Kullanıcı büyüme grafiği
    createUserGrowthChart() {
        const ctx = document.getElementById('user-growth-chart');
        if (!ctx) return;

        this.charts.userGrowth = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: this.stats.user_growth_labels || [],
                datasets: [{
                    label: 'Yeni Kullanıcılar',
                    data: this.stats.user_growth_data || [],
                    backgroundColor: '#2196F3'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // İçerik dağılım grafiği
    createContentDistributionChart() {
        const ctx = document.getElementById('content-distribution-chart');
        if (!ctx) return;

        this.charts.contentDistribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Çizimler', 'Yorumlar', 'Beğeniler', 'Takipçiler'],
                datasets: [{
                    data: [
                        this.stats.total_drawings || 0,
                        this.stats.total_comments || 0,
                        this.stats.total_likes || 0,
                        this.stats.total_follows || 0
                    ],
                    backgroundColor: [
                        '#4CAF50',
                        '#2196F3',
                        '#FF9800',
                        '#9C27B0'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Real-time güncellemeleri başlat
    initRealtimeUpdates() {
        // Ably bağlantısı varsa real-time güncellemeleri dinle
        if (typeof notificationSystem !== 'undefined' && notificationSystem.isAblyConnected) {
            this.subscribeToRealtimeUpdates();
        }

        // 30 saniyede bir istatistikleri yenile
        setInterval(() => {
            this.loadDashboardStats();
        }, 30000);
    }

    // Real-time güncellemelere abone ol
    subscribeToRealtimeUpdates() {
        const adminChannel = notificationSystem.ably.channels.get('admin-dashboard');

        adminChannel.subscribe('stats_update', (message) => {
            this.handleRealtimeStatsUpdate(message.data);
        });

        adminChannel.subscribe('new_user', (message) => {
            this.handleNewUser(message.data);
        });

        adminChannel.subscribe('new_content', (message) => {
            this.handleNewContent(message.data);
        });
    }

    // Real-time istatistik güncellemesi
    handleRealtimeStatsUpdate(data) {
        this.stats = { ...this.stats, ...data };
        this.updateStatsDisplay();

        // Grafikleri güncelle
        if (this.charts.activity) {
            this.charts.activity.data.datasets[0].data = data.activity_data || this.charts.activity.data.datasets[0].data;
            this.charts.activity.update('none');
        }
    }

    // Yeni kullanıcı bildirimi
    handleNewUser(data) {
        this.showRealtimeNotification(`Yeni kullanıcı: ${data.username}`, 'success');

        // İstatistikleri güncelle
        this.stats.total_users = (this.stats.total_users || 0) + 1;
        this.stats.today_activity = (this.stats.today_activity || 0) + 1;
        this.updateStatsDisplay();
    }

    // Yeni içerik bildirimi
    handleNewContent(data) {
        this.showRealtimeNotification(`Yeni ${data.content_type}: ${data.title}`, 'info');

        // İstatistikleri güncelle
        if (data.content_type === 'drawing') {
            this.stats.total_drawings = (this.stats.total_drawings || 0) + 1;
        } else if (data.content_type === 'comment') {
            this.stats.total_comments = (this.stats.total_comments || 0) + 1;
        }

        this.stats.today_activity = (this.stats.today_activity || 0) + 1;
        this.updateStatsDisplay();
    }

    // Real-time bildirim göster
    showRealtimeNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `realtime-notification ${type}`;
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>🔔</span>
                <span>${message}</span>
                <small>${new Date().toLocaleTimeString('tr-TR')}</small>
            </div>
        `;

        notification.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: ${type === 'success' ? '#4CAF50' : '#2196F3'};
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10001;
            animation: slideInRight 0.3s ease-out;
            max-width: 300px;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }

    // Dashboard event'lerini bağla
    bindDashboardEvents() {
        // Hızlı aksiyon butonları
        this.bindQuickActionButtons();

        // Sistem durumu göstergesi
        this.initSystemStatus();

        // Hızlı arama
        this.initQuickSearch();
    }

    // Hızlı aksiyon butonları
    bindQuickActionButtons() {
        // Toplu bildirim butonu
        const broadcastBtn = document.querySelector('[data-action="broadcast"]');
        if (broadcastBtn) {
            broadcastBtn.addEventListener('click', () => {
                this.openBroadcastModal();
            });
        }

        // Sistem temizleme butonu
        const cleanupBtn = document.querySelector('[data-action="cleanup"]');
        if (cleanupBtn) {
            cleanupBtn.addEventListener('click', () => {
                this.runSystemCleanup();
            });
        }

        // Veri yedekleme butonu
        const backupBtn = document.querySelector('[data-action="backup"]');
        if (backupBtn) {
            backupBtn.addEventListener('click', () => {
                this.createBackup();
            });
        }
    }

    // Toplu bildirim modalını aç
    openBroadcastModal() {
        const modal = document.getElementById('broadcast-modal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    // Sistem temizleme çalıştır
    async runSystemCleanup() {
        const confirmed = await adminActions.showConfirm(
            'Sistem Temizleme',
            'Önbellek temizleme ve geçici dosyaları silme işlemini başlatmak istediğinizden emin misiniz?'
        );

        if (confirmed) {
            try {
                const response = await fetch(SITE_BASE_URL + 'admin/run_system_cleanup.php');
                const result = await response.json();

                adminActions.showAdminNotification(
                    result.message,
                    result.success ? 'success' : 'error'
                );
            } catch (error) {
                console.error('Sistem temizleme hatası:', error);
                adminActions.showAdminNotification('Sistem temizleme sırasında hata oluştu.', 'error');
            }
        }
    }

    // Veri yedekleme oluştur
    async createBackup() {
        try {
            const response = await fetch(SITE_BASE_URL + 'admin/create_backup.php');
            const result = await response.json();

            if (result.success) {
                adminActions.showAdminNotification('Yedekleme başarıyla oluşturuldu!', 'success');

                // Yedek indirme linki göster
                if (result.download_url) {
                    setTimeout(() => {
                        const download = confirm('Yedek dosyasını indirmek ister misiniz?');
                        if (download) {
                            window.open(result.download_url, '_blank');
                        }
                    }, 1000);
                }
            } else {
                adminActions.showAdminNotification('Yedekleme oluşturulamadı: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Yedekleme hatası:', error);
            adminActions.showAdminNotification('Yedekleme sırasında hata oluştu.', 'error');
        }
    }

    // Sistem durumu göstergesi
    initSystemStatus() {
        this.updateSystemStatus();
        setInterval(() => this.updateSystemStatus(), 60000); // Her dakika güncelle
    }

    // Sistem durumunu güncelle
    async updateSystemStatus() {
        try {
            const response = await fetch(SITE_BASE_URL + 'admin/get_system_status.php');
            const result = await response.json();

            if (result.success) {
                this.updateSystemStatusDisplay(result.status);
            }
        } catch (error) {
            console.error('Sistem durumu yüklenirken hata:', error);
        }
    }

    // Sistem durumu ekranını güncelle
    updateSystemStatusDisplay(status) {
        const statusContainer = document.getElementById('system-status');
        if (!statusContainer) return;

        let html = `
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
        `;

        // Sistem metrikleri
        const metrics = [
            { label: 'CPU Kullanımı', value: status.cpu_usage, threshold: 80 },
            { label: 'Bellek Kullanımı', value: status.memory_usage, threshold: 85 },
            { label: 'Disk Kullanımı', value: status.disk_usage, threshold: 90 },
            { label: 'Aktif Bağlantı', value: status.active_connections, threshold: null }
        ];

        metrics.forEach(metric => {
            const isWarning = metric.threshold && metric.value > metric.threshold;
            const color = isWarning ? '#FF9800' : '#4CAF50';
            const icon = isWarning ? '⚠️' : '✅';

            html += `
                <div style="text-align: center; padding: 15px; background: var(--fixed-bg); border-radius: 8px; border-left: 4px solid ${color};">
                    <div style="font-size: 12px; opacity: 0.8;">${metric.label}</div>
                    <div style="font-size: 18px; font-weight: bold; color: ${color};">
                        ${icon} ${metric.value}${metric.threshold ? '%' : ''}
                    </div>
                </div>
            `;
        });

        html += `</div>`;
        statusContainer.innerHTML = html;
    }

    // Hızlı arama sistemi
    initQuickSearch() {
        const searchInput = document.getElementById('admin-quick-search');
        if (!searchInput) return;

        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.performQuickSearch(e.target.value);
            }, 500);
        });
    }

    // Hızlı arama yap
    async performQuickSearch(query) {
        if (query.length < 2) return;

        try {
            const response = await fetch(SITE_BASE_URL + `admin/quick_search.php?q=${encodeURIComponent(query)}`);
            const result = await response.json();

            this.displayQuickSearchResults(result);
        } catch (error) {
            console.error('Hızlı arama hatası:', error);
        }
    }

    // Hızlı arama sonuçlarını göster
    displayQuickSearchResults(results) {
        const resultsContainer = document.getElementById('quick-search-results');
        if (!resultsContainer) return;

        if (results.success && results.data.length > 0) {
            let html = '<div class="quick-search-results">';

            results.data.forEach(item => {
                html += `
                    <div class="search-result-item" onclick="adminDashboard.openSearchResult('${item.type}', ${item.id})">
                        <strong>${item.title}</strong>
                        <small>${item.type} - ${item.subtitle}</small>
                    </div>
                `;
            });

            html += '</div>';
            resultsContainer.innerHTML = html;
            resultsContainer.style.display = 'block';
        } else {
            resultsContainer.innerHTML = '<div class="search-result-item">Sonuç bulunamadı</div>';
            resultsContainer.style.display = 'block';
        }
    }

    // Arama sonucunu aç
    openSearchResult(type, id) {
        const urls = {
            'user': `../${id}/`,
            'drawing': `../drawing.php?id=${id}`,
            'comment': `../moderate_comment.php?id=${id}`
        };

        if (urls[type]) {
            window.open(urls[type], '_blank');
        }

        // Arama sonuçlarını temizle
        const resultsContainer = document.getElementById('quick-search-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }

    // Dashboard'u yenile
    refreshDashboard() {
        this.loadDashboardStats();
        adminActions.showAdminNotification('Dashboard yenilendi', 'info');
    }

    // Dashboard'u dışa aktar
    exportDashboard() {
        const dashboardData = {
            stats: this.stats,
            timestamp: new Date().toISOString(),
            exported_by: window.currentUser.username
        };

        const dataStr = JSON.stringify(dashboardData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);

        const link = document.createElement('a');
        link.href = url;
        link.download = `dashboard-export-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        adminActions.showAdminNotification('Dashboard verileri dışa aktarıldı', 'success');
    }
}

// Global admin dashboard instance'ı
const adminDashboard = new AdminDashboard();

// Sayfa yüklendiğinde admin dashboard'u başlat
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('/admin/')) {
        adminDashboard.init();
    }
});

// Eski fonksiyonlar için compatibility
function refreshDashboard() {
    adminDashboard.refreshDashboard();
}

function exportDashboard() {
    adminDashboard.exportDashboard();
}
