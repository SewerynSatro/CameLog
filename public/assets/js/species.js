/* CameLog – wyszukiwanie gatunków przez backend (Perenual lub mock). */

const Species = {
  async search(query) {
    if (!query || query.trim().length < 2) return { source: 'empty', results: [] };
    return await API.get('/api/species/search?query=' + encodeURIComponent(query));
  },

  async detail(externalId) {
    return await API.get('/api/species/' + encodeURIComponent(externalId));
  },

  async importToLocal(species) {
    return await API.post('/api/species/import', species);
  },

  /**
   * Mocuje autouzupełnianie pod inputem.
   * onSelect(speciesDetail) – callback po wyborze.
   */
  attachAutocomplete(input, onSelect) {
    let timer = null;
    let dropdown = null;

    function close() { dropdown?.remove(); dropdown = null; }

    input.addEventListener('input', () => {
      clearTimeout(timer);
      const q = input.value.trim();
      if (q.length < 2) { close(); return; }
      timer = setTimeout(async () => {
        try {
          const r = await Species.search(q);
          renderDropdown(r);
        } catch (err) {
          console.warn('[species] search error', err);
          if (!dropdown) renderDropdown({ results: [] });
        }
      }, 250);
    });

    input.addEventListener('blur', () => setTimeout(close, 200));

    function renderDropdown(r) {
      close();
      dropdown = document.createElement('div');
      dropdown.className = 'species-dropdown';
      const wrap = input.parentElement;
      wrap.classList.add('input-wrap-relative');

      const meta = (r.source === 'mock' || r.source === 'mock-fallback')
        ? '<div class="species-dropdown-meta">DEMO – Brak klucza Perenual API</div>'
        : '';

      if (!r.results || r.results.length === 0) {
        dropdown.innerHTML = meta + '<div class="species-dropdown-empty">Brak wyników. Możesz wpisać nazwę ręcznie.</div>';
      } else {
        dropdown.innerHTML = meta + r.results.map(s => `
          <div data-ext="${UI.escapeHtml(s.external_id)}" class="species-item" role="option" tabindex="0">
            <div class="species-item-title">${UI.escapeHtml(s.common_name)}</div>
            <div class="species-item-sub">${UI.escapeHtml(s.scientific_name || '')}</div>
          </div>`).join('');
      }
      wrap.appendChild(dropdown);

      dropdown.addEventListener('mousedown', async (e) => {
        const item = e.target.closest('.species-item');
        if (!item) return;
        e.preventDefault();
        const ext = item.dataset.ext;
        try {
          const detail = await Species.detail(ext);
          input.value = detail.species.common_name;
          close();
          onSelect && onSelect(detail.species);
        } catch (err) {
          UI.toast('Nie udało się pobrać szczegółów gatunku', 'error');
        }
      });
    }
  },
};
