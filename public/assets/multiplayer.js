/**
 * multiplayer.js — cursor presence + input sync via WebSocket.
 * Loaded only on non-embed canvas pages.
 */

(function () {
  'use strict';

  const CANVAS_ID   = window.CANVAS_ID;
  const VIEWER_ID   = window.CX_VIEWER_ID;
  const VIEWER_NAME = window.CX_VIEWER_NAME;

  if (!CANVAS_ID) return;

  // Determine WS URL from current host
  const proto   = location.protocol === 'https:' ? 'wss:' : 'ws:';
  const WS_URL  = `${proto}//${location.hostname}/ws`;

  // Viewer color palette (8 colors, assigned by viewer_id hash)
  const COLORS = ['#a855f7','#38bdf8','#34d399','#fb923c','#f472b6','#facc15','#60a5fa','#f87171'];
  const myColor = COLORS[hashCode(VIEWER_ID) % COLORS.length];

  // Remote cursors: { viewer_id: { el, name, color } }
  const cursors = {};

  let ws = null;
  let cursorThrottle = 0;

  function connect() {
    try {
      ws = new WebSocket(WS_URL);
    } catch (_) { return; }

    ws.onopen = () => {
      send({ type: 'join', canvas_id: CANVAS_ID, viewer_id: VIEWER_ID, viewer_name: VIEWER_NAME, color: myColor });
    };

    ws.onmessage = (e) => {
      let msg;
      try { msg = JSON.parse(e.data); } catch (_) { return; }
      handleMessage(msg);
    };

    ws.onclose = () => {
      setTimeout(connect, 2000);
    };

    ws.onerror = () => ws.close();
  }

  function send(obj) {
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(obj));
    }
  }

  function handleMessage(msg) {
    switch (msg.type) {
      case 'cursor':
        if (msg.viewer_id !== VIEWER_ID) moveCursor(msg);
        break;
      case 'input':
        if (msg.viewer_id !== VIEWER_ID) applyInput(msg);
        break;
      case 'leave':
        removeCursor(msg.viewer_id);
        break;
      case 'viewers':
        updateViewerCount(msg.count);
        break;
    }
  }

  // ── Cursor rendering ─────────────────────────────────────────────────────

  function moveCursor(msg) {
    let entry = cursors[msg.viewer_id];
    if (!entry) {
      const el = document.createElement('div');
      el.style.cssText = `
        position: fixed; pointer-events: none; z-index: 99999;
        display: flex; align-items: flex-start; gap: 4px;
        transition: left 60ms linear, top 60ms linear;
      `;

      const dot = document.createElement('div');
      dot.style.cssText = `
        width: 10px; height: 10px; border-radius: 50%;
        background: ${msg.color || COLORS[0]}; margin-top: 2px; flex-shrink: 0;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.4);
      `;

      const label = document.createElement('span');
      label.style.cssText = `
        background: ${msg.color || COLORS[0]}; color: #fff;
        font-family: 'Instrument Sans', system-ui, sans-serif; font-size: 11px;
        padding: 2px 7px; border-radius: 10px; white-space: nowrap;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      `;
      label.textContent = msg.viewer_name || 'Viewer';

      el.appendChild(dot);
      el.appendChild(label);
      document.body.appendChild(el);
      entry = { el, name: msg.viewer_name, color: msg.color };
      cursors[msg.viewer_id] = entry;
    }

    entry.el.style.left = msg.x + 'px';
    entry.el.style.top  = msg.y + 'px';
  }

  function removeCursor(viewer_id) {
    const entry = cursors[viewer_id];
    if (entry) {
      entry.el.remove();
      delete cursors[viewer_id];
    }
  }

  // ── Input sync ───────────────────────────────────────────────────────────

  function applyInput(msg) {
    try {
      const el = document.querySelector(msg.selector);
      if (el && 'value' in el) el.value = msg.value;
    } catch (_) {}
  }

  // ── Event listeners ──────────────────────────────────────────────────────

  document.addEventListener('mousemove', (e) => {
    const now = Date.now();
    if (now - cursorThrottle < 50) return;
    cursorThrottle = now;
    send({ type: 'cursor', canvas_id: CANVAS_ID, viewer_id: VIEWER_ID, x: e.clientX, y: e.clientY });
  });

  document.addEventListener('input', (e) => {
    const el = e.target;
    if (!('value' in el)) return;
    const selector = buildSelector(el);
    if (selector) {
      send({ type: 'input', canvas_id: CANVAS_ID, viewer_id: VIEWER_ID, selector, value: el.value });
    }
  });

  window.addEventListener('beforeunload', () => {
    send({ type: 'leave', canvas_id: CANVAS_ID, viewer_id: VIEWER_ID });
  });

  // ── Viewer count badge ───────────────────────────────────────────────────

  function updateViewerCount(count) {
    const badge = document.getElementById('cx-viewers');
    if (badge) badge.textContent = count + (count === 1 ? ' viewer' : ' viewers');
  }

  // ── Helpers ──────────────────────────────────────────────────────────────

  function buildSelector(el) {
    if (el.id) return '#' + CSS.escape(el.id);
    if (el.name) return `[name="${CSS.escape(el.name)}"]`;
    return null;
  }

  function hashCode(str) {
    let h = 0;
    for (let i = 0; i < str.length; i++) h = (Math.imul(31, h) + str.charCodeAt(i)) | 0;
    return Math.abs(h);
  }

  // Connect
  connect();

})();
