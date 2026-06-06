/* CameLog – widoki tasków. */

const Tasks = {
  async renderList(_, user) {
    const content = `
      <div class="page-toolbar">
        <section class="page-head">
          <h1>Twoje Zadania</h1>
          <p>Harmonogram pielęgnacji Twojej oazy na najbliższe dni.</p>
        </section>
        <div class="page-actions">
          <a href="/tasks/create" class="btn btn-primary" data-link>${Icons.plus} Dodaj zadanie</a>
        </div>
      </div>

      <div class="filters mt-3 mb-3">
        <select id="f-type" class="select">
          <option value="">Wszystkie typy</option>
          <option value="watering">Podlewanie</option>
          <option value="fertilizing">Nawożenie</option>
          <option value="pruning">Przycinanie</option>
          <option value="repotting">Przesadzanie</option>
          <option value="custom">Inne</option>
        </select>
        <select id="f-status" class="select">
          <option value="">Wszystkie statusy</option>
          <option value="pending">Otwarte</option>
          <option value="done">Wykonane</option>
          <option value="skipped">Pominięte</option>
        </select>
      </div>

      <div class="two-col">
        <div id="tasks-content">${UI.loader()}</div>
        <aside>
          <div class="card mb-3" id="tasks-summary">${UI.loader('Podsumowanie...')}</div>
          <div class="card-tinted card-centered" style="background:var(--secondary-container);color:var(--on-secondary-container)">
            <span class="icon">${Icons.leaf}</span>
            <h3 class="mt-2">Oaza spokoju</h3>
            <p>Regularna opieka sprawia, że Twoje rośliny rosną zdrowe i silne.</p>
          </div>
        </aside>
      </div>`;
    UI.renderShell({ active: '/tasks', user, content });

    const fType = document.getElementById('f-type');
    const fStatus = document.getElementById('f-status');
    [fType, fStatus].forEach(el => el.addEventListener('change', load));
    load();

    async function load() {
      const params = new URLSearchParams();
      if (fType.value) params.set('type', fType.value);
      if (fStatus.value) params.set('status', fStatus.value);
      try {
        const r = await API.get('/api/tasks?' + params.toString());
        const overdue = r.tasks.filter(t => isOverdue(t) && t.status === 'pending');
        const today = r.tasks.filter(t => isToday(t) && t.status === 'pending');
        const upcoming = r.tasks.filter(t => isFuture(t) && t.status === 'pending');
        const done = r.tasks.filter(t => t.status === 'done').slice(0, 6);

        const html = (label, icon, items, badge='info') => {
          if (items.length === 0) return '';
          return `
            <h2 class="section-header">${icon} ${label} <span class="count-pill ${badge}">${items.length}</span></h2>
            <div class="task-list mb-3">${items.map(taskCard).join('')}</div>`;
        };

        const node = document.getElementById('tasks-content');
        if (overdue.length === 0 && today.length === 0 && upcoming.length === 0 && done.length === 0) {
          node.innerHTML = UI.empty({ title: 'Brak zadań', desc: 'Dodaj nową roślinę, aby system stworzył pierwsze zadania.' });
          return;
        }
        node.innerHTML = `
          ${html('Zaległe', Icons.warning, overdue, 'danger')}
          ${html('Dzisiaj', Icons.calendar, today, 'primary')}
          ${html('Nadchodzące', Icons.list, upcoming)}
          ${done.length ? html('Ostatnio wykonane', Icons.check, done) : ''}`;

        // Summary
        const summary = document.getElementById('tasks-summary');
        const progress = today.length === 0 ? 100 : Math.round((today.filter(t => t.status === 'done').length / today.length) * 100);
        summary.innerHTML = `
          <h2 class="flex items-center gap-2">${Icons.stats} Podsumowanie</h2>
          <div class="stat-mini-grid">
            <div class="stat-tile"><div class="stat-value">${today.length}</div><div class="stat-label">DZISIAJ</div></div>
            <div class="stat-tile tile-danger"><div class="stat-value">${overdue.length}</div><div class="stat-label">ZALEGŁE</div></div>
          </div>
          <div class="bar-row mt-3"><span>Postęp na dziś</span><span>${progress}%</span></div>
          <div class="bar"><span style="width:${progress}%"></span></div>
          <div class="text-muted mt-2 text-center">Wykonano w tym tygodniu: ${done.length} zadań</div>`;
      } catch (err) {
        document.getElementById('tasks-content').innerHTML = UI.empty({ title: 'Błąd', desc: err.message });
      }
    }

    const tasksContent = document.getElementById('tasks-content');
    tasksContent.addEventListener('click', taskClickHandler);

    function taskClickHandler(e) {
      const cb = e.target.closest('[data-complete]');
      const sk = e.target.closest('[data-skip]');
      const del = e.target.closest('[data-delete-task]');
      if (cb) handleAction(cb.dataset.complete, 'complete');
      if (sk) handleAction(sk.dataset.skip, 'skip');
      if (del) handleAction(del.dataset.deleteTask, 'delete');
    }

    async function handleAction(id, action) {
      try {
        if (action === 'complete') await API.patch('/api/tasks/' + id + '/complete', {});
        if (action === 'skip') await API.patch('/api/tasks/' + id + '/skip', {});
        if (action === 'delete') {
          const ok = await UI.confirm({ title: 'Usunąć zadanie?', danger: true, confirmText: 'Usuń' });
          if (!ok) return;
          await API.delete('/api/tasks/' + id);
        }
        UI.toast('Zaktualizowano', 'success');
        load();
      } catch (err) { UI.toast(err.message || 'Błąd', 'error'); }
    }
  },

  async renderCreate(_, user) {
    const url = new URL(location.href);
    const presetPlant = url.searchParams.get('plant');
    const plantsResp = await API.get('/api/plants');
    const plants = plantsResp.plants || [];
    const content = `
      <section class="page-head">
        <h1>Dodaj zadanie</h1>
        <p>Zaplanuj kolejne czynności pielęgnacyjne.</p>
      </section>

      <form id="task-form" class="card form-card-narrow">
        <div class="form-grid">
          <div class="field">
            <label class="field-label">Roślina *</label>
            <select class="select" name="plant_id" required>
              <option value="">Wybierz roślinę…</option>
              ${plants.map(p => `<option value="${p.id}" ${presetPlant == p.id ? 'selected' : ''}>${UI.escapeHtml(p.name)}</option>`).join('')}
            </select>
          </div>
          <div class="field">
            <label class="field-label">Typ *</label>
            <select class="select" name="type" required>
              <option value="watering">Podlewanie</option>
              <option value="fertilizing">Nawożenie</option>
              <option value="pruning">Przycinanie</option>
              <option value="repotting">Przesadzanie</option>
              <option value="misting">Zraszanie</option>
              <option value="custom">Inne</option>
            </select>
          </div>
          <div class="field form-span-full">
            <label class="field-label">Tytuł *</label>
            <input class="input" name="title" required placeholder="np. Podlewanie Monstery" />
          </div>
          <div class="field form-span-full">
            <label class="field-label">Opis</label>
            <textarea class="textarea" name="description" placeholder="Dodatkowe wskazówki, np. dawka nawozu"></textarea>
          </div>
          <div class="field">
            <label class="field-label">Termin *</label>
            <input class="input" name="due_date" type="date" required value="${new Date().toISOString().substring(0,10)}" />
          </div>
          <div class="field">
            <label class="field-label">Powtarzaj co (dni)</label>
            <input class="input" name="repeat_interval_days" type="number" min="1" max="365" placeholder="puste = jednorazowo" />
          </div>
          <div class="field">
            <label class="field-label">Priorytet</label>
            <select class="select" name="priority">
              <option value="low">Niski</option>
              <option value="normal" selected>Normalny</option>
              <option value="high">Wysoki</option>
            </select>
          </div>
        </div>
        <div class="form-actions">
          <a href="/tasks" data-link class="btn btn-outline">Anuluj</a>
          <button class="btn btn-primary" type="submit">${Icons.check} Zapisz task</button>
        </div>
      </form>`;
    UI.renderShell({ active: '/tasks', user, content });

    document.getElementById('task-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      try {
        await API.post('/api/tasks', {
          plant_id: Number(fd.get('plant_id')),
          type: fd.get('type'),
          title: fd.get('title'),
          description: fd.get('description'),
          due_date: fd.get('due_date'),
          repeat_interval_days: fd.get('repeat_interval_days') ? Number(fd.get('repeat_interval_days')) : null,
          priority: fd.get('priority'),
        });
        UI.toast('Zadanie dodane', 'success');
        Router.navigate('/tasks');
      } catch (err) { UI.toast(err.message || 'Błąd', 'error'); }
    });
  },
};

