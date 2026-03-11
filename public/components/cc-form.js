/**
 * <cc-form> — fires cc-form-submit event on submit, then calls /api/form-submit.php
 * Attributes: title="string", webhook="url", submit_label="string"
 * Data: <script type="application/json">{"fields":[{"type":"text","label":"Name","name":"name","required":true}]}</script>
 * Field types: text, email, number, tel, url, date, select, textarea, checkbox, range
 */
(function () {
  class CcForm extends HTMLElement {
    connectedCallback() {
      const title        = this.getAttribute('title')        || '';
      const submitLabel  = this.getAttribute('submit_label') || 'Submit';

      let cfg = { fields: [] };
      const jsonEl = this.querySelector('script[type="application/json"]');
      if (jsonEl) {
        try { cfg = JSON.parse(jsonEl.textContent); } catch (_) {}
      }

      const shadow = this.attachShadow({ mode: 'open' });
      this._shadow = shadow;

      shadow.innerHTML = `
        <style>
          :host { display: block; }
          * { box-sizing: border-box; }
          .form-wrap {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px; padding: 24px;
          }
          h3 { font-size: 16px; font-weight: 700; color: #e0e0e0; margin-bottom: 20px; }
          .field { margin-bottom: 16px; }
          label { display: block; font-size: 12px; font-weight: 600; color: #888;
                  letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 6px; }
          input, select, textarea {
            width: 100%; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 7px; color: #e0e0e0;
            font-family: inherit; font-size: 14px; padding: 10px 12px;
            transition: border-color 200ms, box-shadow 200ms;
            appearance: none;
          }
          input:focus, select:focus, textarea:focus {
            outline: none; border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168,85,247,0.15);
          }
          textarea { resize: vertical; min-height: 80px; }
          input[type="range"] { padding: 6px 0; background: none; border: none; box-shadow: none; }
          .checkbox-row { display: flex; align-items: center; gap: 10px; }
          input[type="checkbox"] { width: auto; }
          .range-val { color: #888; font-size: 12px; margin-left: 8px; }
          button[type="submit"] {
            width: 100%; padding: 12px; background: #a855f7; color: #fff; border: none;
            border-radius: 8px; font-family: inherit; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: background 200ms, transform 150ms; margin-top: 8px;
          }
          button[type="submit"]:hover { background: #7c3aed; transform: translateY(-1px); }
          button[type="submit"]:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
          .msg { margin-top: 14px; padding: 10px 14px; border-radius: 8px; font-size: 13px; text-align: center; }
          .msg.success { background: rgba(52,211,153,0.12); color: #34d399; border: 1px solid rgba(52,211,153,0.2); }
          .msg.error   { background: rgba(239,68,68,0.12);  color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
        </style>
        <div class="form-wrap">
          ${title ? `<h3>${this._esc(title)}</h3>` : ''}
          <form id="ccf"></form>
          <div id="msg" class="msg" style="display:none"></div>
        </div>
      `;

      const form = shadow.getElementById('ccf');

      cfg.fields.forEach(f => {
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'field';

        const label = document.createElement('label');
        label.textContent = f.label || f.name;
        fieldDiv.appendChild(label);

        if (f.type === 'select') {
          const sel = document.createElement('select');
          sel.name = f.name; sel.required = !!f.required;
          (f.options || []).forEach(opt => {
            const o = document.createElement('option');
            o.value = o.textContent = opt;
            sel.appendChild(o);
          });
          fieldDiv.appendChild(sel);
        } else if (f.type === 'textarea') {
          const ta = document.createElement('textarea');
          ta.name = f.name; ta.required = !!f.required;
          if (f.placeholder) ta.placeholder = f.placeholder;
          fieldDiv.appendChild(ta);
        } else if (f.type === 'checkbox') {
          const row = document.createElement('div');
          row.className = 'checkbox-row';
          const cb = document.createElement('input');
          cb.type = 'checkbox'; cb.name = f.name; cb.id = f.name;
          const lbl = document.createElement('label');
          lbl.htmlFor = f.name; lbl.style.textTransform = 'none'; lbl.style.fontSize = '14px';
          lbl.textContent = f.label || f.name;
          row.appendChild(cb); row.appendChild(lbl);
          fieldDiv.innerHTML = ''; fieldDiv.appendChild(row);
        } else if (f.type === 'range') {
          const row = document.createElement('div');
          row.style.display = 'flex'; row.style.alignItems = 'center';
          const inp = document.createElement('input');
          inp.type = 'range'; inp.name = f.name;
          inp.min = f.min ?? 0; inp.max = f.max ?? 100; inp.step = f.step ?? 1;
          inp.value = f.default ?? f.min ?? 0;
          const val = document.createElement('span');
          val.className = 'range-val'; val.textContent = inp.value;
          inp.addEventListener('input', () => val.textContent = inp.value);
          row.appendChild(inp); row.appendChild(val);
          fieldDiv.appendChild(row);
        } else {
          const inp = document.createElement('input');
          inp.type = f.type || 'text'; inp.name = f.name;
          inp.required = !!f.required;
          if (f.placeholder) inp.placeholder = f.placeholder;
          fieldDiv.appendChild(inp);
        }

        form.appendChild(fieldDiv);
      });

      const btn = document.createElement('button');
      btn.type = 'submit'; btn.textContent = submitLabel;
      form.appendChild(btn);

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {};
        const fd = new FormData(form);
        fd.forEach((v, k) => data[k] = v);
        // Include unchecked checkboxes as false
        cfg.fields.filter(f => f.type === 'checkbox').forEach(f => {
          if (!(f.name in data)) data[f.name] = false;
        });

        btn.disabled = true;
        btn.textContent = 'Submitting…';

        this.dispatchEvent(new CustomEvent('cc-form-submit', {
          bubbles: true, composed: true, detail: { data },
        }));

        // Wait for success/error events from runtime.js
        const done = new Promise(resolve => {
          const onSuccess = () => { resolve('success'); this.removeEventListener('cc-form-success', onSuccess); };
          const onError   = (ev) => { resolve(ev.detail?.error || 'Error'); this.removeEventListener('cc-form-error', onError); };
          this.addEventListener('cc-form-success', onSuccess);
          this.addEventListener('cc-form-error', onError);
        });

        const result = await done;
        const msgEl = shadow.getElementById('msg');

        if (result === 'success') {
          msgEl.className = 'msg success'; msgEl.textContent = 'Submitted!'; msgEl.style.display = 'block';
          form.reset();
        } else {
          msgEl.className = 'msg error'; msgEl.textContent = result; msgEl.style.display = 'block';
        }

        btn.disabled = false;
        btn.textContent = submitLabel;
        setTimeout(() => msgEl.style.display = 'none', 5000);
      });
    }

    _esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  }

  customElements.define('cc-form', CcForm);
})();
