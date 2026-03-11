<?php
declare(strict_types=1);

/**
 * POST /api/form-submit
 * Called by cc-form component on submit.
 * Saves submission to form_submissions + fires webhook if set.
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - json_validate() before decode (8.3)
 *  - Chained db()->prepare() calls
 *  - Static closure on curl write function
 *  - Typed casts
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

$input     = json_decode($raw, true);
$canvas_id = preg_replace('/[^a-zA-Z0-9]/', '', trim((string)($input['canvas_id'] ?? '')));
$data      = $input['data'] ?? null;

if ($canvas_id === '' || $data === null) {
    http_response_code(400);
    echo json_encode(['error' => 'canvas_id and data are required']);
    exit;
}

try {
    $pdo = db();

    $canvas_stmt = $pdo->prepare("SELECT id, webhook_url FROM canvases WHERE id = :id");
    $canvas_stmt->execute([':id' => $canvas_id]);
    $canvas = $canvas_stmt->fetch();

    if (!$canvas) {
        http_response_code(404);
        echo json_encode(['error' => 'Canvas not found']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO form_submissions (canvas_id, data)
        VALUES (:canvas_id, :data)
        RETURNING id
    ");
    $stmt->execute([
        ':canvas_id' => $canvas_id,
        ':data'      => json_encode($data),
    ]);
    $submission_id = (int)$stmt->fetchColumn();

    // Fire webhook if configured
    $webhook_url = (string)($canvas['webhook_url'] ?? '');
    if ($webhook_url !== '') {
        $payload = json_encode([
            'canvas_id'     => $canvas_id,
            'submission_id' => $submission_id,
            'data'          => $data,
            'submitted_at'  => date('c'),
        ]);

        $ch = curl_init($webhook_url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    echo json_encode(['ok' => true, 'submission_id' => $submission_id]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
