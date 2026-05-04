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
            --danger:        #ef4444;
            --radius:        10px;
            --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        }

        html, body {
            height: 100%;
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        .app {
            display: flex;
            flex-direction: column;
            height: 100vh;
            max-width: 860px;
            margin: 0 auto;
        }

        /* ── Header ── */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-left h1 {
            font-size: 16px;
            font-weight: 600;
        }

        .badge {
            font-size: 11px;
            background: var(--bg);
            color: var(--text-muted);
            padding: 2px 8px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }

        .header-right { display: flex; gap: 8px; align-items: center; }

        .session-label {
            font-size: 11px;
            color: var(--text-muted);
            font-family: monospace;
        }

        /* ── Settings bar ── */
        .settings {
            display: flex;
            gap: 8px;
            padding: 10px 20px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
            flex-wrap: wrap;
            align-items: center;
        }

        .settings label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
            white-space: nowrap;
        }

        .settings-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .settings input,
        .settings select {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 13px;
            font-family: var(--font);
            outline: none;
            background: var(--bg);
            color: var(--text);
        }

        .settings input:focus,
        .settings select:focus { border-color: var(--primary); }

        #apiKey  { width: 200px; font-family: monospace; }
        #system  { width: 240px; }

        .divider { width: 1px; height: 20px; background: var(--border); margin: 0 4px; }

        /* ── Chat area ── */
        .chat {
            flex: 1;
            overflow-y: auto;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .empty-state {
            margin: auto;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state .icon { font-size: 40px; margin-bottom: 12px; }
        .empty-state p { font-size: 15px; }
        .empty-state small { font-size: 12px; display: block; margin-top: 6px; }

        /* ── Messages ── */
        .message { display: flex; flex-direction: column; max-width: 78%; }
        .message.user    { align-self: flex-end; align-items: flex-end; }
        .message.assistant { align-self: flex-start; align-items: flex-start; }

        .message-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 4px;
            padding: 0 4px;
        }

        .bubble {
            padding: 11px 15px;
            border-radius: var(--radius);
            line-height: 1.6;
            word-break: break-word;
        }

        .message.user .bubble {
            background: var(--primary);
            color: #fff;
            border-bottom-right-radius: 3px;
        }

        .message.assistant .bubble {
            background: var(--surface);
            border: 1px solid var(--border);
            border-bottom-left-radius: 3px;
            color: var(--text);
        }

        .bubble pre {
            background: #1e293b;
            color: #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            overflow-x: auto;
            font-size: 13px;
            margin: 8px 0;
            white-space: pre;
        }

        .bubble code {
            font-family: 'Fira Code', 'Cascadia Code', Consolas, monospace;
            font-size: 13px;
        }

        .bubble p code {
            background: #f1f5f9;
            color: #0f172a;
            padding: 1px 5px;
            border-radius: 4px;
        }

        .bubble strong { font-weight: 600; }

        .bubble p { margin-bottom: 6px; }
        .bubble p:last-child { margin-bottom: 0; }

        /* Typing cursor */
        .cursor {
            display: inline-block;
            width: 2px;
            height: 14px;
            background: var(--text-muted);
            border-radius: 1px;
            margin-left: 2px;
            vertical-align: middle;
            animation: blink 0.8s step-end infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0; }
        }

        .error-bubble {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--danger);
            padding: 10px 14px;
            border-radius: var(--radius);
            font-size: 13px;
        }

        /* ── Input area ── */
        .input-area {
            display: flex;
            gap: 10px;
            padding: 14px 20px;
            background: var(--surface);
            border-top: 1px solid var(--border);
            flex-shrink: 0;
            align-items: flex-end;
        }

        #input {
            flex: 1;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 10px 14px;
            font-size: 14px;
            font-family: var(--font);
            resize: none;
            outline: none;
            min-height: 44px;
            max-height: 160px;
            overflow-y: auto;
            line-height: 1.5;
            background: var(--bg);
            color: var(--text);
        }

        #input:focus { border-color: var(--primary); background: var(--surface); }

        .btn {
            border: none;
            border-radius: var(--radius);
            padding: 10px 20px;
            font-size: 14px;
            font-family: var(--font);
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.15s;
            height: 44px;
        }

        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover:not(:disabled) { background: var(--primary-hover); }
        .btn-primary:disabled { background: #9ca3af; cursor: not-allowed; }

        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border);
            padding: 6px 12px;
            font-size: 13px;
            height: auto;
        }

        .btn-ghost:hover { background: var(--bg); color: var(--text); }
    </style>
</head>
<body>
<div class="app">

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <h1>⚡ Ollama Chat</h1>
            <span class="badge" id="modelBadge">phi</span>
        </div>
        <div class="header-right">
            <span class="session-label" id="sessionLabel"></span>
            <button class="btn btn-ghost" id="newChatBtn">New Chat</button>
        </div>
    </header>

    <!-- Settings -->
    <div class="settings">
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
            <label for="system">System</label>
            <input type="text" id="system" placeholder="e.g. You are a senior Laravel developer">
        </div>
    </div>

    <!-- Chat messages -->
    <div class="chat" id="chat">
        <div class="empty-state" id="emptyState">
            <div class="icon">💬</div>
            <p>Start a conversation</p>
            <small>Set your API key above, then type a message</small>
        </div>
    </div>

    <!-- Input -->
    <div class="input-area">
        <textarea id="input" placeholder="Message… (Enter to send, Shift+Enter for new line)" rows="1"></textarea>
        <button class="btn btn-primary" id="sendBtn">Send</button>
    </div>

