/**
 * <cc-grid> — responsive CSS grid wrapper via slots
 * Attributes: cols="3" (default 2), gap="1rem"
 * Usage: wrap any children in <cc-grid cols="3">...</cc-grid>
 */
(function () {
  class CcGrid extends HTMLElement {
    connectedCallback() {
      const cols = parseInt(this.getAttribute('cols') || '2', 10);
      const gap  = this.getAttribute('gap') || '1rem';

      const shadow = this.attachShadow({ mode: 'open' });
      shadow.innerHTML = `
        <style>
          :host { display: block; }
          .grid {
            display: grid;
            grid-template-columns: repeat(${cols}, 1fr);
            gap: ${gap};
          }
          @media (max-width: 768px) {
            .grid { grid-template-columns: repeat(${Math.min(cols, 2)}, 1fr); }
          }
          @media (max-width: 480px) {
            .grid { grid-template-columns: 1fr; }
          }
        </style>
        <div class="grid"><slot></slot></div>
      `;
    }
  }

  customElements.define('cc-grid', CcGrid);
})();
