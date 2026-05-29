/* ============================================================
   Mi Boutique — Core JS
   ============================================================ */

// ── API helper ───────────────────────────────────────────────
const API = {
  async request(url, options = {}) {
    try {
      const res = await fetch(url, {
        headers: { 'Content-Type': 'application/json', ...options.headers },
        credentials: 'same-origin',
        ...options,
      });
      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.error || 'Error del servidor');
      return data;
    } catch (e) {
      throw e;
    }
  },
  get:    (url)          => API.request(url),
  post:   (url, body)    => API.request(url, { method: 'POST', body: JSON.stringify(body) }),
  delete: (url)          => API.request(url, { method: 'DELETE' }),
  postForm: (url, form)  => fetch(url, { method: 'POST', body: form, credentials: 'same-origin' }).then(r => r.json()),
};

// ── Toast ────────────────────────────────────────────────────
const Toast = {
  container: null,
  init() {
    this.container = document.getElementById('toast-container');
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      document.body.appendChild(this.container);
    }
  },
  show(msg, type = 'default', duration = 3500) {
    if (!this.container) this.init();
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    const icon = { success: '✓', error: '✕', warning: '⚠' }[type] || 'ℹ';
    t.innerHTML = `<span>${icon}</span><span>${msg}</span>`;
    this.container.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, duration);
  },
  success: (msg) => Toast.show(msg, 'success'),
  error:   (msg) => Toast.show(msg, 'error'),
  warning: (msg) => Toast.show(msg, 'warning'),
};

// ── Modal ────────────────────────────────────────────────────
const Modal = {
  open(id)  { document.getElementById(id)?.classList.add('open'); },
  close(id) { document.getElementById(id)?.classList.remove('open'); },
  closeAll() { document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open')); },
};

// ── Confirm dialog ───────────────────────────────────────────
function confirmAction(message) {
  return new Promise(resolve => {
    if (window.confirm(message)) resolve(true);
    else resolve(false);
  });
}

// ── Format helpers ───────────────────────────────────────────
const fmt = {
  money:  (n) => '$' + parseFloat(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
  date:   (s) => s ? new Date(s).toLocaleDateString('es-MX') : '—',
  datetime: (s) => s ? new Date(s).toLocaleString('es-MX') : '—',
  pct:    (n) => parseFloat(n || 0).toFixed(1) + '%',
};

// ── Sidebar toggle ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  Toast.init();

  // Mobile sidebar
  const sidebar  = document.querySelector('.sidebar');
  const toggler  = document.querySelector('.sidebar-toggle');
  const overlay  = document.querySelector('.sidebar-overlay');
  toggler?.addEventListener('click',  () => sidebar?.classList.toggle('open'));
  overlay?.addEventListener('click',  () => sidebar?.classList.remove('open'));

  // Close modals on overlay click
  document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
  });

  // Close modals on Escape
  document.addEventListener('keydown', e => { if (e.key === 'Escape') Modal.closeAll(); });

  // Active nav item
  const currentPath = window.location.pathname;
  document.querySelectorAll('.nav-item').forEach(el => {
    if (el.getAttribute('href') && currentPath.includes(el.getAttribute('href'))) {
      el.classList.add('active');
    }
  });
});

// ── Logout ───────────────────────────────────────────────────
// BASE se inyecta desde head.php via <script>const BASE='...';</script>
async function logout() {
  try {
    await API.post(BASE + '/api/auth.php?action=logout', {});
  } finally {
    window.location.href = BASE + '/views/login.php';
  }
}
