<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>canvas.new — The visual output layer for AI agents</title>
  <meta name="description" content="Self-hosted, open-source alternative to gui.new. Turn any HTML into a permanent, shareable, live canvas URL. Zero frameworks. Zero build steps.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@300;400;700;900&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/style.css">
  <style>
    :root {
      --font-display: 'Unbounded', sans-serif;
      --font-body: 'Instrument Sans', sans-serif;
    }

    body {
      font-family: var(--font-body);
      background: var(--bg);
      color: var(--text);
    }

    /* ── NAV ── */
    nav {
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px 60px;
      border-bottom: 1px solid var(--border);
      position: sticky; top: 0; z-index: 100;
      background: rgba(5,5,5,0.92); backdrop-filter: blur(12px);
    }

    .nav-logo { font-family: var(--font-display); font-weight: 700; font-size: 1rem; color: var(--text); }
    .nav-logo span { color: var(--accent); }

    .nav-links { display: flex; align-items: center; gap: 24px; }
    .nav-links a { color: var(--text-muted); font-size: 14px; transition: color 150ms; }
    .nav-links a:hover { color: var(--text); text-decoration: none; }

    .nav-cta {
      padding: 8px 18px; background: var(--accent); color: #fff; border-radius: 8px;
      font-size: 13px; font-weight: 600; transition: background 150ms;
    }
    .nav-cta:hover { background: var(--accent-2); text-decoration: none; }

    /* ── HERO ── */
    .hero {
      max-width: 900px; margin: 0 auto; padding: 100px 60px 80px;
      text-align: left;
    }

    .hero-kicker {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 12px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase;
      color: var(--accent); background: var(--accent-dim); border: 1px solid rgba(168,85,247,0.2);
      padding: 5px 14px; border-radius: 20px; margin-bottom: 28px;
    }

    h1 {
      font-family: var(--font-display);
      font-size: clamp(2.8rem, 6vw, 5.5rem);
      font-weight: 900;
      line-height: 1.0;
      letter-spacing: -0.04em;
      margin-bottom: 24px;
    }

    h1 em { font-style: normal; color: var(--accent); }

    .hero-sub {
      font-size: 1.15rem; color: var(--text-muted); max-width: 600px;
      line-height: 1.65; margin-bottom: 40px;
    }

    .hero-actions { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }

    .btn-primary {
      padding: 14px 28px; background: var(--accent); color: #fff;
      border-radius: 9px; font-weight: 700; font-size: 0.95rem;
      transition: all 150ms; font-family: var(--font-display);
    }
    .btn-primary:hover { background: var(--accent-2); text-decoration: none; transform: translateY(-1px); }

    .btn-outline {
      padding: 13px 28px; border: 1px solid var(--border-h); color: var(--text-muted);
      border-radius: 9px; font-size: 0.9rem;
      transition: all 150ms;
    }
    .btn-outline:hover { border-color: var(--text-muted); color: var(--text); text-decoration: none; }

    /* ── CODE DEMO ── */
    .demo-section {
      max-width: 900px; margin: 0 auto 80px; padding: 0 60px;
    }

    .demo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    .demo-block {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: 10px; overflow: hidden;
    }

    .demo-header {
      display: flex; align-items: center; gap: 8px;
      padding: 10px 16px; border-bottom: 1px solid var(--border);
      background: var(--surface-2);
    }

    .demo-dot { width: 8px; height: 8px; border-radius: 50%; }
    .demo-label { font-size: 12px; color: var(--text-muted); font-family: var(--font-mono); margin-left: 4px; }

    .demo-code {
      padding: 20px; font-family: var(--font-mono); font-size: 13px;
      color: #ccc; line-height: 1.7; overflow-x: auto;
    }

    .t-purple  { color: #a855f7; }
    .t-green   { color: #34d399; }
    .t-yellow  { color: #facc15; }
    .t-blue    { color: #38bdf8; }
    .t-muted   { color: #666; }
    .t-white   { color: #e0e0e0; }

    /* ── FEATURES ── */
    .features {
      border-top: 1px solid var(--border);
      padding: 80px 60px;
      max-width: 900px; margin: 0 auto;
    }

    .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 48px; }

    .feature {
      border: 1px solid var(--border); border-radius: 10px; padding: 24px;
      transition: border-color 200ms, transform 200ms;
      animation: fadeUp 400ms ease both;
      animation-delay: calc(var(--i, 0) * 80ms);
    }
    @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }
    .feature:hover { border-color: var(--border-h); transform: translateY(-2px); }
    .feature-icon { font-size: 22px; margin-bottom: 12px; }
    .feature-title { font-family: var(--font-display); font-size: 0.85rem; font-weight: 700; margin-bottom: 8px; }
    .feature-desc { font-size: 13px; color: var(--text-muted); line-height: 1.6; }

    /* ── ADVANTAGES ── */
    .vs-section {
      border-top: 1px solid var(--border); padding: 80px 60px;
      max-width: 900px; margin: 0 auto;
    }

    .vs-table { width: 100%; border-collapse: collapse; margin-top: 32px; font-size: 14px; }
    .vs-table th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700;
                   letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted);
                   border-bottom: 1px solid var(--border); }
    .vs-table td { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.04); color: #ccc; }
    .vs-table tr:last-child td { border-bottom: none; }
    .vs-table .check { color: var(--green); font-weight: 700; }
    .vs-table .cross { color: var(--red); }

    /* ── CTA ── */
    .cta-section {
      text-align: center; padding: 100px 60px;
      border-top: 1px solid var(--border);
    }
    .cta-section h2 { font-family: var(--font-display); font-size: clamp(2rem,4vw,3.5rem);
                      font-weight: 900; letter-spacing: -0.04em; margin-bottom: 16px; }
    .cta-section p { color: var(--text-muted); margin-bottom: 32px; font-size: 1rem; }

    /* ── FOOTER ── */
    footer {
      border-top: 1px solid var(--border); padding: 24px 60px;
      display: flex; align-items: center; justify-content: space-between;
      font-size: 13px; color: var(--text-muted);
    }
    footer a { color: var(--text-muted); }
    footer a:hover { color: var(--text); }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
      nav, .hero, .demo-section, .features, .vs-section, .cta-section, footer { padding-left: 24px; padding-right: 24px; }
      .demo-grid, .features-grid { grid-template-columns: 1fr; }
      .vs-table { display: none; }
    }

    /* Section label */
    .section-label {
      font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--text-muted); margin-bottom: 16px;
    }
    .section-title {
      font-family: var(--font-display); font-size: clamp(1.8rem,3vw,2.8rem);
      font-weight: 900; letter-spacing: -0.03em; line-height: 1.1;
    }
  </style>
