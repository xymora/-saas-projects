<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'dueno']);
$pageTitle = 'Reportes — Mi Boutique';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <span class="topbar-title">📈 Reportes Financieros</span>
    <div class="topbar-actions">
      <select id="rep-periodo" class="form-control" style="width:auto" onchange="loadAll()">
        <option value="diario">Hoy</option>
        <option value="semanal">Esta semana</option>
        <option value="mensual" selected>Este mes</option>
        <option value="bimestral">Bimestral</option>
        <option value="semestral">Semestral</option>
        <option value="anual">Anual</option>
      </select>
    </div>
  </header>

  <div class="page-body">
    <div id="rep-range" class="text-muted text-sm mb-4"></div>

    <!-- KPIs -->
    <div class="stats-grid">
      <div class="stat-card brand"><div class="stat-label">Ingresos brutos</div><div class="stat-value" id="r-ingresos">—</div><div class="stat-sub" id="r-ingresos-prev"></div></div>
      <div class="stat-card success"><div class="stat-label">Ganancia real</div><div class="stat-value" id="r-ganancia">—</div><div class="stat-sub" id="r-ganancia-prev"></div></div>
      <div class="stat-card warning"><div class="stat-label">Costo total</div><div class="stat-value" id="r-costo">—</div><div class="stat-sub" id="r-costo-prev"></div></div>
      <div class="stat-card accent"><div class="stat-label">Nº Ventas</div><div class="stat-value" id="r-num">—</div><div class="stat-sub" id="r-num-prev"></div></div>
      <div class="stat-card danger"><div class="stat-label">Comisiones Clip</div><div class="stat-value" id="r-clip">—</div><div class="stat-sub">3.6% + IVA aplicado</div></div>
    </div>

    <!-- Gráficas -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px">
      <div class="card">
        <div class="card-title">Ingresos vs Ganancia (por día)</div>
        <canvas id="chart-comp" height="200"></canvas>
      </div>
      <div class="card">
        <div class="card-title">Métodos de pago</div>
        <canvas id="chart-pay" height="200"></canvas>
        <div id="pay-breakdown" class="mt-3"></div>
      </div>
    </div>

    <!-- Contrafecha año anterior -->
    <div class="card mb-4">
      <div class="card-title">Comparativa año anterior</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <div>
          <div style="font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px">Este periodo</div>
          <div class="d-flex justify-between text-sm"><span>Ingresos</span><strong id="cmp-ing-a">—</strong></div>
          <div class="d-flex justify-between text-sm"><span>Ganancia</span><strong id="cmp-gan-a">—</strong></div>
          <div class="d-flex justify-between text-sm"><span>Ventas</span><strong id="cmp-num-a">—</strong></div>
        </div>
        <div>
          <div style="font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px">Mismo periodo año anterior</div>
          <div class="d-flex justify-between text-sm"><span>Ingresos</span><strong id="cmp-ing-b">—</strong></div>
          <div class="d-flex justify-between text-sm"><span>Ganancia</span><strong id="cmp-gan-b">—</strong></div>
          <div class="d-flex justify-between text-sm"><span>Ventas</span><strong id="cmp-num-b">—</strong></div>
        </div>
      </div>
      <div id="cmp-delta" class="mt-3 alert" style="display:none"></div>
    </div>

    <!-- Ranking inventario -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
      <div class="card">
        <div class="card-title">🏆 Más vendidos</div>
        <div class="table-wrap">
          <table><thead><tr><th>#</th><th>Producto</th><th>Uds.</th><th>Ganancia</th></tr></thead>
          <tbody id="top-table"></tbody></table>
        </div>
      </div>
      <div class="card">
        <div class="card-title">🐌 Menos vendidos</div>
        <div class="table-wrap">
          <table><thead><tr><th>#</th><th>Producto</th><th>Uds.</th><th>Stock</th></tr></thead>
          <tbody id="bot-table"></tbody></table>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>

let chartComp, chartPay;

