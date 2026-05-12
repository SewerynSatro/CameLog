/* CameLog – prosty SPA router używający History API. */

const Router = (() => {
  const routes = [];

  function add(pattern, handler, opts = {}) {
    const p = pattern.replace(/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/g, '(?<$1>[^/]+)');
    routes.push({ regex: new RegExp('^' + p + '$'), handler, opts });
  }

  function navigate(path, replace = false) {
    if (replace) history.replaceState({}, '', path);
    else history.pushState({}, '', path);
    resolve();
  }

  async function resolve() {
    const path = location.pathname || '/';
    const user = window.CURRENT_USER || await Auth.fetchUser();

    for (const r of routes) {
      const m = r.regex.exec(path);
      if (m) {
        const params = m.groups || {};
        const requiresAuth = r.opts.auth !== false;
        const requiresAdmin = r.opts.admin === true;

        if (requiresAuth && !user) {
          navigate('/login', true);
          return;
        }
        if (!requiresAuth && user && (path === '/login' || path === '/register')) {
          navigate('/dashboard', true);
          return;
        }
        if (requiresAdmin && (!user || user.role !== 'admin')) {
          UI.toast('Brak uprawnień', 'error');
          navigate('/dashboard', true);
          return;
        }
        try {
          await r.handler(params, user);
        } catch (e) {
          console.error(e);
          UI.toast(e.message || 'Wystąpił błąd', 'error');
        }
        window.scrollTo(0, 0);
        return;
      }
    }
    navigate(user ? '/dashboard' : '/login', true);
  }

  // Przechwytuj kliknięcia w linki SPA
  document.addEventListener('click', (e) => {
    const link = e.target.closest('a[data-link]');
    if (!link) return;
    const href = link.getAttribute('href');
    if (!href || href.startsWith('http') || href.startsWith('#')) return;
    e.preventDefault();
    navigate(href);
  });

  window.addEventListener('popstate', resolve);

  return { add, navigate, resolve };
})();
