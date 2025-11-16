<?php
require_once 'game_functions.php';

class GameWebSocketServer {
    private $clients = [];
    private $userConnections = [];

    public function __construct($host = '0.0.0.0', $port = 8080) {
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $host, $port);
        socket_listen($this->server);

        echo "🎮 Oyun WebSocket sunucusu {$host}:{$port} adresinde başlatıldı\n";
    }

    public function run() {
        while (true) {
            $read = array_merge([$this->server], $this->clients);
            $write = $except = null;

            if (socket_select($read, $write, $except, 0) > 0) {
                // Yeni bağlantı
                if (in_array($this->server, $read)) {
                    $this->acceptNewConnection();
                    unset($read[array_search($this->server, $read)]);
                }

                // Gelen mesajları işle
                foreach ($read as $client) {
                    $this->handleClientMessage($client);
                }
            }

            // Çevrimdışı kullanıcıları kontrol et (her 30 saniyede bir)
            static $lastCheck = 0;
            if (time() - $lastCheck > 30) {
                GameCommon::checkOfflineUsers();
                $lastCheck = time();
            }

            usleep(100000); // 100ms bekle
        }
    }

    private function acceptNewConnection() {
        $client = socket_accept($this->server);
        socket_set_nonblock($client);
        $this->clients[] = $client;

        echo "Yeni bağlantı kabul edildi. Toplam bağlantı: " . count($this->clients) . "\n";
    }

    private function handleClientMessage($client) {
        $data = @socket_read($client, 1024, PHP_NORMAL_READ);

        if ($data === false || $data === '') {
            // Bağlantı kapandı
            $this->handleDisconnect($client);
            return;
        }

        $message = json_decode(trim($data), true);
        if ($message) {
            $this->processMessage($client, $message);
        }
    }

    private function processMessage($client, $message) {
        switch ($message['type']) {
            case 'register':
                $this->registerUser($client, $message['userId']);
                break;
            case 'challenge':
                $this->sendChallenge($message);
                break;
            case 'accept_challenge':
                $this->acceptChallenge($message);
                break;
            case 'game_move':
                $this->broadcastGameMove($message);
                break;
            case 'game_message':
                $this->broadcastGameMessage($message);
                break;
        }
    }

    private function registerUser($client, $userId) {
        $this->userConnections[$userId] = $client;
        GameCommon::updateUserOnlineStatus($userId);

        echo "Kullanıcı {$userId} WebSocket'e kaydedildi\n";

        // Kullanıcıya bekleyen davetleri gönder
        $this->sendPendingChallenges($userId);
    }

    private function sendChallenge($message) {
        $targetUserId = $message['targetUserId'];

        if (isset($this->userConnections[$targetUserId])) {
            $targetClient = $this->userConnections[$targetUserId];
            $this->sendToClient($targetClient, [
                'type' => 'challenge_received',
                'challengeId' => $message['challengeId'],
                'challengerId' => $message['challengerId'],
                'challengerUsername' => $message['challengerUsername'],
                'gameType' => $message['gameType']
            ]);
        }
    }

    private function broadcastGameMove($message) {
        $gameState = GameCommon::getGameState($message['gameId']);

        // Her iki oyuncuya da hamleyi gönder
        $players = [$gameState['player1_id'], $gameState['player2_id']];

        foreach ($players as $playerId) {
            if (isset($this->userConnections[$playerId])) {
                $this->sendToClient($this->userConnections[$playerId], [
                    'type' => 'game_move',
                    'gameId' => $message['gameId'],
                    'move' => $message['move'],
                    'newState' => $gameState
                ]);
            }
        }
    }

    private function sendToClient($client, $data) {
        $jsonData = json_encode($data) . "\n";
        socket_write($client, $jsonData, strlen($jsonData));
    }

    private function handleDisconnect($client) {
        $userId = array_search($client, $this->userConnections);
        if ($userId) {
            unset($this->userConnections[$userId]);
        }

        $key = array_search($client, $this->clients);
        if ($key !== false) {
            unset($this->clients[$key]);
            socket_close($client);
        }

        echo "Bağlantı kapandı. Kalan bağlantı: " . count($this->clients) . "\n";
    }
}

// Sunucuyu başlat (eğer doğrudan çalıştırılıyorsa)
if (php_sapi_name() === 'cli') {
    $server = new GameWebSocketServer('0.0.0.0', 8080);
    $server->run();
}
?>
