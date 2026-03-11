/**
 * canvas-new JavaScript SDK
 * npm install canvas-new
 *
 * import canvas from 'canvas-new';
 * const c = await canvas.create({ title: 'My Dashboard', html: '<h1>Hello</h1>' });
 * console.log(c.url);
 */

export class CanvasNew {
  /**
   * @param {object} opts
   * @param {string} opts.baseUrl  Base URL of your canvas.new deployment
   */
  constructor({ baseUrl = 'http://localhost:8080' } = {}) {
    this.baseUrl = baseUrl.replace(/\/$/, '');
  }

  /**
   * Create a new canvas.
   * @param {object} params
   * @param {string}   params.html         Required. Raw HTML content (max 10MB)
   * @param {string}   [params.title]      Optional display name
   * @param {Array}    [params.frames]     Optional [{html, label}] for tab support
   * @param {string}   [params.webhook_url] Optional webhook for cc-form submissions
   * @returns {Promise<{id, url, embed_url, edit_token}>}
   */
  async create({ html, title, frames, webhook_url } = {}) {
    if (!html) throw new Error('html is required');
    return this._post('/api/create.php', { html, title, frames, webhook_url });
  }

  /**
   * Update a canvas HTML and/or title. Triggers SSE broadcast to all live viewers.
   * @param {object} params
   * @param {string}  params.id
   * @param {string}  params.edit_token
   * @param {string}  [params.html]
   * @param {string}  [params.title]
   * @returns {Promise<{ok, updated_at}>}
   */
  async update({ id, edit_token, html, title } = {}) {
    if (!id || !edit_token) throw new Error('id and edit_token are required');
    return this._post('/api/update.php', { id, edit_token, html, title });
  }

  /**
   * Delete a canvas.
   * @param {object} params
   * @param {string} params.id
   * @param {string} params.edit_token
   * @returns {Promise<{ok}>}
   */
  async delete({ id, edit_token } = {}) {
    if (!id || !edit_token) throw new Error('id and edit_token are required');
    return this._post('/api/delete.php', { id, edit_token });
  }

  /**
   * Get a canvas by ID.
   * @param {string} id
   * @returns {Promise<{id, title, html, frames, webhook_url, created_at, updated_at, views}>}
   */
  async get(id) {
    if (!id) throw new Error('id is required');
    const res = await fetch(`${this.baseUrl}/api/get.php?id=${encodeURIComponent(id)}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
    return data;
  }

  /**
   * List canvases with pagination.
   * @param {object} [opts]
   * @param {number} [opts.limit=50]
   * @param {number} [opts.offset=0]
   * @returns {Promise<{canvases: Array, total: number}>}
   */
  async list({ limit = 50, offset = 0 } = {}) {
    const res = await fetch(`${this.baseUrl}/api/list.php?limit=${limit}&offset=${offset}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
    return data;
  }

  /**
   * Generate a canvas from text/context using MiniMax M2.5 AI pipeline.
   * Requires the pipeline module to be deployed.
   * @param {object} params
   * @param {string} params.context
   * @param {string} [params.title]
   * @param {'auto'|'dashboard'|'report'|'tool'|'data'|'list'|'creative'} [params.style]
   * @returns {Promise<{ok, id, url, edit_token, title}>}
   */
  async generate({ context, title = '', style = 'auto' } = {}) {
    if (!context) throw new Error('context is required');
    return this._post('/pipeline/generate.php', { context, title, style });
  }

  // ── Private ───────────────────────────────────────────────────────────────

  async _post(path, body) {
    const res = await fetch(`${this.baseUrl}${path}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
    return data;
  }
}

// Default singleton instance — reads CANVAS_BASE_URL from env in Node.js
const defaultBaseUrl = (typeof process !== 'undefined' && process.env?.CANVAS_BASE_URL)
  ? process.env.CANVAS_BASE_URL
  : 'http://localhost:8080';

export default new CanvasNew({ baseUrl: defaultBaseUrl });
