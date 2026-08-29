<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Maersk API Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=JetBrains+Mono:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
  :root {
    --maersk-blue: #00243D;
    --maersk-light: #006EB6;
    --accent: #00B4D8;
    --accent2: #48CAE4;
    --gold: #F4A422;
    --success: #22C55E;
    --danger: #EF4444;
    --surface: #071828;
    --surface2: #0C2336;
    --surface3: #112D44;
    --border: rgba(0,180,216,0.15);
    --text: #E0F2FE;
    --muted: #7BA8C4;
  }
  * { box-sizing: border-box; }
  body {
    font-family: 'JetBrains Mono', monospace;
    background: var(--surface);
    color: var(--text);
    min-height: 100vh;
    margin: 0;
    overflow-x: hidden;
  }
  /* Grid background */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(rgba(0,180,216,0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,180,216,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 0;
  }
  /* Radial glow */
  body::after {
    content: '';
    position: fixed;
    top: -20%;
    left: 50%;
    transform: translateX(-50%);
    width: 800px;
    height: 500px;
    background: radial-gradient(ellipse, rgba(0,110,182,0.12) 0%, transparent 70%);
    pointer-events: none;
    z-index: 0;
  }
  .relative { position: relative; }
  .z1 { z-index: 1; }
  h1, h2, h3, .label { font-family: 'Syne', sans-serif; }

  /* Card style */
  .card {
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 12px;
    position: relative;
    overflow: hidden;
  }
  .card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--accent), transparent);
    opacity: 0.5;
  }

  /* Token box */
  .token-box {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 14px 16px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    color: var(--accent2);
    word-break: break-all;
    line-height: 1.7;
    position: relative;
  }

  /* Buttons */
  .btn-primary {
    background: linear-gradient(135deg, var(--maersk-light), var(--accent));
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    color: white;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(0,180,216,0.3); }
  .btn-primary:active { transform: translateY(0); }
  .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

  .btn-ghost {
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 6px 12px;
    color: var(--accent);
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .btn-ghost:hover { background: rgba(0,180,216,0.1); border-color: var(--accent); }

  .btn-copy {
    background: rgba(0,180,216,0.1);
    border: 1px solid rgba(0,180,216,0.3);
    border-radius: 6px;
    padding: 5px 10px;
    color: var(--accent);
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
  }
  .btn-copy:hover { background: rgba(0,180,216,0.2); }
  .btn-copy.copied { background: rgba(34,197,94,0.15); border-color: rgba(34,197,94,0.4); color: #22C55E; }

  /* Input */
  .inp {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    width: 100%;
    outline: none;
    transition: border-color 0.2s;
  }
  .inp:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,180,216,0.1); }
  .inp-password { letter-spacing: 0.2em; }

  /* Status badges */
  .badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-family: 'Syne', sans-serif;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .badge-success { background: rgba(34,197,94,0.15); color: #4ADE80; border: 1px solid rgba(34,197,94,0.3); }
  .badge-warning { background: rgba(244,164,34,0.15); color: #FBB045; border: 1px solid rgba(244,164,34,0.3); }
  .badge-info { background: rgba(0,180,216,0.15); color: var(--accent2); border: 1px solid rgba(0,180,216,0.3); }
  .badge-error { background: rgba(239,68,68,0.15); color: #F87171; border: 1px solid rgba(239,68,68,0.3); }

  /* Scope tags */
  .scope-tag {
    display: inline-block;
    background: rgba(0,110,182,0.2);
    border: 1px solid rgba(0,110,182,0.4);
    border-radius: 4px;
    padding: 2px 8px;
    font-size: 10px;
    color: var(--accent2);
    margin: 2px;
  }

  /* Timer bar */
  .timer-bar {
    height: 3px;
    background: var(--surface3);
    border-radius: 2px;
    overflow: hidden;
  }
  .timer-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--maersk-light), var(--accent));
    border-radius: 2px;
    transition: width 1s linear;
  }

  /* JSON viewer */
  .json-viewer {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 16px;
    font-size: 11px;
    line-height: 1.8;
    overflow: auto;
    max-height: 420px;
  }
  .json-key { color: #7DD3FC; }
  .json-string { color: #86EFAC; }
  .json-number { color: #FBB045; }
  .json-bool { color: #C084FC; }
  .json-null { color: #94A3B8; }

  /* Divider */
  .divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border), transparent);
    margin: 20px 0;
  }

  /* Spinner */
  @keyframes spin { to { transform: rotate(360deg); } }
  .spinner {
    width: 16px; height: 16px;
    border: 2px solid rgba(255,255,255,0.2);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
  }

  /* Pulse dot */
  @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(1.3)} }
  .pulse-dot { animation: pulse 2s ease-in-out infinite; }

  /* Fade in */
  @keyframes fadeInUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
  .fade-in { animation: fadeInUp 0.4s ease forwards; }
  .delay-1 { animation-delay: 0.1s; opacity: 0; }
  .delay-2 { animation-delay: 0.2s; opacity: 0; }
  .delay-3 { animation-delay: 0.3s; opacity: 0; }

  /* Tabs */
  .tab-btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-family: 'Syne', sans-serif;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
    color: var(--muted);
    background: transparent;
  }
  .tab-btn.active {
    background: rgba(0,180,216,0.15);
    border-color: rgba(0,180,216,0.4);
    color: var(--accent);
  }
  .tab-btn:hover:not(.active) { color: var(--text); background: rgba(255,255,255,0.05); }

  /* Section header */
  .section-label {
    font-family: 'Syne', sans-serif;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 8px;
  }

  /* Scrollbar */
  ::-webkit-scrollbar { width: 6px; height: 6px; }
  ::-webkit-scrollbar-track { background: var(--surface); }
  ::-webkit-scrollbar-thumb { background: var(--surface3); border-radius: 3px; }
  ::-webkit-scrollbar-thumb:hover { background: var(--accent); }

  /* Toast */
  #toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: var(--surface3);
    border: 1px solid var(--accent);
    border-radius: 8px;
    padding: 12px 20px;
    font-family: 'Syne', sans-serif;
    font-size: 12px;
    color: var(--accent);
    z-index: 9999;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s;
    pointer-events: none;
  }
  #toast.show { opacity: 1; transform: translateY(0); }
