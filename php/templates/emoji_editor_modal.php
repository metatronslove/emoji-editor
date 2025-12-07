<div id="main-layout" style="display: inline-flex; flex-grow: 1;">
<div id="left-panel" style="display: inline-flex; align-items: flex-start;">
<div class="card" id="palette">
<strong>Fırça Rengi Seçin:</strong>

<div id="selected-emoji-display">
<span style="font-weight: normal;">Seçili Emoji:</span>
<span id="current-brush-emoji">🖤</span>
<span id="current-brush-name"> (black heart)</span>
</div>

<div id="category-tabs">
</div>

<div id="emoji-container">
<div id="color-options-container">
</div>
</div>
</div>
</div>

<div id="right-panel" style="display: inline-flex;">
<div class="card" id="controls-panel">
<div id="main-controls" style="margin-bottom: 15px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
<label for="firstRowLength" style="color: var(--accent-color);">İlk Satır Çizim Piksel Sayısı (0-Matris Genişliği):</label>
<input type="number" id="firstRowLength" value="6" min="0" max="11" style="width: 70px; padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); background-color: var(--fixed-bg); color: var(--main-text);">
<button id="updateMatrixButton" class="btn-success">Matrisi Güncelle</button>
<button id="showGuideButton" class="btn-primary">Kılavuz</button>
</div>

<div style="margin-bottom: 15px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
<label for="separator-select" style="color: var(--accent-color); white-space: nowrap;">Filtre Atlatma Yöntemi (Hücre Arası):</label>
<select id="separator-select">
<option value="none" selected>Hiçbiri</option>
<option value="ZWNJ">ZWNJ (Zero Width Non-Joiner)</option>
<option value="ZWSP">ZWSP (Zero Width Space)</option>
<option value="ZWJ">ZWJ (Zero Width Joiner)</option>
<option value="WJ">WJ (Word Joiner)</option>
<option value="SHY">SHY (Soft Hyphen)</option>
<option value="HAIR">Hair Space</option>
<option value="LRM">LRM (Yön Kontrol)</option>
<option value="RLM">RLM (Yön Kontrol)</option>
<option value="ZWNBSP">ZWNBSP (Zero Width No-Break Space)</option>
<option value="LRE">LRE (Bidi L-R-Embedding)</option>
<option value="RLE">RLE (Bidi R-L-Embedding)</option>
<option value="PDF">PDF (Bidi Pop Directional)</option>
<option value="LRI">LRI (Bidi L-R-Isolate)</option>
<option value="RLI">RLI (Bidi R-L-Isolate)</option>
<option value="PDI">PDI (Bidi Pop Isolate)</option>
<option value="CGJ">CGJ (Combining Grapheme Joiner)</option>
<option value="SP_BS">DENEYSEL (Space + Backspace)</option>
</select>
</div>

<div style="margin-bottom: 15px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
<label for="line-break-select" style="color: var(--accent-color); white-space: nowrap;">Satır Sonu (Line Break) Denemesi:</label>
<select id="line-break-select">
<option value="none" selected>Yok</option>
<option value="LF">LF (\n)</option>
<option value="CRLF">CRLF (\r\n)</option>
<option value="NEL">NEL (\u0085)</option>
<option value="LS">Line Separator (U+2028)</option>
<option value="PS">Paragraph Separator (U+2029)</option>
</select>
</div>
<!-- Matris Genişliği kontrolü -->
<div style="margin-bottom: 15px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
    <label for="matrixWidth" style="color: var(--accent-color);">Matris Genişliği (1-20):</label>
    <input type="number" id="matrixWidth" value="10" min="1" max="20" 
           style="width: 70px; padding: 8px; border-radius: 4px; 
                  border: 1px solid var(--border-color); 
                  background-color: var(--fixed-bg); color: var(--main-text);">
    <div style="font-size: 0.8em; opacity: 0.7; margin-top: 5px;">
        Değişiklikler anında uygulanır • İlk satır piksel sayısı otomatik güncellenir
    </div>
</div>

<!-- Maksimum Karakter Limiti -->
<div style="margin-bottom: 15px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
    <label for="maxCharsInput" style="color: var(--accent-color);">Maksimum Karakter Limiti (50-1000):</label>
    <input type="number" id="maxCharsInput" value="200" min="50" max="1000" 
           style="width: 100px; padding: 8px; border-radius: 4px; 
                  border: 1px solid var(--border-color); 
                  background-color: var(--fixed-bg); color: var(--main-text);">
    <span style="font-size: 0.9em; opacity: 0.8;">(YouTube sohbet limiti)</span>
    <div style="font-size: 0.8em; opacity: 0.7; margin-top: 5px;">
        Limit değişince otomatik kırpma uygulanır • Fazla hücreler ✂️ ile işaretlenir
    </div>
</div>

<div id="auxiliary-controls" style="flex-direction: column; gap: 8px; width: 100%;">
<button id="copyButton" class="btn-primary" style="width: 100%;">Panoya Kopyala</button>
<button id="importButton" class="btn-primary" style="width: 100%;">Panodan İçe Aktar</button>

