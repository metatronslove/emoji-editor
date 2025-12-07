<div id="flood-editor-container" style="flex-grow: 1; height: 100%;">
    <div style="display: flex; height: 100%; gap: 20px; padding: 20px;">
        <!-- Sol Panel: Set ve Mesaj Listesi -->
        <div style="flex: 1; display: flex; flex-direction: column; gap: 15px;">
            <div style="background: var(--card-bg); padding: 15px; border-radius: 8px;">
                <h4 style="margin-bottom: 10px;">📁 Flood Set'i</h4>
                <select id="flood-set-select" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                    <option value="">Set seçin...</option>
                </select>
                
                <div id="set-info" style="font-size: 0.9em; opacity: 0.8;">
                    Set seçin veya yeni oluşturun
                </div>
            </div>
            
            <div style="background: var(--card-bg); padding: 15px; border-radius: 8px; flex: 1;">
                <h4 style="margin-bottom: 10px;">📝 Mesajlar</h4>
                <div id="flood-messages-list" style="height: 300px; overflow-y: auto;">
                    <!-- Mesaj listesi buraya yüklenecek -->
                </div>
            </div>
        </div>
        
        <!-- Orta Panel: Mesaj Editörü -->
        <div style="flex: 2; display: flex; flex-direction: column; gap: 15px;">
            <!-- EMOJI PALETİ -->
            <div style="background: var(--card-bg); padding: 15px; border-radius: 8px;">
                <h4 style="margin-bottom: 10px;">😊 Emoji Paleti</h4>
                
                <!-- EMOJI TAB BUTONLARI -->
                <div id="flood-emoji-tabs" style="margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 5px;"></div>
                
                <!-- EMOJI GRID -->
                <div id="flood-emoji-container" 
                     style="display: grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); 
                            gap: 5px; max-height: 200px; overflow-y: auto; padding: 10px; 
                            background: var(--fixed-bg); border-radius: 8px;">
                    <!-- Emoji paleti buraya yüklenecek -->
                </div>
            </div>

            <!-- MESAJ EDITÖRÜ -->
            <div style="background: var(--card-bg); padding: 15px; border-radius: 8px; flex: 1;">
                <h4 style="margin-bottom: 10px;">✏️ Mesaj Editörü</h4>
                
                <textarea id="flood-message-input"
                    placeholder="Flood mesajınızı yazın..."
                    style="width: 100%; height: 150px; padding: 10px; margin-bottom: 10px; 
                           border: 1px solid var(--border-color); border-radius: 4px; 
                           resize: vertical; background: var(--fixed-bg); color: var(--main-text);"></textarea>
                
                <!-- AKSİYON BUTONLARI -->
                <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
                    <button id="save-flood-message-btn" class="btn-primary">💾 Kaydet</button>
                    <button id="copy-flood-message-btn" class="btn-success">📋 Kopyala</button>
                    <button id="clear-flood-editor-btn" class="btn-danger">🧹 Temizle</button>
                    <button id="insert-random-emoji-btn" class="btn-secondary">🎲 Rastgele Emoji</button>
                </div>
                
                <!-- KARAKTER İSTATİSTİKLERİ -->
                <div style="font-size: 0.9em; opacity: 0.7;">
                    Karakter: <span id="flood-char-count">0</span>/<span id="flood-max-chars">200</span> | 
                    Emoji: <span id="flood-emoji-count">0</span> | 
                    Toplam: <span id="flood-total-cost" style="color: var(--accent-color);">0</span>
                </div>
            </div>
        </div>
        
        <!-- Sağ Panel: Önizleme ve Ayarlar -->
        <div style="flex: 1; display: flex; flex-direction: column; gap: 15px;">
            <div style="background: var(--card-bg); padding: 15px; border-radius: 8px;">
                <h4 style="margin-bottom: 10px;">👁️ Önizleme</h4>
                <div id="flood-preview" 
                     style="height: 150px; padding: 10px; background: var(--fixed-bg); color: var(--main-text); 
                            border: 1px solid var(--border-color); border-radius: 4px; overflow-y: auto;">
                    Mesajınız burada görünecek...
                </div>
            </div>
            
            <!-- AYARLAR -->
            <div style="background: var(--card-bg); padding: 15px; border-radius: 8px;">
                <h4 style="margin-bottom: 10px;">⚙️ Ayarlar</h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="auto-copy">
                        Otomatik Kopyala
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="auto-save" checked>
                        Otomatik Kaydet
                    </label>
                    <div style="margin-top: 10px;">
                        <label style="display: block; margin-bottom: 5px; font-size: 0.9em;">Maks. Karakter:</label>
                        <input type="number" id="flood-max-chars-input" value="200" min="50" max="1000" 
                               style="width: 100%; padding: 6px; border-radius: 4px; border: 1px solid var(--border-color);">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>