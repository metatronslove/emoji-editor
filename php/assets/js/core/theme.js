// Tema yönetimi
/**
 * Sistemin tercih ettiği renk modunu algılar ve uygular
 */
function detectAndApplySystemTheme() {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (prefersDark) {
        document.body.classList.add('dark-mode');
        console.log('🌙 Sistem koyu mod tercihi algılandı, koyu tema uygulanıyor.');
    } else {
        document.body.classList.remove('dark-mode');
        console.log('☀️ Sistem aydınlık mod tercihi algılandı, aydınlık tema uygulanıyor.');
    }
}

/**
 * Manuel tema değiştirme
 */
function toggleDarkMode() {
    const isDark = document.body.classList.contains('dark-mode');
    const themeIcon = document.getElementById('theme-icon');

    if (isDark) {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
        if (themeIcon) themeIcon.textContent = '🌙';
        showNotification('☀️ Aydınlık moda geçildi', 'info');
    } else {
        document.body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        if (themeIcon) themeIcon.textContent = '☀️';
        showNotification('🌙 Koyu moda geçildi', 'info');
    }
}

/**
 * Kullanıcının önceki tercihini yükler
 */
function loadUserThemePreference() {
    const savedTheme = localStorage.getItem('theme');
    const themeIcon = document.getElementById('theme-icon');

    if (savedTheme) {
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            if (themeIcon) themeIcon.textContent = '☀️';
        } else {
            document.body.classList.remove('dark-mode');
            if (themeIcon) themeIcon.textContent = '🌙';
        }
        console.log(`🎨 Kullanıcı tema tercihi yüklendi: ${savedTheme}`);
        return true;
    }
    return false;
}

/**
 * Sistem tema tercihi değişikliklerini dinler
 */
function watchSystemThemeChanges() {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const themeIcon = document.getElementById('theme-icon');

    const handleThemeChange = (e) => {
        if (e.matches) {
            document.body.classList.add('dark-mode');
            if (themeIcon) themeIcon.textContent = '☀️';
            console.log('🌙 Sistem koyu moda geçti, tema güncelleniyor.');
        } else {
            document.body.classList.remove('dark-mode');
            if (themeIcon) themeIcon.textContent = '🌙';
            console.log('☀️ Sistem aydınlık moda geçti, tema güncelleniyor.');
        }

        localStorage.removeItem('theme');
    };

    mediaQuery.addEventListener('change', handleThemeChange);
}

/**
 * Tema ikonunu başlangıç durumuna ayarla
 */
function initializeThemeIcon() {
    const themeIcon = document.getElementById('theme-icon');
    if (!themeIcon) return;

    const isDark = document.body.classList.contains('dark-mode');
    themeIcon.textContent = isDark ? '☀️' : '🌙';
}

/**
 * Tema sistemini başlat
 */
function initThemeSystem() {
    const hasUserPreference = loadUserThemePreference();

    if (!hasUserPreference) {
        detectAndApplySystemTheme();
    }

    initializeThemeIcon();
    watchSystemThemeChanges();
    console.log('🎨 Tema sistemi başlatıldı');
}
