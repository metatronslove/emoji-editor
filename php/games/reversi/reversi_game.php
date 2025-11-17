<?php
require_once '../common/game_functions.php';

class ReversiGame {
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

    public function makeMove($row, $col, $playerId) {
        $playerColor = $this->getPlayerColor($playerId);

        // Geçerlilik kontrolü
        if (!$this->isValidMove($row, $col, $playerColor)) {
            return ['success' => false, 'message' => 'Geçersiz hamle'];
        }

        // Taşı yerleştir
        $this->gameState['board'][$row][$col] = $playerColor === 'black' ? '⚫' : '⚪';

        // Rakip taşları çevir
        $this->flipPieces($row, $col, $playerColor);

        // Skoru güncelle
        $this->updateScores();

        // Sırayı değiştir
        $this->gameState['current_player'] = $playerColor === 'black' ? 'white' : 'black';

        // Oyun durumunu kaydet
        $this->saveGameState();

        return ['success' => true, 'new_state' => $this->gameState];
    }

    private function isValidMove($row, $col, $playerColor) {
        // Hücre boş olmalı
        if ($this->gameState['board'][$row][$col] !== '') {
            return false;
        }

        // En az bir rakip taşı çevirmeli
        $directions = [
            [-1, -1], [-1, 0], [-1, 1],
            [0, -1],           [0, 1],
            [1, -1],  [1, 0],  [1, 1]
        ];

        foreach ($directions as $direction) {
            if ($this->canFlipInDirection($row, $col, $direction[0], $direction[1], $playerColor)) {
                return true;
            }
        }

        return false;
    }

    private function canFlipInDirection($row, $col, $dRow, $dCol, $playerColor) {
        $opponentColor = $playerColor === 'black' ? 'white' : 'black';
        $currentRow = $row + $dRow;
        $currentCol = $col + $dCol;
        $hasOpponentPiece = false;

        // Rakip taşları kontrol et
        while ($currentRow >= 0 && $currentRow < 8 && $currentCol >= 0 && $currentCol < 8) {
            $piece = $this->gameState['board'][$currentRow][$currentCol];

            if ($piece === '') {
                return false;
            }

            if ($piece === ($opponentColor === 'black' ? '⚫' : '⚪')) {
                $hasOpponentPiece = true;
                $currentRow += $dRow;
                $currentCol += $dCol;
                continue;
            }

            if ($piece === ($playerColor === 'black' ? '⚫' : '⚪') && $hasOpponentPiece) {
                return true;
            }

            break;
        }

        return false;
    }

    private function flipPieces($row, $col, $playerColor) {
        $directions = [
            [-1, -1], [-1, 0], [-1, 1],
            [0, -1],           [0, 1],
            [1, -1],  [1, 0],  [1, 1]
        ];

        foreach ($directions as $direction) {
            $this->flipInDirection($row, $col, $direction[0], $direction[1], $playerColor);
        }
    }

    private function flipInDirection($row, $col, $dRow, $dCol, $playerColor) {
        if (!$this->canFlipInDirection($row, $col, $dRow, $dCol, $playerColor)) {
            return;
        }

        $opponentColor = $playerColor === 'black' ? 'white' : 'black';
        $currentRow = $row + $dRow;
        $currentCol = $col + $dCol;

        while ($currentRow >= 0 && $currentRow < 8 && $currentCol >= 0 && $currentCol < 8) {
            $piece = $this->gameState['board'][$currentRow][$currentCol];

            if ($piece === ($opponentColor === 'black' ? '⚫' : '⚪')) {
                $this->gameState['board'][$currentRow][$currentCol] = $playerColor === 'black' ? '⚫' : '⚪';
                $currentRow += $dRow;
                $currentCol += $dCol;
            } else {
                break;
            }
        }
    }

    private function updateScores() {
        $blackCount = 0;
        $whiteCount = 0;

        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $piece = $this->gameState['board'][$row][$col];
                if ($piece === '⚫') $blackCount++;
                if ($piece === '⚪') $whiteCount++;
            }
        }

        $this->gameState['scores']['black'] = $blackCount;
        $this->gameState['scores']['white'] = $whiteCount;
    }

    private function getPlayerColor($playerId) {
        $gameData = GameCommon::getGameState($this->gameId);
        return $playerId == $gameData['player1_id'] ? 'black' : 'white';
    }

    public function checkGameEnd() {
        // Tüm hücreler dolu mu kontrol et
        $isBoardFull = true;
        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if ($this->gameState['board'][$row][$col] === '') {
                    $isBoardFull = false;
                    break 2;
                }
            }
        }

        if ($isBoardFull) {
            $blackScore = $this->gameState['scores']['black'];
            $whiteScore = $this->gameState['scores']['white'];

            if ($blackScore > $whiteScore) {
                return ['winner' => 'black', 'reason' => 'Taş sayısı üstünlüğü'];
            } elseif ($whiteScore > $blackScore) {
                return ['winner' => 'white', 'reason' => 'Taş sayısı üstünlüğü'];
            } else {
                return ['winner' => 'draw', 'reason' => 'Berabere'];
            }
        }

        return null;
    }

    public function getValidMoves($playerColor) {
        $validMoves = [];

        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if ($this->isValidMove($row, $col, $playerColor)) {
                    $validMoves[] = ['row' => $row, 'col' => $col];
                }
            }
        }

        return $validMoves;
    }
}
?>
