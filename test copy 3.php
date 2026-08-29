<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Maersk API Explorer</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=IBM+Plex+Mono:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
:root {
  --bg: #050E18;
  --s1: #081627;
  --s2: #0C1F35;
  --s3: #112840;
  --s4: #1A3A55;
  --blue: #006EB6;
  --cyan: #00B4D8;
  --cyan2: #48CAE4;
  --gold: #F4A422;
  --green: #22C55E;
  --red: #EF4444;
  --orange: #F97316;
  --text: #D9EEF8;
  --muted: #6A9BBF;
  --border: rgba(0,180,216,0.12);
  --border2: rgba(0,180,216,0.22);
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'IBM Plex Mono',monospace;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,180,216,0.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,180,216,0.025) 1px,transparent 1px);background-size:48px 48px;pointer-events:none;z-index:0}
.z1{position:relative;z-index:1}
h1,h2,.label,.btn-label{font-family:'Syne',sans-serif}

/* Layout */
.shell{display:grid;grid-template-columns:300px 1fr;grid-template-rows:auto 1fr;min-height:100vh;gap:0}
.topbar{grid-column:1/-1;border-bottom:1px solid var(--border);background:rgba(5,14,24,0.95);backdrop-filter:blur(12px);padding:14px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.sidebar{border-right:1px solid var(--border);background:var(--s1);display:flex;flex-direction:column;overflow-y:auto;min-height:calc(100vh - 57px)}
.main{overflow-y:auto;padding:24px;display:flex;flex-direction:column;gap:20px}

/* Cards */
.card{background:var(--s2);border:1px solid var(--border);border-radius:10px;position:relative;overflow:hidden}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(0,180,216,0.4),transparent)}
.card-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.card-body{padding:16px}

/* Inputs */
.inp{background:var(--bg);border:1px solid var(--border2);border-radius:7px;padding:9px 12px;color:var(--text);font-family:'IBM Plex Mono',monospace;font-size:12px;width:100%;outline:none;transition:all .2s}
.inp:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,180,216,0.1)}
.inp::placeholder{color:var(--s4)}
select.inp{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236A9BBF' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;padding-right:30px}
textarea.inp{resize:vertical;min-height:80px;font-size:11px;line-height:1.7}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:7px;font-family:'Syne',sans-serif;font-weight:700;font-size:12px;letter-spacing:.04em;cursor:pointer;transition:all .2s;border:none;white-space:nowrap}
.btn-primary{background:linear-gradient(135deg,var(--blue),var(--cyan));color:#fff}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(0,180,216,0.3)}
.btn-primary:active{transform:translateY(0)}
.btn-primary:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}
.btn-ghost{background:transparent;border:1px solid var(--border2);color:var(--muted)}
.btn-ghost:hover{background:rgba(0,180,216,0.07);color:var(--cyan);border-color:var(--cyan)}
.btn-danger{background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);color:#F87171}
.btn-danger:hover{background:rgba(239,68,68,0.2)}
.btn-sm{padding:5px 10px;font-size:11px;border-radius:5px}
.btn-xs{padding:3px 8px;font-size:10px;border-radius:4px}

