/* CameLog – wejście SPA, dashboard i rejestracja tras. */

const Dashboard = {
  async render(_, user) {
    const greeting = greetingFor(user);
    const content = `
      <div class="page-toolbar">
        <section class="page-head">
          <h1>${UI.escapeHtml(greeting)}, ${UI.escapeHtml(user.name.split(' ')[0])} 🌵</h1>
          <p>Oto stan Twojej zielonej oazy.</p>
        </section>
        <a href="/plants/create" class="btn btn-primary btn-sm" data-link>${Icons.plus} Dodaj roślinę</a>
      </div>

      <div class="stat-grid" id="d-stats">${UI.loader()}</div>

      <div class="two-col mt-2">
        <div>
          <h2 class="section-header">${Icons.calendar} Dzisiaj do zrobienia <span class="count-pill primary" id="today-count">…</span></h2>
          <div id="d-today" class="task-list">${UI.loader()}</div>

          <h2 class="section-header">${Icons.plants} Twoje rośliny</h2>
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
          <div class="stat-row"><div class="stat-label">Moje rośliny</div><span class="icon">${Icons.plants}</span></div>
          <div class="stat-value">${o.plants_count || 0}</div>
        </div>
        <div class="stat-tile tile-warning">
          <div class="stat-row"><div class="stat-label">Taski na dziś</div><span class="icon">${Icons.calendar}</span></div>
          <div class="stat-value">${o.today_tasks || 0}</div>
        </div>
        <div class="stat-tile tile-danger">
          <div class="stat-row"><div class="stat-label">Zaległe</div><span class="icon">${Icons.warning}</span></div>
          <div class="stat-value">${o.overdue_tasks || 0}</div>
        </div>
        <div class="stat-tile tile-success">
          <div class="stat-row"><div class="stat-label">Wykonane (7 dni)</div><span class="icon">${Icons.check}</span></div>
          <div class="stat-value">${o.week_done_tasks || 0}</div>
        </div>`;

      const todayList = today.tasks || [];
      document.getElementById('today-count').textContent = todayList.length;
      const todayNode = document.getElementById('d-today');
      if (todayList.length === 0) {
        todayNode.innerHTML = `<div class="card-tinted card-centered text-muted" style="padding:var(--s-3)"><span class="icon">${Icons.check}</span><p class="mt-2">Wszystko zrobione na dziś! Cieszmy się oazą spokoju.</p></div>`;
      } else {
        todayNode.innerHTML = todayList.slice(0, 6).map(taskCard).join('');
      }

      const ps = plants.plants || [];
      const plantsNode = document.getElementById('d-plants');
      let currentPlantPage = 1;
      const plantsPerPage = 5;

      function renderDashboardPlants() {
        const start = (currentPlantPage - 1) * plantsPerPage;
        const pagePlants = ps.slice(start, start + plantsPerPage);
        
        let html = pagePlants.length ? pagePlants.map(plantCard).join('') : UI.empty({
          title: 'Brak roślin',
          desc: 'Dodaj pierwszą roślinę.',
          action: '<a class="btn btn-primary mt-2" href="/plants/create" data-link>Dodaj roślinę</a>',
        });

        if (ps.length > plantsPerPage) {
          const totalPages = Math.ceil(ps.length / plantsPerPage);
          html += `
            <div class="pagination mt-3" style="display: flex; justify-content: space-between; align-items: center; padding: 0 var(--s-1);">
              <button class="btn btn-sm btn-outline" id="d-plant-prev" ${currentPlantPage === 1 ? 'disabled' : ''}>${Icons.arrowRight} Poprzednie</button>
              <span class="text-sm font-semibold" style="color: var(--on-surface-variant);">Strona ${currentPlantPage} z ${totalPages}</span>
              <button class="btn btn-sm btn-outline" id="d-plant-next" ${currentPlantPage >= totalPages ? 'disabled' : ''}>Następne ${Icons.arrowRight}</button>
            </div>
          `;
          // Zmiana kierunku ikonki dla przycisku "Poprzednie"
          html = html.replace('id="d-plant-prev"', 'id="d-plant-prev" style="flex-direction: row-reverse;"');
        }
        
        plantsNode.innerHTML = html;

        if (ps.length > plantsPerPage) {
          const btnPrev = document.getElementById('d-plant-prev');
          const btnNext = document.getElementById('d-plant-next');
          if (btnPrev && currentPlantPage > 1) {
            btnPrev.addEventListener('click', () => { currentPlantPage--; renderDashboardPlants(); });
          }
          if (btnNext && currentPlantPage < Math.ceil(ps.length / plantsPerPage)) {
            btnNext.addEventListener('click', () => { currentPlantPage++; renderDashboardPlants(); });
          }
          // Fix for arrow icon rotation in prev
          if (btnPrev) {
             const svg = btnPrev.querySelector('svg');
             if (svg) svg.style.transform = 'rotate(180deg)';
          }
        }
      }
      renderDashboardPlants();

      const inc = (incoming.tasks || []).slice(0, 6);
      document.getElementById('d-incoming').innerHTML = inc.length ? inc.map(t => `
        <div class="media-row">
          <div class="media-row-icon">${UI.taskTypeIcon(t.type)}</div>
          <div class="media-row-body">
            <div class="media-row-title">${UI.escapeHtml(t.title)}</div>
            <div class="media-row-meta">${UI.escapeHtml(t.plant_name || '')} · ${UI.formatRelative(t.due_date)}</div>
          </div>
        </div>`).join('') : '<p class="text-muted">Brak nadchodzących tasków.</p>';

      // Działania na taskach (wykonane za pomocą delegacji na całym widoku)
      document.getElementById('d-today').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-complete]');
        if (!b) return;
        try {
          await API.patch('/api/tasks/' + b.dataset.complete + '/complete', {});
          UI.toast('Wykonane!', 'success');
          Router.resolve();
        } catch (err) { UI.toast(err.message || 'Błąd', 'error'); }
      });

      // Działania na roślinach
      document.getElementById('d-plants').addEventListener('click', async (e) => {
        const editBtn = e.target.closest('[data-edit]');
        const delBtn = e.target.closest('[data-delete]');
        const card = e.target.closest('.plant-card');
        if (editBtn) {
          e.stopPropagation();
          Router.navigate('/plants/' + editBtn.dataset.edit + '/edit');
          return;
        }
        if (delBtn) {
          e.stopPropagation();
          const ok = await UI.confirm({
            title: 'Usunąć roślinę?',
            message: 'Tej operacji nie da się cofnąć.',
            confirmText: 'Usuń', danger: true,
          });
          if (!ok) return;
          try {
            await API.delete('/api/plants/' + delBtn.dataset.delete);
            UI.toast('Roślina usunięta', 'success');
            Router.resolve();
          } catch (err) { UI.toast(err.message || 'Błąd', 'error'); }
          return;
        }
        if (card && card.dataset.id) {
          Router.navigate('/plants/' + card.dataset.id);
        }
      });
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
