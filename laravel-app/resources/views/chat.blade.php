<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ollama Chat</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:            #f0f2f5;
            --surface:       #ffffff;
            --border:        #e5e7eb;
            --text:          #111827;
            --text-muted:    #6b7280;
            --primary:       #111827;
            --primary-hover: #1f2937;
            --success:       #16a34a;
            --danger:        #dc2626;
            --warning:       #d97706;
            --radius:        10px;
            --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        }

        html, body { height: 100%; font-family: var(--font); background: var(--bg); color: var(--text); font-size: 14px; }

        .app { display: flex; flex-direction: column; height: 100vh; max-width: 920px; margin: 0 auto; }

        /* ── Header ── */
        .header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 20px; background: var(--surface);
            border-bottom: 1px solid var(--border); flex-shrink: 0; gap: 12px;
        }

        .header-brand { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 600; white-space: nowrap; }

        .tabs { display: flex; gap: 2px; background: var(--bg); padding: 3px; border-radius: 8px; }

        .tab-btn {
            border: none; background: transparent; padding: 5px 14px; border-radius: 6px;
            font-size: 13px; font-family: var(--font); cursor: pointer; color: var(--text-muted);
            font-weight: 500; transition: all 0.15s;
        }
        .tab-btn:hover { color: var(--text); }
        .tab-btn.active { background: var(--surface); color: var(--text); box-shadow: 0 1px 3px rgba(0,0,0,.08); }

        .header-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .session-label { font-size: 11px; color: var(--text-muted); font-family: monospace; }

        /* ── Settings bar ── */
        .settings {
            display: flex; gap: 8px; padding: 8px 20px; background: var(--surface);
            border-bottom: 1px solid var(--border); flex-shrink: 0; flex-wrap: wrap; align-items: center;
        }
        .settings-group { display: flex; align-items: center; gap: 5px; }
        .settings label { font-size: 11px; color: var(--text-muted); font-weight: 500; white-space: nowrap; }

        .settings input, .settings select {
            border: 1px solid var(--border); border-radius: 6px; padding: 5px 10px;
            font-size: 13px; font-family: var(--font); outline: none; background: var(--bg); color: var(--text);
        }
        .settings input:focus, .settings select:focus { border-color: var(--primary); }
        #apiKey { width: 190px; font-family: monospace; }
        #system { width: 220px; }
        .divider { width: 1px; height: 18px; background: var(--border); }

        /* ── Tab panes ── */
        .tab-pane { display: none; flex: 1; overflow: hidden; flex-direction: column; }
        .tab-pane.active { display: flex; }

        /* ── Chat pane ── */
        .chat {
            flex: 1; overflow-y: auto; padding: 20px;
            display: flex; flex-direction: column; gap: 18px;
        }

        .empty-state { margin: auto; text-align: center; color: var(--text-muted); }
        .empty-state .icon { font-size: 36px; margin-bottom: 10px; }
        .empty-state p { font-size: 15px; }
        .empty-state small { font-size: 12px; display: block; margin-top: 5px; }

        .message { display: flex; flex-direction: column; max-width: 80%; }
        .message.user     { align-self: flex-end; align-items: flex-end; }
        .message.assistant { align-self: flex-start; align-items: flex-start; }

        .message-meta { font-size: 11px; color: var(--text-muted); margin-bottom: 3px; padding: 0 3px; }

        .bubble { padding: 11px 15px; border-radius: var(--radius); line-height: 1.6; word-break: break-word; min-width: 40px; }

        .message.user .bubble      { background: var(--primary); color: #fff; border-bottom-right-radius: 3px; }
        .message.assistant .bubble { background: var(--surface); border: 1px solid var(--border); border-bottom-left-radius: 3px; color: var(--text); }

        /* Thinking state */
        .thinking-text { color: var(--text-muted); font-size: 13px; font-style: italic; }
        .thinking-note { font-size: 11px; color: var(--warning); display: block; margin-top: 4px; }

        .cursor {
            display: inline-block; width: 2px; height: 14px; background: var(--text-muted);
            border-radius: 1px; margin-left: 2px; vertical-align: middle;
            animation: blink .8s step-end infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }

        /* Markdown in bubbles */
        .bubble pre { background: #1e293b; color: #e2e8f0; border-radius: 6px; padding: 12px; overflow-x: auto; font-size: 12.5px; margin: 8px 0; }
        .bubble code { font-family: 'Fira Code', Consolas, monospace; font-size: 12.5px; }
        .bubble p code { background: #f1f5f9; color: #0f172a; padding: 1px 5px; border-radius: 4px; }
        .bubble strong { font-weight: 600; }
        .bubble p { margin-bottom: 6px; }
        .bubble p:last-child { margin-bottom: 0; }

        .error-msg {
            background: #fef2f2; border: 1px solid #fecaca; color: var(--danger);
            padding: 10px 14px; border-radius: var(--radius); font-size: 13px;
        }

        /* ── Input area ── */
        .input-area {
            display: flex; gap: 10px; padding: 12px 20px; background: var(--surface);
            border-top: 1px solid var(--border); flex-shrink: 0; align-items: flex-end;
        }
        #input {
            flex: 1; border: 1px solid var(--border); border-radius: var(--radius);
            padding: 10px 14px; font-size: 14px; font-family: var(--font); resize: none;
            outline: none; min-height: 44px; max-height: 150px; overflow-y: auto;
            line-height: 1.5; background: var(--bg); color: var(--text);
        }
        #input:focus { border-color: var(--primary); background: var(--surface); }

        /* ── History pane ── */
        .panel-wrapper { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 12px; }
        .panel-header {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 13px; font-weight: 600; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: .05em; padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .session-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
            padding: 12px 14px; cursor: pointer; transition: border-color .15s;
        }
        .session-card:hover { border-color: var(--primary); }
        .session-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .session-id-label { font-family: monospace; font-size: 12px; color: var(--primary); font-weight: 600; }
        .session-meta { font-size: 11px; color: var(--text-muted); }
        .session-preview { font-size: 13px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .messages-view {
            background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
            padding: 16px;
        }
        .messages-view-header { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
        .history-msg { padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 13px; line-height: 1.5; }
        .history-msg.user      { background: #f1f5f9; border-left: 3px solid var(--primary); }
        .history-msg.assistant { background: #f9fafb; border-left: 3px solid var(--border); }
        .history-msg-role { font-size: 11px; font-weight: 600; color: var(--text-muted); margin-bottom: 3px; text-transform: uppercase; }

        /* ── Logs pane ── */
        .logs-table-wrap { flex: 1; overflow: auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: var(--surface); border-radius: var(--radius); overflow: hidden; font-size: 13px; }
        th { background: var(--bg); color: var(--text-muted); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border); }
        td { padding: 9px 12px; border-bottom: 1px solid var(--border); vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        .badge-success { background: #dcfce7; color: var(--success); padding: 2px 7px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-error   { background: #fee2e2; color: var(--danger);  padding: 2px 7px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .duration      { font-family: monospace; color: var(--text-muted); font-size: 12px; }
        .log-prompt    { max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-muted); }
        .mono          { font-family: monospace; font-size: 12px; }

        /* ── Buttons ── */
        .btn { border: none; border-radius: var(--radius); padding: 8px 18px; font-size: 13px; font-family: var(--font); font-weight: 500; cursor: pointer; transition: background .15s; height: 44px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover:not(:disabled) { background: var(--primary-hover); }
        .btn-primary:disabled { background: #9ca3af; cursor: not-allowed; }
        .btn-ghost  { background: transparent; color: var(--text-muted); border: 1px solid var(--border); padding: 5px 12px; font-size: 12px; height: auto; border-radius: 6px; }
        .btn-ghost:hover { background: var(--bg); color: var(--text); }

        .loading-row td { text-align: center; color: var(--text-muted); padding: 32px; }
        .empty-panel { text-align: center; color: var(--text-muted); padding: 40px; }
    </style>
</head>
<body>
<div class="app">

    <!-- ── Header ── -->
    <header class="header">
        <div class="header-brand">⚡ Ollama Chat</div>

        <div class="tabs">
            <button class="tab-btn active" data-tab="chat"    onclick="switchTab('chat')">Chat</button>
            <button class="tab-btn"        data-tab="history" onclick="switchTab('history')">History</button>
            <button class="tab-btn"        data-tab="logs"    onclick="switchTab('logs')">Logs</button>
        </div>

        <div class="header-right">
            <span class="session-label" id="sessionLabel"></span>
            <button class="btn btn-ghost" id="newChatBtn">New Chat</button>
        </div>
    </header>

    <!-- ── Settings (Chat tab only) ── -->
    <div class="settings" id="settingsBar">
        <div class="settings-group">
            <label for="apiKey">API Key</label>
            <input type="password" id="apiKey" placeholder="your-api-key" autocomplete="off">
        </div>
        <div class="divider"></div>
        <div class="settings-group">
            <label for="model">Model</label>
            <select id="model">
                <option value="phi">phi — fast (3B)</option>
                <option value="llama3">llama3 — quality (8B)</option>
                <option value="gemma2">gemma2 — Google (9B)</option>
                <option value="mistral">mistral — balanced (7B)</option>
            </select>
        </div>
        <div class="divider"></div>
        <div class="settings-group">
            <label for="system">Assistant Role</label>
            <input type="text" id="system" placeholder="e.g. You are a senior developer">
        </div>
    </div>

    <!-- ═══════════════════ CHAT PANE ═══════════════════ -->
    <div class="tab-pane active" id="pane-chat">
        <div class="chat" id="chat">
            <div class="empty-state" id="emptyState">
                <div class="icon">💬</div>
                <p>Start a conversation</p>
                <small>Set your API key above, then type a message</small>
            </div>
        </div>
        <div class="input-area">
            <textarea id="input" placeholder="Message… (Enter to send, Shift+Enter for new line)" rows="1"></textarea>
            <button class="btn btn-primary" id="sendBtn">Send</button>
        </div>
    </div>

    <!-- ═══════════════════ HISTORY PANE ═══════════════════ -->
    <div class="tab-pane" id="pane-history">
        <div class="panel-wrapper" id="historyWrapper">
            <div class="panel-header">
                <span>Conversation History</span>
                <button class="btn btn-ghost" onclick="loadHistory()">↻ Refresh</button>
            </div>
            <div id="historyContent">
                <div class="empty-panel">Switch to this tab to load history</div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════ LOGS PANE ═══════════════════ -->
    <div class="tab-pane" id="pane-logs">
        <div class="logs-table-wrap">
            <div class="panel-header" style="margin-bottom:14px">
                <span>Request Logs</span>
                <button class="btn btn-ghost" onclick="loadLogs()">↻ Refresh</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Endpoint</th>
                        <th>Model</th>
                        <th>Session</th>
                        <th>Prompt</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="logsBody">
                    <tr class="loading-row"><td colspan="7">Switch to this tab to load logs</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
// ── Helpers ──────────────────────────────────────────────────────────────────
const LS = {
    get: (k, d = '') => localStorage.getItem(k) ?? d,
    set: (k, v)      => localStorage.setItem(k, v),
};

function newSessionId() {
    const id = 'session-' + Math.random().toString(36).slice(2, 10);
    LS.set('session_id', id);
    return id;
}
function getSessionId() { return LS.get('session_id') || newSessionId(); }
function getApiKey()    { return document.getElementById('apiKey').value.trim(); }

// ── DOM refs ─────────────────────────────────────────────────────────────────
const chatEl       = document.getElementById('chat');
const emptyState   = document.getElementById('emptyState');
const inputEl      = document.getElementById('input');
const sendBtn      = document.getElementById('sendBtn');
const apiKeyEl     = document.getElementById('apiKey');
const modelEl      = document.getElementById('model');
const systemEl     = document.getElementById('system');
const sessionLabel = document.getElementById('sessionLabel');
const settingsBar  = document.getElementById('settingsBar');
const newChatBtn   = document.getElementById('newChatBtn');

// ── Init ─────────────────────────────────────────────────────────────────────
apiKeyEl.value = LS.get('api_key');
modelEl.value  = LS.get('model', 'phi');
systemEl.value = LS.get('assistant_role');
sessionLabel.textContent = getSessionId();

apiKeyEl.addEventListener('change', () => LS.set('api_key', apiKeyEl.value));
modelEl.addEventListener('change',  () => LS.set('model', modelEl.value));
systemEl.addEventListener('input',  () => LS.set('assistant_role', systemEl.value));

// ── Tabs ─────────────────────────────────────────────────────────────────────
let activeTab = 'chat';

function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('pane-' + tab).classList.add('active');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    settingsBar.style.display = tab === 'chat' ? '' : 'none';
    newChatBtn.style.display  = tab === 'chat' ? '' : 'none';
    if (tab === 'history') loadHistory();
    if (tab === 'logs')    loadLogs();
}

// ── New Chat ──────────────────────────────────────────────────────────────────
newChatBtn.addEventListener('click', () => {
    const id = newSessionId();
    sessionLabel.textContent = id;
    chatEl.innerHTML = '';
    chatEl.appendChild(emptyState);
    emptyState.style.display = '';
});

// ── Markdown renderer ─────────────────────────────────────────────────────────
function renderMarkdown(raw) {
    let s = raw.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    s = s.replace(/```[\w]*\n?([\s\S]*?)```/g, (_, c) => `<pre><code>${c.trimEnd()}</code></pre>`);
    s = s.replace(/`([^`\n]+)`/g, '<code>$1</code>');
    s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    return s.split(/\n{2,}/).map(p => p.startsWith('<pre>') ? p : '<p>' + p.replace(/\n/g,'<br>') + '</p>').join('');
}

// ── Chat helpers ──────────────────────────────────────────────────────────────
function appendMessage(role, text = '') {
    emptyState.style.display = 'none';
    const wrap   = document.createElement('div');
    wrap.className = `message ${role}`;
    const meta   = document.createElement('div');
    meta.className = 'message-meta';
    meta.textContent = role === 'user' ? 'You' : 'Assistant';
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    if (text) bubble.innerHTML = renderMarkdown(text);
    wrap.appendChild(meta);
    wrap.appendChild(bubble);
    chatEl.appendChild(wrap);
    scrollBottom();
    return bubble;
}

function appendError(text) {
    emptyState.style.display = 'none';
    const div = document.createElement('div');
    div.className = 'error-msg';
    div.textContent = '⚠ ' + text;
    chatEl.appendChild(div);
    scrollBottom();
}

function scrollBottom() { chatEl.scrollTop = chatEl.scrollHeight; }

// ── Thinking timer ────────────────────────────────────────────────────────────
function startThinking(bubble) {
    let secs = 0;
    const label = document.createElement('span');
    label.className = 'thinking-text';
    label.textContent = 'Thinking...';
    const note = document.createElement('span');
    note.className = 'thinking-note';
    bubble.appendChild(label);
    bubble.appendChild(note);

    const timer = setInterval(() => {
        secs++;
        label.textContent = `Thinking... ${secs}s`;
        if (secs === 10) note.textContent = 'Model may be loading into RAM — first request takes 30-60s';
        if (secs === 45) note.textContent = 'Still loading... large models can take up to 60s on first use';
    }, 1000);

    return { timer, label, note };
}

function stopThinking(thinking) {
    clearInterval(thinking.timer);
    thinking.label.remove();
    thinking.note.remove();
}

// ── Send ──────────────────────────────────────────────────────────────────────
async function send() {
    const prompt = inputEl.value.trim();
    if (!prompt || sendBtn.disabled) return;

    const apiKey = getApiKey();
    if (!apiKey) { appendError('Set your API key in the settings bar.'); return; }

    appendMessage('user', prompt);
    inputEl.value = '';
    autoResize();

    const assistantBubble = appendMessage('assistant');
    const cursor = document.createElement('span');
    cursor.className = 'cursor';
    assistantBubble.appendChild(cursor);

    const thinking = startThinking(assistantBubble);
    sendBtn.disabled = true;
    let rawText = '';
    let firstToken = false;

    try {
        const res = await fetch('/api/ai/sse', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-API-Key': apiKey, 'Accept': 'text/event-stream' },
            body: JSON.stringify({
                prompt,
                session_id: getSessionId(),
                model:  modelEl.value,
                system: systemEl.value.trim() || undefined,
            }),
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({ message: `HTTP ${res.status}` }));
            assistantBubble.parentElement.remove();
            appendError(err.message ?? `HTTP ${res.status}`);
            return;
        }

        const reader  = res.body.getReader();
        const decoder = new TextDecoder();
        let   buffer  = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;
                const data = line.slice(6);
                if (data === 'true') continue;

                // First token — clear thinking state
                if (!firstToken) {
                    firstToken = true;
                    stopThinking(thinking);
                    cursor.remove();
                }

                rawText += data.replace(/\\n/g, '\n');
                assistantBubble.textContent = rawText;
                assistantBubble.appendChild(cursor);
                scrollBottom();
            }
        }

        // Stream complete — render markdown
        cursor.remove();
        if (rawText) assistantBubble.innerHTML = renderMarkdown(rawText);
        else         assistantBubble.textContent = '(no response)';

    } catch (err) {
        assistantBubble.parentElement?.remove();
        appendError(err.message);
    } finally {
        stopThinking(thinking);
        sendBtn.disabled = false;
        inputEl.focus();
        scrollBottom();
    }
}

// ── History tab ───────────────────────────────────────────────────────────────
async function loadHistory() {
    const el = document.getElementById('historyContent');
    el.innerHTML = '<div class="empty-panel">Loading...</div>';

    const apiKey = getApiKey();
    if (!apiKey) { el.innerHTML = '<div class="empty-panel">Set your API key in the Chat tab first.</div>'; return; }

    try {
        const res  = await fetch('/api/ai/conversations', { headers: { 'X-API-Key': apiKey } });
        const data = await res.json();

        if (!Array.isArray(data) || data.length === 0) {
            el.innerHTML = '<div class="empty-panel">No conversations yet. Start chatting first.</div>';
            return;
        }

        el.innerHTML = '';
        data.forEach(conv => {
            const card = document.createElement('div');
            card.className = 'session-card';
            card.innerHTML = `
                <div class="session-card-header">
                    <span class="session-id-label">${esc(conv.session_id)}</span>
                    <span class="session-meta">${conv.message_count} msgs · ${timeAgo(conv.last_active)}</span>
                </div>
                <div class="session-preview">${esc(conv.last_message || '—')}</div>
            `;
            card.addEventListener('click', () => loadSession(conv.session_id, el));
            el.appendChild(card);
        });
    } catch (e) {
        el.innerHTML = `<div class="empty-panel" style="color:var(--danger)">Error: ${esc(e.message)}</div>`;
    }
}

async function loadSession(sessionId, container) {
    const apiKey = getApiKey();
    try {
        const res  = await fetch(`/api/ai/conversations/${encodeURIComponent(sessionId)}`, { headers: { 'X-API-Key': apiKey } });
        const data = await res.json();

        // Insert messages view above the list
        const existing = document.getElementById('messages-view-panel');
        if (existing) existing.remove();

        const panel = document.createElement('div');
        panel.id = 'messages-view-panel';
        panel.className = 'messages-view';
        panel.innerHTML = `
            <div class="messages-view-header">
                <button class="btn btn-ghost" onclick="document.getElementById('messages-view-panel').remove()">✕ Close</button>
                <span style="font-family:monospace;font-size:12px;color:var(--text-muted)">${esc(sessionId)}</span>
            </div>
            ${(data.messages || []).map(m => `
                <div class="history-msg ${m.role}">
                    <div class="history-msg-role">${m.role}</div>
                    <div>${esc(m.content)}</div>
                </div>
            `).join('')}
        `;

        // Insert at top of container before first session card
        const firstCard = container.querySelector('.session-card');
        if (firstCard) container.insertBefore(panel, firstCard);
        else container.appendChild(panel);
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (e) {
        appendError('Could not load session: ' + e.message);
    }
}

// ── Logs tab ──────────────────────────────────────────────────────────────────
async function loadLogs() {
    const tbody = document.getElementById('logsBody');
    tbody.innerHTML = '<tr class="loading-row"><td colspan="7">Loading...</td></tr>';

    const apiKey = getApiKey();
    if (!apiKey) {
        tbody.innerHTML = '<tr class="loading-row"><td colspan="7">Set your API key in the Chat tab first.</td></tr>';
        return;
    }

    try {
        const res  = await fetch('/api/ai/logs', { headers: { 'X-API-Key': apiKey } });
        const data = await res.json();

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = '<tr class="loading-row"><td colspan="7">No logs yet. Send a chat message first.</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(log => `
            <tr>
                <td class="mono">${shortTime(log.created_at)}</td>
                <td><code>${esc(log.endpoint)}</code></td>
                <td class="mono">${esc(log.model)}</td>
                <td class="mono" style="font-size:11px">${esc(log.session_id)}</td>
                <td class="log-prompt">${esc(log.prompt_preview)}</td>
                <td class="duration">${log.duration_ms != null ? (log.duration_ms / 1000).toFixed(1) + 's' : '—'}</td>
                <td>
                    ${log.status === 'success'
                        ? '<span class="badge-success">ok</span>'
                        : `<span class="badge-error" title="${esc(log.error || '')}">err</span>`}
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = `<tr class="loading-row"><td colspan="7" style="color:var(--danger)">Error: ${esc(e.message)}</td></tr>`;
    }
}

// ── Utilities ─────────────────────────────────────────────────────────────────
function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function timeAgo(iso) {
    if (!iso) return '';
    const diff = Math.floor((Date.now() - new Date(iso)) / 1000);
    if (diff < 60)   return diff + 's ago';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
}

function shortTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// ── Input ─────────────────────────────────────────────────────────────────────
function autoResize() {
    inputEl.style.height = 'auto';
    inputEl.style.height = Math.min(inputEl.scrollHeight, 150) + 'px';
}

inputEl.addEventListener('input', autoResize);
inputEl.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
});
sendBtn.addEventListener('click', send);
inputEl.focus();
</script>
</body>
</html>
