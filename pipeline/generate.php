<?php
/**
 * POST /pipeline/generate
 * AI Pipeline: text/context → MiniMax M2.5 → HTML → canvas stored in PostgreSQL → live URL
 */
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$input   = json_decode(file_get_contents('php://input'), true);
$context = trim($input['context'] ?? '');
$style   = trim($input['style']   ?? 'auto');
$title   = trim($input['title']   ?? '');

if (!$context) {
    http_response_code(400);
    echo json_encode(['error' => 'context required']);
    exit;
}

$system_prompt = require __DIR__ . '/prompt.php';

$api_key = env('NVIDIA_API_KEY');
if (!$api_key) {
    http_response_code(500);
    echo json_encode(['error' => 'NVIDIA_API_KEY not configured']);
    exit;
}

// Style hints
$style_hints = [
    'dashboard' => "\n\nIMPORTANT: Use the Dashboard layout.",
    'report'    => "\n\nIMPORTANT: Use the Report/Document layout.",
    'tool'      => "\n\nIMPORTANT: Use the Tool/Interactive layout.",
    'creative'  => "\n\nIMPORTANT: Use the Creative/Generative layout.",
    'data'      => "\n\nIMPORTANT: Use the Data Visualization layout.",
    'list'      => "\n\nIMPORTANT: Use the List/Tracker layout.",
];
if (isset($style_hints[$style])) {
    $system_prompt .= $style_hints[$style];
}

$payload = json_encode([
    'model'       => 'minimaxai/minimax-m2.5',
    'messages'    => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user',   'content' => $context],
    ],
    'temperature' => 1,
    'top_p'       => 0.95,
    'max_tokens'  => 8192,
    'stream'      => true,
]);

$html_buffer = '';

$ch = curl_init('https://integrate.api.nvidia.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ],
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_TIMEOUT        => 120,
    CURLOPT_WRITEFUNCTION  => function ($ch, $data) use (&$html_buffer) {
        foreach (explode("\n", $data) as $line) {
            $line = trim($line);
            if (!str_starts_with($line, 'data: ')) continue;
            $raw = substr($line, 6);
            if ($raw === '[DONE]') break;
            $json = json_decode($raw, true);
            $html_buffer .= $json['choices'][0]['delta']['content'] ?? '';
        }
        return strlen($data);
    },
]);

$ok  = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if (!$ok || $err) {
    http_response_code(502);
    echo json_encode(['error' => 'AI API error: ' . $err]);
    exit;
}

// Extract HTML from model response
$html = extract_html($html_buffer);

// Auto-detect title
if (!$title) {
    preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m);
    $title = $m[1] ? strip_tags($m[1]) : 'Generated Canvas';
    $title = trim($title);
}

// Store in PostgreSQL via create API
try {
    $pdo        = db();
    $id         = nanoid(8);
    $edit_token = make_edit_token();
    $base_url   = env('CANVAS_BASE_URL', 'http://localhost:8080');

    $pdo->prepare("
        INSERT INTO canvases (id, title, html, edit_token)
        VALUES (:id, :title, :html, :edit_token)
    ")->execute([
        ':id'         => $id,
        ':title'      => $title,
        ':html'       => $html,
        ':edit_token' => $edit_token,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

echo json_encode([
    'ok'         => true,
    'id'         => $id,
    'url'        => "{$base_url}/c/{$id}",
    'embed_url'  => "{$base_url}/c/{$id}?embed=1",
    'edit_token' => $edit_token,
    'title'      => $title,
]);

function extract_html(string $raw): string {
    if (preg_match('/```(?:html)?\s*([\s\S]*?)```/i', $raw, $m)) return trim($m[1]);
    $t = ltrim($raw);
    if (str_starts_with($t, '<!') || str_starts_with($t, '<html')) return $raw;
    return $raw;
}
