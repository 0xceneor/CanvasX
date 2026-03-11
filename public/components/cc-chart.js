/**
 * <cc-chart> — wraps Chart.js from cdnjs
 * Attributes: type="bar|line|pie|doughnut|radar", title="string"
 * Data: <script type="application/json">{"labels":["A","B"],"data":[10,20],"color":"#6366f1"}</script>
 */
(function () {
  const CHART_JS_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js';

  function loadChartJs(cb) {
    if (window.Chart) return cb();
    const s = document.createElement('script');
    s.src = CHART_JS_CDN;
    s.onload = cb;
    document.head.appendChild(s);
  }

  class CcChart extends HTMLElement {
    connectedCallback() {
      const type  = this.getAttribute('type')  || 'bar';
      const title = this.getAttribute('title') || '';

      let cfg = {};
      const jsonEl = this.querySelector('script[type="application/json"]');
      if (jsonEl) {
        try { cfg = JSON.parse(jsonEl.textContent); } catch (_) {}
      }

      const shadow = this.attachShadow({ mode: 'open' });
      shadow.innerHTML = `
        <style>
          :host { display: block; width: 100%; }
          .wrap { background: transparent; padding: 8px 0; }
          h3 { font-family: system-ui, sans-serif; font-size: 13px; font-weight: 600;
               color: #aaa; margin-bottom: 10px; letter-spacing: 0.04em; text-transform: uppercase; }
          canvas { width: 100% !important; }
        </style>
        <div class="wrap">
          ${title ? `<h3>${this._esc(title)}</h3>` : ''}
          <canvas></canvas>
        </div>
      `;

      loadChartJs(() => {
        const canvas = shadow.querySelector('canvas');
        const color  = cfg.color || '#a855f7';

        const datasets = Array.isArray(cfg.datasets)
          ? cfg.datasets
          : [{ label: title || 'Data', data: cfg.data || [], backgroundColor: color, borderColor: color, borderWidth: 2 }];

        new window.Chart(canvas, {
          type,
          data: { labels: cfg.labels || [], datasets },
          options: {
            responsive: true,
            animation: { duration: 800, easing: 'easeOutQuart' },
            plugins: {
              legend: { labels: { color: '#aaa', font: { family: 'system-ui' } } },
              title: { display: false },
            },
            scales: ['bar','line'].includes(type) ? {
              x: { ticks: { color: '#888' }, grid: { color: 'rgba(255,255,255,0.05)' } },
              y: { ticks: { color: '#888' }, grid: { color: 'rgba(255,255,255,0.05)' } },
            } : {},
          },
        });
      });
    }

    _esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  }

  customElements.define('cc-chart', CcChart);
})();
