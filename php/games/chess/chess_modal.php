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

<div id="chess-game-container" class="game-container">
    <div class="game-info">
        <div class="player-info <?php echo $gameData['current_turn'] == $gameData['player1_id'] ? 'current-turn' : ''; ?>">
            <span class="online-indicator"></span>
            <strong><?php echo htmlspecialchars($gameData['player1_username']); ?></strong>
            <span>(Beyaz)</span>
        </div>
        <div class="player-info <?php echo $gameData['current_turn'] == $gameData['player2_id'] ? 'current-turn' : ''; ?>">
            <span class="online-indicator"></span>
            <strong><?php echo htmlspecialchars($gameData['player2_username']); ?></strong>
            <span>(Siyah)</span>
        </div>
    </div>

    <div class="chess-board game-board" id="chess-board">
        <?php
        $gameState = json_decode($gameData['game_state'], true);
        $board = $gameState['board'];

        for ($row = 0; $row < 8; $row++):
            for ($col = 0; $col < 8; $col++):
                $piece = $board[$row][$col];
                $cellClass = ($row + $col) % 2 === 0 ? 'chess-cell white' : 'chess-cell black';
        ?>
            <div class="<?php echo $cellClass; ?>" data-row="<?php echo $row; ?>" data-col="<?php echo $col; ?>">
                <?php if ($piece): ?>
                    <span class="chess-piece"><?php echo $piece; ?></span>
                <?php endif; ?>

                <?php if ($col === 0): ?>
                    <span class="chess-coordinates coord-rank"><?php echo 8 - $row; ?></span>
                <?php endif; ?>

                <?php if ($row === 7): ?>
                    <span class="chess-coordinates coord-file"><?php echo chr(97 + $col); ?></span>
                <?php endif; ?>
            </div>
        <?php endfor; endfor; ?>
    </div>

    <div class="game-controls">
        <?php if ($isPlayer && $gameData['current_turn'] == $currentUserId): ?>
            <button class="btn-primary" onclick="chessResign(<?php echo $gameId; ?>)">🥲 Pes Et</button>
            <button class="btn-secondary" onclick="chessOfferDraw(<?php echo $gameId; ?>)">🤝 Beraberlik Teklifi</button>
        <?php endif; ?>
        <button class="btn-danger" onclick="closeGameModal()">✖️ Oyunu Kapat</button>
    </div>

    <div class="move-history">
        <h4>Hamle Geçmişi</h4>
        <div id="chess-move-history">
            <!-- Hamleler JavaScript ile eklenecek -->
        </div>
    </div>

    <div class="game-messages">
        <h4>Oyun Sohbeti</h4>
        <div id="chess-chat-messages"></div>
        <div style="display: flex; gap: 5px; margin-top: 10px;">
            <input type="text" id="chess-chat-input" placeholder="Mesajınız..." style="flex: 1;">
            <button class="btn-primary" onclick="sendChessMessage(<?php echo $gameId; ?>)">Gönder</button>
        </div>
    </div>
</div>

<script>
// Satranç oyunu JavaScript kodu
let selectedChessPiece = null;

document.querySelectorAll('#chess-board .chess-cell').forEach(cell => {
    cell.addEventListener('click', function() {
        const row = parseInt(this.dataset.row);
        const col = parseInt(this.dataset.col);

        if (selectedChessPiece) {
            // Hamle yap
            makeChessMove(selectedChessPiece.row, selectedChessPiece.col, row, col);
            selectedChessPiece = null;
            document.querySelectorAll('.chess-cell').forEach(c => c.classList.remove('selected'));
        } else if (this.querySelector('.chess-piece')) {
            // Taş seç
            selectedChessPiece = { row, col };
            this.classList.add('selected');
        }
    });
});

function makeChessMove(fromRow, fromCol, toRow, toCol) {
    const gameId = <?php echo $gameId; ?>;

    fetch('https://flood.page.gd/games/chess/chess_move.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            game_id: gameId,
            from: { row: fromRow, col: fromCol },
            to: { row: toRow, col: toCol }
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateChessBoard(data.new_state.board);
        } else {
            showNotification('❌ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Hamle hatası:', error);
        showNotification('❌ Hamle yapılırken hata oluştu', 'error');
    });
}

function updateChessBoard(board) {
    document.querySelectorAll('#chess-board .chess-cell').forEach(cell => {
        const row = parseInt(cell.dataset.row);
        const col = parseInt(cell.dataset.col);
        const piece = board[row][col];

        const pieceElement = cell.querySelector('.chess-piece');
        if (pieceElement) {
            pieceElement.remove();
        }

        if (piece) {
            const newPiece = document.createElement('span');
            newPiece.className = 'chess-piece';
            newPiece.textContent = piece;
            cell.appendChild(newPiece);
        }
    });
}

function chessResign(gameId) {
    if (confirm('Pes etmek istediğinizden emin misiniz?')) {
        // Pes etme işlemi
        showNotification('🏳️ Pes ettiniz', 'info');
    }
}

function chessOfferDraw(gameId) {
    // Beraberlik teklifi
    showNotification('🤝 Beraberlik teklifi gönderildi', 'info');
}

function sendChessMessage(gameId) {
    const input = document.getElementById('chess-chat-input');
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