</style>
</head>
<body>
<div id="toast">✓ Copied to clipboard</div>

<div style="position:relative;z-index:1;" class="min-h-screen p-6">

  <!-- Header -->
  <div class="fade-in mb-8">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <!-- Maersk-style logo mark -->
        <div style="width:44px;height:44px;background:linear-gradient(135deg,#006EB6,#00B4D8);border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:18px;color:white;box-shadow:0 4px 16px rgba(0,180,216,0.3);">M</div>
        <div>
          <h1 style="font-size:22px;font-weight:800;letter-spacing:-0.02em;margin:0;color:white;">Maersk API Console</h1>
          <p style="font-size:11px;color:var(--muted);margin:2px 0 0;">OAuth 2.0 · Transport Documents · Bill of Lading</p>
        </div>
      </div>
      <div id="global-status" class="badge badge-info">
        <span class="pulse-dot" style="width:6px;height:6px;background:var(--accent2);border-radius:50%;display:inline-block;"></span>
        READY
      </div>
    </div>
  </div>

  <div class="grid gap-5" style="grid-template-columns:1fr 1fr;max-width:1200px;margin:0 auto;">

    <!-- ── LEFT COLUMN ── -->
    <div class="flex flex-col gap-5">

      <!-- Credentials Card -->
      <div class="card p-5 fade-in delay-1">
        <div class="section-label">🔐 API Credentials</div>
        <div class="flex flex-col gap-3">
          <div>
            <label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px;">Consumer Key</label>
            <div style="position:relative;">
              <input id="inp-key" class="inp" type="text" value="n4CPpQZo1qZy036yY852oYIIxdSMxKDN" placeholder="Consumer Key" />
              <button class="btn-copy" onclick="copyField('inp-key')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);">COPY</button>
            </div>
          </div>
          <div>
            <label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px;">Client Secret</label>
            <div style="position:relative;">
              <input id="inp-secret" class="inp inp-password" type="password" value="CLGc8Fo0vkJumlcT" placeholder="Client Secret" />
              <button class="btn-ghost" onclick="toggleSecret()" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);padding:4px 8px;font-size:10px;">SHOW</button>
            </div>
          </div>
          <div>
            <label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px;">Customer Code</label>
            <input id="inp-customer" class="inp" type="text" value="33104660082" placeholder="Customer Code" />
          </div>
        </div>

        <div class="divider"></div>

        <button id="btn-token" class="btn-primary w-full justify-center" onclick="getToken()">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 7h3a5 5 0 0 1 5 5 5 5 0 0 1-5 5h-3m-6 0H6a5 5 0 0 1-5-5 5 5 0 0 1 5-5h3"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          Generate Access Token
        </button>
      </div>

      <!-- Token Result Card -->
      <div id="token-card" class="card p-5" style="display:none;">
        <div class="flex items-center justify-between mb-3">
          <div class="section-label" style="margin:0;">🎫 Access Token</div>
          <div class="flex gap-2 items-center">
            <span id="token-status-badge" class="badge badge-success">ACTIVE</span>
            <button class="btn-ghost" onclick="refreshToken()" title="Refresh token">
              <svg id="refresh-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
              REFRESH
            </button>
          </div>
        </div>

        <!-- Token value -->
        <div style="position:relative;">
          <div id="token-display" class="token-box" style="max-height:80px;overflow:hidden;mask-image:linear-gradient(to bottom, black 60%, transparent);">
            —
          </div>
          <div style="position:absolute;top:8px;right:8px;display:flex;gap:4px;">
            <button id="copy-token-btn" class="btn-copy" onclick="copyToken()">COPY</button>
            <button class="btn-copy" onclick="toggleTokenExpand()" id="expand-btn">EXPAND</button>
          </div>
        </div>

        <!-- Expiry timer -->
        <div class="mt-3">
          <div class="flex justify-between mb-1">
            <span style="font-size:10px;color:var(--muted);">Expires in</span>
            <span id="timer-label" style="font-size:10px;color:var(--accent);font-weight:500;">—</span>
          </div>
          <div class="timer-bar">
            <div id="timer-fill" class="timer-fill" style="width:100%;"></div>
          </div>
        </div>

        <!-- Scopes -->
        <div class="mt-3">
          <div class="section-label">Granted Scopes</div>
          <div id="scope-list"></div>
        </div>

        <!-- Token meta -->
        <div class="divider"></div>
        <div class="grid" style="grid-template-columns:1fr 1fr;gap:8px;">
          <div style="background:var(--surface);border-radius:6px;padding:10px;">
            <div style="font-size:9px;color:var(--muted);margin-bottom:2px;">TOKEN TYPE</div>
            <div style="font-size:12px;color:var(--accent);">Bearer</div>
          </div>
          <div style="background:var(--surface);border-radius:6px;padding:10px;">
            <div style="font-size:9px;color:var(--muted);margin-bottom:2px;">HTTP CODE</div>
            <div id="token-http-code" style="font-size:12px;color:#4ADE80;">200 OK</div>
          </div>
        </div>
      </div>

    </div>

    <!-- ── RIGHT COLUMN ── -->
    <div class="flex flex-col gap-5">

      <!-- BOL Request Card -->
      <div class="card p-5 fade-in delay-2">
        <div class="section-label">📋 Transport Document</div>
        <div class="flex gap-3">
          <div style="flex:1;">
            <label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px;">B/L Reference Number</label>
            <input id="inp-bl" class="inp" type="text" value="265305368" placeholder="e.g. MAEU1234567890" />
          </div>
          <div style="padding-top:22px;">
            <button id="btn-bol" class="btn-primary" onclick="getBOL()" style="height:38px;padding:0 20px;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              FETCH
            </button>
          </div>
        </div>
        <div class="mt-2 flex items-center gap-2">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <span style="font-size:10px;color:var(--muted);">Endpoint: <span style="color:var(--accent2);">GET /ocean/document/v1/transport-documents/{ref}</span></span>
        </div>
      </div>

      <!-- BOL Response Card -->
      <div id="bol-card" class="card p-5" style="display:none;flex:1;">
        <div class="flex items-center justify-between mb-3">
          <div class="section-label" style="margin:0;">📦 Response</div>
          <div class="flex gap-2">
            <button class="tab-btn active" onclick="switchTab('formatted',this)">Formatted</button>
            <button class="tab-btn" onclick="switchTab('raw',this)">Raw JSON</button>
          </div>
        </div>

        <!-- Status bar -->
        <div class="flex items-center gap-3 mb-3 p-3" style="background:var(--surface);border-radius:8px;border:1px solid var(--border);">
          <span id="bol-http-badge" class="badge badge-success">200 OK</span>
          <span id="bol-url" style="font-size:10px;color:var(--muted);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">—</span>
          <button class="btn-copy" onclick="copyJSON()">COPY JSON</button>
        </div>

        <!-- Formatted view -->
        <div id="tab-formatted">
          <div id="formatted-content"></div>
        </div>

        <!-- Raw JSON view -->
        <div id="tab-raw" style="display:none;">
          <div id="raw-json" class="json-viewer"></div>
        </div>
      </div>

    </div>

  </div>

  <!-- Full width log -->
  <div id="log-card" class="card p-5 fade-in delay-3" style="max-width:1200px;margin:20px auto 0;">
    <div class="flex items-center justify-between mb-3">
      <div class="section-label" style="margin:0;">📡 Request Log</div>
      <button class="btn-ghost" onclick="clearLog()">CLEAR</button>
    </div>
    <div id="log" style="font-size:10px;line-height:1.8;color:var(--muted);min-height:40px;">
      <span style="color:var(--surface3);">Awaiting requests…</span>
    </div>
  </div>

