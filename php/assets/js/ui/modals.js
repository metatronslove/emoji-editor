// Modal yönetimi
/**
 * Modal sistemini başlat
 */
function initModalSystem() {
    // Modal açma
    document.addEventListener('click', (e) => {
        const target = e.target;

        // Modal aç butonları
        if (target.matches('[data-modal-toggle]')) {
            e.preventDefault();
            const modalId = target.getAttribute('data-modal-toggle');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }

        // Modal kapatma
        if (target.matches('.modal-close') || target.matches('.modal')) {
            e.preventDefault();
            const modal = target.closest('.modal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }

        // Modal geçiş bağlantıları
        if (target.matches('[data-modal-switch]')) {
            e.preventDefault();
            const currentModal = target.closest('.modal');
            const targetModalId = target.getAttribute('data-modal-switch');

            if (currentModal) {
                currentModal.classList.remove('show');
            }

            setTimeout(() => {
                const targetModal = document.getElementById(targetModalId);
                if (targetModal) {
                    targetModal.classList.add('show');
                }
            }, 300);
        }
    });

    // ESC tuşu ile kapatma
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                openModal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
    });
}

/**
 * Kimlik doğrulama formlarını başlat
 */
function initAuthForms() {
    document.addEventListener('submit', async (e) => {
        if (e.target.matches('.auth-form')) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;

            // Butonu devre dışı bırak
            submitButton.disabled = true;
            submitButton.textContent = 'İşleniyor...';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });

                let result;
                try {
                    result = await response.json();
                } catch (jsonError) {
                    throw new Error('Sunucu yanıtı işlenemedi.');
                }

                if (result.success) {
                    showNotification(result.message, 'success');
                    // Modalı kapat
                    const modal = form.closest('.modal');
                    if (modal) {
                        modal.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                    // Sayfayı yenile
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(result.message || 'Bir hata oluştu', 'error');
                }
            } catch (error) {
                console.error('Form gönderim hatası:', error);
                showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
            } finally {
                // Butonu tekrar etkinleştir
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        }
    });
}

/**
 * Google kimlik doğrulama bağlantılarını başlat
 */
function initGoogleAuthLinks() {
    document.addEventListener('click', (e) => {
        if (e.target.matches('.btn-google') || e.target.closest('.btn-google')) {
            e.preventDefault();
            const link = e.target.matches('.btn-google') ? e.target : e.target.closest('.btn-google');
            const currentModal = link.closest('.modal')?.id;

            if (currentModal) {
                const googleUrl = SITE_BASE_URL + `auth/login.php?source=${currentModal}`;
                window.location.href = googleUrl;
            }
        }
    });
}

/**
 * URL'den hata ve başarı mesajlarını oku ve göster
 */
function handleUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const success = urlParams.get('success');
    const hash = window.location.hash;

    if (error) {
        showNotification(decodeURIComponent(error), 'error', 8000);

        // Hash'te belirtilen modalı aç
        if (hash && hash.includes('_modal')) {
            const modalId = hash.split('?')[0].replace('#', '');
            if (modalId) {
                setTimeout(() => {
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.classList.add('show');
                        document.body.style.overflow = 'hidden';
                    }
                }, 1000);
            }
        }
    }

    if (success) {
        showNotification(decodeURIComponent(success), 'success', 5000);
    }

    // URL'yi temizle
    if (error || success) {
        const cleanUrl = window.location.pathname + (hash ? hash.split('?')[0] : '');
        window.history.replaceState({}, document.title, cleanUrl);
    }
}

// Modal yönetim sistemi
class ModalSystem {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Modal kapatma butonları
        document.querySelectorAll('.modal-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', (e) => {
                this.closeModal(e.target.closest('.modal'));
            });
        });

        // ESC tuşu ile kapatma
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });

        // Dışarı tıklayınca kapatma
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target);
            }
        });

        // Modal değiştirme butonları
        document.querySelectorAll('[data-modal-switch]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const targetModal = btn.getAttribute('data-modal-switch');
                this.switchModal(targetModal);
            });
        });

        // Modal açma butonları
        document.querySelectorAll('[data-modal-toggle]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const targetModal = btn.getAttribute('data-modal-toggle');
                this.openModal(targetModal);
            });
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            this.closeModal(modal);
        });
    }

    switchModal(targetModalId) {
        this.closeAllModals();
        setTimeout(() => {
            this.openModal(targetModalId);
        }, 300);
    }
}

// Onay modalı
class ConfirmModal {
    static show(title, message) {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirm-modal');
            const titleEl = document.getElementById('modal-title');
            const messageEl = document.getElementById('modal-message');
            const confirmBtn = document.getElementById('modal-confirm');
            const cancelBtn = document.getElementById('modal-cancel');

            titleEl.textContent = title;
            messageEl.innerHTML = message;

            const modalSystem = new ModalSystem();
            modalSystem.openModal('confirm-modal');

            const cleanup = () => {
                confirmBtn.removeEventListener('click', onConfirm);
                cancelBtn.removeEventListener('click', onCancel);
                modalSystem.closeModal(modal);
            };

            const onConfirm = () => {
                cleanup();
                resolve(true);
            };

            const onCancel = () => {
                cleanup();
                resolve(false);
            };

            confirmBtn.addEventListener('click', onConfirm);
            cancelBtn.addEventListener('click', onCancel);
        });
    }
}

// Global modal instance'ı
const modalSystem = new ModalSystem();

// Eski fonksiyonlar için compatibility
function showConfirm(title, message) {
    return ConfirmModal.show(title, message);
}
