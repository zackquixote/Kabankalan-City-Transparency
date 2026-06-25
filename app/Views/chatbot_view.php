<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant – Kabanakalan City Transparency</title>
    <meta name="description" content="Ask our AI assistant anything about Kabanakalan City's transparency reports, budgets, and government services.">

    <!--
        CSRF meta tag: CI4 outputs the current token here.
        The fetch() call below reads it and sends it as a header.
        If CSRF is disabled in your app config, this tag is harmless.
    -->
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="csrf-header" content="<?= csrf_header() ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* =====================================================
           CSS Custom Properties – design tokens
           ===================================================== */
        :root {
            --clr-bg:            #0d1117;
            --clr-surface:       #161b22;
            --clr-surface-2:     #21262d;
            --clr-border:        #30363d;
            --clr-accent:        #2f81f7;
            --clr-accent-hover:  #388bfd;
            --clr-accent-glow:   rgba(47,129,247,.25);
            --clr-text:          #e6edf3;
            --clr-text-muted:    #8b949e;
            --clr-user-bubble:   #1f4e8c;
            --clr-bot-bubble:    #21262d;
            --clr-danger:        #f85149;
            --clr-success:       #3fb950;
            --radius-md:         12px;
            --radius-lg:         18px;
            --radius-full:       9999px;
            --shadow-glow:       0 0 0 3px var(--clr-accent-glow);
            --transition:        0.2s ease;
            --font:              'Inter', system-ui, sans-serif;
        }

        /* =====================================================
           Reset / Base
           ===================================================== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            background: var(--clr-bg);
            color: var(--clr-text);
            font-family: var(--font);
            font-size: 15px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* =====================================================
           Page layout
           ===================================================== */
        #chat-app {
            display: flex;
            flex-direction: column;
            height: 100vh;
            max-width: 860px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* ── Header ── */
        #chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem 0 .9rem;
            border-bottom: 1px solid var(--clr-border);
            flex-shrink: 0;
        }

        #chat-header .brand {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        #chat-header .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--clr-accent), #7ee8a2);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        #chat-header .brand-name {
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: -.01em;
        }

        #chat-header .brand-sub {
            font-size: .75rem;
            color: var(--clr-text-muted);
        }

        #chat-header .header-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        /* Status pill */
        #status-pill {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .75rem;
            color: var(--clr-text-muted);
            background: var(--clr-surface-2);
            padding: .3rem .75rem;
            border-radius: var(--radius-full);
            border: 1px solid var(--clr-border);
        }

        #status-pill .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--clr-success);
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50%       { opacity: .35; }
        }

        /* Clear-history button */
        #btn-clear {
            background: transparent;
            border: 1px solid var(--clr-border);
            color: var(--clr-text-muted);
            border-radius: var(--radius-md);
            padding: .35rem .75rem;
            font-size: .78rem;
            font-family: var(--font);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: .35rem;
        }
        #btn-clear:hover {
            border-color: var(--clr-danger);
            color: var(--clr-danger);
        }

        /* ── Message list ── */
        #messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem 0;
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
            scroll-behavior: smooth;
        }

        /* Thin scrollbar */
        #messages::-webkit-scrollbar { width: 5px; }
        #messages::-webkit-scrollbar-track { background: transparent; }
        #messages::-webkit-scrollbar-thumb { background: var(--clr-border); border-radius: 3px; }

        /* ── Message rows ── */
        .msg-row {
            display: flex;
            align-items: flex-end;
            gap: .6rem;
            animation: fade-up .3s ease both;
        }
        .msg-row.user  { flex-direction: row-reverse; }
        .msg-row.bot   { flex-direction: row; }

        @keyframes fade-up {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Avatar */
        .msg-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
            font-weight: 700;
        }
        .msg-row.user .msg-avatar  { background: var(--clr-user-bubble); color: #fff; }
        .msg-row.bot  .msg-avatar  { background: linear-gradient(135deg,var(--clr-accent),#7ee8a2); color: #000; }

        /* Bubble */
        .msg-bubble {
            max-width: 72%;
            padding: .75rem 1rem;
            border-radius: var(--radius-lg);
            font-size: .92rem;
            line-height: 1.65;
            word-break: break-word;
            white-space: pre-wrap;
        }

        .msg-row.user .msg-bubble {
            background: var(--clr-user-bubble);
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .msg-row.bot .msg-bubble {
            background: var(--clr-bot-bubble);
            color: var(--clr-text);
            border: 1px solid var(--clr-border);
            border-bottom-left-radius: 4px;
        }

        /* Timestamp */
        .msg-time {
            font-size: .68rem;
            color: var(--clr-text-muted);
            margin-top: .3rem;
            text-align: right;
        }
        .msg-row.bot .msg-time { text-align: left; }

        /* ── Typing indicator ── */
        #typing-indicator {
            display: none; /* shown via JS */
            flex-direction: row;
            align-items: flex-end;
            gap: .6rem;
        }
        #typing-indicator .msg-avatar {
            background: linear-gradient(135deg,var(--clr-accent),#7ee8a2);
            color: #000;
        }
        #typing-indicator .typing-dots {
            background: var(--clr-bot-bubble);
            border: 1px solid var(--clr-border);
            border-radius: var(--radius-lg);
            border-bottom-left-radius: 4px;
            padding: .75rem 1.1rem;
            display: flex;
            gap: .35rem;
            align-items: center;
        }
        .typing-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--clr-text-muted);
            animation: bounce 1.1s ease-in-out infinite;
        }
        .typing-dot:nth-child(2) { animation-delay: .18s; }
        .typing-dot:nth-child(3) { animation-delay: .36s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); }
            40%            { transform: translateY(-6px); }
        }

        /* ── Empty state ── */
        #empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            color: var(--clr-text-muted);
            text-align: center;
            padding: 2rem;
            pointer-events: none;
            user-select: none;
        }
        #empty-state .empty-icon {
            font-size: 3rem;
            opacity: .4;
        }
        #empty-state p {
            font-size: .9rem;
            max-width: 320px;
        }

        /* ── Input area ── */
        #chat-form-wrap {
            flex-shrink: 0;
            padding: .9rem 0 1.2rem;
            border-top: 1px solid var(--clr-border);
        }

        #chat-form {
            display: flex;
            align-items: flex-end;
            gap: .6rem;
            background: var(--clr-surface);
            border: 1px solid var(--clr-border);
            border-radius: var(--radius-lg);
            padding: .55rem .55rem .55rem .9rem;
            transition: border-color var(--transition), box-shadow var(--transition);
        }
        #chat-form:focus-within {
            border-color: var(--clr-accent);
            box-shadow: var(--shadow-glow);
        }

        #msg-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--clr-text);
            font-family: var(--font);
            font-size: .92rem;
            resize: none;
            max-height: 160px;
            overflow-y: auto;
            line-height: 1.6;
            padding: .2rem 0;
        }
        #msg-input::placeholder { color: var(--clr-text-muted); }

        #btn-send {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            border: none;
            background: var(--clr-accent);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background var(--transition), transform var(--transition);
        }
        #btn-send:hover  { background: var(--clr-accent-hover); }
        #btn-send:active { transform: scale(.93); }
        #btn-send:disabled { background: var(--clr-surface-2); cursor: not-allowed; opacity: .6; }

        #btn-send svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        /* Character / hint line below input */
        #input-hint {
            font-size: .72rem;
            color: var(--clr-text-muted);
            margin-top: .45rem;
            padding: 0 .25rem;
        }

        /* =====================================================
           Responsive tweaks
           ===================================================== */
        @media (max-width: 600px) {
            .msg-bubble { max-width: 88%; }
            #chat-header .brand-sub { display: none; }
        }
    </style>
