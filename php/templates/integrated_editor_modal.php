<!-- Bu dosyayı templates/ klasörüne oluşturun -->
<div id="integrated-editor-modal" class="modal" style="display: none; background: transparent;">
    <div class="modal-content" style="background: var(--fixed-bg); color: var(--main-text); width: 100%; max-width: calc(100vw - 40px); height: 100%; max-height: calc(100vh - 40px);">
        
        
<!-- Tab Butonları -->
<div style="display: flex; border-bottom: 2px solid var(--border-color);">	
	<button class="modal-close" onclick="closeIntegratedEditor()"
			style="flex: none; padding: 15px; background: transparent; color: white; border: none;">
	❎
	</button>
    <button data-tab="emoji" class="tab-btn active" 
            style="flex: 1; padding: 15px; background: var(--accent-color); color: white; border: none;">
        🎨 Emoji Çizim
    </button>
    <button data-tab="flood" class="tab-btn" 
            style="flex: 1; padding: 15px; background: var(--fixed-bg); color: var(--main-text); border: none;">
        🌊 Flood Mesaj
    </button>
</div>        
        <!-- Emoji Editör Tab'ı -->
        <div id="emoji-tab" class="editor-tab-content" style="display: block; height: 100%; background: transparent;">
            <?php include __DIR__ . '/emoji_editor_modal.php'; ?>
        </div>
        
        <!-- Flood Editör Tab'ı -->
        <div id="flood-tab" class="editor-tab-content" style="display: none; height: 100%; background: transparent;">
		    <div id="flood-editor-container" style="width: 100%; height: 100%;">
            <div style="display: flex; height: 100%; gap: 20px; padding: 20px;">
                <!-- Sol Panel: Set ve Mesaj Listesi -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 15px;">
                    <div style="background: var(--card-bg); padding: 15px; border-radius: 8px;">
                        <h4 style="margin-bottom: 10px;">📁 Flood Set'i</h4>
                        <select id="flood-set-select-advanced" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                            <option value="">Set seçin...</option>
                        </select>
                        
                        <div id="set-info" style="font-size: 0.9em; opacity: 0.8;">
                            <!-- Set bilgileri buraya gelecek -->
                        </div>
                    </div>
                    
                    <div style="background: var(--card-bg); padding: 15px; border-radius: 8px; flex: 1;">
                        <h4 style="margin-bottom: 10px;">📝 Mesajlar</h4>
                        <div id="flood-messages-list" style="height: 300px; overflow-y: auto;">
                            <!-- Mesaj listesi buraya gelecek -->
                        </div>
                    </div>
                </div>
				
                
                <!-- Orta Panel: Mesaj Editörü -->
                <div style="flex: 2; display: flex; flex-direction: column; gap: 15px;">
	<div style="background: var(--card-bg); padding: 15px; border-radius: 8px;">
    <h4 style="margin-bottom: 10px;">😊 Emoji Paleti</h4>
    
    <!-- EMOJI TAB BUTONLARI -->
    <div id="flood-emoji-tabs" style="margin-bottom: 10px;"></div>
    
    <!-- EMOJI GRID CONTAINER (ID'yi flood-emoji-container olarak değiştirin) -->
    <div id="flood-emoji-container" 
         style="display: grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); 
                gap: 5px; max-height: 200px; overflow-y: auto; padding: 10px; 
                background: var(--fixed-bg); border-radius: 8px;">
        <!-- Emoji paleti buraya yüklenecek -->
    </div>
	</div>

                    <div style="background: var(--card-bg); padding: 15px; border-radius: 8px; flex: 1;">
                        <h4 style="margin-bottom: 10px;">✏️ Mesaj Editörü</h4>
						<textarea id="flood-message-input"
							placeholder="Flood mesajınızı yazın..."
							style="width: 100%; height: 150px; padding: 10px; margin-bottom: 10px; 
								border: 1px solid var(--border-color); border-radius: 4px; 
								resize: vertical;"></textarea>
                        
                        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                            <button id="save-flood-message-btn" class="btn-primary">💾 Kaydet</button>
                            <button id="update-flood-message-btn" class="btn-success" style="display: none;">✅ Güncelle</button>
                            <button id="cancel-edit-btn" class="btn-secondary" style="display: none;">❌ İptal</button>
                            <button onclick="window.floodSystem.cancelEdit()" class="btn-danger">🧹 Temizle</button>
                        </div>
                        
                        <div style="font-size: 0.9em; opacity: 0.7;">
                            Karakter sayısı: <span id="char-count">0</span>/<span id="max-chars">200</span>
                        </div>
                    </div>
                    
                    <div style="background: var(--card-bg); padding: 15px; border-radius: 8px;">
                        <h4 style="margin-bottom: 10px;">😊 Emoji Paleti</h4>
                        <div id="flood-emoji-palette">
                            <!-- Emoji paleti buraya yüklenecek -->
                        </div>
                    </div>
                </div>
                
                <!-- Sağ Panel: Önizleme ve Ayarlar -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 15px;">
                    <div style="background: var(--card-bg); padding: 15px; border-radius: 8px;">
                        <h4 style="margin-bottom: 10px;">👁️ Önizleme</h4>
                        <div id="flood-preview" 
                             style="height: 150px; padding: 10px; background: white; color: black; 
                                    border: 1px solid #ccc; border-radius: 4px; overflow-y: auto;">
                            Mesajınız burada görünecek...
                        </div>
                    </div>
                    
                    <div style="background: var(--card-bg); padding: 15px; border-radius: 8px;">
                        <h4 style="margin-bottom: 10px;">⚙️ Ayarlar</h4>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <label>
                                <input type="checkbox" id="auto-copy" checked>
                                Otomatik Kopyala
                            </label>
                            <label>
                                <input type="checkbox" id="auto-save" checked>
                                Otomatik Kaydet
                            </label>
                            <label>
                                <input type="checkbox" id="dark-mode">
                                Koyu Tema
                            </label>
                        </div>
                    </div>
                </div>
            </div>
		    </div>
        </div>
    </div>
</div>

<script>
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
        
        // Ayarları kaydet
        if (window.integratedEditor && window.integratedEditor.saveSettings) {
            window.integratedEditor.saveSettings();
        }
    }
}
</script>