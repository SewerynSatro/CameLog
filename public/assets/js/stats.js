/* CameLog – widok statystyk. */

const Stats = {
  async render(_, user) {
    const content = `
      <section class="page-head">
        <h1>${Icons.stats} Statystyki</h1>
        <p>Twoja zielona oaza w liczbach – ostatnie 30 dni.</p>
      </section>
      <div id="stats-content">${UI.loader()}</div>`;
    UI.renderShell({ active: '/stats', user, content });

    try {
      const data = await API.get('/api/stats/overview');
      const o = data.overview || {};
      const tiles = [
        { label: 'Wykonane taski', value: o.tasks_done || 0, icon: Icons.check, cls: 'tile-success' },
        { label: 'Podlewania', value: o.watering_count || 0, icon: Icons.drop, cls: 'tile-primary' },
        { label: 'Nawożenia', value: o.fertilizing_count || 0, icon: Icons.flask, cls: 'tile-warning' },
        { label: 'Liczba roślin', value: o.plants_count || 0, icon: Icons.plants, cls: '' },
      ];
      const tileHtml = tiles.map(t => `
        <div class="stat-tile ${t.cls}">
          <div style="display:flex;align-items:center;justify-content:space-between">
            <div class="stat-label">${UI.escapeHtml(t.label)}</div>
            <div class="stat-icon">${t.icon}</div>
          </div>
          <div class="stat-value">${t.value}</div>
        </div>`).join('');

      const series = o.daily_activity || [];
      const max = Math.max(1, ...series.map(s => s.count));

      const node = document.getElementById('stats-content');
      node.innerHTML = `
        <div class="stat-grid">${tileHtml}</div>

        <div class="two-col">
          <div class="card">
            <h2>${Icons.history} Aktywność w ostatnich 30 dniach</h2>
            <canvas id="chart" class="chart-canvas mt-2" height="220"></canvas>
            <p class="text-muted mt-2" style="font-size:13px">Łączna liczba akcji pielęgnacyjnych dziennie.</p>
          </div>
          <aside>
            <div class="card mb-3">
              <h2>Procentowy rozkład</h2>
              <div class="mt-2">
                ${renderTypeBars(o.type_breakdown || [])}
              </div>
            </div>
            <div class="card-tinted">
              <h3>${Icons.leaf} Top roślina</h3>
              <p class="mt-1">${o.top_plant ? UI.escapeHtml(o.top_plant.name) + ' – ' + o.top_plant.count + ' akcji' : 'Zarejestruj akcje opieki, aby zobaczyć ranking.'}</p>
            </div>
          </aside>
        </div>`;

      drawChart(document.getElementById('chart'), series, max);
    } catch (err) {
      document.getElementById('stats-content').innerHTML = UI.empty({ title: 'Błąd', desc: err.message });
    }
  },
};

function renderTypeBars(breakdown) {
  if (!breakdown.length) return '<p class="text-muted">Brak aktywności.</p>';
  const total = breakdown.reduce((a, b) => a + Number(b.count), 0) || 1;
  return breakdown.map(b => {
    const pct = Math.round((Number(b.count) / total) * 100);
    return `
      <div class="mb-2">
        <div style="display:flex;justify-content:space-between;font-size:13px">
          <span>${UI.escapeHtml(UI.taskTypeLabel(b.type))}</span>
          <strong>${pct}% (${b.count})</strong>
        </div>
        <div class="bar mt-1"><span style="width:${pct}%"></span></div>
      </div>`;
  }).join('');
}

function drawChart(canvas, series, max) {
  if (!canvas || !canvas.getContext) return;
  const dpr = window.devicePixelRatio || 1;
  const W = canvas.clientWidth, H = canvas.clientHeight;
  canvas.width = W * dpr; canvas.height = H * dpr;
  const ctx = canvas.getContext('2d');
  ctx.scale(dpr, dpr);
  ctx.clearRect(0, 0, W, H);

  if (!series.length) {
    ctx.fillStyle = '#544438';
    ctx.font = '14px Manrope';
    ctx.fillText('Brak danych', 16, 30);
    return;
  }

  const pad = { l: 32, r: 16, t: 16, b: 28 };
  const cw = W - pad.l - pad.r;
  const ch = H - pad.t - pad.b;
  const step = cw / Math.max(1, series.length - 1);

  // gridlines
  ctx.strokeStyle = '#d9c2b3'; ctx.lineWidth = 1;
  ctx.font = '11px Manrope'; ctx.fillStyle = '#544438';
  for (let g = 0; g <= 4; g++) {
    const y = pad.t + (ch * g / 4);
    ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(W - pad.r, y); ctx.stroke();
    const v = Math.round(max * (1 - g / 4));
    ctx.fillText(v, 4, y + 4);
  }

  // line + area
  ctx.beginPath();
  series.forEach((p, i) => {
    const x = pad.l + i * step;
    const y = pad.t + ch * (1 - p.count / max);
    if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
  });
  ctx.lineTo(pad.l + (series.length - 1) * step, pad.t + ch);
  ctx.lineTo(pad.l, pad.t + ch);
  ctx.closePath();
  const grad = ctx.createLinearGradient(0, pad.t, 0, pad.t + ch);
  grad.addColorStop(0, 'rgba(143,74,0,0.25)');
  grad.addColorStop(1, 'rgba(143,74,0,0.02)');
  ctx.fillStyle = grad; ctx.fill();

  ctx.beginPath();
  series.forEach((p, i) => {
    const x = pad.l + i * step;
    const y = pad.t + ch * (1 - p.count / max);
    if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
  });
  ctx.strokeStyle = '#8f4a00'; ctx.lineWidth = 2.5; ctx.stroke();

  // dots
  ctx.fillStyle = '#8f4a00';
  series.forEach((p, i) => {
    const x = pad.l + i * step;
    const y = pad.t + ch * (1 - p.count / max);
    ctx.beginPath(); ctx.arc(x, y, 3, 0, Math.PI * 2); ctx.fill();
  });

  // x labels (co 5 dni)
  ctx.fillStyle = '#544438';
  series.forEach((p, i) => {
    if (i % 5 !== 0 && i !== series.length - 1) return;
    const x = pad.l + i * step;
    const lbl = (p.day || '').substring(5);
    ctx.fillText(lbl, x - 12, H - 8);
  });
}
