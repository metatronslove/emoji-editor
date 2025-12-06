<!-- Giriş Modal -->
<div id="login_modal" class="modal">
    <div class="modal-content">
        <button class="modal-close">❎</button>
        <h2>Giriş Yap</h2>
        <form action="<?php echo $site_url; ?>auth/login_handler.php" method="POST" class="auth-form">
            <input type="text" name="username" placeholder="Kullanıcı Adı" required>
            <input type="password" name="password" placeholder="Şifre" required>
            <button type="submit">Giriş Yap</button>
        </form>
        <div class="divider"><span>YA DA</span></div>
        <a href="<?php echo $site_url; ?>auth/login.php" class="btn-google">
            <img src="<?php echo $site_url; ?>assets/img/google_logo.svg" alt="Google Logo" style="width: 20px; height: 20px; margin-right: 10px;">
            Google ile Giriş Yap
        </a>
        <div class="auth-links">
            <p>Hesabın yok mu? <a href="#" data-modal-switch="register_modal">Hemen kaydol.</a></p>
        </div>
    </div>
</div>

<!-- Kayıt Modal -->
<div id="register_modal" class="modal">
    <div class="modal-content">
        <button class="modal-close">❎</button>
        <h2>Yeni Kayıt</h2>
        <form action="<?php echo $site_url; ?>auth/register.php" method="POST" class="auth-form">
            <input type="text" name="username" placeholder="Kullanıcı Adı" required minlength="3" maxlength="20">
            <input type="email" name="email" placeholder="E-posta" required>
            <input type="password" name="password" placeholder="Şifre (Min 6 Karakter)" required minlength="6">
            <input type="password" name="password_confirm" placeholder="Şifre (Tekrar)" required>
            <button type="submit">Kayıt Ol</button>
        </form>
        <div class="divider"><span>YA DA</span></div>
        <a href="<?php echo $site_url; ?>auth/login.php" class="btn-google">
            <img src="<?php echo $site_url; ?>assets/img/google_logo.svg" alt="Google Logo" style="width: 20px; height: 20px; margin-right: 10px;">
            Google ile Kayıt Ol
        </a>
        <div class="auth-links">
            <p>Zaten hesabın var mı? <a href="#" data-modal-switch="login_modal">Giriş Yap.</a></p>
        </div>
    </div>
</div>

<!-- Onay Modal -->
<div id="confirm-modal" class="modal-overlay">
    <div class="modal-content">
        <h3 id="modal-title">Emin misiniz?</h3>
        <p id="modal-message">Bu işlem geri alınamaz.</p>
        <div class="modal-buttons">
            <button class="modal-btn confirm" id="modal-confirm">Evet</button>
            <button class="modal-btn cancel" id="modal-cancel">İptal</button>
        </div>
    </div>
</div>

