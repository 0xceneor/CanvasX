/**
 * CanvasX — Text to Page generator UI logic
 */

const form        = document.getElementById('gen-form');
const textarea    = document.getElementById('context');
const charCount   = document.getElementById('char-count');
const styleSelect = document.getElementById('style');
const titleInput  = document.getElementById('title');
const submitBtn   = document.getElementById('submit-btn');
const statusEl    = document.getElementById('status');
const progressBar = document.getElementById('progress-bar');
const codePreview = document.getElementById('code-preview');
const resultEl    = document.getElementById('result');
const resultUrl   = document.getElementById('result-url');
const resultFrame = document.getElementById('result-frame');
const copyBtn     = document.getElementById('copy-btn');
const regenBtn    = document.getElementById('regen-btn');
const recentList  = document.getElementById('recent-list');

// Character counter
textarea.addEventListener('input', () => {
    const n = textarea.value.length;
    charCount.textContent = n.toLocaleString();
    charCount.style.color = n > 4000 ? 'var(--accent)' : 'var(--text-muted)';
});

// Load recent canvases
async function loadRecent() {
    try {
        const res = await fetch('/api/canvas.php?action=list&limit=8');
        const data = await res.json();
        if (!Array.isArray(data) || data.length === 0) {
            recentList.innerHTML = '<p class="empty">No canvases yet.</p>';
            return;
        }
        recentList.innerHTML = data.map(c => `
            <a class="recent-item" href="${escHtml(c.url)}" target="_blank">
                <span class="recent-title">${escHtml(c.title)}</span>
                <span class="recent-id">${escHtml(c.id)}</span>
            </a>
        `).join('');
    } catch (e) {
        recentList.innerHTML = '<p class="empty">Could not load recent canvases.</p>';
    }
}

loadRecent();

// Form submit
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const context = textarea.value.trim();
    if (!context) return;

    setGenerating(true);
    clearResult();
    startWritingAnimation();

    try {
        const res = await fetch('/pipeline/generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                context,
                style: styleSelect.value,
                title: titleInput.value.trim(),
            })
        });

        const data = await res.json();

        if (!res.ok || !data.ok) {
            throw new Error(data.error || 'Generation failed');
        }

        stopWritingAnimation();
        showResult(data);
        loadRecent();

    } catch (err) {
        stopWritingAnimation();
        showError(err.message);
    } finally {
        setGenerating(false);
    }
});

// Regenerate
regenBtn?.addEventListener('click', () => {
    clearResult();
    form.dispatchEvent(new Event('submit'));
});

// Copy URL
copyBtn?.addEventListener('click', () => {
    navigator.clipboard.writeText(resultUrl.href).then(() => {
        copyBtn.textContent = 'Copied!';
        setTimeout(() => { copyBtn.textContent = 'Copy URL'; }, 2000);
    });
});

// ─── Animations ─────────────────────────────────────────────────────────────

let writingInterval = null;
let progress = 0;
const fakeLines = [
    '<!DOCTYPE html>',
    '<html lang="en">',
    '<head>',
    '  <meta charset="UTF-8">',
    '  <title>Generating...</title>',
    '  <link rel="preconnect" href="https://fonts.googleapis.com">',
    '  <style>',
    '    :root {',
    '      --bg: #050505;',
    '      --text: #ffffff;',
    '      --accent: #a855f7;',
    '    }',
    '    body { background: var(--bg); color: var(--text); }',
    '    .card { border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; }',
    '  </style>',
    '</head>',
    '<body>',
    '  <header class="site-header">',
    '    <h1>Building your canvas...</h1>',
    '  </header>',
    '  <main class="grid">',
    '    <!-- AI is writing your page -->',
];

function startWritingAnimation() {
    codePreview.textContent = '';
    codePreview.style.display = 'block';
    statusEl.textContent = 'AI is writing your page…';
    progress = 0;
    progressBar.style.width = '0%';

    let lineIndex = 0;
    let charIndex = 0;
    let currentText = '';

    writingInterval = setInterval(() => {
        // Animate progress bar (fake, up to 90%)
        if (progress < 88) {
            progress += Math.random() * 2.5;
            progressBar.style.width = Math.min(progress, 88) + '%';
        }

        if (lineIndex >= fakeLines.length) return;

        const line = fakeLines[lineIndex];
        if (charIndex < line.length) {
            currentText += line[charIndex];
            charIndex++;
        } else {
            currentText += '\n';
            lineIndex++;
            charIndex = 0;
        }

        codePreview.textContent = currentText;
        codePreview.scrollTop = codePreview.scrollHeight;
    }, 18);
}

function stopWritingAnimation() {
    clearInterval(writingInterval);
    writingInterval = null;
    progressBar.style.width = '100%';
    statusEl.textContent = 'Canvas ready!';
}

// ─── Result display ──────────────────────────────────────────────────────────

function showResult(data) {
    codePreview.style.display = 'none';
    resultEl.style.display = 'block';
    resultUrl.href = data.url;
    resultUrl.textContent = data.url;
    resultFrame.src = data.url;
    // Store for regen
    regenBtn.dataset.context = textarea.value;
    regenBtn.dataset.style = styleSelect.value;
}

function clearResult() {
    resultEl.style.display = 'none';
    resultUrl.href = '#';
    resultUrl.textContent = '';
    resultFrame.src = 'about:blank';
    codePreview.textContent = '';
    codePreview.style.display = 'none';
    statusEl.textContent = '';
    progressBar.style.width = '0%';
}

function showError(msg) {
    statusEl.textContent = 'Error: ' + msg;
    statusEl.style.color = '#ef4444';
    codePreview.style.display = 'none';
    setTimeout(() => { statusEl.style.color = ''; }, 4000);
}

function setGenerating(on) {
    submitBtn.disabled = on;
    submitBtn.textContent = on ? 'Generating…' : 'Generate Page';
    textarea.disabled = on;
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
