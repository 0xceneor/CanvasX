/**
 * canvas.new JavaScript SDK
 * Usage:
 *   import CanvasNew from './sdk/canvas_new.js'
 *   const client = new CanvasNew({ baseUrl: 'https://yourdomain.com' })
 *   const page = await client.generate({ context: '...', title: 'My Page' })
 *   console.log(page.url)
 */

export default class CanvasNew {
    /**
     * @param {object} opts
     * @param {string} opts.baseUrl  Base URL of your CanvasX deployment
     */
    constructor({ baseUrl = 'http://localhost:8080' } = {}) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
    }

    /**
     * Generate a canvas page from text/context.
     * @param {object} params
     * @param {string} params.context  The text, data, or context to turn into a page
     * @param {string} [params.title]  Optional page title (auto-detected if omitted)
     * @param {'auto'|'dashboard'|'report'|'tool'|'data'|'list'|'creative'} [params.style]
     * @returns {Promise<{ok: boolean, id: string, url: string, edit_token: string, title: string}>}
     */
    async generate({ context, title = '', style = 'auto' }) {
        if (!context || !context.trim()) throw new Error('context is required');

        const res = await fetch(`${this.baseUrl}/pipeline/generate.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ context, title, style }),
        });

        const data = await res.json();
        if (!res.ok || !data.ok) {
            throw new Error(data.error || `Generation failed: HTTP ${res.status}`);
        }

        return data; // { ok, id, url, edit_token, title }
    }

    /**
     * List recent canvases.
     * @param {number} [limit=20]
     * @returns {Promise<Array<{id, title, created_at, url}>>}
     */
    async list(limit = 20) {
        const res = await fetch(`${this.baseUrl}/api/canvas.php?action=list&limit=${limit}`);
        if (!res.ok) throw new Error(`List failed: HTTP ${res.status}`);
        return res.json();
    }

    /**
     * Get canvas metadata (does not return HTML).
     * Use canvas.url to open the page.
     * @param {string} id
     * @returns {Promise<{id, title, created_at, url, edit_token}>}
     */
    async get(id) {
        const res = await fetch(`${this.baseUrl}/api/canvas.php?id=${encodeURIComponent(id)}&action=meta`);
        if (!res.ok) throw new Error(`Not found: ${id}`);
        return res.json();
    }

    /**
     * Delete a canvas.
     * @param {string} id
     * @param {string} editToken
     */
    async delete(id, editToken) {
        const res = await fetch(
            `${this.baseUrl}/api/canvas.php?id=${encodeURIComponent(id)}&token=${encodeURIComponent(editToken)}`,
            { method: 'DELETE' }
        );
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.error || 'Delete failed');
        return data;
    }
}

// CommonJS compat shim for Node.js require()
if (typeof module !== 'undefined') module.exports = CanvasNew;
