<?php
require_once '../common/game_functions.php';

if (!isset($_GET['game_id'])) {
    die('Oyun ID gerekli');
}

$gameId = $_GET['game_id'];
$gameData = GameCommon::getGameState($gameId);

if (!$gameData) {
    die('Oyun bulunamadı');
}

$currentUserId = $_SESSION['user_id'] ?? null;
$isPlayer = $currentUserId && ($currentUserId == $gameData['player1_id'] || $currentUserId == $gameData['player2_id']);
?>

<div id="tavla-game-container" class="game-container">
    <div class="game-info">
        <div class="player-info <?php echo $gameData['current_turn'] == $gameData['player1_id'] ? 'current-turn' : ''; ?>">
            <span class="online-indicator"></span>
            <strong><?php echo htmlspecialchars($gameData['player1_username']); ?></strong>
            <span>(Siyah)</span>
        </div>
        <div class="player-info <?php echo $gameData['current_turn'] == $gameData['player2_id'] ? 'current-turn' : ''; ?>">
            <span class="online-indicator"></span>
            <strong><?php echo htmlspecialchars($gameData['player2_username']); ?></strong>
            <span>(Beyaz)</span>
        </div>
    </div>

    <div class="tavla-board" id="tavla-board">
        <!-- Tavla tahtası JavaScript ile oluşturulacak -->
    </div>

    <div class="dice-container" id="tavla-dice">
        <div class="dice" id="dice1">0</div>
        <div class="dice" id="dice2">0</div>
    </div>

    <div class="game-controls">
        <?php if ($isPlayer && $gameData['current_turn'] == $currentUserId): ?>
            <button class="btn-primary" onclick="rollTavlaDice(<?php echo $gameId; ?>)">🎲 Zar At</button>
            <button class="btn-secondary" onclick="tavlaResign(<?php echo $gameId; ?>)">🏳️ Pes Et</button>
        <?php endif; ?>
        <button class="btn-danger" onclick="closeGameModal()">✖️ Oyunu Kapat</button>
    </div>

    <div class="game-messages">
        <h4>Oyun Sohbeti</h4>
        <div id="tavla-chat-messages"></div>
        <div style="display: flex; gap: 5px; margin-top: 10px;">
            <input type="text" id="tavla-chat-input" placeholder="Mesajınız..." style="flex: 1;">
            <button class="btn-primary" onclick="sendTavlaMessage(<?php echo $gameId; ?>)">Gönder</button>
        </div>
    </div>
</div>

<script>
// Tavla oyunu JavaScript kodu
function initTavlaBoard() {
    const board = document.getElementById('tavla-board');
    const gameState = <?php echo $gameData['game_state']; ?>;

    // Tavla tahtasını oluştur
    // Bu kısım daha karmaşık olduğu için basit bir gösterim yapıyoruz
    board.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 2px; background: #8b4513; padding: 10px; border-radius: 8px;">
            ${Array.from({length: 24}, (_, i) => `
                <div class="tavla-point" data-point="${i + 1}" style="height: 60px; background: #deb887; position: relative;">
                    <div class="point-number" style="position: absolute; bottom: 2px; right: 2px; font-size: 10px;">${i + 1}</div>
                </div>
            `).join('')}
        </div>
    `;

    updateTavlaBoard(gameState);
}

function updateTavlaBoard(gameState) {
    // Pulları güncelle
    Object.entries(gameState.board).forEach(([point, data]) => {
        const pointElement = document.querySelector(`.tavla-point[data-point="${point}"]`);
        if (pointElement) {
            pointElement.innerHTML = `
                <div class="point-number" style="position: absolute; bottom: 2px; right: 2px; font-size: 10px;">${point}</div>
                <div style="position: absolute; top: 5px; left: 5px; display: flex; flex-direction: column; gap: 2px;">
                    ${Array.from({length: data.count}, (_, i) => `
                        <div class="tavla-piece ${data.player}"
                             style="width: 25px; height: 15px; border-radius: 4px; border: 1px solid #000; background: ${data.player === 'black' ? '#000' : '#fff'};"></div>
                    `).join('')}
                </div>
            `;
        }
    });

    // Zarları güncelle
    document.getElementById('dice1').textContent = gameState.dice[0] || 0;
    document.getElementById('dice2').textContent = gameState.dice[1] || 0;
}

function rollTavlaDice(gameId) {
    fetch('https://flood.page.gd/games/tavla/tavla_roll.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            game_id: gameId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTavlaBoard(data.new_state);
            showNotification('🎲 Zarlar atıldı: ' + data.dice.join(', '), 'success');
        } else {
            showNotification('❌ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Zar atma hatası:', error);
        showNotification('❌ Zar atılırken hata oluştu', 'error');
    });
}

function makeTavlaMove(fromPoint, toPoint) {
    const gameId = <?php echo $gameId; ?>;

    fetch('https://flood.page.gd/games/tavla/tavla_move.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            game_id: gameId,
            from_point: fromPoint,
            to_point: toPoint
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTavlaBoard(data.new_state);
        } else {
            showNotification('❌ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Hamle hatası:', error);
        showNotification('❌ Hamle yapılırken hata oluştu', 'error');
    });
}

function tavlaResign(gameId) {
    if (confirm('Pes etmek istediğinizden emin misiniz?')) {
        showNotification('🏳️ Pes ettiniz', 'info');
    }
}

function sendTavlaMessage(gameId) {
    const input = document.getElementById('tavla-chat-input');
    const message = input.value.trim();

    if (message) {
        // WebSocket üzerinden mesaj gönder
        if (window.gameWebSocket) {
            window.gameWebSocket.send(JSON.stringify({
                type: 'game_message',
                gameId: gameId,
                message: message
            }));
            input.value = '';
        }
    }
}

// Sayfa yüklendiğinde tahtayı başlat
document.addEventListener('DOMContentLoaded', initTavlaBoard);
</script>