async function loadAll() {
  const periodo = document.getElementById('rep-periodo').value;
  try {
    const [res, inv] = await Promise.all([
      fetch(`${BASE}/api/reportes.php?action=resumen&periodo=${periodo}`, {credentials:'same-origin'}).then(r=>r.json()),
      fetch(`${BASE}/api/reportes.php?action=inventario&periodo=${periodo}`, {credentials:'same-origin'}).then(r=>r.json()),
    ]);

    if (!res.success) throw new Error(res.error);
    const a = res.actual, p = res.anterior;
    document.getElementById('rep-range').textContent = `Período: ${res.desde} → ${res.hasta}`;

    // KPIs
    document.getElementById('r-ingresos').textContent = fmt.money(a.ingresos);
    document.getElementById('r-ingresos-prev').textContent = `Año ant.: ${fmt.money(p.ingresos)}`;
    document.getElementById('r-ganancia').textContent = fmt.money(a.ganancia_real);
    document.getElementById('r-ganancia-prev').textContent = `Año ant.: ${fmt.money(p.ganancia_real)}`;
    document.getElementById('r-costo').textContent = fmt.money(a.costo_total);
    document.getElementById('r-costo-prev').textContent = `Año ant.: ${fmt.money(p.costo_total)}`;
    document.getElementById('r-num').textContent = a.total_ventas;
    document.getElementById('r-num-prev').textContent = `Año ant.: ${p.total_ventas}`;
    document.getElementById('r-clip').textContent = fmt.money(a.comisiones_clip);

    // Comparativa
    document.getElementById('cmp-ing-a').textContent = fmt.money(a.ingresos);
    document.getElementById('cmp-gan-a').textContent = fmt.money(a.ganancia_real);
    document.getElementById('cmp-num-a').textContent = a.total_ventas;
    document.getElementById('cmp-ing-b').textContent = fmt.money(p.ingresos);
    document.getElementById('cmp-gan-b').textContent = fmt.money(p.ganancia_real);
    document.getElementById('cmp-num-b').textContent = p.total_ventas;

    const pct = p.ingresos > 0 ? ((a.ingresos - p.ingresos) / p.ingresos * 100).toFixed(1) : 0;
    const delta = document.getElementById('cmp-delta');
    delta.style.display = 'block';
    if (pct > 0) { delta.className='alert alert-success'; delta.textContent = `▲ +${pct}% en ingresos vs año anterior`; }
    else if (pct < 0) { delta.className='alert alert-danger'; delta.textContent = `▼ ${pct}% en ingresos vs año anterior`; }
    else { delta.className='alert alert-info'; delta.textContent = `Sin variación vs año anterior`; }

    // Gráfica ingresos vs ganancia
    const dias  = res.por_dia || [];
    if (chartComp) chartComp.destroy();
    chartComp = new Chart(document.getElementById('chart-comp'), {
      type: 'bar',
      data: {
        labels: dias.map(d => d.fecha),
        datasets: [
          { label: 'Ingresos', data: dias.map(d => parseFloat(d.total)), backgroundColor: 'rgba(200,149,108,.7)', borderRadius: 4 },
        ]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
    });

    // Métodos pago
    const metodos = res.metodos || [];
    if (chartPay) chartPay.destroy();
    chartPay = new Chart(document.getElementById('chart-pay'), {
      type: 'doughnut',
      data: {
        labels: metodos.map(m => m.metodo_pago === 'clip' ? 'Clip' : 'Efectivo'),
        datasets: [{ data: metodos.map(m => parseFloat(m.monto)), backgroundColor: ['#10b981','#c8956c'], borderWidth: 0 }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    document.getElementById('pay-breakdown').innerHTML = metodos.map(m =>
      `<div class="d-flex justify-between text-sm"><span>${m.metodo_pago==='clip'?'💳 Clip':'💵 Efectivo'} (${m.num})</span><strong>${fmt.money(m.monto)}</strong></div>`
    ).join('');

    // Ranking
    if (inv.success) {
      const ranking = inv.ranking || [];
      const top = ranking.slice(0, 10);
      const bot = [...ranking].reverse().slice(0, 10);
      document.getElementById('top-table').innerHTML = top.map((r, i) =>
        `<tr>
          <td>${i+1}</td>
          <td>
            <div style="font-size:12px;font-weight:600">${r.marca} ${r.modelo}</div>
            <div style="font-size:11px;margin-top:2px">
              <span style="background:var(--gray-100);padding:1px 6px;border-radius:4px;margin-right:3px">${r.color}</span>
              <span style="background:var(--gray-100);padding:1px 6px;border-radius:4px">T: ${r.talla}</span>
            </div>
            <div class="text-xs text-muted" style="margin-top:2px">${r.sku}</div>
          </td>
          <td>${r.unidades}</td>
          <td class="text-success fw-bold">${fmt.money(r.ganancia_real)}</td>
        </tr>`
      ).join('') || '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--gray-400)">Sin datos</td></tr>';
      document.getElementById('bot-table').innerHTML = bot.map((r, i) =>
        `<tr>
          <td>${i+1}</td>
          <td>
            <div style="font-size:12px;font-weight:600">${r.marca} ${r.modelo}</div>
            <div style="font-size:11px;margin-top:2px">
              <span style="background:var(--gray-100);padding:1px 6px;border-radius:4px;margin-right:3px">${r.color}</span>
              <span style="background:var(--gray-100);padding:1px 6px;border-radius:4px">T: ${r.talla}</span>
            </div>
            <div class="text-xs text-muted" style="margin-top:2px">${r.sku}</div>
          </td>
          <td>${r.unidades || 0}</td>
          <td><span class="badge ${r.stock_actual <= 2 ? 'badge-danger' : 'badge-success'}">${r.stock_actual}</span></td>
        </tr>`
      ).join('') || '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--gray-400)">Sin datos</td></tr>';
    }

  } catch(e) { Toast.error('Error: ' + e.message); }
}

loadAll();
</script>
</body>
</html>
