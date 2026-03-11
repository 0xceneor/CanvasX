<?php
/**
 * Canvas viewer — serves /c/<id>
 * Called via .htaccess rewrite: /c/(\w+) → /public/c.php?id=$1
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

$id       = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['id'] ?? '');
$data_dir = getenv('CANVAS_DATA_DIR') ?: __DIR__ . '/../data/canvases';

if (!$id) {
    http_response_code(400);
    die('Canvas ID required.');
}

$html_file = $data_dir . '/' . $id . '.html';
$meta_file = $data_dir . '/' . $id . '.json';

if (!file_exists($html_file)) {
    http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>404 — Canvas not found</title>
<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@700&family=Instrument+Sans&display=swap" rel="stylesheet">
<style>
  :root { --bg:#050505;--text:#fff;--accent:#a855f7;--muted:#555; }
  body { background:var(--bg);color:var(--text);font-family:'Instrument Sans',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center; }
  h1 { font-family:'Unbounded',sans-serif;font-size:4rem;font-weight:700;letter-spacing:-0.04em;color:var(--muted); }
  p { color:var(--muted);margin:12px 0 24px; }
  a { color:var(--accent);text-decoration:none;font-weight:600; }
  a:hover { text-decoration:underline; }
</style>
</head>
<body>
  <div>
    <h1>404</h1>
    <p>Canvas <code style="font-size:.9em;opacity:.6"><?= htmlspecialchars($id) ?></code> not found.</p>
    <a href="/generate">← Create a canvas</a>
  </div>
</body>
</html>
<?php
    exit;
}

// Serve the canvas HTML directly
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');

// Inject a small toolbar overlay
$meta = file_exists($meta_file) ? json_decode(file_get_contents($meta_file), true) : [];
$title = htmlspecialchars($meta['title'] ?? 'Canvas');
$created = $meta['created_at'] ?? '';
$created_fmt = $created ? date('M j, Y', strtotime($created)) : '';

$html = file_get_contents($html_file);

// Inject toolbar before </body>
$toolbar = <<<HTML
<div id="cx-toolbar" style="
  position:fixed;bottom:16px;right:16px;z-index:999999;
  display:flex;align-items:center;gap:8px;
  background:rgba(5,5,5,0.92);
  backdrop-filter:blur(12px);
  border:1px solid rgba(255,255,255,0.1);
  border-radius:30px;padding:8px 16px;
  font-family:'Instrument Sans',sans-serif;font-size:12px;color:#888;
  box-shadow:0 4px 24px rgba(0,0,0,0.5);
">
  <span style="color:#a855f7;font-weight:700;font-family:'Unbounded',sans-serif;font-size:10px;letter-spacing:0.05em">canvas.new</span>
  <span style="color:#333">|</span>
  <span style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{$title}</span>
  {$created_fmt}
  <a href="/generate" style="color:#a855f7;text-decoration:none;font-weight:600;margin-left:4px">+ New</a>
</div>
HTML;

echo str_replace('</body>', $toolbar . "\n</body>", $html);
