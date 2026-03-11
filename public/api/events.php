<?php
declare(strict_types=1);

/**
 * GET /api/events?id=aB3xKp9m
 * SSE stream — polls canvas_events every 800ms.
 * Sends 'update' event on change, 'ping' every 15s.
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - Typed variables throughout
 *  - Chained db()->prepare() calls
 *  - (int) cast instead of intval()
 */

require_once dirname(__DIR__, 2) . '/config/db.php';

$id = preg_replace('/[^a-zA-Z0-9]/', '', (string)($_GET['id'] ?? ''));

if ($id === '') {
    http_response_code(400);
    echo "data: {\"error\":\"id required\"}\n\n";
    exit;
}

// Verify canvas exists
try {
    $pdo   = db();
    $check = $pdo->prepare("SELECT id FROM canvases WHERE id = :id");
    $check->execute([':id' => $id]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo "data: {\"error\":\"Canvas not found\"}\n\n";
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit;
}

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');

if (ob_get_level()) ob_end_flush();
set_time_limit(0);
ignore_user_abort(true);

// Seed last_seen so we only deliver future events
$last_seen = (int)($_GET['lastEventId'] ?? 0);
if ($last_seen === 0) {
    try {
        $max = $pdo->prepare("SELECT COALESCE(MAX(id), 0) FROM canvas_events WHERE canvas_id = :cid");
        $max->execute([':cid' => $id]);
        $last_seen = (int)$max->fetchColumn();
    } catch (PDOException $e) {
        $last_seen = 0;
    }
}

$ping_every = 15;   // seconds between pings
$poll_sleep = 800;  // ms between DB polls
$last_ping  = time();

echo "retry: 3000\n\n";
flush();

while (true) {
    if (connection_aborted()) break;

    try {
        $pdo  = db();
        $stmt = $pdo->prepare("
            SELECT id, html, created_at
            FROM canvas_events
            WHERE canvas_id = :cid AND id > :last
            ORDER BY id ASC
            LIMIT 10
        ");
        $stmt->execute([':cid' => $id, ':last' => $last_seen]);

        foreach ($stmt->fetchAll() as $ev) {
            $payload = json_encode([
                'html'       => $ev['html'],
                'updated_at' => $ev['created_at'],
            ]);
            echo "id: {$ev['id']}\n";
            echo "event: update\n";
            echo "data: {$payload}\n\n";
            $last_seen = (int)$ev['id'];
            flush();
        }

    } catch (PDOException $e) {
        // DB blip — keep loop alive
    }

    if (time() - $last_ping >= $ping_every) {
        echo "event: ping\ndata: {}\n\n";
        flush();
        $last_ping = time();
    }

    usleep($poll_sleep * 1000);
}
