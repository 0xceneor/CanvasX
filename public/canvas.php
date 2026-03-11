<?php
/**
 * Canvas renderer — /c/{id}
 * Fetches canvas from DB, increments views, renders full page with injected components.
 */
require_once dirname(__DIR__) . '/config/db.php';

$id = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['id'] ?? '');
$embed = isset($_GET['embed']) && $_GET['embed'] === '1';

if (!$id) {
    http_response_code(400);
    die('Canvas ID required.');
}

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id, title, html, frames, webhook_url, embed, created_at, updated_at, views
        FROM canvases WHERE id = :id
    ");
    $stmt->execute([':id' => $id]);
    $canvas = $stmt->fetch();

    if (!$canvas) {
        http_response_code(404);
        render_404($id);
        exit;
    }

    // Increment views (fire-and-forget)
    $pdo->prepare("UPDATE canvases SET views = views + 1 WHERE id = :id")
        ->execute([':id' => $id]);

} catch (PDOException $e) {
    http_response_code(500);
    die('Database error.');
}

$base_url = env('CANVAS_BASE_URL', 'http://localhost:8080');
$title    = htmlspecialchars($canvas['title'] ?? 'Canvas ' . $id);
$og_title = $canvas['title'] ?? 'Canvas';
$og_url   = "{$base_url}/c/{$id}";
$og_image = "{$base_url}/og.php?id={$id}";
$frames   = $canvas['frames'] ? json_decode($canvas['frames'], true) : null;

