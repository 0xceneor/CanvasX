/**
 * canvas.new pipeline test — 2-part generation
 * Part 1: HTML + CSS + all content (no JS)
 * Part 2: All JavaScript given the structure from Part 1
 * Then stitched into one complete file.
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dir = path.dirname(fileURLToPath(import.meta.url));

// Load .env
const env = {};
fs.readFileSync(path.join(__dir, '.env'), 'utf8').split('\n').forEach(line => {
  line = line.trim();
  if (!line || line.startsWith('#')) return;
  const [k, ...rest] = line.split('=');
  env[k.trim()] = rest.join('=').trim();
});

const API_KEY = env.NVIDIA_API_KEY;
if (!API_KEY) { console.error('❌ NVIDIA_API_KEY not found'); process.exit(1); }

const CONTEXT = `
Oracle FY2026 Earnings Call — Analysis

Financial Highlights:
- Revenue: $17.19B (beat expectations)
- Next quarter growth: 19–21% expected
- Remaining Performance Obligations (RPO): $553B, up 325% YoY
- Stock: +8% after-hours

AI Cloud (OCI) — Core Driver:
- Cloud revenue growth: 46–50% next quarter
- Building AI clusters for OpenAI, Meta, hyperscalers
- Competing directly with AWS, Azure, Google Cloud

Long-Term Outlook:
- FY2027 revenue target: $90B (analysts estimated $86.6B)
- AI infrastructure demand sustains through 2027+
- Heavy datacenter capex — funded without new debt

Key Risks:
- High capex requirement for AI infra build-out (High impact, High probability)
- Hyperscaler competition: AWS, Microsoft, Google (High impact, Medium probability)
- Legacy software revenue declining as cloud replaces it (Medium impact, High probability)
- Execution risk on $553B RPO delivery (Medium impact, Low probability)

Competitive Landscape (Cloud Market):
- OCI: 46–50% growth rate, $17.19B total Oracle revenue, AI-optimized clusters
- AWS: ~17% growth, market leader, broadest services
- Azure: ~29% growth, enterprise + OpenAI partnership
- Google Cloud: ~28% growth, AI/ML focus, TPU advantage

Revenue Trajectory:
- FY2024: ~$53B
- FY2025: ~$66B
- FY2026: ~$79B (current year)
- FY2027 target: $90B
`;

// ─── DESIGN SEED — injected into Part 1 prompt ──────────────────────────────
// We give the model an exact CSS foundation to build on. It fills in content.
const CSS_SEED = `
:root {
  --bg: #0d1117; --surface: #161b22; --surface-2: #1c2128; --surface-3: #21262d;
  --border: rgba(255,255,255,0.08); --border-h: rgba(255,255,255,0.15);
  --text: #e6edf3; --text-muted: #7d8590; --text-dim: #484f58;
  --accent: #2f81f7; --accent-2: #1f6feb; --accent-dim: rgba(47,129,247,0.12);
  --green: #3fb950; --green-dim: rgba(63,185,80,0.12);
  --yellow: #d29922; --yellow-dim: rgba(210,153,34,0.12);
  --red: #f85149; --red-dim: rgba(248,81,73,0.12);
  --orange: #db6d28;
  --radius: 6px; --radius-lg: 10px;
  --font-display: 'Space Grotesk', sans-serif;
  --font-body: 'Inter', sans-serif;
  --font-mono: 'JetBrains Mono', monospace;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; overflow: hidden; }
body { background: var(--bg); color: var(--text); font-family: var(--font-body); font-size: 13px; display: grid; grid-template-rows: 44px 1fr 32px; grid-template-columns: 220px 1fr; grid-template-areas: "topbar topbar" "sidebar main" "statusbar statusbar"; }

/* TOPBAR */
.topbar { grid-area: topbar; display: flex; align-items: center; gap: 16px; padding: 0 20px; background: var(--surface); border-bottom: 1px solid var(--border); }
.topbar-ticker { font-family: var(--font-display); font-size: 15px; font-weight: 700; color: var(--text); letter-spacing: -0.02em; }
.topbar-price { font-family: var(--font-mono); font-size: 13px; color: var(--green); }
.topbar-change { font-size: 11px; background: var(--green-dim); color: var(--green); padding: 2px 8px; border-radius: 20px; font-weight: 600; }
.topbar-divider { width: 1px; height: 20px; background: var(--border); }
.topbar-meta { font-size: 11px; color: var(--text-muted); }
.topbar-spacer { flex: 1; }
.topbar-badge { font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--accent); background: var(--accent-dim); padding: 3px 10px; border-radius: 20px; border: 1px solid rgba(47,129,247,0.2); }

