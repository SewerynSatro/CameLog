/* CameLog – widoki listy i szczegółów rośliny. */

const Plants = {
  async renderList(_, user) {
    const content = `
      <section class="page-head">
        <h1>Moje rośliny</h1>
        <p>Zadbaj o swoją zieloną oazę.</p>
      </section>

      <div class="filters mb-3">
        <div class="input-wrap filters-grow">
          <span class="leading-icon">${Icons.search}</span>
          <input id="plants-search" class="input has-icon" placeholder="Szukaj po nazwie rośliny" />
        </div>
        <input id="filter-species" class="input" placeholder="Gatunek" />
        <select id="filter-location" class="select">
          <option value="">Lokalizacja</option>
          <option>Salon</option><option>Sypialnia</option><option>Kuchnia</option>
          <option>Łazienka</option><option>Biuro</option><option>Parapet</option><option>Balkon</option>
        </select>
        <a href="/plants/create" class="btn btn-primary btn-sm" data-link>${Icons.plus} Dodaj roślinę</a>
      </div>

      <div id="plants-grid" class="plant-grid">${UI.loader()}</div>`;
    UI.renderShell({ active: '/plants', user, content });

    const grid = document.getElementById('plants-grid');
    const search = document.getElementById('plants-search');
    const fSpecies = document.getElementById('filter-species');
    const fLoc = document.getElementById('filter-location');

    async function load() {
      const params = new URLSearchParams();
      if (search.value) params.set('search', search.value);
      if (fSpecies.value) params.set('species', fSpecies.value);
      if (fLoc.value) params.set('location', fLoc.value);
      grid.innerHTML = UI.loader();
      try {
        const r = await API.get('/api/plants?' + params.toString());
        if (!r.plants || r.plants.length === 0) {
          grid.innerHTML = UI.empty({
            title: 'Brak roślin',
            desc: 'Dodaj pierwszą roślinę, aby rozpocząć pielęgnację.',
            action: '<a href="/plants/create" class="btn btn-primary mt-2" data-link>Dodaj pierwszą roślinę</a>',
          });
          return;
        }
        grid.innerHTML = r.plants.map(plantCard).join('');
      } catch (err) {
        grid.innerHTML = UI.empty({ title: 'Błąd', desc: err.message || 'Nie udało się pobrać roślin' });
      }
    }

    let dt;
    [search, fSpecies, fLoc].forEach(el => el.addEventListener('input', () => {
      clearTimeout(dt); dt = setTimeout(load, 200);
    }));

    grid.addEventListener('click', async (e) => {
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
          message: 'Tej operacji nie da się cofnąć. Wszystkie taski i historia zostaną usunięte.',
          confirmText: 'Usuń', danger: true,
        });
        if (!ok) return;
        try {
          await API.delete('/api/plants/' + delBtn.dataset.delete);
          UI.toast('Roślina usunięta', 'success');
          load();
        } catch (err) { UI.toast(err.message || 'Błąd', 'error'); }
        return;
      }
      if (card && card.dataset.id) {
        Router.navigate('/plants/' + card.dataset.id);
      }
    });

    load();
  },

  async renderDetail({ id }, user) {
    const content = `<div id="plant-detail">${UI.loader()}</div>`;
    UI.renderShell({ active: '/plants', user, content });
    const root = document.getElementById('plant-detail');

    try {
      const [pRes, hRes, tRes, sRes] = await Promise.all([
        API.get('/api/plants/' + id),
        API.get('/api/plants/' + id + '/history'),
        API.get('/api/plants/' + id + '/tasks'),
        API.get('/api/plants/' + id + '/stats'),
      ]);
      const p = pRes.plant;
      const history = hRes.history || [];
      const tasks = tRes.tasks || [];
      const stats = sRes.stats || {};

      let speciesPanel = '';
      if (p.species_common || p.species_scientific) {
        speciesPanel = `
          <div class="api-panel">
            <div class="api-panel-title">${Icons.sparkle} ZALECENIA PIELĘGNACYJNE Z API <span class="pill">Perenual</span></div>
            <div class="api-grid">
              <div class="api-cell"><div class="label-sm">Gatunek (Baza)</div>${UI.escapeHtml(p.species_common || '—')}<br><em class="text-sm text-muted">${UI.escapeHtml(p.species_scientific || '')}</em></div>
              <div class="api-cell"><div class="label-sm">Podlewanie</div>${UI.escapeHtml(p.species_watering || '—')}<br>${p.watering_interval_days ? `<small class="text-muted">Co ${p.watering_interval_days} dni</small>` : ''}</div>
              <div class="api-cell"><div class="label-sm">Nasłonecznienie</div>${UI.escapeHtml(p.species_sunlight || '—')}</div>
              <div class="api-cell"><div class="label-sm">Poziom trudności</div>${UI.escapeHtml(p.species_care_level || p.care_level || '—')}</div>
            </div>
          </div>`;
      }

      const historyHtml = history.length === 0
        ? '<p class="text-muted">Brak wpisów. Zaznaczone jako wykonane taski będą tu rejestrowane.</p>'
        : history.slice(0, 10).map(h => `
            <div class="media-row">
              <div class="media-row-icon">${UI.taskTypeIcon(h.type)}</div>
              <div class="media-row-body">
                <div class="media-row-title">${UI.escapeHtml(UI.taskTypeLabel(h.type))}</div>
                <div class="media-row-meta">${UI.formatDate(h.performed_at)}</div>
                ${h.note ? `<div class="media-row-meta">${UI.escapeHtml(h.note)}</div>` : ''}
              </div>
            </div>`).join('');

      const tasksHtml = tasks.filter(t => t.status === 'pending').slice(0, 5).map(t => `
        <div class="task-card" data-task-id="${t.id}">
          <button class="task-checkbox" data-complete="${t.id}" aria-label="Oznacz wykonane"></button>
          <div class="task-title">
            ${UI.escapeHtml(t.title)}
            <span class="task-meta">${UI.taskTypeLabel(t.type)} · ${UI.formatRelative(t.due_date)}</span>
          </div>
          <span class="badge badge-info task-badge">${UI.formatRelative(t.due_date)}</span>
          <span class="icon task-actions">${UI.taskTypeIcon(t.type)}</span>
        </div>`).join('') || '<p class="text-muted">Brak otwartych tasków dla tej rośliny.</p>';

      root.innerHTML = `
        <div class="card mb-3 plant-detail-hero">
          <div class="plant-detail-media">
            ${UI.plantImg(p)}
          </div>
          <div>
            ${UI.healthBadge(p.health_status)}
            <h1 class="mt-1">${UI.escapeHtml(p.name)}</h1>
            <p class="text-muted italic">${UI.escapeHtml(p.species_common || p.custom_species_name || '')}</p>
            <div class="plant-detail-chips">
              <span class="chip">${Icons.pin} Lokalizacja: ${UI.escapeHtml(p.location || '—')}</span>
              <span class="chip">${Icons.calendar} Posadzono: ${UI.formatDate(p.planted_at)}</span>
            </div>
          </div>
          <div class="plant-detail-actions">
            <a class="btn btn-outline" href="/plants/${p.id}/edit" data-link>${Icons.edit} Edytuj</a>
            <button class="btn btn-danger" id="btn-delete-plant" aria-label="Usuń roślinę">${Icons.trash}</button>
          </div>
        </div>

        <div class="two-col">
          <div>
            <h2 class="section-header section-header--flush">Przegląd</h2>
            <div class="form-grid mb-3">
              <div class="card-tinted">
                <div class="label-sm">Potrzeby</div>
                <div class="mt-2">
                  <div class="care-row"><span class="icon">${Icons.drop}</span><div><strong>Podlewanie</strong><br><span class="text-muted text-sm">${p.watering_interval_days ? 'Co ' + p.watering_interval_days + ' dni' : '—'}</span></div></div>
                  <div class="care-row"><span class="icon">${Icons.sun}</span><div><strong>Światło</strong><br><span class="text-muted text-sm">${UI.escapeHtml(p.species_sunlight || 'Jasne, rozproszone')}</span></div></div>
                  <div class="care-row"><span class="icon">${Icons.flask}</span><div><strong>Nawożenie</strong><br><span class="text-muted text-sm">${p.fertilizing_interval_days ? 'Co ' + p.fertilizing_interval_days + ' dni' : '—'}</span></div></div>
                </div>
              </div>
              <div class="card-tinted">
                <div class="label-sm">Notatki</div>
                <div class="mt-2 italic">${UI.escapeHtml(p.notes || 'Brak notatek.')}</div>
              </div>
            </div>

            ${speciesPanel}

            <h2 class="section-header">${Icons.tasks} Taski</h2>
            <div class="task-list" id="plant-tasks">${tasksHtml}</div>

            <h2 class="section-header">${Icons.history} Historia</h2>
            <div class="card">${historyHtml}</div>
          </div>

          <aside>
            <div class="card-tinted mb-3">
              <div class="label-sm">Szybkie akcje</div>
              <a href="/tasks/create?plant=${p.id}" data-link class="btn btn-primary btn-block mt-2">${Icons.plus} Dodaj zadanie</a>
              <button class="btn btn-outline btn-block mt-2" id="btn-quick-water">${Icons.drop} Zarejestruj podlanie</button>
              <button class="btn btn-outline btn-block mt-2" id="btn-quick-fert">${Icons.flask} Zarejestruj nawożenie</button>
            </div>

            <div class="card">
              <div class="label-sm">Statystyki (30 dni)</div>
              <div class="mt-2">
                <div class="bar-row"><span>Podlewanie</span><strong>${stats.watering || 0}×</strong></div>
                <div class="bar"><span style="width:${Math.min(100,(stats.watering||0)*10)}%"></span></div>
                <div class="bar-row"><span>Nawożenie</span><strong>${stats.fertilizing || 0}×</strong></div>
                <div class="bar bar-tertiary"><span style="width:${Math.min(100,(stats.fertilizing||0)*15)}%"></span></div>
                <div class="bar-row"><span>Zraszanie</span><strong>${stats.misting || 0}×</strong></div>
                <div class="bar"><span style="width:${Math.min(100,(stats.misting||0)*10)}%"></span></div>
              </div>
            </div>
          </aside>
        </div>`;

      document.getElementById('btn-delete-plant').addEventListener('click', async () => {
        const ok = await UI.confirm({
          title: 'Usunąć roślinę?',
          message: 'Operacja jest nieodwracalna.',
          confirmText: 'Usuń', danger: true,
        });
        if (!ok) return;
        await API.delete('/api/plants/' + p.id);
        UI.toast('Roślina usunięta', 'success');
        Router.navigate('/plants');
      });

      document.getElementById('btn-quick-water').addEventListener('click', async () => {
        await API.post('/api/plants/' + p.id + '/history', { type: 'watering', note: 'Szybki wpis' });
        UI.toast('Zarejestrowano podlanie', 'success');
        Router.resolve();
      });
      document.getElementById('btn-quick-fert').addEventListener('click', async () => {
        await API.post('/api/plants/' + p.id + '/history', { type: 'fertilizing', note: 'Szybki wpis' });
        UI.toast('Zarejestrowano nawożenie', 'success');
        Router.resolve();
      });

      document.getElementById('plant-tasks').addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-complete]');
        if (!btn) return;
        try {
          await API.patch('/api/tasks/' + btn.dataset.complete + '/complete', {});
          UI.toast('Zadanie wykonane', 'success');
          Router.resolve();
        } catch (err) { UI.toast(err.message || 'Błąd', 'error'); }
      });
    } catch (err) {
      root.innerHTML = UI.empty({ title: 'Nie znaleziono', desc: err.message || 'Roślina nie istnieje.' });
    }
  },
};

