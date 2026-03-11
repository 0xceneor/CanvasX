<?php
declare(strict_types=1);

/**
 * POST /api/delete
 * Delete a canvas with valid edit_token.
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - json_validate() (8.3)
 *  - Chained prepare()->execute() on db()
 *  - Typed parameters
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

if ($id === '' || $edit_token === '') {
    http_response_code(400);
    echo json_encode(['error' => 'id and edit_token are required']);
    exit;
}

try {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT edit_token FROM canvases WHERE id = :id");
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

    $pdo->prepare("DELETE FROM canvases WHERE id = :id")->execute([':id' => $id]);

    echo json_encode(['ok' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
