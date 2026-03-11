/**
 * <cc-kanban> — drag-and-drop kanban board, HTML5 drag API, no deps
 * Attributes: title="string"
 * Data: <script type="application/json">{"columns":[{"title":"Todo","cards":["Fix bug"]},{"title":"Done","cards":["Deploy"]}]}</script>
 */
(function () {
  class CcKanban extends HTMLElement {
    connectedCallback() {
      const title = this.getAttribute('title') || '';
      let cfg = { columns: [] };
      const jsonEl = this.querySelector('script[type="application/json"]');
      if (jsonEl) {
        try { cfg = JSON.parse(jsonEl.textContent); } catch (_) {}
      }

      this._state = cfg.columns.map(col => ({
        title: col.title,
        cards: [...(col.cards || [])],
      }));

      const shadow = this.attachShadow({ mode: 'open' });
      this._shadow = shadow;

      shadow.innerHTML = `
        <style>
          :host { display: block; }
          .board { display: flex; gap: 16px; overflow-x: auto; padding-bottom: 8px; }
          h2 { font-family: system-ui, sans-serif; font-size: 13px; font-weight: 700;
               color: #aaa; margin-bottom: 14px; letter-spacing: 0.04em; text-transform: uppercase; }
          .column {
            min-width: 220px; flex: 1;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 10px; padding: 14px;
          }
          .col-title { font-family: system-ui, sans-serif; font-size: 12px; font-weight: 700;
                       color: #888; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 12px; }
          .card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 7px; padding: 10px 12px;
            font-family: system-ui, sans-serif; font-size: 13px; color: #ccc;
            margin-bottom: 8px; cursor: grab; user-select: none;
            transition: transform 150ms, box-shadow 150ms, border-color 150ms;
          }
          .card:last-child { margin-bottom: 0; }
          .card:hover { border-color: rgba(168,85,247,0.4); transform: translateY(-1px); }
          .card.dragging { opacity: 0.4; cursor: grabbing; }
          .column.drag-over { border-color: #a855f7; background: rgba(168,85,247,0.05); }
          .drop-indicator {
            height: 2px; background: #a855f7; border-radius: 1px;
            margin-bottom: 8px; display: none;
          }
          .drop-indicator.visible { display: block; }
        </style>
        <div>
          ${title ? `<h2>${this._esc(title)}</h2>` : ''}
          <div class="board" id="board"></div>
        </div>
      `;

      this._render();
    }

    _render() {
      const board = this._shadow.getElementById('board');
      board.innerHTML = '';

      this._state.forEach((col, colIdx) => {
        const colEl = document.createElement('div');
        colEl.className = 'column';
        colEl.dataset.colIdx = colIdx;

        const titleEl = document.createElement('div');
        titleEl.className = 'col-title';
        titleEl.textContent = col.title;
        colEl.appendChild(titleEl);

        col.cards.forEach((card, cardIdx) => {
          const ind = document.createElement('div');
          ind.className = 'drop-indicator';
          colEl.appendChild(ind);

          const cardEl = document.createElement('div');
          cardEl.className = 'card';
          cardEl.textContent = card;
          cardEl.draggable = true;
          cardEl.dataset.colIdx  = colIdx;
          cardEl.dataset.cardIdx = cardIdx;

          cardEl.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', JSON.stringify({ colIdx, cardIdx }));
            cardEl.classList.add('dragging');
          });
          cardEl.addEventListener('dragend', () => cardEl.classList.remove('dragging'));

          colEl.appendChild(cardEl);
        });

        // Final drop indicator
        const lastInd = document.createElement('div');
        lastInd.className = 'drop-indicator';
        colEl.appendChild(lastInd);

        colEl.addEventListener('dragover', (e) => {
          e.preventDefault();
          colEl.classList.add('drag-over');
        });

        colEl.addEventListener('dragleave', () => colEl.classList.remove('drag-over'));

        colEl.addEventListener('drop', (e) => {
          e.preventDefault();
          colEl.classList.remove('drag-over');
          try {
            const { colIdx: srcCol, cardIdx: srcCard } = JSON.parse(e.dataTransfer.getData('text/plain'));
            const card = this._state[srcCol].cards.splice(srcCard, 1)[0];
            this._state[colIdx].cards.push(card);
            this._render();
          } catch (_) {}
        });

        board.appendChild(colEl);
      });
    }

    _esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  }

  customElements.define('cc-kanban', CcKanban);
})();