<!-- Kılavuz Modal -->
<div id="guide-modal" class="modal-overlay">
    <div class="modal-content-guide">
        <h3>📖 YouTube Sohbet Kılavuzu</h3>
        <div style="background-color: var(--fixed-bg); padding: 10px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid var(--accent-color);">
            <strong>🎯 ÖNEMLİ:</strong> Uygulama, çiziminizin toplam maliyetinin **200 karakteri** aşmamasını otomatik olarak garantiler.
        </div>
        <ol style="margin-left: 20px; font-size: 0.95em;">
            <li style="margin-bottom: 8px;">**Kılavuz Adım 1 (İlk Satır Ayarı):** İlk satırda kaç adet emoji pikseli **çizebileceğinizi** belirleyin (Genellikle 5 veya 6'dır). Bu, nickname'inizin kapladığı alanı otomatik hesaplar. **(❌ ile işaretli hücreler çıktıya dahil edilmez.)**</li>
            <li style="margin-bottom: 8px;">**Kılavuz Adım 2 (Filtre Atlatma):** Çiziminizin YouTube sohbetinde görünmemesi durumunda, **Filtre Atlatma Yöntemi**'ni sırayla deneyin. Bu karakterler, çiziminizin toplam karakter sayısına eklenir.</li>
            <li style="margin-bottom: 8px;">**Kılavuz Adım 3 (Kopyalama):** Çiziminizi tamamladıktıktan sonra **Panoya Kopyala** butonuna basın. Çıktınızın 200 karakteri asla aşmadığından emin olabilirsiniz. **Kırpılan (✂️) pikseller çıktıya dahil edilmez.**</li>
        </ol>
        <button id="close-guide-btn">Anladım, Kapat</button>
    </div>
</div>

<!-- Oyun Challenge Modalı -->
<div id="game-challenge-modal" class="modal" style="display: none;">
<div class="modal-content" style="max-width: 500px;">
<button class="modal-close" onclick="closeGameChallengeModal()">❎</button>
<h3 id="game-challenge-title" style="margin-bottom: 20px;"></h3>
<div id="game-challenge-content">
<!-- İçerik dinamik olarak yüklenecek -->
</div>
</div>
</div>

<!-- Oyun Modalı -->
<div id="game-modal" class="modal" style="display: none;">
<div class="modal-content" style="max-width: 95%; max-height: 95%; width: 95%; height: 95%;">
<button class="modal-close" onclick="closeGameModal()">❎</button>
<div id="game-modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
<h3 id="game-modal-title"></h3>
<div id="game-players-info"></div>
</div>
<div id="game-modal-content" style="height: calc(100% - 120px); overflow: hidden;">
<!-- Oyun içeriği buraya yüklenecek -->
</div>
</div>
</div>

<!-- Basit Mesaj Modalı -->
<div id="simple-message-modal" class="modal" style="display: none;">
<div class="modal-content" style="max-width: 500px;">
<button class="modal-close" onclick="closeSimpleMessageModal()">❎</button>
<h3 style="margin-bottom: 20px; color: var(--accent-color);">
💬 <span id="simple-modal-username">Kullanıcı</span> - Mesaj Gönder
</h3>

<div id="simple-modal-file-info" style="display: none; margin-bottom: 10px; padding: 8px; background: var(--fixed-bg); border-radius: 6px; border: 1px solid var(--accent-color);">
<span style="font-weight: bold;">📎 Dosya seçildi:</span>
<span id="simple-modal-file-name" style="margin-left: 5px;"></span>
<button onclick="clearSimpleModalFile()" style="margin-left: 10px; background: #dc3545; color: white; border: none; border-radius: 3px; padding: 2px 6px; font-size: 12px; cursor: pointer;">✖</button>
</div>

<textarea id="simple-message-input"
placeholder="Mesajınızı yazın... (Resim, video veya ses de ekleyebilirsiniz)"
style="width: 100%; height: 120px; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--fixed-bg); color: var(--main-text); font-family: inherit; resize: vertical; margin-bottom: 15px; box-sizing: border-box; font-size: 16px;"></textarea>

<div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
<button onclick="document.getElementById('simple-modal-file-input').click()"
class="btn-secondary" style="flex: 1;">
📎 Dosya Ekle
</button>
</div>

<input type="file" id="simple-modal-file-input" style="display: none;"
accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.mp3,.mp4,.wav">

<div style="display: flex; gap: 10px;">
<button onclick="sendSimpleMessage()"
class="btn-primary" style="flex: 1;">
📤 Gönder
</button>
<button onclick="closeSimpleMessageModal()"
class="btn-danger">
İptal
</button>
</div>
</div>
</div>

<!-- Basit Medya Galerisi Modalı -->
<div id="simple-media-gallery-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <button class="modal-close">❎</button>
        <h3 style="margin-bottom: 15px;">🖼️ Medya Galerisi</h3>
        <div style="margin-bottom: 15px;">
            <button onclick="document.getElementById('simple-gallery-file-input').click()" class="btn-primary">📁 Yeni Medya Yükle</button>
            <input type="file" id="simple-gallery-file-input" style="display: none;" accept="image/*,video/*,audio/*">
        </div>
        <div id="simple-media-gallery-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; max-height: 300px; overflow-y: auto; padding: 10px; background: var(--fixed-bg); border-radius: 8px;"></div>
        <div style="margin-top: 15px; text-align: center;">
            <button onclick="closeSimpleMediaGallery()" class="btn-secondary">Kapat</button>
        </div>
    </div>
</div>

<!-- Medya Görüntüleyici Modalı -->
<div id="media-viewer-modal" class="modal" style="display: none;">
<div class="modal-content" style="max-width: 90vw; max-height: 90vh; background: transparent; box-shadow: none; border: none;">
<button class="modal-close" onclick="closeMediaViewer()"
style="position: fixed; top: 20px; right: 20px; z-index: 1001; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 20px;">
✖
</button>
<img id="media-viewer-image" src="" alt="Medya"
style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 8px;">
</div>
</div>

<!-- TEMA DEĞİŞTİRME BUTONU -->
<button class="theme-toggle-btn" onclick="toggleDarkMode()" title="Tema Değiştir">
    <span id="theme-icon">🌙</span>
</button>
<script src="<?php echo $baseSiteUrl; ?>assets/js/ui/modals.js"></script>

<!-- ENTEGRE EDİTÖR MODALI - HEM EMOJİ HEM FLOOD -->
<?php require_once __DIR__ . '/integrated_editor_modal.php'; ?>