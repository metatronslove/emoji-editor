<?php
require_once '../common/game_functions.php';

class ChessGame {
    private $gameId;
    private $gameState;

    public function __construct($gameId) {
        $this->gameId = $gameId;
        $this->loadGameState();
    }

    public function loadGameState() {
        $gameData = GameCommon::getGameState($this->gameId);
        $this->gameState = json_decode($gameData['game_state'], true);
    }

    public function saveGameState() {
        $db = getDbConnection();
        $stmt = $db->prepare("UPDATE active_games SET game_state = ? WHERE id = ?");
        $stmt->execute([json_encode($this->gameState), $this->gameId]);
    }

    public function makeMove($from, $to, $playerId) {
        $fromRow = $from['row'];
        $fromCol = $from['col'];
        $toRow = $to['row'];
        $toCol = $to['col'];

        // Geçerlilik kontrolü
        if (!$this->isValidMove($fromRow, $fromCol, $toRow, $toCol, $playerId)) {
            return ['success' => false, 'message' => 'Geçersiz hamle'];
        }

        // Taşı hareket ettir
        $piece = $this->gameState['board'][$fromRow][$fromCol];
        $targetPiece = $this->gameState['board'][$toRow][$toCol];

        // Eğer rakip taşı yiyorsa, captured listesine ekle
        if ($targetPiece !== '') {
            $this->gameState['captured'][] = $targetPiece;
        }

        $this->gameState['board'][$toRow][$toCol] = $piece;
        $this->gameState['board'][$fromRow][$fromCol] = '';

        // Sırayı değiştir
        $this->gameState['current_player'] = $this->gameState['current_player'] === 'white' ? 'black' : 'white';
        $this->gameState['move_count']++;

        // Oyun durumunu kaydet
        $this->saveGameState();

        return ['success' => true, 'new_state' => $this->gameState];
    }

    private function isValidMove($fromRow, $fromCol, $toRow, $toCol, $playerId) {
        // Taşın oyuncuya ait olup olmadığını kontrol et
        $piece = $this->gameState['board'][$fromRow][$fromCol];
        $playerColor = $this->getPlayerColor($playerId);

        if (!$this->isPlayerPiece($piece, $playerColor)) {
            return false;
        }

        // Taşın kurallara uygun hareket edip etmediğini kontrol et
        return $this->isValidPieceMove($piece, $fromRow, $fromCol, $toRow, $toCol);
    }

    private function getPlayerColor($playerId) {
        $gameData = GameCommon::getGameState($this->gameId);
        return $playerId == $gameData['player1_id'] ? 'white' : 'black';
    }

    private function isPlayerPiece($piece, $playerColor) {
        if ($piece === '') return false;

        $whitePieces = ['♙', '♖', '♘', '♗', '♕', '♔'];
        $blackPieces = ['♟', '♜', '♞', '♝', '♛', '♚'];

        if ($playerColor === 'white') {
            return in_array($piece, $whitePieces);
        } else {
            return in_array($piece, $blackPieces);
        }
    }

    private function isValidPieceMove($piece, $fromRow, $fromCol, $toRow, $toCol) {
        // Basit hareket kuralları (tam kurallar için daha karmaşık mantık gerekir)
        $rowDiff = abs($toRow - $fromRow);
        $colDiff = abs($toCol - $fromCol);

        switch ($piece) {
            case '♙': // Beyaz piyon
                return $this->isValidWhitePawnMove($fromRow, $fromCol, $toRow, $toCol);
            case '♟': // Siyah piyon
                return $this->isValidBlackPawnMove($fromRow, $fromCol, $toRow, $toCol);
            case '♖': // Kale
            case '♜':
                return $rowDiff === 0 || $colDiff === 0;
            case '♘': // At
            case '♞':
                return ($rowDiff === 2 && $colDiff === 1) || ($rowDiff === 1 && $colDiff === 2);
            case '♗': // Fil
            case '♝':
                return $rowDiff === $colDiff;
            case '♕': // Vezir
            case '♛':
                return $rowDiff === 0 || $colDiff === 0 || $rowDiff === $colDiff;
            case '♔': // Şah
            case '♚':
                return $rowDiff <= 1 && $colDiff <= 1;
            default:
                return false;
        }
    }

    private function isValidWhitePawnMove($fromRow, $fromCol, $toRow, $toCol) {
        $rowDiff = $toRow - $fromRow;
        $colDiff = abs($toCol - $fromCol);

        // İleri hareket
        if ($colDiff === 0 && $rowDiff === -1) {
            return true;
        }

        // İlk hareket (2 kare)
        if ($fromRow === 6 && $colDiff === 0 && $rowDiff === -2) {
            return true;
        }

        // Çapraz yeme
        if ($colDiff === 1 && $rowDiff === -1) {
            $targetPiece = $this->gameState['board'][$toRow][$toCol];
            return $targetPiece !== '' && $this->isPlayerPiece($targetPiece, 'black');
        }

        return false;
    }

    private function isValidBlackPawnMove($fromRow, $fromCol, $toRow, $toCol) {
        $rowDiff = $toRow - $fromRow;
        $colDiff = abs($toCol - $fromCol);

        // İleri hareket
        if ($colDiff === 0 && $rowDiff === 1) {
            return true;
        }

        // İlk hareket (2 kare)
        if ($fromRow === 1 && $colDiff === 0 && $rowDiff === 2) {
            return true;
        }

        // Çapraz yeme
        if ($colDiff === 1 && $rowDiff === 1) {
            $targetPiece = $this->gameState['board'][$toRow][$toCol];
            return $targetPiece !== '' && $this->isPlayerPiece($targetPiece, 'white');
        }

        return false;
    }

    public function getGameState() {
        return $this->gameState;
    }

    public function checkGameEnd() {
        // Şah mat kontrolü (basit versiyon)
        $whiteKing = $this->findKing('white');
        $blackKing = $this->findKing('black');

        if (!$whiteKing) return ['winner' => 'black', 'reason' => 'Şah mat'];
        if (!$blackKing) return ['winner' => 'white', 'reason' => 'Şah mat'];

        return null;
    }

    private function findKing($color) {
        $king = $color === 'white' ? '♔' : '♚';

        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if ($this->gameState['board'][$row][$col] === $king) {
                    return ['row' => $row, 'col' => $col];
                }
            }
        }
        return null;
    }
}
?>
