/* ============================================================
   IgoTech — Chatbot Widget JS
   ============================================================ */

(function () {
  'use strict';

  /* ─── CONFIG ─────────────────────────────────────────────── */
  const API_URL    = '/api/chatbot.php';
  const NOTIF_DELAY = 5000; // ms before notification popup appears
  const STORAGE_KEY = 'igo_cb_session';

  /* ─── STATE ──────────────────────────────────────────────── */
  let sessionId   = null;
  let isOpen      = false;
  let isTyping    = false;
  let notifShown  = false;
  let notifTimer  = null;
  let lang        = 'fr';

  /* ─── ELEMENTS ───────────────────────────────────────────── */
  let trigger, window_, body_, input, sendBtn, badge, notif, quickRepliesEl;

  /* ─── SESSION ID ─────────────────────────────────────────── */
  function getSessionId() {
    let id = localStorage.getItem(STORAGE_KEY);
    if (!id) {
      id = 'igo_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      localStorage.setItem(STORAGE_KEY, id);
    }
    return id;
  }

  /* ─── LANGUAGE DETECTION ─────────────────────────────────── */
  function detectLang() {
    const urlParams = new URLSearchParams(window.location.search);
    const urlLang = urlParams.get('lang');
    if (urlLang === 'en' || urlLang === 'fr') return urlLang;
    const meta = document.querySelector('html[lang]');
    if (meta) {
      const l = meta.getAttribute('lang');
      if (l && l.startsWith('en')) return 'en';
    }
    return 'fr';
  }

  /* ─── MARKDOWN LIGHT ─────────────────────────────────────── */
  function parseMd(text) {
    if (!text) return '';
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      // Bold **text**
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      // Italic *text*
      .replace(/\*(.+?)\*/g, '<em>$1</em>')
      // Links [text](url) — only allow http/https
      .replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
      // Line breaks
      .replace(/\n/g, '<br>');
  }

  /* ─── RENDER MESSAGE ─────────────────────────────────────── */
  function renderMessage(role, text, animate) {
    const wrap = document.createElement('div');
    wrap.className = 'cb-msg ' + role;

    const avatarEl = document.createElement('div');
    avatarEl.className = 'cb-msg-avatar';
    if (role === 'bot') {
      const img = document.createElement('img');
      img.src = '/assets/images/logo.png';
      img.alt = 'Igo';
      img.onerror = function () {
        this.parentNode.textContent = 'I';
      };
      avatarEl.appendChild(img);
    }

    const bubble = document.createElement('div');
    bubble.className = 'cb-bubble';
    bubble.innerHTML = role === 'bot' ? parseMd(text) : escapeHtml(text);

    const time = document.createElement('div');
    time.className = 'cb-msg-time';
    time.textContent = formatTime(new Date());

    wrap.appendChild(avatarEl);
    const inner = document.createElement('div');
    inner.appendChild(bubble);
    inner.appendChild(time);
    wrap.appendChild(inner);

    if (animate) wrap.style.opacity = '0';
    body_.appendChild(wrap);
    if (animate) {
      requestAnimationFrame(() => { wrap.style.opacity = '1'; });
    }
    scrollToBottom();
    return wrap;
  }

  function escapeHtml(text) {
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\n/g, '<br>');
  }

  function formatTime(date) {
    return date.getHours().toString().padStart(2, '0') + ':' +
           date.getMinutes().toString().padStart(2, '0');
  }

  /* ─── QUICK REPLIES ──────────────────────────────────────── */
  function renderQuickReplies(replies) {
    clearQuickReplies();
    if (!replies || !replies.length) return;

    const wrap = document.createElement('div');
    wrap.className = 'cb-quick-replies';
    replies.forEach(function (label) {
      const btn = document.createElement('button');
      btn.className = 'cb-qr-btn';
      btn.textContent = label;
      btn.addEventListener('click', function () {
        clearQuickReplies();
        sendMessage(label);
      });
      wrap.appendChild(btn);
    });
    quickRepliesEl = wrap;
    // Insert before footer
    const footer = window_.querySelector('.cb-footer');
    window_.insertBefore(wrap, footer);
  }

  function clearQuickReplies() {
    if (quickRepliesEl && quickRepliesEl.parentNode) {
      quickRepliesEl.parentNode.removeChild(quickRepliesEl);
    }
    quickRepliesEl = null;
  }

  /* ─── TYPING INDICATOR ───────────────────────────────────── */
  function showTyping() {
    if (isTyping) return;
    isTyping = true;

    const wrap = document.createElement('div');
    wrap.className = 'cb-typing';
    wrap.id = 'cb-typing-indicator';

    const avatarEl = document.createElement('div');
    avatarEl.className = 'cb-msg-avatar';
    const img = document.createElement('img');
    img.src = '/assets/images/logo.png';
    img.alt = 'Igo';
    img.onerror = function () { this.parentNode.textContent = 'I'; };
    avatarEl.appendChild(img);

    const dots = document.createElement('div');
    dots.className = 'cb-typing-dots';
    for (let i = 0; i < 3; i++) {
      const dot = document.createElement('span');
      dot.className = 'cb-dot';
      dots.appendChild(dot);
    }

    wrap.appendChild(avatarEl);
    wrap.appendChild(dots);
    body_.appendChild(wrap);
    scrollToBottom();
  }

  function hideTyping() {
    isTyping = false;
    const indicator = document.getElementById('cb-typing-indicator');
    if (indicator) indicator.parentNode.removeChild(indicator);
  }

  /* ─── SCROLL ─────────────────────────────────────────────── */
  function scrollToBottom() {
    setTimeout(function () {
      body_.scrollTop = body_.scrollHeight;
    }, 50);
  }

  /* ─── SEND MESSAGE ───────────────────────────────────────── */
  function sendMessage(text) {
    text = (text || '').trim();
    if (!text || !text.length) return;

    clearQuickReplies();
    renderMessage('user', text, true);
    input.value = '';
    resizeInput();
    setInputEnabled(false);
    showTyping();

    const payload = {
      message:    text,
      session_id: sessionId,
      lang:       lang,
      page:       window.location.pathname
    };

    fetch(API_URL, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload)
    })
    .then(function (r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(function (data) {
      hideTyping();
      if (data.success && data.data) {
        const d = data.data;
        // Small delay to feel natural
        setTimeout(function () {
          renderMessage('bot', d.message, true);
          if (d.quick_replies && d.quick_replies.length) {
            renderQuickReplies(d.quick_replies);
          }
        }, 200);
      } else {
        const fallback = lang === 'en'
          ? 'Sorry, I encountered an error. Please try again.'
          : 'Désolé, une erreur est survenue. Veuillez réessayer.';
        setTimeout(function () { renderMessage('bot', fallback, true); }, 200);
      }
    })
    .catch(function () {
      hideTyping();
      const fallback = lang === 'en'
        ? 'Connection error. Please try again later.'
        : 'Erreur de connexion. Veuillez réessayer plus tard.';
      setTimeout(function () { renderMessage('bot', fallback, true); }, 200);
    })
    .finally(function () {
      setInputEnabled(true);
      input.focus();
    });
  }

  /* ─── INPUT HELPERS ──────────────────────────────────────── */
  function setInputEnabled(enabled) {
    input.disabled    = !enabled;
    sendBtn.disabled  = !enabled;
  }

  function resizeInput() {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 80) + 'px';
  }

  /* ─── OPEN / CLOSE ───────────────────────────────────────── */
  function openChat() {
    isOpen = true;
    window_.classList.add('open');
    trigger.classList.add('open');
    trigger.setAttribute('aria-expanded', 'true');
    hideBadge();
    hideNotification();
    clearTimeout(notifTimer);
    setTimeout(function () { input.focus(); }, 350);
  }

  function closeChat() {
    isOpen = false;
    window_.classList.remove('open');
    trigger.classList.remove('open');
    trigger.setAttribute('aria-expanded', 'false');
  }

  function toggleChat() {
    if (isOpen) closeChat();
    else openChat();
  }

  /* ─── BADGE ──────────────────────────────────────────────── */
  function showBadge() {
    if (badge) badge.style.display = 'flex';
  }

  function hideBadge() {
    if (badge) badge.style.display = 'none';
  }

  /* ─── NOTIFICATION POPUP ─────────────────────────────────── */
  function showNotification() {
    if (!notif || notifShown || isOpen) return;
    notifShown = true;
    notif.style.display = 'block';
    showBadge();
  }

  function hideNotification() {
    if (!notif) return;
    notif.style.display = 'none';
  }

  /* ─── BUILD HTML ─────────────────────────────────────────── */
  function buildWidget() {
    // Trigger button
    trigger = document.createElement('button');
    trigger.className = 'chatbot-trigger';
    trigger.setAttribute('aria-label', lang === 'en' ? 'Open chat' : 'Ouvrir le chat');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.innerHTML =
      '<i class="fas fa-comment-dots icon-chat" aria-hidden="true"></i>' +
      '<i class="fas fa-times icon-close" aria-hidden="true"></i>';

    badge = document.createElement('span');
    badge.className = 'chatbot-badge';
    badge.textContent = '1';
    badge.style.display = 'none';
    badge.setAttribute('aria-label', lang === 'en' ? '1 new message' : '1 nouveau message');
    trigger.appendChild(badge);

    // Chat window
    window_ = document.createElement('div');
    window_.className = 'chatbot-window';
    window_.setAttribute('role', 'dialog');
    window_.setAttribute('aria-label', 'Chat Igo');
    window_.innerHTML =
      '<div class="cb-header">' +
        '<div class="cb-avatar">' +
          '<img src="/assets/images/logo.png" alt="Igo" onerror="this.parentNode.innerHTML=\'<span>I</span>\'">' +
        '</div>' +
        '<div class="cb-header-info">' +
          '<div class="cb-header-name">Igo — IgoTech</div>' +
          '<div class="cb-header-status">' +
            '<span class="cb-status-dot"></span>' +
            '<span>' + (lang === 'en' ? 'Online — Replies instantly' : 'En ligne — Répond instantanément') + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="cb-header-actions">' +
          '<button class="cb-header-btn cb-minimize-btn" title="' + (lang === 'en' ? 'Minimize' : 'Réduire') + '" aria-label="' + (lang === 'en' ? 'Minimize chat' : 'Réduire le chat') + '">' +
            '<i class="fas fa-minus" aria-hidden="true"></i>' +
          '</button>' +
        '</div>' +
      '</div>' +
      '<div class="cb-body" role="log" aria-live="polite" aria-label="Messages"></div>' +
      '<div class="cb-footer">' +
        '<div class="cb-input-wrap">' +
          '<textarea class="cb-input" rows="1" placeholder="' +
            (lang === 'en' ? 'Type your message…' : 'Votre message…') + '" ' +
            'aria-label="' + (lang === 'en' ? 'Message' : 'Message') + '"></textarea>' +
          '<button class="cb-send-btn" aria-label="' + (lang === 'en' ? 'Send' : 'Envoyer') + '">' +
            '<i class="fas fa-paper-plane" aria-hidden="true"></i>' +
          '</button>' +
        '</div>' +
        '<div class="cb-footer-note">' +
          (lang === 'en' ? 'Powered by IgoTech AI' : 'Propulsé par IgoTech AI') +
        '</div>' +
      '</div>';

    body_   = window_.querySelector('.cb-body');
    input   = window_.querySelector('.cb-input');
    sendBtn = window_.querySelector('.cb-send-btn');

    // Notification popup
    const notifText = lang === 'en'
      ? '👋 Hi! Need help? I\'m <strong>Igo</strong>, your IgoTech assistant.'
      : '👋 Bonjour ! Besoin d\'aide ? Je suis <strong>Igo</strong>, votre assistant IgoTech.';
    notif = document.createElement('div');
    notif.className = 'chatbot-notification';
    notif.setAttribute('role', 'status');
    notif.style.display = 'none';
    notif.innerHTML =
      '<button class="notif-close" aria-label="' + (lang === 'en' ? 'Close' : 'Fermer') + '">' +
        '<i class="fas fa-times" aria-hidden="true"></i>' +
      '</button>' + notifText;

    document.body.appendChild(trigger);
    document.body.appendChild(window_);
    document.body.appendChild(notif);
  }

  /* ─── BIND EVENTS ────────────────────────────────────────── */
  function bindEvents() {
    // Toggle open/close
    trigger.addEventListener('click', toggleChat);

    // Minimize button
    const minBtn = window_.querySelector('.cb-minimize-btn');
    if (minBtn) minBtn.addEventListener('click', closeChat);

    // Send on button click
    sendBtn.addEventListener('click', function () {
      sendMessage(input.value);
    });

    // Send on Enter (Shift+Enter = new line)
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage(input.value);
      }
    });

    // Auto-resize textarea
    input.addEventListener('input', resizeInput);

    // Notification click → open chat
    notif.addEventListener('click', function (e) {
      if (!e.target.classList.contains('notif-close') &&
          !e.target.closest('.notif-close')) {
        openChat();
      }
    });

    // Notification close button
    const notifClose = notif.querySelector('.notif-close');
    if (notifClose) {
      notifClose.addEventListener('click', function (e) {
        e.stopPropagation();
        hideNotification();
        hideBadge();
        notifShown = true; // don't show again
      });
    }

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen) closeChat();
    });

    // Close when clicking outside (mobile UX)
    document.addEventListener('click', function (e) {
      if (isOpen &&
          !window_.contains(e.target) &&
          !trigger.contains(e.target)) {
        closeChat();
      }
    });
  }

  /* ─── INIT ───────────────────────────────────────────────── */
  function init() {
    sessionId = getSessionId();
    lang = detectLang();

    buildWidget();
    bindEvents();

    // Fetch welcome message from API (empty message = init)
    fetch(API_URL, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({
        message:    '',
        session_id: sessionId,
        lang:       lang,
        page:       window.location.pathname
      })
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success && data.data) {
        const d = data.data;
        renderMessage('bot', d.message, false);
        if (d.quick_replies && d.quick_replies.length) {
          renderQuickReplies(d.quick_replies);
        }
      }
    })
    .catch(function () {
      // Silent fail — widget still functional
    });

    // Show notification popup after delay (only if not previously dismissed)
    const dismissed = localStorage.getItem('igo_cb_notif_dismissed');
    if (!dismissed) {
      notifTimer = setTimeout(showNotification, NOTIF_DELAY);
    }
  }

  /* ─── BOOT ───────────────────────────────────────────────── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