</head>
<body>

<nav>
  <span class="nav-logo">canvas<span>.</span>new</span>
  <div class="nav-links">
    <a href="/docs">Docs</a>
    <a href="/api/list.php">API</a>
    <a href="https://github.com/yourusername/canvas-new" target="_blank">GitHub</a>
    <a href="/generate" class="nav-cta">Open Generator →</a>
  </div>
</nav>

<section class="hero">
  <div class="hero-kicker">Open source · Self-hosted · Zero build steps</div>
  <h1>The visual output<br>layer for <em>AI agents</em>.</h1>
  <p class="hero-sub">Any HTML in → permanent live URL out. Canvas.new is the self-hosted alternative to gui.new. You own the data, the server, the code.</p>
  <div class="hero-actions">
    <a href="/generate" class="btn-primary">Try the Generator →</a>
    <a href="/docs" class="btn-outline">Read the docs</a>
  </div>
</section>

<section class="demo-section">
  <div class="demo-grid">
    <div class="demo-block">
      <div class="demo-header">
        <div class="demo-dot" style="background:#ef4444"></div>
        <div class="demo-dot" style="background:#facc15"></div>
        <div class="demo-dot" style="background:#34d399"></div>
        <span class="demo-label">curl</span>
      </div>
      <div class="demo-code">curl -X POST <span class="t-blue">/api/create.php</span> \<br>
  -d '<span class="t-green">{<br>
    "title": "Q3 Dashboard",<br>
    "html": "&lt;h1&gt;Revenue: $2.4M&lt;/h1&gt;"<br>
  }</span>'</div>
    </div>
    <div class="demo-block">
      <div class="demo-header">
        <div class="demo-dot" style="background:#ef4444"></div>
        <div class="demo-dot" style="background:#facc15"></div>
        <div class="demo-dot" style="background:#34d399"></div>
        <span class="demo-label">response</span>
      </div>
      <div class="demo-code"><span class="t-muted">{</span><br>
  <span class="t-purple">"id"</span>: <span class="t-green">"aB3xKp9m"</span>,<br>
  <span class="t-purple">"url"</span>: <span class="t-green">"canvas.new/c/aB3xKp9m"</span>,<br>
  <span class="t-purple">"edit_token"</span>: <span class="t-green">"tok_..."</span><br>
<span class="t-muted">}</span></div>
    </div>
  </div>