function taskCard(t) {
  const overdue = isOverdue(t) && t.status === 'pending';
  const done = t.status === 'done';
  const priorityBadge = t.priority === 'high' ? '<span class="badge badge-danger">Wysoki</span>' :
                        t.priority === 'low' ? '<span class="badge badge-info">Niski</span>' : '';
  return `
    <div class="task-card ${overdue ? 'is-overdue' : ''} ${done ? 'is-done' : ''}">
      <button class="task-checkbox ${done ? 'is-done' : ''}" data-complete="${t.id}" aria-label="Wykonane">${done ? Icons.check : ''}</button>
      <div class="task-title">
        <span>${UI.escapeHtml(t.title || '')}</span>
        <span class="task-meta">${UI.taskTypeIcon(t.type)} ${UI.escapeHtml(UI.taskTypeLabel(t.type))} ${t.plant_name ? '· ' + UI.escapeHtml(t.plant_name) : ''} ${priorityBadge}</span>
      </div>
      <span class="badge task-badge ${overdue ? 'badge-danger' : (isToday(t) ? 'badge-warning' : 'badge-info')}">${UI.formatRelative(t.due_date)}</span>
      ${done ? '' : `<button class="btn btn-ghost btn-sm task-actions" data-complete="${t.id}">Wykonane</button>`}
    </div>`;
}

function isOverdue(t) {
  const dt = new Date(t.due_date.replace(' ', 'T'));
  const today = new Date(); today.setHours(0,0,0,0);
  return dt < today;
}
function isToday(t) {
  const dt = new Date(t.due_date.replace(' ', 'T'));
  const today = new Date(); today.setHours(0,0,0,0);
  const target = new Date(dt); target.setHours(0,0,0,0);
  return today.getTime() === target.getTime();
}
function isFuture(t) {
  const dt = new Date(t.due_date.replace(' ', 'T'));
  const today = new Date(); today.setHours(23,59,59,999);
  return dt > today;
}