/* Method badges */
.method{display:inline-flex;align-items:center;padding:3px 8px;border-radius:4px;font-family:'Syne',sans-serif;font-weight:800;font-size:10px;letter-spacing:.08em;min-width:42px;justify-content:center}
.method-GET{background:rgba(34,197,94,0.15);color:#4ADE80;border:1px solid rgba(34,197,94,0.3)}
.method-POST{background:rgba(0,180,216,0.15);color:var(--cyan2);border:1px solid rgba(0,180,216,0.3)}
.method-PUT{background:rgba(244,164,34,0.15);color:#FBB045;border:1px solid rgba(244,164,34,0.3)}
.method-DELETE{background:rgba(239,68,68,0.15);color:#F87171;border:1px solid rgba(239,68,68,0.3)}
.method-PATCH{background:rgba(168,85,247,0.15);color:#C084FC;border:1px solid rgba(168,85,247,0.3)}

/* Status */
.status{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-family:'Syne',sans-serif;font-weight:700;font-size:10px;letter-spacing:.07em}
.s2xx{background:rgba(34,197,94,0.13);color:#4ADE80;border:1px solid rgba(34,197,94,0.3)}
.s3xx{background:rgba(0,180,216,0.13);color:var(--cyan2);border:1px solid rgba(0,180,216,0.3)}
.s4xx{background:rgba(244,164,34,0.13);color:#FBB045;border:1px solid rgba(244,164,34,0.3)}
.s5xx{background:rgba(239,68,68,0.13);color:#F87171;border:1px solid rgba(239,68,68,0.3)}

/* Token timer */
.timer-bar{height:2px;background:var(--s3);border-radius:1px;overflow:hidden;margin-top:6px}
.timer-fill{height:100%;border-radius:1px;transition:width 1s linear,background .5s}

/* Saved requests list */
.req-item{padding:10px 14px;border-bottom:1px solid var(--border);cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:8px}
.req-item:hover{background:rgba(0,180,216,0.06)}
.req-item.active{background:rgba(0,180,216,0.1);border-left:2px solid var(--cyan)}
.req-item .req-name{font-size:11px;font-family:'Syne',sans-serif;font-weight:600;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.req-item .req-url-preview{font-size:9px;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:2px}

/* Headers / Params table */
.kv-row{display:grid;grid-template-columns:1fr 1fr 28px;gap:6px;align-items:center;margin-bottom:6px}
.kv-row input{font-size:11px;padding:6px 10px}

/* JSON highlight */
.json-viewer{font-size:11px;line-height:1.8;overflow:auto}
.json-viewer pre{white-space:pre-wrap;word-break:break-word}
.jk{color:#7DD3FC}.js{color:#86EFAC}.jn{color:#FBB045}.jb{color:#C084FC}.jnull{color:#94A3B8}

/* Tabs */
.tabs{display:flex;gap:4px;padding:12px 16px 0;border-bottom:1px solid var(--border)}
.tab{padding:6px 14px;font-family:'Syne',sans-serif;font-size:11px;font-weight:700;cursor:pointer;border-radius:6px 6px 0 0;color:var(--muted);border:1px solid transparent;border-bottom:none;transition:all .2s;background:transparent}
.tab.active{background:var(--s3);color:var(--cyan);border-color:var(--border2)}
.tab-panel{display:none;padding:14px 16px}.tab-panel.active{display:block}

/* Spinner */
@keyframes spin{to{transform:rotate(360deg)}}
.spin{animation:spin .7s linear infinite;display:inline-block}

/* Fade */
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp .35s ease forwards}

/* Pulse */
@keyframes pulse2{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.4)}}
.pulse{animation:pulse2 2s ease-in-out infinite}

/* Scrollbar */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--s3);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--cyan)}

/* Label */
.lbl{font-size:9px;font-family:'Syne',sans-serif;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:5px}

/* Token copy */
.token-val{background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:10px;color:var(--cyan2);word-break:break-all;line-height:1.6;max-height:60px;overflow:hidden;mask-image:linear-gradient(to bottom,black 50%,transparent)}

/* Divider */
.divider{height:1px;background:var(--border);margin:12px 0}

/* Toast */
#toast{position:fixed;bottom:20px;right:20px;background:var(--s3);border:1px solid var(--cyan);border-radius:8px;padding:10px 18px;font-family:'Syne',sans-serif;font-size:12px;color:var(--cyan);z-index:9999;opacity:0;transform:translateY(8px);transition:all .25s;pointer-events:none}
#toast.show{opacity:1;transform:translateY(0)}

/* Scope chip */
.chip{display:inline-block;background:rgba(0,110,182,0.18);border:1px solid rgba(0,110,182,0.35);border-radius:3px;padding:1px 7px;font-size:9px;color:var(--cyan2);margin:2px}

/* Section group label in sidebar */
.sidebar-section{padding:8px 14px 4px;font-family:'Syne',sans-serif;font-size:9px;font-weight:800;letter-spacing:.15em;color:var(--s4);text-transform:uppercase}

/* Response time pill */
.res-time{background:rgba(0,180,216,0.1);border:1px solid rgba(0,180,216,0.2);border-radius:10px;padding:2px 8px;font-size:9px;color:var(--muted)}
</style>
</head>
<body>
<div id="toast"></div>

<div class="shell z1">

  <!-- ══ TOP BAR ══════════════════════════════════════════ -->
  <header class="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <div style="width:36px;height:36px;background:linear-gradient(135deg,#006EB6,#00B4D8);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:16px;color:#fff;box-shadow:0 3px 12px rgba(0,180,216,0.3);">M</div>
      <div>
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:16px;letter-spacing:-.02em;color:#fff">Maersk API Explorer</div>
        <div style="font-size:10px;color:var(--muted)">Multi-endpoint request builder</div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <div id="token-pill" style="display:flex;align-items:center;gap:8px;background:var(--s2);border:1px solid var(--border2);border-radius:20px;padding:5px 14px;">
        <span id="token-dot" class="pulse" style="width:7px;height:7px;background:var(--s4);border-radius:50%;display:inline-block"></span>
        <span id="token-label" style="font-size:11px;color:var(--muted);font-family:'Syne',sans-serif;font-weight:600">NO TOKEN</span>
        <span id="token-timer" style="font-size:10px;color:var(--muted);display:none"></span>
      </div>
      <button class="btn btn-ghost btn-sm" onclick="showAuthPanel()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Auth
      </button>
      <button class="btn btn-ghost btn-sm" onclick="saveCurrentRequest()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save
      </button>
    </div>
  </header>

  <!-- ══ SIDEBAR ══════════════════════════════════════════ -->
  <aside class="sidebar">
    <!-- New Request -->
    <div style="padding:14px">
      <button class="btn btn-primary" style="width:100%;justify-content:center" onclick="newRequest()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Request
      </button>
    </div>

    <!-- Quick URLs -->
    <div class="sidebar-section">Quick Endpoints</div>
    <div id="quick-list">
      <!-- Populated by JS -->
    </div>

    <div class="divider" style="margin:0"></div>

    <!-- Saved Requests -->
    <div class="sidebar-section" style="display:flex;align-items:center;justify-content:space-between;padding-right:14px">
      <span>Saved Requests</span>
      <button class="btn btn-ghost btn-xs" onclick="clearSaved()">Clear</button>
    </div>
    <div id="saved-list">
      <div style="padding:14px;font-size:10px;color:var(--s4)">No saved requests yet.</div>
    </div>

    <div style="flex:1"></div>

    <!-- Request Log mini -->
    <div style="border-top:1px solid var(--border)">
      <div class="sidebar-section" style="display:flex;align-items:center;justify-content:space-between;padding-right:14px">
        <span>Log</span>
        <button class="btn btn-ghost btn-xs" onclick="clearLog()">Clear</button>
      </div>
      <div id="log" style="padding:0 12px 12px;font-size:9px;line-height:1.9;color:var(--muted);max-height:180px;overflow-y:auto">
        <span style="color:var(--s3)">Awaiting…</span>
      </div>
    </div>
  </aside>

  <!-- ══ MAIN AREA ══════════════════════════════════════== -->
  <main class="main">

    <!-- Auth Panel (hidden by default) -->
    <div id="auth-panel" class="card fade-up" style="display:none">
      <div class="card-header">
        <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:13px;color:var(--cyan)">🔐 OAuth 2.0 — Client Credentials</span>
        <button class="btn btn-ghost btn-sm" onclick="hideAuthPanel()">✕</button>
      </div>
      <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div>
          <div class="lbl">Consumer Key</div>
          <div style="position:relative">
            <input id="inp-key" class="inp" type="text" placeholder="Consumer Key" value="n4CPpQZo1qZy036yY852oYIIxdSMxKDN"/>
            <button class="btn btn-ghost btn-xs" onclick="copyVal('inp-key')" style="position:absolute;right:6px;top:50%;transform:translateY(-50%)">COPY</button>
          </div>
        </div>
        <div>
          <div class="lbl">Client Secret</div>
          <div style="position:relative">
            <input id="inp-secret" class="inp" type="password" placeholder="Client Secret" value="CLGc8Fo0vkJumlcT"/>
            <button class="btn btn-ghost btn-xs" onclick="toggleSecret()" style="position:absolute;right:6px;top:50%;transform:translateY(-50%)">SHOW</button>
          </div>
        </div>
        <div>
          <div class="lbl">Customer Code</div>
          <input id="inp-customer" class="inp" type="text" placeholder="Customer Code" value="33104660082"/>
        </div>
      </div>
      <div class="card-body" style="padding-top:0;display:flex;align-items:center;gap:10px">
        <button id="btn-auth" class="btn btn-primary" onclick="getToken()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 7h3a5 5 0 0 1 5 5 5 5 0 0 1-5 5h-3m-6 0H6a5 5 0 0 1-5-5 5 5 0 0 1 5-5h3"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          Get Token
        </button>
        <div id="token-preview" style="display:none;flex:1">
          <div class="token-val" id="token-val-display"></div>
        </div>
        <div id="token-scopes" style="display:none;flex-wrap:wrap;display:none"></div>
      </div>
      <div id="timer-bar-wrap" style="display:none;padding:0 16px 14px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
          <span style="font-size:9px;color:var(--muted)">Token expires in</span>
          <span id="timer-lbl" style="font-size:9px;color:var(--cyan)">—</span>
        </div>
        <div class="timer-bar"><div id="timer-fill" class="timer-fill" style="width:100%"></div></div>
      </div>
    </div>

    <!-- ── REQUEST BUILDER ── -->
    <div class="card fade-up">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:8px;flex:1">
          <select id="req-method" class="inp" style="width:90px;font-family:'Syne',sans-serif;font-weight:700">
            <option>GET</option><option>POST</option><option>PUT</option><option>PATCH</option><option>DELETE</option>
          </select>
          <input id="req-url" class="inp" style="flex:1" type="text"
            placeholder="https://api.maersk.com/…"
            oninput="updateMethodBadge()"/>
          <button id="btn-send" class="btn btn-primary" onclick="sendRequest()" style="min-width:100px;justify-content:center">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Send
          </button>
        </div>
      </div>

      <!-- Tabs: Params / Headers / Body / Auth -->
      <div class="tabs">
        <div class="tab active" onclick="switchReqTab('params',this)">Params</div>
        <div class="tab" onclick="switchReqTab('headers',this)">Headers <span id="hdr-count" style="font-size:9px;color:var(--muted)"></span></div>
        <div class="tab" onclick="switchReqTab('body',this)">Body</div>
        <div class="tab" onclick="switchReqTab('auth-inject',this)">Auth</div>
      </div>

      <!-- PARAMS -->
      <div id="tp-params" class="tab-panel active">
        <div class="lbl" style="margin-bottom:8px">Query Parameters</div>
        <div id="params-rows"></div>
        <button class="btn btn-ghost btn-sm" onclick="addKV('params-rows','param')">+ Add Parameter</button>
      </div>

      <!-- HEADERS -->
      <div id="tp-headers" class="tab-panel">
        <div class="lbl" style="margin-bottom:8px">Request Headers</div>
        <div id="headers-rows"></div>
        <button class="btn btn-ghost btn-sm" onclick="addKV('headers-rows','header')">+ Add Header</button>
      </div>

      <!-- BODY -->
      <div id="tp-body" class="tab-panel">
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
          <div class="lbl" style="margin:0">Content-Type</div>
          <select class="inp" id="body-type" style="width:200px;font-size:11px" onchange="toggleBodyType()">
            <option value="none">None</option>
            <option value="json">application/json</option>
            <option value="form">application/x-www-form-urlencoded</option>
            <option value="raw">text/plain</option>
          </select>
        </div>
        <div id="body-editor" style="display:none">
          <textarea id="req-body" class="inp" placeholder='{"key": "value"}'></textarea>
        </div>
      </div>

      <!-- AUTH INJECT -->
      <div id="tp-auth-inject" class="tab-panel">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px">
            <input type="checkbox" id="auto-auth" checked style="accent-color:var(--cyan)"/> Auto-inject Bearer token
          </label>
        </div>
        <div id="manual-token-wrap">
          <div class="lbl">Or use custom token</div>
          <div style="position:relative">
            <input id="custom-token" class="inp" type="text" placeholder="Paste Bearer token manually (overrides auto-inject)"/>
          </div>
        </div>
        <div style="margin-top:10px;font-size:10px;color:var(--muted)">
          When auto-inject is on, the current OAuth token is automatically added to every request as <span style="color:var(--cyan2)">Authorization: Bearer …</span>
        </div>
      </div>
    </div>

    <!-- ── RESPONSE ── -->
    <div id="res-card" class="card fade-up" style="display:none">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:13px">Response</span>
          <span id="res-status" class="status s2xx">—</span>
          <span id="res-time" class="res-time">—</span>
          <span id="res-size" style="font-size:10px;color:var(--muted)">—</span>
        </div>
        <div style="display:flex;gap:6px">
          <button class="btn btn-ghost btn-sm" onclick="copyResponse()">Copy</button>
          <button class="btn btn-ghost btn-sm" onclick="downloadResponse()">↓ Save</button>
        </div>
      </div>

      <!-- Response tabs -->
      <div class="tabs">
        <div class="tab active" onclick="switchResTab('pretty',this)">Pretty</div>
        <div class="tab" onclick="switchResTab('raw',this)">Raw</div>
        <div class="tab" onclick="switchResTab('headers',this)">Headers</div>
      </div>

      <div id="tr-pretty" class="tab-panel active">
        <div class="json-viewer" style="max-height:500px;overflow:auto;background:var(--bg);border-radius:8px;padding:14px;border:1px solid var(--border)">
          <pre id="res-pretty"></pre>
        </div>
      </div>
      <div id="tr-raw" class="tab-panel">
        <div style="background:var(--bg);border-radius:8px;padding:14px;border:1px solid var(--border);max-height:500px;overflow:auto">
          <pre id="res-raw" style="font-size:11px;line-height:1.7;word-break:break-word;white-space:pre-wrap;color:var(--text)"></pre>
        </div>
      </div>
      <div id="tr-headers" class="tab-panel">
        <div id="res-headers-list" style="font-size:11px;line-height:2"></div>
      </div>
    </div>

  </main>
</div>

<script>
// ─────────────────────────────────────────────
// STATE
// ─────────────────────────────────────────────
let token = null;
let tokenExpiry = null;
let timerInterval = null;
let savedRequests = JSON.parse(localStorage.getItem('maersk_saved')||'[]');
let lastResponse = null;

const QUICK_ENDPOINTS = [
  { name:'Transport Documents', method:'GET', url:'https://api.maersk.com/ocean/document/v1/transport-documents/{ref}', desc:'Bill of Lading' },
  { name:'Shipment Tracking', method:'GET', url:'https://api.maersk.com/track/v1/events?equipmentReference={ref}', desc:'Track container' },
  { name:'Invoices', method:'GET', url:'https://api.maersk.com/invoices/v1/invoices/{invoiceNumber}', desc:'Freight invoice' },
  { name:'DND (Demurrage)', method:'GET', url:'https://api.maersk.com/maeu/detention-and-demurrage/v1/calculations', desc:'D&D calculation' },
  { name:'Schedules', method:'GET', url:'https://api.maersk.com/schedules/v1/point-to-point', desc:'Port schedules' },
  { name:'Booking', method:'GET', url:'https://api.maersk.com/bookings/v1/bookings/{bookingRef}', desc:'Booking details' },
  { name:'Vessel Schedules', method:'GET', url:'https://api.maersk.com/schedules/v1/vessel-schedules', desc:'Vessel arrival/dep.' },
  { name:'DCSA Tracking', method:'GET', url:'https://api.maersk.com/track/v2/shipments/{shipmentRef}', desc:'DCSA standard tracking' },
];

// ─────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────
(function init(){
  renderQuickList();
  renderSavedList();
  addDefaultHeaders();
  addKV('params-rows','param');
})();

function addDefaultHeaders(){
  addKV('headers-rows','header','Consumer-Key','n4CPpQZo1qZy036yY852oYIIxdSMxKDN');
  addKV('headers-rows','header','Accept','application/json');
  addKV('headers-rows','header','API-Version','1');
  updateHeaderCount();
}

// ─────────────────────────────────────────────
// SIDEBAR QUICK LIST
// ─────────────────────────────────────────────
function renderQuickList(){
  const el = document.getElementById('quick-list');
  el.innerHTML = QUICK_ENDPOINTS.map((e,i)=>`
    <div class="req-item" onclick="loadQuick(${i})">
      <span class="method method-${e.method}" style="font-size:9px;min-width:36px">${e.method}</span>
      <div style="flex:1;overflow:hidden">
        <div class="req-name">${e.name}</div>
        <div class="req-url-preview">${e.desc}</div>
      </div>
    </div>`).join('');
}

function loadQuick(i){
  const e = QUICK_ENDPOINTS[i];
  document.getElementById('req-url').value = e.url;
  document.getElementById('req-method').value = e.method;
  log(`Loaded: ${e.name}`, 'info');
}

// ─────────────────────────────────────────────
// SAVED REQUESTS
// ─────────────────────────────────────────────
function renderSavedList(){
  const el = document.getElementById('saved-list');
  if(!savedRequests.length){
    el.innerHTML = '<div style="padding:14px;font-size:10px;color:var(--s4)">No saved requests yet.</div>';
    return;
  }
  el.innerHTML = savedRequests.map((r,i)=>`
    <div class="req-item" onclick="loadSaved(${i})">
      <span class="method method-${r.method}" style="font-size:9px;min-width:36px">${r.method}</span>
      <div style="flex:1;overflow:hidden">
        <div class="req-name">${r.name||'Untitled'}</div>
        <div class="req-url-preview">${r.url}</div>
      </div>
      <button class="btn btn-danger btn-xs" onclick="deleteSaved(event,${i})">✕</button>
    </div>`).join('');
}

function saveCurrentRequest(){
  const url = document.getElementById('req-url').value.trim();
  if(!url){ toast('Enter a URL first'); return; }
  const name = prompt('Name this request:', url.split('/').pop()||'Request');
  if(!name) return;
  savedRequests.push({
    name, url,
    method: document.getElementById('req-method').value,
    headers: getKVData('headers-rows'),
    params: getKVData('params-rows'),
    body: document.getElementById('req-body').value,
  });
  localStorage.setItem('maersk_saved', JSON.stringify(savedRequests));
  renderSavedList();
  toast('✓ Request saved');
}

function loadSaved(i){
  const r = savedRequests[i];
  document.getElementById('req-url').value = r.url;
  document.getElementById('req-method').value = r.method;
  document.getElementById('req-body').value = r.body||'';
  document.getElementById('headers-rows').innerHTML='';
  document.getElementById('params-rows').innerHTML='';
  (r.headers||[]).forEach(h=>addKV('headers-rows','header',h.key,h.val));
  (r.params||[]).forEach(p=>addKV('params-rows','param',p.key,p.val));
  if(!r.headers?.length) addKV('headers-rows','header');
  if(!r.params?.length) addKV('params-rows','param');
  updateHeaderCount();
  toast(`Loaded: ${r.name}`);
}

function deleteSaved(e,i){
  e.stopPropagation();
  savedRequests.splice(i,1);
  localStorage.setItem('maersk_saved', JSON.stringify(savedRequests));
  renderSavedList();
}

function clearSaved(){
  if(!confirm('Clear all saved requests?')) return;
  savedRequests=[];
  localStorage.setItem('maersk_saved','[]');
  renderSavedList();
}

function newRequest(){
  document.getElementById('req-url').value='';
  document.getElementById('req-method').value='GET';
  document.getElementById('req-body').value='';
  document.getElementById('headers-rows').innerHTML='';
  document.getElementById('params-rows').innerHTML='';
  addDefaultHeaders();
  addKV('params-rows','param');
  document.getElementById('res-card').style.display='none';
}

// ─────────────────────────────────────────────
// KV ROW (params/headers)
// ─────────────────────────────────────────────
function addKV(containerId, type, k='', v=''){
  const c = document.getElementById(containerId);
  const row = document.createElement('div');
  row.className = 'kv-row';
  row.innerHTML = `
    <input class="inp" type="text" placeholder="${type==='header'?'Header Name':'Key'}" value="${escHtml(k)}" oninput="${type==='header'?'updateHeaderCount()':''}"/>
    <input class="inp" type="text" placeholder="Value" value="${escHtml(v)}"/>
    <button class="btn btn-danger btn-xs" onclick="this.parentElement.remove();${type==='header'?'updateHeaderCount()':''}" style="width:28px;height:28px;padding:0;justify-content:center">✕</button>`;
  c.appendChild(row);
}

function getKVData(containerId){
  const rows = document.querySelectorAll(`#${containerId} .kv-row`);
  return Array.from(rows).map(r=>{
    const inputs = r.querySelectorAll('input');
    return { key: inputs[0].value.trim(), val: inputs[1].value.trim() };
  }).filter(r=>r.key);
}

function updateHeaderCount(){
  const n = getKVData('headers-rows').length;
  document.getElementById('hdr-count').textContent = n?`(${n})`:'';
}

function escHtml(s){ return (s||'').replace(/"/g,'&quot;'); }

// ─────────────────────────────────────────────
// AUTH
// ─────────────────────────────────────────────
function showAuthPanel(){ document.getElementById('auth-panel').style.display='block'; }
function hideAuthPanel(){ document.getElementById('auth-panel').style.display='none'; }

function toggleSecret(){
  const inp = document.getElementById('inp-secret');
  const btn = inp.nextElementSibling;
  inp.type = inp.type==='password'?'text':'password';
  btn.textContent = inp.type==='password'?'SHOW':'HIDE';
}

async function getToken(){
  const key = document.getElementById('inp-key').value.trim();
  const secret = document.getElementById('inp-secret').value.trim();
  const btn = document.getElementById('btn-auth');
  if(!key||!secret){ toast('Enter credentials'); return; }

  btn.disabled=true;
  btn.innerHTML='<span class="spin">⟳</span> Fetching…';
  log('→ OAuth2 token request', 'api');

  try{
    const res = await fetch('https://api.maersk.com/customer-identity/oauth/v2/access_token',{
      method:'POST',
      headers:{'Consumer-Key':key,'Content-Type':'application/x-www-form-urlencoded','Cache-Control':'no-cache'},
      body: new URLSearchParams({grant_type:'client_credentials',client_id:key,client_secret:secret})
    });
    const data = await res.json();
    if(!res.ok){ log(`✗ ${res.status} auth failed`,'error'); toast('Auth failed'); return; }

    token = data.access_token;
    const exp = data.expires_in||7200;

    // Update pill
    document.getElementById('token-dot').style.background='#4ADE80';
    document.getElementById('token-label').textContent='AUTHENTICATED';
    document.getElementById('token-label').style.color='#4ADE80';
    document.getElementById('token-timer').style.display='inline';

    // Token preview
    const tp = document.getElementById('token-preview');
    tp.style.display='block';
    document.getElementById('token-val-display').textContent = token;

    // Scopes
    const scopes = (data.scope||'').split(' ').filter(Boolean);
    const sc = document.getElementById('token-scopes');
    sc.style.display='flex';
    sc.innerHTML = scopes.map(s=>`<span class="chip">${s.split('.').pop()}</span>`).join('');

    document.getElementById('timer-bar-wrap').style.display='block';
    startTimer(exp);
    log(`✓ Token OK · ${exp}s · ${scopes.length} scopes`, 'success');
    toast('✓ Token acquired');

  }catch(e){ log(`✗ ${e.message}`,'error'); }
  finally{
    btn.disabled=false;
    btn.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 7h3a5 5 0 0 1 5 5 5 5 0 0 1-5 5h-3m-6 0H6a5 5 0 0 1-5-5 5 5 0 0 1 5-5h3"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Get Token';
  }
}

function startTimer(exp){
  if(timerInterval) clearInterval(timerInterval);
  tokenExpiry = Date.now()+exp*1000;
  timerInterval = setInterval(()=>{
    const rem = Math.max(0,Math.round((tokenExpiry-Date.now())/1000));
    const pct=(rem/exp)*100;
    const lbl = rem>3600?`${Math.floor(rem/3600)}h ${Math.floor((rem%3600)/60)}m`:`${rem}s`;
    document.getElementById('timer-lbl').textContent=lbl;
    document.getElementById('token-timer').textContent=lbl;
    const fill=document.getElementById('timer-fill');
    fill.style.width=pct+'%';
    fill.style.background=pct<20?'linear-gradient(90deg,#EF4444,#F97316)':pct<50?'linear-gradient(90deg,#F59E0B,#FBB045)':'linear-gradient(90deg,#006EB6,#00B4D8)';
    if(rem===0){clearInterval(timerInterval);document.getElementById('token-dot').style.background='#EF4444';document.getElementById('token-label').textContent='EXPIRED';document.getElementById('token-label').style.color='#F87171';log('⚠ Token expired','warn');}
  },1000);
}

// ─────────────────────────────────────────────
// SEND REQUEST
// ─────────────────────────────────────────────
async function sendRequest(){
  let url = document.getElementById('req-url').value.trim();
  const method = document.getElementById('req-method').value;
  if(!url){ toast('Enter a URL'); return; }

  // Build query string from params
  const params = getKVData('params-rows');
  if(params.length){
    const qs = params.map(p=>`${encodeURIComponent(p.key)}=${encodeURIComponent(p.val)}`).join('&');
    url += (url.includes('?')?'&':'?')+qs;
  }

  // Build headers
  const headers = {};
  getKVData('headers-rows').forEach(h=>{ if(h.key) headers[h.key]=h.val; });

  // Auth
  const autoAuth = document.getElementById('auto-auth').checked;
  const customTok = document.getElementById('custom-token').value.trim();
  if(customTok) headers['Authorization']=`Bearer ${customTok}`;
  else if(autoAuth && token) headers['Authorization']=`Bearer ${token}`;

  // Body
  const bodyType = document.getElementById('body-type').value;
  let body = undefined;
  if(bodyType==='json'){ headers['Content-Type']='application/json'; body=document.getElementById('req-body').value||undefined; }
  else if(bodyType==='form'){ headers['Content-Type']='application/x-www-form-urlencoded'; body=document.getElementById('req-body').value||undefined; }
  else if(bodyType==='raw'){ body=document.getElementById('req-body').value||undefined; }

  const btn = document.getElementById('btn-send');
  btn.disabled=true;
  btn.innerHTML='<span class="spin">⟳</span> Sending…';

  log(`→ ${method} ${url}`,'api');
  const t0=Date.now();

  try{
    const res = await fetch(url,{ method, headers, body: ['GET','HEAD'].includes(method)?undefined:body });
    const ms=Date.now()-t0;
    const text = await res.text();
    let parsed=null;
    try{ parsed=JSON.parse(text); }catch{}

    lastResponse={text,parsed,status:res.status,statusText:res.statusText,headers:res.headers};

    showResponse(res,text,parsed,ms);
    log(`← ${res.status} ${res.statusText} · ${ms}ms · ${text.length}b`,res.ok?'success':'error');

  }catch(e){
    log(`✗ ${e.message}`,'error');
    toast('Request failed — check CORS or URL');
    showErrorResponse(e);
  }finally{
    btn.disabled=false;
    btn.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send';
  }
}

function showResponse(res, text, parsed, ms){
  const card = document.getElementById('res-card');
  card.style.display='block';

  // Status
  const s=res.status;
  const cls=s<300?'s2xx':s<400?'s3xx':s<500?'s4xx':'s5xx';
  document.getElementById('res-status').className=`status ${cls}`;
  document.getElementById('res-status').textContent=`${s} ${res.statusText}`;
  document.getElementById('res-time').textContent=`${ms}ms`;
  document.getElementById('res-size').textContent=`${(text.length/1024).toFixed(1)} KB`;

  // Pretty
  document.getElementById('res-pretty').innerHTML = parsed?syntaxHL(parsed):escHtmlStr(text);

  // Raw
  document.getElementById('res-raw').textContent=text;

  // Response headers
  const hdrList=document.getElementById('res-headers-list');
  hdrList.innerHTML='';
  res.headers.forEach((v,k)=>{
    hdrList.innerHTML+=`<div style="display:flex;gap:12px;border-bottom:1px solid var(--border);padding:4px 0"><span style="color:var(--cyan2);min-width:200px;font-size:10px">${k}</span><span style="color:var(--text);font-size:10px">${v}</span></div>`;
  });
}

function showErrorResponse(e){
  document.getElementById('res-card').style.display='block';
  document.getElementById('res-status').className='status s5xx';
  document.getElementById('res-status').textContent='Network Error';
  document.getElementById('res-time').textContent='—';
  document.getElementById('res-size').textContent='—';
  document.getElementById('res-pretty').innerHTML=`<span style="color:#F87171">${e.message}</span>`;
  document.getElementById('res-raw').textContent=e.message;
}

// ─────────────────────────────────────────────
// TABS
// ─────────────────────────────────────────────
function switchReqTab(id,btn){
  document.querySelectorAll('.tabs .tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  ['params','headers','body','auth-inject'].forEach(t=>{
    const p=document.getElementById('tp-'+t);
    if(p) p.classList.toggle('active',t===id);
  });
}

function switchResTab(id,btn){
  document.querySelectorAll('#res-card .tabs .tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  ['pretty','raw','headers'].forEach(t=>{
    const p=document.getElementById('tr-'+t);
    if(p) p.classList.toggle('active',t===id);
  });
}

function toggleBodyType(){
  const v=document.getElementById('body-type').value;
  document.getElementById('body-editor').style.display=v==='none'?'none':'block';
}

// ─────────────────────────────────────────────
// UTILS
// ─────────────────────────────────────────────
function syntaxHL(obj){
  let s=typeof obj==='string'?obj:JSON.stringify(obj,null,2);
  s=s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  return s.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g,m=>{
    if(/^"/.test(m)){if(/:$/.test(m))return`<span class="jk">${m}</span>`;return`<span class="js">${m}</span>`;}
    if(/true|false/.test(m))return`<span class="jb">${m}</span>`;
    if(/null/.test(m))return`<span class="jnull">${m}</span>`;
    return`<span class="jn">${m}</span>`;
  });
}

function escHtmlStr(s){ return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function copyResponse(){
  if(!lastResponse) return;
  navigator.clipboard.writeText(lastResponse.text);
  toast('✓ Copied!');
}

function downloadResponse(){
  if(!lastResponse) return;
  const blob=new Blob([lastResponse.text],{type:'application/json'});
  const a=document.createElement('a');
  a.href=URL.createObjectURL(blob);
  a.download=`maersk-response-${Date.now()}.json`;
  a.click();
}

function copyVal(id){
  navigator.clipboard.writeText(document.getElementById(id).value);
  toast('✓ Copied!');
}

function updateMethodBadge(){}

function log(msg, type='info'){
  const cols={info:'#6A9BBF',success:'#4ADE80',error:'#F87171',warn:'#FBB045',api:'#00B4D8'};
  const el=document.getElementById('log');
  const line=document.createElement('div');
  const t=new Date().toLocaleTimeString('en-US',{hour12:false,hour:'2-digit',minute:'2-digit',second:'2-digit'});
  line.innerHTML=`<span style="color:var(--s4)">${t}</span> <span style="color:${cols[type]||cols.info}">${msg}</span>`;
  if(el.querySelector('span')?.textContent==='Awaiting…') el.innerHTML='';
  el.appendChild(line);
  el.scrollTop=el.scrollHeight;
}

function clearLog(){ document.getElementById('log').innerHTML='<span style="color:var(--s3)">Cleared.</span>'; }

function toast(msg){
  const t=document.getElementById('toast');
  t.textContent=msg;
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),2200);
}
</script>
</body>
</html>
