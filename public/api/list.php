<?php
declare(strict_types=1);

/**
 * GET /api/list?limit=50&offset=0
 * List all canvases with pagination.
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - array_map with closure to transform rows (instead of reference foreach)
 *  - Chained db()->prepare() call
 *  - Typed bindValue calls
 */

require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$limit  = min(max((int)($_GET['limit']  ?? 50), 1), 200);
$offset = max((int)($_GET['offset'] ?? 0), 0);

try {
    $pdo  = db();
    $stmt = $pdo->prepare("
        SELECT id, title, created_at, updated_at, views
        FROM canvases
        ORDER BY updated_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $base_url = env('CANVAS_BASE_URL', 'http://localhost:8080');

    // array_map replacing reference foreach (cleaner, no unset needed)
    $canvases = array_map(
        static function (array $c) use ($base_url): array {
            $c['views'] = (int)$c['views'];
            $c['url']   = "{$base_url}/c/{$c['id']}";
            return $c;
        },
        $stmt->fetchAll(),
    );

    $total = (int)$pdo->query("SELECT COUNT(*) FROM canvases")->fetchColumn();

    echo json_encode(['canvases' => $canvases, 'total' => $total]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
