<?php
/**
 * POST /api/create
 * Create a new canvas. Returns id, url, edit_token, embed_url.
 */
require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

$input = json_decode(file_get_contents('php://input'), true);

$html        = $input['html']        ?? '';
$title       = trim($input['title']  ?? '');
$frames      = $input['frames']      ?? null;
$webhook_url = trim($input['webhook_url'] ?? '');

if (!$html) {
    http_response_code(400);
    echo json_encode(['error' => 'html is required']);
    exit;
}

if (strlen($html) > 10 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['error' => 'HTML content exceeds 10MB limit']);
    exit;
}

$id         = nanoid(8);
$edit_token = make_edit_token();
$base_url   = env('CANVAS_BASE_URL', 'http://localhost:8080');

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        INSERT INTO canvases (id, title, html, frames, edit_token, webhook_url)
        VALUES (:id, :title, :html, :frames, :edit_token, :webhook_url)
    ");
    $stmt->execute([
        ':id'          => $id,
        ':title'       => $title ?: null,
        ':html'        => $html,
        ':frames'      => $frames ? json_encode($frames) : null,
        ':edit_token'  => $edit_token,
        ':webhook_url' => $webhook_url ?: null,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

echo json_encode([
    'id'         => $id,
    'url'        => "{$base_url}/c/{$id}",
    'embed_url'  => "{$base_url}/c/{$id}?embed=1",
    'edit_token' => $edit_token,
]);
