# CanvasX

A self-hosted, open-source alternative to `canvas.new` / `gui.new` — the visual output layer for AI agents.

Text or structured context goes in → MiniMax M2.5 (via NVIDIA API) generates a polished, interactive HTML page → stored as a live, shareable URL.

---

## What It Does

CanvasX turns raw text/data into beautiful, interactive web pages in seconds. It was designed as the **visual output layer for AI agents** — any agent can call the generate API, hand off its context, and get back a live URL that humans can view, interact with, and share.

Key capabilities:

- **AI pipeline** — POST context to `/pipeline/generate`, get back a live canvas URL
- **Live updates** — Server-Sent Events (SSE) push HTML changes to all viewers in real time
- **Multiplayer cursors** — WebSocket server shows who's viewing a canvas simultaneously
- **8 native web components** — drop `<cc-chart>`, `<cc-form>`, `<cc-table>`, `<cc-kanban>` etc. into any generated page
- **Edit tokens** — `tok_`-prefixed bearer tokens gate write access to each canvas
- **OG images** — `/og.php?id=xxx` generates 1200×630 preview images via PHP GD (no Chrome/headless)
- **SDKs** — JavaScript (ES module) and Python SDKs with a CLI included

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        AI Agent / CLI                           │
│              POST /pipeline/generate { context }                │
└────────────────────────────┬────────────────────────────────────┘
                             │
                    pipeline/generate.php
                             │
                    NVIDIA API (MiniMax M2.5)
                    streaming SSE → HTML
                             │
                    PostgreSQL (canvases table)
                             │
               ┌─────────────┴──────────────┐
               │                            │
         /c/{id}  (viewer)           /api/events.php
         canvas.php                  SSE stream (800ms poll)
               │                            │
         8 web components          multiplayer.js (WebSocket)
         runtime.js                ws/server.php (Ratchet)
```

---

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2, raw PDO (no framework, no ORM) |
| Database | PostgreSQL 15 |
| Web server | Nginx + PHP-FPM |
| AI | MiniMax M2.5 via NVIDIA OpenAI-compatible API |
| WebSocket | Ratchet (PHP) |
| Live updates | Server-Sent Events (SSE) |
| Frontend | Native `customElements` web components, Shadow DOM |
| OG images | PHP GD library |
| SDKs | Node.js ES module, Python 3 |

---

## Setup

### Requirements

- PHP 8.2+ with extensions: `pdo_pgsql`, `gd`, `curl`, `mbstring`, `pcntl`
- PostgreSQL 15+
- Nginx
- Composer (for Ratchet WebSocket server)
- Node.js 18+ (optional, for SDK / test scripts)
- An [NVIDIA API key](https://integrate.api.nvidia.com) with access to `minimaxai/minimax-m2.5`

### 1. Clone & configure

```bash
git clone https://github.com/0xceneor/CanvasX.git
cd CanvasX
cp .env.example .env
# Edit .env with your NVIDIA_API_KEY and PostgreSQL credentials
```

### 2. Database

```bash
# Create the database and user
psql -U postgres -c "CREATE USER canvasnew WITH PASSWORD 'yourpassword';"
psql -U postgres -c "CREATE DATABASE canvasnew OWNER canvasnew;"

# Run the schema
psql -U canvasnew -d canvasnew -f db/schema.sql
```

### 3. Nginx

Copy `nginx/canvas.conf` to `/etc/nginx/sites-available/canvasnew` and enable it:

```bash
sudo cp nginx/canvas.conf /etc/nginx/sites-available/canvasnew
sudo ln -s /etc/nginx/sites-available/canvasnew /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

Key routes the Nginx config handles:

| Pattern | Handler |
|---|---|
| `/c/{id}` | → `public/canvas.php?id={id}` |
| `/api/*` | → `public/api/*.php` |
| `/og` | → `public/og.php` |
| `/ws` | → WebSocket proxy to port `8080` |

### 4. WebSocket server (Ratchet)

Install Ratchet via Composer:

```bash
composer require cboden/ratchet
```

Start the server (or use the included Supervisor config):

```bash
php ws/server.php
# Runs on port 8080 by default
```

Using Supervisor:

```bash
sudo cp supervisor/ws.conf /etc/supervisor/conf.d/canvasnew-ws.conf
sudo supervisorctl reread && sudo supervisorctl update
```

### 5. Dev server (no Nginx)

For local development, a built-in PHP router is included:

```bash
php -S localhost:8080 router.php
```

---

## API Reference

All endpoints accept/return JSON. Edit operations require `Authorization: Bearer tok_...` header.

### Generate a canvas (AI pipeline)

```http
POST /pipeline/generate
Content-Type: application/json

{
  "context": "Your text, data, or structured content here",
  "style": "auto",   // auto | dashboard | report | tool | creative | data | list
  "title": ""        // optional, auto-detected from HTML <title> if omitted
}
```

Response:

```json
{
  "ok": true,
  "id": "aBcD1234",
  "url": "https://your-domain.com/c/aBcD1234",
  "embed_url": "https://your-domain.com/c/aBcD1234?embed=1",
  "edit_token": "tok_abc123...",
  "title": "My Canvas"
}
```