</head>
<body>

<div id="chat-app" role="main">

    <!-- ── Header ──────────────────────────────────────────── -->
    <header id="chat-header">
        <div class="brand">
            <div class="brand-icon" aria-hidden="true">🏛️</div>
            <div>
                <div class="brand-name">AI Assistant</div>
                <div class="brand-sub">Kabanakalan City Transparency Portal</div>
            </div>
        </div>
        <div class="header-actions">
            <div id="status-pill" aria-live="polite">
                <span class="dot" aria-hidden="true"></span>
                Online
            </div>
            <button id="btn-clear" title="Clear conversation history" aria-label="Clear conversation history">
                🗑 Clear
            </button>
        </div>
    </header>

    <!-- ── Message list ─────────────────────────────────────── -->
    <div id="messages" role="log" aria-live="polite" aria-label="Chat messages">

        <!-- Pre-rendered history from server-side session -->
        <?php if (!empty($history)): ?>
            <?php foreach ($history as $turn): ?>
                <?php if ($turn['role'] === 'user'): ?>
                    <div class="msg-row user">
                        <div class="msg-avatar" aria-hidden="true">U</div>
                        <div>
                            <div class="msg-bubble"><?= esc($turn['content']) ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="msg-row bot">
                        <div class="msg-avatar" aria-hidden="true">AI</div>
                        <div>
                            <div class="msg-bubble"><?= esc($turn['content']) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty-state placeholder – hidden once first message appears -->
            <div id="empty-state" aria-hidden="true">
                <div class="empty-icon">💬</div>
                <strong>Start the conversation</strong>
                <p>Ask me anything about Kabanakalan City's budget, projects, or transparency reports.</p>
            </div>
        <?php endif; ?>

        <!-- Typing indicator – shown while awaiting bot reply -->
        <div id="typing-indicator" role="status" aria-label="Assistant is typing">
            <div class="msg-avatar" aria-hidden="true">AI</div>
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        </div>

    </div><!-- /#messages -->

    <!-- ── Input area ─────────────────────────────────────── -->
    <div id="chat-form-wrap">
        <form id="chat-form" autocomplete="off" novalidate>
            <textarea
                id="msg-input"
                name="message"
                placeholder="Type a message… (Enter to send, Shift+Enter for new line)"
                rows="1"
                aria-label="Message input"
                maxlength="4000"
            ></textarea>
            <button id="btn-send" type="submit" aria-label="Send message">
                <!-- Paper-plane icon (inline SVG, no dependencies) -->
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </form>
        <div id="input-hint">Press <kbd>Enter</kbd> to send · <kbd>Shift+Enter</kbd> for a new line</div>
    </div>

