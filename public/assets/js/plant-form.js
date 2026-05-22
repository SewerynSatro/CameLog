/* CameLog – formularz dodawania i edycji rośliny.
 * Integracja z Perenual API (przez backend).
 */

const PlantForm = {
  selectedSpecies: null,

  async renderCreate(_, user) {
    this.selectedSpecies = null;
    UI.renderShell({ active: '/plants', user, content: this._formHtml(null) });
    this._attach(null);
  },

  async renderEdit({ id }, user) {
    const content = `<div id="plant-form-root">${UI.loader()}</div>`;
    UI.renderShell({ active: '/plants', user, content });
    try {
      const r = await API.get('/api/plants/' + id);
      this.selectedSpecies = r.plant.species_id ? {
        local_id: r.plant.species_id,
        common_name: r.plant.species_common,
        scientific_name: r.plant.species_scientific,
        watering_info: r.plant.species_watering,
        sunlight_info: r.plant.species_sunlight,
        care_level: r.plant.species_care_level,
      } : null;
      document.getElementById('plant-form-root').outerHTML = this._formHtml(r.plant);
      this._attach(r.plant);
      if (this.selectedSpecies) this._renderApiPanel(this.selectedSpecies);
    } catch (err) {
      document.getElementById('plant-form-root').innerHTML = UI.empty({ title: 'Błąd', desc: err.message });
    }
  },

  _formHtml(p) {
    const editing = !!p;
    const v = (k) => p ? UI.escapeHtml(p[k] ?? '') : '';
    return `
      <section class="page-head">
        <h1>${editing ? 'Edytuj roślinę' : 'Dodaj nową roślinę'}</h1>
        <p>Wprowadź dane swojej ${editing ? 'rośliny' : 'nowej zielonej podopiecznej'} do systemu CameLog.</p>
      </section>

      <form id="plant-form" novalidate>
        <input type="hidden" name="species_id" value="${p?.species_id ?? ''}" />
        <input type="hidden" name="api_recommendations_used" value="${p?.api_recommendations_used ? '1' : '0'}" />

        <div class="card mb-3">
          <h2>${Icons.leaf} Podstawowe informacje</h2>
          <div class="form-grid mt-2">
            <div class="field">
              <label class="field-label" for="name">Nazwa rośliny *</label>
              <input id="name" name="name" class="input" placeholder="Np. Fikus Benjamina" required value="${v('name')}" />
            </div>
            <div class="field">
              <label class="field-label" for="species">Gatunek (Wyszukaj) *</label>
              <input id="species" name="custom_species_name" class="input" placeholder="np. Monstera Deliciosa" autocomplete="off" value="${UI.escapeHtml(p?.species_common || p?.custom_species_name || '')}" />
            </div>
            <div class="field">
              <label class="field-label" for="location">Lokalizacja</label>
              <select id="location" name="location" class="select">
                <option value="">Wybierz pomieszczenie…</option>
                ${['Salon','Sypialnia','Kuchnia','Łazienka','Biuro','Parapet','Balkon'].map(loc =>
                  `<option ${p?.location === loc ? 'selected' : ''}>${loc}</option>`).join('')}
              </select>
            </div>
            <div class="field">
              <label class="field-label" for="planted_at">Data posadzenia / zakupu</label>
              <input id="planted_at" name="planted_at" class="input" type="date" value="${p?.planted_at ? p.planted_at.substring(0,10) : ''}" />
            </div>
          </div>

          <div id="api-panel-slot" class="mt-3"></div>
        </div>

        <div class="card mb-3">
          <h2>${Icons.upload} Zdjęcie</h2>
          <div id="photo-drop" class="photo-drop">
            <div class="photo-drop-icon">${Icons.upload}</div>
            <div class="photo-drop-title">Przeciągnij i upuść zdjęcie rośliny</div>
            <div class="text-muted text-sm">lub kliknij, aby przeglądać pliki. JPG, PNG, max 5 MB.</div>
            <input id="photo-input" type="file" accept="image/*" hidden />
            <div id="photo-preview" class="mt-2"></div>
          </div>
        </div>

        <div class="card mb-3">
          <h2>${Icons.drop} Pielęgnacja</h2>
          <div class="form-grid mt-2">
            <div class="field">
              <label class="field-label">Częstotliwość podlewania (dni)</label>
              <input class="input" name="watering_interval_days" type="number" min="1" max="365" value="${p?.watering_interval_days ?? 7}" />
            </div>
            <div class="field">
              <label class="field-label">Częstotliwość nawożenia (dni)</label>
              <input class="input" name="fertilizing_interval_days" type="number" min="1" max="365" value="${p?.fertilizing_interval_days ?? 30}" />
            </div>
          </div>
          <div class="field mt-3">
            <label class="field-label">Trudność pielęgnacji</label>
            <div class="chip-group" id="care-level">
              ${['easy','medium','hard'].map(level => {
                const lbl = level === 'easy' ? 'Łatwa' : level === 'medium' ? 'Średnia' : 'Wymagająca';
                const active = (p?.care_level || 'easy') === level;
                return `<button type="button" class="chip ${active ? 'is-active' : ''}" data-care="${level}">${lbl}</button>`;
              }).join('')}
            </div>
            <input type="hidden" name="care_level" value="${p?.care_level || 'easy'}" />
          </div>
          ${editing ? '' : `
          <label class="checkbox mt-3">
            <input type="checkbox" name="auto_task" checked />
            <div>
              <div class="text-strong">Utwórz automatyczny task</div>
              <div class="text-muted text-sm">System automatycznie przypomni o podlewaniu i nawożeniu.</div>
            </div>
          </label>`}
        </div>

        <div class="card mb-3">
          <h2>${Icons.edit} Notatki</h2>
          <textarea name="notes" class="textarea mt-2" placeholder="Dodaj dodatkowe informacje, np. wymagania świetlne, historia szczepki…">${v('notes')}</textarea>
        </div>

        <div class="form-actions mt-4">
          <a href="${editing ? '/plants/'+p.id : '/plants'}" data-link class="btn btn-outline">Anuluj</a>
          <button type="submit" class="btn btn-primary">${Icons.check} ${editing ? 'Zapisz zmiany' : 'Zapisz roślinę'}</button>
        </div>
      </form>`;
  },

  _attach(plant) {
    const form = document.getElementById('plant-form');
    if (!form) return;

    // Care level chips
    form.querySelectorAll('[data-care]').forEach(btn => {
      btn.addEventListener('click', () => {
        form.querySelectorAll('[data-care]').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        form.querySelector('[name=care_level]').value = btn.dataset.care;
      });
    });

    // Species autocomplete
    Species.attachAutocomplete(form.querySelector('#species'), (sp) => {
      this.selectedSpecies = sp;
      this._renderApiPanel(sp);
    });

    // Photo upload (drop or click)
    const drop = document.getElementById('photo-drop');
    const input = document.getElementById('photo-input');
    const preview = document.getElementById('photo-preview');
    drop.addEventListener('click', () => input.click());
    drop.addEventListener('dragover', (e) => { e.preventDefault(); drop.style.background = 'var(--surface-container-high)'; });
    drop.addEventListener('dragleave', () => drop.style.background = 'var(--surface-container)');
    drop.addEventListener('drop', (e) => { e.preventDefault(); drop.style.background = 'var(--surface-container)'; if (e.dataTransfer.files[0]) { input.files = e.dataTransfer.files; renderPreview(); } });
    input.addEventListener('change', renderPreview);
    function renderPreview() {
      const f = input.files[0];
      if (!f) { preview.innerHTML = ''; return; }
      const url = URL.createObjectURL(f);
      preview.innerHTML = `<img src="${url}" alt="Podgląd zdjęcia" />`;
    }

    // Submit
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      // Jeśli wybrano gatunek z API – zaimportuj go lokalnie i dołącz id
      let speciesId = fd.get('species_id') || null;
      if (this.selectedSpecies && !speciesId) {
        try {
          const r = await Species.importToLocal({
            external_id: this.selectedSpecies.external_id || null,
            common_name: this.selectedSpecies.common_name,
            scientific_name: this.selectedSpecies.scientific_name,
            care_level: this.selectedSpecies.care_level,
            watering_info: this.selectedSpecies.watering_info,
            sunlight_info: this.selectedSpecies.sunlight_info,
            climate_info: this.selectedSpecies.climate_info,
          });
          speciesId = r.species_id;
        } catch (err) { console.warn('species import failed', err); }
      }

      const payload = {
        name: fd.get('name'),
        species_id: speciesId ? Number(speciesId) : null,
        custom_species_name: fd.get('custom_species_name'),
        location: fd.get('location') || null,
        planted_at: fd.get('planted_at') || null,
        notes: fd.get('notes') || null,
        watering_interval_days: fd.get('watering_interval_days') ? Number(fd.get('watering_interval_days')) : null,
        fertilizing_interval_days: fd.get('fertilizing_interval_days') ? Number(fd.get('fertilizing_interval_days')) : null,
        care_level: fd.get('care_level') || 'easy',
        api_recommendations_used: fd.get('api_recommendations_used') === '1',
        skip_auto_task: !fd.get('auto_task'),
      };

      try {
        let plantId;
        if (plant) {
          await API.put('/api/plants/' + plant.id, payload);
          plantId = plant.id;
          UI.toast('Zaktualizowano roślinę', 'success');
        } else {
          const r = await API.post('/api/plants', payload);
          plantId = r.plant.id;
          UI.toast('Roślina dodana 🌱', 'success');
        }

        // Upload zdjęcia jeśli wybrano
        if (input.files[0]) {
          const form = new FormData();
          form.append('photo', input.files[0]);
          try { await API.upload('/api/plants/' + plantId + '/photo', form); }
          catch (err) { UI.toast('Nie udało się przesłać zdjęcia: ' + (err.message || ''), 'error'); }
        }

        Router.navigate('/plants/' + plantId);
      } catch (err) {
        UI.toast(err.message || 'Błąd zapisu', 'error');
      }
    });
  },

  _renderApiPanel(sp) {
    const slot = document.getElementById('api-panel-slot');
    if (!slot) return;
    slot.innerHTML = `
      <div class="api-panel">
        <div class="api-panel-title">${Icons.sparkle} Zalecenia z bazy gatunków <span class="pill">Perenual</span></div>
        <div class="api-grid">
          <div class="api-cell"><div class="label-sm">Nazwa zwyczajowa</div>${UI.escapeHtml(sp.common_name || '—')}</div>
          <div class="api-cell"><div class="label-sm">Nazwa naukowa</div><em>${UI.escapeHtml(sp.scientific_name || '—')}</em></div>
          <div class="api-cell"><div class="label-sm">Poziom trudności</div>${UI.escapeHtml(careLabel(sp.care_level) || '—')}</div>
          <div class="api-cell"><div class="label-sm">Podlewanie</div>${sp.watering_interval_days ? `Co ${sp.watering_interval_days} dni` : (UI.escapeHtml(sp.watering_info || '—'))}</div>
          <div class="api-cell"><div class="label-sm">Nasłonecznienie</div>${UI.escapeHtml(sp.sunlight_info || '—')}</div>
          <div class="api-cell"><div class="label-sm">Typ uprawy</div>${UI.escapeHtml(sp.type || sp.cycle || '—')}</div>
        </div>
        ${sp.description ? `<div class="mt-3"><div class="label-sm">Opis pielęgnacji</div><p class="text-muted mt-1">${UI.escapeHtml(sp.description)}</p></div>` : ''}
        <div class="api-help">${Icons.warning}<span>To są zalecenia pobrane z zewnętrznej bazy gatunków. Możesz dostosować harmonogram pielęgnacji ręcznie.</span></div>
        <div class="api-actions">
          <button type="button" class="btn btn-tertiary" id="btn-apply-api">${Icons.check} Użyj zaleceń</button>
          <button type="button" class="btn btn-outline" id="btn-customize-api">Dostosuj ręcznie</button>
        </div>
      </div>`;

    document.getElementById('btn-apply-api').addEventListener('click', () => {
      const form = document.getElementById('plant-form');
      if (sp.watering_interval_days) form.querySelector('[name=watering_interval_days]').value = sp.watering_interval_days;
      if (sp.fertilizing_interval_days) form.querySelector('[name=fertilizing_interval_days]').value = sp.fertilizing_interval_days;
      if (sp.care_level) {
        form.querySelector('[name=care_level]').value = sp.care_level;
        form.querySelectorAll('[data-care]').forEach(b => b.classList.toggle('is-active', b.dataset.care === sp.care_level));
      }
      const notes = form.querySelector('[name=notes]');
      if (sp.description && !notes.value) notes.value = sp.description;
      form.querySelector('[name=api_recommendations_used]').value = '1';
      UI.toast('Zalecenia zastosowane. Możesz je dalej edytować.', 'success');
    });

    document.getElementById('btn-customize-api').addEventListener('click', () => {
      const form = document.getElementById('plant-form');
      form.querySelector('[name=api_recommendations_used]').value = '0';
      UI.toast('Zalecenia zachowane informacyjnie. Pola nie zostały nadpisane.', 'info');
    });
  },
};

function careLabel(c) {
  return ({ easy: 'Łatwa', medium: 'Średnia', hard: 'Wymagająca' })[c] || c || '';
}