### Create a canvas (raw HTML)

```http
POST /api/create
Content-Type: application/json

{
  "html": "<!DOCTYPE html>...",
  "title": "My Canvas",
  "webhook_url": "https://example.com/webhook"  // optional
}
```

### Update a canvas

```http
POST /api/update
Authorization: Bearer tok_...
Content-Type: application/json

{
  "id": "aBcD1234",
  "html": "<!DOCTYPE html>..."
}
```

All connected viewers receive the new HTML via SSE within 800ms.

### SSE stream

```http
GET /api/events.php?id=aBcD1234
Accept: text/event-stream
```

Event format:

```
event: update
data: {"html":"<!DOCTYPE html>..."}

event: ping
data: {}
```

---

## Web Components

Drop these tags into any generated canvas HTML. They are loaded automatically by `public/canvas.php`.

| Component | Description |
|---|---|
| `<cc-chart>` | Chart.js wrapper — pass config as `<script type="application/json">` child |
| `<cc-form>` | Full-featured form — text, email, select, checkbox, range, date |
| `<cc-table>` | Data table with sort, pagination |
| `<cc-card>` | Styled content card |
| `<cc-grid>` | Responsive grid layout |
| `<cc-kanban>` | Drag-and-drop kanban board |
| `<cc-code>` | Syntax-highlighted code block |
| `<cc-timeline>` | Vertical timeline |

---

## SDKs

### JavaScript

```js
import CanvasX from './sdk/js/index.js';

const client = new CanvasX({ baseUrl: 'https://your-domain.com' });

const canvas = await client.generate({
  context: 'Q4 sales summary: revenue $2.1M, up 18% YoY...',
  style: 'dashboard',
});

console.log(canvas.url); // https://your-domain.com/c/aBcD1234
```

### Python

```python
from sdk.python.canvas_new import CanvasNew

client = CanvasNew(base_url='https://your-domain.com')

canvas = client.generate(
    context='Q4 sales summary: revenue $2.1M, up 18% YoY...',
    style='dashboard',
)

print(canvas['url'])
```

CLI:

```bash
python sdk/python/canvas_new.py generate --context "Your context here" --style dashboard
python sdk/python/canvas_new.py list
python sdk/python/canvas_new.py get aBcD1234
```

---

## Node.js Pipeline Test

A standalone Node.js test script is included to test the full AI generation pipeline without PHP/PostgreSQL:

```bash
node test-pipeline.mjs
```

This calls the NVIDIA API directly, generates a 2-part HTML page (Part 1: HTML/CSS/content, Part 2: JavaScript), stitches them together, and saves `test-output.html`.

To re-stitch from cached parts without re-calling the API:

```bash
node test-pipeline.mjs --stitch-only
# or
node stitch-js.mjs
```

---

## Database Schema

```sql
-- Main canvas store
canvases (
  id TEXT PRIMARY KEY,          -- nanoid(8)
  title TEXT,
  html TEXT NOT NULL,
  frames JSONB DEFAULT '[]',    -- multi-tab support
  edit_token TEXT NOT NULL,     -- tok_ prefixed
  webhook_url TEXT,
  views INTEGER DEFAULT 0,
  created_at TIMESTAMPTZ,
  updated_at TIMESTAMPTZ
)

-- Form submissions from <cc-form>
form_submissions (
  id SERIAL PRIMARY KEY,
  canvas_id TEXT REFERENCES canvases(id),
  data JSONB NOT NULL,
  submitted_at TIMESTAMPTZ
)

-- SSE event queue (polled every 800ms)
canvas_events (
  id SERIAL PRIMARY KEY,
  canvas_id TEXT REFERENCES canvases(id),
  html TEXT NOT NULL,
  created_at TIMESTAMPTZ
)
```

---

## cPanel / Shared Hosting

CanvasX can run on cPanel with some adjustments:

1. Use cPanel's **PostgreSQL Databases** tool to create the DB and user, then import `db/schema.sql`
2. Place files in `public_html/canvasx/` and set `public/` as the document root (or use `.htaccess` rewrites)
3. The included `.htaccess` handles routing for Apache
4. WebSocket server (`ws/server.php`) requires a persistent process — use **Cron Jobs** to keep it alive or a background process manager
5. SSE (`/api/events.php`) works fine on shared hosting
6. Set `NVIDIA_API_KEY` via cPanel's **Environment Variables** or hardcode in a local `.env` file outside `public_html`

---

## Design System

All generated pages use a GitHub-dark-inspired token system:

```css
--bg: #0d1117        /* page background */
--surface: #161b22   /* card/panel background */
--accent: #2f81f7    /* primary blue */
--green: #3fb950     /* positive/success */
--red: #f85149       /* negative/error */
--yellow: #d29922    /* warning */
```

Fonts: **Space Grotesk** (display) · **Inter** (body) · **JetBrains Mono** (numbers/code)

Icons: Inline SVG only — `fill="none" stroke="currentColor" stroke-width="1.5"` — never emojis.

---

## License

MIT