/* SIDEBAR */
.sidebar { grid-area: sidebar; overflow-y: auto; border-right: 1px solid var(--border); background: var(--surface); display: flex; flex-direction: column; }
.sidebar-section { padding: 14px 16px 10px; border-bottom: 1px solid var(--border); }
.sidebar-label { font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-dim); margin-bottom: 10px; }
.metric-row { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
.metric-row:last-child { margin-bottom: 0; }
.metric-name { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
.metric-name svg { opacity: 0.6; flex-shrink: 0; }
.metric-val { text-align: right; }
.metric-val .num { font-family: var(--font-mono); font-size: 13px; font-weight: 600; color: var(--text); }
.metric-val .sub { font-size: 10px; color: var(--text-muted); margin-top: 1px; }
.pill { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 20px; }
.pill.green { background: var(--green-dim); color: var(--green); }
.pill.red   { background: var(--red-dim);   color: var(--red);   }
.pill.yellow{ background: var(--yellow-dim); color: var(--yellow);}
.pill.blue  { background: var(--accent-dim); color: var(--accent);}
.sparkline  { margin-top: 6px; }

/* NAV TABS in sidebar */
.sidebar-nav { padding: 8px; }
.nav-item { display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: var(--radius); cursor: pointer; color: var(--text-muted); font-size: 12px; font-weight: 500; transition: all 150ms; margin-bottom: 2px; border: 1px solid transparent; }
.nav-item:hover { background: var(--surface-2); color: var(--text); }
.nav-item.active { background: var(--accent-dim); color: var(--accent); border-color: rgba(47,129,247,0.15); }
.nav-item svg { flex-shrink: 0; }
.nav-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); margin-left: auto; opacity: 0; }
.nav-item.active .nav-dot { opacity: 1; }

/* MAIN */
.main { grid-area: main; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
.tab-panel { display: none; flex-direction: column; gap: 16px; }
.tab-panel.active { display: flex; }

/* KPI ROW */
.kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.kpi-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 14px 16px; transition: border-color 200ms, transform 200ms; animation: fadeUp 350ms ease both; animation-delay: calc(var(--i, 0) * 60ms); }
.kpi-card:hover { border-color: var(--border-h); transform: translateY(-2px); }
.kpi-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.kpi-label { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
.kpi-label svg { opacity: 0.5; }
.kpi-value { font-family: var(--font-mono); font-size: 22px; font-weight: 700; color: var(--text); letter-spacing: -0.02em; line-height: 1; margin-bottom: 6px; }
.kpi-footer { display: flex; align-items: center; gap: 8px; }
.kpi-sub { font-size: 11px; color: var(--text-muted); }

/* CHART CARDS */
.chart-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.chart-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 16px; }
.chart-card.full { grid-column: 1 / -1; }
.chart-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.chart-title { font-size: 12px; font-weight: 600; color: var(--text); display: flex; align-items: center; gap: 8px; }
.chart-title svg { color: var(--accent); }
.chart-subtitle { font-size: 11px; color: var(--text-muted); }
canvas { width: 100% !important; }

/* GRID SECTIONS */
.section-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.section-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 16px; }
.section-card.full { grid-column: 1 / -1; }
.section-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.section-title svg { color: var(--accent); }

/* TABLE */
.data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.data-table th { text-align: left; padding: 8px 12px; font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-dim); border-bottom: 1px solid var(--border); }
.data-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.03); color: var(--text-muted); vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: var(--surface-2); color: var(--text); }
.data-table .num { font-family: var(--font-mono); color: var(--text); }
.data-table .hl { color: var(--accent); font-weight: 600; }

