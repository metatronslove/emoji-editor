<?php
require_once '../common/game_functions.php';

class TavlaGame {
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

    public function rollDice($playerId) {
        $playerColor = $this->getPlayerColor($playerId);

        // Sıra kontrolü
        if ($this->gameState['current_player'] !== $playerColor) {
            return ['success' => false, 'message' => 'Şu anda sıra sizde değil'];
        }

        // Zar at
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);

        $this->gameState['dice'] = [$dice1, $dice2];

        // Çift zar durumu
        if ($dice1 === $dice2) {
            $this->gameState['dice'] = [$dice1, $dice1, $dice1, $dice1];
        }

        $this->saveGameState();

        return ['success' => true, 'dice' => $this->gameState['dice'], 'new_state' => $this->gameState];
    }

    public function makeMove($playerId, $fromPoint, $toPoint) {
        $playerColor = $this->getPlayerColor($playerId);

        // Sıra kontrolü
        if ($this->gameState['current_player'] !== $playerColor) {
            return ['success' => false, 'message' => 'Şu anda sıra sizde değil'];
        }

        // Geçerlilik kontrolü
        if (!$this->isValidMove($playerColor, $fromPoint, $toPoint)) {
            return ['success' => false, 'message' => 'Geçersiz hamle'];
        }

        // Hamleyi yap
        $this->executeMove($playerColor, $fromPoint, $toPoint);

        // Kullanılan zarı kaldır
        $this->useDie(abs($toPoint - $fromPoint));

        // Tüm zarlar kullanıldı mı kontrol et
        if ($this->allDiceUsed()) {
            $this->gameState['current_player'] = $playerColor === 'black' ? 'white' : 'black';
            $this->gameState['dice'] = [0, 0];
        }

        $this->saveGameState();

        // Oyun sonu kontrolü
        $gameEnd = $this->checkGameEnd();
        if ($gameEnd) {
            return ['success' => true, 'new_state' => $this->gameState, 'game_end' => $gameEnd];
        }

        return ['success' => true, 'new_state' => $this->gameState];
    }

    private function isValidMove($playerColor, $fromPoint, $toPoint) {
        // Temel kontroller
        if ($fromPoint < 1 || $fromPoint > 24 || $toPoint < 1 || $toPoint > 24) {
            return false;
        }

        // Pulların sahipliği kontrolü
        if (!isset($this->gameState['board'][$fromPoint]) ||
            $this->gameState['board'][$fromPoint]['player'] !== $playerColor) {
            return false;
        }

        // Zar değeri kontrolü
        $moveDistance = abs($toPoint - $fromPoint);
        if (!in_array($moveDistance, $this->gameState['dice'])) {
            return false;
        }

        // Hedef nokta kontrolü
        if (isset($this->gameState['board'][$toPoint]) &&
            $this->gameState['board'][$toPoint]['player'] !== $playerColor &&
            $this->gameState['board'][$toPoint]['count'] > 1) {
            return false; // Rakibin 2+ pulu varsa yiyemez
        }

        return true;
    }

    private function executeMove($playerColor, $fromPoint, $toPoint) {
        // Pulu hareket ettir
        $this->gameState['board'][$fromPoint]['count']--;

        if ($this->gameState['board'][$fromPoint]['count'] === 0) {
            unset($this->gameState['board'][$fromPoint]);
        }

        // Hedef noktada rakip pul varsa yiyerek bara koy
        if (isset($this->gameState['board'][$toPoint]) &&
            $this->gameState['board'][$toPoint]['player'] !== $playerColor) {
            $this->gameState['bar'][$this->gameState['board'][$toPoint]['player']]++;
            unset($this->gameState['board'][$toPoint]);
        }

        // Pulu hedef noktaya yerleştir
        if (!isset($this->gameState['board'][$toPoint])) {
            $this->gameState['board'][$toPoint] = ['count' => 0, 'player' => $playerColor];
        }
        $this->gameState['board'][$toPoint]['count']++;
    }

    private function useDie($dieValue) {
        $key = array_search($dieValue, $this->gameState['dice']);
        if ($key !== false) {
            unset($this->gameState['dice'][$key]);
            $this->gameState['dice'] = array_values($this->gameState['dice']);
        }
    }

    private function allDiceUsed() {
        return empty($this->gameState['dice']) || (count($this->gameState['dice']) === 1 && $this->gameState['dice'][0] === 0);
    }

    private function getPlayerColor($playerId) {
        $gameData = GameCommon::getGameState($this->gameId);
        return $playerId == $gameData['player1_id'] ? 'black' : 'white';
    }

    public function checkGameEnd() {
        // Tüm pullar eve toplandı mı kontrol et
        $blackHome = 0;
        $whiteHome = 0;

        foreach ($this->gameState['board'] as $point => $data) {
            if ($data['player'] === 'black' && $point >= 19) {
                $blackHome += $data['count'];
            }
            if ($data['player'] === 'white' && $point <= 6) {
                $whiteHome += $data['count'];
            }
        }

        if ($blackHome === 15) {
            return ['winner' => 'black', 'reason' => 'Tüm pullar eve toplandı'];
        }

        if ($whiteHome === 15) {
            return ['winner' => 'white', 'reason' => 'Tüm pullar eve toplandı'];
        }

        return null;
    }

    public function getValidMoves($playerColor) {
        $validMoves = [];
        $dice = $this->gameState['dice'];

        if (empty($dice) || $dice[0] === 0) {
            return $validMoves;
        }

        // Bar'dan çıkış kontrolü
        if ($this->gameState['bar'][$playerColor] > 0) {
            foreach ($dice as $die) {
                $targetPoint = $playerColor === 'black' ? $die : 25 - $die;

                if (!isset($this->gameState['board'][$targetPoint]) ||
                    $this->gameState['board'][$targetPoint]['player'] === $playerColor ||
                    $this->gameState['board'][$targetPoint]['count'] === 1) {
                    $validMoves[] = ['from' => 'bar', 'to' => $targetPoint, 'die' => $die];
                }
            }
            return $validMoves;
        }

        // Normal hamleler
        foreach ($this->gameState['board'] as $point => $data) {
            if ($data['player'] === $playerColor) {
                foreach ($dice as $die) {
                    $direction = $playerColor === 'black' ? -1 : 1;
                    $targetPoint = $point + ($die * $direction);

                    if ($targetPoint >= 1 && $targetPoint <= 24) {
                        if (!isset($this->gameState['board'][$targetPoint]) ||
                            $this->gameState['board'][$targetPoint]['player'] === $playerColor ||
                            $this->gameState['board'][$targetPoint]['count'] === 1) {
                            $validMoves[] = ['from' => $point, 'to' => $targetPoint, 'die' => $die];
                        }
                    }
                }
            }
        }

        return $validMoves;
    }
}
?>
