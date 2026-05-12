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
    let dropdown = null;
    const button = document.getElementById('btn-species-search');

    function close() { dropdown?.remove(); dropdown = null; }

    button?.addEventListener('click', async () => {
      const q = input.value.trim();
      if (q.length < 2) {
        close();
        UI.toast('Wpisz co najmniej 2 znaki nazwy gatunku.', 'warning');
        return;
      }

      button.disabled = true;
      const previousHtml = button.innerHTML;
      button.innerHTML = '<span class="loading"></span> Szukam';
      try {
        const r = await Species.search(q);
        renderDropdown(r);
      } catch (err) {
        console.warn('[species] search error', err);
        renderDropdown({ results: [] });
        UI.toast(err.message || 'Nie udało się sprawdzić gatunku w API', 'error');
      } finally {
        button.disabled = false;
        button.innerHTML = previousHtml;
      }
    });

    input.addEventListener('input', close);
    input.addEventListener('blur', () => setTimeout(close, 200));

    function renderDropdown(r) {
      close();
      dropdown = document.createElement('div');
      dropdown.style.cssText = 'position:absolute;background:#fff;border:1px solid var(--outline-variant);border-radius:12px;box-shadow:var(--shadow-2);z-index:100;max-height:280px;overflow:auto;width:100%;margin-top:4px';
      const wrap = input.closest('.species-lookup') || input.parentElement;
      wrap.style.position = 'relative';

      const meta = (r.source === 'mock' || r.source === 'mock-fallback')
        ? '<div style="padding:6px 12px;font-size:11px;color:var(--on-surface-variant);background:var(--surface-container);border-bottom:1px solid var(--outline-variant)">DEMO – Brak klucza Perenual API</div>'
        : '';

      if (!r.results || r.results.length === 0) {
        dropdown.innerHTML = meta + '<div style="padding:14px;color:var(--on-surface-variant);font-size:13px">Brak wyników. Możesz wpisać nazwę ręcznie.</div>';
      } else {
        dropdown.innerHTML = meta + r.results.map(s => `
          <div data-ext="${UI.escapeHtml(s.external_id)}" class="species-item" style="padding:10px 12px;cursor:pointer;border-bottom:1px solid var(--outline-variant)">
            <div style="font-weight:600">${UI.escapeHtml(s.common_name)}</div>
            <div style="font-size:12px;color:var(--on-surface-variant);font-style:italic">${UI.escapeHtml(s.scientific_name || '')}</div>
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