/* PROGRESS BAR */
.progress-wrap { margin-bottom: 16px; }
.progress-label { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 6px; }
.progress-label span:last-child { color: var(--text); font-family: var(--font-mono); font-weight: 600; }
.progress-track { height: 6px; background: var(--surface-3); border-radius: 3px; overflow: hidden; }
.progress-fill { height: 100%; background: linear-gradient(90deg, var(--accent-2), var(--accent)); border-radius: 3px; transition: width 1.2s cubic-bezier(0.16,1,0.3,1); width: 0; }

/* RISK MATRIX */
.risk-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.risk-card { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 12px; }
.risk-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
.risk-icon { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.risk-icon.high   { background: var(--red-dim); color: var(--red); }
.risk-icon.medium { background: var(--yellow-dim); color: var(--yellow); }
.risk-icon.low    { background: var(--green-dim); color: var(--green); }
.risk-title { font-size: 12px; font-weight: 600; color: var(--text); margin-bottom: 4px; }
.risk-desc  { font-size: 11px; color: var(--text-muted); line-height: 1.5; }
.risk-badges { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }

/* TIMELINE */
.timeline { position: relative; padding-left: 24px; }
.timeline::before { content: ''; position: absolute; left: 7px; top: 8px; bottom: 0; width: 1px; background: var(--border); }
.tl-event { position: relative; margin-bottom: 20px; animation: fadeUp 400ms ease both; animation-delay: calc(var(--i, 0) * 80ms); }
.tl-dot { position: absolute; left: -21px; top: 4px; width: 10px; height: 10px; border-radius: 50%; border: 2px solid var(--bg); }
.tl-date { font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-dim); margin-bottom: 4px; }
.tl-title { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 3px; }
.tl-desc  { font-size: 12px; color: var(--text-muted); line-height: 1.5; }

/* STATUSBAR */
.statusbar { grid-area: statusbar; display: flex; align-items: center; gap: 16px; padding: 0 16px; background: var(--surface); border-top: 1px solid var(--border); font-size: 11px; color: var(--text-dim); font-family: var(--font-mono); }
.statusbar-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); box-shadow: 0 0 6px var(--green); animation: pulse 2s infinite; }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.4; } }
.statusbar-spacer { flex: 1; }

/* ANIMATIONS */
@keyframes fadeUp { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }

/* BAR CHART (pure CSS) */
.bar-chart { display: flex; align-items: flex-end; gap: 8px; height: 80px; padding-top: 8px; }
.bar-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
.bar-fill { width: 100%; background: var(--accent); border-radius: 3px 3px 0 0; transition: height 1s cubic-bezier(0.16,1,0.3,1); position: relative; min-height: 4px; }
.bar-fill.green  { background: var(--green); }
.bar-fill.yellow { background: var(--yellow); }
.bar-fill.orange { background: var(--orange); }
.bar-label { font-size: 10px; color: var(--text-muted); text-align: center; white-space: nowrap; }
.bar-val { font-family: var(--font-mono); font-size: 10px; color: var(--text); font-weight: 600; }

