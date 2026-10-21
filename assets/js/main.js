/**
 * IgoTech — main.js
 * Vanilla JS : preloader, navbar, counters, forms, AOS, mobile menu
 */

'use strict';

/* ═══════════════════════════════════════════════════════════
   PRELOADER
═══════════════════════════════════════════════════════════ */
(function () {
  const preloader = document.getElementById('preloader');
  if (!preloader) return;
  // Laisser les animations se derouler (min 1.8s) puis masquer
  const hideAt = Date.now() + 1800;
  window.addEventListener('load', () => {
    const delay = Math.max(0, hideAt - Date.now());
    setTimeout(() => {
      preloader.classList.add('hide');
      setTimeout(() => {
        if (preloader.parentNode) preloader.parentNode.removeChild(preloader);
      }, 750);
    }, delay);
  });
})();

/* ═══════════════════════════════════════════════════════════
   AOS INIT
═══════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
  if (typeof AOS !== 'undefined') {
    AOS.init({
      duration: 700,
      easing:   'ease-out-cubic',
      once:     true,
      offset:   60,
    });
  }

  /* ─── Navbar scroll ─── */
  const navbar = document.getElementById('navbar');
  if (navbar) {
    const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 50);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ─── Mobile hamburger ─── */
  const hamburger = document.getElementById('hamburger');
  const navMenu   = document.getElementById('navMenu');
  if (hamburger && navMenu) {
    hamburger.addEventListener('click', () => {
      const isOpen = navMenu.classList.toggle('open');
      hamburger.classList.toggle('open', isOpen);
      hamburger.setAttribute('aria-expanded', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });
    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
        navMenu.classList.remove('open');
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      }
    });
    // Close on nav link click
    navMenu.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        navMenu.classList.remove('open');
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });
  }

  /* ─── Scroll to top ─── */
  const scrollTopBtn = document.getElementById('scrollTop');
  if (scrollTopBtn) {
    window.addEventListener('scroll', () => {
      scrollTopBtn.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
    scrollTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* ─── Counter animation ─── */
  initCounters();

  /* ─── Alert close ─── */
  document.querySelectorAll('.alert-close').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('.alert')?.remove());
  });

  /* ─── Contact form ─── */
  initContactForm();

  /* ─── Newsletter form ─── */
  initNewsletterForm();

  /* ─── Registration form ─── */
  initRegistrationForm();

  /* ─── Smooth scroll for anchors ─── */
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const id = a.getAttribute('href').slice(1);
      const el = document.getElementById(id);
      if (el) {
        e.preventDefault();
        const offset = (document.getElementById('navbar')?.offsetHeight || 80) + 16;
        window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - offset, behavior: 'smooth' });
      }
    });
  });

  /* ─── Tabs ─── */
  initTabs();

  /* ─── Image lazy placeholder ─── */
  document.querySelectorAll('img[data-src]').forEach(img => {
    const io = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          img.src = img.dataset.src;
          obs.unobserve(img);
        }
      });
    });
    io.observe(img);
  });
});

/* ═══════════════════════════════════════════════════════════
   COUNTER ANIMATION
═══════════════════════════════════════════════════════════ */
function initCounters() {
  const counters = document.querySelectorAll('[data-count]');
  if (!counters.length) return;

  const easeOut = t => 1 - Math.pow(1 - t, 3);

  const animate = (el, target, duration) => {
    const start = performance.now();
    const update = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      el.textContent = Math.floor(easeOut(progress) * target);
      if (progress < 1) requestAnimationFrame(update);
      else el.textContent = target;
    };
    requestAnimationFrame(update);
  };

  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && !entry.target.dataset.counted) {
        entry.target.dataset.counted = '1';
        animate(entry.target, parseInt(entry.target.dataset.count), 2000);
      }
    });
  }, { threshold: 0.5 });

  counters.forEach(c => io.observe(c));
}

/* ═══════════════════════════════════════════════════════════
   FETCH HELPER
═══════════════════════════════════════════════════════════ */
async function postJSON(url, data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  const res  = await fetch(url, { method: 'POST', body: fd });
  return res.json();
}

/* ═══════════════════════════════════════════════════════════
   FORM HELPERS
═══════════════════════════════════════════════════════════ */
function setFormLoading(form, loading) {
  const btn    = form.querySelector('[type="submit"]');
  const text   = btn?.querySelector('.btn-text');
  const spinner= btn?.querySelector('.btn-spinner');
  if (btn) btn.disabled = loading;
  if (text)   text.hidden   = loading;
  if (spinner)spinner.hidden = !loading;
}

function showFormMsg(el, success, message) {
  if (!el) return;
  el.className = 'form-msg ' + (success ? 'text-success' : 'text-error');
  el.style.color = success ? '#065f46' : '#991b1b';
  el.textContent = message;
}

function validateField(input) {
  const errEl = input.closest('.form-group')?.querySelector('.form-error');
  let valid = true;
  input.classList.remove('error');

  if (input.required && !input.value.trim()) {
    valid = false;
  } else if (input.type === 'email' && input.value.trim()) {
    valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim());
  } else if (input.minLength > 0 && input.value.length < input.minLength) {
    valid = false;
  }

  if (!valid) {
    input.classList.add('error');
    if (errEl) errEl.classList.add('visible');
  } else {
    if (errEl) errEl.classList.remove('visible');
  }
  return valid;
}

function validateForm(form) {
  let valid = true;
  form.querySelectorAll('[required], [minlength]').forEach(input => {
    if (!validateField(input)) valid = false;
  });
  return valid;
}

