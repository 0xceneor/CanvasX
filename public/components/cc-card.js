/**
 * <cc-card> — info/KPI card
 * Attributes: title="string", icon="emoji", accent="#hexcolor"
 * Data: <script type="application/json">{"value":"$42,000","label":"Monthly Revenue","delta":"+12%","delta_positive":true}</script>
 */
(function () {
  class CcCard extends HTMLElement {
    connectedCallback() {
      const title  = this.getAttribute('title')  || '';
      const icon   = this.getAttribute('icon')   || '';
      const accent = this.getAttribute('accent') || '#a855f7';

      let data = {};
      const jsonEl = this.querySelector('script[type="application/json"]');
      if (jsonEl) {
        try { data = JSON.parse(jsonEl.textContent); } catch (_) {}
      }

      const shadow = this.attachShadow({ mode: 'open' });

      const deltaColor = data.delta_positive === false ? '#ef4444' : '#34d399';
      const deltaArrow = data.delta_positive === false ? '↓' : '↑';

      shadow.innerHTML = `
        <style>
          :host { display: block; }
          .card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-left: 3px solid ${accent};
            border-radius: 10px; padding: 18px 20px;
            font-family: 'Instrument Sans', system-ui, sans-serif;
            transition: transform 200ms ease, border-color 200ms;
            animation: fadeUp 400ms ease both;
            animation-delay: calc(var(--i, 0) * 80ms);
          }
          @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
          }
          .card:hover { transform: translateY(-2px); border-color: rgba(255,255,255,0.14); }
          .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
          .title { font-size: 12px; font-weight: 600; color: #888; letter-spacing: 0.05em; text-transform: uppercase; }
          .icon  { font-size: 18px; line-height: 1; }
          .value { font-size: 28px; font-weight: 800; color: #f0f0f0; letter-spacing: -0.02em; line-height: 1; margin-bottom: 6px; }
          .footer { display: flex; align-items: center; gap: 8px; }
          .label { font-size: 13px; color: #666; }
          .delta { font-size: 12px; font-weight: 700; color: ${deltaColor}; }
        </style>
        <div class="card">
          <div class="header">
            <span class="title">${this._esc(title)}</span>
            ${icon ? `<span class="icon">${icon}</span>` : ''}
          </div>
          ${data.value  ? `<div class="value">${this._esc(String(data.value))}</div>` : ''}
          <div class="footer">
            ${data.label ? `<span class="label">${this._esc(data.label)}</span>` : ''}
            ${data.delta ? `<span class="delta">${deltaArrow} ${this._esc(data.delta)}</span>` : ''}
          </div>
        </div>
      `;
    }

    _esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  }

  customElements.define('cc-card', CcCard);
})();
