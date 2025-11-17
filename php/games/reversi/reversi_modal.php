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

<div id="reversi-game-container" class="game-container">
    <div class="game-info">
        <div class="player-info <?php echo $gameData['current_turn'] == $gameData['player1_id'] ? 'current-turn' : ''; ?>">
            <span class="online-indicator"></span>
            <strong><?php echo htmlspecialchars($gameData['player1_username']); ?></strong>
            <span>(Siyah - ⚫)</span>
            <span id="black-score"><?php echo json_decode($gameData['game_state'], true)['scores']['black']; ?></span>
        </div>
        <div class="player-info <?php echo $gameData['current_turn'] == $gameData['player2_id'] ? 'current-turn' : ''; ?>">
            <span class="online-indicator"></span>
            <strong><?php echo htmlspecialchars($gameData['player2_username']); ?></strong>
            <span>(Beyaz - ⚪)</span>
            <span id="white-score"><?php echo json_decode($gameData['game_state'], true)['scores']['white']; ?></span>
        </div>
    </div>

    <div class="reversi-board game-board" id="reversi-board">
        <?php
        $gameState = json_decode($gameData['game_state'], true);
        $board = $gameState['board'];
        $validMoves = [];

        if ($isPlayer) {
            $playerColor = $currentUserId == $gameData['player1_id'] ? 'black' : 'white';
            $reversiGame = new ReversiGame($gameId);
            $validMoves = $reversiGame->getValidMoves($playerColor);
        }

        for ($row = 0; $row < 8; $row++):
            for ($col = 0; $col < 8; $col++):
                $piece = $board[$row][$col];
                $isValidMove = false;

                foreach ($validMoves as $move) {
                    if ($move['row'] == $row && $move['col'] == $col) {
                        $isValidMove = true;
                        break;
                    }
                }
        ?>
            <div class="reversi-cell <?php echo $isValidMove ? 'valid-move' : ''; ?>"
                 data-row="<?php echo $row; ?>" data-col="<?php echo $col; ?>">
                <?php if ($piece): ?>
                    <div class="reversi-piece <?php echo $piece === '⚫' ? 'black' : 'white'; ?>">
                        <?php echo $piece; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; endfor; ?>
    </div>

    <div class="game-controls">
        <?php if ($isPlayer && $gameData['current_turn'] == $currentUserId): ?>
            <button class="btn-primary" onclick="reversiPassTurn(<?php echo $gameId; ?>)">⏭️ Pas Geç</button>
        <?php endif; ?>
        <button class="btn-danger" onclick="closeGameModal()">✖️ Oyunu Kapat</button>
    </div>

    <div class="game-messages">
        <h4>Oyun Sohbeti</h4>
        <div id="reversi-chat-messages"></div>
        <div style="display: flex; gap: 5px; margin-top: 10px;">
            <input type="text" id="reversi-chat-input" placeholder="Mesajınız..." style="flex: 1;">
            <button class="btn-primary" onclick="sendReversiMessage(<?php echo $gameId; ?>)">Gönder</button>
        </div>
    </div>
</div>

<script>
// Reversi oyunu JavaScript kodu
document.querySelectorAll('#reversi-board .reversi-cell.valid-move').forEach(cell => {
    cell.addEventListener('click', function() {
        const row = parseInt(this.dataset.row);
        const col = parseInt(this.dataset.col);
        makeReversiMove(row, col);
    });
});

function makeReversiMove(row, col) {
    const gameId = <?php echo $gameId; ?>;

    fetch('https://flood.page.gd/games/reversi/reversi_move.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            game_id: gameId,
            row: row,
            col: col
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateReversiBoard(data.new_state);
        } else {
            showNotification('❌ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Hamle hatası:', error);
        showNotification('❌ Hamle yapılırken hata oluştu', 'error');
    });
}

function updateReversiBoard(gameState) {
    // Tahtayı güncelle
    document.querySelectorAll('#reversi-board .reversi-cell').forEach(cell => {
        const row = parseInt(cell.dataset.row);
        const col = parseInt(cell.dataset.col);
        const piece = gameState.board[row][col];

        cell.innerHTML = '';
        cell.classList.remove('valid-move');

        if (piece) {
            const pieceDiv = document.createElement('div');
            pieceDiv.className = `reversi-piece ${piece === '⚫' ? 'black' : 'white'}`;
            pieceDiv.textContent = piece;
            cell.appendChild(pieceDiv);
        }
    });

    // Skorları güncelle
    document.getElementById('black-score').textContent = gameState.scores.black;
    document.getElementById('white-score').textContent = gameState.scores.white;
}

function reversiPassTurn(gameId) {
    // Pas geçme işlemi
    showNotification('⏭️ Sıranızı pas geçtiniz', 'info');
}

function sendReversiMessage(gameId) {
    const input = document.getElementById('reversi-chat-input');
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
</script>
