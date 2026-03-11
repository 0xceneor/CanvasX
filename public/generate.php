<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>canvas.new — Text to Page</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@300;400;700&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:         #050505;
      --surface:    #0f0f0f;
      --surface-2:  #1a1a1a;
      --border:     rgba(255,255,255,0.08);
      --border-h:   rgba(255,255,255,0.15);
      --text:       #ffffff;
      --text-muted: #666;
      --accent:     #a855f7;
      --accent-2:   #7c3aed;
      --accent-dim: rgba(168,85,247,0.12);
      --red:        #ef4444;
      --font-display: 'Unbounded', sans-serif;
      --font-body:    'Instrument Sans', sans-serif;
    }

    html { font-size: 16px; scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: var(--font-body);
      min-height: 100vh;
      display: grid;
      grid-template-rows: auto 1fr auto;
      overflow-x: hidden;
    }

    /* Noise overlay */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
      pointer-events: none;
      z-index: 0;
    }

    /* ── HEADER ── */
    .site-header {
      position: relative;
      z-index: 10;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 40px;
      border-bottom: 1px solid var(--border);
    }

    .logo {
      font-family: var(--font-display);
      font-weight: 700;
      font-size: 1rem;
      letter-spacing: -0.02em;
      color: var(--text);
      text-decoration: none;
    }

    .logo span { color: var(--accent); }

    .header-badge {
      font-size: 0.7rem;
      font-weight: 500;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--text-muted);
      background: var(--surface);
      border: 1px solid var(--border);
      padding: 4px 10px;
      border-radius: 20px;
    }

    /* ── MAIN LAYOUT ── */
    .main {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: 1fr 380px;
      gap: 0;
      max-width: 1280px;
      margin: 0 auto;
      width: 100%;
      padding: 48px 40px;
      align-items: start;
    }

    /* ── LEFT — GENERATOR ── */
    .generator {
      padding-right: 48px;
      border-right: 1px solid var(--border);
    }

    .gen-headline {
      font-family: var(--font-display);
      font-weight: 700;
      font-size: clamp(2rem, 4vw, 3.2rem);
      line-height: 1.05;
      letter-spacing: -0.04em;
      margin-bottom: 8px;
    }

    .gen-headline em {
      font-style: normal;
      color: var(--accent);
    }

    .gen-sub {
      color: var(--text-muted);
      font-size: 0.95rem;
      margin-bottom: 36px;
      line-height: 1.6;
    }

    /* Form */
    .form-group { margin-bottom: 16px; }

    label {
      display: block;
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 8px;
    }

    textarea, input[type="text"], select {
      width: 100%;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--text);
      font-family: var(--font-body);
      font-size: 0.95rem;
      transition: border-color 200ms ease, box-shadow 200ms ease;
      appearance: none;
    }

    textarea:focus, input[type="text"]:focus, select:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-dim);
    }

    textarea {
      padding: 16px;
      min-height: 180px;
      resize: vertical;
      line-height: 1.6;
    }

    input[type="text"] { padding: 10px 14px; }

    select {
      padding: 10px 14px;
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23666' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 36px;
    }

    select option { background: #1a1a1a; }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .char-row {
      display: flex;
      justify-content: flex-end;
      margin-top: 6px;
    }

    #char-count { font-size: 0.75rem; color: var(--text-muted); transition: color 200ms; }

    /* Submit button */
    #submit-btn {
      width: 100%;
      padding: 14px 24px;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-family: var(--font-display);
      font-weight: 700;
      font-size: 0.9rem;
      letter-spacing: 0.02em;
      cursor: pointer;
      transition: background 200ms ease, transform 150ms ease;
      margin-top: 8px;
    }

    #submit-btn:hover:not(:disabled) {
      background: var(--accent-2);
      transform: translateY(-1px);
    }

    #submit-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }

    /* Progress */
    .progress-track {
      height: 2px;
      background: var(--border);
      border-radius: 1px;
      margin-top: 16px;
      overflow: hidden;
    }

    #progress-bar {
      height: 100%;
      background: linear-gradient(90deg, var(--accent-2), var(--accent));
      width: 0%;
      transition: width 300ms ease;
      border-radius: 1px;
    }

    #status {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-top: 8px;
      min-height: 1.2em;
    }

    /* Code preview terminal */
    #code-preview {
      display: none;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 16px;
      font-family: 'Courier New', monospace;
      font-size: 0.75rem;
      line-height: 1.6;
      color: #7eca9c;
      max-height: 240px;
      overflow-y: auto;
      overflow-x: hidden;
      white-space: pre-wrap;
      word-break: break-all;
      margin-top: 16px;
    }

    /* Result */
    #result {
      display: none;
      margin-top: 24px;
      border: 1px solid var(--accent);
      border-radius: 10px;
      overflow: hidden;
      background: var(--surface);
    }

    .result-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      border-bottom: 1px solid var(--border);
      gap: 12px;
    }

    #result-url {
      color: var(--accent);
      font-size: 0.85rem;
      font-family: 'Courier New', monospace;
      text-decoration: none;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    #result-url:hover { text-decoration: underline; }

    .result-actions {
      display: flex;
      gap: 8px;
      flex-shrink: 0;
    }

    .result-actions button {
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 150ms ease;
      border: 1px solid var(--border);
      background: var(--surface-2);
      color: var(--text);
    }

    .result-actions button:hover { border-color: var(--accent); color: var(--accent); }

    .open-btn {
      background: var(--accent) !important;
      border-color: var(--accent) !important;
      color: #fff !important;
    }

    .open-btn:hover { background: var(--accent-2) !important; border-color: var(--accent-2) !important; }

    #result-frame {
      width: 100%;
      height: 320px;
      border: none;
      display: block;
    }

    /* ── RIGHT — SIDEBAR ── */
    .sidebar {
      padding-left: 40px;
    }

    .sidebar-title {
      font-family: var(--font-display);
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 16px;
    }

    .recent-item {
      display: flex;
      flex-direction: column;
      gap: 2px;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 8px;
      text-decoration: none;
      margin-bottom: 8px;
      transition: border-color 200ms, background 200ms;
    }

    .recent-item:hover {
      border-color: var(--border-h);
      background: var(--surface);
    }

    .recent-title {
      font-size: 0.88rem;
      color: var(--text);
      font-weight: 500;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .recent-id {
      font-size: 0.72rem;
      color: var(--text-muted);
      font-family: 'Courier New', monospace;
    }

    .empty { font-size: 0.85rem; color: var(--text-muted); padding: 8px 0; }

    /* Pipeline explainer */
    .pipeline-box {
      margin-top: 32px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 20px;
    }

    .pipeline-box .step {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 14px;
    }

    .pipeline-box .step:last-child { margin-bottom: 0; }

    .step-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--accent);
      margin-top: 7px;
      flex-shrink: 0;
    }

    .step-text {
      font-size: 0.82rem;
      color: var(--text-muted);
      line-height: 1.5;
    }

    .step-text strong { color: var(--text); font-weight: 600; }

    /* ── FOOTER ── */
    .site-footer {
      position: relative;
      z-index: 10;
      text-align: center;
      padding: 24px 40px;
      border-top: 1px solid var(--border);
      color: var(--text-muted);
      font-size: 0.78rem;
    }

    .site-footer a { color: var(--text-muted); text-decoration: none; }
    .site-footer a:hover { color: var(--text); }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
      .main {
        grid-template-columns: 1fr;
        padding: 32px 20px;
      }
      .generator { padding-right: 0; border-right: none; border-bottom: 1px solid var(--border); padding-bottom: 40px; margin-bottom: 40px; }
      .sidebar { padding-left: 0; }
      .site-header { padding: 16px 20px; }
      .form-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <header class="site-header">
    <a href="/" class="logo">canvas<span>.</span>new</a>
    <span class="header-badge">Powered by MiniMax M2.5</span>
  </header>

  <main class="main">
    <!-- LEFT: Generator -->
    <section class="generator">
      <h1 class="gen-headline">Text in.<br><em>Live page</em> out.</h1>
      <p class="gen-sub">Paste any text, data, or context. The AI builds a complete, beautiful HTML page. Instantly.</p>

      <form id="gen-form">
        <div class="form-group">
          <label for="context">Your context or data</label>
          <textarea id="context" name="context" placeholder="Paste anything: a dataset, meeting notes, a report, a product spec, raw numbers... The AI will figure out the best layout." required></textarea>
          <div class="char-row"><span id="char-count">0</span> chars</div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="title">Page title <small style="font-weight:400;text-transform:none;letter-spacing:0">(optional)</small></label>
            <input type="text" id="title" name="title" placeholder="Auto-detected">
          </div>
          <div class="form-group">
            <label for="style">Layout style</label>
            <select id="style" name="style">
              <option value="auto">Auto-detect</option>
              <option value="dashboard">Dashboard</option>
              <option value="report">Report / Document</option>
              <option value="tool">Tool / Interactive</option>
              <option value="data">Data Visualization</option>
              <option value="list">List / Tracker</option>
              <option value="creative">Creative</option>
            </select>
          </div>
        </div>

        <button type="submit" id="submit-btn">Generate Page</button>

        <div class="progress-track">
          <div id="progress-bar"></div>
        </div>
        <p id="status"></p>
      </form>

      <pre id="code-preview"></pre>

      <div id="result">
        <div class="result-header">
          <a id="result-url" href="#" target="_blank"></a>
          <div class="result-actions">
            <button id="copy-btn">Copy URL</button>
            <button id="regen-btn">Regenerate</button>
            <a id="open-link" href="#" target="_blank"><button class="open-btn">Open Page →</button></a>
          </div>
        </div>
        <iframe id="result-frame" src="about:blank" sandbox="allow-scripts allow-same-origin"></iframe>
      </div>
    </section>

    <!-- RIGHT: Sidebar -->
    <aside class="sidebar">
      <p class="sidebar-title">Recent Canvases</p>
      <div id="recent-list">
        <p class="empty">Loading…</p>
      </div>

      <div class="pipeline-box">
        <p class="sidebar-title" style="margin-bottom:14px">How it works</p>
        <div class="step">
          <div class="step-dot"></div>
          <p class="step-text"><strong>You paste context</strong> — data, notes, numbers, anything</p>
        </div>
        <div class="step">
          <div class="step-dot"></div>
          <p class="step-text"><strong>MiniMax M2.5</strong> generates complete, styled HTML in one shot</p>
        </div>
        <div class="step">
          <div class="step-dot"></div>
          <p class="step-text"><strong>Canvas stores it</strong> as a permanent, shareable live URL</p>
        </div>
        <div class="step">
          <div class="step-dot"></div>
          <p class="step-text"><strong>You get the link</strong> instantly — no deploy, no code</p>
        </div>
      </div>
    </aside>
  </main>

  <footer class="site-footer">
    canvas.new &mdash; <a href="/api/canvas.php?action=list">API</a>
  </footer>

  <script>
    // Wire open link to result URL
    document.getElementById('result-url') && document.getElementById('result-url').addEventListener('DOMContentLoaded', () => {});
    // Sync open-link href with result-url
    const _origShow = window.showResult;
  </script>
  <script src="/assets/generate.js"></script>
  <script>
    // Patch open-link after result shown
    const _resultUrl = document.getElementById('result-url');
    const _openLink  = document.getElementById('open-link');
    const _observer  = new MutationObserver(() => {
      if (_resultUrl.href && _resultUrl.href !== '#' && _resultUrl.href !== window.location.href) {
        _openLink.href = _resultUrl.href;
      }
    });
    _observer.observe(_resultUrl, { attributes: true, attributeFilter: ['href'] });
  </script>

</body>
</html>
