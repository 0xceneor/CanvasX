<?php
/**
 * GET  /api/canvas.php?id=<id>        — fetch canvas HTML
 * GET  /api/canvas.php?action=list    — list recent canvases
 * DELETE /api/canvas.php?id=<id>&token=<edit_token> — delete canvas
 */

// Load env
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}

$data_dir = getenv('CANVAS_DATA_DIR') ?: __DIR__ . '/../data/canvases';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = $_GET['action'] ?? '';
$id     = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['id'] ?? '');

// List recent canvases
if ($action === 'list' || (!$id && $_SERVER['REQUEST_METHOD'] === 'GET')) {
    header('Content-Type: application/json');
    $index_file = $data_dir . '/index.json';
    $index = file_exists($index_file) ? json_decode(file_get_contents($index_file), true) : [];
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    echo json_encode(array_slice($index, 0, $limit));
    exit;
}

if (!$id) {
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

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    header('Content-Type: application/json');
    $meta  = json_decode(file_get_contents($meta_file), true);
    $token = $_GET['token'] ?? (json_decode(file_get_contents('php://input'), true)['token'] ?? '');
    if (!hash_equals($meta['edit_token'], $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    unlink($html_file);
    unlink($meta_file);
    // Remove from index
    $index_file = $data_dir . '/index.json';
    if (file_exists($index_file)) {
        $index = json_decode(file_get_contents($index_file), true);
        $index = array_values(array_filter($index, fn($c) => $c['id'] !== $id));
        file_put_contents($index_file, json_encode($index, JSON_PRETTY_PRINT));
    }
    echo json_encode(['ok' => true]);
    exit;
}

// GET canvas HTML
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
readfile($html_file);