function plantCard(p) {
  const next = p.next_task;
  return `
    <article class="plant-card" data-id="${p.id}">
      <div class="plant-card-img">
        ${UI.plantImg(p)}
        ${UI.healthBadge(p.health_status)}
      </div>
      <div class="plant-card-body">
        <div class="plant-card-title">
          <h3>${UI.escapeHtml(p.name)}</h3>
          <div class="actions">
            <button data-edit="${p.id}" aria-label="Edytuj">${Icons.edit}</button>
            <button data-delete="${p.id}" aria-label="Usuń">${Icons.trash}</button>
          </div>
        </div>
        <div class="plant-card-species">${UI.escapeHtml(p.species_common || p.custom_species_name || '—')}</div>
        <div class="plant-card-meta">
          ${p.location ? `<span class="chip">${Icons.pin} ${UI.escapeHtml(p.location)}</span>` : ''}
          ${p.planted_at ? `<span class="chip">${Icons.calendar} ${UI.formatDate(p.planted_at)}</span>` : ''}
        </div>
        ${next ? `
          <div class="plant-card-next">
            ${UI.taskTypeIcon(next.type)}
            <div>
              <div class="label-sm">Następny task</div>
              <div>${UI.escapeHtml(UI.taskTypeLabel(next.type))}: ${UI.formatRelative(next.due_date)}</div>
            </div>
          </div>` : ''}
      </div>
    </article>`;
}
