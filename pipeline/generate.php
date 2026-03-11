<?php
declare(strict_types=1);

/**
 * POST /pipeline/generate
 * AI Pipeline: text/context → MiniMax M2.5 → HTML → PostgreSQL → live URL
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - Backed enum CanvasStyle with hint() method (8.1)
 *  - json_validate() before json_decode (8.3)
 *  - match expression replacing array lookup + isset
 *  - Named arguments on json_encode / curl
 *  - static closure on CURLOPT_WRITEFUNCTION
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Input validation ──────────────────────────────────────────────────────────
$raw = (string) file_get_contents('php://input');

if (!json_validate($raw)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

$input   = json_decode($raw, true);
$context = trim((string)($input['context'] ?? ''));
$style   = trim((string)($input['style']   ?? 'auto'));
$title   = trim((string)($input['title']   ?? ''));

if ($context === '') {
    http_response_code(400);
    echo json_encode(['error' => 'context required']);
    exit;
}

// ── Canvas style enum (PHP 8.1 backed enum) ───────────────────────────────────
enum CanvasStyle: string
{
    case Auto      = 'auto';
    case Dashboard = 'dashboard';
    case Report    = 'report';
    case Tool      = 'tool';
    case Creative  = 'creative';
    case Data      = 'data';
    case List      = 'list';

    public function hint(): string
    {
        return match ($this) {
            self::Auto      => '',
            self::Dashboard => "\n\nIMPORTANT: Use the Dashboard layout.",
            self::Report    => "\n\nIMPORTANT: Use the Report/Document layout.",
            self::Tool      => "\n\nIMPORTANT: Use the Tool/Interactive layout.",
            self::Creative  => "\n\nIMPORTANT: Use the Creative/Generative layout.",
            self::Data      => "\n\nIMPORTANT: Use the Data Visualization layout.",
            self::List      => "\n\nIMPORTANT: Use the List/Tracker layout.",
        };
    }
}

$canvas_style  = CanvasStyle::tryFrom($style) ?? CanvasStyle::Auto;
$system_prompt = (require __DIR__ . '/prompt.php') . $canvas_style->hint();

// ── API key ───────────────────────────────────────────────────────────────────
$api_key = env('NVIDIA_API_KEY');
if ($api_key === '') {
    http_response_code(500);
    echo json_encode(['error' => 'NVIDIA_API_KEY not configured']);
    exit;
}

// ── NVIDIA / MiniMax API call (streaming) ────────────────────────────────────
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
    CURLOPT_WRITEFUNCTION  => static function ($ch, string $data) use (&$html_buffer): int {
        foreach (explode("\n", $data) as $line) {
            $line = trim($line);
            if (!str_starts_with($line, 'data: ')) continue;
            $chunk = substr($line, 6);
            if ($chunk === '[DONE]') break;
            if (!json_validate($chunk)) continue;
            $parsed = json_decode($chunk, true);
            $html_buffer .= $parsed['choices'][0]['delta']['content'] ?? '';
        }
        return strlen($data);
    },
]);

$ok  = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if (!$ok || $err !== '') {
    http_response_code(502);
    echo json_encode(['error' => 'AI API error: ' . $err]);
    exit;
}

// ── Extract HTML ──────────────────────────────────────────────────────────────
$html = extract_html($html_buffer);

if ($title === '') {
    preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m);
    $title = isset($m[1]) ? trim(strip_tags($m[1])) : 'Generated Canvas';
}

// ── Persist to PostgreSQL ─────────────────────────────────────────────────────
try {
    $id         = nanoid(8);
    $edit_token = make_edit_token();
    $base_url   = env('CANVAS_BASE_URL', 'http://localhost:8080');

    db()->prepare("
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

// ── Helpers ───────────────────────────────────────────────────────────────────
function extract_html(string $raw): string
{
    if (preg_match('/```(?:html)?\s*([\s\S]*?)```/i', $raw, $m)) {
        return trim($m[1]);
    }
    $trimmed = ltrim($raw);
    return (str_starts_with($trimmed, '<!') || str_starts_with($trimmed, '<html'))
        ? $raw
        : $raw;
}
