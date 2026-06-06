/* CameLog – UI helpers, ikony, toast, modal, shell layoutu. */

const Icons = {
  dashboard: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
  plants: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22V11"/><path d="M5 11C5 7 8 4 12 4c4 0 7 3 7 7-3 0-7-1-7-3"/><path d="M5 11c0 4 3 7 7 7"/></svg>',
  tasks: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>',
  bell: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 1 1 12 0c0 5 2 6 2 7H4c0-1 2-2 2-7Z"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>',
  stats: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10M10 21V4M16 21V14M22 21H2"/></svg>',
  user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></svg>',
  shield: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v6c0 5 3 8 7 9 4-1 7-4 7-9V6l-7-3Z"/></svg>',
  plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
  search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>',
  edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
  trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>',
  block: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m5 5 14 14"/></svg>',
  unblock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0"/></svg>',
  drop: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c4 6 6 9 6 12a6 6 0 0 1-12 0c0-3 2-6 6-12Z"/></svg>',
  flask: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v6L4 19a2 2 0 0 0 2 3h12a2 2 0 0 0 2-3l-5-10V3"/><path d="M9 3h6"/></svg>',
  scissors: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="m20 4-12 8M20 20l-8.5-5.7"/></svg>',
  sun: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/></svg>',
  pin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-7 7-12a7 7 0 0 0-14 0c0 5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>',
  calendar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
  email: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
  lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
  arrowRight: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>',
  check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>',
  doubleCheck: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 12 5 5L17 7"/><path d="m11 17 11-10"/></svg>',
  warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 21h20Z"/><path d="M12 10v5M12 18h0"/></svg>',
  upload: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M6 10l6-6 6 6"/><path d="M4 20h16"/></svg>',
  sparkle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v6M12 15v6M3 12h6M15 12h6M5.6 5.6l4 4M14.4 14.4l4 4M18.4 5.6l-4 4M9.6 14.4l-4 4"/></svg>',
  filter: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h18l-7 9v6l-4 2v-8z"/></svg>',
  list: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>',
  home: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/></svg>',
  history: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-7"/><path d="M3 4v5h5"/><path d="M12 8v4l3 2"/></svg>',
  leaf: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 5C9 6 5 11 5 19c8 0 13-4 14-14Z"/><path d="M5 19c2-7 7-12 14-14"/></svg>',
};

