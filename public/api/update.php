<?php
declare(strict_types=1);

/**
 * POST /api/update
 * Update canvas HTML and/or title. Triggers SSE broadcast via canvas_events.
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - json_validate() before json_decode (8.3)
 *  - Chained ->prepare()->execute() on PDO
 *  - Typed parameters throughout
 */

require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw = (string) file_get_contents('php://input');

if (!json_validate($raw)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

$input      = json_decode($raw, true);
$id         = trim((string)($input['id']         ?? ''));
$edit_token = trim((string)($input['edit_token'] ?? ''));
$html       = isset($input['html'])  ? (string)$input['html']  : null;
$title      = isset($input['title']) ? trim((string)$input['title']) : null;

if ($id === '' || $edit_token === '') {
    http_response_code(400);
    echo json_encode(['error' => 'id and edit_token are required']);
    exit;
}

try {
    $pdo = db();

    $row = $pdo->prepare("SELECT id, edit_token, html FROM canvases WHERE id = :id")
               ->execute([':id' => $id])
            ?: null;

    // fetch after execute
    $stmt = $pdo->prepare("SELECT id, edit_token, html FROM canvases WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Canvas not found']);
        exit;
    }

    if (!hash_equals((string)$row['edit_token'], $edit_token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid edit_token']);
        exit;
    }

    // Build SET clause dynamically
    $sets   = ['updated_at = NOW()'];
    $params = [':id' => $id];

    if ($html !== null) {
        if (strlen($html) > 10 * 1024 * 1024) {
            http_response_code(413);
            echo json_encode(['error' => 'HTML content exceeds 10MB limit']);
            exit;
        }
        $sets[]          = 'html = :html';
        $params[':html'] = $html;
    }

    if ($title !== null) {
        $sets[]           = 'title = :title';
        $params[':title'] = $title;
    }

    $pdo->prepare('UPDATE canvases SET ' . implode(', ', $sets) . ' WHERE id = :id')
        ->execute($params);

    // Insert canvas_event row for SSE pickup
    $new_html = $html ?? (string)$row['html'];
    $pdo->prepare("INSERT INTO canvas_events (canvas_id, html) VALUES (:canvas_id, :html)")
        ->execute([':canvas_id' => $id, ':html' => $new_html]);

    $updated_at = $pdo->query("SELECT updated_at FROM canvases WHERE id = " . $pdo->quote($id))
                      ->fetchColumn();

    echo json_encode(['ok' => true, 'updated_at' => $updated_at]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
