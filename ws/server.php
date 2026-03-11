<?php
/**
 * canvas.new WebSocket server — Ratchet
 * Run: php /var/www/canvas/ws/server.php
 * Kept alive by supervisord (see supervisor/ws.conf)
 *
 * Install: composer require ratchet/ratchet
 * (only allowed Composer dependency per project rules)
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class CanvasWsServer implements MessageComponentInterface {

    /** @var array<string, array<string, ConnectionInterface>> canvas_id -> viewer_id -> connection */
    private array $rooms = [];

    /** @var array<int, array{canvas_id:string, viewer_id:string, viewer_name:string, color:string}> */
    private array $clients = [];

    public function onOpen(ConnectionInterface $conn): void {
        // Will be populated on first 'join' message
        $this->clients[$conn->resourceId] = [
            'canvas_id'   => '',
            'viewer_id'   => '',
            'viewer_name' => 'Viewer',
            'color'       => '#a855f7',
        ];
    }

    public function onMessage(ConnectionInterface $from, $data): void {
        $msg = json_decode($data, true);
        if (!is_array($msg) || !isset($msg['type'])) return;

        $rid = $from->resourceId;

        switch ($msg['type']) {

            case 'join':
                $canvas_id   = preg_replace('/[^a-zA-Z0-9]/', '', $msg['canvas_id'] ?? '');
                $viewer_id   = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $msg['viewer_id'] ?? ''), 0, 32);
                $viewer_name = substr(strip_tags($msg['viewer_name'] ?? 'Viewer'), 0, 32);
                $color       = preg_match('/^#[0-9a-fA-F]{3,6}$/', $msg['color'] ?? '') ? $msg['color'] : '#a855f7';

                if (!$canvas_id || !$viewer_id) break;

                $this->clients[$rid] = compact('canvas_id', 'viewer_id', 'viewer_name', 'color');

                if (!isset($this->rooms[$canvas_id])) $this->rooms[$canvas_id] = [];
                $this->rooms[$canvas_id][$viewer_id] = $from;

                $this->broadcastViewerCount($canvas_id);
                break;

            case 'cursor':
                $canvas_id = $this->clients[$rid]['canvas_id'] ?? '';
                if (!$canvas_id) break;
                $this->broadcast($canvas_id, $from, json_encode([
                    'type'        => 'cursor',
                    'viewer_id'   => $this->clients[$rid]['viewer_id'],
                    'viewer_name' => $this->clients[$rid]['viewer_name'],
                    'color'       => $this->clients[$rid]['color'],
                    'x'           => (float)($msg['x'] ?? 0),
                    'y'           => (float)($msg['y'] ?? 0),
                ]));
                break;

            case 'input':
                $canvas_id = $this->clients[$rid]['canvas_id'] ?? '';
                if (!$canvas_id) break;
                $selector = substr(strip_tags($msg['selector'] ?? ''), 0, 256);
                $value    = substr($msg['value'] ?? '', 0, 4096);
                $this->broadcast($canvas_id, $from, json_encode([
                    'type'      => 'input',
                    'viewer_id' => $this->clients[$rid]['viewer_id'],
                    'selector'  => $selector,
                    'value'     => $value,
                ]));
                break;

            case 'leave':
                $this->handleLeave($rid, $from);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn): void {
        $this->handleLeave($conn->resourceId, $conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    private function handleLeave(int $rid, ConnectionInterface $conn): void {
        $meta      = $this->clients[$rid] ?? null;
        $canvas_id = $meta['canvas_id'] ?? '';
        $viewer_id = $meta['viewer_id'] ?? '';

        if ($canvas_id && $viewer_id) {
            unset($this->rooms[$canvas_id][$viewer_id]);
            if (empty($this->rooms[$canvas_id])) {
                unset($this->rooms[$canvas_id]);
            } else {
                $this->broadcastViewerCount($canvas_id);
                $this->broadcast($canvas_id, $conn, json_encode([
                    'type'      => 'leave',
                    'viewer_id' => $viewer_id,
                ]));
            }
        }

        unset($this->clients[$rid]);
    }

    private function broadcast(string $canvas_id, ConnectionInterface $except, string $msg): void {
        foreach ($this->rooms[$canvas_id] ?? [] as $conn) {
            if ($conn !== $except) {
                $conn->send($msg);
            }
        }
    }

    private function broadcastViewerCount(string $canvas_id): void {
        $count = count($this->rooms[$canvas_id] ?? []);
        $msg   = json_encode(['type' => 'viewers', 'count' => $count]);
        foreach ($this->rooms[$canvas_id] ?? [] as $conn) {
            $conn->send($msg);
        }
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new CanvasWsServer()
        )
    ),
    8080
);

echo "canvas.new WebSocket server running on port 8080\n";
$server->run();
