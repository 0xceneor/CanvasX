/**
 * runtime.js — auto-injected into every canvas page.
 * 1. SSE connection to /api/events.php?id=CANVAS_ID
 * 2. On 'update': hot-swap #canvas-content innerHTML
 * 3. Scan and wire cc-form submit handlers
 * 4. Pass CANVAS_ID + viewer identity to multiplayer.js
 */

(function () {
  'use strict';

  const CANVAS_ID = window.CANVAS_ID;
  if (!CANVAS_ID) return;

  // ── Viewer identity (shared with multiplayer.js) ─────────────────────────
  window.CX_VIEWER_ID   = window.CX_VIEWER_ID   || Math.random().toString(36).slice(2, 10);
  window.CX_VIEWER_NAME = window.CX_VIEWER_NAME || 'Viewer ' + Math.floor(Math.random() * 999);

  // ── Loading indicator ────────────────────────────────────────────────────
  const loader = document.getElementById('cx-loading');
  function showLoader() { if (loader) loader.style.display = 'block'; }
  function hideLoader() { if (loader) loader.style.display = 'none'; }

  // ── SSE ──────────────────────────────────────────────────────────────────
  let es;
  let lastEventId = 0;

  function connectSSE() {
    const url = `/api/events.php?id=${CANVAS_ID}` + (lastEventId ? `&lastEventId=${lastEventId}` : '');
    es = new EventSource(url);

    es.addEventListener('update', (e) => {
      hideLoader();
      try {
        const payload = JSON.parse(e.data);
        swapCanvas(payload.html);
      } catch (_) {}
    });

    es.addEventListener('ping', () => hideLoader());

    es.addEventListener('error', () => {
      es.close();
      showLoader();
      setTimeout(connectSSE, 3000);
    });
  }

  connectSSE();

  // ── DOM hot-swap ─────────────────────────────────────────────────────────
  function swapCanvas(html) {
    const content = document.getElementById('canvas-content');
    if (!content) return;

    // Preserve scroll position
    const scrollY = content.scrollTop;

    content.innerHTML = html;

    // Re-init web components (they re-observe)
    content.scrollTop = scrollY;

    // Re-wire cc-form handlers
    wireForms();
  }

  // ── cc-form submit handler ───────────────────────────────────────────────
  function wireForms() {
    document.querySelectorAll('cc-form').forEach((el) => {
      if (el._cx_wired) return;
      el._cx_wired = true;

      el.addEventListener('cc-form-submit', async (e) => {
        const { data } = e.detail;
        try {
          const res = await fetch('/api/form-submit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ canvas_id: CANVAS_ID, data }),
          });
          const result = await res.json();
          if (!result.ok) throw new Error(result.error || 'Submit failed');
          el.dispatchEvent(new CustomEvent('cc-form-success', { bubbles: true }));
        } catch (err) {
          el.dispatchEvent(new CustomEvent('cc-form-error', { bubbles: true, detail: { error: err.message } }));
        }
      });
    });
  }

  // Wire forms on initial load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', wireForms);
  } else {
    wireForms();
  }

})();