</div><!-- /#chat-app -->

<script>
/* ================================================================
   Chatbot front-end logic
   ================================================================ */
(function () {
    'use strict';

    /* ── DOM refs ──────────────────────────────────────────────── */
    const messagesEl  = document.getElementById('messages');
    const form        = document.getElementById('chat-form');
    const input       = document.getElementById('msg-input');
    const sendBtn     = document.getElementById('btn-send');
    const typingEl    = document.getElementById('typing-indicator');
    const emptyState  = document.getElementById('empty-state');
    const clearBtn    = document.getElementById('btn-clear');

    /* ── CSRF token (read from meta tags injected by CI4) ────── */
    // CI4's csrf_hash() / csrf_header() are rendered server-side into the
    // <meta> tags in <head>. We read them here so the fetch POST includes the
    // correct token header without embedding it into every form field.
    const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content  ?? '';
    const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content ?? 'X-CSRF-TOKEN';

    /* ── Endpoint URL ──────────────────────────────────────────── */
    // base_url() is called server-side to inject the correct root URL.
    // We embed it as a JS string so no hard-coding is needed.
    const SEND_URL  = '<?= base_url('chatbot/send') ?>';
    const CLEAR_URL = '<?= base_url('chatbot/clear') ?>';

    /* ── Auto-resize textarea ──────────────────────────────────── */
    input.addEventListener('input', () => {
        // Reset to 'auto' first to shrink when content is deleted.
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 160) + 'px';
    });

    /* ── Enter-to-send (Shift+Enter = new line) ────────────────── */
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
    });

    /* ── Utility: scroll to bottom of message list ─────────────── */
    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    /* ── Utility: format current time as HH:MM ─────────────────── */
    function now() {
        return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    /* ── Utility: escape HTML to prevent XSS in bot replies ────── */
    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    /* ── Append a message bubble to the list ───────────────────── */
    // role: 'user' | 'bot'
    // text: plain-text content
    function appendMessage(role, text) {
        // Hide the empty-state placeholder on first message.
        if (emptyState) emptyState.remove();

        const isUser   = role === 'user';
        const avatar   = isUser ? 'U' : 'AI';
        const rowClass = isUser ? 'user' : 'bot';

        const row = document.createElement('div');
        row.className = `msg-row ${rowClass}`;
        row.setAttribute('aria-label', `${isUser ? 'You' : 'Assistant'}: ${text}`);

        row.innerHTML = `
            <div class="msg-avatar" aria-hidden="true">${avatar}</div>
            <div>
                <div class="msg-bubble">${escHtml(text)}</div>
                <div class="msg-time" aria-hidden="true">${now()}</div>
            </div>
        `;

        // Insert before the typing indicator so it stays at the bottom.
        messagesEl.insertBefore(row, typingEl);
        scrollToBottom();

        return row;
    }

    /* ── Show / hide the typing indicator ──────────────────────── */
    function showTyping(visible) {
        typingEl.style.display = visible ? 'flex' : 'none';
        if (visible) scrollToBottom();
    }

    /* ── Set UI busy/idle state ─────────────────────────────────── */
    function setBusy(busy) {
        input.disabled  = busy;
        sendBtn.disabled = busy;
        showTyping(busy);
    }

    /* ── Form submit handler ────────────────────────────────────── */
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const message = input.value.trim();
        if (!message) return;

        // Clear the input immediately for a snappy feel.
        input.value = '';
        input.style.height = 'auto';

        // Render the user's bubble right away.
        appendMessage('user', message);

        // Disable input while waiting for the reply.
        setBusy(true);

        try {
            const formData = new FormData();
            formData.append('message', message);

            const response = await fetch(SEND_URL, {
                method: 'POST',
                headers: {
                    // Send the CSRF token as an HTTP header (CI4 accepts this
                    // as an alternative to the hidden form field).
                    [csrfHeader]: csrfToken,
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                // API-level or validation error returned as JSON.
                appendMessage('bot', '⚠️ ' + (data.error ?? 'Unknown error. Please try again.'));
                return;
            }

            appendMessage('bot', data.reply ?? '(No reply received)');

        } catch (err) {
            // Network failure or unexpected non-JSON body.
            appendMessage('bot', '⚠️ Could not reach the server. Check your connection and try again.');
            console.error('Chatbot fetch error:', err);

        } finally {
            // Always restore the UI, even on error.
            setBusy(false);
            input.focus();
        }
    });

    /* ── Clear-history button ───────────────────────────────────── */
    clearBtn.addEventListener('click', async () => {
        if (!confirm('Clear the entire conversation history?')) return;

        try {
            await fetch(CLEAR_URL, {
                method: 'POST',
                headers: { [csrfHeader]: csrfToken },
            });
        } catch (_) {
            // Ignore network errors on clear – the UI is wiped regardless.
        }

        // Remove all rendered message rows from the DOM.
        const rows = messagesEl.querySelectorAll('.msg-row');
        rows.forEach(r => r.remove());

        // Re-insert the empty-state element.
        const es = document.createElement('div');
        es.id = 'empty-state';
        es.setAttribute('aria-hidden', 'true');
        es.innerHTML = `
            <div class="empty-icon">💬</div>
            <strong>Start the conversation</strong>
            <p>Ask me anything about Kabanakalan City's budget, projects, or transparency reports.</p>
        `;
        messagesEl.insertBefore(es, typingEl);
    });

    /* ── On load: scroll to latest message ─────────────────────── */
    scrollToBottom();
    input.focus();

})();
</script>

</body>
</html>
