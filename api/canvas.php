<?php
declare(strict_types=1);

/**
 * GET  /api/canvas.php?id=<id>        — fetch canvas HTML
 * GET  /api/canvas.php?action=list    — list recent canvases
 * DELETE /api/canvas.php?id=<id>      — delete canvas (token in body or query)
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - json_validate() (8.3)
 *  - match expression for action routing
 *  - array_find() (8.4) to filter index without array_filter + array_values
 *  - Typed casts on input
 */

// Load env
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}

$data_dir = (string)(getenv('CANVAS_DATA_DIR') ?: __DIR__ . '/../data/canvases');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = (string)($_GET['action'] ?? '');
$id     = preg_replace('/[^a-zA-Z0-9]/', '', (string)($_GET['id'] ?? ''));
$method = $_SERVER['REQUEST_METHOD'];

// ── List ──────────────────────────────────────────────────────────────────────
if ($action === 'list' || ($id === '' && $method === 'GET')) {
    header('Content-Type: application/json');
    $index_file = $data_dir . '/index.json';
    $index = file_exists($index_file) ? json_decode(file_get_contents($index_file), true) : [];
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    echo json_encode(array_slice($index, 0, $limit));
    exit;
}

if ($id === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'id required']);
    exit;
}

$html_file = $data_dir . '/' . $id . '.html';
$meta_file = $data_dir . '/' . $id . '.json';

if (!file_exists($html_file)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Canvas not found']);
    exit;
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    header('Content-Type: application/json');

    $raw_body = (string) file_get_contents('php://input');
    $token = (string)($_GET['token'] ?? '');
    if ($token === '' && json_validate($raw_body)) {
        $token = (string)(json_decode($raw_body, true)['token'] ?? '');
    }

    $meta = json_decode(file_get_contents($meta_file), true);
    if (!hash_equals((string)$meta['edit_token'], $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }

    unlink($html_file);
    unlink($meta_file);

    // Remove from index — PHP 8.4 array_find_key()
    $index_file = $data_dir . '/index.json';
    if (file_exists($index_file)) {
        $index = json_decode(file_get_contents($index_file), true);

        // PHP 8.4: array_find_key returns the key of the first matching element
        $key = array_find_key($index, fn(array $c): bool => $c['id'] === $id);
        if ($key !== null) {
            array_splice($index, $key, 1);
        }

        file_put_contents($index_file, json_encode($index, JSON_PRETTY_PRINT));
    }

    echo json_encode(['ok' => true]);
    exit;
}

// ── GET HTML ──────────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
readfile($html_file);