const UI = {
  toast(message, type = 'info') {
    const root = document.getElementById('toast-root');
    if (!root) return;
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<span>${escapeHtml(message)}</span>`;
    root.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateX(20px)'; el.style.transition = 'all .25s'; }, 3000);
    setTimeout(() => el.remove(), 3300);
  },

  confirm({ title = 'Potwierdź', message = 'Czy na pewno?', confirmText = 'Potwierdź', cancelText = 'Anuluj', danger = false }) {
    return new Promise((resolve) => {
      const root = document.getElementById('modal-root');
      const wrap = document.createElement('div');
      wrap.className = 'modal-backdrop';
      wrap.innerHTML = `
        <div class="modal" role="dialog" aria-modal="true">
          <h3>${escapeHtml(title)}</h3>
          <p class="text-muted">${escapeHtml(message)}</p>
          <div class="modal-actions">
            <button class="btn btn-outline" data-act="cancel">${escapeHtml(cancelText)}</button>
            <button class="btn ${danger ? 'btn-danger' : 'btn-primary'}" data-act="ok">${escapeHtml(confirmText)}</button>
          </div>
        </div>`;
      root.appendChild(wrap);
      wrap.addEventListener('click', (e) => {
        if (e.target === wrap) { wrap.remove(); resolve(false); }
        const act = e.target.closest('[data-act]')?.dataset.act;
        if (act === 'ok') { wrap.remove(); resolve(true); }
        if (act === 'cancel') { wrap.remove(); resolve(false); }
      });
    });
  },

  escapeHtml: (s) => escapeHtml(s),

  navItems(role) {
    const items = [
      { href: '/dashboard', label: 'Dashboard', icon: Icons.dashboard },
      { href: '/plants', label: 'Rośliny', icon: Icons.plants },
      { href: '/tasks', label: 'Taski', icon: Icons.tasks },
      { href: '/notifications', label: 'Przypomnienia', icon: Icons.bell },
      { href: '/stats', label: 'Statystyki', icon: Icons.stats },
      { href: '/profile', label: 'Profil', icon: Icons.user },
    ];
    if (role === 'admin') items.push({ href: '/admin/users', label: 'Admin', icon: Icons.shield });
    return items;
  },

  renderShell({ active = '', user, content }) {
    const root = document.getElementById('app');
    if (!user) {
      root.className = 'app no-shell';
      root.innerHTML = content;
      return;
    }
    root.className = 'app';
    const nav = this.navItems(user.role).map(it => {
      const isActive = active === it.href || (active.startsWith(it.href) && it.href !== '/dashboard');
      return `
        <a href="${it.href}" class="sidebar-link ${isActive ? 'active' : ''}" data-link>
          ${it.icon}<span>${it.label}</span>
        </a>`;
    }).join('');

    const initials = (user.name || '?').split(' ').map(s => s[0]).slice(0, 2).join('').toUpperCase();

    root.innerHTML = `
      <aside class="sidebar">
        <div class="sidebar-brand">
          <img src="/assets/images/logo.svg" alt="CameLog" />
          <div>
            <div class="name">CameLog</div>
            <div class="subtitle">Desert Oasis Care</div>
          </div>
        </div>
        <nav class="sidebar-nav">${nav}</nav>
        <a href="/tasks/create" class="sidebar-cta sidebar-cta-task" data-link>${Icons.tasks} Dodaj task</a>
        <a href="/plants/create" class="sidebar-cta" data-link>${Icons.plus} Dodaj roślinę</a>
      </aside>
      <header class="mobile-header">
        <div class="name">CameLog</div>
        <div class="topbar-actions">
          <a href="/notifications" class="icon-btn" data-link aria-label="Powiadomienia">${Icons.bell}<span class="dot" id="notif-dot" hidden></span></a>
          <a href="/profile" class="avatar" data-link aria-label="Profil">${initials}</a>
        </div>
      </header>
      <main class="shell-main">
        <div class="topbar">
          <div></div>
          <div class="topbar-actions">
            <a href="/notifications" class="icon-btn" data-link aria-label="Powiadomienia">${Icons.bell}<span class="dot" id="notif-dot" hidden></span></a>
            <a href="/profile" class="avatar" data-link aria-label="Profil">${initials}</a>
          </div>
        </div>
        ${content}
      </main>
      <nav class="bottom-nav">
        <a href="/dashboard" data-link class="${active==='/dashboard'?'active':''}">${Icons.home}<span>Home</span></a>
        <a href="/plants" data-link class="${active==='/plants'?'active':''}">${Icons.plants}<span>Rośliny</span></a>
        <a href="/plants/create" data-link class="fab" aria-label="Dodaj">${Icons.plus}</a>
        <a href="/tasks" data-link class="${active==='/tasks'?'active':''}">${Icons.tasks}<span>Taski</span></a>
        <a href="/profile" data-link class="${active==='/profile'?'active':''}">${Icons.user}<span>Profil</span></a>
      </nav>
    `;
    this.updateNotifDot(user);
  },

  async updateNotifDot(user) {
    if (!user) return;
    try {
      const data = await API.get('/api/notifications');
      const dots = document.querySelectorAll('#notif-dot');
      dots.forEach(d => { d.hidden = !((data.unread_count || 0) > 0); });
    } catch (e) { /* offline ok */ }
  },

  formatDate(d) {
    if (!d) return '—';
    try {
      const dt = new Date(d.replace(' ', 'T'));
      if (isNaN(dt)) return d;
      return dt.toLocaleDateString('pl-PL', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch { return d; }
  },
  formatRelative(d) {
    if (!d) return '';
    const dt = new Date(d.replace(' ', 'T'));
    const today = new Date(); today.setHours(0,0,0,0);
    const target = new Date(dt); target.setHours(0,0,0,0);
    const diff = Math.round((target - today) / 86400000);
    if (diff === 0) return 'Dziś';
    if (diff === -1) return 'Wczoraj';
    if (diff === 1) return 'Jutro';
    if (diff > 1) return `Za ${diff} dni`;
    return `${Math.abs(diff)} dni temu`;
  },

  taskTypeLabel(type) {
    return ({
      watering: 'Podlewanie',
      fertilizing: 'Nawożenie',
      pruning: 'Przycinanie',
      repotting: 'Przesadzanie',
      misting: 'Zraszanie',
      custom: 'Inne',
    })[type] || type;
  },

  taskTypeIcon(type) {
    return ({
      watering: Icons.drop,
      fertilizing: Icons.flask,
      pruning: Icons.scissors,
      repotting: Icons.leaf,
      misting: Icons.drop,
      custom: Icons.tasks,
    })[type] || Icons.tasks;
  },

  healthBadge(status) {
    if (status === 'needs_attention') return `<span class="badge badge-warning">Potrzebuje uwagi</span>`;
    if (status === 'sick') return `<span class="badge badge-danger">Chora</span>`;
    return `<span class="badge badge-healthy">Zdrowa</span>`;
  },

  loader(label = 'Ładowanie…') {
    return `<div class="empty"><span class="loading"></span><p style="margin-top:12px">${escapeHtml(label)}</p></div>`;
  },

  empty({ title, desc, action }) {
    return `<div class="empty">
      <img src="/assets/images/logo.svg" alt="" />
      <h2>${escapeHtml(title || 'Brak danych')}</h2>
      <p>${escapeHtml(desc || '')}</p>
      ${action || ''}
    </div>`;
  },

  plantImg(plant) {
    if (plant.photo) return `<img src="/${escapeHtml(plant.photo)}" alt="${escapeHtml(plant.name)}" loading="lazy" />`;
    return `<img src="/assets/images/plant-placeholder.svg" alt="${escapeHtml(plant.name)}" />`;
  },
};

function escapeHtml(s) {
  if (s === null || s === undefined) return '';
  return String(s)
    .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;').replaceAll("'", '&#39;');
}