/* SCROLLBAR */
::-webkit-scrollbar { width: 4px; } ::-webkit-scrollbar-track { background: transparent; } ::-webkit-scrollbar-thumb { background: #333; border-radius: 2px; }
`;

// ─── PART 1 PROMPT ───────────────────────────────────────────────────────────
const PART1_PROMPT = `You are an elite frontend engineer. Build a financial intelligence dashboard as a single complete HTML file.

## CRITICAL OUTPUT RULES
- Return ONLY raw HTML starting with <!DOCTYPE html>. Zero markdown, zero explanation, zero code fences.
- Part 1 of 2: Generate EVERYTHING except the JavaScript logic. End the file with <script id="charts-js">// JS_PLACEHOLDER</script></body></html>
- Use the EXACT CSS provided below — do not rewrite it, extend it only if needed with additional classes
- Google Fonts import: Space Grotesk + Inter + JetBrains Mono

## CSS FOUNDATION — USE THIS EXACTLY
<style>
${CSS_SEED}
</style>

## STRUCTURE TO BUILD
Exactly this grid layout (already defined in CSS):
- .topbar: ORCL ticker, price area, FY2026 badge, "Earnings Call Analysis" label
- .sidebar: 4 metric sections (Financials, Growth, Outlook, Signal) + nav items for 4 tabs
- .main: 4 tab-panels (id: tab-overview, tab-cloud, tab-outlook, tab-risks)
- .statusbar: live dot, "ORCL · FY2026 EARNINGS" left, timestamp right

## TAB CONTENT TO BUILD

### tab-overview
- .kpi-row with 4 .kpi-card (Revenue $17.19B, RPO $553B, Stock +8%, Cloud Growth +46–50%)
  - Each card: SVG icon top-right, value, pill badge, sub-label
- .chart-row: two canvas elements (id="revenueChart", id="segmentChart")
- Full-width .section-card with a .data-table showing quarterly revenue (Q1: $14.1B, Q2: $15.2B, Q3: $16.1B, Q4: $17.19B) with YoY columns

### tab-cloud
- .chart-card.full with canvas id="growthChart" (bar chart placeholder)
- .section-card.full with .data-table comparing OCI vs AWS vs Azure vs Google (columns: Provider, Growth Rate, Key Advantage, AI Focus)
- .section-grid: two cards — "Why OCI Wins" (bullet list with SVG check icons) and "Competitive Moats"

### tab-outlook
- .section-card.full with 4 .progress-wrap bars:
  - FY2024 Actual: $53B / $90B target (59%)
  - FY2025 Actual: $66B / $90B target (73%)
  - FY2026 Current: $79B / $90B target (88%)
  - FY2027 Target: $90B / $90B (100%)
- .section-card with .timeline (4 events: 2024 foundation, 2025 AI push, 2026 OCI dominance, 2027 $90B target)
- .chart-card with canvas id="outlookChart"

### tab-risks
- .section-title then .risk-grid with 4 .risk-card:
  1. High capex — high/high — red icon with SVG warning triangle
  2. Hyperscaler competition — high/medium — orange icon with SVG users
  3. Legacy decline — medium/high — yellow icon with SVG trend-down arrow
  4. RPO delivery execution — medium/low — yellow icon with SVG clock
- Full-width .section-card with a .bar-chart (pure CSS) showing risk scores visually

## SIDEBAR METRICS to populate
Financials: Revenue $17.19B, RPO $553B
Growth: Cloud +46%, YoY +19–21%
Outlook: FY27 Target $90B, Analyst Est $86.6B
Signal: Beat? Yes (pill green), Debt raised? No (pill green)

## SVG ICONS — INLINE ONLY, NO EMOJIS
All icons: fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" viewBox="0 0 16 16"
- Revenue: <polyline points="2,12 5,7 9,10 14,3"/> (trend up)
- Cloud: <path d="M12 12H4a4 4 0 010-8 5 5 0 019 2 3 3 0 01-1 6z"/>
- Risk warning: <path d="M8 2L2 13h12L8 2z"/><line x1="8" y1="7" x2="8" y2="10"/><circle cx="8" cy="12" r="0.5" fill="currentColor"/>
- Target: <circle cx="8" cy="8" r="6"/><circle cx="8" cy="8" r="3"/><circle cx="8" cy="8" r="0.5" fill="currentColor"/>
- Clock: <circle cx="8" cy="8" r="6"/><polyline points="8,4 8,8 11,10"/>

Now generate the complete HTML for the Oracle FY2026 earnings dashboard. Context:
${CONTEXT}`;

// ─── PART 2 PROMPT ───────────────────────────────────────────────────────────
function makePart2Prompt(part1Html) {
  // Extract canvas IDs and structure hints from part 1
  const canvasIds = [...part1Html.matchAll(/id="([^"]*[Cc]hart[^"]*)"/g)].map(m => m[1]);
  const progressFills = [...part1Html.matchAll(/class="progress-fill[^"]*"/g)].length;

  return `You are an elite frontend JavaScript engineer.

Below is a complete HTML page (Part 1). Your job: replace the placeholder comment inside <script id="charts-js"> with COMPLETE, WORKING JavaScript.

## WHAT TO WRITE (inside the script tag — JS only, no HTML):
1. Tab switching: clicking .nav-item sets active class on nav + corresponding .tab-panel
2. Chart.js configs for these canvas IDs found in the HTML: ${canvasIds.join(', ')}
3. Progress bar animation: after DOMContentLoaded, set each .progress-fill width to its data-width value with a short delay
4. Any .bar-fill elements: animate height from 0 to data-height on load

## CHART CONFIGS — use these exact datasets:

revenueChart (line):
- labels: ['Q1 FY25','Q2 FY25','Q3 FY25','Q4 FY25','Q1 FY26','Q2 FY26','Q3 FY26','Q4 FY26']
- data: [12.5, 13.3, 14.1, 15.2, 15.8, 16.1, 16.7, 17.19]
- color: #2f81f7, fill gradient below line, tension 0.4

segmentChart (doughnut):
- labels: ['Cloud Services','License','Support','Services']
- data: [46, 22, 24, 8]
- colors: ['#2f81f7','#3fb950','#d29922','#7d8590']
- legend position: right

growthChart (bar):
- labels: ['OCI (Oracle)','Google Cloud','Azure','AWS']
- data: [48, 28, 29, 17]
- colors: ['#2f81f7','#3fb950','#d29922','#7d8590']
- horizontal bar, sorted descending

outlookChart (line):
- labels: ['FY2024','FY2025','FY2026E','FY2027T']
- datasets:
  - Oracle Guidance: [53, 66, 79, 90] — color #2f81f7
  - Analyst Estimate: [53, 65, 78, 86.6] — color #7d8590, dashed

## CHART.JS GLOBAL DEFAULTS to set:
Chart.defaults.color = '#7d8590';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 11;

All charts: animation.duration=800, animation.easing='easeOutQuart', responsive=true, maintainAspectRatio=false
All scales: grid.color='rgba(255,255,255,0.05)', ticks.color='#7d8590'
All legends: labels.color='#7d8590', labels.boxWidth=10, labels.padding=12

## PROGRESS BARS
Each .progress-fill has a data-width attribute (e.g. data-width="88").
On DOMContentLoaded + 200ms delay, set el.style.width = el.dataset.width + '%'

## OUTPUT FORMAT
Return ONLY the JavaScript code — no HTML tags, no markdown, no explanation.
The code will be injected directly into <script id="charts-js">

Here is the Part 1 HTML (study the IDs and structure):
---
${part1Html.slice(0, 8000)}
---
(truncated for brevity — write JS based on the canvas IDs and structure above)`;
}

// ─── API CALL ─────────────────────────────────────────────────────────────────
async function generate(prompt, maxTokens, label) {
  console.log(`\n⚡ ${label}`);
  const res = await fetch('https://integrate.api.nvidia.com/v1/chat/completions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${API_KEY}` },
    body: JSON.stringify({
      model: 'minimaxai/minimax-m2.5',
      messages: [{ role: 'user', content: prompt }],
      temperature: 0.7,
      top_p: 0.9,
      max_tokens: maxTokens,
      stream: true,
    }),
  });

  if (!res.ok) {
    const err = await res.text();
    throw new Error(`API ${res.status}: ${err.slice(0, 200)}`);
  }

  let output = '';
  let tokens = 0;
  const reader = res.body.getReader();
  const dec = new TextDecoder();
  const t0 = Date.now();

  process.stdout.write('   ');
  while (true) {
    const { done, value } = await reader.read();
    if (done) break;
    for (const line of dec.decode(value, { stream: true }).split('\n')) {
      const t = line.trim();
      if (!t.startsWith('data: ') || t === 'data: [DONE]') continue;
      try {
        const delta = JSON.parse(t.slice(6)).choices?.[0]?.delta?.content ?? '';
        output += delta;
        tokens++;
        if (tokens % 200 === 0) process.stdout.write(`${tokens}…`);
      } catch (_) {}
    }
  }

  const secs = ((Date.now() - t0) / 1000).toFixed(1);
  const kb = (output.length / 1024).toFixed(1);
  console.log(`\n   ✓ ${tokens} tokens · ${kb} KB · ${secs}s`);
  return output;
}