$component_scripts = [
    '/components/cc-chart.js',
    '/components/cc-table.js',
    '/components/cc-kanban.js',
    '/components/cc-form.js',
    '/components/cc-card.js',
    '/components/cc-grid.js',
    '/components/cc-code.js',
    '/components/cc-timeline.js',
];

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?> — canvas.new</title>

  <!-- OG / Twitter -->
  <meta property="og:title"       content="<?= htmlspecialchars($og_title) ?>">
  <meta property="og:image"       content="<?= $og_image ?>">
  <meta property="og:url"         content="<?= $og_url ?>">
  <meta property="og:type"        content="website">
  <meta name="twitter:card"       content="summary_large_image">
  <meta name="twitter:title"      content="<?= htmlspecialchars($og_title) ?>">
  <meta name="twitter:image"      content="<?= $og_image ?>">

  <link rel="stylesheet" href="/assets/style.css">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body { display: flex; flex-direction: column; background: #0a0a0a; }

    /* Toolbar */
    .cx-toolbar {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 20px;
      background: #0f0f0f;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      font-family: 'Instrument Sans', system-ui, sans-serif;
      font-size: 13px;
      color: #666;
      flex-shrink: 0;
    }
    .cx-toolbar.embed { display: none; }
    .cx-logo { font-weight: 700; color: #a855f7; letter-spacing: -0.01em; text-decoration: none; }
    .cx-title { color: #ccc; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 400px; }
    .cx-spacer { flex: 1; }
    .cx-badge { background: rgba(168,85,247,0.12); color: #a855f7; border-radius: 20px; padding: 2px 10px; font-size: 11px; }
    .cx-btn {
      padding: 4px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);
      background: transparent; color: #888; font-size: 12px; cursor: pointer;
      transition: all 150ms ease; text-decoration: none; display: inline-block;
    }
    .cx-btn:hover { border-color: #a855f7; color: #a855f7; }
    .cx-btn.primary { background: #a855f7; border-color: #a855f7; color: #fff; }
    .cx-btn.primary:hover { background: #7c3aed; }

    /* Tab bar (frames) */
    .cx-tabs {
      display: flex; gap: 0; border-bottom: 1px solid rgba(255,255,255,0.07);
      background: #0f0f0f; flex-shrink: 0;
    }
    .cx-tab {
      padding: 10px 20px; font-size: 13px; color: #666; cursor: pointer;
      border-bottom: 2px solid transparent; transition: all 150ms;
    }
    .cx-tab:hover { color: #ccc; }
    .cx-tab.active { color: #a855f7; border-bottom-color: #a855f7; }

    /* Canvas content */
    #canvas-content {
      flex: 1;
      overflow: auto;
    }

    #canvas-content iframe {
      width: 100%; height: 100%; border: none; display: block;
    }

    /* Loading indicator */
    #cx-loading {
      position: fixed; bottom: 16px; left: 50%; transform: translateX(-50%);
      background: rgba(0,0,0,0.85); color: #a855f7; border: 1px solid rgba(168,85,247,0.3);
      border-radius: 20px; padding: 6px 16px; font-size: 12px;
      font-family: 'Instrument Sans', sans-serif;
      display: none; z-index: 9999;
    }
  </style>
</head>
<body>

<?php if (!$embed): ?>
<div class="cx-toolbar">
  <a href="/" class="cx-logo">canvas.new</a>
  <?php if ($canvas['title']): ?>
  <span class="cx-title"><?= htmlspecialchars($canvas['title']) ?></span>
  <?php endif; ?>
  <span class="cx-spacer"></span>
  <span class="cx-badge" id="cx-viewers">1 viewer</span>
  <button class="cx-btn" id="cx-copy-btn" onclick="copyLink()">Copy link</button>
  <a href="/generate" class="cx-btn primary">+ New</a>
</div>
<?php endif; ?>

<?php if ($frames && count($frames) > 0): ?>
<div class="cx-tabs" id="cx-tabs">
  <div class="cx-tab active" data-frame-index="0" onclick="switchFrame(0)">
    <?= htmlspecialchars($frames[0]['label'] ?? 'Frame 1') ?>
  </div>
  <?php foreach (array_slice($frames, 1) as $i => $frame): ?>
  <div class="cx-tab" data-frame-index="<?= $i + 1 ?>" onclick="switchFrame(<?= $i + 1 ?>)">
    <?= htmlspecialchars($frame['label'] ?? 'Frame ' . ($i + 2)) ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div id="canvas-content">
<?= $canvas['html'] ?>
</div>

<div id="cx-loading">Updating…</div>

<!-- Canvas ID injection -->
<script>
  window.CANVAS_ID    = <?= json_encode($id) ?>;
  window.CANVAS_EMBED = <?= $embed ? 'true' : 'false' ?>;
  <?php if ($frames): ?>
  window.CANVAS_FRAMES = <?= $canvas['frames'] ?>;
  <?php endif; ?>
</script>

<?php foreach ($component_scripts as $src): ?>
<script src="<?= $src ?>"></script>
<?php endforeach; ?>
<script src="/assets/runtime.js"></script>
<?php if (!$embed): ?>
<script src="/assets/multiplayer.js"></script>
<?php endif; ?>

<script>
function copyLink() {
  navigator.clipboard.writeText(window.location.href).then(() => {
    const btn = document.getElementById('cx-copy-btn');
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy link', 2000);
  });
}

<?php if ($frames && count($frames) > 0): ?>
const frames = window.CANVAS_FRAMES;
let currentFrame = 0;

function switchFrame(index) {
  currentFrame = index;
  document.getElementById('canvas-content').innerHTML = frames[index].html;
  document.querySelectorAll('.cx-tab').forEach((t, i) => {
    t.classList.toggle('active', i === index);
  });
}
<?php endif; ?>
</script>

</body>
</html>
<?php

function render_404(string $id): void {
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>404 — canvas.new</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@700&family=Instrument+Sans&display=swap" rel="stylesheet">
  <style>
    :root{--bg:#050505;--text:#fff;--accent:#a855f7;--muted:#444}
    body{background:var(--bg);color:var(--text);font-family:'Instrument Sans',sans-serif;
         display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center}
    h1{font-family:'Unbounded',sans-serif;font-size:clamp(3rem,8vw,6rem);font-weight:700;
       letter-spacing:-0.04em;color:var(--muted);line-height:1}
    p{color:var(--muted);margin:16px 0 28px;font-size:0.95rem}
    code{background:rgba(255,255,255,0.05);padding:2px 8px;border-radius:4px;font-size:.85em}
    a{color:var(--accent);text-decoration:none;font-weight:600;border-bottom:1px solid transparent;
      transition:border-color 150ms}
    a:hover{border-bottom-color:var(--accent)}
  </style>
</head>
<body>
  <div>
    <h1>404</h1>
    <p>Canvas <code><?= htmlspecialchars($id) ?></code> was not found or has been deleted.</p>
    <a href="/generate">← Create a new canvas</a>
  </div>
</body>
</html><?php
}