</div>

<script>
let currentToken = null;
let tokenExpiry = null;
let timerInterval = null;
let bolData = null;
let tokenExpanded = false;

// ── UTILS ──────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2000);
}

function log(msg, type = 'info') {
  const colors = { info: '#7BA8C4', success: '#4ADE80', error: '#F87171', warn: '#FBB045', api: '#00B4D8' };
  const el = document.getElementById('log');
  const line = document.createElement('div');
  const time = new Date().toLocaleTimeString('en-US', { hour12: false });
  line.innerHTML = `<span style="color:#334F6A;">[${time}]</span> <span style="color:${colors[type]||colors.info};">${msg}</span>`;
  if (el.children.length === 1 && el.children[0].textContent === 'Awaiting requests…') el.innerHTML = '';
  el.appendChild(line);
  el.scrollTop = el.scrollHeight;
}

function clearLog() {
  document.getElementById('log').innerHTML = '<span style="color:var(--surface3);">Log cleared.</span>';
}

function copyField(id) {
  const val = document.getElementById(id).value;
  navigator.clipboard.writeText(val);
  showToast('✓ Copied!');
}

function toggleSecret() {
  const inp = document.getElementById('inp-secret');
  const btn = inp.nextElementSibling;
  if (inp.type === 'password') { inp.type = 'text'; btn.textContent = 'HIDE'; }
  else { inp.type = 'password'; btn.textContent = 'SHOW'; }
}

