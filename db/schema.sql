-- canvas.new PostgreSQL schema
-- Run: psql -U postgres -d canvasnew -f schema.sql

CREATE TABLE IF NOT EXISTS canvases (
    id          TEXT PRIMARY KEY,                          -- 8-char nanoid e.g. aB3xKp9m
    title       TEXT,                                      -- optional display name
    html        TEXT NOT NULL,                             -- raw HTML content
    frames      JSONB,                                     -- [{html, label}] for tab support
    edit_token  TEXT NOT NULL,                             -- tok_sha256 for auth
    webhook_url TEXT,                                      -- fires POST on cc-form submit
    embed       BOOLEAN DEFAULT false,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW(),
    views       INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS form_submissions (
    id           SERIAL PRIMARY KEY,
    canvas_id    TEXT REFERENCES canvases(id) ON DELETE CASCADE,
    data         JSONB NOT NULL,                           -- submitted form field key/values
    submitted_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS canvas_events (
    id         SERIAL PRIMARY KEY,
    canvas_id  TEXT REFERENCES canvases(id) ON DELETE CASCADE,
    html       TEXT NOT NULL,                              -- new HTML snapshot on update
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_canvases_updated ON canvases(updated_at DESC);
CREATE INDEX IF NOT EXISTS idx_events_canvas    ON canvas_events(canvas_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_submissions      ON form_submissions(canvas_id, submitted_at DESC);
