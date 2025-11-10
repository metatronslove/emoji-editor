// admin/admin_actions.js

function sendAction(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        alert(result.message);
        if (result.success) {
            window.location.reload(); // İşlem başarılıysa tabloyu yenile
        }
    })
    .catch(error => {
        alert('Sunucu hatası: ' + error);
        console.error('AJAX Hatası:', error);
    });
}

// Kullanıcı Eylemleri
function moderateUser(userId, action) {
    if (confirm(`Kullanıcı ID ${userId} için '${action}' eylemini onaylıyor musunuz?`)) {
        sendAction('moderate_user.php', { user_id: userId, action: action });
    }
}

function showMuteModal(userId) {
    document.getElementById('mute-user-id').value = userId;
    document.getElementById('mute-modal').style.display = 'block';
}

function applyCommentMute() {
    const userId = document.getElementById('mute-user-id').value;
    const duration = document.getElementById('mute-duration').value;

    if (duration > 0) {
        sendAction('moderate_user.php', { user_id: userId, action: 'mute', duration: duration });
    } else {
        alert('Süre 1 günden büyük olmalıdır.');
    }
}

function setRole(userId, newRole) {
    if (confirm(`Kullanıcı ID ${userId}'nin rolünü '${newRole}' olarak değiştirmeyi onaylıyor musunuz? (Sadece Admin yetkilidir)`)) {
        sendAction('moderate_user.php', { user_id: userId, action: 'set_role', new_role: newRole });
    }
}

// İçerik Eylemleri (moderate_content.php için)
function moderateContent(contentId, contentType, action) {
    if (confirm(`Bu ${contentType} ID ${contentId}'i ${action === 'hide' ? 'gizlemeyi' : 'görünür yapmayı'} onaylıyor musunuz?`)) {
        sendAction('moderate_content.php', { content_id: contentId, content_type: contentType, action: action });
    }
}

/**
 * Moderasyon alanı için en son içeriği çeker ve tabloları oluşturur.
 */
async function fetchRecentContentForModeration() {
    const contentArea = document.getElementById('content-moderation-area');
    contentArea.innerHTML = '<p>Son içerikler yükleniyor...</p>';

    try {
        const response = await fetch('fetch_recent_content.php');
        const result = await response.json();

        if (result.success) {
            contentArea.innerHTML = '';

            // Çizim Tablosunu Oluştur
            contentArea.innerHTML += '<h3>Son Çizimler</h3>';
            contentArea.appendChild(createContentTable(result.drawings, 'drawing'));

            // Yorum Tablosunu Oluştur
            contentArea.innerHTML += '<h3>Son Yorumlar</h3>';
            contentArea.appendChild(createContentTable(result.comments, 'comment'));

        } else {
            contentArea.innerHTML = `<p style="color: red;">❌ İçerik yüklenemedi: ${result.message}</p>`;
        }
    } catch (error) {
        contentArea.innerHTML = '<p style="color: red;">❌ Sunucu ile iletişim hatası.</p>';
        console.error('İçerik yükleme hatası:', error);
    }
}

/**
 * İçerik verilerinden (çizim/yorum) HTML tablosu oluşturur.
 */
function createContentTable(data, type) {
    const table = document.createElement('table');

    // Başlık satırı
    const headerHtml = `
    <thead>
    <tr>
    <th>ID</th>
    <th>Yazar</th>
    <th>İçerik Önizleme</th>
    <th>Tip/Hedef</th>
    <th>Görünür?</th>
    <th>Tarih</th>
    <th>Eylemler</th>
    </tr>
    </thead>
    `;
    table.innerHTML = headerHtml;

    // İçerik satırları
    const tbody = document.createElement('tbody');
    data.forEach(item => {
        const row = tbody.insertRow();
        row.id = `${type}-row-${item.id}`;
        if (!item.is_visible) {
            row.style.backgroundColor = '#ffdddd'; // Gizli içeriği vurgula
        }

        row.insertCell().textContent = item.id;
        row.insertCell().innerHTML = `<a href="../${item.author_name}/">${item.author_name}</a>`;

        // İçerik önizleme
        const previewCell = row.insertCell();
        previewCell.textContent = item.content.substring(0, 50) + (item.content.length > 50 ? '...' : '');
        previewCell.style.fontSize = '8px';
        previewCell.style.maxWidth = '150px';

        // Tip/Hedef bilgisi
        const targetCell = row.insertCell();
        if (type === 'drawing') {
            targetCell.textContent = 'Çizim';
        } else {
            targetCell.innerHTML = `Yorum (<a href="../${item.target_type === 'profile' ? item.target_id : 'drawing'}/${item.target_id}">Hedef</a>)`;
        }

        row.insertCell().textContent = item.is_visible ? 'EVET' : 'HAYIR';
        row.insertCell().textContent = new Date(item.updated_at || item.created_at).toLocaleString();

        // Eylemler
        const actionCell = row.insertCell();
        const action = item.is_visible ? 'hide' : 'show';
        const buttonText = item.is_visible ? 'Gizle' : 'Geri Getir';
        const buttonClass = item.is_visible ? 'btn-danger' : 'btn-success';

        actionCell.innerHTML = `
        <button onclick="moderateContent(${item.id}, '${type}', '${action}')" class="${buttonClass} btn-sm">
        ${buttonText}
        </button>
        <a href="${type === 'drawing' ? `../drawing/${item.id}` : `../comment/${item.id}`}" target="_blank" class="btn-sm">Görüntüle</a>
        `;
    });

    table.appendChild(tbody);
    return table;
}

// document.addEventListener'ın güncellenmesi (admin/dashboard.php'nin en altında)
document.addEventListener('DOMContentLoaded', () => {
    // Tüm JS dosyaları yüklenmişse içerik moderasyonunu başlat
    fetchRecentContentForModeration();
});