function copyToken() {
  if (!currentToken) return;
  navigator.clipboard.writeText(currentToken);
  const btn = document.getElementById('copy-token-btn');
  btn.textContent = 'COPIED!';
  btn.classList.add('copied');
  setTimeout(() => { btn.textContent = 'COPY'; btn.classList.remove('copied'); }, 2000);
  showToast('✓ Token copied!');
}

function toggleTokenExpand() {
  const box = document.getElementById('token-display');
  const btn = document.getElementById('expand-btn');
  tokenExpanded = !tokenExpanded;
  if (tokenExpanded) {
    box.style.maxHeight = 'none';
    box.style.maskImage = 'none';
    btn.textContent = 'COLLAPSE';
  } else {
    box.style.maxHeight = '80px';
    box.style.maskImage = 'linear-gradient(to bottom, black 60%, transparent)';
    btn.textContent = 'EXPAND';
  }
}

function copyJSON() {
  if (!bolData) return;
  navigator.clipboard.writeText(JSON.stringify(bolData, null, 2));
  showToast('✓ JSON copied!');
}

function switchTab(tab, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-formatted').style.display = tab === 'formatted' ? 'block' : 'none';
  document.getElementById('tab-raw').style.display = tab === 'raw' ? 'block' : 'none';
}

// ── TIMER ──────────────────────────────────────
function startTimer(expiresIn) {
  if (timerInterval) clearInterval(timerInterval);
  tokenExpiry = Date.now() + expiresIn * 1000;
  timerInterval = setInterval(() => {
    const remaining = Math.max(0, Math.round((tokenExpiry - Date.now()) / 1000));
    const pct = (remaining / expiresIn) * 100;
    document.getElementById('timer-label').textContent = remaining > 3600
      ? Math.floor(remaining/3600) + 'h ' + Math.floor((remaining%3600)/60) + 'm'
      : remaining + 's';
    document.getElementById('timer-fill').style.width = pct + '%';
    const fill = document.getElementById('timer-fill');
    if (pct < 20) fill.style.background = 'linear-gradient(90deg,#EF4444,#F97316)';
    else if (pct < 50) fill.style.background = 'linear-gradient(90deg,#F59E0B,#FBB045)';
    else fill.style.background = 'linear-gradient(90deg,#006EB6,#00B4D8)';
    if (remaining === 0) {
      clearInterval(timerInterval);
      document.getElementById('token-status-badge').className = 'badge badge-error';
      document.getElementById('token-status-badge').textContent = 'EXPIRED';
      log('⚠ Token expired', 'warn');
    }
  }, 1000);
}