</div>

<script>
// ── State ───────────────────────────────────────────────────────────────────
const LS = {
    get: (k, d) => localStorage.getItem(k) ?? d,
    set: (k, v) => localStorage.setItem(k, v),
};

function newSessionId() {
    const id = 'session-' + Math.random().toString(36).slice(2, 10);
    LS.set('session_id', id);
    return id;
}

function getSessionId() {
    return LS.get('session_id', null) || newSessionId();
}

// ── DOM refs ─────────────────────────────────────────────────────────────────
const chatEl      = document.getElementById('chat');
const emptyState  = document.getElementById('emptyState');
const inputEl     = document.getElementById('input');
const sendBtn     = document.getElementById('sendBtn');
const apiKeyEl    = document.getElementById('apiKey');
const modelEl     = document.getElementById('model');
const systemEl    = document.getElementById('system');
const modelBadge  = document.getElementById('modelBadge');
const sessionLabel= document.getElementById('sessionLabel');
const newChatBtn  = document.getElementById('newChatBtn');

// ── Init from localStorage ───────────────────────────────────────────────────
apiKeyEl.value = LS.get('api_key', '');
modelEl.value  = LS.get('model', 'phi');
updateBadge();
updateSessionLabel(getSessionId());

apiKeyEl.addEventListener('change', () => LS.set('api_key', apiKeyEl.value));
modelEl.addEventListener('change', () => { LS.set('model', modelEl.value); updateBadge(); });

function updateBadge() { modelBadge.textContent = modelEl.value; }
function updateSessionLabel(id) { sessionLabel.textContent = id; }

// ── New Chat ─────────────────────────────────────────────────────────────────
newChatBtn.addEventListener('click', () => {
    const id = newSessionId();
    updateSessionLabel(id);
    chatEl.innerHTML = '';
    chatEl.appendChild(emptyState);
    emptyState.style.display = '';
});

// ── Simple markdown renderer ─────────────────────────────────────────────────
function renderMarkdown(raw) {
    // Escape HTML
    let s = raw
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    // Fenced code blocks
    s = s.replace(/```[\w]*\n?([\s\S]*?)```/g, (_, code) =>
        `<pre><code>${code.trimEnd()}</code></pre>`
    );

    // Inline code
    s = s.replace(/`([^`\n]+)`/g, '<code>$1</code>');

    // Bold
    s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

    // Paragraphs — split on double newline
    const parts = s.split(/\n{2,}/);
    return parts.map(p => {
        if (p.startsWith('<pre>')) return p;
        return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
    }).join('');
}

// ── Append a message bubble ──────────────────────────────────────────────────
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
    div.className = 'error-bubble';
    div.textContent = '⚠ ' + text;
    chatEl.appendChild(div);
    scrollBottom();
}

function scrollBottom() {
    chatEl.scrollTop = chatEl.scrollHeight;
}

// ── Send message ─────────────────────────────────────────────────────────────
async function send() {
    const prompt = inputEl.value.trim();
    if (!prompt) return;

    const apiKey   = apiKeyEl.value.trim();
    const model    = modelEl.value;
    const system   = systemEl.value.trim();
    const sessionId = getSessionId();

    if (!apiKey) {
        appendError('Set your API key in the settings bar above.');
        return;
    }

    // Show user message
    appendMessage('user', prompt);
    inputEl.value = '';
    autoResize();

    // Prepare assistant bubble with cursor
    const assistantBubble = appendMessage('assistant');
    const cursor = document.createElement('span');
    cursor.className = 'cursor';
    assistantBubble.appendChild(cursor);

    sendBtn.disabled = true;

    let rawText = '';

    try {
        const res = await fetch('/api/ai/sse', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': apiKey,
                'Accept': 'text/event-stream',
            },
            body: JSON.stringify({ prompt, session_id: sessionId, model, system: system || undefined }),
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({ message: `HTTP ${res.status}` }));
            assistantBubble.remove();
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
            buffer = lines.pop(); // keep incomplete last line

            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;

                const data = line.slice(6);
                if (data === 'true') continue; // done event payload

                rawText += data.replace(/\\n/g, '\n');

                // Update bubble while streaming (raw text, no markdown flicker)
                cursor.remove();
                assistantBubble.textContent = rawText;
                assistantBubble.appendChild(cursor);
                scrollBottom();
            }
        }

        // Streaming done — render markdown
        cursor.remove();
        assistantBubble.innerHTML = renderMarkdown(rawText);

    } catch (err) {
        cursor.remove();
        assistantBubble.remove();
        appendError(err.message);
    } finally {
        sendBtn.disabled = false;
        inputEl.focus();
        scrollBottom();
    }
}

// ── Input auto-resize ────────────────────────────────────────────────────────
function autoResize() {
    inputEl.style.height = 'auto';
    inputEl.style.height = Math.min(inputEl.scrollHeight, 160) + 'px';
}

inputEl.addEventListener('input', autoResize);

inputEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (!sendBtn.disabled) send();
    }
});

sendBtn.addEventListener('click', send);
inputEl.focus();
</script>
</body>
</html>
