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
            openModal(modalId);
        }

        // Modal kapatma
        if (target.matches('.modal-close') || target.matches('.modal')) {
            e.preventDefault();
            const modal = target.closest('.modal');
            if (modal) {
                closeModal(modal);
            }
        }

        // Modal geçiş bağlantıları
        if (target.matches('[data-modal-switch]')) {
            e.preventDefault();
            const currentModal = target.closest('.modal');
            const targetModalId = target.getAttribute('data-modal-switch');

            if (currentModal) {
                closeModal(currentModal);
            }

            setTimeout(() => {
                openModal(targetModalId);
            }, 300);
        }
    });

    // ESC tuşu ile kapatma
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // Pencere boyutu değiştiğinde modaları güncelle
    window.addEventListener('resize', function() {
        updateAllModals();
    });
}

/**
 * Modal aç
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        // Responsive ayarları uygula
        applyResponsiveModalSettings(modalId);

        // Mesaj modalı ise yükseklikleri ayarla
        if (modalId === 'messages-modal') {
            adjustMessageModalLayout();
        }
    }
}

/**
 * Modal kapat
 */
function closeModal(modal) {
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

/**
 * Tüm modaları kapat
 */
function closeAllModals() {
    document.querySelectorAll('.modal.show').forEach(modal => {
        closeModal(modal);
    });
}

/**
 * Responsive modal ayarlarını uygula
 */
function applyResponsiveModalSettings(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    const modalContent = modal.querySelector('.modal-content, .modal-content-guide');
    if (!modalContent) return;

    const isMobile = window.innerWidth <= 768;

    if (isMobile) {
        // Mobil için inline stiller
        modalContent.style.width = '100vw';
        modalContent.style.height = '100vh';
        modalContent.style.maxWidth = '100vw';
        modalContent.style.maxHeight = '100vh';
        modalContent.style.borderRadius = '0';
        modalContent.style.padding = '20px';
        modalContent.style.position = 'fixed';
        modalContent.style.top = '0';
        modalContent.style.left = '0';
        modalContent.style.transform = 'none';
        modalContent.style.margin = '0';
    } else {
        // Masaüstü için inline stilleri temizle (CSS'e bırak)
        modalContent.style.width = '';
        modalContent.style.height = '';
        modalContent.style.maxWidth = '';
        modalContent.style.maxHeight = '';
        modalContent.style.borderRadius = '';
        modalContent.style.padding = '';
        modalContent.style.position = '';
        modalContent.style.top = '';
        modalContent.style.left = '';
        modalContent.style.transform = '';
        modalContent.style.margin = '';
    }
}

/**
 * Mesaj modalı layout ayarları
 */
function adjustMessageModalLayout() {
    const modalContent = document.querySelector('#messages-modal .modal-content');
    const container = modalContent?.querySelector('div > div');
    const messagesArea = document.getElementById('conversation-messages');

    if (!modalContent || !container) return;

    const isMobile = window.innerWidth <= 768;

    if (isMobile) {
        container.style.height = 'calc(100vh - 120px)';
        if (messagesArea) {
            messagesArea.style.height = 'calc(100% - 140px)';
        }
    } else {
        container.style.height = '600px';
        if (messagesArea) {
            messagesArea.style.height = '';
        }
    }
}

/**
 * Tüm açık modaları güncelle
 */
function updateAllModals() {
    document.querySelectorAll('.modal.show').forEach(modal => {
        applyResponsiveModalSettings(modal.id);
        if (modal.id === 'messages-modal') {
            adjustMessageModalLayout();
        }
    });
}

/**
 * Mesaj modalını aç
 */
function openMessagesModal() {
    if (!window.currentUser || !window.currentUser.id) {
        showNotification('Mesajları görüntülemek için giriş yapmalısınız.', 'error');
        return;
    }

    openModal('messages-modal');
    loadConversations();
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
                        applyResponsiveModalSettings(modalId);
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
        openModal(modalId);
    }

    closeModal(modal) {
        closeModal(modal);
    }

    closeAllModals() {
        closeAllModals();
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

// Bu script modal açıldığında çalışır
function initIntegratedEditor() {
    if (window.floodSystem) {
        // Karakter sayacını başlat
        const textarea = document.getElementById('flood-message-input');
        if (textarea) {
            textarea.addEventListener('input', function() {
                const charCount = this.value.length;
                const maxChars = parseInt(document.getElementById('shared-max-chars').value) || 200;
                
                const charCountElement = document.getElementById('char-count');
                const maxCharsElement = document.getElementById('max-chars');
                
                if (charCountElement) charCountElement.textContent = charCount;
                if (maxCharsElement) maxCharsElement.textContent = maxChars;
                
                // Önizlemeyi güncelle
                const preview = document.getElementById('flood-preview');
                if (preview) {
                    preview.textContent = this.value || 'Mesajınız burada görünecek...';
                    
                    // Limit kontrolü
                    if (charCount > maxChars) {
                        preview.style.borderColor = '#dc3545';
                    } else if (charCount > maxChars * 0.9) {
                        preview.style.borderColor = '#ffc107';
                    } else {
                        preview.style.borderColor = '#28a745';
                    }
                }
            });
        }
        
        // Buton event'lerini bağla
        const saveBtn = document.getElementById('save-flood-message-btn');
        if (saveBtn && window.floodSystem.saveFloodMessage) {
            saveBtn.onclick = () => window.floodSystem.saveFloodMessage();
        }
    }
}

// Modal kapatma fonksiyonu
function closeIntegratedEditor() {
    const modal = document.getElementById('integrated-editor-modal');
    if (modal) {
        modal.style.display = 'none';
		document.body.style.overflow = "auto";
        
        // Ayarları kaydet
        if (window.integratedEditor && window.integratedEditor.saveSettings) {
            window.integratedEditor.saveSettings();
        }
    }
}
