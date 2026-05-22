/* CameLog – panel administratora. */

const Admin = {
  async renderUsers(_, user) {
    const content = `
      <section class="page-head">
        <h1>${Icons.shield} Panel administratora</h1>
        <p>Zarządzaj kontami opiekunów roślin.</p>
      </section>

      <div id="admin-stats" class="stat-grid">${UI.loader()}</div>

      <div class="filters mb-3">
        <div class="input-wrap filters-grow">
          <span class="leading-icon">${Icons.search}</span>
          <input id="a-search" class="input has-icon" placeholder="Szukaj po nazwie lub email" />
        </div>
        <select id="a-status" class="select">
          <option value="">Wszystkie statusy</option>
          <option value="active">Aktywni</option>
          <option value="blocked">Zablokowani</option>
        </select>
        <select id="a-role" class="select">
          <option value="">Wszystkie role</option>
          <option value="user">Użytkownicy</option>
          <option value="admin">Administratorzy</option>
        </select>
      </div>

      <div class="card card-flush">
        <table class="table" id="a-table">
          <thead>
            <tr>
              <th>Użytkownik</th><th>Email</th><th>Rola</th><th>Status</th><th>Rośliny</th><th>Dołączył</th><th></th>
            </tr>
          </thead>
          <tbody><tr><td colspan="7">${UI.loader()}</td></tr></tbody>
        </table>
      </div>`;
    UI.renderShell({ active: '/admin/users', user, content });

    const search = document.getElementById('a-search');
    const fStat = document.getElementById('a-status');
    const fRole = document.getElementById('a-role');
    let dt;
    [search, fStat, fRole].forEach(el => el.addEventListener('input', () => { clearTimeout(dt); dt = setTimeout(load, 200); }));

    async function load() {
      const params = new URLSearchParams();
      if (search.value) params.set('search', search.value);
      if (fStat.value) params.set('status', fStat.value);
      if (fRole.value) params.set('role', fRole.value);
      try {
        const r = await API.get('/api/admin/users?' + params.toString());
        renderStats(r.stats || {});
        const tbody = document.querySelector('#a-table tbody');
        if (!r.users || r.users.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-muted" style="text-align:center;padding:32px">Brak użytkowników.</td></tr>';
          return;
        }
        tbody.innerHTML = r.users.map(u => `
          <tr>
            <td data-label="Użytkownik">
              <div class="table-user">
                <div class="avatar avatar-sm">${(u.name || '?').split(' ').map(s=>s[0]).slice(0,2).join('').toUpperCase()}</div>
                <strong>${UI.escapeHtml(u.name)}</strong>
              </div>
            </td>
            <td data-label="Email">${UI.escapeHtml(u.email)}</td>
            <td data-label="Rola"><span class="badge ${u.role==='admin'?'badge-warning':'badge-info'}">${u.role==='admin'?'Admin':'Użytkownik'}</span></td>
            <td data-label="Status">${u.status==='active'?'<span class="badge badge-healthy">Aktywny</span>':'<span class="badge badge-danger">Zablokowany</span>'}</td>
            <td data-label="Rośliny">${u.plants_count || 0}</td>
            <td data-label="Dołączył">${UI.formatDate(u.created_at)}</td>
            <td data-label="Akcje">
              <div class="row-actions">
                ${u.status === 'blocked'
                  ? `<button title="Odblokuj" data-unblock="${u.id}">${Icons.unblock}</button>`
                  : `<button title="Zablokuj" data-block="${u.id}">${Icons.block}</button>`}
                <button title="Usuń" data-del="${u.id}">${Icons.trash}</button>
              </div>
            </td>
          </tr>`).join('');
      } catch (err) {
        document.querySelector('#a-table tbody').innerHTML = `<tr><td colspan="7">${UI.escapeHtml(err.message || 'Błąd')}</td></tr>`;
      }
    }

    function renderStats(s) {
      document.getElementById('admin-stats').innerHTML = `
        <div class="stat-tile"><div class="stat-label">Łącznie kont</div><div class="stat-value">${s.total || 0}</div></div>
        <div class="stat-tile tile-success"><div class="stat-label">Aktywni</div><div class="stat-value">${s.active || 0}</div></div>
        <div class="stat-tile tile-danger"><div class="stat-label">Zablokowani</div><div class="stat-value">${s.blocked || 0}</div></div>
        <div class="stat-tile tile-warning"><div class="stat-label">Administratorzy</div><div class="stat-value">${s.admins || 0}</div></div>`;
    }

    document.querySelector('#a-table').addEventListener('click', async (e) => {
      const b = e.target.closest('[data-block]');
      const u = e.target.closest('[data-unblock]');
      const d = e.target.closest('[data-del]');
      try {
        if (b) {
          const ok = await UI.confirm({ title: 'Zablokować użytkownika?', confirmText: 'Zablokuj', danger: true, message: 'Użytkownik nie będzie mógł korzystać z aplikacji.' });
          if (!ok) return;
          await API.patch('/api/admin/users/' + b.dataset.block + '/block', {});
          UI.toast('Zablokowano', 'success'); load();
        }
        if (u) {
          await API.patch('/api/admin/users/' + u.dataset.unblock + '/unblock', {});
          UI.toast('Odblokowano', 'success'); load();
        }
        if (d) {
          const ok = await UI.confirm({ title: 'Usunąć konto?', confirmText: 'Usuń', danger: true, message: 'Konto i wszystkie jego dane zostaną trwale usunięte.' });
          if (!ok) return;
          await API.delete('/api/admin/users/' + d.dataset.del);
          UI.toast('Usunięto', 'success'); load();
        }
      } catch (err) { UI.toast(err.message || 'Błąd', 'error'); }
    });

    load();
  },
};