// ─── STITCH ───────────────────────────────────────────────────────────────────
function stitch(part1, part2) {
  // Extract from markdown fences if present, then strip any leading prose
  const cleanHtml = s => {
    const fenced = s.match(/```(?:html)?\s*(<!DOCTYPE[\s\S]*?)```/i);
    if (fenced) return fenced[1].trim();
    const docIdx = s.indexOf('<!DOCTYPE');
    if (docIdx > 0) return s.slice(docIdx).trim();
    return s.trim();
  };

  const cleanJs = s => {
    // Extract from js/javascript fences
    const fenced = s.match(/```(?:javascript|js)\s*([\s\S]*?)```/i);
    if (fenced) return fenced[1].trim();
    // Strip any markdown fences generically
    const anyFence = s.match(/```[^\n]*\n([\s\S]*?)```/i);
    if (anyFence) return anyFence[1].trim();
    // Strip leading prose (anything before first JS keyword or comment)
    const jsStart = s.search(/^\s*(\/\/|\/\*|document|window|Chart|const|let|var|function|;)/m);
    if (jsStart > 0) return s.slice(jsStart).trim();
    return s.trim();
  };

  let html = cleanHtml(part1);
  let js   = cleanJs(part2);

  // Save parts for debugging
  fs.writeFileSync(path.join(__dir, '_part1.html'), html, 'utf8');
  fs.writeFileSync(path.join(__dir, '_part2.js'), js, 'utf8');

  // Simple exact string replacement
  const PLACEHOLDER = '<script id="charts-js">// JS_PLACEHOLDER</script>';
  if (html.includes(PLACEHOLDER)) {
    html = html.replace(PLACEHOLDER, `<script id="charts-js">\n${js}\n</script>`);
  } else {
    // Fallback: inject before </body>
    html = html.replace('</body>', `<script id="charts-js">\n${js}\n</script>\n</body>`);
  }

  // Inject Chart.js CDN before </head> if not present
  if (!html.includes('chart.umd')) {
    html = html.replace(
      '</head>',
      '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>\n</head>'
    );
  }

  return html;
}

