<?php
/**
 * GET /api/events?id=aB3xKp9m
 * SSE stream — polls canvas_events table every 800ms.
 * Sends 'update' event when canvas HTML changes, 'ping' every 15s.
 */
require_once dirname(__DIR__, 2) . '/config/db.php';

$id = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['id'] ?? '');

if (!$id) {
    http_response_code(400);
    echo "data: {\"error\":\"id required\"}\n\n";
    exit;
}

// Verify canvas exists
try {
    $pdo  = db();
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

// Disable output buffering
if (ob_get_level()) ob_end_flush();
set_time_limit(0);
ignore_user_abort(true);

// Get the latest event id we already know about
$last_event_id = (int)($_GET['lastEventId'] ?? 0);

// Track last seen canvas_events.id
$last_seen = $last_event_id;
if ($last_seen === 0) {
    // Seed with the current max so we only deliver future events
    try {
        $max = $pdo->prepare("SELECT COALESCE(MAX(id), 0) FROM canvas_events WHERE canvas_id = :cid");
        $max->execute([':cid' => $id]);
        $last_seen = (int)$max->fetchColumn();
    } catch (PDOException $e) {
        $last_seen = 0;
    }
}

$ping_every  = 15;   // seconds between ping events
$poll_sleep  = 800;  // ms between DB polls
$last_ping   = time();

echo "retry: 3000\n\n";
flush();

while (true) {
    if (connection_aborted()) break;

    try {
        $pdo = db(); // reuse singleton

        // Poll for new events
        $stmt = $pdo->prepare("
            SELECT id, html, created_at
            FROM canvas_events
            WHERE canvas_id = :cid AND id > :last
            ORDER BY id ASC
            LIMIT 10
        ");
        $stmt->execute([':cid' => $id, ':last' => $last_seen]);
        $events = $stmt->fetchAll();

        foreach ($events as $ev) {
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

    // Periodic ping
    if (time() - $last_ping >= $ping_every) {
        echo "event: ping\ndata: {}\n\n";
        flush();
        $last_ping = time();
    }

    usleep($poll_sleep * 1000);
}
