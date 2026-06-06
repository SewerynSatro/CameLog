/* CameLog – wejście SPA, dashboard i rejestracja tras. */

const Dashboard = {
  async render(_, user) {
    const greeting = greetingFor(user);
    const content = `
      <section class="page-head" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px">
        <div>
          <h1>${UI.escapeHtml(greeting)}, ${UI.escapeHtml(user.name.split(' ')[0])} 🌵</h1>
          <p>Oto stan Twojej zielonej oazy.</p>
        </div>
        <div class="page-actions">
          <a href="/tasks/create" class="btn btn-secondary" data-link>${Icons.tasks} Dodaj task</a>
          <a href="/plants/create" class="btn btn-primary" data-link>${Icons.plus} Dodaj roślinę</a>
        </div>
      </section>

      <div class="stat-grid" id="d-stats">${UI.loader()}</div>

      <div class="two-col mt-2">
        <div>
          <h2 class="section-header">${Icons.calendar} Dzisiaj do zrobienia <span class="count-pill primary" id="today-count">…</span></h2>
          <div id="d-today" class="task-list">${UI.loader()}</div>

          <h2 class="section-header" style="margin-top:32px">${Icons.plants} Twoje rośliny</h2>
          <div id="d-plants" class="plant-grid">${UI.loader()}</div>
        </div>
        <aside>
          <div class="card mb-3">
            <h2>${Icons.list} Nadchodzące</h2>
            <div id="d-incoming" class="mt-2">${UI.loader()}</div>
          </div>
          <div class="card-tinted">
            <h3>${Icons.sparkle} Inspiracja dnia</h3>
            <p class="text-muted mt-1">"Cierpliwa opieka rodzi obfite plony" – nie spiesz się z podlewaniem, gleba sama Ci powie, kiedy potrzebuje wody.</p>
          </div>
        </aside>
      </div>`;
    UI.renderShell({ active: '/dashboard', user, content });

    try {
      const [stats, today, plants, incoming] = await Promise.all([
        API.get('/api/stats/overview'),
        API.get('/api/tasks/today'),
        API.get('/api/plants'),
        API.get('/api/tasks/incoming'),
      ]);

      const o = stats.overview || {};
      document.getElementById('d-stats').innerHTML = `
        <div class="stat-tile">
          <div style="display:flex;justify-content:space-between"><div class="stat-label">Moje rośliny</div>${Icons.plants}</div>
          <div class="stat-value">${o.plants_count || 0}</div>
        </div>
        <div class="stat-tile tile-warning">
          <div style="display:flex;justify-content:space-between"><div class="stat-label">Taski na dziś</div>${Icons.calendar}</div>
          <div class="stat-value">${o.tasks_today || 0}</div>
        </div>
        <div class="stat-tile tile-danger">
          <div style="display:flex;justify-content:space-between"><div class="stat-label">Zaległe</div>${Icons.warning}</div>
          <div class="stat-value">${o.tasks_overdue || 0}</div>
        </div>
        <div class="stat-tile tile-success">
          <div style="display:flex;justify-content:space-between"><div class="stat-label">Następne 7 dni</div>${Icons.calendar}</div>
          <div class="stat-value">${o.tasks_done_week || 0}</div>
        </div>`;

      const todayList = today.tasks || [];
      document.getElementById('today-count').textContent = todayList.length;
      const todayNode = document.getElementById('d-today');
      if (todayList.length === 0) {
        todayNode.innerHTML = `<div class="card-tinted text-muted" style="text-align:center;padding:24px">${Icons.check}<p class="mt-2">Wszystko zrobione na dziś! Cieszmy się oazą spokoju.</p></div>`;
      } else {
        todayNode.innerHTML = todayList.slice(0, 6).map(taskCard).join('');
      }

      const ps = (plants.plants || []).slice(0, 3);
      const plantsNode = document.getElementById('d-plants');
      plantsNode.innerHTML = ps.length ? ps.map(plantCard).join('') : UI.empty({
        title: 'Brak roślin',
        desc: 'Dodaj pierwszą roślinę.',
        action: '<a class="btn btn-primary mt-2" href="/plants/create" data-link>Dodaj roślinę</a>',
      });

      const inc = (incoming.tasks || []).slice(0, 6);
      document.getElementById('d-incoming').innerHTML = inc.length ? inc.map(t => `
        <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--outline-variant)">
          <div style="width:36px;height:36px;border-radius:9999px;background:var(--surface-container);display:flex;align-items:center;justify-content:center">${UI.taskTypeIcon(t.type)}</div>
          <div style="flex:1">
            <div style="font-weight:600">${UI.escapeHtml(t.title)}</div>
            <div class="text-muted" style="font-size:13px">${UI.escapeHtml(t.plant_name || '')} · ${UI.formatRelative(t.due_date)}</div>
          </div>
        </div>`).join('') : '<p class="text-muted">Brak nadchodzących tasków.</p>';

      // Działania na taskach
      document.querySelectorAll('[data-complete]').forEach(b => b.addEventListener('click', async () => {
        try {
          await API.patch('/api/tasks/' + b.dataset.complete + '/complete', {});
          UI.toast('Wykonane!', 'success');
          Router.resolve();
        } catch (err) { UI.toast(err.message || 'Błąd', 'error'); }
      }));
    } catch (err) {
      document.getElementById('d-stats').innerHTML = UI.empty({ title: 'Błąd', desc: err.message });
    }
  },
};

function greetingFor(user) {
  const h = new Date().getHours();
  if (h < 5) return 'Dobranoc';
  if (h < 12) return 'Dzień dobry';
  if (h < 18) return 'Cześć';
  return 'Dobry wieczór';
}

// === Rejestracja tras ===
Router.add('/login', () => Auth.renderLogin(), { auth: false });
Router.add('/register', () => Auth.renderRegister(), { auth: false });

Router.add('/', async (_, user) => {
  if (user) Router.navigate('/dashboard', true);
  else Router.navigate('/login', true);
}, { auth: false });

Router.add('/dashboard', (p, u) => Dashboard.render(p, u));
Router.add('/plants', (p, u) => Plants.renderList(p, u));
Router.add('/plants/create', (p, u) => PlantForm.renderCreate(p, u));
Router.add('/plants/{id}/edit', (p, u) => PlantForm.renderEdit(p, u));
Router.add('/plants/{id}', (p, u) => Plants.renderDetail(p, u));
Router.add('/tasks', (p, u) => Tasks.renderList(p, u));
Router.add('/tasks/create', (p, u) => Tasks.renderCreate(p, u));
Router.add('/notifications', (p, u) => Notifications.renderList(p, u));
Router.add('/stats', (p, u) => Stats.render(p, u));
Router.add('/profile', (_, u) => Auth.renderProfile(u));
Router.add('/admin/users', (p, u) => Admin.renderUsers(p, u), { admin: true });

document.addEventListener('DOMContentLoaded', () => Router.resolve());