// ─── MAIN ─────────────────────────────────────────────────────────────────────
const STITCH_ONLY = process.argv.includes('--stitch-only');

console.log('🚀 canvas.new — 2-part generation');
console.log('   Model: minimaxai/minimax-m2.5');
console.log('   Context: Oracle FY2026 Earnings Call');
if (STITCH_ONLY) console.log('   Mode: stitch-only (reusing cached parts)');

const t0 = Date.now();

let part1, part2;

if (STITCH_ONLY && fs.existsSync(path.join(__dir, '_part1.html')) && fs.existsSync(path.join(__dir, '_part2.js'))) {
  part1 = fs.readFileSync(path.join(__dir, '_part1.html'), 'utf8');
  part2 = fs.readFileSync(path.join(__dir, '_part2.js'), 'utf8');
  console.log(`   Loaded part1: ${(part1.length/1024).toFixed(1)} KB, part2: ${(part2.length/1024).toFixed(1)} KB`);
} else {
  part1 = await generate(PART1_PROMPT, 16000, 'Part 1/2 — HTML + CSS + Content');
  part2 = await generate(makePart2Prompt(part1), 8000, 'Part 2/2 — JavaScript + Charts');
}

console.log('\n🔧 Stitching parts...');
const final = stitch(part1, part2);

const outPath = path.join(__dir, 'test-output.html');
fs.writeFileSync(outPath, final, 'utf8');

const totalSecs = ((Date.now() - t0) / 1000).toFixed(0);
const kb = (final.length / 1024).toFixed(1);
const svgCount = (final.match(/<svg/g) || []).length;
const canvasCount = (final.match(/<canvas/g) || []).length;
const hasJs = final.includes('new Chart(');

console.log(`\n✅ Done in ${totalSecs}s`);
console.log(`   Total size:   ${kb} KB`);
console.log(`   SVG icons:    ${svgCount}`);
console.log(`   Chart canvas: ${canvasCount}`);
console.log(`   Charts wired: ${hasJs ? '✓' : '✗ (check JS)'}`);
console.log(`\n📄 Saved: test-output.html`);