<div style="display: flex; gap: 8px; width: 100%;">
<button id="saveButton" class="btn-warning" style="flex-grow: 1;">💾 Kaydet (Dosya/Site Kaydı)</button>
<input type="file" id="fileInput" accept=".txt" style="display: none;">
<button id="loadButton" class="btn-warning" style="flex-grow: 1;">Dosya Aç</button>
</div>
<button id="clearButton" class="btn-danger" style="width: 100%;">Temizle</button>
</div>
</div>
</div>
<div id="flex-content" style="max-width: min-content;">
<div id="info-panel" style="max-width: 100%;">
<span class="char-count">Toplam Çıktı Karakteri (Emoji + Ayırıcı): <span id="currentChars">0</span>/200</span>
<span id="charWarning" class="warning" style="display: none;"> - ⚠️ Ekstra karakter maliyeti!</span>
</div>

<div id="matrix-container" style="max-width: min-content;">
<table id="matrix">
</table>
</div>
</div>
</div>
<script>
// Emoji editor entegrasyonu için
document.addEventListener('DOMContentLoaded', function() {
    // Entegre editor'den ayarları al
    function loadIntegratedSettings() {
        try {
            const saved = localStorage.getItem('integratedEditorSettings');
            if (saved) {
                const settings = JSON.parse(saved);
                
                // Genişlik ayarını uygula
                const widthInput = document.getElementById('matrixWidth');
                if (widthInput) {
                    widthInput.value = settings.defaultWidth || 10;
                }
                
                // Karakter limitini uygula
                if (typeof MAX_CHARACTERS !== 'undefined') {
                    MAX_CHARACTERS = settings.maxChars || 200;
                }
                
                // Ayırıcıyı uygula
                const separatorSelect = document.getElementById('separator-select');
                if (separatorSelect && settings.separator) {
                    separatorSelect.value = settings.separator;
                }
                
                console.log('✅ Emoji editor ayarları entegre sistemden yüklendi');
            }
        } catch (error) {
            console.error('Entegre ayarlar yüklenemedi:', error);
        }
    }
    
    // Sayfa yüklendiğinde entegre ayarları yükle
    setTimeout(loadIntegratedSettings, 500);
    
    // Genişlik input'u için event listener
    const widthInput = document.getElementById('matrixWidth');
    if (widthInput) {
        widthInput.addEventListener('change', function() {
            // Entegre sistemdeki genişlik ayarını güncelle
            try {
                const saved = localStorage.getItem('integratedEditorSettings');
                if (saved) {
                    const settings = JSON.parse(saved);
                    settings.defaultWidth = parseInt(this.value) || 10;
                    localStorage.setItem('integratedEditorSettings', JSON.stringify(settings));
                }
            } catch (error) {
                console.error('Genişlik ayarı güncellenemedi:', error);
            }
            
            // Matrisi yeniden oluştur
            if (typeof createMatrix === 'function') {
                createMatrix();
            }
        });
    }
	
	    // Matris genişliği slider'ı için real-time feedback
    const matrixWidthSlider = document.getElementById('matrixWidth');
    const matrixWidthValue = document.getElementById('matrixWidthValue');
    
    if (matrixWidthSlider) {
        matrixWidthSlider.addEventListener('input', function() {
            if (matrixWidthValue) {
                matrixWidthValue.textContent = this.value;
            }
        });
        
        matrixWidthSlider.addEventListener('change', function() {
            // Matris genişliği değişti, matrisi yeniden oluştur
            if (typeof createMatrix === 'function') {
                createMatrix();
            }
        });
    }
    
    // Karakter limiti slider'ı
    const maxCharsSlider = document.getElementById('maxCharsInput');
    const maxCharsValue = document.getElementById('maxCharsValue');
    
    if (maxCharsSlider) {
        maxCharsSlider.addEventListener('input', function() {
            if (maxCharsValue) {
                maxCharsValue.textContent = this.value;
            }
        });
        
        maxCharsSlider.addEventListener('change', function() {
            // Karakter limiti değişti, uygula
            if (typeof updateCharacterCount === 'function') {
                window.MAX_CHARACTERS = parseInt(this.value) || 200;
                updateCharacterCount();
            }
        });
    }
    
    // İlk satır piksel sayısı için max değeri dinamik güncelle
    const firstRowLengthInput = document.getElementById('firstRowLength');
    const matrixWidthInput = document.getElementById('matrixWidth');
    
    if (firstRowLengthInput && matrixWidthInput) {
        matrixWidthInput.addEventListener('input', function() {
            const newMax = parseInt(this.value) || 10;
            firstRowLengthInput.setAttribute('max', newMax);
            
            // Mevcut değer yeni max'tan büyükse, azalt
            if (parseInt(firstRowLengthInput.value) > newMax) {
                firstRowLengthInput.value = newMax;
            }
        });
    }
});
</script>