// ── SYNTAX HIGHLIGHT ──────────────────────────
function syntaxHighlight(json) {
  if (typeof json !== 'string') json = JSON.stringify(json, null, 2);
  return json
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, match => {
      if (/^"/.test(match)) {
        if (/:$/.test(match)) return `<span class="json-key">${match}</span>`;
        return `<span class="json-string">${match}</span>`;
      }
      if (/true|false/.test(match)) return `<span class="json-bool">${match}</span>`;
      if (/null/.test(match)) return `<span class="json-null">${match}</span>`;
      return `<span class="json-number">${match}</span>`;
    });
}

// ── FORMATTED VIEW ──────────────────────────
function renderFormatted(data) {
  const si = data.shippingInstruction || {};
  const parties = {};
  (si.documentParties || []).forEach(p => { parties[p.partyFunction] = p.party; });
  const cargo = (si.consignmentItems || [])[0] || {};
  const eq = (si.utilizedTransportEquipments || [])[0] || {};
  const transports = data.transports || [];
  const charges = data.charges || [];

  const infoRow = (label, val, accent) => `
    <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:6px 0;border-bottom:1px solid rgba(0,180,216,0.06);">
      <span style="font-size:10px;color:var(--muted);min-width:160px;">${label}</span>
      <span style="font-size:11px;color:${accent||'var(--text)'};text-align:right;word-break:break-word;max-width:260px;">${val||'—'}</span>
    </div>`;

  const section = (title, icon, content) => `
    <div style="margin-bottom:14px;">
      <div style="font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:0.1em;color:var(--accent);margin-bottom:8px;display:flex;align-items:center;gap:6px;">
        <span>${icon}</span> ${title}
      </div>
      <div style="background:var(--surface);border-radius:8px;padding:4px 12px;border:1px solid var(--border);">${content}</div>
    </div>`;

  // Route viz
  const stages = transports.map((t,i) => {
    const icons = {TRUCK:'🚛',RAIL:'🚂',VESSEL:'🚢'};
    return `<div style="display:flex;align-items:center;gap:0;">
      <div style="text-align:center;min-width:80px;">
        <div style="font-size:9px;color:var(--muted);">${t.loadLocation?.locationName?.trim()||t.loadLocation?.UNLocationCode||'—'}</div>
        <div style="font-size:9px;color:var(--muted);margin-top:1px;">${(t.plannedDepartureDate||'').slice(5)||''}</div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:center;flex:1;min-width:60px;">
        <span style="font-size:14px;">${icons[t.modeOfTransport]||'➡'}</span>
        <div style="font-size:8px;color:var(--accent2);margin-top:1px;">${t.modeOfTransport}</div>
      </div>
      ${i===transports.length-1?`<div style="text-align:center;min-width:80px;">
        <div style="font-size:9px;color:var(--muted);">${t.dischargeLocation?.locationName?.trim()||t.dischargeLocation?.UNLocationCode||'—'}</div>
        <div style="font-size:9px;color:var(--muted);margin-top:1px;">${(t.plannedArrivalDate||'').slice(5)||''}</div>
      </div>`:''}
    </div>`;
  }).join(`<div style="width:1px;height:20px;background:var(--border);margin:0 2px;"></div>`);

  const chargesTotal = charges.reduce((s,c) => s + (c.currencyAmount||0), 0);
  const chargeRows = charges.map(c => `
    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid rgba(0,180,216,0.05);font-size:10px;">
      <span style="color:var(--muted);">${c.chargeType}</span>
      <span style="color:var(--text);">$${c.currencyAmount.toLocaleString()}</span>
    </div>`).join('');

  return `
    ${section('Shipment Overview','📄',
      infoRow('B/L Reference', `<span style="color:var(--accent);font-weight:600;">${data.transportDocumentReference}</span>`) +
      infoRow('Status', `<span class="badge badge-${si.documentStatus==='APPR'?'success':'warning'}">${si.documentStatus}</span>`) +
      infoRow('Vessel', data.vesselName) +
      infoRow('Voyage Number', data.carrierExportVoyageNumber) +
      infoRow('Shipped On Board', data.shippedOnBoardDate) +
      infoRow('Created', new Date(data.transportDocumentCreatedDateTime).toLocaleString()) +
      infoRow('Updated', new Date(data.transportDocumentUpdatedDateTime).toLocaleString())
    )}

    ${section('Route','🗺',`
      <div style="padding:10px 0;overflow-x:auto;">
        <div style="display:flex;align-items:center;min-width:400px;">${stages}</div>
      </div>
    `)}

    ${section('Cargo','📦',
      infoRow('Description', cargo.descriptionOfGoods, '#FBB045') +
      infoRow('Container', (eq.equipment?.equipmentReference||'—') + ' · ' + (eq.equipment?.ISOEquipmentCode||'')) +
      infoRow('Gross Weight', (cargo.weight||'—') + ' ' + (cargo.weightUnit||'')) +
      infoRow('Packages', cargo.cargoItems?.[0]?.numberOfPackages) +
      infoRow('HS Code', cargo.hscode) +
      infoRow('Seal', si.utilizedTransportEquipments?.[0]?.seals?.[0]?.sealNumber)
    )}

    ${section('Shipper (OS)','🏢',
      infoRow('Name', parties.OS?.partyName, '#E2E8F0') +
      infoRow('Address', parties.OS?.displayedAddresses?.[0]?.addressLine?.replace(/\r\n/g,', ')) +
      infoRow('Tax Ref', parties.OS?.taxReference1) +
      infoRow('Email', parties.OS?.partyContactDetails?.[0]?.email, var_accent2())
    )}

    ${section('Consignee (CN)','📬',
      infoRow('Name', parties.CN?.partyName, '#E2E8F0') +
      infoRow('Address', parties.CN?.displayedAddresses?.[0]?.addressLine?.replace(/\r\n/g,', ')) +
      infoRow('Email', parties.CN?.partyContactDetails?.[0]?.email, var_accent2())
    )}

    ${section('Charges','💰',
      chargeRows +
      `<div style="display:flex;justify-content:space-between;padding:8px 0 4px;font-size:11px;">
        <span style="font-family:'Syne',sans-serif;font-weight:700;color:var(--accent);">TOTAL</span>
        <span style="font-family:'Syne',sans-serif;font-weight:700;color:var(--gold);">$${chargesTotal.toLocaleString()}</span>
       </div>`
    )}
  `;
}

