/**
 * <cc-table> — sortable table, vanilla JS, no deps
 * Attributes: title="string", sortable (boolean attr)
 * Data: <script type="application/json">{"headers":["Name","Score"],"rows":[["Alice",99],["Bob",87]]}</script>
 */
(function () {
  class CcTable extends HTMLElement {
    connectedCallback() {
      const title    = this.getAttribute('title') || '';
      const sortable = this.hasAttribute('sortable');

      let cfg = { headers: [], rows: [] };
      const jsonEl = this.querySelector('script[type="application/json"]');
      if (jsonEl) {
        try { cfg = JSON.parse(jsonEl.textContent); } catch (_) {}
      }

      this._data    = cfg.rows.map(r => [...r]);
      this._headers = cfg.headers;
      this._sortCol = null;
      this._sortAsc = true;

      const shadow = this.attachShadow({ mode: 'open' });

      shadow.innerHTML = `
        <style>
          :host { display: block; width: 100%; overflow-x: auto; }
          .wrap { font-family: system-ui, 'Instrument Sans', sans-serif; }
          h3 { font-size: 13px; font-weight: 600; color: #aaa; margin-bottom: 10px;
               letter-spacing: 0.04em; text-transform: uppercase; }
          table { width: 100%; border-collapse: collapse; font-size: 14px; }
          th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.07); }
          th { color: #888; font-size: 12px; font-weight: 600; letter-spacing: 0.05em;
               text-transform: uppercase; background: rgba(255,255,255,0.03); }
          th.sortable { cursor: pointer; user-select: none; }
          th.sortable:hover { color: #ccc; }
          th.sort-asc::after  { content: ' ↑'; color: #a855f7; }
          th.sort-desc::after { content: ' ↓'; color: #a855f7; }
          tr:last-child td { border-bottom: none; }
          tr:nth-child(even) td { background: rgba(255,255,255,0.015); }
          td { color: #ccc; }
        </style>
        <div class="wrap">
          ${title ? `<h3>${this._esc(title)}</h3>` : ''}
          <table><thead id="thead"></thead><tbody id="tbody"></tbody></table>
        </div>
      `;

      this._shadow = shadow;
      this._renderHead(sortable);
      this._renderBody();
    }

    _renderHead(sortable) {
      const tr = document.createElement('tr');
      this._headers.forEach((h, i) => {
        const th = document.createElement('th');
        th.textContent = h;
        if (sortable) {
          th.classList.add('sortable');
          th.addEventListener('click', () => this._sort(i));
        }
        if (this._sortCol === i) th.classList.add(this._sortAsc ? 'sort-asc' : 'sort-desc');
        tr.appendChild(th);
      });
      const thead = this._shadow.getElementById('thead');
      thead.innerHTML = '';
      thead.appendChild(tr);
    }

    _renderBody() {
      const tbody = this._shadow.getElementById('tbody');
      tbody.innerHTML = '';
      this._data.forEach(row => {
        const tr = document.createElement('tr');
        row.forEach(cell => {
          const td = document.createElement('td');
          td.textContent = cell ?? '';
          tr.appendChild(td);
        });
        tbody.appendChild(tr);
      });
    }

    _sort(colIndex) {
      if (this._sortCol === colIndex) {
        this._sortAsc = !this._sortAsc;
      } else {
        this._sortCol = colIndex;
        this._sortAsc = true;
      }
      this._data.sort((a, b) => {
        const va = a[colIndex], vb = b[colIndex];
        const na = parseFloat(va), nb = parseFloat(vb);
        const cmp = !isNaN(na) && !isNaN(nb)
          ? na - nb
          : String(va).localeCompare(String(vb));
        return this._sortAsc ? cmp : -cmp;
      });
      this._renderHead(true);
      this._renderBody();
    }

    _esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  }

  customElements.define('cc-table', CcTable);
})();
