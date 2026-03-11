/**
 * Injects hand-written JavaScript into the cached Part 1 HTML.
 * Run: node stitch-js.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
const __dir = path.dirname(fileURLToPath(import.meta.url));

const html_path = path.join(__dir, '_part1.html');
if (!fs.existsSync(html_path)) { console.error('❌ _part1.html not found — run test-pipeline.mjs first'); process.exit(1); }

let html = fs.readFileSync(html_path, 'utf8');

// Extract from <!DOCTYPE if there's leading prose
const docIdx = html.indexOf('<!DOCTYPE');
if (docIdx > 0) html = html.slice(docIdx);

// ─── THE JAVASCRIPT ──────────────────────────────────────────────────────────
const JS = `
// ── Globals ──────────────────────────────────────────────────────────────────
Chart.defaults.color = '#7d8590';
Chart.defaults.borderColor = 'rgba(255,255,255,0.05)';
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 11;

const CHART_OPTS = {
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 900, easing: 'easeOutQuart' },
};

const GRID = { color: 'rgba(255,255,255,0.05)', drawBorder: false };
const TICKS = { color: '#7d8590', padding: 8 };

// ── Tab switching ─────────────────────────────────────────────────────────────
document.querySelectorAll('.nav-item[data-tab]').forEach(item => {
  item.addEventListener('click', () => {
    const target = item.dataset.tab;
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    item.classList.add('active');
    const panel = document.getElementById(target);
    if (panel) panel.classList.add('active');
    // Trigger chart resize after tab switch
    setTimeout(() => window.dispatchEvent(new Event('resize')), 50);
  });
});

// ── Timestamp ─────────────────────────────────────────────────────────────────
const tsEl = document.getElementById('timestamp');
if (tsEl) tsEl.textContent = new Date().toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });

// ── Progress bars ─────────────────────────────────────────────────────────────
setTimeout(() => {
  document.querySelectorAll('.progress-fill[data-width]').forEach(el => {
    el.style.width = el.dataset.width + '%';
  });
}, 300);

// ── CSS bar-fill height animation ─────────────────────────────────────────────
setTimeout(() => {
  document.querySelectorAll('.bar-fill[data-height]').forEach(el => {
    el.style.height = el.dataset.height + 'px';
  });
}, 400);

// ── Charts ────────────────────────────────────────────────────────────────────
function makeChart(id, config) {
  const el = document.getElementById(id);
  if (!el) return;
  return new Chart(el, config);
}

// Revenue trend (line)
makeChart('revenueChart', {
  type: 'line',
  data: {
    labels: ['Q1 FY25','Q2 FY25','Q3 FY25','Q4 FY25','Q1 FY26','Q2 FY26','Q3 FY26','Q4 FY26'],
    datasets: [{
      label: 'Quarterly Revenue ($B)',
      data: [12.5, 13.3, 14.1, 15.2, 15.8, 16.1, 16.7, 17.19],
      borderColor: '#2f81f7',
      backgroundColor: (ctx) => {
        const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, ctx.chart.height);
        g.addColorStop(0, 'rgba(47,129,247,0.18)');
        g.addColorStop(1, 'rgba(47,129,247,0)');
        return g;
      },
      borderWidth: 2,
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#2f81f7',
      pointRadius: 3,
      pointHoverRadius: 5,
    }]
  },
  options: { ...CHART_OPTS,
    scales: {
      x: { grid: GRID, ticks: TICKS },
      y: { grid: GRID, ticks: { ...TICKS, callback: v => '$' + v + 'B' }, beginAtZero: false },
    },
    plugins: { legend: { display: false } }
  }
});

// Segment breakdown (doughnut)
makeChart('segmentChart', {
  type: 'doughnut',
  data: {
    labels: ['Cloud Services','License','Support','Services'],
    datasets: [{
      data: [46, 22, 24, 8],
      backgroundColor: ['#2f81f7','#3fb950','#d29922','#484f58'],
      borderColor: '#161b22',
      borderWidth: 2,
      hoverBorderColor: '#1c2128',
    }]
  },
  options: { ...CHART_OPTS,
    plugins: {
      legend: {
        position: 'right',
        labels: { color: '#7d8590', boxWidth: 10, padding: 14, font: { size: 11 } }
      },
      tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + '%' } }
    },
    cutout: '65%',
  }
});

// Cloud growth comparison (horizontal bar)
makeChart('growthChart', {
  type: 'bar',
  data: {
    labels: ['Oracle OCI','Microsoft Azure','Google Cloud','Amazon AWS'],
    datasets: [{
      label: 'YoY Growth Rate (%)',
      data: [48, 29, 28, 17],
      backgroundColor: ['#2f81f7','rgba(47,129,247,0.55)','rgba(47,129,247,0.4)','rgba(47,129,247,0.25)'],
      borderColor:     ['#2f81f7','rgba(47,129,247,0.8)','rgba(47,129,247,0.6)','rgba(47,129,247,0.4)'],
      borderWidth: 1,
      borderRadius: 4,
    }]
  },
  options: { ...CHART_OPTS,
    indexAxis: 'y',
    scales: {
      x: { grid: GRID, ticks: { ...TICKS, callback: v => v + '%' }, max: 60 },
      y: { grid: { display: false }, ticks: TICKS },
    },
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.x + '% YoY growth' } }
    }
  }
});

// Revenue outlook (line — guidance vs analyst estimate)
makeChart('outlookChart', {
  type: 'line',
  data: {
    labels: ['FY2024','FY2025','FY2026E','FY2027T'],
    datasets: [
      {
        label: 'Oracle Guidance ($B)',
        data: [53, 66, 79, 90],
        borderColor: '#2f81f7',
        backgroundColor: 'rgba(47,129,247,0.08)',
        borderWidth: 2.5,
        fill: true,
        tension: 0.3,
        pointBackgroundColor: '#2f81f7',
        pointRadius: 4,
        pointHoverRadius: 6,
      },
      {
        label: 'Analyst Estimate ($B)',
        data: [53, 65, 78, 86.6],
        borderColor: '#484f58',
        backgroundColor: 'transparent',
        borderWidth: 1.5,
        borderDash: [4, 4],
        fill: false,
        tension: 0.3,
        pointBackgroundColor: '#484f58',
        pointRadius: 3,
        pointHoverRadius: 5,
      }
    ]
  },
  options: { ...CHART_OPTS,
    scales: {
      x: { grid: GRID, ticks: TICKS },
      y: { grid: GRID, ticks: { ...TICKS, callback: v => '$' + v + 'B' }, beginAtZero: false, min: 40 },
    },
    plugins: {
      legend: { labels: { color: '#7d8590', boxWidth: 12, padding: 16 } },
      tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': $' + ctx.parsed.y + 'B' } }
    }
  }
});
`;

// ─── INJECT ──────────────────────────────────────────────────────────────────
const PLACEHOLDER = '<script id="charts-js">// JS_PLACEHOLDER</script>';
const INJECT = `<script id="charts-js">\n${JS}\n</script>`;

if (html.includes(PLACEHOLDER)) {
  html = html.replace(PLACEHOLDER, INJECT);
  console.log('✓ Replaced JS_PLACEHOLDER');
} else {
  // Fallback: inject before </body>
  html = html.replace('</body>', INJECT + '\n</body>');
  console.log('⚠ Injected before </body> (placeholder not found)');
}

// Ensure Chart.js CDN
if (!html.includes('chart.umd')) {
  html = html.replace('</head>', '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>\n</head>');
  console.log('✓ Injected Chart.js CDN');
}

const outPath = path.join(__dir, 'test-output.html');
fs.writeFileSync(outPath, html, 'utf8');

const kb = (html.length / 1024).toFixed(1);
const charts = (html.match(/makeChart\('/g) || []).length;
const svgs   = (html.match(/<svg/g) || []).length;
console.log(`✓ Written: test-output.html  (${kb} KB, ${charts} charts wired, ${svgs} SVGs)`);