function var_accent2() { return 'var(--accent2)'; }

// ── GET TOKEN ──────────────────────────────────
async function getToken() {
  const key = document.getElementById('inp-key').value.trim();
  const secret = document.getElementById('inp-secret').value.trim();
  const btn = document.getElementById('btn-token');

  if (!key || !secret) { log('✗ Missing credentials', 'error'); return; }

  btn.disabled = true;
  btn.innerHTML = '<div class="spinner"></div> Authenticating…';
  log('→ POST /customer-identity/oauth/v2/access_token', 'api');
  log('  grant_type: client_credentials', 'info');

  try {
    const res = await fetch('https://api.maersk.com/customer-identity/oauth/v2/access_token', {
      method: 'POST',
      headers: {
        'Consumer-Key': key,
        'Content-Type': 'application/x-www-form-urlencoded',
        'Cache-Control': 'no-cache',
      },
      body: new URLSearchParams({ grant_type: 'client_credentials', client_id: key, client_secret: secret }),
    });

    const data = await res.json();
    log(`← ${res.status} ${res.statusText}`, res.ok ? 'success' : 'error');

    if (!res.ok) {
      log(`✗ ${JSON.stringify(data)}`, 'error');
      document.getElementById('global-status').className = 'badge badge-error';
      document.getElementById('global-status').innerHTML = '<span style="width:6px;height:6px;background:#F87171;border-radius:50%;display:inline-block;"></span> AUTH FAILED';
      return;
    }

    currentToken = data.access_token;
    const expiresIn = data.expires_in || 7200;

    // Show token card
    const card = document.getElementById('token-card');
    card.style.display = 'block';
    card.classList.add('fade-in');

    document.getElementById('token-display').textContent = currentToken;
    document.getElementById('token-http-code').textContent = res.status + ' OK';
    document.getElementById('token-status-badge').className = 'badge badge-success';
    document.getElementById('token-status-badge').textContent = 'ACTIVE';

    // Scopes
    const scopes = (data.scope || '').split(' ').filter(Boolean);
    document.getElementById('scope-list').innerHTML = scopes.map(s => `<span class="scope-tag">${s.split('.').pop()}</span>`).join('');

    startTimer(expiresIn);

    document.getElementById('global-status').className = 'badge badge-success';
    document.getElementById('global-status').innerHTML = '<span class="pulse-dot" style="width:6px;height:6px;background:#4ADE80;border-radius:50%;display:inline-block;"></span> AUTHENTICATED';

    log(`✓ Token acquired · Expires in ${expiresIn}s · ${scopes.length} scopes`, 'success');

  } catch (e) {
    log(`✗ Network error: ${e.message}`, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 7h3a5 5 0 0 1 5 5 5 5 0 0 1-5 5h-3m-6 0H6a5 5 0 0 1-5-5 5 5 0 0 1 5-5h3"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Generate Access Token';
  }
}

async function refreshToken() {
  const icon = document.getElementById('refresh-icon');
  icon.style.animation = 'spin 0.7s linear infinite';
  await getToken();
  setTimeout(() => icon.style.animation = '', 1000);
}

// ── GET BOL ──────────────────────────────────
async function getBOL() {
  if (!currentToken) {
    log('✗ No active token — generate a token first', 'warn');
    showToast('⚠ Generate a token first');
    return;
  }

  const ref = document.getElementById('inp-bl').value.trim();
  if (!ref) { log('✗ Missing B/L reference', 'error'); return; }

  const btn = document.getElementById('btn-bol');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner"></div>';

  const url = `https://api.maersk.com/ocean/document/v1/transport-documents/${ref}`;
  const key = document.getElementById('inp-key').value.trim();

  log(`→ GET /ocean/document/v1/transport-documents/${ref}`, 'api');

  try {
    const res = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${currentToken}`,
        'Consumer-Key': key,
        'API-Version': '1',
        'Accept': 'application/json',
      }
    });

    const data = await res.json();
    log(`← ${res.status} ${res.statusText}`, res.ok ? 'success' : 'error');

    // Show card
    const card = document.getElementById('bol-card');
    card.style.display = 'block';
    card.classList.add('fade-in');

    // URL & status
    document.getElementById('bol-url').textContent = url;
    const badge = document.getElementById('bol-http-badge');
    badge.textContent = res.status + (res.ok ? ' OK' : ' Error');
    badge.className = 'badge ' + (res.ok ? 'badge-success' : 'badge-error');

    if (res.ok) {
      bolData = data;
      document.getElementById('formatted-content').innerHTML = renderFormatted(data);
      document.getElementById('raw-json').innerHTML = `<pre>${syntaxHighlight(data)}</pre>`;
      log(`✓ B/L ${ref} retrieved · ${JSON.stringify(data).length} bytes`, 'success');
    } else {
      document.getElementById('formatted-content').innerHTML = `<div style="color:#F87171;padding:12px;font-size:11px;">${JSON.stringify(data, null, 2)}</div>`;
      document.getElementById('raw-json').innerHTML = `<pre>${syntaxHighlight(data)}</pre>`;
      log(`✗ ${JSON.stringify(data)}`, 'error');
    }

  } catch (e) {
    log(`✗ Network error: ${e.message}`, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg> FETCH';
  }
}

// ── KEYBOARD SHORTCUTS ──────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Enter' && e.ctrlKey) getToken();
});
</script>
</body>
</html>
