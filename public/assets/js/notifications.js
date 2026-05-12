/* CameLog – widok przypomnień. */

const Notifications = {
  async renderList(_, user) {
    const content = `
      <section class="page-head">
        <h1>${Icons.bell} Przypomnienia</h1>
        <p>Powiadomienia o pielęgnacji Twoich roślin.</p>
      </section>

      <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
        <button class="btn btn-outline" id="btn-mark-all">${Icons.doubleCheck} Oznacz wszystkie jako przeczytane</button>
      </div>

      <div class="two-col">
        <div id="notifs">${UI.loader()}</div>
        <aside>
          <div class="card mb-3">
            <h3>${Icons.shield} Tryby pielęgnacji</h3>
            <p class="text-muted mt-1">Włącz powiadomienia in-app dla codziennych aktywności.</p>
            <div class="mt-2 flex flex-col gap-2">
              <label class="checkbox"><input type="checkbox" checked /> Podlewanie</label>
              <label class="checkbox"><input type="checkbox" checked /> Nawożenie</label>
              <label class="checkbox"><input type="checkbox" /> Sezonowe (poza MVP)</label>
            </div>
          </div>
          <div class="card-tinted">
            <h3>${Icons.sparkle} Tip dnia</h3>
            <p class="text-muted mt-1">Sprawdzaj wilgotność podłoża palcem przed podlaniem – nie zawsze warto trzymać się sztywnego harmonogramu.</p>
          </div>
        </aside>
      </div>`;
    UI.renderShell({ active: '/notifications', user, content });

    document.getElementById('btn-mark-all').addEventListener('click', async () => {
      try {
        await API.patch('/api/notifications/read-all', {});
        UI.toast('Oznaczono wszystkie jako przeczytane', 'success');
        load();
      } catch (err) { UI.toast(err.message || 'Błąd', 'error'); }
    });

    async function load() {
      try {
        const r = await API.get('/api/notifications');
        const items = r.notifications || [];
        const today = items.filter(n => n.type === 'today');
        const overdue = items.filter(n => n.type === 'overdue');
        const incoming = items.filter(n => n.type === 'incoming');
        const system = items.filter(n => n.type === 'system');

        const sec = (label, icon, list, danger=false) => {
          if (list.length === 0) return '';
          return `
            <h2 class="section-header">${icon} ${label} <span class="count-pill ${danger ? 'danger' : ''}">${list.length}</span></h2>
            <div class="task-list mb-3">${list.map(notifCard).join('')}</div>`;
        };

        const node = document.getElementById('notifs');
        if (items.length === 0) {
          node.innerHTML = UI.empty({ title: 'Brak powiadomień', desc: 'Wszystko pod kontrolą! 🌵' });
          return;
        }
        node.innerHTML = `
          ${sec('Zaległe', Icons.warning, overdue, true)}
          ${sec('Dzisiaj', Icons.calendar, today)}
          ${sec('Nadchodzące', Icons.list, incoming)}
          ${sec('Systemowe', Icons.bell, system)}`;
      } catch (err) {
        document.getElementById('notifs').innerHTML = UI.empty({ title: 'Błąd', desc: err.message });
      }
    }

    document.addEventListener('click', async (e) => {
      const r = e.target.closest('[data-read]');
      if (!r) return;
      try {
        await API.patch('/api/notifications/' + r.dataset.read + '/read', {});
        load();
      } catch {}
    });

    load();
  },
};

function notifCard(n) {
  return `
    <div class="notif-card ${n.type === 'overdue' ? 'is-overdue' : ''}" style="${n.is_read ? 'opacity:0.6' : ''}">
      <div class="notif-icon">${UI.taskTypeIcon(n.task_type || 'custom')}</div>
      <div>
        <div class="notif-title">${UI.escapeHtml(n.title)}</div>
        <div class="notif-desc">${UI.escapeHtml(n.message || '')}</div>
        <div class="text-muted" style="font-size:12px;margin-top:4px">${UI.formatDate(n.created_at)}</div>
      </div>
      <div>
        ${!n.is_read ? `<button class="btn btn-ghost btn-sm" data-read="${n.id}">${Icons.check} Przeczytane</button>` : `<span class="badge badge-info">Przeczytane</span>`}
      </div>
    </div>`;
}
