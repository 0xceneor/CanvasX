/**
 * <cc-code> — syntax-highlighted code block via highlight.js from cdnjs
 * Attributes: lang="js|python|php|sql|bash|json|html|css", title="filename.js"
 * Content: text content inside the element is the code
 */
(function () {
  const HLJS_CSS = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css';
  const HLJS_JS  = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js';

  let hljsReady = null;

  function loadHljs(cb) {
    if (window.hljs) return cb();
    if (hljsReady) return hljsReady.then(cb);

    hljsReady = new Promise(resolve => {
      const link = document.createElement('link');
      link.rel = 'stylesheet'; link.href = HLJS_CSS;
      document.head.appendChild(link);

      const s = document.createElement('script');
      s.src = HLJS_JS;
      s.onload = () => resolve();
      document.head.appendChild(s);
    });
    hljsReady.then(cb);
  }

  class CcCode extends HTMLElement {
    connectedCallback() {
      const lang  = this.getAttribute('lang')  || 'plaintext';
      const title = this.getAttribute('title') || '';
      const code  = this.textContent.trim();

      const shadow = this.attachShadow({ mode: 'open' });
      this._shadow = shadow;

      shadow.innerHTML = `
        <style>
          :host { display: block; }
          .wrap {
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px; overflow: hidden;
            font-family: 'Courier New', monospace;
          }
          .header {
            display: flex; align-items: center; justify-content: space-between;
            background: #1a1a1a; padding: 9px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
          }
          .lang-badge {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            font-size: 11px; font-weight: 600; color: #666; text-transform: uppercase;
            letter-spacing: 0.06em;
          }
          .file-name { font-size: 12px; color: #aaa; }
          .copy-btn {
            background: none; border: 1px solid rgba(255,255,255,0.1);
            color: #888; font-size: 11px; padding: 3px 10px; border-radius: 5px;
            cursor: pointer; transition: all 150ms; font-family: inherit;
          }
          .copy-btn:hover { border-color: #a855f7; color: #a855f7; }
          pre { margin: 0 !important; border-radius: 0 !important; border: none !important; }
          pre code.hljs { font-size: 13px !important; line-height: 1.6 !important; }
        </style>
        <div class="wrap">
          <div class="header">
            <span class="file-name">${title ? this._esc(title) : `<span class="lang-badge">${this._esc(lang)}</span>`}</span>
            <button class="copy-btn" id="copy">Copy</button>
          </div>
          <pre><code class="language-${this._esc(lang)}" id="code"></code></pre>
        </div>
      `;

      shadow.getElementById('code').textContent = code;
      shadow.getElementById('copy').addEventListener('click', () => {
        navigator.clipboard.writeText(code).then(() => {
          const btn = shadow.getElementById('copy');
          btn.textContent = 'Copied!';
          setTimeout(() => btn.textContent = 'Copy', 2000);
        });
      });

      loadHljs(() => {
        if (window.hljs) {
          const el = shadow.getElementById('code');
          window.hljs.highlightElement(el);
        }
      });
    }

    _esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  }

  customElements.define('cc-code', CcCode);
})();
