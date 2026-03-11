<?php
/**
 * GET /api/list?limit=50&offset=0
 * List all canvases with pagination.
 */
require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$limit  = min(max((int)($_GET['limit']  ?? 50), 1), 200);
$offset = max((int)($_GET['offset'] ?? 0), 0);

try {
    $pdo = db();

    $rows = $pdo->prepare("
        SELECT id, title, created_at, updated_at, views
        FROM canvases
        ORDER BY updated_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $rows->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $rows->bindValue(':offset', $offset, PDO::PARAM_INT);
    $rows->execute();
    $canvases = $rows->fetchAll();

    $total = (int)$pdo->query("SELECT COUNT(*) FROM canvases")->fetchColumn();

    $base_url = env('CANVAS_BASE_URL', 'http://localhost:8080');
    foreach ($canvases as &$c) {
        $c['views'] = (int)$c['views'];
        $c['url']   = "{$base_url}/c/{$c['id']}";
    }
    unset($c);

    echo json_encode(['canvases' => $canvases, 'total' => $total]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
