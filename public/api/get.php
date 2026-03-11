<?php
declare(strict_types=1);

/**
 * GET /api/get?id=aB3xKp9m
 * Fetch single canvas JSON (without edit_token).
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - Typed casts on row fields
 *  - Chained db()->prepare() call
 */

require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$id = preg_replace('/[^a-zA-Z0-9]/', '', (string)($_GET['id'] ?? ''));

if ($id === '') {
    http_response_code(400);
    echo json_encode(['error' => 'id is required']);
    exit;
}

try {
    $stmt = db()->prepare("
        SELECT id, title, html, frames, webhook_url, embed,
               created_at, updated_at, views
        FROM canvases WHERE id = :id
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Canvas not found']);
        exit;
    }

    $row['frames'] = $row['frames'] !== null ? json_decode((string)$row['frames'], true) : null;
    $row['embed']  = (bool)$row['embed'];
    $row['views']  = (int)$row['views'];

    echo json_encode($row);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