</section>

<section class="features">
  <p class="section-label">What you get</p>
  <h2 class="section-title">Everything gui.new<br>should have been.</h2>

  <div class="features-grid">
    <div class="feature" style="--i:0">
      <div class="feature-icon">♾️</div>
      <div class="feature-title">Permanent canvases</div>
      <div class="feature-desc">No expiry. No 24-hour free tier. Your canvases live as long as your server does.</div>
    </div>
    <div class="feature" style="--i:1">
      <div class="feature-icon">🔄</div>
      <div class="feature-title">Live updates via SSE</div>
      <div class="feature-desc">Update any canvas and all live viewers see the DOM hot-swapped in &lt;800ms. No reload.</div>
    </div>
    <div class="feature" style="--i:2">
      <div class="feature-icon">👥</div>
      <div class="feature-title">Multiplayer cursors</div>
      <div class="feature-desc">Real-time cursor presence and input sync via WebSocket. See who's viewing right now.</div>
    </div>
    <div class="feature" style="--i:3">
      <div class="feature-icon">🧩</div>
      <div class="feature-title">8 web components</div>
      <div class="feature-desc">cc-chart, cc-table, cc-kanban, cc-form, cc-card, cc-grid, cc-code, cc-timeline — all auto-injected.</div>
    </div>
    <div class="feature" style="--i:4">
      <div class="feature-icon">📬</div>
      <div class="feature-title">Form webhooks</div>
      <div class="feature-desc">cc-form submissions stored in PostgreSQL and fired to any webhook URL. Agents receive user input back.</div>
    </div>
    <div class="feature" style="--i:5">
      <div class="feature-icon">🗄️</div>
      <div class="feature-title">Real PostgreSQL</div>
      <div class="feature-desc">Full query power. Submissions, events, and canvases all stored durably. You own the data.</div>
    </div>
  </div>
</section>

<section class="vs-section">
  <p class="section-label">Comparison</p>
  <h2 class="section-title">canvas.new vs gui.new</h2>

  <table class="vs-table">
    <thead>
      <tr>
        <th>Feature</th>
        <th>canvas.new</th>
        <th>gui.new (free)</th>
      </tr>
    </thead>
    <tbody>
      <tr><td>Canvas expiry</td><td class="check">Never</td><td class="cross">24 hours</td></tr>
      <tr><td>Edit limit</td><td class="check">Unlimited</td><td class="cross">3 edits</td></tr>
      <tr><td>Size limit</td><td class="check">10MB</td><td class="cross">2MB</td></tr>
      <tr><td>No watermark / branding</td><td class="check">✓</td><td class="cross">✗</td></tr>
      <tr><td>Live SSE updates</td><td class="check">✓</td><td class="cross">✗</td></tr>
      <tr><td>Multiplayer cursors</td><td class="check">✓</td><td class="cross">✗</td></tr>
      <tr><td>Form webhook submissions</td><td class="check">✓</td><td class="cross">✗</td></tr>
      <tr><td>Canvas management API</td><td class="check">✓ (list, delete)</td><td class="cross">✗</td></tr>
      <tr><td>Embed mode</td><td class="check">✓ (?embed=1)</td><td class="cross">✗</td></tr>
      <tr><td>OG image generation</td><td class="check">✓ PHP GD</td><td class="cross">✗</td></tr>
      <tr><td>Self-hosted</td><td class="check">✓</td><td class="cross">✗</td></tr>
      <tr><td>PostgreSQL storage</td><td class="check">✓</td><td class="cross">✗</td></tr>
      <tr><td>Open source</td><td class="check">✓</td><td class="cross">✗</td></tr>
    </tbody>
  </table>
</section>

<section class="cta-section">
  <h2>Ready to ship?</h2>
  <p>One POST request. A permanent live URL. That's it.</p>
  <a href="/generate" class="btn-primary">Open the Generator →</a>
</section>

<footer>
  <span>canvas.new</span>
  <div style="display:flex;gap:20px">
    <a href="/docs">Docs</a>
    <a href="/api/list.php">API</a>
    <a href="https://github.com/yourusername/canvas-new" target="_blank">GitHub</a>
  </div>
</footer>

</body>
</html>
