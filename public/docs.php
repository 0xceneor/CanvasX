<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Docs — canvas.new</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@700;900&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/style.css">
  <style>
    :root { --font-display: 'Unbounded', sans-serif; }
    body { font-family: 'Instrument Sans', system-ui, sans-serif; }

    .layout { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }

    /* ── SIDEBAR ── */
    .sidebar {
      position: sticky; top: 0; height: 100vh; overflow-y: auto;
      padding: 28px 20px; border-right: 1px solid var(--border);
      background: var(--surface); flex-shrink: 0;
    }
    .sidebar-logo { font-family: var(--font-display); font-size: 0.85rem; font-weight: 700;
                    color: var(--text); margin-bottom: 28px; display: block; text-decoration: none; }
    .sidebar-logo span { color: var(--accent); }
    .nav-section { font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
                   color: var(--text-muted); margin: 20px 0 8px; }
    .nav-section:first-of-type { margin-top: 0; }
    .sidebar a { display: block; padding: 5px 10px; font-size: 13px; color: var(--text-muted);
                 border-radius: 6px; transition: all 150ms; text-decoration: none; }
    .sidebar a:hover { color: var(--text); background: rgba(255,255,255,0.04); }
    .sidebar a.active { color: var(--accent); background: var(--accent-dim); }

    /* ── CONTENT ── */
    .content { padding: 60px 80px; max-width: 860px; }
    .content h1 { font-family: var(--font-display); font-size: 2.4rem; font-weight: 900;
                  letter-spacing: -0.04em; margin-bottom: 12px; }
    .content h2 { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700;
                  letter-spacing: -0.02em; margin: 48px 0 16px; padding-top: 48px;
                  border-top: 1px solid var(--border); }
    .content h2:first-of-type { border-top: none; padding-top: 0; margin-top: 32px; }
    .content h3 { font-size: 1rem; font-weight: 700; color: var(--text); margin: 28px 0 10px; }
    .content p  { color: #bbb; line-height: 1.7; margin-bottom: 16px; font-size: 15px; }
    .content ul { margin: 0 0 16px 20px; color: #bbb; line-height: 1.8; font-size: 14px; }

    /* Code blocks */
    .code-block {
      position: relative; background: var(--surface); border: 1px solid var(--border);
      border-radius: 10px; overflow: hidden; margin: 16px 0 24px;
    }
    .code-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 9px 16px; background: var(--surface-2); border-bottom: 1px solid var(--border);
    }
    .code-lang { font-size: 11px; font-weight: 600; letter-spacing: 0.05em;
                 text-transform: uppercase; color: var(--text-muted); }
    .code-copy { background: none; border: 1px solid var(--border); color: var(--text-muted);
                 font-size: 11px; padding: 3px 10px; border-radius: 5px; cursor: pointer;
                 transition: all 150ms; font-family: inherit; }
    .code-copy:hover { border-color: var(--accent); color: var(--accent); }
    .code-block pre { margin: 0; border: none; border-radius: 0; padding: 20px; overflow-x: auto; }
    .code-block code { background: none; padding: 0; font-size: 13px; line-height: 1.7; color: #ccc; }

    /* Table */
    .api-table { width: 100%; border-collapse: collapse; margin: 16px 0 24px; font-size: 13px; }
    .api-table th { text-align: left; padding: 10px 14px; font-size: 11px; font-weight: 700;
                    letter-spacing: 0.05em; text-transform: uppercase; color: var(--text-muted);
                    background: var(--surface-2); border-bottom: 1px solid var(--border); }
    .api-table td { padding: 10px 14px; border-bottom: 1px solid rgba(255,255,255,0.04); color: #bbb; vertical-align: top; }
    .api-table tr:last-child td { border-bottom: none; }
    .api-table code { background: rgba(168,85,247,0.1); color: #c4a0f0; padding: 1px 6px; border-radius: 4px; font-size: 12px; }
    .api-table .req { color: var(--accent); font-size: 10px; font-weight: 700; }

    /* Method badge */
    .badge {
      display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px;
      font-weight: 700; letter-spacing: 0.04em; margin-right: 8px; font-family: var(--font-mono);
    }
    .badge.post { background: rgba(168,85,247,0.15); color: #a855f7; }
    .badge.get  { background: rgba(52,211,153,0.12);  color: #34d399; }

    /* Endpoint URL */
    .endpoint-url { font-family: var(--font-mono); font-size: 14px; font-weight: 600; color: var(--text); }

    /* Callout */
    .callout {
      background: var(--accent-dim); border: 1px solid rgba(168,85,247,0.2);
      border-left: 3px solid var(--accent); border-radius: 8px;
      padding: 14px 18px; margin: 16px 0; font-size: 14px; color: #c4a0f0; line-height: 1.6;
    }

    /* Component demo */
    .comp-demo {
      background: var(--surface); border: 1px solid var(--border); border-radius: 10px;
      padding: 20px; margin: 12px 0;
    }

    @media (max-width: 900px) {
      .layout { grid-template-columns: 1fr; }
      .sidebar { display: none; }
      .content { padding: 32px 24px; }
    }
  </style>
</head>
<body>
<div class="layout">

  <!-- SIDEBAR -->
  <nav class="sidebar">
    <a href="/" class="sidebar-logo">canvas<span>.</span>new</a>
    <div class="nav-section">Getting Started</div>
    <a href="#quickstart">Quickstart</a>
    <a href="#self-hosting">Self-hosting</a>
    <div class="nav-section">API Reference</div>
    <a href="#api-create">POST /create</a>
    <a href="#api-update">POST /update</a>
    <a href="#api-delete">POST /delete</a>
    <a href="#api-get">GET /get</a>
    <a href="#api-list">GET /list</a>
    <a href="#api-events">GET /events (SSE)</a>
    <a href="#api-form-submit">POST /form-submit</a>
    <div class="nav-section">AI Pipeline</div>
    <a href="#pipeline">POST /pipeline/generate</a>
    <div class="nav-section">Web Components</div>
    <a href="#cc-chart">cc-chart</a>
    <a href="#cc-table">cc-table</a>
    <a href="#cc-kanban">cc-kanban</a>
    <a href="#cc-form">cc-form</a>
    <a href="#cc-card">cc-card</a>
    <a href="#cc-grid">cc-grid</a>
    <a href="#cc-code">cc-code</a>
    <a href="#cc-timeline">cc-timeline</a>
    <div class="nav-section">SDKs</div>
    <a href="#sdk-js">JavaScript</a>
    <a href="#sdk-python">Python</a>
  </nav>

  <!-- CONTENT -->
  <main class="content">
    <h1>canvas.new Docs</h1>
    <p>Self-hosted, open-source visual output layer for AI agents. Zero frameworks. Zero build steps. One POST request gives you a permanent live URL.</p>

    <!-- QUICKSTART -->
    <h2 id="quickstart">Quickstart</h2>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">curl</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>curl -X POST https://yourdomain.com/api/create.php \
  -H 'Content-Type: application/json' \
  -d '{
    "title": "My Dashboard",
    "html": "&lt;h1&gt;Hello from canvas.new&lt;/h1&gt;"
  }'

# Response:
# { "id": "aB3xKp9m", "url": "https://yourdomain.com/c/aB3xKp9m", "edit_token": "tok_..." }</code></pre>
    </div>

    <div class="code-block">
      <div class="code-header"><span class="code-lang">JavaScript</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>import canvas from 'canvas-new';

const c = await canvas.create({
  title: 'My Dashboard',
  html: '&lt;h1&gt;Hello&lt;/h1&gt;'
});
console.log(c.url); // https://yourdomain.com/c/aB3xKp9m

// Update it live — all viewers see the change instantly
await canvas.update({ id: c.id, edit_token: c.edit_token, html: '&lt;h1&gt;Updated!&lt;/h1&gt;' });</code></pre>
    </div>

    <div class="code-block">
      <div class="code-header"><span class="code-lang">Python</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>import canvas_new

c = canvas_new.create('&lt;h1&gt;Hello&lt;/h1&gt;', title='My Dashboard')
print(c['url'])  # https://yourdomain.com/c/aB3xKp9m

canvas_new.update(c['id'], c['edit_token'], html='&lt;h1&gt;Updated!&lt;/h1&gt;')</code></pre>
    </div>

    <!-- SELF-HOSTING -->
    <h2 id="self-hosting">Self-hosting</h2>
    <h3>Requirements</h3>
    <ul>
      <li>PHP 8.2+ with extensions: pdo_pgsql, gd, curl</li>
      <li>PostgreSQL 14+</li>
      <li>Nginx + PHP-FPM</li>
      <li>Supervisord (for WebSocket daemon)</li>
      <li>Composer (only for Ratchet WebSocket server)</li>
    </ul>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">bash</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code># 1. Clone and configure
git clone https://github.com/yourusername/canvas-new /var/www/canvas
cp /var/www/canvas/.env.example /var/www/canvas/.env
# Edit .env — set DB_PASS, CANVAS_BASE_URL, NVIDIA_API_KEY

# 2. Database
createdb canvasnew
psql canvasnew &lt; /var/www/canvas/db/schema.sql

# 3. WebSocket server dependencies
cd /var/www/canvas && composer require ratchet/ratchet

# 4. Nginx
cp nginx/canvas.conf /etc/nginx/sites-available/canvas
ln -s /etc/nginx/sites-available/canvas /etc/nginx/sites-enabled/
# Edit server_name in canvas.conf, then:
nginx -t && systemctl reload nginx

# 5. WebSocket daemon
cp supervisor/ws.conf /etc/supervisor/conf.d/canvas-ws.conf
supervisorctl reread && supervisorctl update
supervisorctl status canvas-ws</code></pre>
    </div>

    <!-- API CREATE -->
    <h2 id="api-create">API Reference</h2>
    <h3><span class="badge post">POST</span><span class="endpoint-url">/api/create.php</span></h3>
    <p>Create a new canvas. Returns its permanent URL and edit token.</p>
    <table class="api-table">
      <thead><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>html</code></td><td>string</td><td class="req">required</td><td>Raw HTML content. Max 10MB.</td></tr>
        <tr><td><code>title</code></td><td>string</td><td></td><td>Optional display name for the canvas.</td></tr>
        <tr><td><code>frames</code></td><td>array</td><td></td><td><code>[{html, label}]</code> — enables tab switching.</td></tr>
        <tr><td><code>webhook_url</code></td><td>string</td><td></td><td>POST endpoint for cc-form submissions.</td></tr>
      </tbody>
    </table>
    <div class="callout">Response: <code>{id, url, embed_url, edit_token}</code>. Save the <code>edit_token</code> — it's shown only once and required for all future updates.</div>

    <!-- API UPDATE -->
    <h3 id="api-update"><span class="badge post">POST</span><span class="endpoint-url">/api/update.php</span></h3>
    <p>Update a canvas HTML and/or title. All live viewers see the change within 800ms via SSE — no page reload.</p>
    <table class="api-table">
      <thead><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>id</code></td><td>string</td><td class="req">required</td><td>Canvas ID.</td></tr>
        <tr><td><code>edit_token</code></td><td>string</td><td class="req">required</td><td>Token from create response.</td></tr>
        <tr><td><code>html</code></td><td>string</td><td></td><td>New HTML content.</td></tr>
        <tr><td><code>title</code></td><td>string</td><td></td><td>New title.</td></tr>
      </tbody>
    </table>

    <!-- API DELETE -->
    <h3 id="api-delete"><span class="badge post">POST</span><span class="endpoint-url">/api/delete.php</span></h3>
    <p>Permanently delete a canvas. Cascades to form_submissions and canvas_events.</p>

    <!-- API GET -->
    <h3 id="api-get"><span class="badge get">GET</span><span class="endpoint-url">/api/get.php?id=aB3xKp9m</span></h3>
    <p>Fetch full canvas data including HTML content, frames, and view count.</p>

    <!-- API LIST -->
    <h3 id="api-list"><span class="badge get">GET</span><span class="endpoint-url">/api/list.php?limit=50&amp;offset=0</span></h3>
    <p>Paginated list of all canvases, ordered by most recently updated. Max limit: 200.</p>

    <!-- API EVENTS -->
    <h3 id="api-events"><span class="badge get">GET</span><span class="endpoint-url">/api/events.php?id=aB3xKp9m</span></h3>
    <p>Server-Sent Events stream. Long-polling PHP polls <code>canvas_events</code> table every 800ms. Sends an <code>update</code> event when HTML changes, <code>ping</code> every 15s. Auto-reconnects every 3s on disconnect.</p>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">JavaScript</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>const es = new EventSource('/api/events.php?id=aB3xKp9m');
es.addEventListener('update', (e) => {
  const { html, updated_at } = JSON.parse(e.data);
  document.getElementById('canvas-content').innerHTML = html;
});</code></pre>
    </div>

    <!-- API FORM SUBMIT -->
    <h3 id="api-form-submit"><span class="badge post">POST</span><span class="endpoint-url">/api/form-submit.php</span></h3>
    <p>Called automatically by the <code>cc-form</code> web component on submit. Stores data in <code>form_submissions</code> table and fires the canvas's <code>webhook_url</code> if configured.</p>

    <!-- PIPELINE -->
    <h2 id="pipeline">AI Pipeline</h2>
    <h3><span class="badge post">POST</span><span class="endpoint-url">/pipeline/generate.php</span></h3>
    <p>Convert any text, data, or context into a beautiful canvas page using MiniMax M2.5. Requires <code>NVIDIA_API_KEY</code> in <code>.env</code>.</p>
    <table class="api-table">
      <thead><tr><th>Field</th><th>Type</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>context</code></td><td>string</td><td>Any text, data, or description to turn into a page.</td></tr>
        <tr><td><code>title</code></td><td>string</td><td>Optional. Auto-detected from generated &lt;title&gt; tag.</td></tr>
        <tr><td><code>style</code></td><td>string</td><td><code>auto</code> | <code>dashboard</code> | <code>report</code> | <code>tool</code> | <code>data</code> | <code>list</code> | <code>creative</code></td></tr>
      </tbody>
    </table>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">Python</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>page = canvas_new.generate(
    context="Q3 revenue was $2.4M, up 34% YoY. Top products: Pro 61%, Enterprise 28%. Churn 3.2%. NPS 67.",
    style="dashboard"
)
print(page['url'])  # https://yourdomain.com/c/aB3xKp9m — live, beautiful dashboard instantly</code></pre>
    </div>

    <!-- WEB COMPONENTS -->
    <h2 id="cc-chart">Web Components</h2>
    <p>All components are auto-injected into every canvas page. Use them directly in your HTML.</p>

    <h3 id="cc-chart">&lt;cc-chart&gt;</h3>
    <p>Wraps Chart.js from cdnjs. Supports bar, line, pie, doughnut, radar.</p>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">html</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>&lt;cc-chart type="bar" title="Monthly Revenue"&gt;
  &lt;script type="application/json"&gt;
    {"labels":["Jan","Feb","Mar"],"data":[10,20,15],"color":"#a855f7"}
  &lt;/script&gt;
&lt;/cc-chart&gt;

&lt;!-- Multi-dataset --&gt;
&lt;cc-chart type="line" title="Comparison"&gt;
  &lt;script type="application/json"&gt;
    {"labels":["Q1","Q2","Q3"],"datasets":[
      {"label":"Revenue","data":[10,20,15],"borderColor":"#a855f7","backgroundColor":"transparent"},
      {"label":"Costs","data":[8,12,10],"borderColor":"#38bdf8","backgroundColor":"transparent"}
    ]}
  &lt;/script&gt;
&lt;/cc-chart&gt;</code></pre>
    </div>

    <h3 id="cc-table">&lt;cc-table&gt;</h3>
    <p>Sortable table with vanilla JS. Click column headers to sort.</p>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">html</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>&lt;cc-table sortable title="Leaderboard"&gt;
  &lt;script type="application/json"&gt;
    {"headers":["Name","Score","Region"],"rows":[["Alice",99,"EU"],["Bob",87,"US"]]}
  &lt;/script&gt;
&lt;/cc-table&gt;</code></pre>
    </div>

    <h3 id="cc-kanban">&lt;cc-kanban&gt;</h3>
    <p>Drag-and-drop kanban board using HTML5 native drag API.</p>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">html</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>&lt;cc-kanban title="Sprint Board"&gt;
  &lt;script type="application/json"&gt;
    {"columns":[
      {"title":"Todo","cards":["Fix auth bug","Write tests"]},
      {"title":"In Progress","cards":["Design review"]},
      {"title":"Done","cards":["Deploy v1.2"]}
    ]}
  &lt;/script&gt;
&lt;/cc-kanban&gt;</code></pre>
    </div>

    <h3 id="cc-form">&lt;cc-form&gt;</h3>
    <p>Fires webhook on submit. Stores submission in PostgreSQL. Does not reload the page.</p>
    <p>Field types: <code>text</code>, <code>email</code>, <code>number</code>, <code>tel</code>, <code>url</code>, <code>date</code>, <code>select</code>, <code>textarea</code>, <code>checkbox</code>, <code>range</code></p>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">html</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>&lt;cc-form title="Contact Us" submit_label="Send Message"&gt;
  &lt;script type="application/json"&gt;
    {"fields":[
      {"type":"text","label":"Name","name":"name","required":true},
      {"type":"email","label":"Email","name":"email","required":true},
      {"type":"select","label":"Topic","name":"topic","options":["Bug","Feature","Other"]},
      {"type":"textarea","label":"Message","name":"message"}
    ]}
  &lt;/script&gt;
&lt;/cc-form&gt;</code></pre>
    </div>

    <h3 id="cc-card">&lt;cc-card&gt;</h3>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">html</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>&lt;cc-card title="Monthly Revenue" icon="💰" accent="#a855f7"&gt;
  &lt;script type="application/json"&gt;
    {"value":"$42,000","label":"vs last month","delta":"+12%","delta_positive":true}
  &lt;/script&gt;
&lt;/cc-card&gt;</code></pre>
    </div>

    <h3 id="cc-grid">&lt;cc-grid&gt;</h3>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">html</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>&lt;cc-grid cols="3" gap="16px"&gt;
  &lt;cc-card title="Revenue" icon="💰"&gt;...&lt;/cc-card&gt;
  &lt;cc-card title="Users"   icon="👥"&gt;...&lt;/cc-card&gt;
  &lt;cc-card title="Uptime"  icon="✅"&gt;...&lt;/cc-card&gt;
&lt;/cc-grid&gt;</code></pre>
    </div>

    <h3 id="cc-code">&lt;cc-code&gt;</h3>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">html</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>&lt;cc-code lang="python" title="analysis.py"&gt;
import pandas as pd
df = pd.read_csv('data.csv')
print(df.describe())
&lt;/cc-code&gt;</code></pre>
    </div>

    <h3 id="cc-timeline">&lt;cc-timeline&gt;</h3>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">html</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>&lt;cc-timeline title="Product Roadmap"&gt;
  &lt;script type="application/json"&gt;
    {"events":[
      {"date":"Q1 2024","title":"v1.0 Launch","description":"Initial release","color":"#a855f7"},
      {"date":"Q2 2024","title":"Multiplayer","description":"Real-time cursors","color":"#38bdf8"},
      {"date":"Q3 2024","title":"AI Pipeline","description":"Text to canvas","color":"#34d399"}
    ]}
  &lt;/script&gt;
&lt;/cc-timeline&gt;</code></pre>
    </div>

    <!-- SDKs -->
    <h2 id="sdk-js">JavaScript SDK</h2>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">bash</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>npm install canvas-new</code></pre>
    </div>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">JavaScript</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>import canvas from 'canvas-new';  // uses CANVAS_BASE_URL env var

// Or with explicit URL:
import { CanvasNew } from 'canvas-new';
const canvas = new CanvasNew({ baseUrl: 'https://yourdomain.com' });

const c = await canvas.create({ html: '&lt;h1&gt;Hello&lt;/h1&gt;', title: 'My Canvas' });
await canvas.update({ id: c.id, edit_token: c.edit_token, html: '&lt;h1&gt;Updated&lt;/h1&gt;' });
const data = await canvas.get(c.id);
const { canvases, total } = await canvas.list({ limit: 10 });
await canvas.delete({ id: c.id, edit_token: c.edit_token });

// AI pipeline
const page = await canvas.generate({ context: 'Q3 revenue $2.4M...', style: 'dashboard' });</code></pre>
    </div>

    <h2 id="sdk-python">Python SDK</h2>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">bash</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>pip install canvas-new</code></pre>
    </div>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">Python</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>import canvas_new  # uses CANVAS_BASE_URL env var

c = canvas_new.create('&lt;h1&gt;Hello&lt;/h1&gt;', title='My Canvas')
canvas_new.update(c['id'], c['edit_token'], html='&lt;h1&gt;Updated&lt;/h1&gt;')
data  = canvas_new.get(c['id'])
items = canvas_new.list_canvases(limit=10)
canvas_new.delete(c['id'], c['edit_token'])

# AI pipeline
page = canvas_new.generate('Q3 revenue $2.4M, up 34% YoY', style='dashboard')
print(page['url'])</code></pre>
    </div>
    <div class="code-block">
      <div class="code-header"><span class="code-lang">bash (CLI)</span><button class="code-copy" onclick="copyCode(this)">Copy</button></div>
      <pre><code>canvas-new create index.html --title "My Page"
canvas-new update aB3xKp9m tok_abc123 new.html
canvas-new list --limit 10
canvas-new generate "Daily standup notes: Alice shipped auth, Bob fixing bug #441" --style tool
canvas-new delete aB3xKp9m tok_abc123</code></pre>
    </div>

  </main>
</div>

<script>
function copyCode(btn) {
  const code = btn.closest('.code-block').querySelector('code');
  navigator.clipboard.writeText(code.textContent).then(() => {
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy', 2000);
  });
}

// Highlight active nav link on scroll
const headings = document.querySelectorAll('h2[id], h3[id]');
const navLinks = document.querySelectorAll('.sidebar a[href^="#"]');

window.addEventListener('scroll', () => {
  let current = '';
  headings.forEach(h => {
    if (window.scrollY >= h.offsetTop - 80) current = h.id;
  });
  navLinks.forEach(a => {
    a.classList.toggle('active', a.getAttribute('href') === '#' + current);
  });
}, { passive: true });
</script>
</body>
</html>
