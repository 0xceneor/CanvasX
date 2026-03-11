/**
 * <cc-timeline> — vertical timeline, no deps
 * Attributes: title="string"
 * Data: <script type="application/json">{"events":[{"date":"Jan 2024","title":"Founded","description":"Started the project","color":"#a855f7"}]}</script>
 */
(function () {
  class CcTimeline extends HTMLElement {
    connectedCallback() {
      const title = this.getAttribute('title') || '';

      let cfg = { events: [] };
      const jsonEl = this.querySelector('script[type="application/json"]');
      if (jsonEl) {
        try { cfg = JSON.parse(jsonEl.textContent); } catch (_) {}
      }

      const shadow = this.attachShadow({ mode: 'open' });

      const eventsHtml = cfg.events.map((ev, i) => {
        const color = ev.color || '#a855f7';
        return `
          <div class="event" style="--i:${i}">
            <div class="dot" style="background:${this._esc(color)};box-shadow:0 0 0 3px ${this._esc(color)}22"></div>
            <div class="content">
              <div class="date">${this._esc(ev.date || '')}</div>
              <div class="ev-title">${this._esc(ev.title || '')}</div>
              ${ev.description ? `<div class="desc">${this._esc(ev.description)}</div>` : ''}
            </div>
          </div>
        `;
      }).join('');

      shadow.innerHTML = `
        <style>
          :host { display: block; }
          .wrap { font-family: 'Instrument Sans', system-ui, sans-serif; }
          h3 { font-size: 13px; font-weight: 700; color: #aaa; margin-bottom: 20px;
               letter-spacing: 0.05em; text-transform: uppercase; }
          .timeline { position: relative; padding-left: 28px; }
          .timeline::before {
            content: ''; position: absolute; left: 7px; top: 8px;
            bottom: 0; width: 1px; background: rgba(255,255,255,0.1);
          }
          .event {
            position: relative; margin-bottom: 24px;
            animation: fadeUp 400ms ease both;
            animation-delay: calc(var(--i, 0) * 80ms);
          }
          @keyframes fadeUp {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
          }
          .event:last-child { margin-bottom: 0; }
          .dot {
            position: absolute; left: -24px; top: 4px;
            width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
          }
          .date    { font-size: 11px; color: #666; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 4px; }
          .ev-title{ font-size: 14px; font-weight: 700; color: #e0e0e0; margin-bottom: 4px; }
          .desc    { font-size: 13px; color: #888; line-height: 1.5; }
        </style>
        <div class="wrap">
          ${title ? `<h3>${this._esc(title)}</h3>` : ''}
          <div class="timeline">${eventsHtml}</div>
        </div>
      `;
    }

    _esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  }

  customElements.define('cc-timeline', CcTimeline);
})();