/* ═══════════════════════════════════════════════════════════
   CONTACT FORM
═══════════════════════════════════════════════════════════ */
function initContactForm() {
  const form = document.getElementById('contactForm');
  if (!form) return;

  // Live validation
  form.querySelectorAll('input, textarea, select').forEach(field => {
    field.addEventListener('blur', () => validateField(field));
  });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    if (!validateForm(form)) return;

    setFormLoading(form, true);
    const msg = document.getElementById('contactMsg');

    try {
      const data = Object.fromEntries(new FormData(form));
      const res  = await postJSON('/api/contact.php', data);
      if (res.success) {
        showFormMsg(msg, true, res.message);
        form.reset();
      } else {
        showFormMsg(msg, false, res.message || 'Une erreur est survenue.');
      }
    } catch {
      showFormMsg(msg, false, 'Erreur réseau. Veuillez réessayer.');
    } finally {
      setFormLoading(form, false);
    }
  });
}

/* ═══════════════════════════════════════════════════════════
   NEWSLETTER FORM
═══════════════════════════════════════════════════════════ */
function initNewsletterForm() {
  const form = document.getElementById('newsletterForm');
  if (!form) return;

  form.addEventListener('submit', async e => {
    e.preventDefault();
    setFormLoading(form, true);
    const msg = document.getElementById('newsletterMsg');

    try {
      const data = Object.fromEntries(new FormData(form));
      const res  = await postJSON('/api/newsletter.php', data);
      showFormMsg(msg, res.success, res.message);
      if (res.success) form.reset();
    } catch {
      showFormMsg(msg, false, 'Erreur réseau.');
    } finally {
      setFormLoading(form, false);
    }
  });
}

/* ═══════════════════════════════════════════════════════════
   REGISTRATION FORM (formations)
═══════════════════════════════════════════════════════════ */
function initRegistrationForm() {
  const form = document.getElementById('registrationForm');
  if (!form) return;

  form.querySelectorAll('input, textarea, select').forEach(field => {
    field.addEventListener('blur', () => validateField(field));
  });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    if (!validateForm(form)) return;

    setFormLoading(form, true);
    const msg = document.getElementById('registrationMsg');

    try {
      const data = Object.fromEntries(new FormData(form));
      const res  = await postJSON('/api/inscription.php', data);
      if (res.success) {
        showFormMsg(msg, true, res.message);
        form.reset();
      } else {
        showFormMsg(msg, false, res.message || 'Une erreur est survenue.');
      }
    } catch {
      showFormMsg(msg, false, 'Erreur réseau. Veuillez réessayer.');
    } finally {
      setFormLoading(form, false);
    }
  });
}

/* ═══════════════════════════════════════════════════════════
   TABS
═══════════════════════════════════════════════════════════ */
function initTabs() {
  document.querySelectorAll('.tabs-nav').forEach(nav => {
    const tabs  = nav.querySelectorAll('[data-tab]');
    const panes = nav.closest('.tabs-wrapper')?.querySelectorAll('.tab-pane');
    if (!panes) return;

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        tabs.forEach(t => t.classList.remove('active'));
        panes.forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(target)?.classList.add('active');
      });
    });
  });
}

/* ═══════════════════════════════════════════════════════════
   ADMIN HELPERS (sidebar, confirm, flash)
═══════════════════════════════════════════════════════════ */
function initAdmin() {
  /* Sidebar toggle */
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar       = document.getElementById('adminSidebar');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
  }

  /* Confirm delete */
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm || 'Confirmer cette action ?')) e.preventDefault();
    });
  });

  /* Auto-dismiss flash */
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => a.style.opacity = '0');
    setTimeout(() => document.querySelectorAll('.alert').forEach(a => a.remove()), 500);
  }, 5000);

  /* DataTable minimal sort */
  document.querySelectorAll('table[data-sortable] th[data-col]').forEach(th => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', () => {
      const table = th.closest('table');
      const col   = parseInt(th.dataset.col);
      const tbody = table.querySelector('tbody');
      const rows  = Array.from(tbody.querySelectorAll('tr'));
      const asc   = th.dataset.asc !== '1';
      th.dataset.asc = asc ? '1' : '0';
      rows.sort((a, b) => {
        const va = a.cells[col]?.textContent.trim() || '';
        const vb = b.cells[col]?.textContent.trim() || '';
        return asc ? va.localeCompare(vb) : vb.localeCompare(va);
      });
      rows.forEach(r => tbody.appendChild(r));
    });
  });

  /* Slug generator */
  const titleInput = document.getElementById('titleInput');
  const slugInput  = document.getElementById('slugInput');
  if (titleInput && slugInput && !slugInput.value) {
    titleInput.addEventListener('input', () => {
      slugInput.value = slugify(titleInput.value);
    });
  }

  /* Character counter */
  document.querySelectorAll('[data-maxlength]').forEach(el => {
    const max = parseInt(el.dataset.maxlength);
    const counter = document.createElement('small');
    counter.className = 'char-counter text-muted';
    el.after(counter);
    const update = () => {
      const left = max - el.value.length;
      counter.textContent = left + ' caractères restants';
      counter.style.color = left < 20 ? '#e63946' : '';
    };
    el.addEventListener('input', update);
    update();
  });
}

/* Slugify (client-side mirror of PHP slugify) */
function slugify(str) {
  const map = {'à':'a','á':'a','â':'a','ä':'a','ç':'c','è':'e','é':'e','ê':'e','ë':'e','î':'i','ï':'i','ô':'o','ö':'o','ù':'u','ú':'u','û':'u','ü':'u','ÿ':'y'};
  return str.toLowerCase()
    .replace(/[àáâäçèéêëîïôöùúûüÿ]/g, m => map[m] || m)
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/[\s-]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

/* ─── Init admin if on admin page ─── */
if (document.body.classList.contains('admin-page')) {
  document.addEventListener('DOMContentLoaded', initAdmin);
}
