<h2 id="main-title">KALP EMOJİ PİKSEL SANATI EDİTÖRÜ V.6.5 (Sezgisel Giriş Düzeltmesi)</h2>

<div id="main-layout">
<div id="left-panel">
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

<div id="right-panel">
<div class="card" id="controls-panel">
<div id="main-controls" style="margin-bottom: 15px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
<label for="firstRowLength" style="color: var(--accent-color);">İlk Satır Çizim Piksel Sayısı (0-11):</label>
<input type="number" id="firstRowLength" value="6" min="0" max="11" style="width: 70px; padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); background-color: var(--fixed-bg); color: var(--main-text);">
<button id="updateMatrixButton" class="btn-success">Matrisi Güncelle</button>
<button id="showGuideButton" class="btn-primary">Kılavuz</button>
</div>

<div style="margin-bottom: 15px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
<label for="separator-select" style="color: var(--accent-color); white-space: nowrap;">Filtre Atlatma Yöntemi:</label>
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

<div id="info-panel">
<span class="char-count">Toplam Çıktı Karakteri (Emoji + Ayırıcı): <span id="currentChars">0</span>/200</span>
<span id="charWarning" class="warning" style="display: none;"> - ⚠️ Ekstra karakter maliyeti!</span>
</div>

<div id="matrix-container" style="max-width: 100%;">
<table id="matrix">
</table>
</div>
</div>
</div>
