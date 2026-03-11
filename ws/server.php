<?php
declare(strict_types=1);

/**
 * CanvasX WebSocket server — Ratchet
 * Run: php /var/www/canvasx/ws/server.php
 * Kept alive by supervisord (see supervisor/ws.conf)
 *
 * Install: composer require ratchet/ratchet
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - Backed enum WsMessageType (8.1)
 *  - readonly class ClientMeta (8.2)
 *  - #[\Override] attribute on interface methods (8.3)
 *  - json_validate() before json_decode (8.3)
 *  - Typed class constants (8.3)
 *  - match expression replacing switch
 *  - Named arguments on constructor
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// ── Message type enum (PHP 8.1 backed enum) ───────────────────────────────────
enum WsMessageType: string
{
    case Join   = 'join';
    case Cursor = 'cursor';
    case Input  = 'input';
    case Leave  = 'leave';
}

// ── Immutable client metadata (PHP 8.2 readonly class) ───────────────────────
readonly class ClientMeta
{
    public function __construct(
        public string $canvas_id   = '',
        public string $viewer_id   = '',
        public string $viewer_name = 'Viewer',
        public string $color       = '#a855f7',
    ) {}

    public function with(string $canvas_id, string $viewer_id, string $viewer_name, string $color): self
    {
        return new self(
            canvas_id:   $canvas_id,
            viewer_id:   $viewer_id,
            viewer_name: $viewer_name,
            color:       $color,
        );
    }
}

// ── WebSocket handler ─────────────────────────────────────────────────────────
class CanvasWsServer implements MessageComponentInterface
{
    /** Typed class constants — PHP 8.3 */
    private const string DEFAULT_COLOR = '#a855f7';
    private const int    MAX_NAME_LEN  = 32;
    private const int    MAX_SEL_LEN   = 256;
    private const int    MAX_VAL_LEN   = 4096;

    /** @var array<string, array<string, ConnectionInterface>> */
    private array $rooms = [];

    /** @var array<int, ClientMeta> */
    private array $clients = [];

    #[\Override]
    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients[$conn->resourceId] = new ClientMeta();
    }

    #[\Override]
    public function onMessage(ConnectionInterface $from, mixed $data): void
    {
        $raw = (string)$data;
        if (!json_validate($raw)) return;

        $msg = json_decode($raw, true);
        if (!is_array($msg)) return;

        $type = WsMessageType::tryFrom((string)($msg['type'] ?? ''));
        if ($type === null) return;

        $rid = $from->resourceId;

        match ($type) {
            WsMessageType::Join   => $this->handleJoin($rid, $from, $msg),
            WsMessageType::Cursor => $this->handleCursor($rid, $from, $msg),
            WsMessageType::Input  => $this->handleInput($rid, $from, $msg),
            WsMessageType::Leave  => $this->handleLeave($rid, $from),
        };
    }

    #[\Override]
    public function onClose(ConnectionInterface $conn): void
    {
        $this->handleLeave($conn->resourceId, $conn);
    }

    #[\Override]
    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    // ── Private handlers ──────────────────────────────────────────────────────
    private function handleJoin(int $rid, ConnectionInterface $conn, array $msg): void
    {
        $canvas_id   = preg_replace('/[^a-zA-Z0-9]/', '', (string)($msg['canvas_id'] ?? ''));
        $viewer_id   = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($msg['viewer_id'] ?? '')), 0, self::MAX_NAME_LEN);
        $viewer_name = substr(strip_tags((string)($msg['viewer_name'] ?? 'Viewer')), 0, self::MAX_NAME_LEN);
        $color       = preg_match('/^#[0-9a-fA-F]{3,6}$/', (string)($msg['color'] ?? ''))
                       ? $msg['color']
                       : self::DEFAULT_COLOR;

        if ($canvas_id === '' || $viewer_id === '') return;

        $this->clients[$rid]            = (new ClientMeta())->with($canvas_id, $viewer_id, $viewer_name, $color);
        $this->rooms[$canvas_id]        ??= [];
        $this->rooms[$canvas_id][$viewer_id] = $conn;

        $this->broadcastViewerCount($canvas_id);
    }

    private function handleCursor(int $rid, ConnectionInterface $conn, array $msg): void
    {
        $meta = $this->clients[$rid] ?? null;
        if ($meta === null || $meta->canvas_id === '') return;

        $this->broadcast($meta->canvas_id, $conn, json_encode([
            'type'        => 'cursor',
            'viewer_id'   => $meta->viewer_id,
            'viewer_name' => $meta->viewer_name,
            'color'       => $meta->color,
            'x'           => (float)($msg['x'] ?? 0),
            'y'           => (float)($msg['y'] ?? 0),
        ]));
    }

    private function handleInput(int $rid, ConnectionInterface $conn, array $msg): void
    {
        $meta = $this->clients[$rid] ?? null;
        if ($meta === null || $meta->canvas_id === '') return;

        $this->broadcast($meta->canvas_id, $conn, json_encode([
            'type'      => 'input',
            'viewer_id' => $meta->viewer_id,
            'selector'  => substr(strip_tags((string)($msg['selector'] ?? '')), 0, self::MAX_SEL_LEN),
            'value'     => substr((string)($msg['value'] ?? ''), 0, self::MAX_VAL_LEN),
        ]));
    }

    private function handleLeave(int $rid, ConnectionInterface $conn): void
    {
        $meta = $this->clients[$rid] ?? null;

        if ($meta !== null && $meta->canvas_id !== '' && $meta->viewer_id !== '') {
            unset($this->rooms[$meta->canvas_id][$meta->viewer_id]);

            if (empty($this->rooms[$meta->canvas_id])) {
                unset($this->rooms[$meta->canvas_id]);
            } else {
                $this->broadcastViewerCount($meta->canvas_id);
                $this->broadcast($meta->canvas_id, $conn, json_encode([
                    'type'      => 'leave',
                    'viewer_id' => $meta->viewer_id,
                ]));
            }
        }

        unset($this->clients[$rid]);
    }

    private function broadcast(string $canvas_id, ConnectionInterface $except, string $msg): void
    {
        foreach ($this->rooms[$canvas_id] ?? [] as $conn) {
            if ($conn !== $except) {
                $conn->send($msg);
            }
        }
    }

    private function broadcastViewerCount(string $canvas_id): void
    {
        $msg = json_encode(['type' => 'viewers', 'count' => count($this->rooms[$canvas_id] ?? [])]);
        foreach ($this->rooms[$canvas_id] ?? [] as $conn) {
            $conn->send($msg);
        }
    }
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$server = IoServer::factory(
    new HttpServer(new WsServer(new CanvasWsServer())),
    8080,
);

echo "CanvasX WebSocket server running on port 8080\n";
$server->run();
